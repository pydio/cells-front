<?php
/*
 * Copyright 2007-2016 Abstrium <contact (at) pydio.com>
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

use Pydio\Core\Exception\NoActiveWorkspaceException;
use Pydio\Core\Exception\WorkspaceForbiddenException;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class WorkspacesAccess
 * @package Pydio\Core\Model
 */
class AccessibleWorkspaces {

    /**
     * @var array : wsId => rightString
     */
    private $acls = [];

    /**
     * @var RepositoryInterface[]
     */
    private $workspaces = [];

    public function __construct($acls = null){
        if ($acls !== null){
            $this->acls = $acls;
        }
    }

    /**
     * @param $wsId
     * @return bool
     */
    public function hasWorkspace($wsId){
        return $this->canRead($wsId) || $this->canWrite($wsId);
    }

    /**
     * @param string $wsId
     * @return bool
     */
    public function canRead($wsId){
        return array_key_exists($wsId, $this->acls) && strpos($this->acls[$wsId], "read") !== false;
    }

    /**
     * @param string $wsId
     * @return bool
     */
    public function canWrite($wsId){
        return array_key_exists($wsId, $this->acls) && strpos($this->acls[$wsId], "write") !== false;
    }

    /**
     * @return RepositoryInterface[]
     */
    public function getWorkspaces()
    {
        return $this->workspaces;
    }

    /**
     * @param RepositoryInterface[] $workspaces
     */
    public function setWorkspaces($workspaces)
    {
        $this->workspaces = $workspaces;
    }

    /**
     * @return bool
     */
    public function noActiveWorkspaces(){
        return empty($this->workspaces);
    }

    /**
     * @return RepositoryInterface
     * @throws NoActiveWorkspaceException
     */
    public function firstWorkspace(){
        if(empty($this->workspaces)) {
            throw new NoActiveWorkspaceException();
        }
        $k = array_shift(array_keys($this->workspaces));
        return $this->workspaces[$k];
    }

    /**
     * @param $wsId
     * @return RepositoryInterface
     * @throws WorkspaceForbiddenException
     */
    public function workspaceById($wsId){
        if (array_key_exists($wsId, $this->workspaces)) {
            return $this->workspaces[$wsId];
        } else {
            // Try to find by slug
            foreach($this->workspaces as $id => $workspace){
                if($workspace->getSlug() === $wsId) return $workspace;
            }
        }
        throw new WorkspaceForbiddenException($wsId);
    }

}