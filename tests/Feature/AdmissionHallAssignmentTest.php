<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers AdmissionService::autoAssignHall(): triggered when an admin uploads an
 * applicant's passport photo, drawing only from halls already in real use (no
 * capacity/Hall model in this app), balanced toward whichever hall(s) are currently
 * least full, and never overriding a hall a staff member already set by hand.
 */
class AdmissionHallAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        // See AdmissionLetterBillingTest for why: this app's migration history has
        // MySQL-only DDL that sqlite (phpunit.xml's default test connection) cannot
        // run at all. Point at the real, already-migrated MySQL database instead;
        // DatabaseTransactions rolls back everything this test does.
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        putenv('DB_DATABASE=slceedugh_transcript_db_june_25');
        $_ENV['DB_DATABASE'] = 'slceedugh_transcript_db_june_25';

        parent::setUp();

        Mail::fake();
        Storage::fake('local');
    }

    protected function officer(): User
    {
        return User::role('Super Admin')->firstOrFail();
    }

    protected function importAdmission(): Admission
    {
        return AdmissionService::import([
            'full_name' => 'Hall Test Applicant ' . uniqid(),
            'email' => null,
            'phone' => null,
            'programme_id' => Programme::firstOrFail()->id,
            'academic_year_id' => AcademicYear::firstOrFail()->id,
            'level' => 100,
            'gender' => 'Female',
            'date_of_birth' => '2005-01-01',
        ], $this->officer());
    }

    /**
     * The real pool of halls autoAssignHall() balances against - same source used by
     * the admissions index/show hall dropdown.
     */
    protected function realHallPool(): array
    {
        return Student::whereNotNull('hall')->where('hall', '!=', '')->distinct()->pluck('hall')->all();
    }

    public function test_uploading_a_photo_through_the_real_route_auto_assigns_a_hall(): void
    {
        $this->assertNotEmpty($this->realHallPool(), 'Need at least one real hall on the Student table for this test to mean anything.');

        $officer = $this->officer();
        $admission = $this->importAdmission();
        $this->assertNull($admission->fresh()->hall);

        $response = $this->actingAs($officer)->post(route('admissions.photo.update', $admission), [
            'passport_photo' => UploadedFile::fake()->image('passport.jpg'),
        ]);

        $response->assertRedirect(route('admissions.show', $admission));
        $response->assertSessionHas('success', function ($message) {
            return str_contains($message, 'Automatically assigned to');
        });

        $admission->refresh();
        $this->assertNotNull($admission->hall);
        $this->assertContains($admission->hall, $this->realHallPool());
    }

    public function test_auto_assign_hall_does_not_override_a_hall_already_set(): void
    {
        $admission = $this->importAdmission();
        $pool = $this->realHallPool();
        $manuallyAssigned = $pool[0];

        AdmissionService::assignHall($admission, $manuallyAssigned, $this->officer());
        $admission->refresh();

        $result = AdmissionService::autoAssignHall($admission, $this->officer());

        $this->assertSame($manuallyAssigned, $result->hall);
    }

    public function test_auto_assign_hall_never_picks_a_hall_outside_the_real_pool(): void
    {
        $pool = $this->realHallPool();

        for ($i = 0; $i < 5; $i++) {
            $admission = $this->importAdmission();
            $result = AdmissionService::autoAssignHall($admission, $this->officer());

            $this->assertNotNull($result->hall);
            $this->assertContains($result->hall, $pool);
        }
    }

    public function test_auto_assign_hall_favors_the_currently_least_full_hall(): void
    {
        // For each assignment, compute the real min combined (Student + Admission) count
        // per hall *before* assigning, then assert the hall actually chosen was one of
        // the tied-for-least-full halls at that moment - proving it balances rather than
        // picking arbitrarily.
        for ($i = 0; $i < 5; $i++) {
            $counts = $this->combinedHallCounts();
            $minCount = min($counts);
            $leastFullHalls = array_keys(array_filter($counts, fn ($count) => $count === $minCount));

            $admission = $this->importAdmission();
            $result = AdmissionService::autoAssignHall($admission, $this->officer());

            $this->assertContains(
                $result->hall,
                $leastFullHalls,
                "Expected the newly assigned hall to be among the least-full halls at the time of assignment."
            );
        }
    }

    /**
     * @return array<string, int> hall name => combined Student + Admission count
     */
    protected function combinedHallCounts(): array
    {
        $studentCounts = Student::whereNotNull('hall')->where('hall', '!=', '')
            ->selectRaw('hall, count(*) as total')->groupBy('hall')->pluck('total', 'hall');

        $admissionCounts = Admission::whereNotNull('hall')->where('hall', '!=', '')
            ->selectRaw('hall, count(*) as total')->groupBy('hall')->pluck('total', 'hall');

        $counts = [];
        foreach ($this->realHallPool() as $hall) {
            $counts[$hall] = (int) ($studentCounts[$hall] ?? 0) + (int) ($admissionCounts[$hall] ?? 0);
        }

        return $counts;
    }
}
