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

namespace Pydio\Action\Compression;

use Exception;
use Phar;
use PharData;
use Pydio\Access\Core\MetaStreamWrapper;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;

use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Services\LocaleService;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Utils\Vars\InputFilter;
use Pydio\Core\Utils\Vars\PathUtils;

use Pydio\Core\PluginFramework\Plugin;

use Pydio\Access\Core\Model\NodesDiff;
use Pydio\Tasks\Task;
use Pydio\Tasks\TaskService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Swagger\Client\Model\RestUserJobRequest;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Plugin to compress to TAR or TAR.GZ or TAR.BZ2... He can also extract your archives
 * @package AjaXplorer_Plugins
 * @subpackage Action
 */
class PluginCompression extends Plugin
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $requestInterface
     * @param \Psr\Http\Message\ResponseInterface $responseInterface
     * @throws Exception
     * @throws PydioException
     * @throws \Pydio\Core\Exception\ActionNotFoundException
     * @throws \Pydio\Core\Exception\AuthRequiredException
     */
    public function receiveAction(\Psr\Http\Message\ServerRequestInterface &$requestInterface, \Psr\Http\Message\ResponseInterface &$responseInterface)
    {
        /** @var \Pydio\Core\Model\ContextInterface $ctx */
        $ctx = $requestInterface->getAttribute("ctx");

        $httpVars = $requestInterface->getParsedBody();
        $messages = LocaleService::getMessages();

        $userSelection = UserSelection::fromContext($ctx, $httpVars);
        $nodes = $userSelection->buildNodes();
        $currentDirPath = PathUtils::forwardSlashDirname($userSelection->getUniqueNode()->getPath());
        $currentDirPath = rtrim($currentDirPath, "/") . "/";
        $currentDirUrl = $userSelection->currentBaseUrl() . $currentDirPath;

        $serializableStream = new \Pydio\Core\Http\Response\SerializableResponseStream();
        $responseInterface = $responseInterface->withBody($serializableStream);

        $repoSlug = $ctx->getRepository()->getSlug();

        $api = MicroApi::GetJobsServiceApi();
        $request = new RestUserJobRequest();

        switch ($requestInterface->getAttribute("action")) {

            case "compression":

                $archiveName = InputFilter::decodeSecureMagic($httpVars["archive_name"], InputFilter::SANITIZE_FILENAME);
                $archiveFormat = InputFilter::sanitize($httpVars["type_archive"], InputFilter::SANITIZE_ALPHANUM);
                $selectedPathes = [];
                foreach($nodes as $node){
                    $selectedPathes[] = $repoSlug . $node->getPath();
                }

                $request->setJobName("compress");
                $request->setJsonParameters(json_encode([
                    "nodes"         => $selectedPathes,
                    "archiveName"   => "",
                    "format"        => $archiveFormat
                ]));
                $response = $api->userCreateJob("compress", $request);

                break;

            case "extraction":

                $archivePath = $userSelection->getUniqueNode()->getPath();
                $extension = pathinfo($archivePath, PATHINFO_EXTENSION);
                if ($extension == "gz") {
                    $archiveFormat = "tar.gz";
                } else if ($extension == "tar") {
                    $archiveFormat = "tar";
                } else if ($extension == "zip") {
                    $archiveFormat = "zip";
                } else {
                    throw new PydioException("Unsupported format");
                }

                $request->setJobName("extract");
                $request->setJsonParameters(json_encode([
                    "node"      => $repoSlug . $archivePath,
                    "target"    => "",
                    "format"    => $archiveFormat
                ]));
                $response = $api->userCreateJob("extract", $request);

                break;

            default:
                break;
        }
    }

    /**
     * @param Task $task
     * @param string $message
     * @param integer $taskStatus
     * @param null|integer $progress
     */
    private function operationStatus($task, $message, $taskStatus, $progress = null)
    {
        $task->setStatusMessage($message);
        $task->setStatus($taskStatus);
        if ($progress != null) {
            $task->setProgress($progress);
        }
        TaskService::getInstance()->updateTask($task);
    }
}
