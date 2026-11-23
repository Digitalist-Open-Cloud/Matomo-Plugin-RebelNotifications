<?php

/**
 * The Rebel Notification plugin for Matomo.
 *
 * Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Piwik\Plugins\RebelNotifications\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\RebelNotifications\Message;

/**
 * @group RebelNotifications
 * @group Plugins
 */
class MessageTest extends TestCase
{
    /**
     * @dataProvider getMessages
     */
    public function testAMessageKeepsOnlyWhatIsAllowed(string $html, string $expected)
    {
        $this->assertSame($expected, Message::clean($html));
        $this->assertSame($expected, Message::clean($expected), 'Cleaning twice changes nothing.');
    }

    public function getMessages(): array
    {
        return [
            'text' => ['Service window at 22:00 & onwards', 'Service window at 22:00 & onwards'],
            'allowed tags' => ['<h2>Hi</h2> <b>a</b> <strong>b</strong> <i>c</i> <em>d</em>', '<h2>Hi</h2> <b>a</b> <strong>b</strong> <i>c</i> <em>d</em>'],
            'a link' => ['<a href="https://example.org/a?b=1&c=2">read</a>', '<a href="https://example.org/a?b=1&amp;c=2" rel="noreferrer noopener">read</a>'],
            'a mail link' => ["<a href='mailto:help@example.org'>mail</a>", '<a href="mailto:help@example.org" rel="noreferrer noopener">mail</a>'],
            'attributes are dropped' => ['<b onclick="alert(1)" style="x">a</b>', '<b>a</b>'],
            'a handler on a link' => ['<a href="https://example.org" onmouseover="alert(1)">x</a>', '<a href="https://example.org" rel="noreferrer noopener">x</a>'],
            'a script address' => ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'],
            'an encoded script address' => ['<a href="java&#115;cript:alert(1)">x</a>', '<a>x</a>'],
            'a data address' => ['<a href=data:text/html,x>x</a>', '<a>x</a>'],
            'other tags are removed' => ['<script>alert(1)</script><img src=x onerror=alert(1)><iframe src="//x"></iframe>ok', 'alert(1)ok'],
            'upper case' => ['<SCRIPT>x</SCRIPT><B>y</B>', 'x<b>y</b>'],
            'a tag that is not closed' => ['<b onclick=alert(1) ', '&lt;b onclick=alert(1) '],
            'a tag inside a tag' => ['<<b>script>alert(1)</script>', '&lt;<b>script&gt;alert(1)'],
            'a quote that ends early' => ['<a href="https://example.org/"><b>" onmouseover="alert(1)">x</a>', '<a href="https://example.org/" rel="noreferrer noopener"><b>" onmouseover="alert(1)"&gt;x</a>'],
        ];
    }
}
