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
namespace Pydio\Editor\Image;

use Pydio\Access\Core\MetaStreamWrapper;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;

use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Services\LocalCache;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Services\RepositoryService;
use Pydio\Core\Utils\Vars\StatHelper;
use Pydio\Core\PluginFramework\Plugin;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Class ImagePreviewer
 * Generate an image thumbnail and send the thumb/full version to the browser
 * @package Pydio\Editor\Image
 */
class ImagePreviewer extends Plugin
{
    private $currentDimension;

    /**
     * @param $action
     * @param $httpVars
     * @param $filesVars
     * @param ContextInterface $contextInterface
     * @throws \Exception
     */
    public function switchAction($action, $httpVars, $filesVars, ContextInterface $contextInterface)
    {
        if (!isSet($this->pluginConf)) {
            $this->pluginConf = array("GENERATE_THUMBNAIL"=>false);
        }
        $selection = UserSelection::fromContext($contextInterface, $httpVars);
        $destStreamURL = $selection->currentBaseUrl();
        $selection->buildNodes();

        session_write_close();
        if ($action == "preview_data_proxy") {
            $node = $selection->getUniqueNode();
            if (!$node->exists()) {
                header("Content-Type: ". StatHelper::getImageMimeType(basename($node->getPath())) ."; name=\"".basename($node->getPath())."\"");
                header("Content-Length: 0");
                return;
            }
            //$this->logInfo('Preview', 'Preview content of '.$file, array("files" =>$selection->getUniqueFile()));

            //$node = new Node($destStreamURL.$file);
            if(isSet($httpVars["get_thumb"]) && $httpVars["get_thumb"] == "true"){
                $node->loadNodeInfo(false, false, true);
                $uuid = $node->getUuid();
                if (!empty($uuid)){
                    $file = $uuid . "-512.jpg";
                    $userId = $contextInterface->getUser()->getId();
                    $tNode = new Node("pydio://$userId@pydiogateway/pydio-thumbstore/".$file);
                    if (file_exists($tNode->getUrl())) {
                        $node = $tNode;
                    } else {
                        // Not created yet ?
                        header("Content-Type: ". StatHelper::getImageMimeType(basename($node->getPath())) ."; name=\"".basename($node->getPath())."\"");
                        header("Content-Length: 0");
                        return;
                    }
                } else {
                    // No Uuid found!
                    header("Content-Type: ". StatHelper::getImageMimeType(basename($node->getPath())) ."; name=\"".basename($node->getPath())."\"");
                    header("Content-Length: 0");
                    return;
                }
            }

            $fp = fopen($node->getUrl(), "r");
            $stat = fstat($fp);
            $filesize = $stat["size"];
            header("Content-Type: ". StatHelper::getImageMimeType(basename($file)) ."; name=\"".basename($file)."\"");
            header("Content-Length: ".$filesize);
            header('Cache-Control: public');
            header("Pragma:");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()-10000) . " GMT");
            header("Expires: " . gmdate("D, d M Y H:i:s", time()+5*24*3600) . " GMT");

            $stream = fopen("php://output", "a");
            if(!is_resource($fp)) return;
            while (!feof($fp)) {
                if(!ini_get("safe_mode")) @set_time_limit(60);
                $data = fread($fp, 4096);
                fwrite($stream, $data, strlen($data));
            }
            fclose($fp);
            fflush($stream);
            fclose($stream);

        }
    }


}
