<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'category',
        'author',
        'author_email',
        'created_at',
    ];

    /**
     * Get the attachments for the blog.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(BlogAttachment::class);
    }
}