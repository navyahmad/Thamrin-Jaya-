<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('sites')->whereNull('company_id')->count() > 1) {
            throw new RuntimeException('Terdapat lebih dari satu holding. Selesaikan duplikasi sebelum migration; tidak ada data yang dihapus otomatis.');
        }

        Schema::table('sites', function (Blueprint $table): void {
            $table->unsignedTinyInteger('holding_slot')->nullable()->virtualAs('CASE WHEN company_id IS NULL THEN 1 ELSE NULL END');
            $table->unique('holding_slot');
        });
        foreach (['products', 'pillars', 'process_steps'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->index();
            });
        }
        Schema::create('content_initializations', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->timestamp('completed_at')->nullable();
        });
        foreach (['demo-companies-v1' => 'companies', 'dynamic-content-v1' => 'sites'] as $key => $table) {
            if (DB::table($table)->exists()) {
                DB::table('content_initializations')->insert(['key' => $key, 'completed_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_initializations');
        foreach (['products', 'pillars', 'process_steps'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropUnique(['holding_slot']);
            $table->dropColumn('holding_slot');
        });
    }
};
