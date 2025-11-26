<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    /**
     * MongoDB collection name
     *
     * @var string
     */
    protected $collection = 'blogs';

    /**
     * MongoDB connection name
     *
     * @var string
     */
    protected $connection = 'mongodb';

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
     * Disable Laravel timestamps (Mongo stores its own)
     *
     * @var bool
     */
    public $timestamps = false;
}
