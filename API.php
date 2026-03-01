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

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Db;
use Exception;

/**
 * API for plugin RebelNotifications. With this you can handle notifications to
 * your users of Matomo through the API. Delete, Update, Edit etc.
 *
 * @method static \Piwik\Plugins\RebelNotifications\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
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
        $query = "INSERT INTO `" . Common::prefixTable('rebel_notifications') . "`
            (enabled, title, message, context, priority, type, raw) VALUES (?,?,?,?,?,?,?)";
        $params = [$enabled, $title, $message, $context, $priority, $type, $raw];

        $db = $this->getDb();

        try {
            $db->query($query, $params);
        } catch (Exception $e) {
            if (!$db->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }

    public function deleteNotification(int $id)
    {
        Piwik::checkUserHasSuperUserAccess();
        try {
            $db = $this->getDb();
            $query = $db->query(
                "DELETE FROM `" .
                Common::prefixTable('rebel_notifications') .
                "` WHERE `id` = ?",
                [$id]
            );
            return true;
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

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

        $query = "UPDATE `" . Common::prefixTable('rebel_notifications') . "`
                  SET enabled = ?, title = ?, message = ?, context = ?, priority = ?, type = ?, raw = ?
                  WHERE id = ?";
        $params = [$enabled, $title, $message, $context, $priority, $type, $raw, $id];

        $db = $this->getDb();

        try {
            $db->query($query, $params);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getEnabledNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess(); // Ensure only authorized users can access this method

        $db = Db::get();
        $query = "SELECT * FROM `" . Common::prefixTable('rebel_notifications') . "` WHERE `enabled` = ?";
        $params = [1];  // Only get notifications where `enabled` is set to 1

        try {
            $notifications = $db->fetchAll($query, $params);
            return $notifications;
        } catch (\Exception $e) {
            throw new Exception("Error fetching enabled notifications: " . $e->getMessage());
        }
    }

    public function getDisabledNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess(); // Ensure only authorized users can access this method

        $db = Db::get();
        $query = "SELECT * FROM `" . Common::prefixTable('rebel_notifications') . "` WHERE `enabled` = ?";
        $params = [0];  // Only get notifications where `enabled` is set to 1

        try {
            $notifications = $db->fetchAll($query, $params);
            return $notifications;
        } catch (\Exception $e) {
            throw new Exception("Error fetching disabled notifications: " . $e->getMessage());
        }
    }

    public function getAllNotifications(): array
    {
        Piwik::checkUserHasSuperUserAccess(); // Ensure only authorized users can access this method

        $db = Db::get();
        $query = "SELECT * FROM `" . Common::prefixTable('rebel_notifications') . "`";

        try {
            $notifications = $db->fetchAll($query);
            return $notifications;
        } catch (\Exception $e) {
            throw new Exception("Error fetching notifications: " . $e->getMessage());
        }
    }

    private function getDb()
    {
        return Db::get();
    }
}
