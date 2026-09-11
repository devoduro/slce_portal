<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends Model
{
    use HasFactory;

    public const STATUS_OFFERED = 'offered';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_MIGRATED = 'migrated';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const PAYMENT_NOT_BILLED = 'not_billed';
    public const PAYMENT_BILLED = 'billed';
    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_PENDING_VERIFICATION = 'paid_pending_verification';
    public const PAYMENT_CONFIRMED = 'confirmed';
    public const PAYMENT_REJECTED = 'rejected';
    public const PAYMENT_REVERSED = 'reversed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'applicant_number',
        'full_name',
        'title',
        'email',
        'phone',
        'programme_id',
        'academic_year_id',
        'level',
        'hall',
        'gender',
        'date_of_birth',
        'address',
        'hometown',
        'gps_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'reference_number',
        'passport_photo',
        'admission_status',
        'payment_status',
        'documents_verified_at',
        'profile_confirmed_at',
        'reported_at',
        'approved_at',
        'migrated_at',
        'imported_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'documents_verified_at' => 'datetime',
        'profile_confirmed_at' => 'datetime',
        'reported_at' => 'datetime',
        'approved_at' => 'datetime',
        'migrated_at' => 'datetime',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * The applicant's own login account, once one has been created for them.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(AdmissionBillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AdmissionPayment::class);
    }

    public function photoAudits(): HasMany
    {
        return $this->hasMany(AdmissionPhotoAudit::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdmissionAuditLog::class)->latest();
    }

    /**
     * Total amount billed across every line item.
     */
    public function totalBilled(): float
    {
        return (float) $this->billItems()->sum('amount');
    }

    /**
     * Total amount paid across every payment record that has at least been recorded
     * (not rejected/reversed) - matches the same "money actually received" definition
     * used by recalculatePaymentStatus() in AdmissionService.
     */
    public function totalPaid(): float
    {
        return (float) $this->payments()
            ->whereIn('status', [AdmissionPayment::STATUS_RECORDED, AdmissionPayment::STATUS_VERIFIED, AdmissionPayment::STATUS_CONFIRMED])
            ->sum('amount');
    }

    public function outstandingBalance(): float
    {
        return round($this->totalBilled() - $this->totalPaid(), 2);
    }

    public function isPaymentConfirmed(): bool
    {
        return $this->payment_status === self::PAYMENT_CONFIRMED;
    }

    public function isWithdrawn(): bool
    {
        return $this->admission_status === self::STATUS_WITHDRAWN;
    }

    public function isMigrated(): bool
    {
        return $this->admission_status === self::STATUS_MIGRATED;
    }

    /**
     * Human-readable label for the applicant dashboard's "next required action" prompt.
     */
    public function nextAction(): string
    {
        if ($this->isWithdrawn()) {
            return 'Your admission has been withdrawn. Contact the Admissions Office.';
        }

        if ($this->isMigrated()) {
            return 'You have been migrated into the student register.';
        }

        if (!$this->isPaymentConfirmed()) {
            return 'Complete the required admission payment.';
        }

        if (!$this->profile_confirmed_at) {
            return 'Complete and confirm your personal information.';
        }

        if (!$this->reported_at) {
            return 'Report to the institution and mark yourself as reported.';
        }

        if ($this->admission_status !== self::STATUS_APPROVED) {
            return 'Awaiting final approval from the Admissions Office.';
        }

        return 'Awaiting migration into the student register.';
    }
}
