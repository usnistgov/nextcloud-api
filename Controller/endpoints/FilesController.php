<?php

namespace NamespaceFunction;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Files resource endpoints
 * 
 * POST
 * - files/file/{path to dir}
 * - files/directory/{directory name}
 * - files/userpermissions/{user}/{permissions}/{directory}
 * - files/sharegroup/{group}/{permissions}/{directory}
 * PUT
 * - files/scan
 * - files/scan/{user}
 * - files/scan/directory/{directory path}
 * - files/file/{path to file}
 * - files/userpermissions/{user}/{permissions}/{directory}
 * GET
 * - files/file/{path to file}
 * - files/directory/{directory name}
 * - files/userpermissions/{directory}
 * DELETE
 * - files/directory/{directory name}
 * - files/userpermissions/{user}/{directory}
 * - files/file/{file path}
 **/
class FilesController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $this->logger->info("Handling Files", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);
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
                    $this->logger->warning("The endpoint doesn't exist for the requested method.", ['requestMethod' => $requestMethod]);
                    return $this->sendError405Output($strErrorDesc);
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in handle method", ['exception' => $e->getMessage()]);
            return $this->sendError400Output($e->getMessage());
        }
    }

    /**
     * "-X DELETE /files/file/{file path}" Endpoint - deletes file
     */
    private function deleteFile($filePath)
    {
        try {
            $response = $this->guzzleClient->request('DELETE', parent::$nextcloud_base . "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($filePath, '/'), [
                'auth' => [parent::$oar_api_usr, parent::$oar_api_pwd]
            ]);

            if ($response->getStatusCode() === 200) {
                $this->logger->info("File deleted successfully", ['filePath' => $filePath]);
                $responseData = (string) $response->getBody();
                return $this->sendOkayOutput($responseData);
            } else {
                $this->logger->warning("Failed to delete the file with status code", ['filePath' => $filePath, 'statusCode' => $response->getStatusCode()]);
                return $this->sendError500Output('Failed to delete the file.');
            }
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to delete file", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            return $this->sendError500Output('Failed to delete the file. ' . $e->getMessage());
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
        try {
            $response = $this->guzzleClient->request('PROPFIND', parent::$nextcloud_base . "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($filePath, '/'), [
                'headers' => [
                    'Depth' => '0'
                ],
                'auth' => [parent::$oar_api_usr, parent::$oar_api_pwd]
            ]);

            $metadata = (string) $response->getBody();

            if (strpos($metadata, '<s:exception>Sabre\\DAV\\Exception\\NotFound</s:exception>') !== false) {
                $this->logger->warning("File not found during getFile", ['filePath' => $filePath]);
                return $this->sendError404Output('File not found.');
            }

            $this->logger->info("File retrieved successfully", ['filePath' => $filePath]);
            $responseData = json_encode([
                'metadata' => $metadata,
            ]);

            return $this->sendOkayOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to retrieve the file metadata", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            return $this->sendError500Output('Failed to retrieve the file metadata. ' . $e->getMessage());
        }
    }

    /**
     * "-X POST /files/file/{ path to destination directory } Endpoint - creates file
     */
    private function postFile($localFilePath, $destinationPath)
    {

        // Check if file was provided and uploaded without errors
        if (!$localFilePath || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->logger->error("File upload error", ['error' => $_FILES['file']['error'] ?? 'No file uploaded']);
            $error = "File upload error!";
            return $this->sendError500Output($error);
        }

        $filenameWithExtension = basename($_FILES['file']['name']);
        $fullDestinationPath = rtrim(parent::$nextcloud_base . "/remote.php/dav/files/oar_api/" . ($destinationPath ? trim($destinationPath, '/') . '/' : ''), '/') . '/' . $filenameWithExtension;
        try {
            $response = $this->guzzleClient->request('PUT', $fullDestinationPath, [
                'body' => fopen($localFilePath, 'r'),
                'headers' => [
                    'Content-Type' => 'application/octet-stream',
                ],
            ]);

            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $this->logger->info("File uploaded successfully", ['path' => $fullDestinationPath]);
                $responseData = (string) $response->getBody();
                return $this->sendCreatedOutput($responseData);
            } else {
                $this->logger->warning("File upload failed with status code", ['path' => $fullDestinationPath, 'statusCode' => $response->getStatusCode()]);
                return $this->sendError500Output("File upload failed!");
            }
        } catch (GuzzleException $e) {
            $this->logger->error("File upload failed", ['path' => $fullDestinationPath, 'error' => $e->getMessage()]);
            return $this->sendError500Output("File upload failed! " . $e->getMessage());
        }
    }

    /**
     * "PUT /files/file/{path to file}" Endpoint - updates existing file
     */
    private function putFile($localFilePath, $destinationPath)
    {
        if (!$localFilePath || !file_exists($localFilePath)) {
            $this->logger->error("Local file path invalid or file does not exist", ['localFilePath' => $localFilePath]);
            return $this->sendError500Output("Local file path invalid or file does not exist!");
        }

        try {
            $response = $this->guzzleClient->request('PUT', "/remote.php/dav/files/oar_api/" . ltrim($destinationPath, '/'), [
                'body' => fopen($localFilePath, 'r'),
                'headers' => [
                    'Content-Type' => 'application/octet-stream',
                ],
            ]);

            $this->logger->info("File updated successfully", ['destinationPath' => $destinationPath]);
            $responseData = $response->getBody()->getContents();
            return $this->sendOkayOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("File update failed", ['destinationPath' => $destinationPath, 'error' => $e->getMessage()]);
            return $this->sendError500Output("File update failed! Error: " . $e->getMessage());
        }
    }

    /**
     * "-X POST /files/directory/{directory name}" Endpoint - creates directory
     */
    private function postDirectory($dir)
    {
        $this->guzzleClient = $this->getGuzzleClient();
        try {
            $response = $this->guzzleClient->request('MKCOL', "/remote.php/dav/files/oar_api/" . $dir);
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $this->logger->info("Directory created successfully", ['dir' => $dir]);
                return $this->sendCreatedOutput("Directory created successfully.");
            } else {
                $this->logger->warning("Directory creation failed with status code", ['path' => $dir, 'statusCode' => $response->getStatusCode()]);
                return $this->sendError500Output("Directory creation failed!");
            }
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to create directory", ['dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to create directory. " . $e->getMessage());
        }
    }


    /**
     * "-X GET /files/directory/{directory name}" Endpoint - get directory info
     */
    private function getDirectory($dir)
    {
        try {
            $response = $this->guzzleClient->request('PROPFIND', "/remote.php/dav/files/oar_api/" . $dir, [
                'headers' => ['Depth' => '0'],
            ]);

            $responseData = $response->getBody()->getContents();
            $this->logger->info("Directory info retrieved successfully", ['dir' => $dir]);
            return $this->sendOkayOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to get directory info", ['dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to get directory info. " . $e->getMessage());
        }
    }

    /**
     * "-X DELETE /files/directory/{directory name}" Endpoint - deletes directory
     */
    private function deleteDirectory($dir)
    {
        try {
            $response = $this->guzzleClient->request('DELETE', "/remote.php/dav/files/oar_api/" . $dir);
            $this->logger->info("Directory deleted successfully", ['dir' => $dir]);
            return $this->sendOkayOutput($response);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to delete directory", ['path' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to delete directory. " . $e->getMessage());
        }
    }

    /**
     * "-X PUT /files/scan" Endpoint - scans all users file systems
     */
    private function scanAllFiles()
    {
        // Define the command to execute
        $command = parent::$occ . " files:scan --all";
        $scanResults = [];
        $this->logger->info("Executing scanAllFiles command", ['command' => $command]);

        exec($command, $scanResults, $returnVar);

        // Check if the command was executed successfully
        if ($returnVar === 0) {
            $this->logger->info("All files scanned successfully");
            $responseData = json_encode($scanResults);
            return $this->sendOkayOutput($responseData);
        } else {
            $this->logger->error("scanAllFiles command failed", ['command' => $command, 'returnVar' => $returnVar]);
            error_log("Error executing scanAllFiles: $command");
            return $this->sendError500Output("Failed to scan files.");
        }
    }

    /**
     * "-X PUT /files/scan/{user}" Endpoint - scan user file system
     */
    private function scanUserFiles($user)
    {
        // Define the command to execute
        $command = parent::$occ . " files:scan " . $user;
        $scanResults = [];
        $this->logger->info("Executing scanUserFiles command", ['command' => $command, 'user' => $user]);

        exec($command, $scanResults, $returnVar);

        // Check if the command was executed successfully
        if ($returnVar === 0) {
            $this->logger->info("Files scanned successfully for user", ['user' => $user]);
            $responseData = json_encode($scanResults);
            return $this->sendOkayOutput($responseData);
        } else {
            $this->logger->error("scanUserFiles command failed", ['command' => $command, 'user' => $user, 'returnVar' => $returnVar]);
            return $this->sendError500Output("Failed to scan files.");
        }
    }


    /**
     * "-X PUT /files/scan/directory/{directory path}" Endpoint - scan directory file system
     */
    private function scanDirectoryFiles($dir)
    {
        try {
            $response = $this->guzzleClient->request('PROPFIND', "/remote.php/dav/files/" . parent::$oar_api_usr . "/" . ltrim($dir, '/'), [
                'headers' => [
                    'Depth' => '1',
                    'Content-Type' => 'application/xml',
                ],
                'body' => '<?xml version="1.0"?>
                    <d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
                        <d:allprop/>
                    </d:propfind>'
            ]);

            $this->logger->info("Directory files scanned successfully", ['dir' => $dir]);
            $responseData = $response->getBody()->getContents();
            return $this->sendOkayOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to scan directory files", ['dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to scan directory. " . $e->getMessage());
        }
    }



    /**
     * "-X POST /files/userpermissions/{user}/{permissions}/{directory}" Endpoint - share file/folder with user with permissions
     */
    private function postUserPermissions($user, $perm, $dir)
    {
        try {
            $response = $this->guzzleClient->request('POST', "/ocs/v2.php/apps/files_sharing/api/v1/shares", [
                'headers' => [
                    'OCS-APIRequest' => 'true',
                ],
                'form_params' => [
                    'shareType' => '0',
                    'path' => $dir,
                    'shareWith' => $user,
                    'permissions' => $perm,
                ],
            ]);

            $this->logger->info("User permissions posted successfully", ['user' => $user, 'permissions' => $perm, 'dir' => $dir]);
            $responseData = $response->getBody()->getContents();
            return $this->sendCreatedOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to post user permissions", ['user' => $user, 'permissions' => $perm, 'dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to share file/folder with user. " . $e->getMessage());
        }
    }


    /**
     * "-X GET /files/userpermissions/{directory}" Endpoint - get users with permissions to file/folder
     */
    private function getUserPermissions($dir)
    {
        try {
            $response = $this->guzzleClient->request('GET', "/ocs/v2.php/apps/files_sharing/api/v1/shares", [
                'headers' => [
                    'OCS-APIRequest' => 'true',
                ],
                'query' => [
                    'path' => '/' . $dir,
                    'reshares' => 'true',
                ],
            ]);

            $this->logger->info("User permissions retrieved successfully", ['dir' => $dir]);
            $responseData = $response->getBody()->getContents();
            return $this->sendOkayOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to get user permissions", ['dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError404Output("Failed to get user permissions. " . $e->getMessage());
        }
    }


    /**
     * "-X PUT /files/userpermissions/{user}/{permissions}/{directory}" Endpoint - Modify user permissions to directory
     */
    private function putUserPermissions($user, $perm, $dir)
    {
        // Delete existing permissions
        $deleteSuccessful = $this->deleteUserPermissions($user, $dir);

        // If the deletion was successful, add new permissions
        if ($deleteSuccessful) {
            try {
                // Try to add new permissions
                $postResult = $this->postUserPermissions($user, $perm, $dir);

                if ($postResult) {
                    // Success path: permissions were successfully modified
                    $this->logger->info("User permissions modified successfully", ['user' => $user, 'permissions' => $perm, 'dir' => $dir]);
                    return true;
                } else {
                    // Failed to add new permissions after deletion
                    $this->logger->error("Failed to modify user permissions after deleting old ones", ['user' => $user, 'dir' => $dir]);
                    return $this->sendError500Output("Failed to add new permissions after deleting the old ones.");
                }
            } catch (GuzzleException $e) {
                $this->logger->error("Exception occurred while modifying user permissions", ['user' => $user, 'dir' => $dir, 'error' => $e->getMessage()]);
                return $this->sendError500Output("Failed to modify user permissions: " . $e->getMessage());
            }
        } else {
            // Deletion failed, report back
            $this->logger->error("Failed to delete existing permissions, cannot modify", ['user' => $user, 'dir' => $dir]);
            return $this->sendError500Output("Failed to delete existing permissions.");
        }
    }


    /**
     * "-X DELETE /files/userpermissions/{user}/{directory}" Endpoint - Delete user permissions to directory
     */
    private function deleteUserPermissions($user, $dir)
    {
        // Step 1: Retrieve the list of shares to find the relevant share ID
        try {
            $response = $this->guzzleClient->request('GET', "/ocs/v2.php/apps/files_sharing/api/v1/shares", [
                'headers' => [
                    'OCS-APIRequest' => 'true',
                ],
                'query' => [
                    'path' => '/' . $dir,
                    'reshares' => 'true',
                ],
            ]);

            $shares = json_decode($response->getBody()->getContents(), true);

            // Assuming $shares contains an array of share information
            foreach ($shares['ocs']['data'] as $share) {
                if ($share['share_with'] === $user) {
                    // Step 2: Delete the share using its ID
                    $shareId = $share['id'];
                    $deleteResponse = $this->guzzleClient->request('DELETE', "/ocs/v2.php/apps/files_sharing/api/v1/shares/$shareId", [
                        'headers' => [
                            'OCS-APIRequest' => 'true',
                        ],
                    ]);

                    if ($deleteResponse->getStatusCode() == 200) {
                        $this->logger->info("User permissions deleted successfully", ['user' => $user, 'dir' => $dir, 'shareId' => $shareId]);
                        return $this->sendOkayOutput(json_encode(['success' => true, 'message' => "Share ID $shareId deleted."]));
                    } else {
                        $this->logger->error("Failed to delete user permissions", ['user' => $user, 'dir' => $dir, 'shareId' => $shareId]);
                        return $this->sendError500Output("Failed to delete share ID $shareId.");
                    }
                }
            }

            // If no matching share was found
            $this->logger->warning("No matching share found to delete user permissions", ['user' => $user, 'dir' => $dir]);
            return $this->sendError404Output("No matching share found for user $user in directory $dir.");
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to delete user permissions due to an exception", ['user' => $user, 'dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to retrieve or delete user permissions. " . $e->getMessage());
        }
    }

    /**
     * "-X POST /files/sharegroup/{group}/{permissions}/{directory}" Endpoing - share file/folder with group with permissions
     */
    private function shareGroup($group, $perm, $dir)
    {
        try {
            $response = $this->guzzleClient->request('POST', "/ocs/v2.php/apps/files_sharing/api/v1/shares", [
                'headers' => [
                    'OCS-APIRequest' => 'true',
                ],
                'form_params' => [
                    'shareType' => '1',
                    'path' => $dir,
                    'shareWith' => $group,
                    'permissions' => $perm,
                ],
            ]);

            $this->logger->info("Directory shared with group successfully", ['group' => $group, 'permissions' => $perm, 'dir' => $dir]);
            $responseData = $response->getBody()->getContents();
            return $this->sendCreatedOutput($responseData);
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to share directory with group", ['group' => $group, 'permissions' => $perm, 'dir' => $dir, 'error' => $e->getMessage()]);
            return $this->sendError500Output("Failed to share file/folder with group. " . $e->getMessage());
        }
    }
}
