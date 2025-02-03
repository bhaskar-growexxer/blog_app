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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request['category']) {
            $blogs = Blog::where('category', $request['category'])->get();
        }
        elseif ($request['search']) {
            $blogs = Blog::where('title', 'like', '%' . $request['search'] . '%')
                        ->orWhere('description', 'like', '%' . $request['search'] . '%')
                        ->orWhere('author', 'like', '%' . $request['search'] . '%')
                        ->get();
        }
        else{
            $blogs = Blog::all();
        }

        $blogs = array_map(function($blog){

            $dateTime = new DateTime($blog['created_at']);
            $blog['created_at'] = $dateTime->setTimezone(new DateTimeZone(self::TIMEZONE))->format('H:i d M Y');
            return $blog;
        }, $blogs->toArray());

        return response()->json(['isSuccess' => true, 'data' => $blogs ?? []],200);

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
            $blog->update([
                'title' => $request->title ?? $blog->title,
                'category' => $request->category ?? $blog->category,
                'description' => $request->description ?? $blog->description,
            ]);
            return response()->json(['isSuccess' => true, 'data' =>$blog], 200);
        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        if(!empty($id)){
            $blog = Blog::find($id);
            $blog->delete();
            return response()->json(['isSuccess' => true, 'message' => 'blog deleted'], 200);
        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }
}
