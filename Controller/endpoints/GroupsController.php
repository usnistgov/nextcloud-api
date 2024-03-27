<?php

namespace NamespaceFunction;

/**
 * GROUPS resource endpoints
 * 
 * GET
 * - groups
 * - groups/{group name}
 * POST
 * - groups/{group name}
 * - groups/{group name}/{member}
 * DELETE
 * - groups/{group name}
 * - groups/{group name}/{member}
 **/
class GroupsController extends \NamespaceBase\BaseController
{
    public function handle()
    {

        $this->logger->info("Handling Groups", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);

        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        try {
            switch ($requestMethod) {
                case "GET":
                    if (count($arrQueryUri) == 4) {
                        // "/genapi.php/groups" Endpoint - returns list of all groups
                        $this->getGroups();
                    } elseif (count($arrQueryUri) == 5) {
                        // "/genapi.php/groups/{group name}" Endpoint - returns list of members of specific group
                        $this->getGroupMembers($arrQueryUri[4]);
                    }
                    break;
                case "POST":
                    if (count($arrQueryUri) == 5) {
                        // "/genapi.php/groups/{group name}" Endpoing - creates group
                        $this->addGroup($arrQueryUri[4]);
                    } elseif (count($arrQueryUri) == 6) {
                        // "/genapi.php/groups/{group name}/{member}" Endpoint - adds member to group
                        $this->addGroupMember($arrQueryUri[4], $arrQueryUri[5]);
                    }
                    break;
                case "DELETE":
                    if (count($arrQueryUri) == 5) {
                        // "/genapi.php/groups/{group name}" Endpoint - deletes group
                        $this->deleteGroup($arrQueryUri[4]);
                    } elseif (count($arrQueryUri) == 6) {
                        // "/genapi.php/groups/{group name}/{member}" Endpoint - removes member from group
                        $this->removeGroupMember($arrQueryUri[4], $arrQueryUri[5]);
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
     * "-X GET /groups" Endpoint - Get list of all groups
     */
    private function getGroups()
    {
        $command = parent::$occ . " group:list";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Groups listed successfully.");
            return $this->sendOkayOutput(json_encode(['groups' => $output]));
        } else {
            $this->logger->error("Error listing groups.");
            return $this->sendError500Output("Failed to list groups.");
        }
    }

    /**
     * "-X GET /groups/{group name}" Endpoint - Get list of all members of given group
     */
    private function getGroupMembers($groupName)
    {
        $command = parent::$occ . " group:list --output=json";
        exec($command, $output, $returnVar);
        $groups = json_decode(implode("\n", $output), true);
        if (isset($groups[$groupName])) {
            $this->logger->info("Group members listed successfully.", ['group' => $groupName]);
            return $this->sendOkayOutput(json_encode(['members' => $groups[$groupName]]));
        } else {
            $this->logger->error("Error listing group members.", ['group' => $groupName]);
            return $this->sendError404Output("Group not found.");
        }
    }

    /**
     * "-X POST /groups/{group name}" Endpoint - Create group
     */
    private function addGroup($groupName)
    {
        $command = parent::$occ . " group:add '" . $groupName . "'";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Group added successfully.", ['group' => $groupName]);
            return $this->sendCreatedOutput("Group '{$groupName}' created.");
        } else {
            $this->logger->error("Error adding group.", ['group' => $groupName]);
            return $this->sendError500Output("Failed to add group '{$groupName}'.");
        }
    }
    /**
     * "-X POST /groups/{group name}/{member}" Endpoint - Add member to group
     */
    private function addGroupMember($groupName, $memberName)
    {
        $command = parent::$occ . " group:adduser '" . $memberName . "' '" . $groupName . "'";

        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Member added to group successfully.", ['group' => $groupName, 'member' => $memberName]);
            return $this->sendCreatedOutput("Member '{$memberName}' added to group '{$groupName}'.");
        } else {
            $this->logger->error("Error adding member to group.", ['group' => $groupName, 'member' => $memberName]);
            return $this->sendError500Output("Failed to add member '{$memberName}' to group '{$groupName}'.");
        }
    }

    /**
     * -X DELETE /groups/{group name}" Endpoint - Delete group
     */
    private function deleteGroup($groupName)
    {
        $command = parent::$occ . " group:delete '" . $groupName . "'";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Group deleted successfully.", ['group' => $groupName]);
            return $this->sendOkayOutput("Group '{$groupName}' deleted.");
        } else {
            $this->logger->error("Error deleting group.", ['group' => $groupName]);
            return $this->sendError500Output("Failed to delete group '{$groupName}'.");
        }
    }

    /**
     * "-X DELETE /groups/{group name}/{member}" Endpoint - Remove member from group
     */
    private function removeGroupMember($groupName, $memberName)
    {
        $command = parent::$occ . " group:removeuser '" . $memberName . "' '" . $groupName . "'";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->info("Member removed from group successfully.", ['group' => $groupName, 'member' => $memberName]);
            return $this->sendOkayOutput("Member '{$memberName}' removed from group '{$groupName}'.");
        } else {
            $this->logger->error("Error removing member from group.", ['group' => $groupName, 'member' => $memberName]);
            return $this->sendError500Output("Failed to remove member '{$memberName}' from group '{$groupName}'.");
        }
    }
}
