<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (!in_array($user->role, [self::ROLE_ADMIN, self::ROLE_STUDENT])) {
                throw new \InvalidArgumentException('Invalid user role');
            }
        });
        
        static::updating(function ($user) {
            if (!in_array($user->role, [self::ROLE_ADMIN, self::ROLE_STUDENT])) {
                throw new \InvalidArgumentException('Invalid user role');
            }
            // Prevent changing role from student to admin
            if ($user->getOriginal('role') === self::ROLE_STUDENT && $user->role === self::ROLE_ADMIN) {
                throw new \InvalidArgumentException('Cannot change role from student to admin');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'student_id',
        'first_login',
        'index_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the student associated with the user.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
