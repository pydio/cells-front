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

use Pydio\Conf\Core\PydioUser;
use Pydio\Conf\Core\Role;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\UserNotFoundException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\Context;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Model\FilteredRepositoriesList;
use Pydio\Core\Model\RepositoryInterface;
use Pydio\Core\Model\UserInterface;
use Pydio\Core\PluginFramework\PluginsService;
use Pydio\Core\Utils\Http\CookiesHelper;
use Pydio\Log\Core\Logger;
use Swagger\Client\Model\IdmNodeType;
use Swagger\Client\Model\IdmRole;
use Swagger\Client\Model\IdmUser;
use Swagger\Client\Model\IdmUserSingleQuery;
use Swagger\Client\Model\ResourcePolicyQueryQueryType;
use Swagger\Client\Model\RestSearchUserRequest;
use Swagger\Client\Model\ServiceResourcePolicy;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class UsersService
 * @package Pydio\Core\Services
 */
class UsersService
{
    /**
     * @var UsersService
     */
    private static $_instance;
    /**
     * @var array
     */
    private $repositoriesCache = [];
    /**
     * @var array
     */
    private $usersCache = [];

    /**
     * @var array
     */
    private $userParametersCache = [];

    /**
     * @return UsersService
     */
    public static function instance(){
        if(empty(self::$_instance)) self::$_instance = new UsersService();
        return self::$_instance;
    }

    /**
     * @param string $userId
     * @param bool $checkExists
     * @return UserInterface
     * @throws UserNotFoundException
     */
    public static function getUserById($userId, $checkExists = true){

        $self = self::instance();
        // Try to get from memory
        if(isSet($self->usersCache[$userId])){
            return $self->usersCache[$userId];
        }
        if($checkExists && !self::userExists($userId)){
            throw new UserNotFoundException($userId);
        }
        // Try to get from conf
        $userObject = ConfService::getConfStorageImpl()->createUserObject($userId);
        if($userObject instanceof UserInterface){
            // Save in memory
            $self->usersCache[$userId] = $userObject;
        }
        return $userObject;

    }

    /**
     * @param $userRole Role
     * @return string
     * @throws UserNotFoundException
     */
    public static function getUserFromUserRole($userRole){
        $api = MicroApi::GetUserServiceApi();
        $subQuery = new IdmUserSingleQuery();
        $subQuery->setUuid($userRole->getUuid());
        $subQuery->setNodeType(IdmNodeType::USER);
        $query = new RestSearchUserRequest();
        $query->setLimit(1);
        $query->setQueries([$subQuery]);
        $collection = $api->searchUsers($query);
        if ($collection->getTotal() == 0) {
            throw new UserNotFoundException($userUuid);
        }
        $idmUser = $collection->getUsers()[0];
        return new PydioUser($idmUser->getLogin(), $idmUser, $userRole);
    }


    /**
     * @param $userObject UserInterface
     * @param string $scope
     */
    public static function updateUser($userObject, $scope = "user"){
        $self = self::instance();
        $userId = $userObject->getId();
        $self->usersCache[$userId] = $userObject;
    }

    /**
     * @param string $userId
     * @param RepositoryInterface[] $repoList
     */
    private function setInCache($userId, $repoList){

        $this->repositoriesCache[$userId] = $repoList;
        if(SessionService::has(SessionService::USER_KEY) && SessionService::fetch(SessionService::USER_KEY)->getId() === $userId){
            SessionService::updateLoadedRepositories($repoList);
        }

    }

    /**
     * @param $userId
     * @return mixed|null|\Pydio\Core\Model\RepositoryInterface[]
     */
    private function getFromCaches($userId){

        // TODO - REMOVE
        return null;

        if(SessionService::has(SessionService::USER_KEY) && SessionService::fetch(SessionService::USER_KEY)->getId() === $userId) {
            $fromSession = SessionService::getLoadedRepositories();
            if ($fromSession !== null && is_array($fromSession) && count($fromSession)) {
                $this->repositoriesCache[$userId] = $fromSession;
                return $fromSession;
            }
        }
        if(isSet($this->repositoriesCache[$userId])) {
            $configsNotCorrupted = array_reduce($this->repositoriesCache[$userId], function($carry, $item){ return $carry && is_object($item) && ($item instanceof RepositoryInterface); }, true);
            if($configsNotCorrupted){
                return $this->repositoriesCache[$userId];
            }else{
                $this->repositoriesCache = [];
            }
        }
        return null;

    }

