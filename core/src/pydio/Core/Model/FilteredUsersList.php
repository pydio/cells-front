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
namespace Pydio\Core\Model;


use Psr\Http\Message\ResponseInterface;
use Pydio\Conf\Core\PydioUser;
use Pydio\Conf\Core\Role;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\LocaleService;
use Pydio\Core\Services\RolesService;
use Pydio\Core\Services\UsersService;
use Swagger\Client\Model\IdmUserSingleQuery;
use Swagger\Client\Model\ResourcePolicyQueryQueryType;
use Swagger\Client\Model\RestResourcePolicyQuery;
use Swagger\Client\Model\RestSearchUserRequest;
use Swagger\Client\Model\ServiceOperationType;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class FilteredUsersList
 * @package Pydio\Core\Model
 */
class FilteredUsersList{

    const FILTER_USERS_INTERNAL = 1;
    const FILTER_USERS_EXTERNAL = 2;
    const FILTER_GROUPS = 4;
    const FILTER_TEAMS = 8;

    const TEAM_PREFIX = '/USER_TEAM';

    /**
     * @var ContextInterface
     */
    private $ctx;

    /**
     * @var bool
     */
    private $excludeCurrent;

    /**
     * @var string
     */
    private $range;

    /**
     * @var
     */
    private $responseRange;

    /**
     * FilteredUsersList constructor.
     * @param ContextInterface $ctx
     */
    public function __construct(ContextInterface $ctx, $excludeCurrent = true, $range = ''){
        $this->ctx = $ctx;
        $this->excludeCurrent = $excludeCurrent;
        $this->range = $range;
    }

    /**
     * @param $confName
     * @param string $coreType
     * @return mixed
     */
    protected function getConf($confName, $coreType = 'auth'){
        return ConfService::getContextConf($this->ctx, $confName, $coreType);
    }

    /**
     * @param string $groupPathFilter
     * @param string $searchQuery
     * @return string
     */
    protected function computeBaseGroup($groupPathFilter = '', $searchQuery = ''){

        if(strpos($groupPathFilter, self::TEAM_PREFIX) === 0){
            return $groupPathFilter;
        }

        $searchAll      = true;
        $displayAll     = true;

        $contextGroupPath   = $this->ctx->getUser()->getGroupPath();
        $baseGroup = '/';
        if( (empty($searchQuery) && !$displayAll) || (!empty($searchQuery) && !$searchAll)){
            $baseGroup = $contextGroupPath;
        }
        if( !empty($groupPathFilter) ){
            $baseGroup = rtrim($baseGroup, '/') . $groupPathFilter;
        }

        return $baseGroup;

    }

    /**
     * @param UserInterface $userObject
     * @param string $rolePrefix get all roles with prefix
     * @param string $includeString get roles in this string
     * @param string $excludeString eliminate roles in this string
     * @param bool $byUserRoles
     * @return array
     */
    protected function searchUserRolesList($userObject, $rolePrefix, $includeString, $excludeString, $byUserRoles = false)
    {
        if (!$userObject){
            return [];
        }
        if ($byUserRoles) {
            $allUserRoles = $userObject->getRoles();
        } else {
            $allUserRoles = RolesService::getRolesList([], true);
        }
        $allRoles = [];
        if (isset($allUserRoles)) {

            // Exclude
            if ($excludeString) {
                if (strpos($excludeString, "preg:") !== false) {
                    $matchFilterExclude = "/" . str_replace("preg:", "", $excludeString) . "/i";
                } else {
                    $valueFiltersExclude = array_map("trim", explode(",", $excludeString));
                    $valueFiltersExclude = array_map("strtolower", $valueFiltersExclude);
                }
            }

            // Include
            if ($includeString) {
                if (strpos($includeString, "preg:") !== false) {
                    $matchFilterInclude = "/" . str_replace("preg:", "", $includeString) . "/i";
                } else {
                    $valueFiltersInclude = array_map("trim", explode(",", $includeString));
                    $valueFiltersInclude = array_map("strtolower", $valueFiltersInclude);
                }
            }

            foreach ($allUserRoles as $roleId => $role) {
                if (!empty($rolePrefix) && strpos($roleId, $rolePrefix) === false) continue;
                if (isSet($matchFilterExclude) && preg_match($matchFilterExclude, substr($roleId, strlen($rolePrefix)))) continue;
                if (isSet($valueFiltersExclude) && in_array(strtolower(substr($roleId, strlen($rolePrefix))), $valueFiltersExclude)) continue;
                if (isSet($matchFilterInclude) && !preg_match($matchFilterInclude, substr($roleId, strlen($rolePrefix)))) continue;
                if (isSet($valueFiltersInclude) && !in_array(strtolower(substr($roleId, strlen($rolePrefix))), $valueFiltersInclude)) continue;
                if($role instanceof Role) $roleObject = $role;
                else $roleObject = RolesService::getRole($roleId);
                $label = $roleObject->getLabel();
                $label = !empty($label) ? $label : substr($roleId, strlen($rolePrefix));
                $allRoles[$roleId] = $label;
            }
        }
        return $allRoles;
    }

