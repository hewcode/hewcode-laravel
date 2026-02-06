<?php

namespace Hewcode\Hewcode\Panel\Controllers\Auth;

use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Panel\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(Hewcode::route('dashboard', absolute: false).'?verified=1');
        }

        $request->fulfill();

        return redirect()->intended(Hewcode::route('dashboard', absolute: false).'?verified=1');
    }
}
