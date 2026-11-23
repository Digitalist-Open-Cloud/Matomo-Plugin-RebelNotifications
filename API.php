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

use Exception;
use Piwik\Common;
use Piwik\Db;
use Piwik\Notification;
use Piwik\Piwik;

/**
 * API for plugin RebelNotifications. With this you can handle notifications to
 * your users of Matomo through the API. Delete, Update, Edit etc.
 *
 * @method static \Piwik\Plugins\RebelNotifications\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    public const CONTEXTS = [
        Notification::CONTEXT_INFO,
        Notification::CONTEXT_ERROR,
        Notification::CONTEXT_SUCCESS,
        Notification::CONTEXT_WARNING,
    ];

    public const PRIORITIES = [
        Notification::PRIORITY_MIN,
        Notification::PRIORITY_LOW,
        Notification::PRIORITY_HIGH,
        Notification::PRIORITY_MAX,
    ];

    public const TYPES = [
        Notification::TYPE_PERSISTENT,
        Notification::TYPE_TRANSIENT,
        Notification::TYPE_TOAST,
    ];

    private const MAX_TITLE_LENGTH = 255;
    private const MAX_MESSAGE_BYTES = 60000;

    /**
     * @unsanitized
     */
    public function insertNotification(
        int $enabled,
        string $title,
        string $message,
        string $context,
        string $priority,
        string $type,
        int $raw
    ): void {
        Piwik::checkUserHasSuperUserAccess();
        $values = $this->check($enabled, $title, $message, $context, $priority, $type, $raw);

        Db::query(
            'INSERT INTO `' . Common::prefixTable('rebel_notifications') . '`
            (enabled, title, message, context, priority, type, raw) VALUES (?,?,?,?,?,?,?)',
            $values
        );

        /**
         * Posted when a notification is added.
         *
         * @param int $id
         * @param string $title
         */
        Piwik::postEvent('RebelNotifications.notification.added', [(int) Db::get()->lastInsertId(), $values[1]]);
    }

    public function deleteNotification(int $id)
    {
        Piwik::checkUserHasSuperUserAccess();

        Db::query('DELETE FROM `' . Common::prefixTable('rebel_notifications') . '` WHERE `id` = ?', [$id]);

        /**
         * Posted when a notification is deleted.
         *
         * @param int $id
         */
        Piwik::postEvent('RebelNotifications.notification.deleted', [$id]);

        return true;
    }

    /**
     * @unsanitized
     */
    public function updateNotification(
        int $id,
        int $enabled,
        string $title,
        string $message,
        string $context,
        string $priority,
        string $type,
        int $raw
    ): void {
        Piwik::checkUserHasSuperUserAccess();
        $values = $this->check($enabled, $title, $message, $context, $priority, $type, $raw);

        Db::query(
            'UPDATE `' . Common::prefixTable('rebel_notifications') . '`
                SET enabled = ?, title = ?, message = ?, context = ?, priority = ?, type = ?, raw = ?
              WHERE id = ?',
            array_merge($values, [$id])
        );

        /**
         * Posted when a notification is changed.
         *
         * @param int $id
         * @param string $title
         */
        Piwik::postEvent('RebelNotifications.notification.updated', [$id, $values[1]]);
    }

    public function getEnabledNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        return Db::fetchAll('SELECT * FROM `' . Common::prefixTable('rebel_notifications') . '` WHERE `enabled` = 1');
    }

    public function getDisabledNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        return Db::fetchAll('SELECT * FROM `' . Common::prefixTable('rebel_notifications') . '` WHERE `enabled` = 0');
    }

    public function getAllNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        return Db::fetchAll('SELECT * FROM `' . Common::prefixTable('rebel_notifications') . '`');
    }

    /**
     * @return array<int, int|string> the values to store, in the order of the parameters
     */
    private function check(int $enabled, string $title, string $message, string $context, string $priority, string $type, int $raw): array
    {
        $title = trim($title);
        $message = trim($message);
        if ($title === '' || mb_strlen($title, 'UTF-8') > self::MAX_TITLE_LENGTH) {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorTitle', [self::MAX_TITLE_LENGTH]));
        }
        if ($message === '' || strlen($message) > self::MAX_MESSAGE_BYTES) {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorMessage'));
        }
        if (!in_array($context, self::CONTEXTS, true)) {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorOneOf', ['context', implode(', ', self::CONTEXTS)]));
        }
        if (!ctype_digit($priority) || (int) $priority < Notification::PRIORITY_MIN || (int) $priority > Notification::PRIORITY_MAX) {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorOneOf', ['priority', Notification::PRIORITY_MIN . ' - ' . Notification::PRIORITY_MAX]));
        }
        if (!in_array($type, self::TYPES, true)) {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorOneOf', ['type', implode(', ', self::TYPES)]));
        }
        $raw = $raw ? 1 : 0;

        return [$enabled ? 1 : 0, $title, $raw ? Message::clean($message) : $message, $context, $priority, $type, $raw];
    }
}
