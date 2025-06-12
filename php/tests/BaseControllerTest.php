<?php
// tests/BaseControllerTest.php
namespace Tests;

use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class BaseControllerTest extends TestCase
{
    use PHPMock;

    private function makeController()
    {
        $rc = new \ReflectionClass(\NamespaceBase\BaseController::class);
        return $rc->newInstanceWithoutConstructor();   // ← key change
    }

    protected function setUp(): void
    {
        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)->expects($this->any());
        }

        $_SERVER['REQUEST_URI'] = '/api/genapi.php/users';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function test_it_extracts_uri_segments_correctly(): void
    {
        $ctrl = $this->makeController();

        $method = new \ReflectionMethod($ctrl, 'getUriSegments');
        $method->setAccessible(true);
        $segments = $method->invoke($ctrl);

        $this->assertSame(['', 'api', 'genapi.php', 'users'], $segments);
    }

    public function test_it_detects_string_prefix_and_suffix(): void
    {
        $ctrl = $this->makeController();

        $starts = new \ReflectionMethod($ctrl, 'startsWith');
        $starts->setAccessible(true);
        $ends   = new \ReflectionMethod($ctrl, 'endsWith');
        $ends->setAccessible(true);

        $this->assertTrue($starts->invoke($ctrl, 'hello-world', 'hello'));
        $this->assertTrue($ends->invoke($ctrl, 'hello-world', 'world'));
    }
}
