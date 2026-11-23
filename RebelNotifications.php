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
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Notification;

class RebelNotifications extends \Piwik\Plugin
{
    private const TABLE = 'rebel_notifications';

    public function registerEvents()
    {
        return [
            'Login.authenticate.successful' => 'getNotifications',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Db.getTablesInstalled' => 'getTablesInstalled',
        ];
    }

    /**
     * Shows the enabled notifications to the user who logs in.
     */
    public function getNotifications()
    {
        $rows = Db::fetchAll('SELECT * FROM `' . Common::prefixTable(self::TABLE) . '` WHERE `enabled` = 1');
        foreach ($rows as $row) {
            $raw = (bool) $row['raw'];
            // Cleaned here as well: a message stored before it was cleaned on saving is shown as HTML.
            $notification = new Notification($raw ? Message::clean((string) $row['message']) : (string) $row['message']);
            $notification->title = $row['title'];
            $notification->context = $row['context'];
            $notification->priority = (int) $row['priority'];
            $notification->raw = $raw;
            $notification->type = $row['type'];

            Notification\Manager::notify('RebelNotifications_' . $row['id'], $notification);
        }
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/RebelNotifications/stylesheets/rebel.less";
        $stylesheets[] = "plugins/RebelNotifications/stylesheets/menuIcon.less";
    }

    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled[] = Common::prefixTable(self::TABLE);
    }

    public function install()
    {
        // DbHelper creates the table with the character set and engine Matomo is configured for.
        DbHelper::createTable(self::TABLE, "
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
            PRIMARY KEY (`id`)");
    }

    public function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::TABLE));
    }
}
