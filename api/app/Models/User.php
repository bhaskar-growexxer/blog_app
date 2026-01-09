<?php
// ============================================================================
// MODELS
// ============================================================================

// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============
    
    /**
     * Get all blogs authored by this user
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'author_id');
    }

    /**
     * Get all comments made by this user (polymorphic)
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get only published blogs
     */
    public function publishedBlogs()
    {
        return $this->hasMany(Blog::class, 'author_id')
                    ->where('status', Blog::STATUS_PUBLISHED);
    }

    // ============ QUERY SCOPES ============
    
    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users with verified email
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to get users who have published blogs
     */
    public function scopeAuthors($query)
    {
        return $query->whereHas('blogs', function ($q) {
            $q->where('status', Blog::STATUS_PUBLISHED);
        });
    }

    // ============ ACCESSORS & MUTATORS ============
    
    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('Y-m-d');
    }
}