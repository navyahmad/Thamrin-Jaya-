<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_identifies_missing_admin_without_creating_one(): void
    {
        $this->artisan('cms:check')->expectsOutputToContain('FAIL — Akun admin tersedia')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_audit_rejects_local_configuration_without_printing_secrets(): void
    {
        User::factory()->create(['is_admin' => true]);
        config(['app.debug' => true, 'app.url' => 'http://localhost', 'mail.default' => 'log', 'session.secure' => false]);
        $this->artisan('cms:check', ['--production' => true])
            ->expectsOutputToContain('OK — Akun admin tersedia')
            ->expectsOutputToContain('FAIL — Debug nonaktif')
            ->expectsOutputToContain('FAIL — APP_URL memakai HTTPS')
            ->expectsOutputToContain('FAIL — Mailer bukan log/array')->assertFailed();
    }
}
