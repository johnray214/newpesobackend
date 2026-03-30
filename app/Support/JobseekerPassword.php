<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Jobseeker password policy: min 8 chars, upper & lower, number, symbol.
 * Keep in sync with Flutter {@see untitled1/lib/password_rules.dart}.
 */
class JobseekerPassword
{
    public static function createRules(): array
    {
        return [
            'required',
            'confirmed',
            Password::min(8)->mixedCase()->numbers()->symbols(),
        ];
    }
}
