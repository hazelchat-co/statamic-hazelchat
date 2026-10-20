<?php

declare(strict_types=1);

/*
 * This file is part of the Hazelchat addon for Statamic sites.
 *
 * (C) 2026 Hazelchat. License: MIT; see LICENSE.md.
 */

namespace Hazelchat\Statamic\Http\Middleware;

use Closure;
use Hazelchat\Statamic\Key;
use Hazelchat\Statamic\Widget;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Puts the chat before </body> of the site's HTML pages, so no template
 * needs editing. It runs in Statamic's front-end middleware group, inside
 * static caching, so cached pages carry the tag too. The Control Panel,
 * redirects, downloads, JSON, live preview and pages that already use the
 * {{ hazelchat }} tag are left alone.
 */
final class InjectWidget
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! config('hazelchat.auto_inject', true)
            || ! $request->isMethod('GET')
            || ! $response instanceof Response
            || ! ($response->isSuccessful() || $response->isNotFound())
            || ! str_contains(strtolower((string) $response->headers->get('Content-Type', 'text/html')), 'text/html')
            || (Request::hasMacro('isLivePreview') && $request->isLivePreview())) {
            return $response;
        }

        $content = $response->getContent();
        if (! \is_string($content) || str_contains($content, 'id="'.Key::SCRIPT_ID.'"')) {
            return $response;
        }
        $at = strripos($content, '</body>');
        if ($at === false) {
            return $response;
        }
        $tag = Widget::html();
        if ($tag === '') {
            return $response;
        }

        $response->setContent(substr($content, 0, $at).$tag."\n".substr($content, $at));
        if ($response->headers->has('Content-Length')) {
            $response->headers->set('Content-Length', (string) \strlen((string) $response->getContent()));
        }

        return $response;
    }
}
