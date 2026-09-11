<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionPayment extends Model
{
    use HasFactory;

    public const STATUS_RECORDED = 'recorded';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVERSED = 'reversed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admission_id',
        'amount',
        'payment_method',
        'bank',
        'reference_number',
        'receipt_number',
        'evidence_path',
        'status',
        'recorded_by',
        'verified_by',
        'verified_at',
        'confirmed_by',
        'confirmed_at',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
