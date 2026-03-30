<?php

namespace App\Http\Controllers;

use App\Support\JobseekerPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class JobseekerPasswordResetWebController extends Controller
{
    public function show(Request $request)
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if ($token === '' || $email === '') {
            return response()->view('jobseeker.reset-password', [
                'token' => $token,
                'email' => $email,
                'linkError' => 'This reset link is invalid or incomplete. Request a new link from PESO Connect.',
            ], 422);
        }

        $broker = Password::broker('jobseekers');
        $user = $broker->getUser(['email' => $email]);

        if (! $user || ! $broker->tokenExists($user, $token)) {
            return response()->view('jobseeker.reset-password', [
                'token' => $token,
                'email' => $email,
                'linkError' => 'This reset link is invalid, expired, or has already been used. Request a new link from PESO Connect.',
            ], 422);
        }

        return view('jobseeker.reset-password', [
            'token' => $token,
            'email' => $email,
            'linkError' => null,
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => JobseekerPassword::createRules(),
        ]);

        $status = Password::broker('jobseekers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return view('jobseeker.reset-password-success');
        }

        return back()
            ->withErrors(['email' => $this->messageForStatus($status)])
            ->withInput($request->only('email'));
    }

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            PasswordBroker::INVALID_TOKEN => 'This reset link is invalid or has expired. Request a new one from the app.',
            PasswordBroker::INVALID_USER => 'We could not find an account for that email.',
            default => 'Unable to reset your password. Please try again.',
        };
    }
}
