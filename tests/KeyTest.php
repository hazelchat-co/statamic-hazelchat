<?php

namespace Hazelchat\Statamic\Tests;

use Hazelchat\Statamic\Key;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * The WordPress, Joomla and TYPO3 vectors: only a valid key ever reaches a
 * page, and anything doubtful prints nothing.
 */
class KeyTest extends BaseTestCase
{
    private const KEY = 'a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1';

    private const OTHER = 'b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2';

    private static function code(): string
    {
        return '<script src="https://app.hazelchat.co/widget.js" data-agent="'.self::KEY.'" defer></script>';
    }

    public function test_a_bare_key_or_a_pasted_code_gives_the_key(): void
    {
        $this->assertSame(self::KEY, Key::fromInput(self::KEY));
        $this->assertSame(self::KEY, Key::fromInput('  '.self::KEY."\n"));
        $this->assertSame(self::KEY, Key::fromInput(self::code()));
        $this->assertSame(self::KEY, Key::fromInput("<script data-agent='".self::KEY."'></script>"));
        $this->assertSame(self::KEY, Key::fromInput('<script data-agent='.self::KEY.' defer>'));
        $this->assertSame(self::KEY, Key::fromInput('<!-- Hazelchat -->'.self::code()));
    }

    public function test_anything_doubtful_gives_nothing(): void
    {
        $refused = [
            'more hex after the key' => '<script data-agent="'.self::KEY.'ff">',
            'upper case' => strtoupper(self::KEY),
            'upper case in a code' => '<script data-agent="'.strtoupper(self::KEY).'">',
            'an API key' => 'sk-'.self::KEY,
            'an API key in a code' => '<script data-agent="sk-'.self::KEY.'">',
            '63 characters' => substr(self::KEY, 1),
            '65 characters' => self::KEY.'a',
            'over 1,000 characters' => str_repeat('x', 1000).self::code(),
            'two keys' => 'data-agent="'.self::KEY.'" data-agent="'.self::OTHER.'"',
            'the same code twice' => self::code().self::code(),
            'a second data-agent in upper case' => '<script DATA-AGENT="'.self::OTHER.'" data-agent="'.self::KEY.'">',
            'xdata-agent' => '<script xdata-agent="'.self::KEY.'">',
            'data-agent-x' => '<script data-agent-x="'.self::KEY.'">',
            'x-data-agent' => '<script x-data-agent="'.self::KEY.'">',
            'a commented-out code' => '<!-- '.self::code().' -->',
            'an unclosed comment' => '<!-- '.self::code(),
            'a newline inside the key' => substr(self::KEY, 0, 32)."\n".substr(self::KEY, 32),
            'Antlers' => '{{ config:app.key }}',
            'empty' => '',
        ];
        foreach ($refused as $name => $input) {
            $this->assertNull(Key::fromInput($input), $name);
        }
        $this->assertNull(Key::fromInput(['data-agent' => self::KEY]));
        $this->assertNull(Key::fromInput(null));
        $this->assertNull(Key::fromInput(123));
    }

    public function test_a_hostile_src_changes_nothing(): void
    {
        $this->assertSame(self::KEY, Key::fromInput('<script src="https://evil.example/x.js" data-agent="'.self::KEY.'"></script>'));
        $this->assertStringContainsString('src="https://app.hazelchat.co/widget.js"', Key::tag(self::KEY, false));
        $this->assertStringNotContainsString('evil', Key::tag(Key::fromInput('<script src="https://evil.example/x.js" data-agent="'.self::KEY.'"></script>'), false));
    }

    public function test_the_tag(): void
    {
        $this->assertSame('', Key::tag(null, true));
        $this->assertSame('', Key::tag('not-a-key', true));
        $this->assertSame(
            '<script id="hazelchat-statamic" data-agent="'.self::KEY.'" src="https://app.hazelchat.co/widget.js" defer></script>',
            Key::tag(self::KEY, false)
        );
        $this->assertStringContainsString(' data-require-consent="true" ', Key::tag(self::KEY, true));
        $this->assertStringContainsString(' nonce="a&quot;&gt;&lt;x" ', Key::tag(self::KEY, false, 'a"><x'));
        $this->assertSame('https://app.hazelchat.co/widget.js', Key::SCRIPT_URL);
    }
}
