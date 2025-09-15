<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ImageHandling
{
    /**
     * Upload an image file to storage with a unique name
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory The storage directory (default: 'prescriptions')
     * @param string|null $prefix Optional prefix for the filename
     * @return string The stored filename
     */
    protected function uploadImage(UploadedFile $file, string $directory = 'prescriptions', ?string $prefix = null): string
    {
        // Validate file type
        if (!$this->isValidImageFile($file)) {
            throw new \InvalidArgumentException('Invalid image file type. Only JPEG, PNG, and GIF files are allowed.');
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateUniqueFilename($extension, $prefix);

        // Store the file
        $path = $file->storeAs("public/{$directory}", $filename);

        if (!$path) {
            throw new \RuntimeException('Failed to upload image file.');
        }

        return $filename;
    }

    /**
     * Get the public URL for an image file
     *
     * @param string $filename The stored filename
     * @param string $directory The storage directory (default: 'prescriptions')
     * @return string The public URL
     */
    protected function getImageUrl(string $filename, string $directory = 'prescriptions'): string
    {
        if (empty($filename)) {
            return '';
        }

        return Storage::url("{$directory}/{$filename}");
    }

    /**
     * Delete an image file from storage
     *
     * @param string $filename The filename to delete
     * @param string $directory The storage directory (default: 'prescriptions')
     * @return bool True if deleted successfully, false otherwise
     */
    protected function deleteImage(string $filename, string $directory = 'prescriptions'): bool
    {
        if (empty($filename)) {
            return false;
        }

        $path = "public/{$directory}/{$filename}";

        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }

    /**
     * Generate a unique filename with optional prefix
     *
     * @param string $extension The file extension
     * @param string|null $prefix Optional prefix for the filename
     * @return string The unique filename
     */
    private function generateUniqueFilename(string $extension, ?string $prefix = null): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        if ($prefix) {
            return "{$prefix}_{$timestamp}_{$random}.{$extension}";
        }

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Validate if the uploaded file is a valid image
     *
     * @param UploadedFile $file The uploaded file
     * @return bool True if valid image, false otherwise
     */
    private function isValidImageFile(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($mimeType, $allowedMimeTypes) && in_array($extension, $allowedExtensions);
    }

    /**
     * Get image file size in human readable format
     *
     * @param UploadedFile $file The uploaded file
     * @return string Human readable file size
     */
    protected function getImageFileSize(UploadedFile $file): string
    {
        $bytes = $file->getSize();
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate image file size
     *
     * @param UploadedFile $file The uploaded file
     * @param int $maxSizeInMB Maximum size in MB (default: 5)
     * @return bool True if size is valid, false otherwise
     */
    protected function validateImageSize(UploadedFile $file, int $maxSizeInMB = 5): bool
    {
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;
        return $file->getSize() <= $maxSizeInBytes;
    }

    /**
     * Get image dimensions
     *
     * @param UploadedFile $file The uploaded file
     * @return array|null Array with width and height, or null if not an image
     */
    protected function getImageDimensions(UploadedFile $file): ?array
    {
        $imageInfo = getimagesize($file->getPathname());

        if ($imageInfo === false) {
            return null;
        }

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime_type' => $imageInfo['mime']
        ];
    }
}
