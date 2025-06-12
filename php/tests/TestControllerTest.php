<?php

namespace Tests;

use NamespaceFunction\TestController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class TestControllerTest extends TestCase
{
    use PHPMock;

    private function ctrl(): TestController
    {
        $rc = new \ReflectionClass(TestController::class);
        $obj = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('logger');
        $prop->setAccessible(true);
        $lg = new Logger('test');
        $lg->pushHandler(new NullHandler());
        $prop->setValue($obj, $lg);
        return $obj;
    }

    protected function setUp(): void
    {
        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)->expects($this->any());
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/test';
        $_SERVER['QUERY_STRING'] = '';
    }

    public function test_returns_success_message(): void
    {
        ob_start();
        $this->ctrl()->handle();
        $msg = ob_get_clean();

        $this->assertStringContainsString('Test endpoint reached successfully', $msg);
    }
}
