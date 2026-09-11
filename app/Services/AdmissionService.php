<?php

namespace App\Services;

use App\Mail\ApplicantCredentialsMail;
use App\Models\Admission;
use App\Models\AdmissionAuditLog;
use App\Models\AdmissionBillItem;
use App\Models\AdmissionPayment;
use App\Models\AdmissionPhotoAudit;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Business logic and the immutable audit trail for the admission workflow. Mirrors
 * StsPlacementService's static-method-plus-DB::transaction shape.
 */
class AdmissionService
{
    /**
     * Create a new admission record and its linked applicant login account.
     *
     * $applicantNumber lets a bulk import (see AdmissionImport) supply the institution's
     * own already-issued number directly instead of generating an internal APPyyyynnnn
     * one - real bulk admission lists come with a 7-digit number already assigned, which
     * doubles as both the login id and (via $referenceNumber, usually the same value)
     * the eventual student reference/index number.
     */
    public static function import(array $data, User $officer, ?string $applicantNumber = null, ?string $referenceNumber = null): Admission
    {
        $admission = DB::transaction(function () use ($data, $officer, $applicantNumber, $referenceNumber) {
            $admission = Admission::create(array_merge($data, [
                'applicant_number' => $applicantNumber ?: self::generateApplicantNumber(),
                'reference_number' => $referenceNumber ?: ($data['reference_number'] ?? null),
                'admission_status' => Admission::STATUS_OFFERED,
                'payment_status' => Admission::PAYMENT_NOT_BILLED,
                'imported_by' => $officer->id,
            ]));

            $temporaryPassword = str()->random(10);

            $user = User::create([
                'name' => $admission->full_name,
                'email' => $admission->email ?? ($admission->applicant_number . '@applicants.slce.local'),
                'password' => Hash::make($temporaryPassword),
                'role' => User::ROLE_APPLICANT,
                'admission_id' => $admission->id,
                'first_login' => true,
            ]);

            self::logAction($admission, 'admission.imported', null, Admission::STATUS_OFFERED, $officer);
            ActivityLogger::log('admission.imported', "Imported applicant {$admission->applicant_number} ({$admission->full_name})");

            // Stashed on the model instance only (never persisted) so the controller can
            // hand the officer a one-time slip with the applicant's first login password.
            // syncOriginal() keeps Eloquent's dirty-tracking from treating these as real
            // columns to write back - without it, any *later* ->update() call on this same
            // instance (e.g. a subsequent autoAssignHall()) fails with "column not found".
            $admission->setAttribute('generated_password', $temporaryPassword);
            $admission->setRelation('user', $user);
            $admission->syncOriginal();

            return $admission;
        });

        // Sent after the transaction commits - a mail failure (bad SMTP, unreachable
        // network) must never roll back an otherwise-successful import. Synchronous, not
        // queued: this app has no queue worker running, so a queued mail would just sit
        // in the `jobs` table and never reach the applicant.
        $admission->setAttribute('credentials_emailed', false);
        $admission->syncOriginal();

        if ($admission->email) {
            try {
                Mail::to($admission->email)->send(new ApplicantCredentialsMail($admission, $admission->generated_password));
                $admission->setAttribute('credentials_emailed', true);
                $admission->syncOriginal();
            } catch (\Throwable $e) {
                \Log::error('Failed to email applicant credentials', [
                    'admission_id' => $admission->id,
                    'email' => $admission->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $admission;
    }

    /**
     * Generate a unique applicant login number, e.g. APP20260001. Purely an internal
     * tracking/login id - distinct from the institution's own reference_number, which
     * staff enter separately (see Admission::reference_number).
     */
    public static function generateApplicantNumber(): string
    {
        $year = now()->format('Y');
        $sequence = Admission::whereYear('created_at', now()->year)->count() + 1;

        do {
            $candidate = 'APP' . $year . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Admission::where('applicant_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Store a new passport photo on the protected `local` disk, auditing the
     * replacement if one already existed.
     */
    public static function uploadPhoto(Admission $admission, UploadedFile $file, User $officer, ?string $reason = null): Admission
    {
        return DB::transaction(function () use ($admission, $file, $officer, $reason) {
            $previousPath = $admission->passport_photo;
            $newPath = $file->store('admission-photos', 'local');

            if ($previousPath) {
                AdmissionPhotoAudit::create([
                    'admission_id' => $admission->id,
                    'previous_photo_path' => $previousPath,
                    'new_photo_path' => $newPath,
                    'changed_by' => $officer->id,
                    'reason' => $reason,
                ]);
                Storage::disk('local')->delete($previousPath);
            }

            $admission->update(['passport_photo' => $newPath]);

            self::logAction($admission, $previousPath ? 'admission.photo.replaced' : 'admission.photo.uploaded', null, null, $officer, reason: $reason);

            return $admission->fresh();
        });
    }

    /**
     * Add a bill line item (School Fees, Admission Fee, Accommodation, etc.) and
     * recompute the admission's aggregate payment status.
     */
    public static function addBillItem(Admission $admission, array $data, User $staff): AdmissionBillItem
    {
        return DB::transaction(function () use ($admission, $data, $staff) {
            $item = AdmissionBillItem::create(array_merge($data, [
                'admission_id' => $admission->id,
                'created_by' => $staff->id,
            ]));

            self::recalculatePaymentStatus($admission->fresh());
            self::logAction($admission, 'admission.bill_item.added', null, null, $staff, amount: $item->amount, reference: $item->category);
            ActivityLogger::log('admission.bill_item.added', "Billed {$admission->applicant_number} {$item->amount} for {$item->category}");

            return $item;
        });
    }

    /**
     * Bill the same set of items (e.g. "School Fees GH¢3,000" + "Admission Fee
     * GH¢300" + "Mattress Fee GH¢200") onto every admission in a programme/academic
     * year (optionally narrowed to one level) in a single action - for a blanket
     * per-programme bill covering several fee categories at once, as opposed to
     * addBillItem()'s one-admission-at-a-time entry or AdmissionBillImport's
     * per-applicant spreadsheet (different amounts per row). Skips withdrawn and
     * already-migrated admissions - a migrated applicant is a Student now and belongs
     * in the Fees module instead.
     *
     * @param  array<int, array{category: string, description: ?string, amount: float, payment_deadline: ?string}>  $items
     * @return int number of admissions billed (each gets every item in $items)
     */
    public static function bulkBillByProgramme(int $academicYearId, int $programmeId, ?int $level, array $items, User $staff): int
    {
        return DB::transaction(function () use ($academicYearId, $programmeId, $level, $items, $staff) {
            $admissions = Admission::where('academic_year_id', $academicYearId)
                ->where('programme_id', $programmeId)
                ->when($level, fn ($query) => $query->where('level', $level))
                ->whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN, Admission::STATUS_MIGRATED])
                ->get();

            foreach ($admissions as $admission) {
                foreach ($items as $itemData) {
                    self::addBillItem($admission, $itemData, $staff);
                }
            }

            return $admissions->count();
        });
    }

    public static function removeBillItem(AdmissionBillItem $item, User $staff): void
    {
        DB::transaction(function () use ($item, $staff) {
            $admission = $item->admission;
            $item->delete();

            self::recalculatePaymentStatus($admission->fresh());
            self::logAction($admission, 'admission.bill_item.removed', null, null, $staff, amount: $item->amount, reference: $item->category);
        });
    }

    /**
     * Accounts records a payment against the bill. Starts life as 'recorded' - it must
     * still be verified and confirmed before it counts toward the payment gate.
     */
    public static function recordPayment(Admission $admission, array $data, User $staff): AdmissionPayment
    {
        return DB::transaction(function () use ($admission, $data, $staff) {
            $payment = AdmissionPayment::create(array_merge($data, [
                'admission_id' => $admission->id,
                'status' => AdmissionPayment::STATUS_RECORDED,
                'recorded_by' => $staff->id,
            ]));

            self::recalculatePaymentStatus($admission->fresh());
            self::logAction($admission, 'admission.payment.recorded', $admission->payment_status, null, $staff, amount: $payment->amount, reference: $payment->reference_number);
            ActivityLogger::log('admission.payment.recorded', "Recorded a payment of {$payment->amount} for {$admission->applicant_number}");

            return $payment;
        });
    }

    public static function verifyPayment(AdmissionPayment $payment, User $staff): AdmissionPayment
    {
        return DB::transaction(function () use ($payment, $staff) {
            $payment = AdmissionPayment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== AdmissionPayment::STATUS_RECORDED) {
                throw ValidationException::withMessages(['payment' => 'Only a recorded payment can be verified.']);
            }

            $payment->update([
                'status' => AdmissionPayment::STATUS_VERIFIED,
                'verified_by' => $staff->id,
                'verified_at' => now(),
            ]);

            $admission = $payment->admission;
            self::recalculatePaymentStatus($admission->fresh());
            self::logAction($admission, 'admission.payment.verified', null, null, $staff, amount: $payment->amount, reference: $payment->reference_number);

            return $payment;
        });
    }

    /**
     * The step that actually opens the gate: only a verified payment may be confirmed.
     * Requires the `admission.confirm_payment`-gated action (route middleware), matching
     * the rule that only Accounts closes the loop on money received.
     */
    public static function confirmPayment(AdmissionPayment $payment, User $staff): AdmissionPayment
    {
        return DB::transaction(function () use ($payment, $staff) {
            $payment = AdmissionPayment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== AdmissionPayment::STATUS_VERIFIED) {
                throw ValidationException::withMessages(['payment' => 'Only a verified payment can be confirmed.']);
            }

            $payment->update([
                'status' => AdmissionPayment::STATUS_CONFIRMED,
                'confirmed_by' => $staff->id,
                'confirmed_at' => now(),
            ]);

            $admission = Admission::lockForUpdate()->findOrFail($payment->admission_id);
            $previousStatus = $admission->payment_status;
            self::recalculatePaymentStatus($admission);

            self::logAction($admission->fresh(), 'admission.payment.confirmed', $previousStatus, $admission->fresh()->payment_status, $staff, amount: $payment->amount, reference: $payment->reference_number);
            ActivityLogger::log('admission.payment.confirmed', "Confirmed payment for {$admission->applicant_number} - payment status now {$admission->fresh()->payment_status}");

            return $payment;
        });
    }

    public static function rejectPayment(AdmissionPayment $payment, User $staff, ?string $reason = null): AdmissionPayment
    {
        return DB::transaction(function () use ($payment, $staff, $reason) {
            $payment->update([
                'status' => AdmissionPayment::STATUS_REJECTED,
                'remarks' => $reason,
            ]);

            $admission = $payment->admission;
            self::recalculatePaymentStatus($admission->fresh());
            self::logAction($admission, 'admission.payment.rejected', null, null, $staff, amount: $payment->amount, reason: $reason);

            return $payment;
        });
    }

    /**
     * Reverse a previously confirmed payment. The original row is never deleted -
     * only its status changes - so the financial history stays intact.
     */
    public static function reversePayment(AdmissionPayment $payment, User $staff, string $reason): AdmissionPayment
    {
        return DB::transaction(function () use ($payment, $staff, $reason) {
            $payment = AdmissionPayment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== AdmissionPayment::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['payment' => 'Only a confirmed payment can be reversed.']);
            }

            $payment->update([
                'status' => AdmissionPayment::STATUS_REVERSED,
                'remarks' => $reason,
            ]);

            $admission = Admission::lockForUpdate()->findOrFail($payment->admission_id);
            $previousStatus = $admission->payment_status;
            self::recalculatePaymentStatus($admission);

            self::logAction($admission->fresh(), 'admission.payment.reversed', $previousStatus, $admission->fresh()->payment_status, $staff, amount: $payment->amount, reason: $reason);
            ActivityLogger::log('admission.payment.reversed', "Reversed a confirmed payment for {$admission->applicant_number}: {$reason}");

            return $payment;
        });
    }

    /**
     * Recompute admissions.payment_status from the bill items and payment rows on
     * record, and - the load-bearing side effect - advance admission_status from
     * "offered" to "processing" the moment the bill becomes fully confirmed.
     */
    public static function recalculatePaymentStatus(Admission $admission): void
    {
        $totalBilled = (float) $admission->billItems()->sum('amount');
        $confirmedPaid = (float) $admission->payments()->where('status', AdmissionPayment::STATUS_CONFIRMED)->sum('amount');
        $hasPending = $admission->payments()->whereIn('status', [AdmissionPayment::STATUS_RECORDED, AdmissionPayment::STATUS_VERIFIED])->exists();
        $lastPayment = $admission->payments()->latest()->first();

        $status = match (true) {
            $totalBilled <= 0 => Admission::PAYMENT_NOT_BILLED,
            $confirmedPaid >= $totalBilled => Admission::PAYMENT_CONFIRMED,
            $hasPending => Admission::PAYMENT_PENDING_VERIFICATION,
            $confirmedPaid > 0 => Admission::PAYMENT_PARTIALLY_PAID,
            $lastPayment?->status === AdmissionPayment::STATUS_REVERSED => Admission::PAYMENT_REVERSED,
            $lastPayment?->status === AdmissionPayment::STATUS_REJECTED => Admission::PAYMENT_REJECTED,
            default => Admission::PAYMENT_BILLED,
        };

        $updates = ['payment_status' => $status];

        if ($status === Admission::PAYMENT_CONFIRMED && $admission->admission_status === Admission::STATUS_OFFERED) {
            $updates['admission_status'] = Admission::STATUS_PROCESSING;
        }

        $admission->update($updates);
    }

    /**
     * Applicant fills in / edits their personal information. Allowed any time before
     * they confirm it (confirmProfile() locks it), gated on the payment gate being open.
     */
    public static function updateProfile(Admission $admission, array $data): Admission
    {
        AdmissionEligibilityService::assertCanProceedToProcessing($admission);

        if ($admission->profile_confirmed_at) {
            throw ValidationException::withMessages(['profile' => 'Your profile has already been confirmed and can no longer be edited.']);
        }

        $admission->update($data);

        return $admission->fresh();
    }

    public static function confirmProfile(Admission $admission): Admission
    {
        AdmissionEligibilityService::assertCanProceedToProcessing($admission);

        $admission->update(['profile_confirmed_at' => now()]);
        self::logAction($admission, 'admission.profile.confirmed', null, null, $admission->user);

        return $admission->fresh();
    }

    public static function markReported(Admission $admission): Admission
    {
        AdmissionEligibilityService::assertCanProceedToProcessing($admission);

        if (!$admission->profile_confirmed_at) {
            throw ValidationException::withMessages(['profile' => 'Confirm your personal information before reporting.']);
        }

        $admission->update([
            'reported_at' => now(),
            'admission_status' => Admission::STATUS_REPORTED,
        ]);
        self::logAction($admission, 'admission.reported', null, Admission::STATUS_REPORTED, $admission->user);

        return $admission->fresh();
    }

    public static function markDocumentsVerified(Admission $admission, User $staff): Admission
    {
        $admission->update(['documents_verified_at' => now()]);
        self::logAction($admission, 'admission.documents.verified', null, null, $staff);

        return $admission->fresh();
    }

    /**
     * Staff enter the institution's own externally-assigned reference number - never
     * system-generated (see the migration comment on admissions.reference_number).
     */
    public static function updateReferenceNumber(Admission $admission, string $referenceNumber, User $staff): Admission
    {
        if (Admission::where('reference_number', $referenceNumber)->where('id', '!=', $admission->id)->exists()
            || \App\Models\Student::where('reference_number', $referenceNumber)->orWhere('index_number', $referenceNumber)->exists()) {
            throw ValidationException::withMessages(['reference_number' => 'This reference number is already in use.']);
        }

        $admission->update(['reference_number' => $referenceNumber]);
        self::logAction($admission, 'admission.reference_number.set', null, null, $staff, reference: $referenceNumber);

        return $admission->fresh();
    }

    public static function assignHall(Admission $admission, string $hall, User $staff): Admission
    {
        $admission->update(['hall' => $hall]);
        self::logAction($admission, 'admission.hall.assigned', null, null, $staff, reference: $hall);

        return $admission->fresh();
    }

    /**
     * Automatically assign a hall the moment an admin uploads the applicant's passport
     * photo - triggered by AdmissionController::updatePhoto(), not called directly.
     * Never overrides a hall a staff member already set by hand.
     *
     * Picks from the halls already in real use on the Student table (per "use the halls
     * already available" - this app has no Hall/capacity model, just names people have
     * actually been assigned to). Balances load by counting both existing students and
     * admissions already sitting in each hall, narrowing to whichever hall(s) are
     * currently least full, then picking randomly among that tied set - "random ... but
     * count to make it balanced": never piles everyone into one hall, but ties are still
     * broken by chance rather than always favoring the same hall alphabetically.
     */
    public static function autoAssignHall(Admission $admission, ?User $staff = null): Admission
    {
        if ($admission->hall) {
            return $admission;
        }

        $halls = Student::whereNotNull('hall')->where('hall', '!=', '')->distinct()->pluck('hall');

        if ($halls->isEmpty()) {
            return $admission;
        }

        $studentCounts = Student::whereNotNull('hall')->where('hall', '!=', '')
            ->select('hall', DB::raw('count(*) as total'))->groupBy('hall')->pluck('total', 'hall');

        $admissionCounts = Admission::whereNotNull('hall')->where('hall', '!=', '')
            ->select('hall', DB::raw('count(*) as total'))->groupBy('hall')->pluck('total', 'hall');

        $counts = $halls->mapWithKeys(fn (string $hall) => [
            $hall => (int) ($studentCounts[$hall] ?? 0) + (int) ($admissionCounts[$hall] ?? 0),
        ]);

        $minCount = $counts->min();
        $leastFullHalls = $counts->filter(fn (int $count) => $count === $minCount)->keys();
        $chosenHall = $leastFullHalls->random();

        $admission->update(['hall' => $chosenHall]);
        self::logAction($admission, 'admission.hall.auto_assigned', null, null, $staff, reference: $chosenHall);

        return $admission->fresh();
    }

    public static function approve(Admission $admission, User $staff): Admission
    {
        if (!$admission->reported_at) {
            throw ValidationException::withMessages(['admission' => 'The applicant must report before final approval.']);
        }

        $admission->update([
            'approved_at' => now(),
            'admission_status' => Admission::STATUS_APPROVED,
        ]);
        self::logAction($admission, 'admission.approved', null, Admission::STATUS_APPROVED, $staff);
        ActivityLogger::log('admission.approved', "Approved admission for {$admission->applicant_number}");

        return $admission->fresh();
    }

    public static function withdraw(Admission $admission, User $staff, ?string $reason = null): Admission
    {
        $previousStatus = $admission->admission_status;
        $admission->update(['admission_status' => Admission::STATUS_WITHDRAWN]);
        self::logAction($admission, 'admission.withdrawn', $previousStatus, Admission::STATUS_WITHDRAWN, $staff, reason: $reason);
        ActivityLogger::log('admission.withdrawn', "Withdrew admission {$admission->applicant_number}: {$reason}");

        return $admission->fresh();
    }

    /**
     * Migrate a fully-cleared admission into the Student table. Guarded by
     * AdmissionEligibilityService::canMigrate() - every precondition in the spec must
     * hold before this runs.
     */
    public static function migrateToStudent(Admission $admission, User $staff, bool $reusePhoto): Student
    {
        return DB::transaction(function () use ($admission, $staff, $reusePhoto) {
            $admission = Admission::lockForUpdate()->findOrFail($admission->id);

            AdmissionEligibilityService::assertCanMigrate($admission);

            $profilePhotoPath = null;

            if ($reusePhoto && $admission->passport_photo) {
                // The admission photo lives on the private `local` disk; Student.profile_photo
                // is served from the public disk - copy the actual bytes, not just the path
                // string, or the new student's photo 404s.
                $profilePhotoPath = 'profile-photos/' . basename($admission->passport_photo);
                Storage::disk('public')->put($profilePhotoPath, Storage::disk('local')->get($admission->passport_photo));
            }

            $student = Student::create([
                // Reference number now; the institution's own process assigns/updates the real
                // index number later through the existing Student edit screens.
                'index_number' => $admission->reference_number,
                'reference_number' => $admission->reference_number,
                'full_name' => $admission->full_name,
                'date_of_birth' => $admission->date_of_birth,
                'gender' => $admission->gender,
                'programme_id' => $admission->programme_id,
                'level' => $admission->level,
                'hall' => $admission->hall,
                'profile_photo' => $profilePhotoPath,
                'email' => $admission->email,
                'phone' => $admission->phone,
                'address' => $admission->address,
                'hometown' => $admission->hometown,
                'gps_address' => $admission->gps_address,
                'emergency_contact_name' => $admission->emergency_contact_name,
                'emergency_contact_phone' => $admission->emergency_contact_phone,
                'emergency_contact_relationship' => $admission->emergency_contact_relationship,
            ]);

            if ($admission->user) {
                $admission->user->update([
                    'role' => User::ROLE_STUDENT,
                    'student_id' => $student->id,
                    // admission_id is deliberately left in place - the account's admission
                    // history stays reachable after migration.
                    'first_login' => true,
                ]);
            }

            $admission->update([
                'admission_status' => Admission::STATUS_MIGRATED,
                'migrated_at' => now(),
            ]);

            self::logAction($admission, 'admission.migrated', Admission::STATUS_APPROVED, Admission::STATUS_MIGRATED, $staff, reference: $student->index_number);
            ActivityLogger::log('admission.migrated', "Migrated {$admission->applicant_number} to Student #{$student->id} ({$student->index_number})");

            return $student;
        });
    }

    /**
     * Write a structured admission_audit_logs row. Kept alongside (not instead of) the
     * app's existing free-text ActivityLogger, whose schema has no room for
     * previous/new status or an amount.
     */
    private static function logAction(
        Admission $admission,
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?User $performedBy = null,
        ?float $amount = null,
        ?string $reference = null,
        ?string $reason = null,
    ): AdmissionAuditLog {
        return AdmissionAuditLog::create([
            'admission_id' => $admission->id,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'amount' => $amount,
            'performed_by' => $performedBy?->id,
            'reference' => $reference,
            'reason' => $reason,
        ]);
    }
}
