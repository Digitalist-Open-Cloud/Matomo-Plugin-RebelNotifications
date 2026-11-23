<?php

namespace Piwik\Plugins\RebelNotifications\tests\Integration;

use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;
use Piwik\Tests\Framework\Fixture;
use Piwik\Plugins\RebelNotifications\API;

/**
 * @group RebelNotifications
 * @group RebelCommandTest
 * @group Plugins
 */
class CommandsTest extends ConsoleCommandTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();
        Fixture::createSuperUser();
        Fixture::createWebsite('2025-01-01 00:00:00');
        $this->api = API::getInstance();
    }

    public function testHelpCreateNotification()
    {
        $code = $this->applicationTester->run([
            'command' => 'rebelnotifications:create',
            '--help' => true,
            '-vvv' => true,
        ]);
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase(
            "rebelnotifications:create [options]",
            $this->applicationTester->getDisplay()
        );
    }

    public function testCreateNotification()
    {
        $code = $this->applicationTester->run([
            'command' => 'rebelnotifications:create',
            '--raw' => true,
            '--enabled' => true,
            '--title' => 'test title',
            '--message' => 'this is the message',
            '--context' => 'warning',
            '--priority' => '50',
            '--type' => 'persistent'
        ]);

        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase(
            "Created notification: test title",
            $this->applicationTester->getDisplay()
        );

        $this->applicationTester->run([
            'command' => 'rebelnotifications:create',
            '--title' => 'plain and off',
            '--message' => 'this is <b>text</b>',
            '--context' => 'warning',
            '--priority' => '50',
            '--type' => 'persistent'
        ]);
        $created = $this->api->getAllNotifications();
        $this->assertEquals([[1, 1], [0, 0]], [[$created[0]['enabled'], $created[0]['raw']], [$created[1]['enabled'], $created[1]['raw']]]);
    }

    public function testHelpListNotifications()
    {
        $code = $this->applicationTester->run([
            'command' => 'rebelnotifications:list',
            '--help' => true,
            '-vvv' => true,
        ]);
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase(
            "rebelnotifications:list [options]",
            $this->applicationTester->getDisplay()
        );
    }

    public function testListEnabledNotifications()
    {
        $this->api->insertNotification('1', 'this should show', 'bar', 'warning', '25', 'persistent', '0');
        $this->api->insertNotification('0', 'this should not show', 'bar', 'warning', '25', 'persistent', '0');
        $code = $this->applicationTester->run([
            'command' => 'rebelnotifications:list',
            '--enabled' => true,
        ]);

        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase(
            "this should show",
            $this->applicationTester->getDisplay()
        );
        $this->assertStringNotContainsStringIgnoringCase(
            "this should not show",
            $this->applicationTester->getDisplay()
        );
    }

    public function testListAllNotifications()
    {
        $this->api->insertNotification('0', 'this one is disabled', 'bar', 'warning', '25', 'persistent', '0');
        $code = $this->applicationTester->run([
            'command' => 'rebelnotifications:list',
        ]);

        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase(
            "this one is disabled",
            $this->applicationTester->getDisplay()
        );
    }
}
