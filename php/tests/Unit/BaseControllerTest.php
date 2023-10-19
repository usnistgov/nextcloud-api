<?php

namespace NamespaceBase;

require_once('./Controller/BaseController.php');

use PHPUnit\Framework\TestCase;

// Unit test class for BaseController
class BaseControllerTest extends TestCase
{
    private $controller;

    // Set up method runs before each test
    protected function setUp(): void
    {
        $this->controller = new BaseController();
    }

    public function testGetUriSegments()
    {
        // Get a reflection of BaseController and make getUriSegments accessible
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getMethod('getUriSegments');
        $reflectionMethod->setAccessible(true);

        // Set up a sample REQUEST_URI
        $_SERVER['REQUEST_URI'] = '/example/path/segments';
        $result = $reflectionMethod->invoke($this->controller);
        $expected = ['', 'example', 'path', 'segments'];

        $this->assertEquals($expected, $result);
    }

    public function testGetQueryStringParams()
    {
        // Get a reflection of BaseController and make getQueryStringParams accessible
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getMethod('getQueryStringParams');
        $reflectionMethod->setAccessible(true);

        // Set up a sample QUERY_STRING
        $_SERVER['QUERY_STRING'] = 'key1=value1&key2=value2';
        $result = $reflectionMethod->invoke($this->controller);
        $expected = ['key1' => 'value1', 'key2' => 'value2'];

        $this->assertEquals($expected, $result);
    }

    public function testGetRequestMethod()
    {
        // Get a reflection of BaseController and make getRequestMethod accessible
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getMethod('getRequestMethod');
        $reflectionMethod->setAccessible(true);

        // Set up a sample REQUEST_METHOD
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = $reflectionMethod->invoke($this->controller);
        $expected = 'GET';

        $this->assertEquals($expected, $result);
    }

    public function testStartsWith()
    {
        // Get a reflection of BaseController and make startsWith accessible
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getMethod('startsWith');
        $reflectionMethod->setAccessible(true);

        // Test cases for startsWith
        $result = $reflectionMethod->invokeArgs($this->controller, ['hello world', 'hello']);
        $this->assertTrue($result);

        $result = $reflectionMethod->invokeArgs($this->controller, ['hello world', 'world']);
        $this->assertFalse($result);
    }

    public function testEndsWith()
    {
        // Get a reflection of BaseController and make endsWith accessible
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getMethod('endsWith');
        $reflectionMethod->setAccessible(true);

        // Test cases for endsWith
        $result = $reflectionMethod->invokeArgs($this->controller, ['hello world', 'world']);
        $this->assertTrue($result);

        $result = $reflectionMethod->invokeArgs($this->controller, ['hello world', 'hello']);
        $this->assertFalse($result);
    }
}
