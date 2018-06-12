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
 *
 */
namespace Pydio\Access\Driver\StreamProvider\S3;

use DOMNode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Access\Core\RecycleBinManager;

use Pydio\Access\Driver\StreamProvider\FS\FsAccessDriver;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\SessionService;
use Pydio\Core\Utils\Vars\UrlUtils;
use Swagger\Client\Model\RestUserJobRequest;
use Zend\Diactoros\Response\JsonResponse;

use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Plugin to access a webdav enabled server
 * @package AjaXplorer_Plugins
 * @subpackage Access
 */
class S3AccessDriver extends FsAccessDriver
{
    /**
    * @var \Pydio\Access\Core\Model\Repository
    */
    public $repository;
    public $driverConf;
    protected $wrapperClassName;
    protected $urlBase;
    protected $s3Client;

    public function performChecks()
    {
        // Check CURL, OPENSSL & AWS LIBRARY
        if(!extension_loaded("curl")) throw new \Exception("Cannot find php_curl extension!");
    }

    /**
     * @param ContextInterface $contextInterface
     * @throws PydioException
     * @throws \Exception
     */
    protected function initRepository(ContextInterface $contextInterface)
    {

        if (is_array($this->pluginConf)) {
            $this->driverConf = $this->pluginConf;
        } else {
            $this->driverConf = array();
        }

        ConfService::setConf("PROBE_REAL_SIZE", false);
        $this->urlBase = $contextInterface->getUrlBase();
        $recycle = $contextInterface->getRepository()->getContextOption($contextInterface, "RECYCLE_BIN");
        $roots = $contextInterface->getRepository()->getRootNodes();
        if ($recycle != "" && count($roots) === 1) {
            RecycleBinManager::init($this->urlBase, "/".$recycle);
        }

    }

    /**
     * @return S3Client
     */
    public function getS3Service(ContextInterface $context){
        return S3AccessWrapper::getClientForContext($context);
    }

    /**
     * @param String $srcdir Url of source file
     * @param String $dstdir Url of dest file
     * @param array $errors Array of errors
     * @param array $success Array of success
     * @param bool $verbose Boolean
     * @param bool $convertSrcFile Boolean
     * @param array $srcRepoData Set of data concerning source repository: base_url, recycle option
     * @param array $destRepoData Set of data concerning destination repository: base_url, chmod option
     * @param string $taskId Optional Task ID
     * @return int
     */
    protected function dircopy($srcdir, $dstdir, &$errors, &$success, $verbose = false, $convertSrcFile = true, $srcRepoData = array(), $destRepoData = array(), $taskId = null)
    {

        $node = new Node($srcdir);
        $ctx = $node->getContext();
        $repoObject = $node->getRepository();
        $basePath = $repoObject->getContextOption($ctx, "PATH");

        $fromKeyname = trim(str_replace("//", "/", $basePath . UrlUtils::mbParseUrl($srcdir, PHP_URL_PATH)), '/');
        $toKeyname = trim(str_replace("//", "/", $basePath . UrlUtils::mbParseUrl($dstdir, PHP_URL_PATH)), '/');

        if($destRepoData["base_url"] !== $srcRepoData["base_url"]) {
            $destNode = new Node($dstdir);
            $destCtx = $destNode->getContext();
            $destRepoObject = $destNode->getRepository();
            $destBasePath = $destRepoObject->getContextOption($destCtx, "PATH");
            $toKeyname = trim(str_replace("//", "/", $destBasePath . UrlUtils::mbParseUrl($dstdir, PHP_URL_PATH)), '/');
        }

        $request = new RestUserJobRequest();
        $request->setJobName("copy");
        $request->setJsonParameters(json_encode([
            "nodes"         => [$fromKeyname],
            "target"        => $toKeyname,
        ]));
        $api = MicroApi::GetJobsServiceApi();
        try{
            $response = $api->userCreateJob("copy", $request);
        }catch (ApiException $a){
            return 0;
        }
        return 1;
    }

    /**
     * @param Node $node
     * @return int
     */
    public function directoryUsage(Node $node){
        $client = $this->getS3Service($node->getContext());
        $bucket = $node->getRepository()->getContextOption($node->getContext(), "CONTAINER");
        $path   = rtrim($node->getRepository()->getContextOption($node->getContext(), "PATH"), "/").$node->getPath();
        $objects = $client->getIterator('ListObjects', array(
            'Bucket' => $bucket,
            'Prefix' => $path
        ));

        $usage = 0;
        foreach ($objects as $object) {
            $usage += (double)$object['Size'];
        }
        return $usage;

    }

    /**
     * @inheritdoc
     */
    protected function parseSpecificContributions(ContextInterface $ctx, \DOMNode &$contribNode)
    {
        parent::parseSpecificContributions($ctx, $contribNode);
        if($contribNode->nodeName != "actions") return ;
        //$this->disableArchiveBrowsingContributions($contribNode);
    }

    /**
     * We have to overwrite original FS function as S3 wrapper does not support "a+" open mode.
     *
     * @param String $folder Folder destination
     * @param String $source Maybe updated by the function
     * @param String $target Existing part to append data
     * @return bool If the target file already existed or not.
     * @throws \Exception
     */
    protected function appendUploadedData($folder, $source, $target){

        $already_existed = false;
        if($source == $target){
            throw new \Exception("Something nasty happened: trying to copy $source into itself, it will create a loop!");
        }
        // S3 does not really support append. Let's grab the remote target first.
        if (file_exists($folder ."/" . $target)) {
            $already_existed = true;
            $this->logDebug("Should copy stream from $source to $target - folder is ($folder)");
            $partO = fopen($folder."/".$source, "r");
            $appendF = fopen($folder ."/". $target, 'a');
            while (!feof($partO)) {
                $buf = fread($partO, 1024);
                fwrite($appendF, $buf);
            }
            fclose($partO);
            fclose($appendF);
            $this->logDebug("Done, closing streams!");
        }
        @unlink($folder."/".$source);
        return $already_existed;

    }

    /**
     * @param ContextInterface $ctx Folder destination
     * @param String $source Maybe updated by the function
     * @param String $target Existing part to append data
     */
    protected function uploadTemporaryUpload($ctx, $source, $target){

        $time = time();
        $client = $this->getS3Service($ctx);
        $node = new Node($target);
        $bucket = $node->getRepository()->getContextOption($ctx, "CONTAINER");
        $path   = rtrim($node->getRepository()->getContextOption($ctx, "PATH"), "/").$node->getPath();
        $this->logError("FS", "Use Multipart Upload to upload $source to $bucket / $key");
        $uploader = new MultipartUploader($client, $source, [
            'bucket' => $bucket,
            'key'    => $path,
        ]);
        try {
            $result = $uploader->upload();
            $this->logError("FS", "Upload complete: {$result['ObjectURL']}");
        } catch (MultipartUploadException $e) {
            $this->logError("FS", $e->getMessage());
            @unlink($source);
            throw $e;
        }
        @unlink($source);

    }

    /**
     * @param Node $dir
     * @param string $type
     * @return bool
     */
    public function isWriteable(Node $node)
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function isRemote()
    {
        return true;
    }

    /**
     * @param \Pydio\Access\Core\Model\Node $ajxpNode
     * @param bool $parentNode
     * @param bool $details
     * @return void
     */
    public function loadNodeInfo(&$node, $parentNode = false, $details = false)
    {
        parent::loadNodeInfo($node, $parentNode, $details);
        if (!$node->isLeaf()) {
            $node->setLabel(rtrim($node->getLabel(), "/"));
        }
    }

}