    public static function invalidateCache(){

        self::instance()->repositoriesCache = [];
        self::instance()->usersCache = [];
        SessionService::invalidateLoadedRepositories();

    }



    /**
     * Whether the whole users management system is enabled or not.
     * @static
     * @return bool
     */
    public static function usersEnabled()
    {
        return ConfService::getGlobalConf("ENABLE_USERS", "auth");
    }

    /**
     * Whether the current auth driver supports password update or not
     * @static
     * @return bool
     */
    public static function changePasswordEnabled()
    {
        $authDriver = ConfService::getAuthDriverImpl();
        return $authDriver->passwordsEditable();
    }

    /**
     * Return user to lower case if ignoreUserCase
     * @param $user
     * @return string
     */
    public static function filterUserSensitivity($user)
    {
        if (!ConfService::getGlobalConf("CASE_SENSITIVE", "auth")) {
            return strtolower($user);
        } else {
            return $user;
        }
    }

    /**
     * Get config to knwo whether we should ignore user case
     * @return bool
     */
    public static function ignoreUserCase()
    {
        return !ConfService::getGlobalConf("CASE_SENSITIVE", "auth");
    }

    /**
     * If the auth driver implementation has a logout redirect URL.
     * @static
     * @return string
     */
    public static function getLogoutAddress()
    {
        $authDriver = ConfService::getAuthDriverImpl();
        $logout = $authDriver->getLogoutRedirect();
        return $logout;
    }

    /**
     * Use driver implementation to check whether the user exists or not.
     * @static
     * @param String $userId
     * @param String $mode "r" or "w"
     * @return bool
     */
    public static function userExists($userId, $mode = "r")
    {
        if ($userId == "guest" && !ConfService::getGlobalConf("ALLOW_GUEST_BROWSING", "auth")) {
            return false;
        }
        $userId = self::filterUserSensitivity($userId);
        $authDriver = ConfService::getAuthDriverImpl();
        if ($mode == "w") {
            return $authDriver->userExistsWrite($userId);
        }
        return $authDriver->userExists($userId);
    }

    /**
     * Make sure a user id is not reserved for low-level tasks (currently "guest" and "shared").
     * @static
     * @param String $username
     * @return bool
     */
    public static function isReservedUserId($username)
    {
        $username = self::filterUserSensitivity($username);
        return in_array($username, array("guest", "shared"));
    }

    /**
     * Check a password
     * @static
     * @param $userId
     * @param $userPass
     * @param bool $cookieString
     * @return bool|void
     * @throws UserNotFoundException
     */
    public static function checkPassword($userId, $userPass, $cookieString = false)
    {
        if (ConfService::getGlobalConf("ALLOW_GUEST_BROWSING", "auth") && $userId == "guest") return true;
        $userId = self::filterUserSensitivity($userId);
        $authDriver = ConfService::getAuthDriverImpl();
        if ($cookieString) {
            $userObject = self::getUserById($userId);
            $res = CookiesHelper::checkCookieString($userObject, $userPass);
            return $res;
        }
        return $authDriver->checkPassword($userId, $userPass);
    }

    /**
     * Update the password in the auth driver implementation.
     * @static
     * @throws \Exception
     * @param $userId
     * @param $userPass
     * @return bool
     */
    public static function updatePassword($userId, $userPass)
    {
        if (strlen($userPass) < ConfService::getGlobalConf("PASSWORD_MINLENGTH", "auth")) {
            $messages = LocaleService::getMessages();
            throw new \Exception($messages[378]);
        }
        $userId = self::filterUserSensitivity($userId);
        $authDriver = ConfService::getAuthDriverImpl();
        $ctx = Context::emptyContext();
        Controller::applyHook("user.before_password_change", array($ctx, $userId));
        $authDriver->changePassword($userId, $userPass);
        Controller::applyHook("user.after_password_change", array($ctx, $userId));

        Logger::info(__CLASS__, "Update Password", array("user_id" => $userId));
        return true;
    }


