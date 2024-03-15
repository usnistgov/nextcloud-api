<?php

namespace NamespaceFunction;


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
    class ExtStoragesController extends \NamespaceBase\BaseController {
        public function handle()
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
        $command = parent::$occ . " files_external:list";
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
        $command = parent::$occ . " files_external:list";
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
            parent::$occ .
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
            parent::$occ .
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
            parent::$occ .
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
            parent::$occ .
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
        $command = parent::$occ . " files_external:delete -y " . $storageId;
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
            parent::$occ .
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
            parent::$occ .
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
            parent::$occ .
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
            parent::$occ .
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
}