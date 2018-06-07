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

use Pydio\Access\Core\Model\Repository;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\AccessibleWorkspaces;
use Pydio\Core\Model\Context;
use Pydio\Core\Model\FilteredRepositoriesList;
use Pydio\Core\Model\RepositoryInterface;
use Pydio\Core\Model\RepositoryRoot;
use Pydio\Core\Model\UserInterface;
use Pydio\Core\PluginFramework\PluginsService;
use Pydio\Log\Core\Logger;
use Swagger\Client\ApiException;
use Swagger\Client\Model\IdmACL;
use Swagger\Client\Model\IdmACLAction;
use Swagger\Client\Model\IdmACLSingleQuery;
use Swagger\Client\Model\IdmWorkspace;
use Swagger\Client\Model\IdmWorkspaceScope;
use Swagger\Client\Model\IdmWorkspaceSingleQuery;
use Swagger\Client\Model\RestDataSource;
use Swagger\Client\Model\RestDataSourceType;
use Swagger\Client\Model\RestEncryptionMode;
use Swagger\Client\Model\RestSearchACLRequest;
use Swagger\Client\Model\RestSearchWorkspaceRequest;
use Swagger\Client\Model\ServiceOperationType;

defined('PYDIO_EXEC') or die('Access not allowed');


/**
 * Class RepositoryService
 * @package Pydio\Core\Services
 */
class RepositoryService
{
    const AccessListCacheKey = "pydio_access_list_cache";
    const WorkspacesCacheKey = "pydio_workspaces_cache";

    private $cache = [];
    /**
     * @var RepositoryService
     */
    private static $instance;

