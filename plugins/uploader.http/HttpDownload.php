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
namespace Pydio\Uploader\Processor;

use Pydio\Access\Core\AbstractAccessDriver;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\PluginFramework\PluginsService;
use Pydio\Core\Services\LocaleService;
use Pydio\Core\Utils\Vars\InputFilter;
use Pydio\Core\Utils\Vars\StatHelper;

use Pydio\Core\PluginFramework\Plugin;
use Pydio\Core\Controller\UnixProcess;
use Pydio\Tasks\Task;
use Pydio\Tasks\TaskService;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Remote files downloader
 * @package AjaXplorer_Plugins
 * @subpackage Downloader
 */
class HttpDownload extends Plugin
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return bool
     * @throws \Exception
     * @throws \Pydio\Core\Exception\PydioException
     */
    public function switchAction(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        //$this->logInfo("DL file", $httpVars);
        $httpVars = $request->getParsedBody();
        $action = $request->getAttribute("action");
        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        $userSelection = UserSelection::fromContext($request->getAttribute("ctx"), $httpVars);
        $dir = InputFilter::decodeSecureMagic($httpVars["dir"]);
        $currentDirUrl = $userSelection->currentBaseUrl().$dir."/";
        $dlURL = null;
        if (isSet($httpVars["file"])) {
            $parts = parse_url($httpVars["file"]);
            $getPath = $parts["path"];
            $basename = basename($getPath);
            //$dlURL = $httpVars["file"];
        }else if (isSet($httpVars["dlfile"])) {
            $dlFile = $userSelection->currentBaseUrl(). InputFilter::decodeSecureMagic($httpVars["dlfile"]);
            $realFile = file_get_contents($dlFile);
            if(empty($realFile)) throw new \Exception("cannot find file $dlFile for download");
            $parts = parse_url($realFile);
            $getPath = $parts["path"];
            $basename = basename($getPath);
            //$dlURL = $realFile;
        }else{
            throw new \Exception("Missing argument, either file or dlfile");
        }
        /** @var AbstractAccessDriver $fsDriver */
        $fsDriver = PluginsService::getInstance($ctx)->getUniqueActivePluginForType("access");
        $fsDriver->filterUserSelectionToHidden($ctx, array($basename));

        switch ($action) {
            case "external_download":

                throw new PydioException("Not Implemented - Use Backend Task");

            break;
            case "update_dl_data":

                throw new PydioException("Not Implemented - Use Backend Task");

            break;
            case "stop_dl":

                throw new PydioException("Not Implemented - Use Backend Task");

            break;
            default:
            break;
        }

        return false;
    }



    /**
     * @param Node $node
     */
    public function detectDLParts(&$node)
    {
        if (!preg_match("/\.dlpart$/i",$node->getUrl())) {
            return;
        }
        $basename = basename($node->getUrl());
        $newName = "__".str_replace(".dlpart", ".ser", $basename);
        $hidFile = str_replace($basename, $newName, $node->getUrl());
        if (is_file($hidFile)) {
            $data = unserialize(file_get_contents($hidFile));
            if ($data["totalSize"] != -1) {
                $node->target_bytesize = $data["totalSize"];
                $node->target_filesize = StatHelper::roundSize($data["totalSize"]);
                $node->process_stoppable = (isSet($data["pid"])?"true":"false");
            }
        }
    }
}
