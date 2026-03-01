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
class CreateNotification extends ConsoleCommand
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
                $this->setName('rebelnotifications:create');
                $this->setDescription('CreateNotification');
                $this->addNoValueOption(
                    'enabled',
                    null,
                    'Set to enabled',
                    null
                );
                $this->addNoValueOption(
                    'raw',
                    null,
                    'Allow raw (HTML) input in message',
                    null
                );
                $this->addOptionalValueOption(
                    'type',
                    null,
                    'Type',
                    null
                );
                $this->addOptionalValueOption(
                    'title',
                    null,
                    'Title',
                    null
                );
                $this->addOptionalValueOption(
                    'message',
                    null,
                    'Message',
                    null
                );
                $this->addOptionalValueOption(
                    'context',
                    null,
                    'Context',
                    null
                );
                $this->addOptionalValueOption(
                    'priority',
                    null,
                    'Priority',
                    null
                );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        if (!$input->hasOption('enabled') || $input->getOption('enabled') === null) {
            $enabled = 0;
        } else {
            $enabled = 1;
        }
        if (!$input->hasOption('raw') || $input->getOption('raw') === null) {
            $raw = 0;
        } else {
            $raw = 1;
        }
        if (!$input->hasOption('type') || $input->getOption('type') === null) {
            throw new \InvalidArgumentException("The 'type' option is required.");
        }
        $type = $input->getOption('type');
        if (!$input->hasOption('title') || $input->getOption('title') === null) {
            throw new \InvalidArgumentException("The 'title' option is required.");
        }
        $title = $input->getOption('title');
        if (!$input->hasOption('message') || $input->getOption('message') === null) {
            throw new \InvalidArgumentException("The 'message' option is required.");
        }
        $message = $input->getOption('message');
        if (!$input->hasOption('context') || $input->getOption('context') === null) {
            throw new \InvalidArgumentException("The 'context' option is required.");
        }
        $context = $input->getOption('context');
        if (!$input->hasOption('priority') || $input->getOption('priority') === null) {
            throw new \InvalidArgumentException("The 'priority' option is required.");
        }
        $priority = $input->getOption('priority');
        $api = new API();
        $addNotification = $api->insertNotification($enabled, $title, $message, $context, $priority, $type, $raw);
        $message = sprintf('<info>Created notification: %s</info>', $title);

        $output->writeln($message);

        return self::SUCCESS;
    }
}