    /**
     * Creates a user
     * @static
     *
     * @param $userId
     * @param $userPass
     * @param bool $isAdmin
     * @param bool $isHidden
     * @param string $profile
     * @param array $attributes
     * @param $resourcePolicies ServiceResourcePolicy[]
     *
     * @return UserInterface
     */
    public static function createUser($userId, $userPass, $isAdmin = false, $isHidden = false, $groupPath = "/", $profile = "", $attributes = [], $resourcePolicies = [])
    {
        $userId = self::filterUserSensitivity($userId);
        $localContext = Context::emptyContext();
        Controller::applyHook("user.before_create", array($localContext, $userId, $userPass, $isAdmin, $isHidden));
        $api = MicroApi::GetUserServiceApi();
        $user = (new IdmUser())
            ->setLogin($userId)
            ->setPassword($userPass)
            ->setGroupPath($groupPath);
        $atts = [];
        if($isAdmin) {
            $atts["admin"] = "true";
        }
        if($isHidden) {
            $atts["hidden"] = "true";
        }
        if(!empty($profile)) {
            $atts["profile"] = $profile;
        }
        if(!empty($attributes)) {
            $atts = array_merge($atts, $attributes);
        }
        if (count($atts) ) {
            $user->setAttributes($atts);
        }
        if(!empty($resourcePolicies)) {
            $user->setPolicies($resourcePolicies);
        }
        $createdUser = $api->putUser($userId, $user);
        // Create Associated Role
        $roleApi = MicroApi::GetRoleServiceApi();
        $idmRole = (new IdmRole())
            ->setUuid($createdUser->getUuid())
            ->setLabel("User " . $userId)
            ->setPolicies($resourcePolicies)
            ->setUserRole(true);
        $roleApi->setRole($idmRole->getUuid(), $idmRole);
        $pydioUser = new PydioUser($userId, $createdUser, $idmRole);

        Controller::applyHook("user.after_create", array($localContext, $pydioUser));
        return $pydioUser;
    }

    /**
     * Delete a user in the auth/conf driver impl
     * @static
     * @param $userId
     * @return bool
     */
    public static function deleteUser($userId)
    {
        $ctx = Context::emptyContext();
        Controller::applyHook("user.before_delete", array($ctx, $userId));
        $userId = self::filterUserSensitivity($userId);
        $authDriver = ConfService::getAuthDriverImpl();
        $authDriver->deleteUser($userId);
        Controller::applyHook("user.after_delete", array($ctx, $userId));
        Logger::info(__CLASS__, "Delete User", array("user_id" => $userId));
        return true;
    }

    /**
     * @param $fullGroupPath string
     * @return bool
     */
    public static function groupExists($fullGroupPath) {
        return ConfService::getAuthDriverImpl()->groupExists($fullGroupPath);
    }

    /**
     * Load a group object by path
     * @param $groupPath
     * @return bool|IdmUser
     */
    public static function getGroupByPath($groupPath){
        return ConfService::getAuthDriverImpl()->getGroupByPath($groupPath);
    }

    /**
     * Load a group object by uuid
     * @param string $groupUuid
     * @return bool|IdmUser
     */
    public static function getGroupById($groupUuid){
        return ConfService::getAuthDriverImpl()->getGroupById($groupUuid);
    }

    /**
     * List children groups of current base
     * @param string $baseGroup
     * @return IdmUser[]
     */
    public static function listChildrenGroups($baseGroup = "/", $policyContext = ResourcePolicyQueryQueryType::ANY, $term = null, $recursive = false)
    {
        return ConfService::getAuthDriverImpl()->listChildrenGroups($baseGroup, $policyContext, $term, $recursive);

    }

    /**
     * Create a new group at the given path
     *
     * @param $baseGroup
     * @param $groupName
     * @param $groupLabel
     * @throws \Exception
     */
    public static function createGroup($baseGroup, $groupName, $groupLabel)
    {
        if (empty($groupName)) throw new \Exception("Please provide a name for this new group!");
        $fullGroupPath = rtrim($baseGroup, "/") . "/" . $groupName;
        $exists = ConfService::getAuthDriverImpl()->groupExists($fullGroupPath);
        if ($exists) {
            throw new \Exception("Group with this name already exists, please pick another name!");
        }
        if (empty($groupLabel)) $groupLabel = $groupName;
        ConfService::getAuthDriverImpl()->createGroup(rtrim($baseGroup, "/") . "/" . $groupName, $groupLabel);
    }

    /**
     * Delete group by name
     * @param $baseGroup
     * @param $groupName
     */
    public static function deleteGroup($baseGroup, $groupName)
    {
        ConfService::getAuthDriverImpl()->deleteGroup(rtrim($baseGroup, "/") . "/" . $groupName);
    }

    /**
     * Count the number of children a given user has already created
     * @param $parentUserId
     * @return UserInterface[]
     */
    public static function getChildrenUsers($parentUserId)
    {
        return ConfService::getAuthDriverImpl()->getUserChildren($parentUserId);
    }

