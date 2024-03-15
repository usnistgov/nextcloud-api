<?php

namespace NamespaceFunction;

use mysqli;

/**
 * Users resource endpoints
 * GET
 * - users
 * - users/{user}
 * PUT
 * - users/{user}/enable
 * - users/{user}/disable
 */
class UsersController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $strErrorDesc = "";

        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        if ($requestMethod == "GET") {
            // GET method
            if (count($arrQueryUri) == 4) {
                // "/genapi.php/users" Endpoint - Gets info on all users
                $this->getUsers();
            } elseif (count($arrQueryUri) == 5) {
                // "/genapi.php/users/{user}" Endpoint - Gets info on one user
                $this->getUser($arrQueryUri[4]);
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "PUT") {
            // PUT method
            if (count($arrQueryUri) == 6) {
                if ($arrQueryUri[5] == "enable") {
                    // "/genapi.php/users/{user}/enable" Endpoint - enables user
                    $this->enableUser($arrQueryUri[4]);
                } elseif ($arrQueryUri[5] == "disable") {
                    // "/genapi.php/users/{user}/diable" Endpoint - disables user
                    $this->disableUser($arrQueryUri[4]);
                }
            }
        } elseif ($requestMethod == "POST") {
            // POST method
            if (count($arrQueryUri) == 5) {
                $this->createUser($arrQueryUri[4]); // "/genapi.php/users/{user}" Endpoint - creates user
            }
        }
        // unsupported method
        else {
            $strErrorDesc =
                $requestMethod . " is not an available request Method";

            $this->sendError405Output($strErrorDesc);
        }
    }

    /**
     * "-X GET /users" Endpoint - Gets list of all users
     */
    private function getUsers()
    {
        $command = parent::$occ . " user:list -i --output json";
        if (exec($command, $arrUser)) {
            $this->sendOkayOutput($arrUser[0]);
            return $arrUser[0];
        }
    }

    /**
     * "-X GET /users/{user}" Endpoint - Gets single user info
     */
    private function getUser($user)
    {
        $command = parent::$occ . " user:info " . $user . " --output json";
        if (exec($command, $arrUser)) {
            $this->sendOkayOutput($arrUser[0]);
            return $arrUser[0];
        }
    }

    /**
     * "-X PUT /users/{user}/enable" Endpoint - Enables user
     */
    private function enableUser($user)
    {
        $command = parent::$occ . " user:enable " . $user;
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X PUT /users/{user}/disable" Endpoint - Disables user
     */
    private function disableUser($user)
    {
        $command = parent::$occ . " user:disable " . $user;
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /users/{user}" Endpoint - creates user
     */
    private function createUser($user)
    {
        // create connection
        $db = new mysqli(
            parent::$dbhost,
            parent::$dbuser,
            parent::$dbpass,
            parent::$dbname
        );
        // check connection
        if ($db->connect_error) {
            die("connection failed: " . $db->connect_error);
        }

        // check if user already exists in database
        $sqlCheck =
            "SELECT COUNT(*) FROM oc_user_saml_users WHERE uid='" .
            $user .
            "';";

        $checkResult = $db->query($sqlCheck);
        foreach ($checkResult as $row) {
            $count = $row["COUNT(*)"];
        }

        if ($count > 0) {
            print_r($user . " already exists");
        } else {
            print_r("adding " . $user);
            // add user to database
            $sql =
                "INSERT INTO oc_user_saml_users (uid) VALUES ('" .
                $user .
                "');";

            if ($db->query($sql) === true) {
                echo $user . " added";
            } else {
                echo "Error: " . $sql . "<br>" . $db->error;
            }
        }

        $db->close();
    }
}
