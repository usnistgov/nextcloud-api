<?php

namespace NamespaceFunction;

require_once "BaseController.php";
require_once __DIR__ . "/endpoints/FilesController.php";
require_once __DIR__ . "/endpoints/ExtstoragesController.php";
require_once __DIR__ . "/endpoints/GroupsController.php";
require_once __DIR__ . "/endpoints/HeadersController.php";
require_once __DIR__ . "/endpoints/TestController.php";
require_once __DIR__ . "/endpoints/UsersController.php";

class FunctionController extends \NamespaceBase\BaseController
{
    /**
     * Expected API endpoint:
     * https://nextcloud-dev.nist.gov/api/genapi.php/{resource}/{additional options}
     * uri positions                0   1          2          3                   4+
     *
     * {resource} can be one of the following
     * - Files
     * - Users
     * - Groups
     * - ExtStorage
     * - Test (Returns Method and Query uri)
     * - Auth
     */
    public function __construct()
    {
        parent::__construct();
        $this->verifyClientCertificate();
    }

    private function verifyClientCertificate()
    {
        # Check Valid Client Certificate
        if (!isset($_SERVER['SSL_CLIENT_VERIFY']) || $_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Client certificate verification failed']);
            exit();
        }

        # Check CN
        $config = require $configFilePath;
        $expectedCN = $config['expected_cn'];
        if (isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
            $clientCN = $_SERVER['SSL_CLIENT_S_DN_CN'];
            if ($clientCN !== $expectedCN) {
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['error' => 'Client certificate CN is not valid']);
                exit();
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Client certificate CN not found']);
            exit();
        }
        
    }

    public function getOarApiLogin()
    {
        return parent::$oar_api_login;
    }

    /**
     * All resource endpoints
     */
    public function controller()
    {
        $arrQueryUri = $this->getUriSegments();

        if (count($arrQueryUri) < 4) {
            // "Invalid endpoint"
            $strErrorDesc = $this->getUri() . " is not a valid endpoint";
            $this->sendError404Output($strErrorDesc);
            return;
        }
        if (!$this->isAuthenticated()) {
            $this->sendError401Output(
                $_SERVER["PHP_AUTH_USER"] .
                    " is not authorized to access the API"
            );
            return;
        }

        $resource = strtoupper($arrQueryUri[3]);

        try {
            switch ($resource) {
                case "FILES":
                    // "/genapi.php/files/" group of endpoints
                    $filesController = new FilesController();
                    $filesController->handle();
                    break;
                case "USERS":
                    // "/genapi.php/users/" group of endpoints
                    $usersController = new UsersController();
                    $usersController->handle();
                    break;
                case "GROUPS":
                    // "/genapi.php/groups/" group of endpoints
                    $groupsController = new GroupsController();
                    $groupsController->handle();
                    break;
                case "EXTSTORAGES":
                    // "/genapi.php/extstorages/" group of endpoints
                    $extStoragesController = new ExtStoragesController();
                    $extStoragesController->handle();
                    break;
                case "HEADERS":
                    // "/genapi.php/headers/" Endpoint - prints headers from API call
                    $headersController = new HeadersController();
                    $headersController->handle();
                    break;
                case "TEST":
                    // "/genapi.ph$testController = new TestController();
                    $testController = new TestController();
                    $testController->handle();
                    break;
                default:
                    //Unavailable/unsupported resource
                    return $this->sendError404Output("{$resource} is not an available resource");
                    break;
            }
        } catch (\Exception $e) {
            return $this->send500ErrorResponse($e->getMessage());
        }
    }

    /**
     * Checks if the request is authenticated using Basic Authentication.
     * Returns true if authenticated, false otherwise.
     */
    private function isAuthenticated()
    {
        if (
            !isset($_SERVER["PHP_AUTH_USER"]) ||
            !isset($_SERVER["PHP_AUTH_PW"])
        ) {
            return false;
        }

        $validUser = parent::$oar_api_usr;
        $validPassword = parent::$oar_api_pwd;

        return $_SERVER["PHP_AUTH_USER"] === $validUser &&
            $_SERVER["PHP_AUTH_PW"] === $validPassword;
    }
}
