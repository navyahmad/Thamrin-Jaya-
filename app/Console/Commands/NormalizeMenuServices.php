<?php

namespace App\Console\Commands;

use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NormalizeMenuServices extends Command
{
    protected $signature = 'cms:normalize-menu-services {--apply : Nonaktifkan target layanan yang tidak sesuai perusahaan}';

    protected $description = 'Audit target layanan pada menu anak perusahaan; default tidak mengubah data';

    public function handle(): int
    {
        $count = DB::transaction(function (): int {
            $items = MenuItem::where('is_active', true)->whereNotNull('service_id')
                ->whereHas('site', fn (Builder $site): Builder => $site->whereNotNull('company_id'))
                ->with('site.company.services')->lockForUpdate()->get();
            $invalid = $items->filter(fn (MenuItem $item): bool => ! $item->site->company->services->contains('id', $item->service_id));
            if ($this->option('apply')) {
                foreach ($invalid as $item) {
                    $item->update(['is_active' => false]);
                }
            }

            return $invalid->count();
        });
        $this->info($count.' item layanan '.($this->option('apply') ? 'dinonaktifkan.' : 'tidak sesuai konteks. Gunakan --apply untuk menonaktifkan.'));

        return self::SUCCESS;
    }
}
