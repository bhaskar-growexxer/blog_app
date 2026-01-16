<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class AttachmentService
{
    const STREAM_THRESHOLD = 100 * 1024 * 1024; // 100MB in bytes
    const CHUNK_SIZE = 8192; // 8KB chunks for streaming

    protected string $disk;
    protected string $basePath;

    public function __construct()
    {
        $this->disk = config('filesystems.attachment_disk', config('filesystems.default'));
        $this->basePath = 'blog_attachments';
    }

    /**
     * Upload a file with automatic streaming for large files.
     *
     * @param UploadedFile $file
     * @param int $blogId
     * @return array
     * @throws Exception
     */
    public function uploadFile(UploadedFile $file, int $blogId): array
    {
        $fileSize = $file->getSize();
        $originalFilename = $file->getClientOriginalName();
        $filename = $this->generateUniqueFilename($originalFilename);
        $filePath = $this->basePath . '/' . $blogId . '/' . $filename;

        try {
            if ($fileSize > self::STREAM_THRESHOLD) {
                $this->uploadLargeFile($file, $filePath);
            } else {
                $this->uploadSmallFile($file, $filePath);
            }

            return [
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $file->getMimeType(),
                'storage_disk' => $this->disk,
            ];
        } catch (Exception $e) {
            throw new Exception("File upload failed: " . $e->getMessage());
        }
    }

    /**
     * Upload small files using standard method.
     *
     * @param UploadedFile $file
     * @param string $path
     * @return void
     */
    protected function uploadSmallFile(UploadedFile $file, string $path): void
    {
        Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );
    }

    /**
     * Upload large files using streaming.
     *
     * @param UploadedFile $file
     * @param string $path
     * @return void
     * @throws Exception
     */
    protected function uploadLargeFile(UploadedFile $file, string $path): void
    {
        $stream = fopen($file->getRealPath(), 'r');
        
        if ($stream === false) {
            throw new Exception("Unable to open file stream");
        }

        try {
            Storage::disk($this->disk)->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Download a file with streaming support for large files.
     *
     * @param string $filePath
     * @param int $fileSize
     * @return resource|string
     */
    public function downloadFile(string $filePath, int $fileSize)
    {
        if ($fileSize > self::STREAM_THRESHOLD) {
            return $this->downloadLargeFile($filePath);
        }
        
        return Storage::disk($this->disk)->get($filePath);
    }

    /**
     * Download large files using streaming.
     *
     * @param string $filePath
     * @return resource
     * @throws Exception
     */
    protected function downloadLargeFile(string $filePath)
    {
        $stream = Storage::disk($this->disk)->readStream($filePath);
        
        if ($stream === false) {
            throw new Exception("Unable to read file stream");
        }

        return $stream;
    }

    /**
     * Delete a file from storage.
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }
        
        return false;
    }

    /**
     * Generate a unique filename.
     *
     * @param string $originalFilename
     * @return string
     */
    protected function generateUniqueFilename(string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        $sanitizedBaseName = Str::slug($baseName);
        
        return $sanitizedBaseName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Get file size in human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Validate file type.
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @return bool
     */
    public function validateFileType(UploadedFile $file, array $allowedTypes = []): bool
    {
        if (empty($allowedTypes)) {
            return true;
        }

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($mimeType, $allowedTypes) || in_array($extension, $allowedTypes);
    }

    /**
     * Set the storage disk dynamically.
     *
     * @param string $disk
     * @return self
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }
}