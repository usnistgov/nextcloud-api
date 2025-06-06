<?php

namespace Tests;

use NamespaceFunction\FilesController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class FilesControllerTest extends TestCase
{
    use PHPMock;

    private function ctrl(): FilesController
    {
        $rc = new \ReflectionClass(FilesController::class);
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

    protected function setUp(): void
    {
        $this->silenceHeaders();
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/files/scan';
        $_SERVER['QUERY_STRING'] = '';
    }

    public function test_scan_all_files_success(): void
    {
        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(fn($c, &$o, &$r) => [$o = ['Scanned 42 files'], $r = 0]);

        ob_start();
        $this->ctrl()->handle();
        $output = ob_get_clean();

        $this->assertStringContainsString('Scanned 42 files', $output);
    }

    public function test_scan_all_files_failure(): void
    {
        // silence the log line
        $this->getFunctionMock('NamespaceFunction', 'error_log')
            ->expects($this->once());

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(fn($c, &$o, &$r) => [$o = [], $r = 1]);

        ob_start();
        $this->ctrl()->handle();
        $payload = json_decode(ob_get_clean(), true);

        $this->assertArrayHasKey('error', $payload);
    }
}
