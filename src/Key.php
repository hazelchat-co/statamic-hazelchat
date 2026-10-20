<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic;

/**
 * The public widget key and the one script tag, as plain functions with no
 * Statamic dependency, so they can be tested with PHP alone. The key is
 * public: it names an agent and grants no access to the Hazelchat account.
 */
final class Key
{
    /** The only address this addon ever prints. */
    public const SCRIPT_URL = 'https://app.hazelchat.co/widget.js';

    /** Hazelchat counts installations of this addon by this id. */
    public const SCRIPT_ID = 'hazelchat-statamic';

    /** Room for a pasted installation code; the key itself is 64 characters. */
    public const MAX_INPUT = 1000;

    public static function valid(mixed $key): bool
    {
        return \is_string($key) && preg_match('/\A[a-f0-9]{64}\z/', $key) === 1;
    }

    /**
     * The setting takes the whole installation code or just the key. A bare
     * 64-hex value is the key; otherwise the text must hold exactly one
     * data-agent attribute (outside HTML comments) whose value is 64
     * lowercase hex. Anything else gives null, and nothing is printed.
     */
    public static function fromInput(mixed $value): ?string
    {
        if (! \is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || \strlen($value) > self::MAX_INPUT) {
            return null;
        }
        if (self::valid($value)) {
            return $value;
        }

        // A key that is only mentioned in a comment is not the code.
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? '';
        if (str_contains($value, '<!--')) {
            return null;
        }

        // Exactly one data-agent attribute; look-alikes such as
        // xdata-agent or data-agent-x are not it, and two are ambiguous.
        $attribute = '(?<![A-Za-z0-9_:-])data-agent(?![A-Za-z0-9_:-])\s*=';
        if (preg_match_all('/'.$attribute.'/i', $value) !== 1) {
            return null;
        }
        if (preg_match('/'.$attribute.'\s*(?:"([a-f0-9]{64})"|\'([a-f0-9]{64})\'|([a-f0-9]{64})(?=[\s>\/]|\z))/', $value, $match) !== 1) {
            return null;
        }

        $key = implode('', \array_slice($match, 1));

        return self::valid($key) ? $key : null;
    }

    /**
     * The script tag, or '' when nothing may be printed. No inline code, no
     * data-platform and no address but SCRIPT_URL. A nonce is added only
     * when the site already sets one for its own scripts (Laravel's Vite
     * nonce, used by CSP packages).
     */
    public static function tag(?string $key, bool $requireConsent, ?string $nonce = null): string
    {
        if ($key === null || ! self::valid($key)) {
            return '';
        }

        $attributes = [
            'id' => self::SCRIPT_ID,
            'data-agent' => $key,
        ];
        if ($requireConsent) {
            $attributes['data-require-consent'] = 'true';
        }
        if (\is_string($nonce) && $nonce !== '') {
            $attributes['nonce'] = $nonce;
        }
        $attributes['src'] = self::SCRIPT_URL;

        $html = '<script';
        foreach ($attributes as $name => $value) {
            $html .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return $html.' defer></script>';
    }
}
