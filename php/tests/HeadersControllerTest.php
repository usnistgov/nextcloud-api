<?php

namespace Tests;

use NamespaceFunction\HeadersController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class HeadersControllerTest extends TestCase
{
    use PHPMock;

    private function ctrl(): HeadersController
    {
        $rc = new \ReflectionClass(HeadersController::class);
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

        $this->getFunctionMock('NamespaceFunction', 'apache_request_headers')
            ->expects($this->once())
            ->willReturn(['X-Test' => 'ok']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/headers';
        $_SERVER['QUERY_STRING'] = '';
    }

    public function test_returns_headers_as_json(): void
    {
        ob_start();
        $this->ctrl()->handle();
        $json = ob_get_clean();

        $this->assertSame(['X-Test' => 'ok'], json_decode($json, true));
    }
}
