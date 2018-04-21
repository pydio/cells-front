<?php
/*
 * Copyright 2007-2017 Abstrium <contact (at) pydio.com>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <https://pydio.com/>.
 */
namespace Pydio\Core\Services;

use Pydio\Conf\Core\Role;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Model\RepositoryRoot;
use Pydio\Core\Model\UserInterface;
use Pydio\Core\PluginFramework\PluginsService;
use Swagger\Client\ApiException;
use Swagger\Client\Model\IdmACL;
use Swagger\Client\Model\IdmACLAction;
use Swagger\Client\Model\IdmACLSingleQuery;
use Swagger\Client\Model\IdmRoleSingleQuery;
use Swagger\Client\Model\ResourcePolicyQueryQueryType;
use Swagger\Client\Model\RestResourcePolicyQuery;
use Swagger\Client\Model\RestSearchACLRequest;
use Swagger\Client\Model\RestSearchRoleRequest;
use Swagger\Client\Model\ServiceOperationType;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class RolesService
 * @package Pydio\Core\Services
 */
class RolesService
{

    const RootGroup = "ROOT_GROUP";

    /**
     * @todo MOVE TO BACKEND
     * Update a user with admin rights and return it
    * @param UserInterface $adminUser
     * @return UserInterface
    */
    public static function updateAdminRights($adminUser)
    {
        if ($adminUser->getPersonalRole()->getAcl('settings') != "read,write") {
            $adminUser->getPersonalRole()->setAcl('settings', 'read,write');
            self::updateRole($adminUser->getPersonalRole(), true);
            $adminUser->recomputeMergedRole();
        }
        return $adminUser;
    }

    /**
     * Update a user object with the default repositories rights
     * @todo MOVE TO BACKEND
     * @param UserInterface $userObject
     */
    public static function updateDefaultRights(&$userObject)
    {
        if (!$userObject->hasSharedProfile()) {
            $rolesList = self::getRolesList(array(), true);
            foreach ($rolesList as $roleId => $roleObject) {
                if (!$userObject->canSee($roleObject)) continue;
                if ($userObject->getProfile() == "shared" && $roleObject->autoAppliesTo("shared")) {
                    $userObject->addRole($roleObject);
                } else if ($roleObject->autoAppliesTo("standard")) {
                    $userObject->addRole($roleObject);
                }
            }
        }
    }

    /**
     * @todo MOVE TO BACKEND
     * @static
     * @param UserInterface $userObject
     */
    public static function updateAutoApplyRole(&$userObject)
    {
        return;
        $roles = self::getRolesList(array(), true);
        foreach ($roles as $roleObject) {
            if (!$userObject->canSee($roleObject)) continue;
            if ($roleObject->autoAppliesTo($userObject->getProfile()) || $roleObject->autoAppliesTo("all")) {
                $userObject->addRole($roleObject);
            }
        }
    }

    /**
     * @param UserInterface $userObject
     */
    public static function updateAuthProvidedData(&$userObject)
    {
        ConfService::getAuthDriverImpl()->updateUserObject($userObject);
    }

    /**
     * Retrieve the current users who have either read or write access to a repository
     * @param $repositoryId
     * @param string $rolePrefix
     * @param bool $splitByType
     * @return array
     */
    public static function getRolesForRepository($repositoryId, $rolePrefix = '', $splitByType = false)
    {
        $aclClient = MicroApi::GetAclServiceApi();
        $request = new RestSearchACLRequest();
        $repoQuery = new IdmACLSingleQuery();
        $repoQuery->setWorkspaceIDs([$repositoryId]);
        $read = new IdmACLAction();
        $read->setName("read");
        $write = new IdmACLAction();
        $write->setName("write");
        $repoQuery->setActions([$read, $write]);
        if($rolePrefix !== "") {
            $repoQuery->setRoleIDs([$rolePrefix . "*"]);
        }
        $request->setQueries([$repoQuery]);

        $result = [];
        $response = $aclClient->searchAcls($request);
        if($response->getAcLs() != null) {
            $acls = $response->getAcLs();
            foreach ($acls as $acl) {
                $rId = $acl->getRoleId();
                if (!empty($rId) && !in_array($rId, $result)){
                    $result[] = $acl->getRoleId();
                }
            }
        }


        $rolesClient = MicroApi::GetRoleServiceApi();
        $request = new RestSearchRoleRequest();
        $query = new IdmRoleSingleQuery();
        $query->setUuid($result);
        $request->setQueries([$query]);
        $response = $rolesClient->searchRoles($request);
        if($splitByType){
            $newResult = ["role_user" => [], "role_group" => [], "role_role" => []];
        } else {
            $newResult = [];
        }
        if($response->getRoles() === null){
            return $newResult;
        }
        foreach($response->getRoles() as $role){
            $pRole = new Role($role->getUuid(), $role, true);
            if($splitByType){
                if ($role->getGroupRole()) {
                    $newResult["role_group"][] = $role;
                } else if ($role->getUserRole()){
                    $newResult["role_user"][] = $role;
                } else {
                    $newResult["role_role"][] = $role;
                }
            } else {
                $newResult[$role->getUuid()] = $pRole;
            }
        }
        if($splitByType){
            return $newResult;
        }
        self::loadAcls($newResult);
        return $newResult;

    }

