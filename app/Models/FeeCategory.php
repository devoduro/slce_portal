<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeeCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'name',
        'is_protected',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_protected' => 'boolean',
    ];

    /**
     * The full set of fee categories, as [slug => name], for populating dropdowns and
     * validating uploads/forms. This is the single source of truth for what a fee
     * structure or student fee charge can be categorized as.
     */
    public static function options(): array
    {
        return static::orderBy('name')->pluck('name', 'slug')->all();
    }

    /**
     * Derive a unique slug from a display name (e.g. "Library Fee" -> "library-fee",
     * "library-fee-2" if that slug is already taken).
     */
    public static function slugFor(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Whether this category is currently referenced by any fee structure or student
     * fee charge - used to block deletion of a category that's actually in use.
     */
    public function isInUse(): bool
    {
        return FeeStructure::where('category', $this->slug)->exists()
            || StudentFeeCharge::where('category', $this->slug)->exists();
    }
}
