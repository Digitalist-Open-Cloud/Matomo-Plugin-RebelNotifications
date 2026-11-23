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
use Piwik\Common;
use Piwik\Db;
use Piwik\Nonce;
use Piwik\Notification\Manager;
use Piwik\Plugins\RebelNotifications\API;
use Piwik\Plugins\RebelNotifications\Controller;
use Piwik\Plugins\RebelNotifications\RebelNotifications;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group RebelNotifications
 * @group Plugins
 */
class ControllerTest extends IntegrationTestCase
{
    private const FORM = [
        'enabled' => '1',
        'title' => 'Service window',
        'message' => 'We are <b onclick="alert(1)">closed</b><script>alert(1)</script>',
        'context' => 'warning',
        'priority' => '25',
        'type' => 'persistent',
        'raw' => '1',
    ];

    private const CHANGES = ['createNotification', 'updateNotification', 'deleteNotification'];

    /**
     * @var int the notification that is there from the start
     */
    private $id;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        Fixture::createSuperUser();
        Fixture::createWebsite('2025-01-01 00:00:00');
        FakeAccess::clearAccess(true);
        API::getInstance()->insertNotification(1, 'There from the start', 'bar', 'info', '25', 'persistent', 0);
        $this->id = (int) API::getInstance()->getAllNotifications()[0]['id'];
        $_GET = $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function tearDown(): void
    {
        $_GET = $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\Access' => new FakeAccess()];
    }

    public function testANotificationIsCreatedWithACleanMessage()
    {
        $page = $this->post('createNotification', self::FORM);

        $created = API::getInstance()->getAllNotifications()[1];
        $this->assertSame(['Service window', 'We are <b>closed</b>alert(1)'], [$created['title'], $created['message']]);
        $this->assertStringContainsString('Service window', $page);
        $this->assertStringNotContainsString('<script>alert(1)', $page);
    }

    public function testANotificationIsChanged()
    {
        $this->post('updateNotification', ['id' => $this->id, 'title' => 'Changed', 'enabled' => '0', 'raw' => '0'] + self::FORM);

        $changed = API::getInstance()->getAllNotifications()[0];
        $this->assertSame('Changed', $changed['title']);
        $this->assertEquals([0, 0], [$changed['enabled'], $changed['raw']]);
    }

    public function testANotificationIsDeleted()
    {
        $this->post('deleteNotification', ['id' => $this->id]);

        $this->assertSame([], API::getInstance()->getAllNotifications());
    }

    public function testAnErrorIsShownAndNothingIsSaved()
    {
        $page = $this->post('createNotification', ['context' => 'danger'] + self::FORM);

        $this->assertCount(1, API::getInstance()->getAllNotifications());
        $this->assertStringContainsString('has to be one of', $page);
    }

    /**
     * @dataProvider getChanges
     */
    public function testAChangeIsPosted(string $action)
    {
        $this->refuse($action, 'GET', ['nonce' => Nonce::getNonce(Controller::NONCE)]);
    }

    /**
     * @dataProvider getChanges
     */
    public function testAChangeNeedsTheNonceOfThePage(string $action)
    {
        $this->refuse($action, 'POST', []);
        $this->refuse($action, 'POST', ['nonce' => 'not-the-nonce']);
    }

    /**
     * @dataProvider getChanges
     */
    public function testAChangeNeedsASuperUser(string $action)
    {
        $nonce = Nonce::getNonce(Controller::NONCE);
        FakeAccess::clearAccess(false, [1]);

        $this->refuse($action, 'POST', ['nonce' => $nonce]);
    }

    public function getChanges(): array
    {
        return array_combine(self::CHANGES, array_map(function (string $action): array {
            return [$action];
        }, self::CHANGES));
    }

    /**
     * @dataProvider getPages
     */
    public function testThePagesAreForSuperUsers(string $action)
    {
        FakeAccess::clearAccess(false, [1]);

        $this->expectException(Exception::class);
        $this->call($action, 'GET', [], ['id' => $this->id]);
    }

    public function getPages(): array
    {
        return ['index' => ['index'], 'editNotification' => ['editNotification']];
    }

    public function testThePagesEscapeWhatIsStored()
    {
        // As it could be stored before messages were cleaned on saving.
        Db::query(
            'UPDATE ' . Common::prefixTable('rebel_notifications') . ' SET title = ?, message = ?, raw = 1',
            ['"><script>alert(1)</script>', '<b>ok</b><script>alert(2)</script><img src=x onerror=alert(3)>']
        );

        $pages = $this->call('index', 'GET', []) . $this->call('editNotification', 'GET', [], ['id' => $this->id]);

        $this->assertStringContainsString('<b>ok</b>', $pages);
        $this->assertStringNotContainsString('<script>alert(', $pages);
        $this->assertStringNotContainsString('<img src=x', $pages);
    }

    public function testAMessageWithoutRawIsShownAsText()
    {
        Db::query('UPDATE ' . Common::prefixTable('rebel_notifications') . ' SET message = ?, raw = 0', ['<b>bold</b>']);

        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $this->call('index', 'GET', []));
    }

    public function testEnabledNotificationsAreShownAtLoginWithACleanMessage()
    {
        API::getInstance()->insertNotification(0, 'Disabled', 'bar', 'info', '25', 'persistent', 0);
        Db::query(
            'UPDATE ' . Common::prefixTable('rebel_notifications') . ' SET message = ?, raw = 1 WHERE id = ?',
            ['<b>ok</b><script>alert(2)</script>', $this->id]
        );

        (new RebelNotifications())->getNotifications();

        $shown = Manager::getAllNotificationsToDisplay();
        $this->assertSame(['RebelNotifications_' . $this->id], array_keys($shown));
        $this->assertSame('<b>ok</b>alert(2)', $shown['RebelNotifications_' . $this->id]->message);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function post(string $action, array $form): string
    {
        return $this->call($action, 'POST', $form + ['nonce' => Nonce::getNonce(Controller::NONCE)]);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function refuse(string $action, string $method, array $form): void
    {
        $form += ['id' => $this->id, 'title' => 'Planted'] + self::FORM;
        try {
            $this->call($action, $method, $method === 'GET' ? [] : $form, $method === 'GET' ? $form : []);
            $this->fail($action . ' was done.');
        } catch (Exception $e) {
            FakeAccess::clearAccess(true);
            $this->assertSame(['There from the start'], array_column(API::getInstance()->getAllNotifications(), 'title'));
        }
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, mixed> $query
     */
    private function call(string $action, string $method, array $form, array $query = []): string
    {
        $_GET = $query + ['idSite' => 1];
        $_POST = $form;
        $_SERVER['REQUEST_METHOD'] = $method;

        return (string) (new Controller())->$action();
    }
}
