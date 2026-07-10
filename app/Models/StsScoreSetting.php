<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StsScoreSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'level',
        'attendance_max',
        'project_max',
        'assignment_max',
        'mid_semester_max',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_max' => 'decimal:2',
        'project_max' => 'decimal:2',
        'assignment_max' => 'decimal:2',
        'mid_semester_max' => 'decimal:2',
    ];
}
