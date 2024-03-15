<?php

namespace NamespaceFunction;

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
    class GroupsController extends \NamespaceBase\BaseController {
        public function handle()
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
        $command = parent::$occ . " group:list";
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
        $command = parent::$occ . " group:list";
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
        $command = parent::$occ . " group:add " . $group;
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
        $command = parent::$occ . " group:adduser " . $group . " " . $member;
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
        $command = parent::$occ . " group:delete " . $group;
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
        $command = parent::$occ . " group:removeuser " . $group . " " . $member;
        if (exec($command, $arrGroup)) {
            $responseData = json_encode($arrGroup);

            $this->sendOkayOutput($responseData);
            return $responseData;
        }
    }
}