<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'disk', 'original_path', 'thumbnails', 'metadata'];

    protected $casts = [
        'thumbnails' => 'array',
        'metadata' => 'array',
    ];

    public function getUrl($size = null)
    {
        if ($size === null) {
            $size = config('image_upload.default_size');
        }

        if ($size && isset($this->thumbnails[$size])) {
            return Storage::disk($this->disk)->url($this->thumbnails[$size]);
        }

        return Storage::disk($this->disk)->url($this->original_path);
    }

    public function getThumbnailUrls()
    {
        return collect($this->thumbnails)->mapWithKeys(function ($path, $size) {
            return [$size => Storage::disk($this->disk)->url($path)];
        })->all();
    }
}
