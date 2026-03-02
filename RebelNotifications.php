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

use Piwik\Db;
use Exception;
use Piwik\Common;
use Piwik\Notification;
use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\RebelNotifications\API;

class RebelNotifications extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'Login.authenticate.successful' => 'getNotifications',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        ];
    }

    public function getNotifications()
    {
        $this->logger()->info("Get notifications");
        $api = API::getInstance();
        $enabledNotifications = $this->getEnabledNotifications();
        foreach ($enabledNotifications as $notificationData) {
            $notification = new Notification($notificationData['message']);
            $notification->title = $notificationData['title'];
            $notification->context = $notificationData['context'];
            $notification->priority = $notificationData['priority'];
            $notification->raw = $notificationData['raw'];
            $notification->type = $notificationData['type'];

            Notification\Manager::notify('RebelNotifications_' . $notificationData['id'], $notification);
        }
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/RebelNotifications/stylesheets/rebel.less";
    }

    public function install()
    {
        $db = $this->getDb();
        $query = "CREATE TABLE " . Common::prefixTable('rebel_notifications') . " (
            `id` int(24) NOT NULL AUTO_INCREMENT,
            `enabled` int NOT NULL,
            `title` varchar(255) NOT NULL,
            `message` text,
            `context` varchar(128) NOT NULL,
            `priority` varchar(128) NOT NULL,
            `type` varchar(128) NOT NULL,
            `raw` int NOT NULL,
            `flags` varchar(255),
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
            )
            ";
        try {
            $db->exec($query);
        } catch (Exception $e) {
            if (!$db->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }

    private function getEnabledNotifications(): array
    {
        $db = Db::get();
        $query = "SELECT * FROM `" . Common::prefixTable('rebel_notifications') . "` WHERE `enabled` = ?";
        $params = [1];

        try {
            $notifications = $db->fetchAll($query, $params);
            return $notifications;
        } catch (\Exception $e) {
            throw new Exception("Error fetching enabled notifications: " . $e->getMessage());
        }
    }

    private function getDb()
    {
        return Db::get();
    }

    public function logger()
    {
        return StaticContainer::get(LoggerInterface::class);
    }
}
