<?php

namespace Hazelchat\Statamic\Tests;

use Hazelchat\Statamic\Http\Middleware\InjectWidget;
use Hazelchat\Statamic\ServiceProvider;
use Hazelchat\Statamic\Widget;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Statamic;
use Statamic\StaticCaching\Middleware\Cache;

class WidgetTest extends TestCase
{
    private const PAGE = '<!doctype html><html><head><title>t</title></head><body><p>Hello</p></body></html>';

    protected function setUp(): void
    {
        parent::setUp();

        // Ahead of Statamic's catch-all front-end route.
        $router = $this->app['router'];
        $statamic = $router->getRoutes();
        $router->setRoutes(new RouteCollection);
        Route::middleware(['web', 'statamic.web'])->group(function () {
            Route::get('/page', fn () => response(self::PAGE));
            Route::post('/page', fn () => response(self::PAGE));
            Route::get('/json', fn () => response()->json(['body' => '</body>']));
            Route::get('/text', fn () => response('</body>', 200, ['Content-Type' => 'text/plain']));
            Route::get('/away', fn () => redirect('/page'));
            Route::get('/missing', fn () => response(self::PAGE, 404));
            Route::get('/error', fn () => response(self::PAGE, 500));
            Route::get('/tagged', fn () => response('<html><body><p>x</p>'.Widget::html().'</body></html>'));
        });
        foreach ($statamic->getRoutes() as $route) {
            $router->getRoutes()->add($route);
        }
    }

    private function page(string $uri = '/page'): string
    {
        return (string) $this->get($uri)->getContent();
    }

    public function test_the_chat_goes_in_inside_static_caching(): void
    {
        $group = $this->app['router']->getMiddlewareGroups()['statamic.web'];

        $this->assertSame(InjectWidget::class, end($group));
        $this->assertSame(1, \count(array_keys($group, InjectWidget::class, true)));
        $this->assertContains(Cache::class, $group);
    }

    public function test_nothing_without_a_code(): void
    {
        $this->assertSame(self::PAGE, $this->page());
    }

    public function test_the_env_code_puts_one_tag_before_the_body_end(): void
    {
        config(['hazelchat.installation_code' => self::code()]);

        $this->assertSame(
            str_replace('</body>', self::tag()."\n</body>", self::PAGE),
            $this->page()
        );
        $this->assertStringContainsString(self::tag(), $this->page('/missing'));
    }

    public function test_an_invalid_code_prints_nothing(): void
    {
        foreach (['sk-'.self::KEY, strtoupper(self::KEY), self::code().self::code(), '{{ config:app.key }}'] as $code) {
            config(['hazelchat.installation_code' => $code]);
            $this->assertSame(self::PAGE, $this->page(), $code);
        }
    }

    public function test_consent_adds_the_attribute(): void
    {
        config(['hazelchat.installation_code' => self::KEY, 'hazelchat.wait_for_consent' => 'true']);

        $this->assertStringContainsString(self::tag(self::KEY, ' data-require-consent="true"'), $this->page());
    }

    public function test_only_html_pages_that_are_read(): void
    {
        config(['hazelchat.installation_code' => self::KEY]);

        $this->assertStringNotContainsString('hazelchat', (string) $this->post('/page')->getContent());
        $this->assertStringNotContainsString('hazelchat', (string) $this->get('/json')->getContent());
        $this->assertStringNotContainsString('hazelchat', (string) $this->get('/text')->getContent());
        $this->assertStringNotContainsString('hazelchat', (string) $this->get('/error')->getContent());
        $this->get('/away')->assertRedirect('/page');
    }

    public function test_a_template_tag_is_not_doubled(): void
    {
        config(['hazelchat.installation_code' => self::KEY]);

        $this->assertSame(1, substr_count($this->page('/tagged'), 'hazelchat-statamic'));
    }

    public function test_auto_inject_can_be_turned_off_and_the_tag_still_works(): void
    {
        config(['hazelchat.installation_code' => self::KEY, 'hazelchat.auto_inject' => false]);

        $this->assertSame(self::PAGE, $this->page());
        $this->assertSame(self::tag(), (string) Statamic::tag('hazelchat')->fetch());
    }

    public function test_each_site_can_have_its_own_code_or_none(): void
    {
        config(['hazelchat.installation_code' => self::KEY]);
        Site::setSites([
            'en' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en_US'],
            'de' => ['name' => 'Deutsch', 'url' => 'http://localhost/de/', 'locale' => 'de_DE', 'attributes' => ['hazelchat' => self::OTHER]],
            'fr' => ['name' => 'Français', 'url' => 'http://localhost/fr/', 'locale' => 'fr_FR', 'attributes' => ['hazelchat' => '']],
        ]);

        Site::setCurrent('en');
        $this->assertSame(self::tag(), Widget::html());
        Site::setCurrent('de');
        $this->assertSame(self::tag(self::OTHER), Widget::html());
        Site::setCurrent('fr');
        $this->assertSame('', Widget::html());
    }

    public function test_the_control_panel_never_gets_the_chat(): void
    {
        config(['hazelchat.installation_code' => self::KEY]);

        $this->assertStringNotContainsString('hazelchat-statamic', (string) $this->get('/cp/auth/login')->getContent());
    }

    public function test_settings_page_keeps_only_the_key_and_wins_over_env(): void
    {
        $addon = Addon::get(Widget::PACKAGE);
        if (! method_exists($addon, 'settings')) {
            $this->markTestSkipped('Statamic 5 has no addon settings page.');
        }

        $this->assertInstanceOf(Blueprint::class, $addon->settingsBlueprint());
        $this->assertSame(['installation_code', 'wait_for_consent'], $addon->settingsBlueprint()->fields()->all()->keys()->all());

        config(['hazelchat.installation_code' => self::OTHER]);
        $addon->settings()->set(['installation_code' => self::code(), 'wait_for_consent' => true])->save();

        $this->assertSame(self::KEY, $addon->settings()->raw()['installation_code']);
        $this->assertStringContainsString(self::tag(self::KEY, ' data-require-consent="true"'), $this->page());
    }

    public function test_the_settings_field_refuses_what_it_cannot_use(): void
    {
        $rules = ServiceProvider::settingsBlueprint()['tabs']['main']['sections'][0]['fields'][0]['field']['validate'];
        $rules = array_map(fn ($rule) => str_starts_with($rule, 'new ') ? new (substr($rule, 4)) : $rule, $rules);

        $this->assertTrue(Validator::make(['c' => self::code()], ['c' => $rules])->passes());
        $this->assertTrue(Validator::make(['c' => null], ['c' => $rules])->passes());
        $this->assertTrue(Validator::make(['c' => 'sk-'.self::KEY], ['c' => $rules])->fails());
        $this->assertTrue(Validator::make(['c' => str_repeat('x', 1001)], ['c' => $rules])->fails());
    }

    public function test_labels_exist_in_english_and_german(): void
    {
        foreach (['en', 'de'] as $locale) {
            app()->setLocale($locale);
            foreach (['section', 'section_instructions', 'code_display', 'code_instructions', 'code_invalid', 'consent_display', 'consent_instructions'] as $key) {
                $this->assertNotSame("hazelchat::settings.$key", __("hazelchat::settings.$key"), "$locale $key");
            }
        }
        $this->assertSame('Installationscode', __('hazelchat::settings.code_display'));
    }
}