    /**
     * @param $groupPath string
     * @param $searchTerm string
     * @param $offset int
     * @param $searchLimit int
     * @param $filterArray array
     * @param $recursive bool
     * @return UserInterface[]
     * @throws \Swagger\Client\ApiException
     */
    protected function listUsers($groupPath, $searchTerm, $offset, $searchLimit, $filterArray, $recursive = false){

        $api = MicroApi::GetUserServiceApi();
        $query = new RestSearchUserRequest();
        $query->setOffset($offset);
        if($searchLimit > 0) {
            $query->setLimit($searchLimit);
        }
        $subQueries = [];


        $subQueries[]= (new IdmUserSingleQuery())
            ->setAttributeName("hidden")
            ->setAttributeValue("true")
            ->setNot(true);

        if($filterArray['users_external'] && !$filterArray['users_internal']) {
            $subQueries[] = (new IdmUserSingleQuery())->setAttributeName("profile")->setAttributeValue("shared");
        } else if(!$filterArray['users_external'] && $filterArray['users_internal']) {
            $subQueries[] = (new IdmUserSingleQuery())->setAttributeName("profile")->setAttributeValue("shared")->setNot(true);
        }

        if(strpos($groupPath, self::TEAM_PREFIX) === 0){
            $teamId = str_replace(self::TEAM_PREFIX.'/', '', $groupPath);
            $subQuery = new IdmUserSingleQuery();
            $subQuery->setHasRole($teamId);
            $subQueries[] = $subQuery;
        } else {
            $subQueries[] = (new IdmUserSingleQuery())
                ->setGroupPath($groupPath)
                ->setRecursive($recursive);

            if(!empty($searchTerm)) {
                $subQueries[] = (new IdmUserSingleQuery())->setLogin($searchTerm."*");
            }
        }

        if($this->excludeCurrent) {
            $subQueries[] = (new IdmUserSingleQuery())->setLogin($this->ctx->getUser()->getId())->setNot(true);
        }
        $query->setQueries($subQueries);
        $query->setOperation(ServiceOperationType::_AND);
        $query->setResourcePolicyQuery((new RestResourcePolicyQuery())->setType(ResourcePolicyQueryQueryType::CONTEXT));

        $collection = $api->searchUsers($query);
        $result = [];
        if ($collection->getUsers() != null) {
            foreach ($collection->getUsers() as $user) {
                if($user->getLogin() === 'pydio.anon.user') {
                    continue;
                }
                $result[] = new PydioUser($user->getLogin(), $user, null, false);
            }
        }

        // Handle Total Count ?
        // $this->responseRange = ($count > $searchLimit) ? $offset."-".($offset+$searchLimit)."/".$count : null;

        return $result;

    }

    /**
     * @param $baseGroup
     * @return array
     */
    protected function listGroupsOrRoles($baseGroup, $regexp, $pregexp, $searchLimit = null){

        $allGroups = [];
        $authGroups = UsersService::listChildrenGroups($baseGroup, ResourcePolicyQueryQueryType::CONTEXT, $regexp);
        foreach ($authGroups as $gId => $gObject) {
            $allGroups[] = new IdmAdressBookItem($gObject, $baseGroup);
        }
        return $allGroups;

    }

    /**
     * @param $searchQuery string
     * @return AddressBookItem[]
     */
    public function listTeams($searchQuery = ''){
        if(!empty($searchQuery)){
            $pregexp = '/^'.preg_quote($searchQuery).'/i';
        }
        $res = [];
        $teams = RolesService::getUserTeamRoles();
        foreach ($teams as $teamObject) {
            if(empty($pregexp) || preg_match($pregexp, $teamObject->getLabel()) || preg_match($pregexp, $teamObject->getId())){
                $res[] = new IdmAdressBookItem($teamObject);
            }
        }
        return $res;
    }

