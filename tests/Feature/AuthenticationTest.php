<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_pages_render(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example?email=admin@example.com')->assertOk();
    }

    public function test_administrator_can_login_and_logout(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'StrongPassword123']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'StrongPassword123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/login')->assertRedirect('/admin');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_and_non_admin_accounts_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'StrongPassword123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword123'])->assertSessionHasErrors('email');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->post('/login', ['email' => $admin->email, 'password' => 'bad-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'missing@example.com', 'password' => 'bad-password'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'missing@example.com', 'password' => 'bad-password'])->assertStatus(429);
    }

    public function test_admin_password_can_be_changed_with_current_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'OriginalPassword123']);
        $this->actingAs($admin)->put(route('user-password.update'), ['current_password' => 'wrong', 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'])->assertSessionHasErrors('current_password');
        $this->put(route('user-password.update'), ['current_password' => 'OriginalPassword123', 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword12345', $admin->fresh()->password));
    }

    public function test_reset_link_only_sent_to_admin_and_password_can_be_reset(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('success');
        Notification::assertNothingSent();
        $this->post(route('password.email'), ['email' => $admin->email])->assertSessionHas('success');
        Notification::assertSentTo($admin, ResetPassword::class);
        $token = Password::createToken($admin);
        $this->post(route('password.update'), ['email' => $admin->email, 'token' => $token, 'password' => 'ResetPassword12345', 'password_confirmation' => 'ResetPassword12345'])->assertRedirect('/login');
        $this->assertTrue(Hash::check('ResetPassword12345', $admin->fresh()->password));
        $this->post(route('password.update'), ['email' => $admin->email, 'token' => $token, 'password' => 'AnotherPassword123', 'password_confirmation' => 'AnotherPassword123'])->assertSessionHasErrors('email');
    }

    public function test_admin_command_creates_account_without_overwriting_existing_users(): void
    {
        $this->artisan('admin:create', ['email' => 'cms@example.com', '--generate' => true])->assertSuccessful();
        $this->assertTrue(User::where('email', 'cms@example.com')->firstOrFail()->is_admin);
        $this->artisan('admin:create', ['email' => 'cms@example.com', '--generate' => true])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_fortify_owns_auth_routes_and_registration_and_unused_features_are_disabled(): void
    {
        foreach (['login', 'login.store', 'logout', 'password.email', 'password.update', 'user-password.update'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringStartsWith('Laravel\\Fortify\\Http\\Controllers\\', $route->getActionName());
        }

        foreach (['register', 'register.store', 'passkey.store', 'user-profile-information.update', 'admin.password.update', 'password.store'] as $name) {
            $this->assertNull(app('router')->getRoutes()->getByName($name));
        }
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'new@example.com', 'password' => 'StrongPassword123'])->assertStatus(405);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_update_requires_admin_and_does_not_allow_privilege_escalation(): void
    {
        $this->put(route('user-password.update'))->assertRedirect('/login');
        $user = User::factory()->create(['password' => 'OriginalPassword123']);
        $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'OriginalPassword123',
            'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123',
            'is_admin' => true,
        ])->assertForbidden();
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertTrue(Hash::check('OriginalPassword123', $user->fresh()->password));
        $this->get(route('password.confirm'))->assertForbidden();
    }

    public function test_recovery_response_does_not_disclose_account_existence_or_broker_throttling(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $message = ['message' => 'Jika email terdaftar sebagai admin, tautan pemulihan akan dikirim.'];
        $this->postJson(route('password.email'), ['email' => $admin->email])->assertOk()->assertExactJson($message);
        $this->postJson(route('password.email'), ['email' => $user->email])->assertOk()->assertExactJson($message);
        $this->postJson(route('password.email'), ['email' => 'missing@example.com'])->assertOk()->assertExactJson($message);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson(route('password.email'), ['email' => $admin->email])->assertOk()->assertExactJson($message);
        Notification::assertSentToTimes($admin, ResetPassword::class, 1);
        Notification::assertNotSentTo($user, ResetPassword::class);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_non_admin_cannot_reset_even_with_a_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'OriginalPassword123']);
        $token = Password::createToken($user);
        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'ResetPassword12345', 'password_confirmation' => 'ResetPassword12345',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OriginalPassword123', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_expired_reset_tokens_and_weak_or_unconfirmed_passwords_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'OriginalPassword123']);
        $token = Password::createToken($admin);
        $input = ['email' => $admin->email, 'token' => $token, 'password' => 'short1', 'password_confirmation' => 'short1'];
        $this->post(route('password.update'), $input)->assertSessionHasErrors('password');
        $this->post(route('password.update'), [...$input, 'password' => 'StrongPassword123'])->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), [...$input, 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OriginalPassword123', $admin->fresh()->password));
    }

    public function test_recovery_and_reset_endpoints_are_rate_limited(): void
    {
        Notification::fake();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('password.email'), ['email' => 'missing@example.com'])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => 'missing@example.com'])->assertStatus(429);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.update'), ['email' => 'missing@example.com', 'token' => 'invalid', 'password' => 'StrongPassword123'])->assertRedirect();
        }
        $this->post(route('password.update'), ['email' => 'missing@example.com', 'token' => 'invalid', 'password' => 'StrongPassword123'])->assertStatus(429);
    }

    public function test_password_update_rotates_remember_token_invalidates_reset_token_and_keeps_current_session(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'OriginalPassword123', 'remember_token' => 'original-token']);
        $token = Password::createToken($admin);
        $this->post('/login', ['email' => $admin->email, 'password' => 'OriginalPassword123'])->assertRedirect('/admin');
        $this->get('/admin/account')->assertOk();
        $sessionId = session()->getId();
        $this->from('/admin/account')->put(route('user-password.update'), [
            'current_password' => 'OriginalPassword123',
            'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123',
        ])->assertRedirect('/admin/account')->assertSessionHas('status', 'password-updated');
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('original-token', $admin->fresh()->remember_token);
        $this->assertFalse(Password::tokenExists($admin->fresh(), $token));
        $this->get('/admin/account')->assertOk()->assertSee('Password berhasil diperbarui.');
    }

    public function test_login_and_logout_rotate_session_and_do_not_flash_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'StrongPassword123']);
        $this->withSession(['marker' => 'private-session']);
        $oldId = session()->getId();
        $this->post('/login', ['email' => $admin->email, 'password' => 'StrongPassword123', 'remember' => '1'])->assertRedirect('/admin');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotNull($admin->fresh()->remember_token);
        $oldToken = session()->token();
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('marker');
        $this->assertNotSame($oldToken, session()->token());
        $this->post('/login', ['email' => $admin->email, 'password' => 'WrongPassword123'])->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');
    }

    public function test_revoked_admin_is_not_retrieved_by_session_remember_or_password_broker_provider(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'remember_token' => 'remember-secret']);
        $provider = auth()->guard('web')->getProvider();
        $this->assertNotNull($provider->retrieveById($admin->id));
        $admin->forceFill(['is_admin' => false])->save();
        $this->assertNull($provider->retrieveById($admin->id));
        $this->assertNull($provider->retrieveByToken($admin->id, 'remember-secret'));
        $this->assertNull(Password::getUser(['email' => $admin->email]));
    }

    public function test_builtin_password_confirmation_screen_and_validation_work(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'StrongPassword123']);
        $this->actingAs($admin)->get(route('password.confirm'))->assertOk();
        $this->post(route('password.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post(route('password.confirm.store'), ['password' => 'StrongPassword123'])->assertRedirect('/admin')->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_existing_session_is_rejected_after_password_reset_elsewhere(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'OriginalPassword123']);
        $oldHash = $admin->password;
        $token = Password::createToken($admin);
        $this->post(route('password.update'), [
            'email' => $admin->email, 'token' => $token,
            'password' => 'ResetPassword12345', 'password_confirmation' => 'ResetPassword12345',
        ])->assertRedirect('/login');
        $this->actingAs($admin->fresh())->withSession(['password_hash_web' => $oldHash])->get('/admin/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_login_input_and_password_update_validation_are_handled(): void
    {
        $this->postJson('/login', ['email' => ['bad'], 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'OriginalPassword123']);
        foreach ([['short1', 'short1'], ['OnlyLettersPassword', 'OnlyLettersPassword'], ['ChangedPassword123', 'mismatch']] as [$password, $confirmation]) {
            $this->actingAs($admin)->put(route('user-password.update'), [
                'current_password' => 'OriginalPassword123', 'password' => $password, 'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors('password');
        }
        $this->assertTrue(Hash::check('OriginalPassword123', $admin->fresh()->password));
    }
}
