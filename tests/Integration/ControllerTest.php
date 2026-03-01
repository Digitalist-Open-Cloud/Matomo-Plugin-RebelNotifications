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

namespace Piwik\Plugins\RebelNotifications\tests\Integration;

use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugins\RebelNotifications\Controller;
use Piwik\Plugins\RebelNotifications\API;
use Piwik\Tests\Framework\Fixture;
use Piwik\Nonce;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group RebelNotifications
 * @group ControllerTest
 * @group Plugins
 */
class ControllerTest extends IntegrationTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();
        Fixture::createWebsite('2025-01-01 00:00:00');
        FakeAccess::$superUser = true;
        $this->controller = new Controller();
        $this->api = API::getInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testIndex()
    {
        $result = $this->controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('</html>', $result);
        $this->assertStringContainsString('<select name="priority"', $result);
        $this->assertStringContainsString('<option value="toast">toast</option>', $result);
    }

    public function testIndexGeneratesCreateNonce()
    {
        $result = $this->controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('name="nonce" value="', $result);
    }

    public function testIndexGeneratesDeleteNonce()
    {
        $this->api->insertNotification('1', 'Test notification', 'bar', 'warning', '25', 'persistent', '0');
        $result = $this->controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('action="{{ linkTo({\'module\':\'RebelNotifications\',\'action\':\'deleteNotification\'}) }}"', $result);
    }

    public function testEditNotificationGeneratesUpdateNonce()
    {
        $this->api->insertNotification('1', 'Title to edit', 'bar', 'warning', '25', 'persistent', '0');
        $result = $this->controller->editNotification('1');
        $this->assertIsString($result);
        $this->assertStringContainsString('name="nonce" value="', $result);
    }

    public function testCreateNotificationWithValidNonce()
    {
        $nonce = Nonce::getNonce('RebelNotifications.create');
        $_POST = [
            'nonce' => $nonce,
            'enabled' => '1',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'context' => 'warning',
            'priority' => '25',
            'type' => 'persistent',
            'raw' => '0',
        ];
        $_REQUEST = $_POST;

        $result = $this->controller->createNotification();
        $this->assertIsString($result);

        $notifications = $this->api->getEnabledNotifications();
        $found = false;
        foreach ($notifications as $notification) {
            if ($notification['title'] === 'Test Notification') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Notification should be created with valid nonce');

        unset($_POST, $_REQUEST);
    }

    public function testCreateNotificationWithoutNonceFails()
    {
        $_POST = [
            'enabled' => '1',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'context' => 'warning',
            'priority' => '25',
            'type' => 'persistent',
            'raw' => '0',
        ];
        $_REQUEST = $_POST;

        $this->expectException(\Piwik\Security\CsrfException::class);
        $this->controller->createNotification();

        unset($_POST, $_REQUEST);
    }

    public function testUpdateNotificationWithValidNonce()
    {
        $this->api->insertNotification('1', 'Original Title', 'Original message', 'warning', '25', 'persistent', '0');

        $nonce = Nonce::getNonce('RebelNotifications.update');
        $_POST = [
            'nonce' => $nonce,
            'id' => '1',
            'enabled' => '1',
            'title' => 'Updated Title',
            'message' => 'Updated message',
            'context' => 'success',
            'priority' => '30',
            'type' => 'toast',
            'raw' => '1',
        ];
        $_REQUEST = $_POST;

        $result = $this->controller->updateNotification();
        $this->assertIsString($result);

        $notifications = $this->api->getAllNotifications();
        $updated = false;
        foreach ($notifications as $notification) {
            if ($notification['id'] == 1 && $notification['title'] === 'Updated Title') {
                $updated = true;
                break;
            }
        }
        $this->assertTrue($updated, 'Notification should be updated with valid nonce');

        unset($_POST, $_REQUEST);
    }

    public function testUpdateNotificationWithoutNonceFails()
    {
        $this->api->insertNotification('1', 'Original Title', 'Original message', 'warning', '25', 'persistent', '0');

        $_POST = [
            'id' => '1',
            'enabled' => '1',
            'title' => 'Updated Title',
            'message' => 'Updated message',
            'context' => 'success',
            'priority' => '30',
            'type' => 'toast',
            'raw' => '1',
        ];
        $_REQUEST = $_POST;

        $this->expectException(\Piwik\Security\CsrfException::class);
        $this->controller->updateNotification();

        unset($_POST, $_REQUEST);
    }

    public function testDeleteNotificationWithValidNonce()
    {
        $this->api->insertNotification('1', 'To delete', 'bar', 'warning', '25', 'persistent', '0');
        $this->api->insertNotification('1', 'To keep', 'bar', 'warning', '25', 'persistent', '0');

        $nonce = Nonce::getNonce('RebelNotifications.delete');
        $_POST = [
            'nonce' => $nonce,
            'id' => '1',
        ];
        $_REQUEST = $_POST;

        $result = $this->controller->deleteNotification();
        $this->assertIsString($result);

        $notifications = $this->api->getAllNotifications();
        $this->assertCount(1, $notifications);
        $this->assertEquals('To keep', $notifications[0]['title']);

        unset($_POST, $_REQUEST);
    }

    public function testDeleteNotificationWithoutNonceFails()
    {
        $this->api->insertNotification('1', 'To delete', 'bar', 'warning', '25', 'persistent', '0');

        $_POST = [
            'id' => '1',
        ];
        $_REQUEST = $_POST;

        $this->expectException(\Piwik\Security\CsrfException::class);
        $this->controller->deleteNotification();

        unset($_POST, $_REQUEST);
    }

    public function testAddingNotificationAndGetItListed()
    {
        $this->api->insertNotification('1', 'Title to check for', 'bar', 'warning', '25', 'persistent', '0');
        $result = $this->controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('Title to check for', $result);
    }

    public function testAddingNotificationAndEdit()
    {
        $this->api->insertNotification('1', 'Title to edit', 'bar', 'warning', '25', 'persistent', '0');
        $result = $this->controller->editNotification('1');
        $this->assertIsString($result);
        $this->assertStringContainsString('Title to edit', $result);
    }

    public function testAddingNotificationAndDelete()
    {
        $this->api->insertNotification('1', 'To delete', 'bar', 'warning', '25', 'persistent', '0');
        $this->api->insertNotification('1', 'To keep', 'bar', 'warning', '25', 'persistent', '0');

        $nonce = Nonce::getNonce('RebelNotifications.delete');
        $_POST = ['nonce' => $nonce, 'id' => '1'];
        $_REQUEST = $_POST;

        $this->controller->deleteNotification('1');

        unset($_POST, $_REQUEST);

        $result = $this->api->getAllNotifications();
        $this->assertIsArray($result[0]);
        $results = $result[0];
        $this->assertNotContains('To delete', $results);
        $this->assertContains('To keep', $results);
    }
}
