<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProtectTwoFactorChallenge
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('two-factor.login.store')) {
            return DB::transaction(fn (): Response => $this->process($request, $next));
        }

        return $this->process($request, $next);
    }

    /** @param Closure(Request): Response $next */
    private function process(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login.store')) {
            $request->session()->forget(['login', 'two_factor_challenge']);
        }

        if ($request->routeIs('two-factor.login', 'two-factor.login.store')) {
            $query = User::query();
            if ($request->routeIs('two-factor.login.store')) {
                $query->lockForUpdate();
            }
            $user = $query->find($request->session()->get('login.id'));
            $challenge = $request->session()->get('two_factor_challenge', []);
            if (! $user || ! $user->is_admin || ! $user->hasEnabledTwoFactorAuthentication()
                || ($challenge['expires_at'] ?? 0) < now()->timestamp
                || ! hash_equals($this->fingerprint($user), $challenge['fingerprint'] ?? '')) {
                $request->session()->forget(['login', 'two_factor_challenge']);

                return $request->expectsJson()
                    ? response()->json(['message' => 'Silakan masuk kembali.'], 401)
                    : redirect()->route('login');
            }
        }

        $response = $next($request);

        if ($request->routeIs('login.store') && $request->session()->has('login.id')) {
            $user = User::findOrFail($request->session()->get('login.id'));
            $request->session()->regenerate();
            $request->session()->put('two_factor_challenge', [
                'expires_at' => now()->addMinutes(10)->timestamp,
                'fingerprint' => $this->fingerprint($user),
            ]);
        }

        if ($request->routeIs('two-factor.login.store') && $request->user()) {
            $request->session()->forget(['login', 'two_factor_challenge']);
        }

        return $response;
    }

    private function fingerprint(User $user): string
    {
        return hash('sha256', $user->password.'|'.$user->two_factor_secret);
    }
}
