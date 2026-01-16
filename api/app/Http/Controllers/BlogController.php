<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use DateTime;
use DateTimeZone;
use Exception;

class BlogController extends Controller
{
    const ID_REQUIRED_MESSAGE = "ID is required";
    const TIMEZONE = 'Asia/Kolkata';

    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request['category']) {
            $blogs = Blog::where('category', $request['category'])->with('attachments')->get();
        }
        elseif ($request['search']) {
            $blogs = Blog::where('title', 'like', '%' . $request['search'] . '%')
                        ->orWhere('description', 'like', '%' . $request['search'] . '%')
                        ->orWhere('author', 'like', '%' . $request['search'] . '%')
                        ->with('attachments')
                        ->get();
        }
        else{
            $blogs = Blog::with('attachments')->get();
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
                'attachments.*' => 'nullable|file|max:524288', // 512MB max
            ]);

            $blog = Blog::create([
                'title' => $request->title,
                'author' => $request->author,
                'category' => $request->category,
                'description' => $request->description,
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($request->file('attachments'), $blog->id);
            }

            $blog->load('attachments');
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
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        if(!empty($id)){
            $blog = Blog::with('attachments')->find($id);
            return response()->json(['isSuccess' => true, 'data' => $blog],200);
        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(String $id, Request $request)
    {
        if(!empty($id)){
            $blog = Blog::find($id);

            if($blog && $blog->author == $request->user()->email){
                $blog->update([
                    'title' => $request->title ?? $blog->title,
                    'category' => $request->category ?? $blog->category,
                    'description' => $request->description ?? $blog->description,
                ]);

                // Handle new attachments
                if ($request->hasFile('attachments')) {
                    $this->handleAttachments($request->file('attachments'), $blog->id);
                }

                $blog->load('attachments');
                return response()->json(['isSuccess' => true, 'data' => $blog], 200);
            }
            
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to update this blog'], 401);
        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id, Request $request)
    {
        if(!empty($id)){
            $blog = Blog::find($id);
            if($blog && $blog->author == $request->user()->email){
                // Delete all attachments
                foreach ($blog->attachments as $attachment) {
                    $this->attachmentService->deleteFile($attachment->file_path);
                    $attachment->delete();
                }
                
                $blog->delete();
                return response()->json(['isSuccess' => true, 'message' => 'blog deleted'], 200);
            }
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to delete this blog'], 401);
        }
        return response()->json(['isSuccess' => false, 'message' => self::ID_REQUIRED_MESSAGE], 422);
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(String $attachmentId)
    {
        $attachment = BlogAttachment::find($attachmentId);

        if (!$attachment) {
            return response()->json(['isSuccess' => false, 'message' => 'Attachment not found'], 404);
        }

        try {
            $fileContent = $this->attachmentService->downloadFile(
                $attachment->file_path,
                $attachment->file_size
            );

            if (is_resource($fileContent)) {
                // Stream large files
                return response()->stream(function() use ($fileContent) {
                    while (!feof($fileContent)) {
                        echo fread($fileContent, AttachmentService::CHUNK_SIZE);
                        flush();
                    }
                    fclose($fileContent);
                }, 200, [
                    'Content-Type' => $attachment->mime_type,
                    'Content-Disposition' => 'attachment; filename="' . $attachment->original_filename . '"',
                ]);
            }

            // Return small files directly
            return response($fileContent, 200, [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $attachment->original_filename . '"',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'File download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific attachment.
     */
    public function deleteAttachment(String $attachmentId, Request $request)
    {
        $attachment = BlogAttachment::with('blog')->find($attachmentId);

        if (!$attachment) {
            return response()->json(['isSuccess' => false, 'message' => 'Attachment not found'], 404);
        }

        if ($attachment->blog->author != $request->user()->email) {
            return response()->json(['isSuccess' => false, 'message' => 'You are not authorized to delete this attachment'], 401);
        }

        try {
            $this->attachmentService->deleteFile($attachment->file_path);
            $attachment->delete();

            return response()->json(['isSuccess' => true, 'message' => 'Attachment deleted'], 200);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Attachment deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file attachments upload.
     */
    protected function handleAttachments(array $files, int $blogId): void
    {
        foreach ($files as $file) {
            $attachmentData = $this->attachmentService->uploadFile($file, $blogId);
            
            BlogAttachment::create([
                'blog_id' => $blogId,
                'filename' => $attachmentData['filename'],
                'original_filename' => $attachmentData['original_filename'],
                'file_path' => $attachmentData['file_path'],
                'file_size' => $attachmentData['file_size'],
                'mime_type' => $attachmentData['mime_type'],
                'storage_disk' => $attachmentData['storage_disk'],
            ]);
        }
    }
}