    /**
     * @param $oneRoleId string
     * @return Role[]|Role
     */
    public static function getUserTeamRoles($oneRoleId = null){

        $api = MicroApi::GetRoleServiceApi();
        $req = new RestSearchRoleRequest();
        $sQ = new IdmRoleSingleQuery();
        $sQ->setIsTeam(true);
        if($oneRoleId !== null) {
            $sQ->setUuid([$oneRoleId]);
        }
        $resourceQuery = new RestResourcePolicyQuery();
        $resourceQuery->setType(ResourcePolicyQueryQueryType::CONTEXT);
        $req->setQueries([$sQ]);
        $req->setResourcePolicyQuery($resourceQuery);
        $rolesList = $api->searchRoles($req);
        $result = [];
        if ($rolesList->getRoles() == null) {
            if(!empty($oneRoleId)) return null;
            else return $result;
        }
        foreach ($rolesList->getRoles() as $idmRole) {
            if(!empty($oneRoleId)) {
                return new Role($oneRoleId, $idmRole);
            }
            $roleId = $idmRole->getUuid();
            $result[$roleId] = new Role($roleId, $idmRole, true);
        }
        if(!empty($oneRoleId)){
            return null;
        }
        return $result;

    }

    /**
     * @param $roleId
     * @param $userId
     * @return Role
     */
    public static function getOrCreateTeamRole(ContextInterface $ctx, $roleId){

        $existing = self::getUserTeamRoles($roleId);
        if (!empty($existing)) {
            return $existing;
        }
        $r = new Role($roleId);
        $r->setIsTeam(true);
        $userId = $ctx->getUser()->getId();
        $r->setPolicies(PoliciesFactory::policiesForUniqueUser($ctx->getUser()));
        self::updateRole($r, false);
        return $r;
    }

    public static function roleExists($roleId){
        try{
            $existingRole = MicroApi::GetRoleServiceApi()->getRole($roleId);
            return true;
        } catch (ApiException $ex){
            if($ex->getCode() == 404){
                return false;
            } else {
                throw $ex;
            }
        }
    }

    /**
     * Get Role by Id
     *
     * @param string $roleId
     * @return Role|false
     */
    public static function getRole($roleId)
    {
        try{
            $existingRole = MicroApi::GetRoleServiceApi()->getRole($roleId);
        } catch (ApiException $ex){
            if($ex->getCode() == 404){
                return false;
            } else {
                throw $ex;
            }
        }
        return new Role($roleId, $existingRole);
    }

    /**
     * @param string $roleId Id of the role
     * @param string $groupPath GroupPath to be applied
     * @return Role
     */
    public static function getOrCreateRole($roleId, $groupPath = "/", $isGroupRole = false, $isUserRole = false)
    {
        $existingRole = self::getRole($roleId);
        if (!empty($existingRole)) {
            return $existingRole;
        }
        $role = new Role($roleId);
        $role->setGroupPath($groupPath);
        if($isGroupRole) {
            $role->setGroupRole(true);
        } else if ($isUserRole) {
            $role->setUserRole(true);
        }
        self::updateRole($role, false);
        return $role;
    }

