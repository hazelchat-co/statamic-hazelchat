<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic\Tags;

use Hazelchat\Statamic\Widget;
use Statamic\Tags\Tags;

/**
 * {{ hazelchat }} in Antlers, <s:hazelchat /> or Statamic::tag('hazelchat')
 * in Blade: the chat's script tag for the current site, or nothing. For
 * templates that place the tag themselves; the automatic insertion then
 * skips the page.
 */
class Hazelchat extends Tags
{
    /** @var string */
    protected static $handle = 'hazelchat';

    public function index(): string
    {
        return Widget::html();
    }
}
