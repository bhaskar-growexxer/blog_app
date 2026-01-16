<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogAttachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'blog_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'storage_disk',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the blog that owns the attachment.
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Get the full URL of the attachment.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->storage_disk === 's3') {
            return \Storage::disk('s3')->url($this->file_path);
        }
        
        return \Storage::disk($this->storage_disk)->url($this->file_path);
    }
}