    /**
     * Create or update role
     *
     * @param Role $roleObject
     * @internal param null|UserInterface $userObject
     */
    public static function updateRole($roleObject, $includeAcls = false)
    {
        $roleApi = MicroApi::GetRoleServiceApi();
        $label = $roleObject->getLabel();
        if (empty($label)) {
            if ($roleObject->getGroupRole()) {
                $roleObject->setLabel("Group " . $roleObject->getUuid());
            } else if ($roleObject->getUserRole()) {
                $roleObject->setLabel("User " . $roleObject->getUuid());
            } else {
                $roleObject->setLabel("Role " . $roleObject->getUuid());
            }
        }
        // Save Role
        $idmRole = $roleApi->setRole($roleObject->getUuid(), $roleObject);
        // Save Acls
        self::saveAcls($roleObject, $includeAcls);
    }

    /**
     * Delete a role by its id
     * @static
     * @param string $roleId
     * @param string $ownerId
     * @return void
     * @throws PydioException
     */
    public static function deleteRole($roleId)
    {
        MicroApi::GetRoleServiceApi()->deleteRole($roleId);
    }

    /**
     * @param string $parentUserId
     * @return Role
     */
    public static function limitedRoleFromParent($parentUserId)
    {
        $parentRole = self::getRole($parentUserId);
        if ($parentRole === false) return null;

        $inheritActions = PluginsService::searchManifestsWithCache("//server_settings/param[@inherit='true']", function ($nodes) {
            $result = [];
            if (is_array($nodes) && count($nodes)) {
                foreach ($nodes as $node) {
                    $paramName = $node->getAttribute("name");
                    $pluginId = $node->parentNode->parentNode->getAttribute("id");
                    if (isSet($result[$pluginId])) $result[$pluginId] = array();
                    $result[$pluginId][] = $paramName;
                }
            }
            return $result;
        });

        // Clear ACL, Keep disabled actions, keep 'inherit' parameters.
        $childRole = new Role("PYDIO_PARENT_USR_/");
        $childRole->bunchUpdate(array(
            "ACL" => array(),
            "ACTIONS" => $parentRole->listAllActionsStates(),
            "APPLIES" => array(),
            "PARAMETERS" => array()));
        $params = $parentRole->listParameters();

        foreach ($params as $scope => $plugData) {
            foreach ($plugData as $pId => $paramData) {
                if (!isSet($inheritActions[$pId])) continue;
                foreach ($paramData as $pName => $pValue) {
                    $childRole->setParameterValue($pId, $pName, $pValue, $scope);
                }
            }
        }

        return $childRole;
    }

    /**
     * @param boolean $status
     */
    public static function enableRolesCache($status){
        self::$useCache = $status;
        if($status){
            self::$rolesCache = null;
        }
    }

    /** @var  boolean */
    private static $useCache;

    /** @var  array */
    private static $rolesCache;

    /**
     * Get all defined roles
     * @static
     * @param array $roleIds
     * @param boolean $excludeReserved,
     * @return Role[]
     */
    public static function getRolesList($roleIds = array(), $excludeReserved = false)
    {
        $api = MicroApi::GetRoleServiceApi();
        $req = new RestSearchRoleRequest();
        $subQueries = [];
        if (count($roleIds) > 0) {
            $sQ = new IdmRoleSingleQuery();
            $sQ->setUuid($roleIds);
            $subQueries[] = $sQ;
        }
        if ($excludeReserved) {
            $sQ2 = new IdmRoleSingleQuery();
            $sQ2->setIsGroupRole(true);
            $sQ2->setIsUserRole(true);
            $sQ2->setNot(true);
            $subQueries[] = $sQ2;
        }

        // Exclude teams
        $sQ3 = new IdmRoleSingleQuery();
        $sQ3->setIsTeam(true);
        $sQ3->setNot(true);
        $subQueries[] = $sQ3;

        $req->setQueries($subQueries);
        $req->setOperation(ServiceOperationType::_AND);
        $resourceQuery = new RestResourcePolicyQuery();
        $resourceQuery->setType(ResourcePolicyQueryQueryType::ANY);
        $req->setResourcePolicyQuery($resourceQuery);
        $rolesList = $api->searchRoles($req);
        $roleData = [];
        if ($rolesList->getRoles() != null) {
            foreach($rolesList->getRoles() as $role) {
                $pydioRole = new Role($role->getUuid(), $role, true);
                $roleData[$role->getUuid()] = $pydioRole;
            }
        }
        self::loadAcls($roleData);
        return $roleData;

    }

