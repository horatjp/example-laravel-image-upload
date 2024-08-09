<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeleteOldTempImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $disk = Storage::disk(config('image_upload.disk'));
        $tempPath = config('image_upload.temp_path');
        $lifetime = config('image_upload.temp_file_lifetime', 60); // default to 60 minutes

        $threshold = Carbon::now()->subMinutes($lifetime);

        $files = $disk->allFiles($tempPath);

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
            if ($lastModified->lt($threshold)) {
                $disk->delete($file);
                Log::info("Deleted temporary file: {$file}");
            }
        }

        Log::info("Temporary file cleanup completed. Processed " . count($files) . " files.");
    }
}
