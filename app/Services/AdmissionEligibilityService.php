<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Student;

/**
 * The single server-side gate the whole admission workflow depends on. Every
 * payment-dependent controller action calls the assert*() methods here first -
 * enforcement lives in this one place, never in a hidden button or a disabled link.
 */
class AdmissionEligibilityService
{
    public static function isPaymentConfirmed(Admission $admission): bool
    {
        return $admission->payment_status === Admission::PAYMENT_CONFIRMED;
    }

    /**
     * Whether the applicant may proceed into profile/documents/hall/reporting - the
     * "Admission Processing" stage. Requires a confirmed payment and an admission that
     * hasn't been withdrawn.
     */
    public static function canProceedToProcessing(Admission $admission): bool
    {
        return self::isPaymentConfirmed($admission) && !$admission->isWithdrawn();
    }

    /**
     * Whether the admission may be migrated into the Student table. Every one of these
     * must hold: reported, profile confirmed, payment confirmed, documents verified,
     * staff approval recorded, a usable institutional reference number that no existing
     * Student already holds, and the admission hasn't already been migrated or withdrawn.
     */
    public static function canMigrate(Admission $admission): bool
    {
        if ($admission->isWithdrawn() || $admission->isMigrated()) {
            return false;
        }

        if (!$admission->reported_at || !$admission->profile_confirmed_at || !$admission->documents_verified_at) {
            return false;
        }

        if (!self::isPaymentConfirmed($admission)) {
            return false;
        }

        if ($admission->admission_status !== Admission::STATUS_APPROVED) {
            return false;
        }

        if (empty($admission->reference_number)) {
            return false;
        }

        $referenceTaken = Student::where('index_number', $admission->reference_number)
            ->orWhere('reference_number', $admission->reference_number)
            ->exists();

        return !$referenceTaken;
    }

    /**
     * Abort the request (403) unless canProceedToProcessing() holds.
     */
    public static function assertCanProceedToProcessing(Admission $admission): void
    {
        abort_unless(
            self::canProceedToProcessing($admission),
            403,
            'Your admission payment has not yet been confirmed by the Accounts Office.'
        );
    }

    /**
     * Abort the request (403) unless canMigrate() holds.
     */
    public static function assertCanMigrate(Admission $admission): void
    {
        abort_unless(
            self::canMigrate($admission),
            403,
            'This admission does not yet satisfy every requirement for migration to the student register.'
        );
    }
}