    /**
     * Singleton method
     *
     * @return RepositoryService The service instance
     */
    public static function getInstance()
    {
        if (!isSet(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * RepositoryService constructor.
     */
    private function __construct(){

        if (is_file(PYDIO_CONF_PATH."/bootstrap_repositories.php")) {
            $REPOSITORIES = array();
            include(PYDIO_CONF_PATH."/bootstrap_repositories.php");
            $this->cache["DEFAULT_REPOSITORIES"] = $REPOSITORIES;
        } else {
            $this->cache["DEFAULT_REPOSITORIES"] = array();
        }

    }

    /**
     * @param RepositoryInterface $repository
     * @param UserInterface $userObject
     * @return bool
     * @internal param bool $details
     */
    public static function repositoryIsAccessible($repository, $userObject)
    {
        if ($userObject === null && UsersService::usersEnabled()) {
            return false;
        }
        if (!$userObject->canSee($repository)) {
            return false;
        }
        if ($repository->isTemplate()) {
            return false;
        }
        $isAdminRepo = ($repository->getAccessType()==="settings");
        if ($isAdminRepo && $userObject !== null) {
            if (UsersService::usersEnabled() && !$userObject->isAdmin()) {
                return false;
            }
        }
        $adminURI = ConfService::getGlobalConf("ADMIN_URI");
        if(ApplicationState::sapiIsCli() && !empty($adminURI) && (($isAdminRepo && !ApplicationState::isAdminMode()) || (!$isAdminRepo && ApplicationState::isAdminMode()))){
            return false;
        }
        $repositoryId = $repository->getId();

        $res = null;
        $args = array($repositoryId, $repository, $userObject, &$res);
        Controller::applyIncludeHook("repository.test_access", $args);
        if($res === false){
            return false;
        }
        return true;
    }


    /**
     * PUBLIC STATIC METHODS
     */

    /**
     * @return RepositoryInterface[]
     */
    public static function getStaticRepositories(){
        $self = self::getInstance();
        if(!isSet($self->cache["STATIC"])){
            $self->cache["STATIC"] = [];
            foreach ($self->cache["DEFAULT_REPOSITORIES"] as $index=>$repository) {
                $repoObject = self::createRepositoryFromArray($index, $repository);
                $repoObject->setWriteable(false);
                $self->cache["STATIC"][$repoObject->getId()] = $repoObject;
            }
        }
        return $self->cache["STATIC"];
    }

    /**
     * @param $user UserInterface
     * @return AccessibleWorkspaces
     */
    public static function contextUserRepositories(UserInterface $user, $forceRefresh = false){

        if (!$forceRefresh && SessionService::has(self::AccessListCacheKey)) {
            $accesses = SessionService::fetch(self::AccessListCacheKey);
            $idmWorkspaces = SessionService::fetch(self::WorkspacesCacheKey);
        } else {
            $api = MicroApi::GetGraphServiceApi();
            $response = $api->userState("workspaces"); // not used, but must not be empty
            $repositories = [];
            $idmWorkspaces = $response->getWorkspaces();
            $accesses = $response->getWorkspacesAccesses();
            if ($accesses !== null) {
                SessionService::save(self::AccessListCacheKey, $accesses);
                SessionService::save(self::WorkspacesCacheKey, $idmWorkspaces);
            }
        }
        $accessList = new AccessibleWorkspaces($accesses);
        if ($accesses == null) {
            return $accessList;
        }

        $statics = self::getStaticRepositories();
        foreach ($statics as $static){
            if ($accessList->hasWorkspace($static->getId()) && self::repositoryIsAccessible($static, $user)){
                $repositories[$static->getId()] = $static;
            }
        }
        if($idmWorkspaces != null){
            foreach($idmWorkspaces as $idmWorkspace){
                $repo = self::idmWsToRepository($idmWorkspace, true);
                if(!$accessList->hasWorkspace($repo->getId()) || !self::repositoryIsAccessible($repo, $user)){
                    continue;
                }
                $roots = [];
                if($idmWorkspace->getRootNodes() == null) continue;
                foreach($idmWorkspace->getRootNodes() as $rootNodeId){
                    $root = new RepositoryRoot();
                    $root->setUuid($rootNodeId);
                    $roots[] = $root;
                }
                $repo->setRootNodes($roots);
                $repositories[$idmWorkspace->getUuid()] = $repo;
            }
        }

        $accessList->setWorkspaces($repositories);

        return $accessList;

    }

    /**
     * @return RepositoryInterface[]
     */
    public static function listAllRepositories(){

        $excludeShares = ["scope" => IdmWorkspaceScope::ADMIN];
        return self::getStaticRepositories() + self::listRepositoriesWithCriteria($excludeShares, $count);

    }

    /**
     * @param RepositoryInterface[] $repoList
     * @param array $criteria
     * @return RepositoryInterface[] array
     */
    public static function filterRepositoryListWithCriteria($repoList, $criteria)
    {
        $repositories = array();
        $searchableKeys = array("uuid", "parent_uuid", "scope", "display", "accessType", "isTemplate", "slug", "groupPath");
        foreach ($repoList as $repoId => $repoObject) {
            $failOneCriteria = false;
            foreach ($criteria as $key => $value) {
                if (!in_array($key, $searchableKeys)) continue;
                $criteriumOk = false;
                $comp = null;
                if ($key == "uuid") $comp = $repoObject->getId();
                else if ($key == "parent_uuid") $comp = $repoObject->getParentId();
                else if ($key == "scope") $comp = $repoObject->getScope();
                else if ($key == "display") $comp = $repoObject->getDisplay();
                else if ($key == "accessType") $comp = $repoObject->getAccessType();
                else if ($key == "isTemplate") $comp = $repoObject->isTemplate();
                else if ($key == "slug") $comp = $repoObject->getSlug();
                if (is_array($value) && in_array($comp, $value)) {
                    $criteriumOk = true;
                } else if ($value == PYDIO_FILTER_EMPTY && empty($comp)) {
                    $criteriumOk = true;
                } else if ($value == PYDIO_FILTER_NOT_EMPTY && !empty($comp)) {
                    $criteriumOk = true;
                } else if (is_string($value) && strpos($value, "regexp:") === 0 && preg_match(str_replace("regexp:", "", $value), $comp)) {
                    $criteriumOk = true;
                } else if ($value == $comp) {
                    $criteriumOk = true;
                }
                if (!$criteriumOk) {
                    $failOneCriteria = true;
                    break;
                }
            }
            if (!$failOneCriteria) {
                $repositories[$repoId] = $repoObject;
            }
        }
        return $repositories;
    }

    /**
     * @param array $criteria
     * @param $count
     * @return RepositoryInterface[]
     */
    public static function listRepositoriesWithCriteria($criteria, &$count)
    {

        $statics = self::getStaticRepositories();
        $statics = self::filterRepositoryListWithCriteria($statics, $criteria);
        $workspaces = [];

        $count = count($statics);

        // Build search Request based on $criteria
        $api = MicroApi::GetWorkspaceServiceApi();
        $request = new RestSearchWorkspaceRequest();
        $query = new IdmWorkspaceSingleQuery();
        $mainEmpty = true;
        $queries = [];
        if(isSet($criteria["display"])){
            $mainEmpty = false;
            $query->setLabel($criteria["display"]);
        }
        $or = false;
        if(isSet($criteria["uuid"])) {
            if (is_string($criteria)){
                $query->setUuid($criteria["uuid"]);
                $mainEmpty = false;
            } else if(is_array($criteria["uuid"])){
                $or = true;
                foreach($criteria["uuid"] as $uuid){
                    $uQ = new IdmWorkspaceSingleQuery();
                    $uQ->setUuid($uuid);
                    $queries[] = $uQ;
                }
            }
        }
        if(isSet($criteria["slug"])) {
            $mainEmpty = false;
            $query->setSlug($criteria["slug"]);
        }
        if(isSet($criteria["scope"])){
            $mainEmpty = false;
            $query->setScope($criteria["scope"]);
        }
        if(!$mainEmpty){
            $queries[] = $query;
        }
        $request->setQueries($queries);
        $request->setOperation($or ? ServiceOperationType::_OR : ServiceOperationType::_AND);
        $collection = $api->searchWorkspaces($request);
        if($collection->getWorkspaces() != null) {
            $result = $collection->getWorkspaces();
            $toLoad = [];
            foreach($collection->getWorkspaces() as $workspace) {
                $workspaces[$workspace->getUuid()] = self::idmWsToRepository($workspace, true);
                $toLoad[$workspace->getUuid()] = $workspace;
            }
            $rootNodes = self::getInstance()->loadRootsForWorkspaces($toLoad);
            foreach($rootNodes as $wsId => $wsNodes){
                $workspaces[$wsId]->setRootNodes($wsNodes);
            }
        }

        $count = count($statics) + count($workspaces);

        return $statics + $workspaces;

    }

    /**
     * Create a repository object from a config options array
     *
     * @param integer $index
     * @param array $repository
     * @return Repository
     */
    public static function createRepositoryFromArray($index, $repository)
    {
        return self::getInstance()->createRepositoryFromArrayInst($index, $repository);
    }

    /**
     * Add dynamically created repository
     *
     * @param \Pydio\Core\Model\RepositoryInterface $oRepository
     * @return -1|null if error
     */
    public static function addRepository($oRepository)
    {
        return self::getInstance()->addRepositoryInst($oRepository);
    }

    /**
     * @param $idOrAlias
     * @return null|RepositoryInterface
     */
    public static function findRepositoryByIdOrAlias($idOrAlias)
    {
        $repository = RepositoryService::getRepositoryById($idOrAlias);
        if ($repository != null) return $repository;
        $repository = RepositoryService::getRepositoryByAlias($idOrAlias);
        if ($repository != null) return $repository;
        return null;
    }

    /**
     * Get the reserved slugs used for config defined repositories
     * @return array
     */
    public static function reservedSlugsFromConfig()
    {
        $slugs = array();
        $statics = self::getStaticRepositories();
        foreach ($statics as $repo) {
            $slugs[] = $repo->getSlug();
        }
        return $slugs;
    }

    /**
     * Retrieve a repository object
     *
     * @param String $repoId
     * @return RepositoryInterface
     */
    public static function getRepositoryById($repoId)
    {
        return self::getInstance()->getRepositoryByIdInst($repoId);
    }

    /**
     * Retrieve a repository object by its slug
     *
     * @param String $repoAlias
     * @return RepositoryInterface
     */
    public static function getRepositoryByAlias($repoAlias)
    {
        $api = MicroApi::GetWorkspaceServiceApi();
        $request = new RestSearchWorkspaceRequest();
        $query = new IdmWorkspaceSingleQuery();
        $query->setSlug($repoAlias);
        $request->setQueries([$query]);
        $response = $api->searchWorkspaces($request);
        if($response->getWorkspaces() != null) {
            $repo = self::idmWsToRepository($response->getWorkspaces()[0]);
        }
        if (!empty($repo)) return $repo;

        // check default repositories
        return self::getInstance()->getRepositoryByAliasInstDefaults($repoAlias);
    }

    /**
     * Replace a repository by an update one.
     *
     * @param String $oldId
     * @param RepositoryInterface $oRepositoryObject
     * @return mixed
     */
    public static function replaceRepository($oldId, $oRepositoryObject)
    {
        return self::getInstance()->replaceRepositoryInst($oldId, $oRepositoryObject);
    }

    /**
     * Remove a repository using the conf driver implementation
     * @static
     * @param $repoId
     * @return int
     */
    public static function deleteRepository($repoId)
    {
        return self::getInstance()->deleteRepositoryInst($repoId);
    }

    public function __clone()
    {
        trigger_error("Cannot clone me, i'm a singleton!", E_USER_ERROR);
    }


    /**
     * @param IdmWorkspace $ws
     * @return RepositoryInterface
     */
    public static function idmWsToRepository(IdmWorkspace $workspace, $deferRootNodes = false){
        $repo = new Repository($workspace->getUuid(), $workspace->getLabel(), "gateway");
        $repo->setUniqueId($workspace->getUuid());
        $repo->setSlug($workspace->getSlug());
        $repo->setScope($workspace->getScope());
        $repo->setPolicies($workspace->getPolicies());

        if ($repo->getScope() == IdmWorkspaceScope::ADMIN){
            $options["RECYCLE_BIN"] = "recycle_bin";
        }

        $options["API_KEY"] = "gateway";
        $options["SECRET_KEY"] = "gatewaysecret";
        $options["REGION"] = "us-east-1";
        $options["CONTAINER"] = "io";
        $options["SIGNATURE_VERSION"] = "v4";
        $options["API_VERSION"] = "latest";
        $options["STORAGE_URL"] = "core.conf/ENDPOINT_S3_GATEWAY";
        $options["S3_FOLDER_EMPTY_FILE"] = ".pydio";
        $options["PATH"] = "/" . $repo->getSlug();
        $options["META_SOURCES"] = [];
        $options["META_SOURCES"]["metastore.pydio"] = [];
        // Find active Meta plugins
        $plugs = PluginsService::getInstance()->getDetectedPlugins();
        if($plugs["meta"]) {
            foreach($plugs["meta"] as $plug){
                if($plug->isEnabled() && strpos($plug->getId(), "meta.layout") === false){
                    $options["META_SOURCES"][$plug->getId()] = [];
                }
            }
        }
        $jsonAttributes = $workspace->getAttributes();
        if(!empty($jsonAttributes)){
            $attributes = json_decode($jsonAttributes, true);
            if(!empty($attributes)){
                $repo->setIdmAttributes($attributes);
                if(isSet($attributes["plugins"])){
                    foreach($attributes["plugins"] as $metaName => $metaOptions){
                        list($metaType, $metaId) = explode(".", $metaName);
                        if(isSet($plugs[$metaType][$metaId]) && $plugs[$metaType][$metaId]->isEnabled()){
                            $options["META_SOURCES"][$metaName] = $metaOptions;
                        }
                    }
                }
            }
        }
        if(isSet($plugs["index"]["pydio"]) && $plugs["index"]["pydio"]->isEnabled()){
            $options["META_SOURCES"]["index.pydio"] = [];
        }

        $repo->options = $options;
        $repo->setDescription($workspace->getDescription());

        if(!$deferRootNodes) {
            $toLoad = [$workspace->getUuid() => $workspace];
            $rootNodes = self::getInstance()->loadRootsForWorkspaces($toLoad);
            $repo->setRootNodes($rootNodes[$workspace->getUuid()]);
        }
        return $repo;
    }

    /**
     * @return \Swagger\Client\Model\ObjectDataSource[]
     */
    public static function listDataSources(){
        $api = MicroApi::GetConfigServiceApi();
        $sources = $api->listDataSources();
        if ($sources->getDataSources() === null){
            return [];
        }
        return $sources->getDataSources();
    }

    /**
     * @param $options
     * @throws ApiException
     */
    public static function putDataSourceFromOptions($options){

        $dataSource = new RestDataSource();
        $dataSource->setDsName($options["DATASOURCE_NAME"]);
        $dataSource->setLocalFolder($options["DATASOURCE_PATH"]);
        $dataSource->setType(RestDataSourceType::LOCAL);
        $dataSource->setLocalMacOs($options["DATASOURCE_NORMALIZE"]);
        $dataSource->setWatch($options["DATASOURCE_WATCH"]);
        if($options["DATASOURCE_ENCRYPT"] === "master"){
            $dataSource->setEncryptionMode(RestEncryptionMode::MASTER);
        } else {
            $dataSource->setEncryptionMode(RestEncryptionMode::CLEAR);
        }
        $port = intval($options["DATASOURCE_OBJECTS_PORT"]);
        $existingSources = self::listDataSources();
        foreach($existingSources as $source){
            if($source->getObjectsPort() === $port && $source->getDsName() !== $dataSource->getDsName()){
                $port ++;
            }
        }
        $dataSource->setObjectsPort($port);
        $api = MicroApi::GetConfigServiceApi();
        $api->putDataSource($dataSource->getDsName(), $dataSource);

    }

    /**
     * @param $dsName
     * @throws ApiException
     * @return \Swagger\Client\Model\RestDeleteDataSourceResponse
     */
    public static function deleteDataSource($dsName){
        $api = MicroApi::GetConfigServiceApi();
        return $api->deleteDataSource($dsName);
    }


    /**
     * PRIVATE INSTANCE IMPLEMENTATIONS
     */
    /**
     * See static method
     * @param $repoId
     * @return RepositoryInterface|null
     */
    private function getRepositoryByIdInst($repoId)
    {
        if (empty($repoId)) {
            return null;
        }
        if (isSet($this->cache["REPOSITORIES"]) && isSet($this->cache["REPOSITORIES"][$repoId]) && !empty($this->cache["REPOSITORIES"][$repoId])) {
            return $this->cache["REPOSITORIES"][$repoId];
        }
        // Search first in default repositories
        $statics = self::getStaticRepositories();
        if (isSet($statics[$repoId])) {
            $repo = $statics[$repoId];
            $this->cache["REPOSITORIES"][$repoId] = $test;
            return $repo;
        }
        $api = MicroApi::GetWorkspaceServiceApi();
        $request = new RestSearchWorkspaceRequest();
        $query = new IdmWorkspaceSingleQuery();
        $query->setUuid($repoId . "");
        $request->setQueries([$query]);
        $response = $api->searchWorkspaces($request);
        if($response->getWorkspaces() != null) {
            $test = self::idmWsToRepository($response->getWorkspaces()[0]);
        }
        if(!empty($test)) {
            $this->cache["REPOSITORIES"][$repoId] = $test;
            return $test;
        }
        $hookedRepo = null;
        $args = array($repoId, &$hookedRepo);
        Controller::applyIncludeHook("repository.search", $args);
        if($hookedRepo !== null){
            return $hookedRepo;
        }
        return null;
    }



    /**
     * See static method
     * @param string $index
     * @param array $repository
     * @return Repository
     */
    private function createRepositoryFromArrayInst($index, $repository)
    {
        $repo = new Repository($index, $repository["DISPLAY"], $repository["DRIVER"]);
        if (isSet($repository["DISPLAY_ID"])) {
            $repo->setDisplayStringId($repository["DISPLAY_ID"]);
        }
        if (isSet($repository["DESCRIPTION_ID"])) {
            $repo->setDescription($repository["DESCRIPTION_ID"]);
        }
        if (isSet($repository["PYDIO_SLUG"])) {
            $repo->setSlug($repository["PYDIO_SLUG"]);
        }
        if (isSet($repository["IS_TEMPLATE"]) && $repository["IS_TEMPLATE"]) {
            $repo->isTemplate = true;
            $repo->uuid = $index;
        }
        if (array_key_exists("DRIVER_OPTIONS", $repository) && is_array($repository["DRIVER_OPTIONS"])) {
            foreach ($repository["DRIVER_OPTIONS"] as $oName=>$oValue) {
                $repo->addOption($oName, $oValue);
            }
        }
        // BACKWARD COMPATIBILITY!
        if (array_key_exists("PATH", $repository)) {
            $repo->addOption("PATH", $repository["PATH"]);
            $repo->addOption("CREATE", intval($repository["CREATE"]));
            $repo->addOption("RECYCLE_BIN", $repository["RECYCLE_BIN"]);
        }
        $repo->setScope(IdmWorkspaceScope::ADMIN);
        return $repo;

    }

    /**
     * @param Repository|\Pydio\Core\Model\RepositoryInterface $oRepository
     * @return -1|null on error
     */
    private function addRepositoryInst($oRepository)
    {
        $api = MicroApi::GetWorkspaceServiceApi();
        $ws = new IdmWorkspace();
        $ws->setSlug($oRepository->getSlug());
        $ws->setUuid($oRepository->getId());
        $ws->setLabel($oRepository->getDisplay());
        $ws->setDescription($oRepository->getDescription());
        $ws->setScope($oRepository->getScope());
        $ws->setPolicies($oRepository->getPolicies());
        $ws->setAttributes(json_encode($oRepository->getIdmAttributes()));
        $api->putWorkspace($oRepository->getSlug(), $ws);

        $this->storeRootsAsACL($oRepository, false);

        Logger::info(__CLASS__,"Create Repository", array("repo_name"=>$oRepository->getDisplay()));
        return null;
    }

    /**
     * See static method
     * @param $repoAlias
     * @return RepositoryInterface|null
     */
    private function getRepositoryByAliasInstDefaults($repoAlias)
    {
        $conf = self::getStaticRepositories();
        foreach ($conf as $repoId => $repo) {
            if ($repo->getSlug() === $repoAlias) {
                return $repo;
            }
        }
        return null;
    }

    /**
     * @param $repositoryObject Repository
     * @throws ApiException
     */
    public static function updateRepositoryPolicies($repositoryObject){
        $api = MicroApi::GetWorkspaceServiceApi();
        $ws = new IdmWorkspace();
        $ws->setSlug($repositoryObject->getSlug());
        $ws->setUuid($repositoryObject->getId());
        $ws->setLabel($repositoryObject->getDisplay());
        $ws->setDescription($repositoryObject->getDescription());
        $ws->setScope($repositoryObject->getScope());
        $ws->setAttributes(json_encode($repositoryObject->getIdmAttributes()));
        $ws->setPolicies($repositoryObject->getPolicies());
        $api->putWorkspace($repositoryObject->getSlug(), $ws);
    }

    /**
     * See static method
     * @param string $oldId
     * @param RepositoryInterface $oRepositoryObject
     * @return int
     */
    private function replaceRepositoryInst($oldId, $oRepositoryObject)
    {
        $api = MicroApi::GetWorkspaceServiceApi();
        $ws = new IdmWorkspace();
        $ws->setSlug($oRepositoryObject->getSlug());
        $ws->setUuid($oRepositoryObject->getId());
        $ws->setLabel($oRepositoryObject->getDisplay());
        $ws->setDescription($oRepositoryObject->getDescription());
        $ws->setScope($oRepositoryObject->getScope());
        $ws->setAttributes(json_encode($oRepositoryObject->getIdmAttributes()));
        $ws->setPolicies($oRepositoryObject->getPolicies());
        $api->putWorkspace($oRepositoryObject->getSlug(), $ws);

        $this->storeRootsAsACL($oRepositoryObject, true);

        Logger::info(__CLASS__,"Edit Repository", array("repo_name"=>$oRepositoryObject->getDisplay()));
        return 0;
    }

    private function storeRootsAsACL(RepositoryInterface $oRepositoryObject, $replace = false) {
        // Store PATH as an ACL
        $rootNodes = $oRepositoryObject->getRootNodes($replace);

        $aclApi = MicroApi::GetAclServiceApi();
        if ($replace) {
            // Delete current Root Node ACLs
            $acl = new IdmACL();
            $acl->setWorkspaceId($oRepositoryObject->getId());
            $idmAction = new IdmACLAction();
            $idmAction->setName("workspace-path");
            $acl->setAction($idmAction);
            $aclApi->deleteAcl($acl);

            // Load all existings acl's that are not workspace-path
            $aclQ = new IdmACLSingleQuery();
            $aclQ->setWorkspaceIDs([$oRepositoryObject->getId()]);
            $request = new RestSearchACLRequest();
            $request->setQueries([$aclQ]);
            $existingResp = $aclApi->searchAcls($request);
            $rolesToReassign = [];
            if($existingResp->getAcLs() != null) {
                foreach ($existingResp->getAcLs() as $acl){
                    if($acl->getRoleId() === -1 || $acl->getNodeId() === -1) continue;
                    $roleId = $acl->getRoleId();
                    $oldNodeId = $acl->getNodeId();
                    $action = $acl->getAction();
                    if(!isSet($rolesToReassign[$roleId])) {
                        $rolesToReassign[$roleId] = [];
                    }
                    $rolesToReassign[$roleId][$action->getName()] = $action->getValue();
                    $aclApi->deleteAcl($acl);
                }
            }

        }

        foreach ($rootNodes as $rootNode){
            $acl = new IdmACL();

            $acl->setWorkspaceId($oRepositoryObject->getId());
            $acl->setNodeId($rootNode->getUuid());
            $idmAction = new IdmACLAction();
            $idmAction->setName("workspace-path");
            $path = $rootNode->getPath();
            if (empty($path)) {
                $path = "uuid:" .$rootNode->getUuid();
            }
            $idmAction->setValue($path);

            $acl->setAction($idmAction);
            $aclApi->putAcl($acl);

            if($replace && count($rolesToReassign)) {
                foreach($rolesToReassign as $roleId => $actions){
                    foreach($actions as $actionName => $actionValue){
                        $acl = new IdmACL();
                        $acl->setRoleId($roleId);
                        $acl->setWorkspaceId($oRepositoryObject->getId());
                        $acl->setNodeId($rootNode->getUuid());
                        $act = new IdmACLAction();
                        $act->setName($actionName);
                        $act->setValue($actionValue);
                        $acl->setAction($act);
                        $aclApi->putAcl($acl);
                    }
                }
            }

        }

    }

    /**
     * @param IdmWorkspace[] $workspaces
     * @return RepositoryRoot[]
     */
    private function loadRootsForWorkspaces($workspaces) {
        $nodes = [];
        $cached = [];
        foreach($workspaces as $wsUuid => $workspace) {
            $nodes[$wsUuid] = [];
            if(isSet($this->cache["WS_ROOTS"]) && isSet($this->cache["WS_ROOTS"][$wsUuid]) && is_array($this->cache["WS_ROOTS"][$wsUuid])) {
                $cached[$wsUuid] = $this->cache["WS_ROOTS"][$wsUuid];
            }
        }
        if(count($cached) == count($workspaces)) {
            return $cached;
        } else if(count($cached) > 0) {
            // Search not cached, and add cached ones afterward
            foreach(array_keys($cached) as $cachedId) {
                unset($workspaces[$cachedId]);
            }
        }
        if(!isSet($this->cache["WS_ROOTS"])){
            $this->cache["WS_ROOTS"] = [];
        }

        $aclApi = MicroApi::GetAclServiceApi();
        $req = new RestSearchACLRequest();
        $query = new IdmACLSingleQuery();
        $idmAction = new IdmACLAction();
        $idmAction->setName("workspace-path");
        $query->setActions([$idmAction]);
        $query->setWorkspaceIDs(array_keys($workspaces));
        $req->setQueries([$query]);
        $col = $aclApi->searchAcls($req);

        if($col->getAcLs() != null){
            foreach($col->getAcLs() as $acl){
                $nodeId = $acl->getNodeId();
                $wsId = $acl->getWorkspaceId();
                $path = $acl->getAction()->getValue();
                $rootNode = new RepositoryRoot();
                $rootNode->setPath($acl->getAction()->getValue());
                $rootNode->setUuid($acl->getNodeId());
                $nodes[$wsId][] = $rootNode;
            }
        }
        foreach($nodes as $wsId => $wsNodes){
            $this->cache["WS_ROOTS"][$wsId] = $wsNodes;
        }
        if(count($cached) > 0) {
            $nodes = $nodes + $cached;
        }
        return $nodes;
    }

    /**
     * See static method
     * @param $repoId
     * @return int
     */
    private function deleteRepositoryInst($repoId)
    {
        $repoObject = $this->getRepositoryByIdInst($repoId);
        if ($repoObject == null) {
            throw new PydioException("Cannot find workspace with id $repoId");
        }
        $api = MicroApi::GetWorkspaceServiceApi();
        $api->deleteWorkspace($repoObject->getSlug());
        Logger::info(__CLASS__,"Delete Repository", array("repo_id"=>$repoId));
        return 0;
    }



}
