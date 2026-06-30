<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'filters',
        'total_recipients',
        'success_count',
        'failed_count',
        'sent_by',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
