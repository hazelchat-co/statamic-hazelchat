<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic;

use Hazelchat\Statamic\Http\Middleware\InjectWidget;
use Hazelchat\Statamic\Rules\InstallationCode;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Statamic\Events\AddonSettingsSaving;
use Statamic\Providers\AddonServiceProvider;

/**
 * Adds the Hazelchat chat to a Statamic site: one script from
 * app.hazelchat.co with the site's public widget key. Nothing is sent from
 * the site to Hazelchat, there are no database tables, no scheduled tasks
 * and no telemetry; the Control Panel never loads the chat.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Hazelchat::class,
    ];

    public function bootAddon(): void
    {
        // The chat goes in last in Statamic's front-end group, inside its
        // static caching, so cached pages and full-caching files carry it.
        // Statamic 5 builds that group only once the app has booted (after
        // addons), so the order is set then; on 6 this runs at once.
        $this->app->booted(function (): void {
            $router = $this->app->make(Router::class);
            $group = array_values(array_filter(
                $router->getMiddlewareGroups()['statamic.web'] ?? [],
                fn ($middleware) => $middleware !== InjectWidget::class
            ));
            $group[] = InjectWidget::class;
            $router->middlewareGroup('statamic.web', $group);
        });

        // Statamic 6 has a settings page per addon; Statamic 5 uses .env.
        if (method_exists($this, 'registerSettingsBlueprint')) {
            $this->registerSettingsBlueprint(fn () => self::settingsBlueprint());
        }

        // Only the key is kept, never the pasted script.
        if (class_exists(AddonSettingsSaving::class)) {
            Event::listen(AddonSettingsSaving::class, function (AddonSettingsSaving $event): void {
                $settings = $event->settings;
                if ($settings->addon()->package() !== Widget::PACKAGE) {
                    return;
                }
                $code = $settings->raw()['installation_code'] ?? null;
                if (\is_string($code) && trim($code) !== '') {
                    $settings->set('installation_code', Key::fromInput($code) ?? '');
                }
            });
        }
    }

    /**
     * Built when the page opens, so labels follow the user's language.
     *
     * @return array<string, mixed>
     */
    public static function settingsBlueprint(): array
    {
        return [
            'tabs' => [
                'main' => [
                    'sections' => [
                        [
                            // Without a title Statamic hides the section's
                            // text, and that text is the hosted-service notice.
                            'display' => __('hazelchat::settings.section'),
                            'instructions' => __('hazelchat::settings.section_instructions'),
                            'fields' => [
                                [
                                    'handle' => 'installation_code',
                                    'field' => [
                                        'type' => 'textarea',
                                        'display' => __('hazelchat::settings.code_display'),
                                        'instructions' => __('hazelchat::settings.code_instructions'),
                                        'validate' => ['nullable', 'string', 'max:'.Key::MAX_INPUT, 'new '.InstallationCode::class],
                                    ],
                                ],
                                [
                                    'handle' => 'wait_for_consent',
                                    'field' => [
                                        'type' => 'toggle',
                                        'default' => false,
                                        'display' => __('hazelchat::settings.consent_display'),
                                        'instructions' => __('hazelchat::settings.consent_instructions'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
