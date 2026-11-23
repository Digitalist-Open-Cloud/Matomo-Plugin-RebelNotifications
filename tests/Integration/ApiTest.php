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

use Exception;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugins\RebelNotifications\API;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group RebelNotifications
 * @group Plugins
 */
class ApiTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        FakeAccess::clearAccess(true);
        $this->api = API::getInstance();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\Access' => new FakeAccess()];
    }

    public function testANotificationIsStoredAsWritten()
    {
        $this->api->insertNotification(1, ' Tom & "Jerry" ', 'Closed <today>', 'warning', '25', 'persistent', 0);

        $stored = $this->api->getAllNotifications()[0];

        $this->assertSame(['Tom & "Jerry"', 'Closed <today>', 'warning', '25', 'persistent'], [$stored['title'], $stored['message'], $stored['context'], $stored['priority'], $stored['type']]);
        $this->assertEquals([1, 0], [$stored['enabled'], $stored['raw']]);
    }

    public function testARawMessageIsCleanedWhenAddedAndWhenChanged()
    {
        $html = '<b onclick="alert(1)">Hi</b><script>alert(1)</script> <a href="javascript:alert(1)">x</a>';

        $this->api->insertNotification(1, 'Title', $html, 'info', '25', 'toast', 1);
        $id = (int) $this->api->getAllNotifications()[0]['id'];
        $this->assertSame('<b>Hi</b>alert(1) <a>x</a>', $this->api->getAllNotifications()[0]['message']);

        $this->api->updateNotification($id, 1, 'Title', $html, 'info', '25', 'toast', 1);
        $this->assertSame('<b>Hi</b>alert(1) <a>x</a>', $this->api->getAllNotifications()[0]['message']);
    }

    public function testEnabledAndDisabledNotificationsAreListedApart()
    {
        $this->api->insertNotification(1, 'On', 'bar', 'warning', '25', 'persistent', 0);
        $this->api->insertNotification(0, 'Off', 'bar', 'warning', '25', 'persistent', 0);

        $this->assertSame(['On'], array_column($this->api->getEnabledNotifications(), 'title'));
        $this->assertSame(['Off'], array_column($this->api->getDisabledNotifications(), 'title'));
        $this->assertSame(['On', 'Off'], array_column($this->api->getAllNotifications(), 'title'));
    }

    public function testANotificationIsChangedAndDeleted()
    {
        $this->api->insertNotification(1, 'First', 'bar', 'warning', '25', 'persistent', 0);
        $this->api->insertNotification(1, 'Second', 'bar', 'warning', '25', 'persistent', 0);
        [$first, $second] = array_map('intval', array_column($this->api->getAllNotifications(), 'id'));

        $this->api->updateNotification($second, 0, 'Changed', 'new', 'error', '100', 'toast', 0);
        $this->api->deleteNotification($first);
        $this->api->deleteNotification(999);

        $left = $this->api->getAllNotifications();
        $this->assertCount(1, $left);
        $this->assertSame(['Changed', 'new', 'error', '100', 'toast'], [$left[0]['title'], $left[0]['message'], $left[0]['context'], $left[0]['priority'], $left[0]['type']]);
        $this->assertEquals(0, $left[0]['enabled']);
    }

    /**
     * @dataProvider getValuesThatAreRefused
     */
    public function testANotificationIsChecked(string $title, string $message, string $context, string $priority, string $type)
    {
        $this->api->insertNotification(1, 'Kept', 'bar', 'warning', '25', 'persistent', 0);
        $id = (int) $this->api->getAllNotifications()[0]['id'];

        foreach (['insert', 'update'] as $how) {
            try {
                $how === 'insert'
                    ? $this->api->insertNotification(1, $title, $message, $context, $priority, $type, 0)
                    : $this->api->updateNotification($id, 1, $title, $message, $context, $priority, $type, 0);
                $this->fail('The notification was saved.');
            } catch (Exception $e) {
                $this->assertStringNotContainsString('SQLSTATE', $e->getMessage());
            }
        }

        $this->assertSame(['Kept'], array_column($this->api->getAllNotifications(), 'title'));
    }

    public function getValuesThatAreRefused(): array
    {
        return [
            'no title' => [' ', 'Message', 'info', '25', 'toast'],
            'a title that is too long' => [str_repeat('t', 256), 'Message', 'info', '25', 'toast'],
            'no message' => ['Title', '', 'info', '25', 'toast'],
            'a message that is too long' => ['Title', str_repeat('m', 70000), 'info', '25', 'toast'],
            'an unknown context' => ['Title', 'Message', 'danger', '25', 'toast'],
            'a priority that is no number' => ['Title', 'Message', 'info', 'high', 'toast'],
            'a priority out of range' => ['Title', 'Message', 'info', '101', 'toast'],
            'an unknown type' => ['Title', 'Message', 'info', '25', 'popup'],
        ];
    }

    public function testOnlyASuperUserReadsAndChangesNotifications()
    {
        $this->api->insertNotification(1, 'Kept', 'bar', 'warning', '25', 'persistent', 0);
        $id = (int) $this->api->getAllNotifications()[0]['id'];
        FakeAccess::clearAccess(false, [1]);

        $calls = [
            'insertNotification' => [1, 'Planted', 'bar', 'warning', '25', 'persistent', 0],
            'updateNotification' => [$id, 1, 'Planted', 'bar', 'warning', '25', 'persistent', 0],
            'deleteNotification' => [$id],
            'getAllNotifications' => [],
            'getEnabledNotifications' => [],
            'getDisabledNotifications' => [],
        ];
        foreach ($calls as $method => $arguments) {
            try {
                $this->api->$method(...$arguments);
                $this->fail($method . ' worked for an admin.');
            } catch (NoAccessException $e) {
                $this->addToAssertionCount(1);
            }
        }

        FakeAccess::clearAccess(true);
        $this->assertSame(['Kept'], array_column($this->api->getAllNotifications(), 'title'));
    }

    public function testChangesAreAnnounced()
    {
        $events = [];
        foreach (['added', 'updated', 'deleted'] as $what) {
            Piwik::addAction('RebelNotifications.notification.' . $what, function (...$parameters) use (&$events, $what) {
                $events[] = array_merge([$what], $parameters);
            });
        }

        $this->api->insertNotification(1, 'Title', 'bar', 'warning', '25', 'persistent', 0);
        $id = (int) $this->api->getAllNotifications()[0]['id'];
        $this->api->updateNotification($id, 1, 'New title', 'bar', 'warning', '25', 'persistent', 0);
        $this->api->deleteNotification($id);

        $this->assertSame([['added', $id, 'Title'], ['updated', $id, 'New title'], ['deleted', $id]], $events);
    }
}
