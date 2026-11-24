<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $request->input('login');
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$fieldType => $loginInput, 'password' => $request->input('password')], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (! $user->profile_completed_at) {
                return redirect()->route('profile.edit');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'regex:/^\S+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.regex' => 'The username field must not contain any spaces.',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('verification.notice')->withErrors([
                'email' => 'Account created, but the verification email could not be sent. Please try again later.',
            ]);
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Account created! Please check your email inbox for the confirmation link.');
    }

    public function showVerifyNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $request->user()->profile_completed_at
                ? redirect()->route('dashboard')
                : redirect()->route('profile.edit');
        }

        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        if (! $request->user()->profile_completed_at) {
            return redirect()->route('profile.edit')
                ->with('success', 'Your email is verified! Complete your administrator profile below.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Your email address has been verified.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Could not send the verification email. Please try again later.',
            ]);
        }

        return back()->with('success', 'A fresh verification link has been sent to your email address.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
