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

namespace Piwik\Plugins\RebelNotifications;

/**
 * The HTML a notification may hold.
 */
class Message
{
    public const TAGS = ['a', 'b', 'strong', 'i', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * Keeps the allowed tags without their attributes, and the address of a link when it is a
     * web or mail address. Everything else becomes text.
     */
    public static function clean(string $html): string
    {
        $parts = preg_split('/(<\/?[a-zA-Z][^<>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return self::toText($html);
        }

        $clean = '';
        foreach ($parts as $index => $part) {
            // Every second part is a tag.
            $clean .= $index % 2 === 1 ? self::cleanTag($part) : self::toText($part);
        }

        return $clean;
    }

    private static function cleanTag(string $tag): string
    {
        if (!preg_match('/^<(\/?)([a-zA-Z][a-zA-Z0-9]*)(\s[^<>]*)?\/?>$/', $tag, $found)) {
            return self::toText($tag);
        }

        $name = strtolower($found[2]);
        if (!in_array($name, self::TAGS, true)) {
            return '';
        }
        if ($found[1] === '/' || $name !== 'a') {
            return '<' . $found[1] . $name . '>';
        }

        $href = '';
        if (preg_match('/\shref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $found[3] ?? '', $address)) {
            $href = trim(html_entity_decode($address[1] . ($address[2] ?? '') . ($address[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if (!preg_match('/^(https?:\/\/|mailto:)[^\s]+$/i', $href)) {
            return '<a>';
        }

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noreferrer noopener">';
    }

    private static function toText(string $text): string
    {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $text);
    }
}
