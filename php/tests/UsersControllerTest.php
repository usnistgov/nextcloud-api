<?php
// tests/UsersControllerTest.php
namespace Tests;

use NamespaceFunction\UsersController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;


class UsersControllerTest extends TestCase
{
    use PHPMock;

    protected function setUp(): void
    {
        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)
                ->expects($this->any());
        }


        $_SERVER['QUERY_STRING'] = '';
    }

    /** @test */
    public function get_users_returns_json_list(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/api/genapi.php/users';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(function ($command, &$output, &$return) {
                $output   = ['["alice","bob"]'];   // what occ would echo
                $return   = 0;                      // success
            });

        $ctrl = new UsersController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame(['alice', 'bob'], $decoded);
    }

    /** @test */
    public function get_users_handles_occ_failure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/users';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(function ($c, &$o, &$r) {
                $o = [];     // no payload
                $r = 1;      // non-zero return-code = error
            });

        $ctrl = new UsersController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $payload = json_decode($json, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('error', $payload);
        $this->assertStringContainsStringIgnoringCase('failed', $payload['error']);
    }

    /** @test */
    public function get_single_user_happy_path(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/users/mnm16';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(function ($c, &$o, &$r) {
                $o = ['{"id":"mnm16","email":"mnm16@nist.gov"}'];
                $r = 0;
            });

        $ctrl = new UsersController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $user = json_decode($json, true);

        $this->assertSame('mnm16', $user['id']);
        $this->assertSame('mnm16@nist.gov', $user['email']);
    }

    /** @test */
    public function get_single_user_not_found(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/api/genapi.php/users/nobody';

        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(function ($c, &$o, &$r) {
                $o = ['User not found'];
                $r = 1;
            });

        $ctrl = new UsersController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $payload = json_decode($json, true);
        $this->assertArrayHasKey('error', $payload);
        $this->assertStringContainsString('does not exist', $payload['error']);
    }
}
