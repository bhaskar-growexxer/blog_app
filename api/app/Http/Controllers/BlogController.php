<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use DateTime;
use DateTimeZone;

class BlogController extends Controller
{
    const ID_REQUIRED_MESSAGE = "ID is required";
    const TIMEZONE = 'Asia/Kolkata';

    /**
     * List blogs with category or search filter
     */
    public function index(Request $request)
    {
        if ($request['category']) {
            $blogs = Blog::where('category', $request['category'])->get();
            return $this->formatResponse($blogs);
        }

        if ($request['search']) {
            $blogs = Blog::where('title', 'like', '%' . $request['search'] . '%')
                        ->orWhere('description', 'like', '%' . $request['search'] . '%')
                        ->orWhere('author', 'like', '%' . $request['search'] . '%')
                        ->get();
            return $this->formatResponse($blogs);
        }

        return $this->formatResponse(Blog::all());
    }

    /**
     * Store a new blog (MongoDB)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required',
                'author' => 'required',
                'description' => 'required',
                'category' => 'required',
            ]);

            $blog = Blog::create([
                'title' => $request->title,
                'author' => $request->author,
                'category' => $request->category,
                'description' => $request->description,
                'created_at' => now()->toDateTimeString(), // Mongo stores string/UTC
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => $this->formatOne($blog->toArray())
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Show a single blog by ObjectId
     */
    public function show(string $id)
    {
        if (empty($id)) {
            return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
        }

        $blog = Blog::find($id);

        return response()->json([
            'isSuccess' => true,
            'data' => $blog ? $this->formatOne($blog->toArray()) : null
        ], 200);
    }

    /**
     * Update a blog (owner only)
     */
    public function update(string $id, Request $request)
    {
        if (empty($id)) {
            return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
        }

        $blog = Blog::find($id);

        if (!$blog || !$blog->exists()) {
            return response()->json(['isSuccess' => false, 'message' => 'Blog not found'], 404);
        }

        if ($blog->author !== $request->user()->email) {
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to update this blog'], 401);
        }

        $blog->update([
            'title'       => $request->title ?? $blog->title,
            'category'    => $request->category ?? $blog->category,
            'description' => $request->description ?? $blog->description,
        ]);

        return response()->json([
            'isSuccess' => true,
            'data' => $this->formatOne($blog->toArray())
        ], 200);
    }

    /**
     * Delete a blog (owner only)
     */
    public function destroy(string $id, Request $request)
    {
        if (empty($id)) {
            return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
        }

        $blog = Blog::find($id);

        if (!$blog || !$blog->exists()) {
            return response()->json(['isSuccess' => false, 'message' => 'Blog not found'], 404);
        }

        if ($blog->author !== $request->user()->email) {
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to delete this blog'], 401);
        }

        $blog->delete();

        return response()->json(['isSuccess' => true, 'message' => 'Blog deleted'], 200);
    }

    /* ------------------ Helper Functions ------------------ */

    /**
     * Format date for list results
     */
    private function formatResponse($blogs)
    {
        $formatted = array_map(function ($blog) {
            return $this->formatOne($blog);
        }, $blogs->toArray());

        return response()->json(['isSuccess' => true, 'data' => $formatted], 200);
    }

    /**
     * Format single blog record
     */
    private function formatOne($blog)
    {
        if (!empty($blog['created_at'])) {
            $dateTime = new DateTime($blog['created_at']);
            $blog['created_at'] = $dateTime
                ->setTimezone(new DateTimeZone(self::TIMEZONE))
                ->format('H:i d M Y');
        }

        // Convert `_id` (ObjectId) to string for frontend
        if (isset($blog['_id']) && is_object($blog['_id'])) {
            $blog['_id'] = (string) $blog['_id'];
        }

        return $blog;
    }

    /**
     * Aggregate: Count blogs grouped by category
     */
    public function getBlogsByCategoryCount()
    {
        $result = Blog::raw(function ($collection) {
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$category',
                    'total' => ['$sum' => 1],
                ]],
                ['$sort' => ['total' => -1]]
            ]);
        });

        // Convert cursor to array
        $data = array_map(function ($item) {
            return [
                'category' => $item['_id'],
                'total' => $item['total'],
            ];
        }, iterator_to_array($result));

        return response()->json(['isSuccess' => true, 'data' => $data], 200);
    }

}
