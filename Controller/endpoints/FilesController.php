<?php

namespace NamespaceFunction;

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
 **/
class FilesController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        try {
            $strErrorDesc = "";

            $requestMethod = $this->getRequestMethod();
            $arrQueryUri = $this->getUriSegments();

            switch ($requestMethod) {
                case "POST":
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
                    break;
                case "PUT":
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
                    break;
                case "GET":
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
                    break;
                case "DELETE":
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
                    break;
                default:
                    $strErrorDesc = $requestMethod . " is not supported for files endpoint.";
                    $this->sendError405Output($strErrorDesc);
                    break;
            }
        } catch (\Exception $e) {
            $this->sendError400Output($e->getMessage());
        }
    }

    /**
     * "-X DELETE /files/file/{file path}" Endpoint - deletes file
     */
    private function deleteFile($filePath)
    {
        $command = "curl -s -X DELETE -u " .
            parent::$oar_api_login .
            " \"" . parent::$nextcloud_base . "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($filePath, '/') . "\"";

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
        $metadataCommand = "curl -s -X PROPFIND -u " .
            parent::$oar_api_login .
            " -H \"Depth: 0\" \"" .
            parent::$nextcloud_base .
            "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($filePath, '/') . "\"";

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

        $command = "curl -X PUT -u " . escapeshellarg(parent::$oar_api_login) .
            " --data-binary @" . escapeshellarg($localFilePath) .
            " " . parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" . $fullDestinationPath;

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

        $credentials = parent::$oar_api_login;

        $url = parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" . ltrim($fullDestinationPath, '/');

        $command = "curl -X PUT -u " . escapeshellarg($credentials) .
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
            "curl -X MKCOL -u " .
            parent::$oar_api_login .
            " " . parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" .
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
            "curl -X PROPFIND -u " .
            parent::$oar_api_login .
            " -H \"Depth: 0\" " . parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" .
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
            "curl -X DELETE -u " .
            parent::$oar_api_login .
            " " . parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" .
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
        $command = parent::$occ . " files:scan --all";
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
        $command = parent::$occ . " files:scan " . $user;

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
            "curl -X PROPFIND -H \"Depth: 1\" -H \"Content-Type: application/xml\" -u " .
            parent::$oar_api_login .
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
            "\"" . parent::$nextcloud_base . "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($dir, '/') . "\"";

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
            "curl -X POST -H \"ocs-apirequest:true\" -u " .
            parent::$oar_api_login .
            " \"" . parent::$nextcloud_base . "/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=0" .
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
            "curl -X GET -H \"OCS-APIRequest: true\" -u " .
            parent::$oar_api_login .
            " '" . parent::$nextcloud_base . "/ocs/v2.php/apps/files_sharing/api/v1/shares?path=/" .
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
            "curl -X GET -H \"OCS-APIRequest: true\" -u " .
            parent::$oar_api_login .
            " '" . parent::$nextcloud_base . "/ocs/v2.php/apps/files_sharing/api/v1/shares?path=/" .
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
                            "curl -X DELETE -H \"OCS-APIRequest: true\" -u " .
                            parent::$oar_api_login .
                            " '" . parent::$nextcloud_base . "/ocs/v2.php/apps/files_sharing/api/v1/shares/" .
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
            "curl -X POST -H \"ocs-apirequest:true\" -u " .
            parent::$oar_api_login .
            " \"" . parent::$nextcloud_base . "/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=1" .
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
}
