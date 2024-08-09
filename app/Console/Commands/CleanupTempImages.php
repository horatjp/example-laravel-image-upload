<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DeleteOldTempImages;

class CleanupTempImages extends Command
{
    protected $signature = 'app:cleanup-temp';
    protected $description = 'Cleanup old temporary images';

    public function handle()
    {
        DeleteOldTempImages::dispatch();
        $this->info('Temporary image cleanup job has been dispatched.');
    }
}
