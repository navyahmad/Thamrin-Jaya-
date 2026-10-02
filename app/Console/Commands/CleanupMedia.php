<?php

namespace App\Console\Commands;

use App\Actions\Cms\MediaManager;
use Illuminate\Console\Command;

class CleanupMedia extends Command
{
    protected $signature = 'media:cleanup {path : Path upload yang tercatat pada log cleanup} {--delete : Hapus file jika tidak lagi direferensikan}';

    protected $description = 'Periksa atau ulangi cleanup satu file upload setelah kegagalan storage';

    public function handle(MediaManager $media): int
    {
        $path = MediaManager::uploadPath($this->argument('path'));
        if ($path === null) {
            $this->error('Path tidak valid atau merupakan aset bawaan.');

            return self::FAILURE;
        }
        if ($media->referenced($path)) {
            $this->info('File masih direferensikan; tidak dihapus.');

            return self::SUCCESS;
        }
        if (! $this->option('delete')) {
            $this->info('File tidak direferensikan. Gunakan --delete untuk mengulangi cleanup.');

            return self::SUCCESS;
        }
        if (! $media->cleanupUnused($path)) {
            $this->error('File tidak dihapus. Periksa referensi terbaru atau log storage.');

            return self::FAILURE;
        }
        $this->info('Cleanup selesai.');

        return self::SUCCESS;
    }
}
