<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\AdmissionLetterTemplate;
use App\Models\Programme;
use App\Models\User;
use App\Services\AdmissionService;
use App\Mail\ApplicantCredentialsMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Covers the two rules this test was asked to check:
 *  1. A bill Accounts raises against an admission shows up correctly on that
 *     applicant's admission letter (via AdmissionLetterTemplate's
 *     {{total_billed}}/{{outstanding_balance}}/{{bill_items}} placeholders).
 *  2. Billing - and the letter content itself - is scoped per academic year:
 *     billing one admission never leaks into another year's totals, and a
 *     custom letter saved for one academic year doesn't affect a different one.
 */
class AdmissionLetterBillingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        // phpunit.xml points the default test run at a sqlite :memory: database so
        // RefreshDatabase-based tests are fast - but this app's migration history
        // contains raw MySQL-only DDL (an older migration runs `ALTER TABLE ...
        // MODIFY ...`), which sqlite cannot execute at all. Point this test back at
        // the real, already-migrated MySQL database instead; DatabaseTransactions
        // (used below) wraps every test method in a transaction that is rolled back
        // when the test ends, so nothing here is ever actually persisted.
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        putenv('DB_DATABASE=slceedugh_transcript_db_june_25');
        $_ENV['DB_DATABASE'] = 'slceedugh_transcript_db_june_25';

        parent::setUp();

        // Never actually send an email during a test run.
        Mail::fake();
    }

    protected function officer(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    protected function importAdmission(Programme $programme, AcademicYear $academicYear): Admission
    {
        return AdmissionService::import([
            'full_name' => 'Test Applicant ' . uniqid(),
            'email' => null,
            'phone' => null,
            'programme_id' => $programme->id,
            'academic_year_id' => $academicYear->id,
            'level' => 100,
            'gender' => 'Female',
            'date_of_birth' => '2005-01-01',
        ], $this->officer());
    }

    public function test_accounts_bill_reflects_on_the_admission_letter(): void
    {
        $programme = Programme::firstOrFail();
        $academicYear = AcademicYear::firstOrFail();
        $officer = $this->officer();

        $admission = $this->importAdmission($programme, $academicYear);

        // Before any bill exists, the letter must not claim an amount is owed.
        $letterBeforeBilling = AdmissionLetterTemplate::bodyFor($admission->fresh(), false);
        $this->assertStringContainsString('GH&cent;0.00', $letterBeforeBilling);

        // Two distinct real FeeCategory slugs, so the bill-items summary genuinely
        // exercises two different category labels rather than the same one twice.
        AdmissionService::addBillItem($admission, ['category' => 'tuition', 'description' => 'School Fees', 'amount' => 3000], $officer);
        AdmissionService::addBillItem($admission, ['category' => 'library', 'description' => 'Library Fee', 'amount' => 1000], $officer);
        $admission->refresh();

        $this->assertEquals(4000.0, $admission->totalBilled());
        $this->assertEquals(4000.0, $admission->outstandingBalance());

        // The letter must now show the real billed figures, not the placeholder tokens.
        $letterAfterBilling = AdmissionLetterTemplate::bodyFor($admission->fresh(), false);
        $this->assertStringContainsString('GH&cent;4,000.00', $letterAfterBilling);
        $this->assertStringNotContainsString('{{total_billed}}', $letterAfterBilling);
        $this->assertStringNotContainsString('{{outstanding_balance}}', $letterAfterBilling);
        $this->assertStringContainsString('Tuition / School Fees (GH¢3,000.00)', $letterAfterBilling);
        $this->assertStringContainsString('Library (GH¢1,000.00)', $letterAfterBilling);

        // Record, verify, and confirm full payment - the letter's outstanding balance
        // must drop to zero and the payment gate must actually open.
        $payment = AdmissionService::recordPayment($admission, [
            'amount' => 4000,
            'payment_method' => 'Mobile Money',
        ], $officer);
        AdmissionService::verifyPayment($payment, $officer);
        AdmissionService::confirmPayment($payment, $officer);
        $admission->refresh();

        $this->assertSame(Admission::PAYMENT_CONFIRMED, $admission->payment_status);
        $this->assertTrue(\App\Services\AdmissionEligibilityService::canProceedToProcessing($admission));

        $letterAfterPayment = AdmissionLetterTemplate::bodyFor($admission->fresh(), false);
        $this->assertStringContainsString('GH&cent;0.00', $letterAfterPayment);
    }

    public function test_accounts_can_bill_a_specific_academic_year_independently(): void
    {
        $programme = Programme::firstOrFail();
        $officer = $this->officer();

        $years = AcademicYear::orderBy('start_date')->take(2)->get();
        $this->assertGreaterThanOrEqual(2, $years->count(), 'Need at least two academic years seeded to test year-scoping.');

        [$yearOne, $yearTwo] = $years;

        $admissionYearOne = $this->importAdmission($programme, $yearOne);
        $admissionYearTwo = $this->importAdmission($programme, $yearTwo);

        AdmissionService::addBillItem($admissionYearOne, ['category' => 'tuition', 'description' => 'School Fees', 'amount' => 2500], $officer);
        AdmissionService::addBillItem($admissionYearTwo, ['category' => 'tuition', 'description' => 'School Fees', 'amount' => 3500], $officer);

        $admissionYearOne->refresh();
        $admissionYearTwo->refresh();

        // Billing one admission/year must not leak into the other's totals.
        $this->assertEquals(2500.0, $admissionYearOne->totalBilled());
        $this->assertEquals(3500.0, $admissionYearTwo->totalBilled());

        // A letter template saved for year one only must not affect year two's letter.
        AdmissionLetterTemplate::updateOrCreate(
            ['academic_year_id' => $yearOne->id],
            ['provisional_body' => '<p>CUSTOM YEAR ONE TEXT: billed GH&cent;{{total_billed}}</p>', 'updated_by' => $officer->id]
        );

        $letterYearOne = AdmissionLetterTemplate::bodyFor($admissionYearOne->fresh(), false);
        $letterYearTwo = AdmissionLetterTemplate::bodyFor($admissionYearTwo->fresh(), false);

        $this->assertStringContainsString('CUSTOM YEAR ONE TEXT', $letterYearOne);
        $this->assertStringContainsString('GH&cent;2,500.00', $letterYearOne);

        $this->assertStringNotContainsString('CUSTOM YEAR ONE TEXT', $letterYearTwo);
        $this->assertStringContainsString('GH&cent;3,500.00', $letterYearTwo);
    }

    public function test_applicant_receives_their_login_credentials_by_email_on_admission(): void
    {
        $programme = Programme::firstOrFail();
        $academicYear = AcademicYear::firstOrFail();
        $officer = $this->officer();
        $email = 'applicant.' . uniqid() . '@example-test.invalid';

        $admission = AdmissionService::import([
            'full_name' => 'Email Test Applicant',
            'email' => $email,
            'phone' => null,
            'programme_id' => $programme->id,
            'academic_year_id' => $academicYear->id,
            'level' => 100,
            'gender' => 'Male',
            'date_of_birth' => '2005-01-01',
        ], $officer);

        // Exactly one credentials email, addressed to the applicant's own email, and it
        // must actually carry their real login (applicant number + generated password) -
        // not a blank/templated placeholder.
        Mail::assertSentCount(1);
        Mail::assertSent(ApplicantCredentialsMail::class, function (ApplicantCredentialsMail $mail) use ($admission, $email) {
            return $mail->hasTo($email)
                && $mail->admission->id === $admission->id
                && $mail->temporaryPassword === $admission->generated_password
                && $mail->temporaryPassword !== '';
        });

        // The service must also report the send succeeded, since the controller's flash
        // message to the officer depends on this flag.
        $this->assertTrue($admission->credentials_emailed);

        // The rendered email itself must contain the applicant's real number and password,
        // and a working login link - not empty placeholders.
        $rendered = (new ApplicantCredentialsMail($admission, $admission->generated_password))->render();
        $this->assertStringContainsString($admission->applicant_number, $rendered);
        $this->assertStringContainsString($admission->generated_password, $rendered);
        $this->assertStringContainsString(route('applicant.login'), $rendered);

        // An applicant with no email on file must not trigger a send attempt at all.
        Mail::fake();
        $admissionNoEmail = AdmissionService::import([
            'full_name' => 'No Email Applicant',
            'email' => null,
            'phone' => null,
            'programme_id' => $programme->id,
            'academic_year_id' => $academicYear->id,
            'level' => 100,
            'gender' => 'Female',
            'date_of_birth' => '2005-01-01',
        ], $officer);

        Mail::assertNothingSent();
        $this->assertFalse($admissionNoEmail->credentials_emailed);
    }
}
