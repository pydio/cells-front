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
namespace Pydio\Access\Meta\Lock;

use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\NodesDiff;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Access\Meta\Core\AbstractMetaSource;
use Pydio\Access\Metastore\Core\IMetaStoreProvider;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Http\Response\SerializableResponseStream;
use Pydio\Core\Model\ContextInterface;
use Swagger\Client\Model\IdmACL;
use Swagger\Client\Model\IdmACLAction;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Locks a folder manually
 * @package AjaXplorer_Plugins
 * @subpackage Meta
 */
class SimpleLockManager extends AbstractMetaSource
{
    const METADATA_LOCK_NAMESPACE = "simple_lock";
    /**
    * @var IMetaStoreProvider
    */
    protected $metaStore;


    /**
     * @param \Psr\Http\Message\ServerRequestInterface $requestInterface
     * @param \Psr\Http\Message\ResponseInterface $responseInterface
     * @throws PydioException
     */
    public function applyChangeLock(\Psr\Http\Message\ServerRequestInterface $requestInterface, \Psr\Http\Message\ResponseInterface &$responseInterface)
    {
        $httpVars = $requestInterface->getParsedBody();
        /** @var ContextInterface $ctx */
        $ctx = $requestInterface->getAttribute("ctx");

        $repo = $ctx->getRepository();
        $user = $ctx->getUser();
        $selection = UserSelection::fromContext($ctx, $httpVars);
        $unlock = (isSet($httpVars["unlock"])?true:false);
        $node = $selection->getUniqueNode();
        $node->loadNodeInfo();

        $api = MicroApi::GetAclServiceApi();
        $acl = (new IdmACL())
            ->setNodeId($node->getUuid())
            ->setAction((new IdmACLAction())->setName("content_lock")->setValue($user->getId()));
        if($unlock) {
            $response = $api->deleteAcl($acl);
        } else {
            $response = $api->putAcl($acl);
        }

        $node->loadNodeInfo(true);
        $x = new SerializableResponseStream();
        $diff = new NodesDiff();
        $diff->update($selection->getUniqueNode());
        $x->addChunk($diff);
        $responseInterface = $responseInterface->withBody($x);
    }

    /**
     * @param Node $node
     */
    public function processLockMeta($node)
    {
        $lockUser = null;
        if($node->content_lock){
            $lockUser = $node->content_lock;
        }
        // lock should be in metadata ?
        if(!empty($lockUser)){
            if ($lockUser != $node->getContext()->getUser()->getId()) {
                $displayName = $lockUser;
                $node->setLabel($node->getLabel() . " (locked by ".$displayName.")");
                $node->mergeMetadata(array(
                    "sl_locked" => "true",
                    "overlay_class" => "icon-lock"
                ), true);
            } else {
                $node->setLabel($node->getLabel() . " (locked by you)");
                $node->mergeMetadata(array(
                    "sl_locked" => "true",
                    "sl_mylock" => "true",
                    "overlay_class" => "icon-lock"
                ), true);
            }
        }
    }

}
