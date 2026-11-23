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
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;
use Piwik\Request;
use Piwik\View;

class Controller extends ControllerAdmin
{
    public const NONCE = 'RebelNotifications.manage';

    /**
     * The notifications, and a form to create one.
     *
     * @param string[] $notificationList what to tell the user
     */
    public function index(array $notificationList = [])
    {
        Piwik::checkUserHasSuperUserAccess();

        $notifications = API::getInstance()->getAllNotifications();
        foreach ($notifications as &$notification) {
            // What was stored before the message was cleaned on saving.
            $notification['message'] = $notification['raw'] ? Message::clean((string) $notification['message']) : $notification['message'];
        }

        return $this->render('manageNotifications', [
            'messages' => $notifications,
            'notificationList' => $notificationList,
        ]);
    }

    public function createNotification()
    {
        $this->checkPost();
        $values = $this->getPostedValues();

        try {
            API::getInstance()->insertNotification(...array_values($values));
        } catch (Exception $e) {
            return $this->index([$e->getMessage()]);
        }

        return $this->index([Piwik::translate('RebelNotifications_Created', [$values['title']])]);
    }

    public function deleteNotification()
    {
        $this->checkPost();
        $id = Request::fromPost()->getIntegerParameter('id', 0);

        API::getInstance()->deleteNotification($id);

        return $this->index([Piwik::translate('RebelNotifications_Deleted', [$id])]);
    }

    public function editNotification()
    {
        Piwik::checkUserHasSuperUserAccess();
        $id = Request::fromRequest()->getIntegerParameter('id', 0);

        foreach (API::getInstance()->getAllNotifications() as $notification) {
            if ((int) $notification['id'] === $id) {
                return $this->render('editNotification', ['notification' => $notification]);
            }
        }

        throw new Exception(Piwik::translate('RebelNotifications_ErrorNotFound'));
    }

    public function updateNotification()
    {
        $this->checkPost();
        $id = Request::fromPost()->getIntegerParameter('id', 0);

        try {
            API::getInstance()->updateNotification($id, ...array_values($this->getPostedValues()));
        } catch (Exception $e) {
            return $this->index([$e->getMessage()]);
        }

        return $this->index([Piwik::translate('RebelNotifications_Updated', [$id])]);
    }

    /**
     * A change is posted by a super user with the nonce of the page it comes from.
     */
    private function checkPost(): void
    {
        Piwik::checkUserHasSuperUserAccess();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new Exception(Piwik::translate('RebelNotifications_ErrorNotPosted'));
        }
        Nonce::checkNonce(self::NONCE, Request::fromPost()->getStringParameter('nonce', ''));
    }

    /**
     * @return array{enabled: int, title: string, message: string, context: string, priority: string, type: string, raw: int}
     */
    private function getPostedValues(): array
    {
        $post = Request::fromPost();

        return [
            'enabled' => $post->getIntegerParameter('enabled', 0),
            'title' => $post->getStringParameter('title', ''),
            'message' => $post->getStringParameter('message', ''),
            'context' => $post->getStringParameter('context', ''),
            'priority' => $post->getStringParameter('priority', ''),
            'type' => $post->getStringParameter('type', ''),
            'raw' => $post->getIntegerParameter('raw', 0),
        ];
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(string $template, array $variables): string
    {
        $view = new View('@RebelNotifications/' . $template);
        $this->setBasicVariablesView($view);
        foreach ($variables as $name => $value) {
            $view->assign($name, $value);
        }
        $view->assign('contexts', API::CONTEXTS);
        $view->assign('types', API::TYPES);
        $view->assign('priorities', API::PRIORITIES);
        $view->assign('nonce', Nonce::getNonce(self::NONCE));

        return $view->render();
    }
}
