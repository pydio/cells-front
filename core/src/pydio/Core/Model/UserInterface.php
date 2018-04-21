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

defined('PYDIO_EXEC') or die('Access not allowed');

use Pydio\Conf\Core\IGroupPathProvider;
use Pydio\Conf\Core\Role;
use Swagger\Client\Model\IdmUser;

/**
 * User abstraction, the "conf" driver must provides its own implementation
 */
interface UserInterface extends IGroupPathProvider
{
    /**
     * @return IdmUser
     */
    public function getIdmUser();

    /**
     * @param bool $hidden
     */
    public function setHidden($hidden);

    /**
     * @return bool
     */
    public function isHidden();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getUuid();

    /**
     * @param string $id
     */
    public function setId($id);

    /**
     * @return bool
     */
    public function storageExists();

    /**
     * @param ServiceResourcePolicy[] $policies
     */
    public function setPolicies($policies);

    /**
     * @return ServiceResourcePolicy[]
     */
    public function getPolicies();

    /**
     * @param Role $roleObject
     */
    public function addRole($roleObject);

    /**
     * @param string $roleId
     * @throws \Exception
     */
    public function removeRole($roleId);

    /**
     * @param $orderedRolesIds array of roles ids
     */
    public function updateRolesOrder($orderedRolesIds);

    /**
     * @return Role[]
     */
    public function getRoles();

    /**
     * @return Role[]
     */
    public function getNonReservedRoles();

    /**
     * @return bool
     */
    public function hasSharedProfile();

    /**
     * @return string
     */
    public function getProfile();

    /**
     * @param string $profile
     */
    public function setProfile($profile);

    /**
     * @param string $lockAction
     * @throws \Exception
     */
    public function setLock($lockAction);

    /**
     * @throws \Exception
     */
    public function removeLock($lockAction);

    /**
     * @param $lockAction
     * @return string|false
     */
    public function hasLockByName($lockAction);

    /**
     * @return string|false
     */
    public function getLock();

    /**
     * @return bool
     */
    public function isAdmin();

    /**
     * @param bool $boolean
     */
    public function setAdmin($boolean);

    /**
     * @param $prefName
     * @return mixed|string
     */
    public function getPref($prefName);

    /**
     * @param $prefName
     * @param $prefValue
     */
    public function setPref($prefName, $prefValue);

    /**
     * @param string $prefName
     * @param string $prefPath
     * @param mixed $prefValue
     */
    public function setArrayPref($prefName, $prefPath, $prefValue);

    /**
     * @param $prefName
     * @param $prefPath
     * @return mixed|string
     */
    public function getArrayPref($prefName, $prefPath);

    /**
     * @param $repositoryId
     * @param string $path
     * @param string $title
     * @return
     */
    public function addBookmark($repositoryId, $path, $title);

    /**
     * @param string $repositoryId
     * @param string $path
     */
    public function removeBookmark($repositoryId, $path);

    /**
     * @param string $repositoryId
     * @param string $path
     * @param string $title
     */
    public function renameBookmark($repositoryId, $path, $title);

    /**
     * @return array
     */
    public function getBookmarks($repositoryId);

    /**
     * Check if the current user can administrate the GroupPathProvider object
     * @param IGroupPathProvider $provider
     * @return bool
     */
    public function canAdministrate(IGroupPathProvider $provider);

    /**
     * Check if the current user can assign administration for the GroupPathProvider object
     * @param IGroupPathProvider $provider
     * @return bool
     */
    public function canSee(IGroupPathProvider $provider);

    /**
     * Automatically set the group to the current user base
     * @param $baseGroup
     * @return string
     */
    public function getRealGroupPath($baseGroup);

    /**
     * @return mixed
     */
    public function load();

    /**
     * @param string $context
     */
    public function save($includePersonalAcls = false, $includePersonalRole = true);

    /**
     * Just save the set of roles for this user, no
     * other attributes.
     */
    public function saveRoles();

        /**
     * @param string $key
     * @return mixed
     */
    public function getTemporaryData($key);

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function saveTemporaryData($key, $value);

    /**
     * Rebuild the current merged role
     * @throws \Exception
     */
    public function recomputeMergedRole();

    /**
     * @return Role
     */
    public function getMergedRole();

    /**
     * @return Role
     */
    public function getPersonalRole();

    /**
     * @param Role $role
     */
    public function updatePersonalRole(Role $role);

    /**
     * @return array
     */
    public function getRolesKeys();

    /**
     * @param string
     * @param mixed
     * @return mixed
     */
    public function getPersonalAttribute($attributeName, $defaultValue = null);

    /**
     * @param string
     * @param mixed
     * @return string
     */
    public function setPersonalAttribute($attributeName, $attributeValue);

}