    /**
     * Count the number of users who have either read or write access to a repository
     * @param ContextInterface $ctx
     * @param $repositoryId
     * @param bool $details
     * @param bool $admin True if called in an admin context
     * @return array|int
     */
    public static function countUsersForRepository(ContextInterface $ctx, $repositoryId, $details = false, $admin = false)
    {
        $object = RepositoryService::getRepositoryById($repositoryId);
        if(!$admin){
            if($object->securityScope() == "USER"){
                if($details) {
                    return array('users' => 1);
                } else {
                    return 1;
                }
            }else if($object->securityScope() == "GROUP" && $ctx->hasUser()){
                $groupUsers = UsersService::authCountUsers($ctx->getUser()->getGroupPath());
                if($details) {
                    return array('users' => $groupUsers);
                } else {
                    return $groupUsers;
                }
            }
        }

        // Users from roles
        $roles = RolesService::getRolesForRepository($repositoryId, '', $details);

        if($details){
            return array(
                'users' => count($roles['role_user']),
                'groups' => count($roles['role_group']) + count($roles['role_role'])
            );
        }else{
            return count($roles);
        }
    }

    /**
     * List users with a specific filter
     * @param string $baseGroup
     * @param null $regexp
     * @param int $offset
     * @param int $limit
     * @return UserInterface[]
     */
    public static function listUsers($baseGroup = "/", $regexp = null, $offset = -1, $limit = -1, $recursive = false)
    {
        return  ConfService::getAuthDriverImpl()->listUsersPaginated($baseGroup, $regexp, $offset, $limit, $recursive);
    }

    /**
     * Depending on the plugin, tried to compute the actual page where a given user can be located
     *
     * @param $baseGroup
     * @param $userLogin
     * @param $usersPerPage
     * @param int $offset
     * @return int
     */
    public static function findUserPage($baseGroup, $userLogin, $usersPerPage, $offset = 0)
    {
        if (ConfService::getAuthDriverImpl()->supportsUsersPagination()) {
            return ConfService::getAuthDriverImpl()->findUserPage($baseGroup, $userLogin, $usersPerPage, $offset);
        } else {
            return -1;
        }
    }

    /**
     * Count the total number of users inside a group (recursive).
     * Regexp can be used to limit the users IDs with a specific expression
     * Property can be used for basic filtering, either on "parent" or "admin".
     *
     * @param string $baseGroup
     * @param string $regexp
     * @param null $filterProperty Can be "parent" or "admin"
     * @param null $filterValue Can be a string, or constants FILTER_EMPTY / FILTER_NOT_EMPTY
     * @param bool $recursive
     * @return int
     */
    public static function authCountUsers($baseGroup = "/", $regexp = "", $filterProperty = null, $filterValue = null, $recursive = true)
    {
        $authDriver = ConfService::getAuthDriverImpl();
        return $authDriver->getUsersCount($baseGroup, $regexp, $filterProperty, $filterValue, $recursive);
    }

    /**
     * Makes a correspondance between a user and its auth scheme, for multi auth
     * @param $userName
     * @return String
     */
    public static function getAuthScheme($userName)
    {
        $authDriver = ConfService::getAuthDriverImpl();
        return $authDriver->getAuthScheme($userName);
    }

    /**
     * Check if auth implementation supports schemes detection
     * @return bool
     */
    public static function driverSupportsAuthSchemes()
    {
        $authDriver = ConfService::getAuthDriverImpl();
        return $authDriver->supportsAuthSchemes();
    }

    /**
     * Get parameters with scope='user' expose='true' attributes.
     * Cached in plugin service.
     *
     * @return array Array of [PLUGIN_ID=>id, NAME=>name] objects.
     */
    public static function getUsersExposedParameters(){
        $exposed = PluginsService::searchManifestsWithCache("//server_settings/param[contains(@scope,'user') and @expose='true']", function($nodes){
            $result = [];
            /** @var \DOMElement $exposed_prop */
            foreach($nodes as $exposed_prop){
                $parentNode = $exposed_prop->parentNode->parentNode;
                $pluginId = $parentNode->getAttribute("id");
                if (empty($pluginId)) {
                    $pluginId = $parentNode->nodeName.".".$parentNode->getAttribute("name");
                }
                $paramName = $exposed_prop->getAttribute("name");
                $scope = $exposed_prop->getAttribute("scope");
                $result[] = ["PLUGIN_ID" => $pluginId, "NAME" => $paramName, "SCOPE" => $scope];
            }
            return $result;
        });
        return $exposed;
    }

}
