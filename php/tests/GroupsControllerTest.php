<?php

namespace Tests;

use NamespaceFunction\GroupsController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class GroupsControllerTest extends TestCase
{
    use PHPMock;

    private function ctrl(): GroupsController
    {
        $rc = new \ReflectionClass(GroupsController::class);
        $obj = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('logger');
        $prop->setAccessible(true);
        $lg = new Logger('test');
        $lg->pushHandler(new NullHandler());
        $prop->setValue($obj, $lg);
        return $obj;
    }

    private function silenceHeaders(): void
    {
        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)->expects($this->any());
        }
    }

    public function test_get_groups_success(): void
    {
        $this->silenceHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/groups';
        $_SERVER['QUERY_STRING'] = '';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(fn($c, &$o, &$r) => [$o = ['group1', 'group2'], $r = 0]);

        ob_start();
        $this->ctrl()->handle();
        $payload = json_decode(ob_get_clean(), true);

        $this->assertSame(['group1', 'group2'], $payload['groups']);
    }

    public function test_get_groups_failure(): void
    {
        $this->silenceHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/groups';
        $_SERVER['QUERY_STRING'] = '';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(fn($c, &$o, &$r) => [$o = [], $r = 1]);

        ob_start();
        $this->ctrl()->handle();
        $payload = json_decode(ob_get_clean(), true);

        $this->assertArrayHasKey('error', $payload);
    }
}