    // Assign directly an ACL to a given role on a workspace root nodes
    public static function assignWorkspaceAcl($roleId, $wsId, $aclValue, $replaceIfExists = false) {
        if ($replaceIfExists) {
            self::removeWorkspaceAcls($roleId, $wsId);
        }
        $api = MicroApi::GetAclServiceApi();
        $acls = self::workspaceAclToList($wsId, $aclValue);
        foreach($acls as $acl) {
            $idmAcl = new IdmACL();
            $action = new IdmACLAction();
            $idmAcl->setRoleId($roleId);
            $idmAcl->setWorkspaceId($acl[0]);
            $idmAcl->setNodeId($acl[1]);
            $action->setName($acl[2]);
            $action->setValue($acl[3]);
            $idmAcl->setAction($action);
            $api->putAcl($idmAcl);
        }
    }

    // Remove directly an ACL from a given role from a workspace root nodes
    public static function removeWorkspaceAcls($roleId, $wsId) {
        $api = MicroApi::GetAclServiceApi();
        $acls = self::workspaceAclToList($wsId, "", true);
        foreach($acls as $acl) {
            $idmAcl = new IdmACL();
            $idmAcl->setRoleId($roleId);
            $idmAcl->setWorkspaceId($acl[0]);
            $idmAcl->setNodeId($acl[1]);
            $api->deleteAcl($idmAcl);
        }
    }

    /**
     * @param $roles Role[]
     */
    public static function loadAcls(&$roles) {

        $wsIds = [];
        $rolesNodesAcls = [];
        foreach($roles as $role){
            $wsIds[$role->getId()] = [];
            $rolesNodesAcls[$role->getId()] = [];
            $role->setAcl("pydiogateway", "read,write");
        }
        // List ACLS
        $api = MicroApi::GetAclServiceApi();
        $request = new RestSearchACLRequest();
        $query = new IdmACLSingleQuery();
        $query->setRoleIDs(array_keys($roles));
        $request->setQueries([$query]);
        $collection = $api->searchAcls($request);
        if ($collection->getAcLs() == null) {
            return;
        }

        $fillRight = function(&$origin, $key, $actionName, $actionValue){
            if(!isSet($origin[$key])) {
                $origin[$key] = [
                    "read" => false,
                    "write" => false,
                    "deny" => false,
                    "children" => false,
                    "policy" => false
                ];
            }
            if (array_key_exists($actionName, $origin[$key])) {
                $origin[$key][$actionName] = ($actionName === "policy" ? $actionValue : true);
            }
        };

        $acls = $collection->getAcLs();
        foreach($acls as $acl) {

            $actionName = $acl->getAction()->getName();
            $workspaceId = $acl->getWorkspaceId();
            $roleId = $acl->getRoleId();
            if(!isSet($roles[$roleId])) {
                continue;
            }
            $role = &$roles[$roleId];

            if(strpos($actionName, "action:") === 0) {

                list($a, $pluginId, $realActionName) = explode(":", $actionName);
                $actionValue = $acl->getAction()->getValue();
                $role->setActionState($pluginId, $realActionName, $workspaceId, json_decode($actionValue, true));

            } else if(strpos($actionName, "parameter:") === 0) {

                list($a, $pluginId, $paramName) = explode(":", $actionName);
                $paramValue = $acl->getAction()->getValue();
                $role->setParameterValue($pluginId, $paramName, json_decode($paramValue), $workspaceId);

            } else if( $actionName === "quota" ) {

                $role->setParameterValue("meta.quota", "USAGE_QUOTA", $acl->getAction()->getValue(), $workspaceId);

            } else if(empty($workspaceId)) {

                $nodeId = $acl->getNodeId();
                $fillRight($rolesNodesAcls[$roleId], $nodeId, $actionName, $actionValue);

            } else {

                $fillRight($wsIds[$roleId], $workspaceId, $actionName, $acl->getAction()->getValue());

            }

        }

        foreach($wsIds as $roleId => $wsRights) {
            if(!isSet($roles[$roleId])) {
                continue;
            }
            $role = &$roles[$roleId];
            foreach($wsRights as $wsId => $rights) {
                if ($rights["deny"]) {
                    $role->setAcl($wsId, PYDIO_VALUE_CLEAR);
                } else if(!empty($rights["policy"])){
                    $role->setAcl($wsId, "policy:" . $rights["policy"]);
                } else {
                    $rString =  implode(",", array_keys(array_filter($rights, function ($v){ return $v === true ; })));
                    if (!empty($rString)) {
                        $role->setAcl($wsId, $rString);
                    }
                }
            }
        }

        foreach($rolesNodesAcls as $roleId => $nodesAcls) {
            if(!isSet($roles[$roleId])) {
                continue;
            }
            $role = &$roles[$roleId];
            $role->setNodesAcls($nodesAcls);
        }

    }

