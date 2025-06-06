<?php
// tests/FunctionControllerTest.php
namespace Tests;

use NamespaceFunction\FunctionController;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;


class FunctionControllerTest extends TestCase
{
    use PHPMock;

    protected function setUp(): void
    {
        foreach (['header', 'header_remove'] as $fn) {
            $this->getFunctionMock('NamespaceBase', $fn)
                ->expects($this->any());
        }


        // All FunctionController::controller() really needs
        $_SERVER['REQUEST_URI'] = '/api/genapi.php/files';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // base-config used in isAuthenticated()
        $prop = new \ReflectionProperty(\NamespaceBase\BaseController::class, 'oar_api_usr');
        $prop->setAccessible(true);
        $prop->setValue('oar_api');
    }

    /** @test */
    public function denies_access_when_cn_does_not_match_admin(): void
    {
        $_SERVER['HTTP_X_CLIENT_VERIFY'] = 'SUCCESS';
        $_SERVER['HTTP_X_CLIENT_CN'] = 'somebody_else';

        $router = new FunctionController;

        ob_start();
        $router->controller();
        $json = ob_get_clean();

        $payload = json_decode($json, true);

        $this->assertArrayHasKey('error', $payload);
        $this->assertSame('CN does not match the superuser admin username.', $payload['error']);
    }
}
