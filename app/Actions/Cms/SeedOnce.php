<?php

namespace App\Actions\Cms;

use Closure;
use Illuminate\Support\Facades\DB;

class SeedOnce
{
    /** @param Closure(): void $initialize */
    public static function run(string $key, Closure $initialize): void
    {
        DB::transaction(function () use ($key, $initialize): void {
            DB::table('content_initializations')->insertOrIgnore(['key' => $key, 'completed_at' => null]);
            $state = DB::table('content_initializations')->where('key', $key)->lockForUpdate()->first();
            if ($state->completed_at !== null) {
                return;
            }
            $initialize();
            DB::table('content_initializations')->where('key', $key)->update(['completed_at' => now()]);
        });
    }
}