    protected static function saveAcls(Role $roleObject, $includeAcls = false) {

        $roleID = $roleObject->getId();

        $api = MicroApi::GetAclServiceApi();
        $idmAcl = new IdmACL();
        $idmAcl->setRoleId($roleID);
        if(!$includeAcls){
            $idmAcl->setAction((new IdmACLAction())->setName("parameter:*"));
        }
        $api->deleteAcl($idmAcl);

        $acls = [];

        if($includeAcls) {

            $roleAcls = $roleObject->listAcls(false);
            foreach ($roleAcls as $workspaceId => $acl) {
                $toAdd = self::workspaceAclToList($workspaceId, $acl);
                foreach ($toAdd as $newAcl) {
                    $acls[] = $newAcl;
                }
            }

            $nodesAcls = $roleObject->getNodesAcls();
            foreach($nodesAcls as $nodeId => $nodeAcls){
                foreach($nodeAcls as $actionName => $valueBool){
                    if($valueBool){
                        $acls[] = ["", $nodeId, $actionName, "1"];
                    }
                }
            }

            $actions = $roleObject->listAllActionsStates();
            foreach ($actions as $workspaceId => $pluginData) {
                foreach ($pluginData as $pluginId => $actions) {
                    foreach($actions as $actionName => $actionValue) {
                        $acls[] = [$workspaceId, "", "action:$pluginId:$actionName", json_encode($actionValue)];
                    }
                }
            }

        }

        $parameters = $roleObject->listParameters(true);
        foreach ($parameters as $workspaceId => $pluginData) {
            foreach ($pluginData as $pluginId => $params) {
                foreach ($params as $paramName => $paramValue) {
                    if($pluginId === "meta.quota" && $paramName === "USAGE_QUOTA") {
                        if($includeAcls) $acls[] = [$workspaceId, "", "quota", $paramValue];
                    } else {
                        $acls[] = [$workspaceId, "", "parameter:$pluginId:$paramName", json_encode($paramValue)];
                    }
                }
            }
        }

        foreach($acls as $acl) {

            $idmAcl = new IdmACL();
            $action = new IdmACLAction();
            $idmAcl->setRoleId($roleID);
            if(!empty($acl[0])) {
                $idmAcl->setWorkspaceId($acl[0]);
            }
            $idmAcl->setNodeId($acl[1]);
            $action->setName($acl[2]);
            $action->setValue($acl[3]);
            $idmAcl->setAction($action);

            $api->putAcl($idmAcl);

        }

    }

    private static $wsCache = [];

    private static function workspaceAclToList($workspaceId, $acl, $delete = false) {

        $acls = [];
        $workspaceId .= ""; // make sure it's a string
        if(isSet(self::$wsCache[$workspaceId])){
            $workspace = self::$wsCache[$workspaceId];
        } else {
            $workspace = RepositoryService::getRepositoryById($workspaceId);
            if(!empty($workspace)) self::$wsCache[$workspaceId] = $workspace;
        }

        if (empty($workspace)) {
            return $acls;
        }
        if ($workspace->isWriteable()){
            $rootNodes = $workspace->getRootNodes();
        } else {
            // This is a special ws
            $root = new RepositoryRoot();
            $root->setUuid("$workspaceId-ROOT");
            $rootNodes = [$root];
        }
        foreach ($rootNodes as $rootNode) {
            $nodeId = $rootNode->getUuid();
            if ($delete) {
                $acls[] = [$workspaceId, $nodeId, "", ""];
            } else if ($acl === PYDIO_VALUE_CLEAR) {
                $acls[] = [$workspaceId, $nodeId, "deny", "1"];
                continue;
            } else if (strpos($acl, "policy:") === 0) {
                $acls[] = [$workspaceId, $nodeId, "policy", substr($acl, strlen("policy:"))];
            } else {
                $parts = explode(",", $acl);
                foreach($parts as $part){
                    $acls[] = [$workspaceId, $nodeId, $part, "1"];
                }
            }
        }

        return $acls;

    }

}
