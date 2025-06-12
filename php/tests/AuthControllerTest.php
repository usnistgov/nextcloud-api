<?php
// tests/AuthControllerTest.php
namespace Tests;

use NamespaceFunction\AuthController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;


class AuthControllerTest extends TestCase
{
    use PHPMock;

    protected function setUp(): void
    {

        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)
                ->expects($this->any());
        }


        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/auth';
        $_SERVER['QUERY_STRING'] = '';
    }

    /** @test */
    public function rejects_when_certificate_verification_fails(): void
    {
        $_SERVER['HTTP_X_CLIENT_VERIFY'] = 'FAILED';
        $_SERVER['HTTP_X_CLIENT_CN'] = 'irrelevant';

        $ctrl = new AuthController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $payload = json_decode($json, true);
        $this->assertSame('Client certificate verification failed.', $payload['error']);
    }

    /** @test */
    public function rejects_when_cn_missing(): void
    {
        $_SERVER['HTTP_X_CLIENT_VERIFY'] = 'SUCCESS';
        unset($_SERVER['HTTP_X_CLIENT_CN']);

        $ctrl = new AuthController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $payload = json_decode($json, true);
        $this->assertSame('Client CN is missing.', $payload['error']);
    }

    /** @test */
    public function rejects_when_user_not_found(): void
    {
        $_SERVER['HTTP_X_CLIENT_VERIFY'] = 'SUCCESS';
        $_SERVER['HTTP_X_CLIENT_CN'] = 'ghost';

        // exec called inside verifyUserExists – pretend “occ user:info” fails
        $this->getFunctionMock('NamespaceFunction', 'exec')
            ->expects($this->once())
            ->willReturnCallback(function ($c, &$o, &$r) {
                $o = [];   // empty output
                $r = 1;    // non-zero (user not found)
            });

        $ctrl = new AuthController;

        ob_start();
        $ctrl->handle();
        $json = ob_get_clean();

        $payload = json_decode($json, true);
        $this->assertSame('Certificate is valid, but no matching user found.', $payload['error']);
    }
}
