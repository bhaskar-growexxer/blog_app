<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // $request->validate([
        //     'title' => 'required',
        //     'description' => 'required',
        //     'category' => 'required',
        //     'author' => 'required',
        // ]);

        // $blog = Blog::create($request->all());

        //if there is category in req return only that category ,if there is search in req return only that has search in name or description or author . if there is no category or search return all
        if ($request['category']) {
            $blogs = Blog::where('category', $request['category'])->get();
            return response()->json($blogs, 200);
        }
        if ($request['search']) {
            $blogs = Blog::where('title', 'like', '%' . $request['search'] . '%')
                        ->orWhere('description', 'like', '%' . $request['search'] . '%')
                        ->orWhere('author', 'like', '%' . $request['search'] . '%')
                        ->get();
            return response()->json($blogs, 200);
        }
        return Blog::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'category' => 'required',
            'author' => 'required',
        ]);

        $blog = Blog::create($request->all());

        return response()->json($blog, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        if($request['id']){
            return Blog::find($request['id']);
        }
        return response()->json(['error' => 'id is required'], 400);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        if($request['id']){
            $blog = Blog::find($request['id']);
            $blog->update($request->all());
            return response()->json($blog, 200);
        }
        return response()->json(['error' => 'id is required'], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        //
    }
}
