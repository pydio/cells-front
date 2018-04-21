<?php
/*
 * Copyright 2007-2017 Charles du Jeu <contact (at) cdujeu.me>
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
namespace Pydio\Access\Metastore\Implementation;

use GuzzleHttp\Client;
use Pydio\Access\Core\Model\Node;

use Pydio\Access\Meta\Core\AbstractMetaSource;
use Pydio\Access\Metastore\Core\IMetaStoreProvider;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Log\Core\Logger;
use Swagger\Client\Model\RestMetaCollection;
use Swagger\Client\Model\RestMetadata;
use Swagger\Client\Model\RestMetaNamespaceRequest;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Class PydioMetaStore
 * @package Pydio\Access\Metastore\Implementation
 */
class PydioMetaStore extends AbstractMetaSource implements IMetaStoreProvider
{
    protected $rootPath;

    /**
     * @var NodeProviderClient
     */
    protected $client;

    public function performChecks(){
    }

    /**
     * @abstract
     * @return bool
     */
    public function inherentMetaMove()
    {
        return true;
    }

    /**
     * @abstract
     * @param \Pydio\Access\Core\Model\Node $node
     * @param String $nameSpace
     * @param array $metaData
     * @param bool $private
     * @param int $scope
     */
    public function setMetadata($node, $nameSpace, $metaData, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY)
    {
        $api = MicroApi::GetMetaServiceApi();
        $path = $node->getRepository()->getSlug() . $node->getPath();
        $metaCollection = new RestMetaCollection();
        $metadatas = new RestMetadata();
        $metadatas->setNamespace($nameSpace);
        $metadatas->setJsonMeta(json_encode($metaData));
        $metaCollection->setMetadatas([$metadatas]);
        $api->setMeta($path, $metaCollection);

        $node->metadataLoaded = false;
    }

    /**
     * @abstract
     * @param \Pydio\Access\Core\Model\Node $node
     * @param String $nameSpace
     * @param bool $private
     * @param int $scope
     * @return array|void
     * @throws \Exception
     */
    public function removeMetadata($node, $nameSpace, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY)
    {

        $api = MicroApi::GetMetaServiceApi();
        $path = $node->getRepository()->getSlug() . $node->getPath();

        // Can be used to get only a specific namespace
        $nsRequest = new RestMetaNamespaceRequest();
        $nsRequest->setNamespace([$nameSpace]);
        $api->deleteMeta($path, $nsRequest);

    }

    /**
     * @abstract
     * @param Node $node
     * @param String $nameSpace
     * @param bool|String $private
     * @param int $scope
     * @return array()
     */
    public function retrieveMetadata($node, $nameSpace, $private = false, $scope=PYDIO_METADATA_SCOPE_REPOSITORY)
    {
        $this->enrichNode($node);
        $data = $node->$nameSpace;
        if (!empty($data)) {
            return $data;
        }else {
            return [];
        }
        $path = $node->getPath();

    }

    /**
     * @param \Pydio\Access\Core\Model\Node $node
     * @return void
     */
    public function enrichNode(&$node) {

        if (!empty($node->metadata["metadataLoaded"]) && $node->metadata["metadataLoaded"] === true ) {
            return;
        }
        $api = MicroApi::GetMetaServiceApi();
        $path = $node->getRepository()->getSlug() . $node->getPath();

        // Can be used to get only a specific namespace
        $nsRequest = new RestMetaNamespaceRequest();

        $treeNode = $api->getMeta($path, $nsRequest);
        $receivedNode = Node::fromApiNode($node->getContext(), $treeNode);
        $node->mergeMetadata($receivedNode->getNodeInfoMeta(), true);

        $nameMeta = $node->name;
        if (!empty($nameMeta)){
            $node->setLabel($nameMeta);
        }
        $node->metadataLoaded = true;

    }
}
