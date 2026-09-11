<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionBillItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admission_id',
        'category',
        'description',
        'amount',
        'payment_deadline',
        'created_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_deadline' => 'date',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return FeeCategory::options()[$this->category] ?? ucfirst($this->category);
    }
}
