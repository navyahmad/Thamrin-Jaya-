<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckBackendReadiness extends Command
{
    protected $signature = 'cms:check {--production : Periksa juga konfigurasi wajib produksi}';

    protected $description = 'Audit backend hanya-baca, tanpa mencetak kredensial atau mengubah data';

    public function handle(): int
    {
        $checks = [
            'Application key tersedia' => filled(config('app.key')),
            'Build manifest tersedia' => is_file(public_path('build/manifest.json')),
            'Storage dan cache dapat ditulis' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'Public storage terhubung' => realpath(public_path('storage')) === realpath(storage_path('app/public')) && is_dir(public_path('storage')),
        ];
        try {
            DB::connection()->getPdo();
            $checks['Database terhubung'] = true;
            $migrator = app('migrator');
            $checks['Semua migration diterapkan'] = $migrator->repositoryExists()
                && array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan()) === [];
            $checks['Akun admin tersedia'] = User::where('is_admin', true)->exists();
            $demoCount = Company::where('is_demo', true)->count();
            $this->line('Perusahaan dengan penanda demo: '.$demoCount);
            if ($this->option('production')) {
                $checks['Konten perusahaan demo telah diverifikasi'] = $demoCount === 0;
            }
        } catch (Throwable) {
            $checks['Database dan skema dapat diperiksa'] = false;
        }
        if ($this->option('production')) {
            $checks += [
                'Environment production' => app()->environment('production'),
                'Debug nonaktif' => config('app.debug') === false,
                'APP_URL memakai HTTPS' => parse_url(config('app.url'), PHP_URL_SCHEME) === 'https',
                'Cookie session secure dan HttpOnly' => config('session.secure') === true && config('session.http_only') === true,
                'Mailer bukan log/array' => ! in_array(config('mail.default'), ['log', 'array'], true),
            ];
        }
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'OK' : 'FAIL').' — '.$label);
        }
        $this->line('Queue driver: '.config('queue.default').'; mailer: '.config('mail.default'));
        $this->warn('Audit ini tidak membuktikan SMTP delivery, worker aktif, HTTPS jaringan, atau backup/restore. Verifikasi operasional terpisah tetap diperlukan.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
