<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'biometric_device_id',
        'device_user_id',
        'punched_at',
        'verify_mode',
        'raw_payload',
        'student_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'punched_at' => 'datetime',
    ];

    /**
     * Get the device that produced this log.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    /**
     * Get the student this log was matched to, if any.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
