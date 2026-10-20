<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic\Rules;

use Closure;
use Hazelchat\Statamic\Key;
use Illuminate\Contracts\Validation\ValidationRule;

/** The settings field takes the installation code or its key, nothing else. */
final class InstallationCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || (\is_string($value) && trim($value) === '')) {
            return;
        }
        if (Key::fromInput($value) === null) {
            $fail(__('hazelchat::settings.code_invalid'));
        }
    }
}
