<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    /** @param Request $request */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $message = 'Jika email terdaftar sebagai admin, tautan pemulihan akan dikirim.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }
}
