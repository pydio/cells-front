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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Core\Model\ContextInterface;

use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\LocaleService;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Utils\Vars\InputFilter;

use Pydio\Core\PluginFramework\Plugin;

use GuzzleHttp\Client;
use Pydio\Core\Utils\Vars\UrlUtils;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Class PixlrEditor
 * Uses Pixlr.com service to edit images online.
 * @package Pydio\Editor\Image
 */
class PixlrEditor extends Plugin
{

    /**
     * @var Client
     */
    private $client;

    // Plugin initialisation
    /**
     * @param ContextInterface $context
     */
    public function init(\Pydio\Core\Model\ContextInterface $ctx, $options = []) {

        parent::init($ctx, $options);

        $this->client = new Client([
            'base_url' => "https://pixlr.com/editor/"
        ]);
    }

    // Main controller function
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws PydioException
     * @throws \Exception
     */
    public function switchAction(ServerRequestInterface &$request, ResponseInterface &$response) {

        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        $action = $request->getAttribute("action");
        $httpVars = $request->getParsedBody();

        $selection = UserSelection::fromContext($ctx, $httpVars);
        $selectedNode = $selection->getUniqueNode();
        $selectedNodeUrl = $selectedNode->getUrl();

        if ($action == "post_to_server") {
            if(!is_writeable($selectedNodeUrl)){
                header("Location:". ApplicationState::detectServerURL(true) ."/?get_action=pixlr_error");
                return;
            }

            // Backward compat
            if(strpos($httpVars["file"], "base64encoded:") !== 0){
                $legacyFilePath = InputFilter::decodeSecureMagic(base64_decode($httpVars["file"]));
                $selectedNode = new Node($selection->currentBaseUrl().$legacyFilePath);
                $selectedNodeUrl = $selectedNode->getUrl();
            }

            $target = rtrim(base64_decode($httpVars["parent_url"]), '/');
            $this->logInfo('Preview', 'Sending content of '.$selectedNodeUrl.' to Pixlr server.', array("files" => $selectedNodeUrl));
            Controller::applyHook("node.read", array($selectedNode));

            $saveTarget = $target."/?get_action=pixlr_save";
            if ($this->getContextualOption($ctx, "CHECK_SECURITY_TOKEN")) {
                $saveTarget .= "&securityToken=". md5($httpVars["secure_token"]);
            }
            $saveTarget = urlencode($saveTarget);

            $type = pathinfo($selectedNodeUrl, PATHINFO_EXTENSION);
            $data = file_get_contents($selectedNodeUrl);
            $rawData = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $params = [
                'allow_redirects' => false,
                'body' => [
                    "referrer"   => "Pydio",
                    "method"     => "get",
                    "type"       => $type,
                    "loc"        => LocaleService::getLanguage(),
                    "target"     => $saveTarget,
                    "exit"       => urlencode($target."/?get_action=pixlr_close"),
                    "title"      => urlencode(basename($selectedNodeUrl)),
                    "locktarget" => "false",
                    "locktitle"  => "true",
                    "locktype"   => "source",
                    "image"      => $rawData
                ]
            ];

            $postResponse = $this->client->post("/editor/", $params);

            // Make sure we load the https
            $redirect = $postResponse->getHeader("Location");
            if(strpos($target, "https://") === 0 && strpos($redirect, "https://") === false){
                $redirect = str_replace("http://", "https://", $redirect);
            }
            // Send redirect Header
            $response = $response
                ->withStatus(302)
                ->withHeader("Location", $redirect);

        } else if ($action == "retrieve_pixlr_image") {

            $file = InputFilter::decodeSecureMagic($httpVars["original_file"]);
            $selectedNode = new Node($selection->currentBaseUrl() . $file);
            $selectedNode->loadNodeInfo();

            if(!is_writeable($selectedNode->getUrl())){
                $this->logError("Pixlr Editor", "Trying to edit an unauthorized file ".$selectedNode->getUrl());
                return;
            }

            $this->logInfo('Edit', 'Retrieving content of '.$file.' from Pixlr server.', array("files" => $file));
            $url = $httpVars["new_url"];
            $urlParts = UrlUtils::mbParseUrl($url);
            $query = $urlParts["query"];
            $params = array();
            parse_str($query, $params);
            if ($this->getContextualOption($ctx, "CHECK_SECURITY_TOKEN")) {
                $token = $params["securityToken"];
                if ($token != md5($httpVars["secure_token"])) {
                    throw new PydioException("Invalid Token, this could mean some security problem!");
                }
            }
            $image = $params['image'];
            $headers = get_headers($image, 1);
            $content_type = explode("/", $headers['Content-Type']);
            if ($content_type[0] != "image") {
                throw new PydioException("Invalid File Type");
            }
            $orig = fopen($image, "r");
            $target = fopen($selectedNode->getUrl(), "w");
            if(is_resource($orig) && is_resource($target)){
                while (!feof($orig)) {
                    fwrite($target, fread($orig, 4096));
                }
                fclose($orig);
                fclose($target);
            }
            clearstatcache(true, $selectedNode->getUrl());
        } else if ($action === "pixlr_save") {
            $response = $response->withHeader("Content-type", "text/html");
            $response->getBody()->write('<br/><br/><br/><br/><div align="center" style="font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#AAA;font-weight:bold;">Saving Pixlr image, you can close the editor now.</div>');
        } else if($action === "pixlr_close") {
            $response = $response->withHeader("Content-type", "text/html");
            $response->getBody()->write('<br/><br/><br/><br/><div align="center" style="font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#AAA;font-weight:bold;">Please wait while closing Pixlr editor...</div>');
        } else if($action === "pixlr_error") {
            $response = $response->withHeader("Content-type", "text/html");
            $response->getBody()->write('<br/><br/><br/><br/><div align="center" style="font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#AAA;font-weight:bold;">Error while opening Pixlr editor!</div>');
        }

    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

}
