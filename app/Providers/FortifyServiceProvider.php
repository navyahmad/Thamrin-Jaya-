<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Responses\PasswordResetLinkResponse;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
    }

    public function boot(): void
    {
        Auth::provider('admin-eloquent', function (Application $app, array $config): EloquentUserProvider {
            return (new EloquentUserProvider($app['hash'], $config['model']))
                ->withQuery(fn (Builder $query): Builder => $query->where('is_admin', true));
        });

        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn (): View => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn (): View => view('auth.forgot'));
        Fortify::resetPasswordView(fn (Request $request): View => view('auth.reset', [
            'token' => $request->route('token'),
            'email' => $request->query('email'),
        ]));
        Fortify::twoFactorChallengeView(fn (): View => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn (): View => view('auth.confirm-password'));

        RateLimiter::for('login', function (Request $request): array {
            $email = $request->input('email');
            $key = Str::lower(is_string($email) ? $email : '').'|'.$request->ip();

            return [Limit::perMinute(5)->by('account:'.$key), Limit::perMinute(20)->by('ip:'.$request->ip())];
        });

        RateLimiter::for('fortify-requests', function (Request $request): Limit|array {
            return match ($request->route()->getName()) {
                'two-factor.login.store' => [
                    Limit::perMinute(5)->by('challenge:'.$request->session()->get('login.id').'|'.$request->ip()),
                    Limit::perMinute(20)->by('challenge-ip:'.$request->ip()),
                ],
                'password.email' => Limit::perMinute(3)->by('forgot:'.$request->ip()),
                'password.update' => Limit::perMinute(5)->by('reset:'.$request->ip()),
                'two-factor.confirm', 'two-factor.enable', 'two-factor.disable', 'two-factor.regenerate-recovery-codes', 'password.confirm.store', 'user-password.update' => Limit::perMinute(5)->by('password:'.$request->ip()),
                default => Limit::none(),
            };
        });
    }
}
