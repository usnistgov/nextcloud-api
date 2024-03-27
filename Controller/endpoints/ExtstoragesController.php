<?php

namespace NamespaceFunction;

/**
 * EXTSTORAGES resource endpoints
 * 
 * GET
 * - extstorages
 * - extstorages/{storage id}
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
 * 
 **/
class ExtStoragesController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $this->logger->info("Handling External Storages", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);
        try {
            $requestMethod = $this->getRequestMethod();
            $arrQueryUri = $this->getUriSegments();

            switch ($requestMethod) {
                case "GET":
                    if (count($arrQueryUri) == 4) {
                        // /genapi.php/extstorages endpoint - list all external storages
                        $this->getExtStorages();
                    } elseif (count($arrQueryUri) == 5) {
                        // /genapi.php/extstorages/{storage id} endpoint - get specific external storage

                        $this->getExtStorage($arrQueryUri[4]);
                    }
                    break;

                case "POST":
                    if (count($arrQueryUri) == 7 && in_array($arrQueryUri[5], ['users', 'groups'])) {
                        // /genapi.php/extstorages/{storage id}/users/{user} endpoint - add user to external storage applicable users
                        // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - add group to external storage applicable groups
                        $arrQueryUri[5] == "users" ? $this->addUserExtStorage($arrQueryUri[4], $arrQueryUri[6]) : $this->addGroupExtStorage($arrQueryUri[4], $arrQueryUri[6]);
                    } elseif (count($arrQueryUri) == 6 && in_array($arrQueryUri[4], ['local', 's3'])) {
                        // /genapi.php/extstorages/local/{name} endpoint - create external storage of type local (not configured)
                        // /genapi.php/extstorages/s3/{name} endpoint - create external storage of type s3 (not configured)

                        $arrQueryUri[4] == "local" ? $this->createLocalExtStorage($arrQueryUri[5]) : $this->createS3ExtStorage($arrQueryUri[5]);
                    }
                    break;

                case "PUT":
                    if (count($arrQueryUri) == 8 && in_array($arrQueryUri[5], ['config', 'option'])) {
                        // /genapi.php/extstorages/{storage id}/config/{key}/{value} endpoint - sets external storages config key/value
                        // /genapi.php/extstorages/{storage id}/option/{key}/{value} endpoint - sets external storages option key/value

                        $value = implode("/", array_slice($arrQueryUri, 7));
                        $arrQueryUri[5] == "config" ? $this->setConfigExtStorage($arrQueryUri[4], $arrQueryUri[6], $value) : $this->setOptionExtStorage($arrQueryUri[4], $arrQueryUri[6], $value);
                    }
                    break;

                case "DELETE":
                    if (count($arrQueryUri) == 5) {
                        // /genapi.php/extstorages/{storage id} endpoint - delete external storage

                        $this->deleteExtStorage($arrQueryUri[4]);
                    } elseif (count($arrQueryUri) == 7 && in_array($arrQueryUri[5], ['users', 'groups'])) {
                        // /genapi.php/extstorages/{storage id}/users/{user} endpoint - remove user from external storage applicable users
                        // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - remove group from external storage applicable groups

                        $arrQueryUri[5] == "users" ? $this->removeUserExtStorage($arrQueryUri[4], $arrQueryUri[6]) : $this->removeGroupExtStorage($arrQueryUri[4], $arrQueryUri[6]);
                    }
                    break;

                default:
                    $strErrorDesc = $requestMethod . " is not an available request Method";
                    return $this->sendError405Output($strErrorDesc);
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in handle method", ['exception' => $e->getMessage()]);
            return $this->sendError400Output($e->getMessage());
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
        $command = parent::$occ . " files_external:list";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully retrieved external storages list");
            return $this->sendOkayOutput(json_encode($this->parseExtStorages($output)));
        } else {
            $this->logger->error("Failed to retrieve external storages list");
            return $this->sendError500Output("Failed to retrieve external storages list.");
        }
    }

    /**
     * "-X GET /extstorages/{storage id}" Endpoint - get specified external storage
     */
    private function getExtStorage($storageId)
    {
        $command = parent::$occ . " files_external:list --output=json";

        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $storages = json_decode(implode("\n", $output), true);
            if (isset($storages[$storageId])) {
                $this->logger->info("Successfully retrieved external storage", ['storageId' => $storageId]);
                return $this->sendOkayOutput(json_encode($storages[$storageId]));
            } else {
                $this->logger->warning("External storage not found", ['storageId' => $storageId]);
                return $this->sendError404Output("External storage not found.");
            }
        } else {
            $this->logger->error("Failed to retrieve external storage", ['storageId' => $storageId]);
            return $this->sendError500Output("Failed to retrieve external storage.");
        }
    }

    /**
     * "-X POST /extstorages/local/{name}" Endpoint - creates external storage of type local (not configured)
     */
    private function createLocalExtStorage($name)
    {
        $command = parent::$occ . " files_external:create \"$name\" local null::null --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully created local external storage", ['name' => $name]);
            return $this->sendCreatedOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to create local external storage", ['name' => $name]);
            return $this->sendError500Output("Failed to create local external storage.");
        }
    }

    /**
     * "-X POST /extstorages/s3/{name}" Endpoint - creates external storage of type s3 (not configured)
     */
    private function createS3ExtStorage($name)
    {
        $command = parent::$occ . " files_external:create \"$name\" amazons3 amazons3::accesskey --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully created S3 external storage", ['name' => $name]);
            return $this->sendCreatedOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to create S3 external storage", ['name' => $name]);
            return $this->sendError500Output("Failed to create S3 external storage.");
        }
    }


    /**
     * "-X PUT /extstorages/{storage id}/config/{key}/{value}" Endpoint - sets key/value pair in external storage configuration
     */
    private function setConfigExtStorage($storageId, $key, $value)
    {
        $command = parent::$occ . " files_external:config $storageId $key \"$value\" --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully set external storage configuration", ['storageId' => $storageId, 'key' => $key, 'value' => $value]);
            return $this->sendOkayOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to set external storage configuration", ['storageId' => $storageId, 'key' => $key, 'value' => $value]);
            return $this->sendError500Output("Failed to set external storage configuration.");
        }
    }

    /**
     * "-X PUT /extstorages/{storage id}/option/{key}/{value}" Endoint - sets key/value pair in external storage options
     */
    private function setOptionExtStorage($storageId, $key, $value)
    {
        $command = parent::$occ . " files_external:option $storageId $key \"$value\" --output=json";

        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully set external storage option", ['storageId' => $storageId, 'key' => $key, 'value' => $value]);
            return $this->sendOkayOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to set external storage option", ['storageId' => $storageId, 'key' => $key, 'value' => $value]);
            return $this->sendError500Output("Failed to set external storage option.");
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}" Endpoint - deletes specified external storage
     */
    private function deleteExtStorage($storageId)
    {
        $command = parent::$occ . " files_external:delete $storageId --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully deleted external storage", ['storageId' => $storageId]);
            return $this->sendOkayOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to delete external storage", ['storageId' => $storageId]);
            return $this->sendError500Output("Failed to delete external storage.");
        }
    }

    /**
     * "-X POST /extstorages/{storage id}/users/{user}" Endpoint - add user to external storage applicable users
     */
    private function addUserExtStorage($storageId, $user)
    {
        $command = parent::$occ . " files_external:applicable --add-user $user $storageId --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully added user to external storage", ['storageId' => $storageId, 'user' => $user]);
            return $this->sendCreatedOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to add user to external storage", ['storageId' => $storageId, 'user' => $user]);
            return $this->sendError500Output("Failed to add user to external storage.");
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}/users/{user}" Endpoint - remove user from external storage applicable users
     */
    private function removeUserExtStorage($storageId, $user)
    {
        $command = parent::$occ . " files_external:applicable --remove-user $user $storageId --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully removed user from external storage", ['storageId' => $storageId, 'user' => $user]);
            return $this->sendOkayOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to remove user from external storage", ['storageId' => $storageId, 'user' => $user]);
            return $this->sendError500Output("Failed to remove user from external storage.");
        }
    }

    /**
     * "-X POST /extstorages/{storage id}/groups/{group}" Endpoint - add group to external storage applicable groups
     */
    private function addGroupExtStorage($storageId, $group)
    {
        $command = parent::$occ . " files_external:applicable --add-group $group $storageId --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully added group to external storage", ['storageId' => $storageId, 'group' => $group]);
            $this->sendCreatedOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to add group to external storage", ['storageId' => $storageId, 'group' => $group]);
            $this->sendError500Output("Failed to add group to external storage.");
        }
    }

    /**
     * "-X DELETE /extstorages/{storage id}/groups/{group}" Endpoint - remove group from external storage applicable groups
     */
    private function removeGroupExtStorage($storageId, $group)
    {
        $command = parent::$occ . " files_external:applicable --remove-group $group $storageId --output=json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Successfully removed group from external storage", ['storageId' => $storageId, 'group' => $group]);
            return $this->sendOkayOutput(json_encode($output));
        } else {
            $this->logger->error("Failed to remove group from external storage", ['storageId' => $storageId, 'group' => $group]);
            return $this->sendError500Output("Failed to remove group from external storage.");
        }
    }
}
