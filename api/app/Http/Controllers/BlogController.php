<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    const TIMEZONE = 'Asia/Kolkata';

    /**
     * Display a listing of blogs with advanced filtering and optimization
     */
    public function index(Request $request)
    {
        // Start with base query using scopes
        $query = Blog::query();

        // Apply category filter using scope
        if ($request->has('category')) {
            $query->ofCategory($request->category);
        }

        // Apply search filter using scope
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Apply status filter
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            }
        } else {
            // Default: only show published blogs for public
            $query->published();
        }

        // Apply author filter using scope
        if ($request->has('author_id')) {
            $query->byAuthor($request->author_id);
        }

        // Apply popular filter
        if ($request->has('popular')) {
            $query->popular($request->get('popular', 100));
        }

        // Apply recent filter
        if ($request->has('recent')) {
            $query->recent($request->get('recent', 7));
        }

        // Optimize queries - prevent N+1 problem
        $query->with([
            'author:id,name,email',
            'category:id,name',
            'tags:id,name'
        ])->withCount([
            'comments' => function ($q) {
                $q->where('approved', true);
            }
        ]);

        // Order by latest
        $query->orderBy('published_at', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $blogs = $query->paginate($perPage);

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }

    /**
     * Store a newly created blog
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'content' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'status' => 'in:draft,published,archived',
                'tags' => 'array',
                'tags.*' => 'exists:tags,id',
            ]);

            // Use database transaction for data integrity
            DB::beginTransaction();

            try {
                // Create blog
                $blog = Blog::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'content' => $request->content,
                    'category_id' => $request->category_id,
                    'author_id' => Auth::id(),
                    'status' => $request->status ?? Blog::STATUS_DRAFT,
                    'published_at' => $request->status === Blog::STATUS_PUBLISHED ? now() : null,
                    'views_count' => 0,
                ]);

                // Attach tags if provided (polymorphic many-to-many)
                if ($request->has('tags')) {
                    $blog->tags()->attach($request->tags);
                }

                DB::commit();

                // Load relationships for response (eager loading)
                $blog->load([
                    'author:id,name,email',
                    'category:id,name',
                    'tags:id,name'
                ]);

                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Blog created successfully',
                    'data' => $blog
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'An error occurred while creating the blog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified blog with optimized relationships
     */
    public function show(string $id)
    {
        try {
            // Use eager loading to prevent N+1 queries
            $blog = Blog::with([
                    'author:id,name,email',
                    'category:id,name,description',
                    'approvedComments' => function ($query) {
                        $query->with('user:id,name')
                              ->orderBy('created_at', 'desc')
                              ->limit(10);
                    },
                    'tags:id,name'
                ])
                ->withCount('comments')
                ->findOrFail($id);

            // Increment views count
            $blog->increment('views_count');

            return response()->json([
                'isSuccess' => true,
                'data' => $blog
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Blog not found'
            ], 404);
        }
    }

    /**
     * Update the specified blog
     */
    public function update(string $id, Request $request)
    {
        try {
            // Find blog or fail
            $blog = Blog::findOrFail($id);

            // Check authorization - only author can update
            if ($blog->author_id !== Auth::id()) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'You are not authorized to update this blog'
                ], 403);
            }

            // Validate request
            $request->validate([
                'title' => 'string|max:255',
                'description' => 'string',
                'content' => 'string',
                'category_id' => 'exists:categories,id',
                'status' => 'in:draft,published,archived',
                'tags' => 'array',
                'tags.*' => 'exists:tags,id',
            ]);

            DB::beginTransaction();

            try {
                // Update blog fields
                $blog->update($request->only([
                    'title',
                    'description',
                    'content',
                    'category_id',
                    'status'
                ]));

                // Update published_at if status changed to published
                if ($request->status === Blog::STATUS_PUBLISHED && !$blog->published_at) {
                    $blog->update(['published_at' => now()]);
                }

                // Sync tags if provided (polymorphic many-to-many)
                if ($request->has('tags')) {
                    $blog->tags()->sync($request->tags);
                }

                DB::commit();

                // Load updated relationships
                $blog->load([
                    'author:id,name,email',
                    'category:id,name',
                    'tags:id,name'
                ]);

                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Blog updated successfully',
                    'data' => $blog
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Blog not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'An error occurred while updating the blog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified blog (soft delete)
     */
    public function destroy(string $id)
    {
        try {
            // Find blog or fail
            $blog = Blog::findOrFail($id);

            // Check authorization - only author can delete
            if ($blog->author_id !== Auth::id()) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'You are not authorized to delete this blog'
                ], 403);
            }

            // Soft delete the blog
            $blog->delete();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Blog deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Blog not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'An error occurred while deleting the blog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular blogs using scope
     */
    public function popular(Request $request)
    {
        $threshold = $request->get('threshold', 100);

        $blogs = Blog::published()
            ->popular($threshold)
            ->with([
                'author:id,name,email',
                'category:id,name',
                'tags:id,name'
            ])
            ->withCount('comments')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }

    /**
     * Get user's own blogs using scope and relationship filtering
     */
    public function myBlogs(Request $request)
    {
        // Using scope to filter by authenticated user
        $blogs = Blog::byAuthor(Auth::id())
            ->with([
                'category:id,name',
                'tags:id,name'
            ])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }

    /**
     * Get blogs by category with optimized queries
     */
    public function byCategory(string $categoryId)
    {
        try {
            // Using whereHas to filter by relationship
            $blogs = Blog::published()
                ->ofCategory($categoryId)
                ->whereHas('category', function ($query) {
                    $query->where('is_active', true);
                })
                ->with([
                    'author:id,name,email',
                    'category:id,name,description',
                    'tags:id,name'
                ])
                ->withCount('comments')
                ->orderBy('published_at', 'desc')
                ->paginate(15);

            return response()->json([
                'isSuccess' => true,
                'data' => $blogs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get blogs with approved comments using whereHas
     */
    public function withComments()
    {
        // Using whereHas to filter blogs that have approved comments
        $blogs = Blog::published()
            ->whereHas('comments', function ($query) {
                $query->where('approved', true);
            })
            ->with([
                'author:id,name,email',
                'approvedComments' => function ($query) {
                    $query->with('user:id,name')->limit(3);
                }
            ])
            ->withCount(['comments' => function ($q) {
                $q->where('approved', true);
            }])
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }

    /**
     * Get recent blogs using scope
     */
    public function recent(Request $request)
    {
        $days = $request->get('days', 7);

        $blogs = Blog::published()
            ->recent($days)
            ->with([
                'author:id,name,email',
                'category:id,name'
            ])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }
}