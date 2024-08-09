<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image as InterventionImage;
use Illuminate\Support\Str;
use App\Exceptions\ImageProcessingException;

class ImageUploadService
{
    public function getAllImages()
    {
        return Image::latest()->get();
    }

    public function uploadTemporary($imageFile)
    {
        $filename = $this->generateFilename($imageFile);
        $tempPath = $this->saveTempImage($imageFile, $filename);
        $thumbnails = $this->generateTempThumbnails($imageFile, $filename);
        $metadata = $this->getImageMetadata($imageFile);

        return [
            'temp_id' => Str::uuid(),
            'filename' => $filename,
            'temp_path' => $tempPath,
            'thumbnails' => $thumbnails,
            'metadata' => $metadata,
        ];
    }

    public function confirmUpload(array $data)
    {
        $permanentPath = $this->moveToPermanentStorage($data['temp_path'], $data['filename']);
        $permanentThumbnails = $this->moveThumbnailsToPermanentStorage($data['thumbnails'], $data['filename']);

        return $this->saveToDatabase($data['filename'], $permanentPath, $permanentThumbnails, $data['metadata']);
    }

    public function discardTemporary(array $data)
    {
        Storage::disk(config('image_upload.disk'))->delete($data['temp_path']);
        foreach ($data['thumbnails'] as $thumbnail) {
            Storage::disk(config('image_upload.disk'))->delete($thumbnail);
        }
    }

    public function delete($id)
    {
        $image = Image::findOrFail($id);
        Storage::disk($image->disk)->delete($image->original_path);
        foreach ($image->thumbnails as $thumbnail) {
            Storage::disk($image->disk)->delete($thumbnail);
        }
        $image->delete();
    }

    private function generateFilename($image)
    {
        return time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
    }

    private function saveTempImage($image, $filename)
    {
        $path = config('image_upload.temp_path') . '/original/' . $filename;
        if (!Storage::disk(config('image_upload.disk'))->put($path, file_get_contents($image))) {
            throw new ImageProcessingException("Failed to save temporary image");
        }
        return $path;
    }

    private function generateTempThumbnails($image, $filename)
    {
        $thumbnails = [];
        foreach (config('image_upload.thumbnails') as $size => $dimensions) {
            $thumbnailPath = config('image_upload.temp_path') . '/' . $size . '/' . $filename;
            $img = InterventionImage::read($image->getRealPath())->scaleDown($dimensions['width'], $dimensions['height']);

            if (!Storage::disk(config('image_upload.disk'))->put($thumbnailPath, $img->encode())) {
                throw new ImageProcessingException("Failed to generate thumbnail: {$size}");
            }
            $thumbnails[$size] = $thumbnailPath;
        }
        return $thumbnails;
    }

    private function getImageMetadata($image)
    {
        $metadata = [
            'mime_type' => $image->getMimeType(),
            'size' => $image->getSize(),
        ];

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($image);
            if ($exif) {
                $metadata['exif'] = [
                    'make' => $exif['Make'] ?? null,
                    'model' => $exif['Model'] ?? null,
                    'exposure_time' => $exif['ExposureTime'] ?? null,
                    'aperture' => $exif['COMPUTED']['ApertureFNumber'] ?? null,
                    'iso' => $exif['ISOSpeedRatings'] ?? null,
                    'taken_at' => isset($exif['DateTimeOriginal']) ? date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal'])) : null,
                ];
            }
        }

        return $metadata;
    }

    private function moveToPermanentStorage($tempPath, $filename)
    {
        $permanentPath = config('image_upload.path') . '/original/' . $filename;
        if (!Storage::disk(config('image_upload.disk'))->move($tempPath, $permanentPath)) {
            throw new ImageProcessingException("Failed to move image to permanent storage");
        }
        return $permanentPath;
    }

    private function moveThumbnailsToPermanentStorage($tempThumbnails, $filename)
    {
        $permanentThumbnails = [];
        foreach ($tempThumbnails as $size => $tempPath) {
            $permanentPath = config('image_upload.path') . '/' . $size . '/' . $filename;
            if (!Storage::disk(config('image_upload.disk'))->move($tempPath, $permanentPath)) {
                throw new ImageProcessingException("Failed to move thumbnail to permanent storage: {$size}");
            }
            $permanentThumbnails[$size] = $permanentPath;
        }
        return $permanentThumbnails;
    }

    private function saveToDatabase($filename, $originalPath, $thumbnails, $metadata)
    {
        return Image::create([
            'filename' => $filename,
            'disk' => config('image_upload.disk'),
            'original_path' => $originalPath,
            'thumbnails' => $thumbnails,
            'metadata' => $metadata,
        ]);
    }
}
