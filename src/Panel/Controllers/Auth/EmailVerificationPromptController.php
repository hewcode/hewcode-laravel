<?php

namespace Hewcode\Hewcode\Panel\Controllers\Auth;

use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Panel\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(Hewcode::route('dashboard', absolute: false))
                    : Inertia::render('hewcode/auth/verify-email', ['status' => $request->session()->get('status')]);
    }
}
