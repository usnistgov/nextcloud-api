<?php

namespace NamespaceFunction;

require_once "BaseController.php";

use mysqli;

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

    /**
     * Path to occ command
     */
    private static $occ = "php /var/www/html/occ";

    // service credentials
    private static $oar_api_login = "";
    private static $oar_api_usr = "";
    private static $oar_api_pwd = "";

    // db credentials
    private static $dbhost = "";
    private static $dbuser = "";
    private static $dbpass = "";
    private static $dbname = "";

    public function __construct()
    {
        $configFilePath = __DIR__ . '/../config/custom_config.php';
        if (!file_exists($configFilePath)) {
            throw new \RuntimeException("Config file not found: {$configFilePath}");
        }
        $config = require $configFilePath;

        self::$oar_api_login = $config["user_pass"];
        self::$dbhost = $config["db_host"];
        self::$dbuser = $config["mariadb_user"];
        self::$dbpass = $config["mariadb_password"];
        self::$dbname = $config["mariadb_database"];
        list(self::$oar_api_usr, self::$oar_api_pwd) = explode(
            ":",
            self::$oar_api_login
        );
    }

    public function getOarApiLogin()
    {
        return self::$oar_api_login;
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
        } elseif (!$this->isAuthenticated()) {
            $this->sendError401Output(
                $_SERVER["PHP_AUTH_USER"] .
                    " is not authorized to access the API"
            );
        } else {
            $resource = strtoupper($arrQueryUri[3]);

            if ($resource == "FILES") {
                // "/genapi.php/files/" group of endpoints
                $this->files();
            } elseif ($resource == "USERS") {
                // "/genapi.php/users/" group of endpoints
                $this->users();
            } elseif ($resource == "GROUPS") {
                // "/genapi.php/groups/" group of endpoints
                $this->groups();
            } elseif ($resource == "EXTSTORAGES") {
                // "/genapi.php/extstorages/" group of endpoints
                $this->extStorages();
            } elseif ($resource == "HEADERS") {
                // "/genapi.php/headers/" Endpoint - prints headers from API call
                $this->headers();
            } elseif ($resource == "TEST") {
                // "/genapi.php/test/" Endpoint - prints Method and URI
                $this->test();
            }
            //Unavailable/unsupported resource
            else {
                $strErrorDesc = $resource . " is not an available resource";

                $this->SendError404Output($strErrorDesc);
            }
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

        $validUser = self::$oar_api_usr;
        $validPassword = self::$oar_api_pwd;

        return $_SERVER["PHP_AUTH_USER"] === $validUser &&
            $_SERVER["PHP_AUTH_PW"] === $validPassword;
    }

    /**
     * Files resource endpoints
     * PUT
     * - files/scan
     * - files/scan/{user}
     * - files/file/{path to file}
     * POST
     * - files/file/{path to dir}
     * GET
     * - files/file/{path to file}
     */
    private function files()
    {
        $strErrorDesc = "";

        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        if ($requestMethod == "POST") {
            // POST method
            if ($arrQueryUri[4] == "file") {
                // "/genapi.php/files/file/{path to destination directory (optional: default is oar_api root dir)} Endpoint - creates file
                $destinationPath = isset($arrQueryUri[5]) ? $arrQueryUri[5] : '';
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    if (isset($arrQueryUri[$i])) {
                        $destinationPath .= "/" . $arrQueryUri[$i];
                    }
                }
                // Assuming the file is sent with a field name 'file'
                $localFilePath = $_FILES['file']['tmp_name'] ?? null;
                $this->postFile($localFilePath, $destinationPath);
            } elseif ($arrQueryUri[4] == "directory") {
                // "/genapi.php/files/directory/{directory name}" Endpoint - creates directory
                $dir = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->postDirectory($dir);
            } elseif ($arrQueryUri[4] == "userpermissions") {
                // "/genapi.php/files/userpermissions/{user}/{permissions}/{directory}" Endpoint - share directory with user with permissions
                $user = $arrQueryUri[5];
                $perm = $arrQueryUri[6];
                $dir = $arrQueryUri[7];
                for ($i = 8; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->postUserPermissions($user, $perm, $dir);
            } elseif ($arrQueryUri[4] == "sharegroup") {
                // "/genapi.php/files/sharegroup/{group}/{permissions}/{directory}" Endpoint - share directory with group with permissions
                $group = $arrQueryUri[5];
                $perm = $arrQueryUri[6];
                $dir = $arrQueryUri[7];
                for ($i = 8; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->shareGroup($group, $perm, $dir);
            }
        } elseif ($requestMethod == "PUT") {
            // PUT method
            if ($arrQueryUri[4] == "file") {
                // "/genapi.php/files/file/{full path to file}" Endpoint - updates file
                $destinationPath = $arrQueryUri[5] ?? '';
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $destinationPath .= "/" . ($arrQueryUri[$i] ?? '');
                }

                // Read the PUT input data and write it to a temporary file
                $putData = fopen("php://input", "r");
                $tempFilePath = tempnam(sys_get_temp_dir(), 'PUT_');
                $tempFile = fopen($tempFilePath, "w");
                stream_copy_to_stream($putData, $tempFile);

                // Close the streams
                fclose($tempFile);
                fclose($putData);

                $this->putFile($tempFilePath, $destinationPath);

                // After the operation, delete the temporary file
                unlink($tempFilePath);
            } elseif ($arrQueryUri[4] == "userpermissions") {
                // "/genapi.php/files/userpermissions/{user}/{permissions}/{directory}" Endpoint - Modify user permissions on directory
                $user = $arrQueryUri[5];
                $perm = $arrQueryUri[6];
                $dir = $arrQueryUri[7];
                for ($i = 8; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->putUserPermissions($user, $perm, $dir);
            } elseif ($arrQueryUri[4] == "scan" && $arrQueryUri[5] == "directory") {
                // "/genapi.php/files/scan/directory/{directory path}" Endpoint - scan directory's file system
                $dir = $arrQueryUri[6];
                for ($i = 7; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->scanDirectoryFiles($dir);
            } elseif (count($arrQueryUri) == 5) {
                // "/genapi.php/files/scan" Endpoint - scans all file systems
                $this->scanAllFiles();
            } elseif (count($arrQueryUri) == 6) {
                // "/genapi.php/files/scan/{user}" Endpoint - scan user's file system
                $this->scanUserFiles($arrQueryUri[5]);
            }
        } elseif ($requestMethod == "GET") {
            // GET method
            if ($arrQueryUri[4] == "file") {
                // "/genapi.php/files/file/{file path}" Endpoint - get textual file content
                $filepath = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $filepath .= "/" . $arrQueryUri[$i];
                }
                $this->getFile($filepath);
            } else if ($arrQueryUri[4] == "directory") {
                // "/genapi.php/files/directory/{directory name}" Endpoint - get directory info
                $dir = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->getDirectory($dir);
            } elseif ($arrQueryUri[4] == "userpermissions") {
                // "/genapi.php/files/userpermissions/{directory}" Endpoint - Get users with permissions to directory
                $dir = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->getUserPermissions($dir);
            }
        } elseif ($requestMethod == "DELETE") {
            // DELETE method
            if ($arrQueryUri[4] == "directory") {
                // "/genapi.php/files/directory/{directory name}" Endpoint - delete directory
                $dir = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->deleteDirectory($dir);
            } elseif ($arrQueryUri[4] == "userpermissions") {
                // "/genapi.php/files/userpermissions/{user}/{directory}" Endpoint - Remove all user permissions to directory
                $user = $arrQueryUri[5];
                $dir = $arrQueryUri[6];
                for ($i = 7; $i < count($arrQueryUri); $i++) {
                    $dir .= "/" . $arrQueryUri[$i];
                }
                $this->deleteUserPermissions($user, $dir);
            } elseif ($arrQueryUri[4] == "file") {
                // "/genapi.php/files/file/{file path}" Endpoint - Delete file
                $filepath = $arrQueryUri[5];
                for ($i = 6; $i < count($arrQueryUri); $i++) {
                    $filepath .= "/" . $arrQueryUri[$i];
                }
                $this->deleteFile($filepath);
            }
            // unsupported method
        } else {
            $strErrorDesc =
                $requestMethod . " is not an available request Method";

            $this->sendError405Output($strErrorDesc);
        }
    }

    /**
     * "-X DELETE /files/file/{file path}" Endpoint - deletes file
     */
    private function deleteFile($filePath)
    {
        $command = "curl -s -X DELETE -k -u " .
            self::$oar_api_login .
            " \"http://localhost/remote.php/dav/files/" . self::$oar_api_usr . "/" . ltrim($filePath, '/') . "\"";

        $output = null;
        $returnVar = null;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $responseData = json_encode($output);
            $this->sendOkayOutput($responseData);
            return $responseData;
        } else {
            $this->sendError500Output('Failed to delete the file.');
            return null;
        }
    }

    /**
     * -X PUT /files/movefile/{file path}" Endpoint - moves file TODO
     */
    private function moveFile($file, $dest)
    {
    }

    /**
     * -X PUT /files/copyfile/{file path}" Endpoint - copy file TODO
     */
    private function copyFile($file, $dest)
    {
    }

    /**
     * "-X GET /files/file/{file path}" Endpoint - gets file content (if textual) and metadata
     */
    private function getFile($filePath)
    {
        // Fetch file metadata
        $metadataCommand = "curl -s -X PROPFIND -k -u " .
            self::$oar_api_login .
            " -H \"Depth: 0\" \"http://localhost/remote.php/dav/files/" . self::$oar_api_usr . "/" . ltrim($filePath, '/') . "\"";

        $mdOutput = null;
        $mdReturnVar = null;

        // Execute metadata command
        exec($metadataCommand, $mdOutput, $mdReturnVar);

        $metadata = implode("\n", $mdOutput);

        // Check if file exists
        if (strpos($metadata, '<s:exception>Sabre\\DAV\\Exception\\NotFound</s:exception>') !== false) {
            $this->sendError404Output('File not found.');
            return null;
        }    

        if ($mdReturnVar !== 0) {
            $this->sendError500Output('Failed to retrieve the file metadata.');
            return null;
        }

        $responseData = json_encode([
            'metadata' => $metadata,
        ]);

        $this->sendOkayOutput($responseData);
        return $responseData;
    }

    /**
     * "-X POST /files/file/{ path to destination directory } Endpoint - creates file
     */
    private function postFile($localFilePath, $destinationPath)
    {
        // Check if file was provided and uploaded without errors
        if (!$localFilePath || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $error = "File upload error!";
            $this->sendError500Output($error);
            return $error;
        }
        // Extract filename and extension from the local file path
        $originalFilename = $_FILES['file']['name'];
        $fileInfo = pathinfo($originalFilename);
        $filenameWithExtension = $fileInfo['basename'];


        // Construct the full destination path for the file on Nextcloud
        $fullDestinationPath = ($destinationPath ? rtrim($destinationPath, '/') . '/' : '') . $filenameWithExtension;

        $command = "curl -X PUT -k -u " . escapeshellarg(self::$oar_api_login) .
            " --data-binary @" . escapeshellarg($localFilePath) .
            " http://localhost/remote.php/dav/files/oar_api/" . $fullDestinationPath;

        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $responseData = json_encode($output);
            $this->sendOkayOutput($responseData);
            return $responseData;
        } else {
            $this->sendError500Output("File upload failed!");
            return null;
        }
    }

    /**
     * "PUT /files/file/{path to file}" Endpoint - updates existing file
     */
    private function putFile($localFilePath, $destinationPath)
    {
        if (!$localFilePath || !file_exists($localFilePath)) {
            $error = "Local file path invalid or file does not exist!";
            $this->sendError500Output($error);
            return;
        }

        $fullDestinationPath = rtrim($destinationPath, '/');

        $credentials = self::$oar_api_login;

        $url = "http://localhost/remote.php/dav/files/oar_api/" . ltrim($fullDestinationPath, '/');

        $command = "curl -X PUT -k -u " . escapeshellarg($credentials) .
            " --data-binary @" . escapeshellarg($localFilePath) .
            " " . escapeshellarg($url) . " 2>&1";

        exec($command, $output, $return_var);


        if ($return_var === 0) {
            $responseData = json_encode(['content' => $output]);
            $this->sendOkayOutput($responseData);
        } else {
            $errorOutput = implode("\n", $output);
            $this->sendError500Output("File update failed! Error: " . $errorOutput);
        }
    }

    /**
     * "-X POST /files/directory/{directory name}" Endpoint - creates directory
     */
    private function postDirectory($dir)
    {
        $command =
            "curl -X MKCOL -k -u " .
            self::$oar_api_login .
            " http://localhost/remote.php/dav/files/oar_api/" .
            $dir;
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X GET /files/directory/{directory name}" Endpoint - get directory info
     */
    private function getDirectory($dir)
    {
        $command =
            "curl -X PROPFIND -k -u " .
            self::$oar_api_login .
            " -H \"Depth: 0\" http://localhost/remote.php/dav/files/oar_api/" .
            $dir;
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X DELETE /files/directory/{directory name}" Endpoint - deletes directory
     */
    #TODO
    private function deleteDirectory($dir)
    {
        $command =
            "curl -X DELETE -k -u " .
            self::$oar_api_login .
            " http://localhost/remote.php/dav/files/oar_api/" .
            $dir;
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X PUT /files/scan" Endpoint - scans all users file systems
     */
    private function scanAllFiles()
    {
        $command = self::$occ . " files:scan --all";
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X PUT /files/scan/{user}" Endpoint - scan user file system
     */
    private function scanUserFiles($user)
    {
        $command = self::$occ . " files:scan " . $user;

        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }


    /**
     * "-X PUT /files/scan/directory/{directory path}" Endpoint - scan directory file system
     */
    private function scanDirectoryFiles($dir)
    {
        $command =
            "curl -X PROPFIND -H \"Depth: 1\" -H \"Content-Type: application/xml\" -k -u " .
            self::$oar_api_login .
            " -d '<?xml version=\"1.0\"?> " .
            "<d:propfind xmlns:d=\"DAV:\" xmlns:oc=\"http://owncloud.org/ns\" xmlns:nc=\"http://nextcloud.org/ns\">" .
            "<d:allprop />" .
            "<d:prop>" .
            "<oc:fileid />" .
            "<oc:permissions />" .
            "<oc:size />" .
            "<oc:checksums />" .
            "<oc:favorite />" .
            "<nc:has-preview />" .
            "<oc:tags />" .
            "<oc:comments-href />" .
            "<oc:comments-count />" .
            "<oc:comments-unread />" .
            "<oc:share-types />" .
            "<oc:owner-display-name />" .
            "<oc:quota-used-bytes />" .
            "<oc:quota-available-bytes />" .
            "</d:prop>" .
            "</d:propfind>' " .
            "\"http://localhost/remote.php/dav/files/" . self::$oar_api_usr . "/" . ltrim($dir, '/') . "\"";

        if (exec($command, $arrDir)) {
            $responseData = json_encode($arrDir);
            $this->sendOkayOutput($responseData);

            return $responseData;
        } else {
            $this->sendError500Output("Failed to scan directory.");
            return;
        }
    }


    /**
     * "-X POST /files/userpermissions/{user}/{permissions}/{directory}" Endpoint - share file/folder with user with permissions
     */
    private function postUserPermissions($user, $perm, $dir)
    {
        $command =
            "curl -X POST -H \"ocs-apirequest:true\" -k -u " .
            self::$oar_api_login .
            " \"http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=0" .
            "&path=" .
            $dir .
            "&shareWith=" .
            $user .
            "&permissions=" .
            $perm .
            "\"";
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X GET /files/userpermissions/{directory}" Endpoint - get users with permissions to file/folder
     */
    private function getUserPermissions($dir)
    {
        $command =
            "curl -X GET -H \"OCS-APIRequest: true\" -k -u " .
            self::$oar_api_login .
            " 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?path=/" .
            $dir .
            "&reshares=true'";

        $arrResult = [];

        if (exec($command, $arrResult)) {
            $responseData = json_encode($arrResult);
            $this->sendOkayOutput($responseData);
            return $responseData;
        }

        $responseData = json_encode($arrResult);
        $this->sendError404Output($responseData);

        return $responseData;
    }

    /**
     * "-X PUT /files/userpermissions/{user}/{permissions}/{directory}" Endpoint - Modify user permissions to directory
     */
    #TODO
    private function putUserPermissions($user, $perm, $dir)
    {
        // Delete existing permissions
        $deleteResult = $this->deleteUserPermissions($user, $dir);

        // If the deletion was successful, add new permissions
        $deleteResultData = json_decode($deleteResult, true);

        if (!array_key_exists('error', $deleteResultData)) {
            return $this->postUserPermissions($user, $perm, $dir);
        }

        // If there was an issue with the deletion
        return $deleteResult;
    }

    /**
     * "-X DELETE /files/userpermissions/{user}/{directory}" Endpoint - Delete user permissions to directory
     */
    private function deleteUserPermissions($user, $dir)
    {
        // list of all shares on a specific directory to retrieve shareID
        $command =
            "curl -X GET -H \"OCS-APIRequest: true\" -k -u " .
            self::$oar_api_login .
            " 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?path=/" .
            $dir .
            "&reshares=true'";

        $arrResult = [];

        if (exec($command, $arrResult)) {
            $data = implode("\n", $arrResult);
            $idPattern = '/<id>(.*?)<\/id>/';
            $userPattern = '/<share_with>(.*?)<\/share_with>/';

            if (preg_match_all($idPattern, $data, $idMatches) && preg_match_all($userPattern, $data, $userMatches)) {
                $shareIds = $idMatches[1];
                $shareUsers = $userMatches[1];

                foreach ($shareIds as $index => $shareId) {
                    if (isset($shareUsers[$index]) && $shareUsers[$index] == $user) {
                        // Delete the share
                        $deleteCommand =
                            "curl -X DELETE -H \"OCS-APIRequest: true\" -k -u " .
                            self::$oar_api_login .
                            " 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/" .
                            $shareId .
                            "'";

                        // Execute delete command
                        $arrDeleteResult = [];
                        if (exec($deleteCommand, $arrDeleteResult)) {
                            $responseData = json_encode($arrDeleteResult);
                            $this->sendOkayOutput($responseData);

                            return $responseData;
                        }
                    }
                }
            }
        }

        $responseData = json_encode($arrResult);
        $this->sendError404Output($responseData);

        return $responseData;
    }

    /**
     * "-X POST /files/sharegroup/{group}/{permissions}/{directory}" Endpoing - share file/folder with group with permissions
     */
    private function shareGroup($group, $perm, $dir)
    {
        $command =
            "curl -X POST -H \"ocs-apirequest:true\" -k -u " .
            self::$oar_api_login .
            " \"http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=1" .
            "&path=" .
            $dir .
            "&shareWith=" .
            $group .
            "&permissions=" .
            $perm .
            "\"";
        if (exec($command, $arrUser)) {
            $responseData = json_encode($arrUser);
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * Users resource endpoints
     * GET
     * - users
     * - users/{user}
     * PUT
     * - users/{user}/enable
     * - users/{user}/disable
     */
    private function users()
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
        $command = self::$occ . " user:list -i --output json";
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
        $command = self::$occ . " user:info " . $user . " --output json";
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
        $command = self::$occ . " user:enable " . $user;
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
        $command = self::$occ . " user:disable " . $user;
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
            self::$dbhost,
            self::$dbuser,
            self::$dbpass,
            self::$dbname
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

    /**
     * Group resource endpoints
     * GET
     * - groups
     * - groups/{group name}
     * POST
     * - groups/{group name}
     * - groups/{group name}/{member}
     * DELETE
     * - groups/{group name}
     * - groups/{group name}/{member}
     */
    private function groups()
    {
        $strErrorDesc = "";

        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        if ($requestMethod == "GET") {
            // GET method
            if (count($arrQueryUri) == 4) {
                // "/genapi.php/groups" Endpoint - returns list of all groups
                $this->getGroups();
            } elseif (count($arrQueryUri) == 5) {
                // "/genapi.php/groups/{group name}" Endpoint - returns list of members of specific group
                $this->getGroupMembers($arrQueryUri[4]);
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "POST") {
            if (count($arrQueryUri) == 5) {
                // "/genapi.php/groups/{group name}" Endpoing - creates group
                $this->addGroup($arrQueryUri[4]);
            } elseif (count($arrQueryUri) == 6) {
                // "/genapi.php/groups/{group name}/{member}" Endpoint - adds member to group
                $this->addGroupMember($arrQueryUri[4], $arrQueryUri[5]);
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "DELETE") {
            if (count($arrQueryUri) == 5) {
                // "/genapi.php/groups/{group name}" Endpoint - deletes group
                $this->deleteGroup($arrQueryUri[4]);
            } elseif (count($arrQueryUri) == 6) {
                // "/genapi.php/groups/{group name}/{member}" Endpoint - removes member from group
                $this->removeGroupMember($arrQueryUri[4], $arrQueryUri[5]);
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
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
     * Returns array of array of occ group:list output
     */
    private function parseGroups($groups)
    {
        // Building json file from occ output
        $jsonArr = [];
        $group = "";

        foreach ($groups as $var) {
            // Group name found
            if ($this->endsWith($var, ":")) {
                $group = rtrim(substr($var, 4), ":"); // parse out group
                $jsonArr[$group] = [];
            }
            // member found
            else {
                $member = substr($var, 6); // parse out member
                array_push($jsonArr[$group], $member);
            }

            unset($var);
        }

        return $jsonArr;
    }

    /**
     * "-X GET /groups" Endpoint - Get list of all groups
     */
    private function getGroups()
    {
        $command = self::$occ . " group:list";
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($this->parseGroups($arrGroup));
            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X GET /groups/{group name}" Endpoint - Get list of all members of given group
     */
    private function getGroupMembers($group)
    {
        $command = self::$occ . " group:list";
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($this->parseGroups($arrGroup)[$group]);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /groups/{group name}" Endpoint - Create group
     */
    private function addGroup($group)
    {
        $command = self::$occ . " group:add " . $group;
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($arrGroup);

            $this->sendCreatedOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /groups/{group name}/{member}" Endpoint - Add member to group
     */
    private function addGroupMember($group, $member)
    {
        $command = self::$occ . " group:adduser " . $group . " " . $member;
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($arrGroup);

            $this->sendCreatedOutput($responseData);
            return $responseData;
        }
    }

    /**
     * -X DELETE /groups/{group name}" Endpoint - Delete group
     */
    private function deleteGroup($group)
    {
        $command = self::$occ . " group:delete " . $group;
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($arrGroup);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X DELETE /groups/{group name}/{member}" Endpoint - Remove member from group
     */
    private function removeGroupMember($group, $member)
    {
        $command = self::$occ . " group:removeuser " . $group . " " . $member;
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($arrGroup);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * External Storages resource endpoints
     * GET
     * - extstorages
     * POST
     * - extstorages/local/{name}
     * - extstorages/s3/{name}
     * - extstorages/users/{user}
     * - extstorages/groups/{group}
     * PUT
     * - extstorages/{storage id}/config/{key}/{value}
     * - extstorages/{storage id}/option/{key}/{value}
     * DELETE
     * - extstorages/{storage id}
     * - extstorages/users/{user}
     * - extstorages/groups/{group}
     */
    private function extStorages()
    {
        $strErrorDesc = "";

        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        if ($requestMethod == "GET") {
            if (count($arrQueryUri) == 4) {
                // /genapi.php/extstorages endpoint - list all external storages
                $this->getExtStorages();
            } elseif (count($arrQueryUri) == 5) {
                // /genapi.php/extstorages/{storage id} endpoint - get specific external storage
                $this->getExtStorage($arrQueryUri[4]);
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "POST") {
            if (count($arrQueryUri) == 7) {
                if ($arrQueryUri[5] == "users") {
                    // /genapi.php/extstorages/{storage id}/users/{user} endpoint - add user to external storage applicable users
                    $this->addUserExtStorage($arrQueryUri[4], $arrQueryUri[6]);
                } elseif ($arrQueryUri[5] == "groups") {
                    // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - add group to external storage applicable groups
                    $this->addGroupExtStorage($arrQueryUri[4], $arrQueryUri[6]);
                }
            } elseif (count($arrQueryUri) == 6) {
                if ($arrQueryUri[4] == "local") {
                    // /genapi.php/extstorages/local/{name} endpoint - create external storage of type local (not configured)
                    $this->createLocalExtStorage($arrQueryUri[5]);
                } elseif ($arrQueryUri[4] == "s3") {
                    // /genapi.php/extstorages/s3/{name} endpoint - create external storage of type s3 (not configured)
                    $this->createS3ExtStorage($arrQueryUri[5]);
                }
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "PUT") {
            if ($arrQueryUri[5] == "config") {
                // /genapi.php/extstorages/{storage id}/config/{key}/{value} endpoint - sets external storages config key/value
                $value = $arrQueryUri[7];
                for ($i = 8; $i < count($arrQueryUri); $i++) {
                    $value .= "/" . $arrQueryUri[$i];
                }

                $this->setConfigExtStorage(
                    $arrQueryUri[4],
                    $arrQueryUri[6],
                    $value
                );
            } elseif ($arrQueryUri[5] == "option") {
                // /genapi.php/extstorages/{storage id}/option/{key}/{value} endpoint - sets external storages option key/value
                $value = $arrQueryUri[7];
                for ($i = 8; $i < count($arrQueryUri); $i++) {
                    $value .= "/" . $arrQueryUri[$i];
                }

                $this->setOptionExtStorage(
                    $arrQueryUri[4],
                    $arrQueryUri[6],
                    $value
                );
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } elseif ($requestMethod == "DELETE") {
            if (count($arrQueryUri) == 5) {
                // /genapi.php/extstorages/{storage id} endpoint - delete external storage
                $this->deleteExtStorage($arrQueryUri[4]);
            } elseif (count($arrQueryUri) == 7) {
                if ($arrQueryUri[5] == "users") {
                    // /genapi.php/extstorages/{storage id}/users/{user} endpoint - remove user from external storage applicable users
                    $this->removeUserExtStorage(
                        $arrQueryUri[4],
                        $arrQueryUri[6]
                    );
                } elseif ($arrQueryUri[5] == "groups") {
                    // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - remove group from external storage applicable groups
                    $this->removeGroupExtStorage(
                        $arrQueryUri[4],
                        $arrQueryUri[6]
                    );
                }
            } else {
                $strErrorDesc =
                    $requestMethod .
                    " " .
                    $this->getUri() .
                    " is not an available Method and Endpoint";

                $this->sendError404Output($strErrorDesc);
            }
        } else {
            $strErrorDesc =
                $requestMethod . " is not an available request Method";

            $this->sendError405Output($strErrorDesc);
        }
    }

    /**
     * returns array of array of occ files_external:list output
     * Current fields are:
     * - Mount ID			- 0
     * - Mount Point		- 1
     * - Storage			- 2
     * - Authentication		- 3
     * - Configuration		- 4
     * - Options			- 5
     * - Applicable Users	- 6
     * - Applicable Groups	- 7
     */
    private function parseExtStorages($extStorages)
    {
        $parsedExtStorages = [];

        // remove blank array items
        unset($extStorages[count($extStorages) - 1]);
        unset($extStorages[2]);
        unset($extStorages[0]);

        // get header rows
        $headers = explode("|", $extStorages[1]);
        array_shift($headers);
        array_pop($headers);

        // clean up $headers
        for ($i = 0; $i < count($headers); $i++) {
            $headers[$i] = trim($headers[$i]);
        }

        // remove header row
        unset($extStorages[1]);

        foreach ($extStorages as $extStorage) {
            // clean up each entry
            $row = explode("|", $extStorage);
            array_shift($row);
            array_pop($row);

            for ($i = 0; $i < count($row); $i++) {
                $row[$i] = trim($row[$i]);
            }

            // set storage id as entry key
            $parsedExtStorages[$row[0]] = [];

            for ($i = 0; $i < count($headers); $i++) {
                if ($i == 6 || $i == 7) {
                    // Either Applicable Users or Applicable Groups, set output as array
                    $parsedExtStorages[$row[0]][$headers[$i]] = explode(
                        ", ",
                        $row[$i]
                    );
                } elseif ($i == 4 || $i == 5) {
                    // Configuration and option set as keyed array
                    if (strlen($row[$i]) == 0) {
                        $parsedExtStorages[$row[0]][$headers[$i]] = [];
                    } else {
                        $rowArr = explode(", ", $row[$i]);
                        foreach ($rowArr as $rowEle) {
                            $keyValue = explode(": ", $rowEle, 2);
                            $parsedExtStorages[$row[0]][$headers[$i]][$keyValue[0]] = $keyValue[1];
                        }
                    }
                } else {
                    $parsedExtStorages[$row[0]][$headers[$i]] = $row[$i];
                }
            }
        }

        return $parsedExtStorages;
    }

    /**
     * "-X GET /extstorages/" Endpoint - list all external storages
     */
    private function getExtStorages()
    {
        $command = self::$occ . " files_external:list";
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode(
                $this->parseExtStorages($arrExtStorage)
            );

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X GET /extstorages/{storage id}" Endpoint - get specified external storage
     */
    private function getExtStorage($storageId)
    {
        $command = self::$occ . " files_external:list";
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode(
                $this->parseExtStorages($arrExtStorage)[$storageId]
            );

            $this->sendOkayOutput($responseData);

            return $responseData;
        }
    }

    /**
     * "-X POST /extstorages/local/{name}" Endpoint - creates external storage of type local (not configured)
     */
    private function createLocalExtStorage($name)
    {
        $command =
            self::$occ .
            " files_external:create " .
            $name .
            " local null::null";
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendCreatedOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /extstorages/s3/{name}" Endpoint - creates external storage of type s3 (not configured)
     */
    private function createS3ExtStorage($name)
    {
        $command =
            self::$occ .
            " files_external:create " .
            $name .
            " amazons3 amazons3::accesskey";
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendCreatedOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X PUT /extstorages/{storage id}/config/{key}/{value}" Endpoint - sets key/value pair in external storage configuration
     */
    private function setConfigExtStorage($storageId, $key, $value)
    {
        $command =
            self::$occ .
            " files_external:config " .
            $storageId .
            " " .
            $key .
            " " .
            $value;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X PUT /extstorages/{storage id}/option/{key}/{value}" Endoint - sets key/value pair in external storage options
     */
    private function setOptionExtStorage($storageId, $key, $value)
    {
        $command =
            self::$occ .
            " files_external:option " .
            $storageId .
            " " .
            $key .
            " " .
            $value;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}" Endpoint - deletes specified external storage
     */
    private function deleteExtStorage($storageId)
    {
        $command = self::$occ . " files_external:delete -y " . $storageId;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /extstorages/{storage id}/users/{user}" Endpoint - add user to external storage applicable users
     */
    private function addUserExtStorage($storageId, $user)
    {
        $command =
            self::$occ .
            " files_external:applicable --add-user " .
            $user .
            " " .
            $storageId;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}/users/{user}" Endpoint - remove user from external storage applicable users
     */
    private function removeUserExtStorage($storageId, $user)
    {
        $command =
            self::$occ .
            " files_external:applicable --remove-user " .
            $user .
            " " .
            $storageId;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X POST /extstorages/{storage id}/groups/{group}" Endpoint - add group to external storage applicable groups
     */
    private function addGroupExtStorage($storageId, $group)
    {
        $command =
            self::$occ .
            " files_external:applicable --add-group " .
            $group .
            " " .
            $storageId;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}/groups/{group}" Endpoint - remove group from external storage applicable groups
     */
    private function removeGroupExtStorage($storageId, $group)
    {
        $command =
            self::$occ .
            " files_external:applicable --remove-group " .
            $group .
            " " .
            $storageId;
        if (exec($command, $arrExtStorage)) {
            $responseData = json_encode($arrExtStorage);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }

    /**
     * "/headers/" Endpoint - prints headers from API call
     */
    private function headers()
    {
        $strErrorDesc = "";
        $strErrorHeader = "";
        $requestMethod = $this->getRequestMethod();
        $arrQueryStringParams = $this->getQueryStringParams();
        $arrQueryUri = $this->getUriSegments();

        $headers = apache_request_headers();

        $this->sendOkayOutput(json_encode($headers));

        return $headers;
    }

    /**
     *"/test/" Endpoint - prints method with query uri
     */
    private function test()
    {
        $strErrorDesc = "";
        $strErrorHeader = "";
        $requestMethod = $this->getRequestMethod();
        $arrQueryStringParams = $this->getQueryStringParams();
        $arrQueryUri = $this->getUriSegments();

        array_unshift($arrQueryUri, $requestMethod);
        $responseData = json_encode($arrQueryUri);

        // send output
        if (!$strErrorDesc) {
            $this->sendOkayOutput($responseData);
        } else {
            $this->sendErrorOutput($strErrorDesc, $strErrorHeader);
        }
    }
}
