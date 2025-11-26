<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    const ID_REQUIRED_MESSAGE = "ID is required";
    const TIMEZONE = 'Asia/Kolkata';
    const CACHE_TTL = 600; // 10 minutes in seconds
    const CACHE_DRIVER = 'redis';

    /**
     * Fetch blogs with Cache caching (direct Cache usage)
     *
     * @param Request $request
     * @return array
     */
    private function getCachedBlogs(Request $request): array
    {
        // Preparing fiter params fr cache key
        $searchParams = [];
        if ($request->category) {
            $searchParams['category'] = $request->category;
        }
        if ($request->search) {
            $searchParams['search'] = $request->search;
        }

        // Generate key based on params
        $cacheKey = 'blogs_' . md5(json_encode($searchParams));

        // Check cache
        if (self::CACHE_DRIVER === 'redis') {
            // Fetch from Redis Cache
            $cached = Redis::get($cacheKey);
            // $cached = Cache::get($cacheKey);

        } else {
            // Fetch from File Cache
            $cached = Cache::get($cacheKey);
        }

        if ($cached) {
            return [
                'blogs' => json_decode($cached, true),
                'isCached' => true,
                'cacheKey' => $cacheKey,
                'source' => 'cache',
                'ttl' => self::CACHE_TTL,
                'driver' => self::CACHE_DRIVER
            ];
        }

        // Fetch from DB
        if ($request->category) {
            $blogs = Blog::where('category', $request->category)->get();
        }
        elseif ($request->search) {
            $blogs = Blog::where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('author', 'like', '%' . $request->search . '%')
                        ->get();
        }
        else {
            $blogs = Blog::all();
        }

        // Format
        $blogsArray = array_map(function ($blog) {
            $dateTime = new DateTime($blog['created_at']);
            $blog['created_at'] = $dateTime
                ->setTimezone(new DateTimeZone(self::TIMEZONE))
                ->format('H:i d M Y');
            return $blog;
        }, $blogs->toArray());

        if (self::CACHE_DRIVER === 'redis') {
            // Save to Redis Cache with TTL 10 minutes (600 seconds)
            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($blogsArray));
            // Redis::setex($cacheKey, json_encode($blogsArray));

        } else {
            // Save to File Cache with TTL 10 minutes (600 seconds)
            Cache::put($cacheKey, json_encode($blogsArray), self::CACHE_TTL);
        }
    
        return [
            'blogs' => $blogsArray,
            'isCached' => false,
            'cacheKey' => $cacheKey,
            'source' => 'database',
            'ttl' => self::CACHE_TTL,
            'driver' => self::CACHE_DRIVER
        ];
    }

    /**
     * Display a listing of the resource using Cache cache.
     */
    public function index(Request $request)
    {
        try {
            $blogs = $this->getCachedBlogs($request);

            return response()->json([
                'isSuccess' => true,
                'data' => $blogs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
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
            ]);

            $blog = $blog->toArray();
            $dateTime = new DateTime($blog['created_at']);
            $blog['created_at'] = $dateTime->setTimezone(new DateTimeZone(self::TIMEZONE))->format('H:i d M Y');

            return response()->json(['isSuccess' => true, 'data' => $blog],200);

        }catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        if(!empty($id)){
            return response()->json(['isSuccess' => true, 'data' => Blog::find($id)],200);
        }
        return response()->json(['isSuccess' => false, 'mesage' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(String $id,Request $request)
    {
        if(!empty($id)){
            $blog = Blog::find($request['id']);

            if($blog->exists() && $blog->author == $request->user()->email){
                $blog->update([
                    'title' => $request->title ?? $blog->title,
                    'category' => $request->category ?? $blog->category,
                    'description' => $request->description ?? $blog->description,
                ]);
                return response()->json(['isSuccess' => true, 'data' =>$blog], 200);
            }
            
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to delete this blog'], 401);

        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id,Request $request)
    {
        if(!empty($id)){
            $blog = Blog::find($id);
            if($blog->exists() && $blog->author == $request->user()->email){
                $blog->delete();
                return response()->json(['isSuccess' => true, 'message' => 'blog deleted'], 200);
            }
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to delete this blog'], 401);

        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }
}
