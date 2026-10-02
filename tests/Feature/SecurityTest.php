<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Pillar;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use App\Policies\CmsContentPolicy;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithTwoFactor(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'password' => 'StrongPassword123',
            'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['first-recovery-code', 'second-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function code(User $user): string
    {
        return (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
    }

    private function beginChallenge(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword123'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_all_cms_modules_have_explicit_admin_only_policies(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $visitor = User::factory()->create();
        foreach ([Company::class, Product::class, Pillar::class, ProcessStep::class, Inquiry::class, Site::class, Page::class, PageSection::class, SectionItem::class, Menu::class, MenuItem::class, Service::class] as $model) {
            $record = $model::factory()->create();
            $this->assertNotNull(Gate::getPolicyFor($model));
            foreach (['viewAny', 'create'] as $ability) {
                $this->assertTrue(Gate::forUser($admin)->allows($ability, $model));
                $this->assertFalse(Gate::forUser($visitor)->allows($ability, $model));
            }
            foreach (['view', 'update', 'delete'] as $ability) {
                $this->assertTrue(Gate::forUser($admin)->allows($ability, $record));
                $this->assertFalse(Gate::forUser($visitor)->allows($ability, $record));
            }
        }
    }

    public function test_controllers_enforce_model_policy_in_addition_to_admin_gate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::factory()->create();
        $this->mock(CmsContentPolicy::class)->shouldReceive('delete')->once()->andReturn(false);
        $this->actingAs($admin)->delete(route('admin.content.destroy', [$product->company, 'products', $product->id]))->assertForbidden();
        $this->assertModelExists($product);
    }

    public function test_security_endpoints_require_admin_and_recent_password_confirmation(): void
    {
        $this->get(route('admin.account.two-factor'))->assertRedirect('/login');
        $this->post(route('two-factor.enable'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('admin.account.two-factor'))->assertForbidden();
        $this->post(route('two-factor.enable'))->assertForbidden();
        $admin = $this->adminWithTwoFactor();
        session()->forget('password_hash_web');
        $this->actingAs($admin)->get(route('admin.account.two-factor'))->assertRedirect(route('password.confirm'));
        foreach (['two-factor.qr-code', 'two-factor.secret-key', 'two-factor.recovery-codes'] as $route) {
            $this->getJson(route($route))->assertStatus(423);
        }
        $this->deleteJson(route('two-factor.disable'))->assertStatus(423);
        $this->postJson(route('two-factor.regenerate-recovery-codes'))->assertStatus(423);
        $this->withSession(['auth.password_confirmed_at' => now()->subSeconds(config('auth.password_timeout') + 1)->timestamp])
            ->get(route('admin.account.two-factor'))->assertRedirect(route('password.confirm'));
    }

    public function test_setup_requires_totp_confirmation_and_secrets_are_encrypted_and_hidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->timestamp]);
        $this->from(route('admin.account.two-factor'))->post(route('two-factor.enable'))->assertRedirect(route('admin.account.two-factor'));
        $admin->refresh();
        $this->assertFalse($admin->hasEnabledTwoFactorAuthentication());
        $this->assertNotSame(decrypt($admin->two_factor_secret), $admin->getRawOriginal('two_factor_secret'));
        $this->assertArrayNotHasKey('two_factor_secret', $admin->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $admin->toArray());
        $this->get(route('admin.account.two-factor'))->assertOk()->assertSee('<svg', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->post(route('two-factor.confirm'), ['code' => 'invalid'])->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code')->assertSessionMissing('_old_input.code');
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
        $this->post(route('two-factor.confirm'), ['code' => $this->code($admin)])->assertSessionHas('status', 'two-factor-authentication-confirmed');
        $this->assertTrue($admin->fresh()->hasEnabledTwoFactorAuthentication());
        $this->get(route('admin.account.two-factor'))->assertOk()->assertSee($admin->fresh()->recoveryCodes()[0]);
    }

    public function test_pending_setup_does_not_lock_user_out_of_password_login(): void
    {
        $admin = $this->adminWithTwoFactor();
        $admin->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->post('/login', ['email' => $admin->email, 'password' => 'StrongPassword123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_totp_challenge_is_required_before_cms_access_and_can_complete(): void
    {
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        $this->get('/admin')->assertRedirect('/login');
        $this->get(route('two-factor.login'))->assertOk()->assertDontSee($admin->two_factor_secret);
        $this->post(route('two-factor.login.store'), ['code' => 'invalid'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post(route('two-factor.login.store'), ['code' => $this->code($admin)])->assertRedirect('/admin')->assertSessionMissing('login.id')->assertSessionMissing('two_factor_challenge');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_recovery_code_is_single_use_and_replaced_after_login(): void
    {
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotContains('first-recovery-code', $admin->fresh()->recoveryCodes());
        $this->post('/logout')->assertRedirect('/login');
        $this->beginChallenge($admin);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertSessionHasErrors('recovery_code')->assertSessionMissing('_old_input.recovery_code');
        $this->assertGuest();
    }

    public function test_regenerating_and_disabling_two_factor_only_affects_current_admin(): void
    {
        $admin = $this->adminWithTwoFactor();
        $other = $this->adminWithTwoFactor();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->timestamp]);
        $this->post(route('two-factor.regenerate-recovery-codes'), ['user_id' => $other->id])->assertRedirect();
        $this->assertNotContains('first-recovery-code', $admin->fresh()->recoveryCodes());
        $this->assertContains('first-recovery-code', $other->fresh()->recoveryCodes());
        $this->delete(route('two-factor.disable'), ['user_id' => $other->id])->assertRedirect();
        $admin->refresh();
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertTrue($other->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_challenge_expires_and_cannot_be_used_without_password_step(): void
    {
        $this->get(route('two-factor.login'))->assertRedirect('/login');
        $this->postJson(route('two-factor.login.store'), ['code' => '123456'])->assertUnauthorized();
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        $this->travel(11)->minutes();
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertRedirect('/login')->assertSessionMissing('login.id');
        $this->assertGuest();
    }

    public function test_revoked_admin_changed_password_or_disabled_two_factor_invalidates_pending_challenge(): void
    {
        foreach ([['is_admin' => false], ['password' => 'DifferentPassword123'], ['two_factor_secret' => null, 'two_factor_confirmed_at' => null]] as $changes) {
            $admin = $this->adminWithTwoFactor();
            $this->beginChallenge($admin);
            $admin->forceFill($changes)->save();
            $this->post(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertRedirect('/login')->assertSessionMissing('login.id');
            $this->assertGuest();
        }
    }

    public function test_challenge_requests_are_rate_limited(): void
    {
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('two-factor.login.store'), ['code' => 'invalid'])->assertUnprocessable();
        }
        $this->postJson(route('two-factor.login.store'), ['code' => $this->code($admin)])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_account_page_does_not_expose_secrets_and_sensitive_responses_are_not_cached(): void
    {
        $admin = $this->adminWithTwoFactor();
        $response = $this->actingAs($admin)->get('/admin/account')->assertOk()
            ->assertDontSee(decrypt($admin->two_factor_secret))->assertDontSee('first-recovery-code');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->withSession(['auth.password_confirmed_at' => now()->timestamp]);
        $response = $this->getJson(route('two-factor.recovery-codes'))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $admin->fill(['is_admin' => false, 'two_factor_secret' => 'injected', 'two_factor_recovery_codes' => 'injected', 'two_factor_confirmed_at' => null]);
        $this->assertTrue($admin->is_admin);
        $this->assertNotSame('injected', $admin->two_factor_secret);
        $this->assertNotNull($admin->two_factor_confirmed_at);
    }

    public function test_used_totp_code_cannot_be_replayed_in_a_new_login_challenge(): void
    {
        $admin = $this->adminWithTwoFactor();
        $code = $this->code($admin);
        $this->beginChallenge($admin);
        $this->postJson(route('two-factor.login.store'), ['code' => $code])->assertNoContent();
        $this->post('/logout')->assertRedirect('/login');
        $this->beginChallenge($admin);
        $this->postJson(route('two-factor.login.store'), ['code' => $code])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertGuest();
    }

    public function test_failed_new_password_attempt_clears_previous_challenge(): void
    {
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email')->assertSessionMissing('login.id');
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_database_cache_keeps_failed_challenge_attempts_when_auth_transaction_rolls_back(): void
    {
        $limiter = new RateLimiter(app('cache')->store('database'));
        foreach (['login', 'fortify-requests'] as $name) {
            $limiter->for($name, \Illuminate\Support\Facades\RateLimiter::limiter($name));
        }
        \Illuminate\Support\Facades\RateLimiter::swap($limiter);
        $admin = $this->adminWithTwoFactor();
        $this->beginChallenge($admin);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'invalid'])->assertUnprocessable();
        }
        $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'first-recovery-code'])->assertStatus(429);
        $this->assertGuest();
    }
}
