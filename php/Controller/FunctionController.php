<?php

namespace NamespaceFunction;

require_once "BaseController.php";
require_once __DIR__ . "/endpoints/FilesController.php";
require_once __DIR__ . "/endpoints/ExtstoragesController.php";
require_once __DIR__ . "/endpoints/GroupsController.php";
require_once __DIR__ . "/endpoints/HeadersController.php";
require_once __DIR__ . "/endpoints/TestController.php";
require_once __DIR__ . "/endpoints/UsersController.php";
require_once __DIR__ . "/endpoints/AuthController.php";


class FunctionController extends \NamespaceBase\BaseController
{
    /**
     * Expected API endpoint:
     * https://nextcloud-dev.nist.gov/api/genapi.php/{resource}/{additional options}
     * uri positions                0   1          2          3                   4+
     *
     * {resource} can be one of the following
     * - Auth
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

        $resource = strtoupper($arrQueryUri[3]);

        try {
            switch ($resource) {
                case "AUTH":
                    // Skip authentication check for the "/auth" endpoint
                    $authController = new AuthController();
                    $authController->handle();
                    break;
                case "FILES":
                case "USERS":
                case "GROUPS":
                case "EXTSTORAGES":
                case "HEADERS":
                case "TEST":
                    // Apply authentication check for all other endpoints
                    if ($this->isAuthenticated()) {

                        // Route to the corresponding controller
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
                                // "/genapi.php/test/" Endpoint - test endpoint
                                $testController = new TestController();
                                $testController->handle();
                                break;
                            }
                        }
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
     * Checks if the request is authenticated.
     * Returns true if authenticated, false otherwise.
     */
    private function isAuthenticated()
    {

        $clientVerify = $_SERVER['HTTP_X_CLIENT_VERIFY'] ?? null;
        $clientCN = $_SERVER['HTTP_X_CLIENT_CN'] ?? null;
      
        // Check if client verification succeeded
        if ($clientVerify !== 'SUCCESS') {
            $this->sendError401Output("Client certificate verification failed.");
            return false;
        }
    
        // Check if the client CN is provided
        if (!$clientCN) {
            $this->sendError401Output("Client CN is missing.");
            return false;
        }

        $superuserUsername = parent::$oar_api_usr;

        // Check if the CN from the certificate matches the admin username
        if ($clientCN !== $superuserUsername) {
            $this->sendError401Output("CN does not match the superuser admin username.");
            return false;
        }

        // If the CN matches, allow access
        $this->logger->info("User authenticated as admin via certificate CN match.", ['CN' => $clientCN]);
        return true;
    }

}