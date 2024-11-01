<?php

namespace NamespaceFunction;

use mysqli;

/**
 * USERS resource endpoints
 * 
 * GET
 * - users
 * - users/{user}
 * PUT
 * - users/{user}/enable
 * - users/{user}/disable
 * POST
 * - users/{user}
 **/
class UsersController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $this->logger->info("Handling Users", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);
        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();
        $queryUri = $this->getUri();

        try {
            switch ($requestMethod) {
                case 'GET':
                    if (count($arrQueryUri) === 4) {
                        // "/genapi.php/users" Endpoint - Gets info on all users
                        $this->getUsers();
                    } elseif (count($arrQueryUri) === 5) {
                        // "/genapi.php/users/{user}" Endpoint - Gets info on one user
                        $this->getUser($arrQueryUri[4]);
                    } else {
                        return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                    }
                    break;
                case 'PUT':
                    if (count($arrQueryUri) === 6) {
                        $user = $arrQueryUri[4];
                        if ($arrQueryUri[5] === 'enable') {
                            // "/genapi.php/users/{user}/enable" Endpoint - enables user
                            $this->enableUser($user);
                        } elseif ($arrQueryUri[5] === 'disable') {
                            // "/genapi.php/users/{user}/diable" Endpoint - disables user
                            $this->disableUser($user);
                        } else {
                            return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                        }
                    }
                    else {
                        return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                    }
                    break;
                case 'POST':
                    if (count($arrQueryUri) === 5) {
                        // "/genapi.php/users/{user}" Endpoint - creates user
                        $this->createUser($arrQueryUri[4]);
                    } else {
                       return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                    }
                    break;

                default:
                    return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in handle method", ['exception' => $e->getMessage()]);
            return $this->sendError400Output($e->getMessage());
        }
    }

    /**
     * "-X GET /users" Endpoint - Gets list of all users
     */
    private function getUsers()
    {
        $command = parent::$occ . " user:list -i --output json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $usersJsonString = $output[0];
            $users = json_decode($usersJsonString, true);
            $this->logger->info("Retrieved all users successfully.");
            return $this->sendOkayOutput(json_encode($users));
        } else {
            $this->logger->error("Failed to retrieve users.");
            return $this->sendError500Output("Failed to retrieve users.");
        }
    }

    /**
     * "-X GET /users/{user}" Endpoint - Gets single user info
     */
    private function getUser($user)
    {
        $command = parent::$occ . " user:info " . $user . " --output json";
        exec($command, $output, $returnVar);
        if (!empty($output)) {
          if ($returnVar === 0) {
            $userJsonString = $output[0];
            $userInfo = json_decode($userJsonString, true);
            $this->logger->info("Retrieved user info successfully.", ['user' => $user]);
            return $this->sendOkayOutput(json_encode($userInfo));
          } else if (str_contains($output[0], "not found")) {
            // user does not exist
            $this->logger->info("Requested user does not exist", ['user' => $user]);
            return $this->sendError404Output("User does not exist");
          }
        }

        // Unexpected error
        $this->logger->error("Unexpected error retrieving user info.", ['user' => $user]);
        return $this->sendError500Output("Failed to retrieve user info for " . $user);
    }

    /**
     * "-X PUT /users/{user}/enable" Endpoint - Enables user
     */
    private function enableUser($user)
    {
        $command = parent::$occ . " user:enable " . $user;
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $this->logger->info("User enabled successfully.", ['user' => $user]);
            return $this->sendOkayOutput("User " . $user . " enabled successfully.");
        } else {
            $this->logger->error("Failed to enable user.", ['user' => $user]);
            return $this->sendError500Output("Failed to enable user " . $user);
        }
    }

    /**
     * "-X PUT /users/{user}/disable" Endpoint - Disables user
     */
    private function disableUser($user)
    {
        $command = parent::$occ . " user:disable " . $user;
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $this->logger->info("User disabled successfully.", ['user' => $user, 'ouput' => $output]);
            return $this->sendOkayOutput("User " . $user . " disabled successfully.");
        } else {
            $this->logger->error("Failed to disable user.", ['user' => $user, 'ouput' => $output]);
            return $this->sendError500Output("Failed to disable user " . $user);
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
            $this->logger->error("Database connection failed: " . $db->connect_error);
            return $this->sendError500Output("Database connection failed.");
        }

        // check if user already exists in database
        $stmt = $db->prepare("SELECT COUNT(*) FROM oc_user_saml_users WHERE uid=?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $this->logger->warning("User already exists.", ['user' => $user]);
            return $this->sendError400Output("User " . $user . " already exists.");
        } else {
            $stmt = $db->prepare("INSERT INTO oc_user_saml_users (uid) VALUES (?)");
            $stmt->bind_param("s", $user);
            if ($stmt->execute()) {
                $this->logger->info("User created successfully.", ['user' => $user]);
                return $this->sendCreatedOutput("User " . $user . " created successfully.");
            } else {
                $this->logger->error("Failed to create user.", ['user' => $user, 'error' => $db->error]);
                return $this->sendError500Output("Failed to create user " . $user);
            }
            $stmt->close();
        }

        $db->close();
    }
}
