<?php

namespace Hazelchat\Statamic\Tests;

use Hazelchat\Statamic\ServiceProvider;
use Illuminate\Support\Facades\File;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();
        File::delete(resource_path('addons/hazelchat.yaml'));
    }

    protected function tearDown(): void
    {
        File::delete(resource_path('addons/hazelchat.yaml'));
        parent::tearDown();
    }

    protected const KEY = 'a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1';

    protected const OTHER = 'b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2';

    protected static function code(string $key = self::KEY): string
    {
        return '<script src="https://app.hazelchat.co/widget.js" data-agent="'.$key.'" defer></script>';
    }

    protected static function tag(string $key = self::KEY, string $extra = ''): string
    {
        return '<script id="hazelchat-statamic" data-agent="'.$key.'"'.$extra.' src="https://app.hazelchat.co/widget.js" defer></script>';
    }
}
