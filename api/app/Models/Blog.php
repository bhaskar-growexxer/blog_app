<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'content',
        'category_id',
        'author_id',
        'status',
        'published_at',
        'views_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    /**
     * Relationships to auto-eager load (prevents N+1 queries)
     *
     * @var array
     */
    protected $with = ['author'];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * Get the author (user) of this blog
     * Relationship: Blog belongs to User
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the category of this blog
     * Relationship: Blog belongs to Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all comments for this blog (polymorphic relationship)
     * Relationship: Blog has many Comments (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get only approved comments
     * Constrained relationship for better performance
     */
    public function approvedComments()
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->where('approved', true)
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get tags for this blog (polymorphic many-to-many)
     * Relationship: Blog has many Tags through polymorphic pivot
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // ============================================================================
    // QUERY SCOPES (Reusable Query Logic)
    // ============================================================================

    /**
     * Scope to get only published blogs
     * Usage: Blog::published()->get()
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope to get only draft blogs
     * Usage: Blog::draft()->get()
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get blogs by category
     * Usage: Blog::ofCategory(1)->get()
     */
    public function scopeOfCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to get popular blogs based on view threshold
     * Usage: Blog::popular(100)->get()
     */
    public function scopePopular($query, $threshold = 100)
    {
        return $query->where('views_count', '>', $threshold);
    }

    /**
     * Scope to get recent blogs within specified days
     * Usage: Blog::recent(7)->get()
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get blogs by specific author
     * Usage: Blog::byAuthor(1)->get()
     */
    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Scope for searching blogs by title, description, or content
     * Usage: Blog::search('laravel')->get()
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to eager load optimized relationships (prevents N+1 queries)
     * Usage: Blog::withOptimizedRelations()->get()
     */
    public function scopeWithOptimizedRelations($query)
    {
        return $query->with([
            'author:id,name,email',
            'category:id,name',
            'approvedComments' => function ($q) {
                $q->with('user:id,name')->limit(5);
            }
        ])->withCount(['comments', 'tags']);
    }

    /**
     * Scope to filter blogs that have approved comments
     * Usage: Blog::hasApprovedComments()->get()
     */
    public function scopeHasApprovedComments($query)
    {
        return $query->whereHas('comments', function ($q) {
            $q->where('approved', true);
        });
    }

    /**
     * Scope to filter blogs by active authors
     * Usage: Blog::byActiveAuthors()->get()
     */
    public function scopeByActiveAuthors($query)
    {
        return $query->whereHas('author', function ($q) {
            $q->where('is_active', true);
        });
    }

    // ============================================================================
    // ACCESSORS & MUTATORS
    // ============================================================================

    /**
     * Get formatted published date
     */
    public function getFormattedPublishedAtAttribute()
    {
        return $this->published_at ? $this->published_at->format('Y-m-d') : null;
    }

    /**
     * Check if blog is published
     */
    public function getIsPublishedAttribute()
    {
        return $this->status === self::STATUS_PUBLISHED 
               && $this->published_at 
               && $this->published_at <= now();
    }

    /**
     * Get excerpt from content
     */
    public function getExcerptAttribute()
    {
        return substr(strip_tags($this->content), 0, 150) . '...';
    }
}