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

namespace Piwik\Plugins\RebelNotifications\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\RebelNotifications\API;

/**
 * This class lets you define a new command. To read more about commands have a look at our Matomo Console guide on
 * https://developer.matomo.org/guides/piwik-on-the-command-line
 *
 * As Matomo Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class ListNotifications extends ConsoleCommand
{
    /**
     * This method allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will create a notification.
        <comment>Samples:</comment>
        To run:
        <info>%command.name%</info>';
                $this->setHelp($HelpText);
                $this->setName('rebelnotifications:list');
                $this->setDescription('List notifications');
                $this->addNoValueOption(
                    'enabled',
                    null,
                    'Set to enabled',
                    null
                );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $enabled = $input->getOption('enabled') ? true : false;

        $api = API::getInstance();
        if ($enabled === false) {
            $listNotifications = $api->getAllNotifications();
        } else {
            $listNotifications = $api->getEnabledNotifications();
        }

        foreach ($listNotifications as $notification) {
            $enabled = 'no';
            $raw = 'no';
            if ($notification['enabled'] == 1) {
                $enabled = 'yes';
            }
            if ($notification['raw'] == 1) {
                $raw = 'yes';
            }

            $out = "ID: <comment>{$notification['id']}</comment>\n";
            $out .= "Enabled: <comment>{$enabled}</comment>\n";
            $out .= "Title: <comment>{$notification['title']}</comment>\n";
            $out .= "Message: <comment>{$notification['message']}</comment>\n";
            $out .= "Context: <comment>{$notification['context']}</comment>\n";
            $out .= "Priority: <comment>{$notification['priority']}</comment>\n";
            $out .= "Type: <comment>{$notification['type']}</comment>\n";
            $out .= "Raw (HTML allowed): <comment>{$raw}</comment>\n";
            $out .= "-----------------------------------";
            $output->writeln("<info>$out</info>");
        }


        return self::SUCCESS;
    }
}