    /**
     * @param $value int
     * @return array
     */
    private function parseFilterValue($value){
        return [
            'users_internal' => ($value & self::FILTER_USERS_INTERNAL) > 0,
            'users_external' => ($value & self::FILTER_USERS_EXTERNAL) > 0,
            'groups'         => ($value & self::FILTER_GROUPS) > 0,
            'teams'          => ($value & self::FILTER_TEAMS) > 0
        ];
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponseHeaders(ResponseInterface &$response){
        if(!$this->responseRange) return;
        $response = $response
            ->withHeader('Content-Range', $this->responseRange)
            ->withHeader('Accept-Range', 'user '.$this->getConf('USERS_LIST_COMPLETE_LIMIT'));
    }

    /**
     * @param int $filterValue
     * @param bool $allowCreation
     * @param string $searchQuery
     * @param string $groupPathFilter
     * @param string $remoteServerId
     * @return AddressBookItem[]
     */
    public function load($filterValue, $allowCreation = true, $searchQuery = '', $groupPathFilter = '', $remoteServerId = ''){

        $FILTER = $this->parseFilterValue($filterValue);
        if(!empty($groupPathFilter) && strpos($groupPathFilter, self::TEAM_PREFIX) !== 0){
            $FILTER['users_external'] = false;
        }

        // No Regexp and it's mandatory. Just return the current user teams. If asking for externals only, this is from address book, allow query
        if($this->getConf('USERS_LIST_REGEXP_MANDATORY') && empty($searchQuery) && empty($groupPathFilter) && !($FILTER['users_external'] && !$FILTER['users_internal'])){
            return $this->listTeams();
        }

        $items          = [];
        $mess           = LocaleService::getMessages();
        $allowCreation &= $this->getConf('USER_CREATE_USERS');
        if(empty($this->range)){
            $offset = 0;
            $searchLimit = $this->getConf('USERS_LIST_COMPLETE_LIMIT');
        }else{
            list($offset, $end) = explode('-', $this->range);
            $searchLimit = min($end - $offset, $this->getConf('USERS_LIST_COMPLETE_LIMIT'));
        }
        $baseGroup      = $this->computeBaseGroup($groupPathFilter, $searchQuery);

        if(!empty($searchQuery)) {
            $regexp = $searchQuery;
            $pregexp = '/^'.preg_quote($searchQuery).'/i';
        } else {
            $regexp = $pregexp = null;
        }

        $allGroups = [];
        $allUsers = [];
        if( $FILTER['users_internal'] || $FILTER['users_external'] ){
            $allUsers = $this->listUsers($baseGroup, $searchQuery, $offset, $searchLimit, $FILTER, empty($groupPathFilter));
        }
        if( $FILTER['groups'] ) {
            $allGroups = $this->listGroupsOrRoles($baseGroup, $regexp, $pregexp, $searchLimit);
        }

        if ( $allowCreation && !empty($searchQuery) && (!count($allUsers) || !array_key_exists(strtolower($searchQuery), $allUsers)) ) {
            $items[] = new TempAddressBookItem($searchQuery);
        }
        if ( $FILTER['groups'] && empty($groupPathFilter) && (empty($regexp)  ||  preg_match($pregexp, $mess["447"]))) {
            try{
                $gRole = RolesService::getRole(RolesService::RootGroup);
                $aB = new IdmAdressBookItem($gRole);
                $items[] = $aB;
            } catch(\Exception $e){}
        }

        foreach($allGroups as $groupItem) {
            $items[] = $groupItem;
        }

        if ( $FILTER['teams'] && empty($groupPathFilter) ) {
            $teams = $this->listTeams($searchQuery);
            foreach($teams as $t){
                $items[] = $t;
            }
        }

        $index = 0;
        foreach ($allUsers as $userObject) {

            $addressBookItem = new IdmAdressBookItem($userObject->getIdmUser());
            $index ++;

            if($userObject->getIdmUser()->getPoliciesContextEditable()){
                $lang = $userObject->getMergedRole()->filterParameterValue("core.conf", "lang", PYDIO_REPO_SCOPE_ALL, "");
                $addressBookItem->appendData('lang', $lang);
            }

            $items[] = $addressBookItem;
            if($index == $searchLimit) break;
        }

        return $items;

    }



}
