<?php
/*
 * Copyright 2007-2017 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
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
 * The latest code can be found at <https://pydio.com>.
 */
namespace Pydio\Access\Metastore\Core;

use Pydio\Access\Core\AbstractAccessDriver;
use Pydio\Access\Core\IPydioWrapperProvider;
use Pydio\Access\Core\Model\Node;
use Pydio\Core\Model\ContextInterface;

defined('PYDIO_EXEC') or die( 'Access not allowed');
define('PYDIO_METADATA_SHAREDUSER', 'PYDIO_METADATA_SHAREDUSER');
define('PYDIO_METADATA_ALLUSERS', 'PYDIO_METADATA_ALLUSERS');

define('PYDIO_METADATA_SCOPE_GLOBAL', 1);
define('PYDIO_METADATA_SCOPE_REPOSITORY', 2);
/**
 * Metadata interface, must be implemented by Metastore plugins.
 *
 * @package Pydio\Access\Metastore\Core;
 */
interface IMetaStoreProvider
{

    /**
     * @param ContextInterface $ctx
     * @param AbstractAccessDriver|IPydioWrapperProvider $accessDriver
     */
    public function initMeta(ContextInterface $ctx, AbstractAccessDriver $accessDriver);

    /**
     * @abstract
     * @return bool
     */
    public function inherentMetaMove();

    /**
     * @abstract
     * @param Node $node The node where to set metadata
     * @param String $nameSpace The metadata namespace (generally depending on the plugin)
     * @param array $metaData Metadata to store
     * @param bool $private Either false (will store under a shared user name) or true (will store under the node user name).
     * @param int $scope
     * Either PYDIO_METADATA_SCOPE_REPOSITORY (this metadata is available only inside the current repository)
     * or PYDIO_METADATA_SCOPE_GLOBAL (metadata available globally).
     */
    public function setMetadata($node, $nameSpace, $metaData, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY);
    
    /**
     *
     * @abstract
     * @param Node $node The node to inspect
     * @param String $nameSpace The metadata namespace (generally depending on the plugin)
     * @param bool $private Either false (will store under a shared user name) or true (will store under the node user name).
     * @param int $scope
     * Either PYDIO_METADATA_SCOPE_REPOSITORY (this metadata is available only inside the current repository)
     * or PYDIO_METADATA_SCOPE_GLOBAL (metadata available globally).
     * @return array Metadata or empty array.
     */
    public function removeMetadata($node, $nameSpace, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY);

    /**
     * @abstract
     * @param Node $node
     * @param String $nameSpace
     * @param bool|String $private
     * Either false (will store under a shared user name), true (will store under the node user name),
     * or PYDIO_METADATA_ALL_USERS (will retrieve and merge all metadata from all users).
     * @param int $scope
     */
    public function retrieveMetadata($node, $nameSpace, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY);

    /**
     * @param Node $node Load all metadatas on this node, merging the global, shared and private ones.
     * @return void
     */
    public function enrichNode(&$node);

}
