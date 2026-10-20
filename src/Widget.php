<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic;

use Illuminate\Support\Facades\Vite;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;
use Statamic\Sites\Site as SiteEntity;

/**
 * Which installation code applies to the current site, and the tag it gives.
 *
 * In order: a "hazelchat" attribute on the site (Configure → Sites), for a
 * multi-site install where one site needs another code or none; the code
 * saved under Tools → Addons → Hazelchat (Statamic 6);
 * HAZELCHAT_INSTALLATION_CODE.
 * Values are read raw, never parsed as Antlers.
 */
final class Widget
{
    public const PACKAGE = 'hazelchat/statamic-hazelchat';

    /** The site attribute that overrides the code for one site. */
    public const SITE_ATTRIBUTE = 'hazelchat';

    public static function key(): ?string
    {
        $attributes = self::siteAttributes();
        if (\array_key_exists(self::SITE_ATTRIBUTE, $attributes)) {
            return Key::fromInput($attributes[self::SITE_ATTRIBUTE]);
        }

        $saved = self::setting('installation_code');
        if (\is_string($saved) && trim($saved) !== '') {
            return Key::fromInput($saved);
        }

        return Key::fromInput(config('hazelchat.installation_code'));
    }

    /** Either the saved setting or the .env value turns it on. */
    public static function waitForConsent(): bool
    {
        return self::truthy(self::setting('wait_for_consent'))
            || self::truthy(config('hazelchat.wait_for_consent'));
    }

    public static function html(): string
    {
        return Key::tag(self::key(), self::waitForConsent(), Vite::cspNonce());
    }

    /** @return array<string, mixed> */
    private static function siteAttributes(): array
    {
        $site = Site::current();
        $attributes = $site instanceof SiteEntity ? $site->attributes() : [];

        return \is_array($attributes) ? $attributes : [];
    }

    /** Addon settings exist from Statamic 6; on 5 there are none. */
    private static function setting(string $handle): mixed
    {
        $addon = Addon::get(self::PACKAGE);
        if ($addon === null || ! method_exists($addon, 'settings')) {
            return null;
        }

        return $addon->settings()->raw()[$handle] ?? null;
    }

    private static function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
