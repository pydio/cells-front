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
namespace Pydio\Access\Driver\StreamProvider\FS;

use DOMNode;
use DOMXPath;
use Normalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Pydio\Access\Core\AbstractAccessDriver;
use Pydio\Access\Core\Exception\FileNotWriteableException;
use Pydio\Access\Core\IPydioWrapperProvider;
use Pydio\Access\Core\MetaStreamWrapper;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\NodesDiff;
use Pydio\Access\Core\Model\NodesList;
use Pydio\Access\Core\Model\Repository;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Access\Core\RecycleBinManager;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Message\ExternalUploadedFile;
use Pydio\Core\Http\Message\UserMessage;
use Pydio\Core\Http\Response\FileReaderResponse;
use Pydio\Core\Http\Response\SerializableResponseStream;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\LocaleService;
use Pydio\Core\Services\SessionService;
use Pydio\Core\Utils\Http\UserAgent;
use Pydio\Core\Utils\TextEncoder;
use Pydio\Core\Utils\Vars\InputFilter;
use Pydio\Core\Utils\Vars\PathUtils;
use Pydio\Core\Utils\Vars\StatHelper;
use Pydio\Tasks\Schedule;
use Pydio\Tasks\Task;
use Pydio\Tasks\TaskService;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;

defined('PYDIO_EXEC') or die( 'Access not allowed');


// This is used to catch exception while downloading
if (!function_exists('download_exception_handler')) {
    /**
     * @param $exception
     */
    function download_exception_handler($exception){}
}
/**
 * Plugin to access a filesystem. Most "FS" like driver (even remote ones)
 * extend this one.
 * @package AjaXplorer_Plugins
 * @subpackage Access
 */
class FsAccessDriver extends AbstractAccessDriver implements IPydioWrapperProvider
{
    /**
    * @var Repository
    */
    public $repository;
    public $driverConf;
    protected $wrapperClassName;
    protected $urlBase;

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
            $this->driverConf = [];
        }
        $repository = $contextInterface->getRepository();
        $create = $repository->getContextOption($contextInterface, "CREATE");
        $path = $repository->getContextOption($contextInterface, "PATH");
        $storagePath = TextEncoder::toStorageEncoding($path);
        $recycle = $repository->getContextOption($contextInterface, "RECYCLE_BIN");
        $chmod = $repository->getContextOption($contextInterface, "CHMOD_VALUE");
        $this->urlBase = $contextInterface->getUrlBase();

        MetaStreamWrapper::appendMetaWrapper("pydio.encoding", "Pydio\\Access\\Core\\EncodingWrapper", 100);

        if ($create == true) {
            if(!is_dir($storagePath)) @mkdir($storagePath, 0755, true);
            if (!is_dir($storagePath)) {
                throw new PydioException("Cannot create root path for repository (".$repository->getDisplay()."). Please check repository configuration or that your folder is writeable!");
            }
            if ($recycle!= "" && !is_dir($storagePath."/".$recycle)) {
                @mkdir($storagePath."/".$recycle);
                if (!is_dir($storagePath."/".$recycle)) {
                    throw new PydioException("Cannot create recycle bin folder. Please check repository configuration or that your folder is writeable!");
                }
            }
            $dataTemplate = TextEncoder::toStorageEncoding($repository->getContextOption($contextInterface, "DATA_TEMPLATE"));
            if (!empty($dataTemplate) && is_dir($dataTemplate) && !is_file($storagePath."/.ajxp_template")) {
                $errs = [];$succ = [];
                $repoData = ['base_url' => $contextInterface->getUrlBase(), 'chmod' => $chmod, 'recycle' => $recycle];
                $this->dircopy($dataTemplate, $storagePath, $succ, $errs, false, false, $repoData, $repoData);
                touch($storagePath."/.ajxp_template");
            }
        } else {
            if (!is_dir($storagePath)) {
                throw new PydioException("Cannot find base path for your repository! Please check the configuration!");
            }
        }
        if ($recycle != "") {
            RecycleBinManager::init($contextInterface->getUrlBase(), "/".$recycle);
        }
    }

    /**
     * @param String $path
     * @return string
     */
    public function getResourceUrl($path)
    {
        return $this->urlBase.$path;
    }

    /**
     * @param Node $node
     * @return int
     */
    public function directoryUsage(Node $node){

        if(MetaStreamWrapper::wrapperIsRemote($node->getUrl())){
            return $this->recursiveDirUsageByListing($node->getUrl());
        }
        $dir = $node->getRealFile();
        $size = -1;
        if ( ( PHP_OS == "WIN32" || PHP_OS == "WINNT" || PHP_OS == "Windows") && class_exists("COM") ) {
            $obj = new \COM ( 'scripting.filesystemobject' );
            if ( is_object ( $obj ) ) {
                $ref = $obj->getfolder ( $dir );
                $size = floatval($ref->size);
                $obj = null;
            }
        } else {
            if((PHP_OS == "Darwin") || (PHP_OS == "FreeBSD")) $option = "-sk";
            else $option = "-sb";
            $cmd = '/usr/bin/du '.$option.' ' . escapeshellarg($dir);
            $io = popen ( $cmd , 'r' );
            $size = fgets ( $io, 4096);
            $size = trim(str_replace($dir, "", $size));
            $size =  floatval($size);
            if((PHP_OS == "Darwin") || (PHP_OS == "FreeBSD")) $size = $size * 1024;
            pclose ( $io );
        }
        if($size != -1){
            return $size;
        }else{
            return $this->recursiveDirUsageByListing($node->getUrl());
        }

    }

    /**
     * @param $path
     * @return int|string
     */
    protected function recursiveDirUsageByListing($path){
        $total_size = 0;
        $files = scandir($path);

        foreach ($files as $t) {
            if (is_dir(rtrim($path, '/') . '/' . $t)) {
                if ($t <> "." && $t <> "..") {
                    $size = $this->recursiveDirUsageByListing(rtrim($path, '/') . '/' . $t);
                    $total_size += $size;
                }
            } else {
                $size = sprintf("%u", filesize(rtrim($path, '/') . '/' . $t));
                $total_size += $size;
            }
        }
        return $total_size;
    }

    /**
     * @param $contribNode
     * @param $arrayActions
     * @param $targetMethod
     */
    public function redirectActionsToMethod(&$contribNode, $arrayActions, $targetMethod)
    {
        $actionXpath=new DOMXPath($contribNode->ownerDocument);
        foreach ($arrayActions as $index => $value) {
            $arrayActions[$index] = 'action[@name="'.$value.'"]/processing/serverCallback';
        }
        $procList = $actionXpath->query(implode(" | ", $arrayActions), $contribNode);
        foreach ($procList as $node) {
            $node->setAttribute("methodName", $targetMethod);
        }
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function filterByApi(&$request){
        if($request->getAttribute("api") !== "v2") return;
        $params = $request->getParsedBody();
        $action = $request->getAttribute("action");
        switch($action){
            case "ls":
                $children = $params["children"] OR null;
                $meta     = $params["meta"] OR "standard";
                if(!empty($children)){
                    $options = $children;
                } else {
                    $options = "dzf";
                }
                if($meta !== "minimal") $options .= "l";
                $params["options"] = $options;
                $request = $request->withParsedBody($params);
                break;
            case "download":

                break;

            default:
                break;
        }
    }

    /**
     * @param DOMNode $contribNode
     */
    public function disableArchiveBrowsingContributions(&$contribNode)
    {
        // Cannot use zip features on FTP !
        // Remove "compress" action
        $actionXpath=new DOMXPath($contribNode->ownerDocument);
        $compressNodeList = $actionXpath->query('action[@name="compress"]|action[@name="compress_ui"]|action[@name="download_all"]', $contribNode);
        if(!$compressNodeList->length) return ;
        foreach($compressNodeList as $compressNodeAction){
            $contribNode->removeChild($compressNodeAction);
        }
        // Disable "download" if selection is multiple
        $nodeList = $actionXpath->query('action[@name="download"]/gui/selectionContext', $contribNode);
        $selectionNode = $nodeList->item(0);
        $values = ["dir" => "false", "unique" => "true"];
        foreach ($selectionNode->attributes as $attribute) {
            if (isSet($values[$attribute->name])) {
                $attribute->value = $values[$attribute->name];
            }
        }
        $nodeList = $actionXpath->query('action[@name="download"]/processing/clientListener[@name="selectionChange"]', $contribNode);
        $listener = $nodeList->item(0);
        $listener->parentNode->removeChild($listener);
        // Disable "Explore" action on files
        $nodeList = $actionXpath->query('action[@name="ls"]/gui/selectionContext', $contribNode);
        $selectionNode = $nodeList->item(0);
        $values = ["file" => "false", "allowedMimes" => ""];
        foreach ($selectionNode->attributes as $attribute) {
            if (isSet($values[$attribute->name])) {
                $attribute->value = $values[$attribute->name];
            }
        }
    }

    /**
     * @param $selection
     * @return array|string
     */
    public function addSlugToPath($selection)
    {
        if (is_array($selection))
            // As passed by Copy/Move
            $orig_files = $selection;
        elseif ((is_object($selection)) && (isset($selection->files)) && (is_array($selection->files)))
            // As passed by Download
            $orig_files = $selection->files;
        elseif (is_string($selection))
            // As passed by destination parameter
            return $this->repository->slug.$selection;
        else
            // Unrecognized
            return $selection;

        $files = [];
        foreach ($orig_files as $file)
            $files[] = $this->repository->slug.$file;
        return $files;
    }

    /**
     * API V2, will get POST / PUT actions, will reroute to mkdir, mkfile, copy, move actions
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws \Exception
     */
    public function createResourceAction(ServerRequestInterface &$request, ResponseInterface &$response){

        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        $selection = UserSelection::fromContext($ctx, $request->getParsedBody());
        if($selection->isEmpty()){
            throw new PydioException("Empty resource");
        }
        $path = $selection->getUniqueFile();
        $params = $request->getParsedBody();
        $newAction = null;
        $newVars = [];
        if(isSet($params["copy_source"])){
            $newVars["dest"] = PathUtils::forwardSlashDirname($path);
            $newVars["targetBaseName"] = PathUtils::forwardSlashBasename($path);

            $sourceParts = explode("/", trim($params["copy_source"], "/"));
            $sourceRepo  = array_shift($sourceParts);
            $newVars["file"] = "/".implode("/", $sourceParts);
            $currentRepo = $ctx->getRepositoryId()."";
            if($currentRepo !== $sourceRepo){
                // Cross repo, invert parameters and forward to parent method!
                throw new PydioException("Cross Repository copy is not implemented on this api.");
            }
            if(isSet($params["delete_source"]) && $params["delete_source"] == "true"){
                $newAction = "move";
            }else{
                $newAction = "copy";
            }
        }else{
            $qPath = $params["path"];
            if(substr_compare($qPath, "/", strlen($qPath)-1, 1) === 0){
                // Ends with slash => mkdir
                $newAction = "mkdir";
                $newVars["file"] = $path;
                if(!empty($params["override"])) {
                    $newVars["ignore_exists"] = $params["override"];
                }
                if(!empty($params["recursive"])) {
                    $newVars["recursive"] = $params["recursive"];
                }
            }else{
                $newAction = "mkfile";
                $newVars["node"] = $path;
                if(!empty($params["content"])) {
                    $newVars["content"] = $params["content"];
                }
                if(!empty($params["override"])) {
                    $newVars["force"] = $params["override"];
                }
            }
        }
        $request = $request->withParsedBody($newVars)->withAttribute("action", $newAction);
        $this->switchAction($request, $response);

    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws \Exception
     */
    public function uploadAction(ServerRequestInterface &$request, ResponseInterface &$response){

        $httpVars = $request->getParsedBody();
        $dir = InputFilter::sanitize($httpVars["dir"], InputFilter::SANITIZE_DIRNAME) OR "";
        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        if (MetaStreamWrapper::actualRepositoryWrapperClass(new Node($ctx->getUrlBase())) === "Pydio\\Access\\Driver\\StreamProvider\\FS\\FsAccessWrapper") {
            $dir = PathUtils::patchPathForBaseDir($dir);
        }
        $dir = InputFilter::securePath($dir);
        $selection = UserSelection::fromContext($ctx, $httpVars);
        if (!$selection->isEmpty()) {
            $this->filterUserSelectionToHidden($selection->getContext(), $selection->getFiles());
            if(empty($dir) && $selection->isUnique()){
                $dir = PathUtils::forwardSlashDirname($selection->getUniqueFile());
            }
        }
        $mess = LocaleService::getMessages();

        $repoData = [
            'chmod'     => $ctx->getRepository()->getContextOption($ctx, 'CHMOD_VALUE'),
            'recycle'   => $ctx->getRepository()->getContextOption($ctx, 'RECYCLE_BIN')
        ];
        $this->logDebug("Upload Files Data", $request->getUploadedFiles());

        $destNode = $selection->nodeForPath(InputFilter::decodeSecureMagic($dir));
        $destination = $destNode->getUrl();
        $this->logDebug("Upload inside", ["destination"=>$this->addSlugToPath($destNode->getUrl())]);
        if (!$this->isWriteable($destNode)) {
            $errorCode = 412;
            $errorMessage = "$mess[38] ".$dir." $mess[99].";
            $this->logDebug("Upload error 412", ["destination"=>$this->addSlugToPath($destination)]);
            $this->writeUploadError($request, $errorMessage, $errorCode);
            return;
        }

        $partialUpload = false;
        $partialTargetSize = -1;
        $originalAppendTo = "";
        $createdNode = null;

        /** @var UploadedFileInterface[] $uploadedFiles */
        $uploadedFiles = $request->getUploadedFiles();
        if(!count($uploadedFiles)){
            $this->writeUploadError($request, "Could not find any uploaded file", 411);
            return;
        }
        $uploadedFile = array_shift($uploadedFiles);

        try{
            // CHECK PHP UPLOAD ERRORS
            InputFilter::parseFileDataErrors($uploadedFile, true);

            // FIND PROPER FILE NAME / FILTER IF NECESSARY
            if (isSet($httpVars["urlencoded_filename"])) {
                $userfile_name = InputFilter::sanitize(urldecode($httpVars["urlencoded_filename"]), InputFilter::SANITIZE_FILENAME, true);
            }else{
                $userfile_name= InputFilter::sanitize(InputFilter::fromPostedFileName($uploadedFile->getClientFileName()), InputFilter::SANITIZE_FILENAME, true);
            }
            $userfile_name = cropFilename($userfile_name, ConfService::getContextConf($ctx, "NODENAME_MAX_LENGTH"));
            $this->logDebug("User filename ".$userfile_name);
            if(class_exists("Normalizer")){
                $userfile_name = Normalizer::normalize($userfile_name, Normalizer::FORM_C);
            }
            // Chec if it's forbidden
            $this->filterUserSelectionToHidden($selection->getContext(), [$userfile_name]);

            // MODIFY TARGET IF AUTO RENAME
            if (isSet($httpVars["auto_rename"])) {
                $userfile_name = self::autoRenameForDest($destination, $userfile_name);
            }

            // APPLY PRE-UPLOAD HOOKS
            $already_existed = false;
            try {
                $newFileSize = $uploadedFile->getSize();
                $targetUrl = $destination."/".$userfile_name;
                $targetNode = new Node($targetUrl);
                if ($targetNode->exists()) {
                    $already_existed = true;
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), 507);
            }

            $this->logDebug("fs", json_encode($httpVars));
            // PARTIAL UPLOAD CASE - PREPPEND .dlpart extension
            if(isSet($httpVars["partial_upload"]) && $httpVars["partial_upload"] == 'true' && isSet($httpVars["partial_target_bytesize"])) {
                $realDestination = $destination;
                $destination = ApplicationState::getTemporaryFolder() . "/" . md5($destination);
                if(!file_exists($destination)) {
                    mkdir($destination, 0755);
                }
                $partialUpload = true;
                $partialTargetSize = intval($httpVars["partial_target_bytesize"]);
                if(!isSet($httpVars["appendto_urlencoded_part"])) {
                    $userfile_name .= ".dlpart";
                }
                $targetUrl = $destination."/".$userfile_name;
            }

            // NOW DO THE ACTUAL COPY
            $this->logDebug("fs", "Copying uploaded data to " . $targetUrl);
            $this->copyUploadedData($uploadedFile, $targetUrl, $mess);
            $this->logDebug("fs", "Copying uploaded data OK");

            // PARTIAL UPLOAD - PART II: APPEND DATA TO EXISTING PART
            if (isSet($httpVars["appendto_urlencoded_part"])) {
                $appendTo = InputFilter::sanitize(urldecode($httpVars["appendto_urlencoded_part"]), InputFilter::SANITIZE_FILENAME);
                if(isSet($httpVars["partial_upload"]) && $httpVars["partial_upload"] == 'true'){
                    $originalAppendTo = $appendTo;
                    $appendTo .= ".dlpart";
                }
                $this->logError("fs", "Append ".$userfile_name." to " . $appendTo);
                $already_existed = $this->appendUploadedData($destination, $userfile_name, $appendTo);
                $userfile_name = $appendTo;
                if($partialUpload && $partialTargetSize == filesize($destination."/".$userfile_name)){
                    // This was the last part. We can now rename to the original name.
                    if(is_file($destination."/".$originalAppendTo)){
                        unlink($destination."/".$originalAppendTo);
                    }
                    $result = @rename($destination."/".$userfile_name, $destination."/".$originalAppendTo);
                    if($result === false){
                        throw new \Exception("Error renaming ".$destination."/".$userfile_name." to ".$destination."/".$originalAppendTo);
                    }
                    $userfile_name = $originalAppendTo;
                    $partialUpload = false;
                    // Send a create event!
                    $already_existed = false;
                    $lastPartAppended = true;

                    // Now upload to backend and clean temporary resources
                    @ini_set("max_execution_time", 240);
                    $this->logDebug("fs", "Now Uploading to backend: " . $realDestination . "/" . $userfile_name);
                    $this->uploadTemporaryUpload($ctx, $destination . "/" . $userfile_name, $realDestination . "/" . $userfile_name);
                    @rmdir($destination);
                    $realDestination = false;
                }
            }
            if(!isSet($realDestination)) {
                $createdNode = new Node($destination."/".$userfile_name);
                $this->uploadPostProcess($request, $createdNode, $partialUpload, $already_existed, $repoData["chmod"]);
            }

        }catch(\Exception $e){
            $errorCode = $e->getCode();
            if(empty($errorCode)) $errorCode = 411;
            $this->writeUploadError($request, $e->getMessage(), $errorCode);
        }

    }

    /**
     * @param ContextInterface $ctx Context
     * @param String $source Maybe updated by the function
     * @param String $target Existing part to append data
     * @return bool If the target file already existed or not.
     * @throws \Exception
     */
    protected function uploadTemporaryUpload($ctx, $source, $target){

        $time = time();
        $this->logError("FS", "Should copy stream from $source to $target");
        $partO = fopen($source, "r");
        $appendF = fopen($target, "w");
        while (!feof($partO)) {
            $buf = fread($partO, 4096*4069);
            fwrite($appendF, $buf, strlen($buf));
        }
        fclose($partO);
        fclose($appendF);
        $this->logDebug("Done, closing streams!");
        $total = time() - $time;
        $this->logError("FS", "FrontDebug : Took " . $total + " to upload temporary file");
        @unlink($source);

    }


    /**
     * @param ServerRequestInterface $request
     * @param Node $createdNode
     * @param $partialUpload
     * @param $nodeOverriden
     * @param string $chmodValue
     */
    protected function uploadPostProcess(&$request, $createdNode, $partialUpload = false, $nodeOverriden = false, $chmodValue = ""){

        // NOW PREPARE POST-UPLOAD EVENTS
        $this->changeMode($createdNode->getUrl(),["chmod" => $chmodValue]);
        clearstatcache(true, $createdNode->getUrl());
        $createdNode->loadNodeInfo(true);
        $logFile = $this->addSlugToPath($createdNode->getParent()->getPath())."/".$createdNode->getLabel();
        $this->logInfo("Upload File", ["file"=>$logFile, "files"=> $logFile]);

        if($partialUpload){
            $this->logDebug("Return Partial Upload: SUCESS but no event yet");
            // Make sure to clear cache for parent
            $createdNode->getParent()->loadNodeInfo(true);
            $this->writeUploadSuccess($request, ["PARTIAL_NODE" => $createdNode]);
        } else {
            $this->logDebug("Return success");
            $params = $request->getParsedBody();
            if(isSet($params['hash']) && $params['hash'] === 'true'){
                $createdNode->loadHash();
            }
            if($nodeOverriden){
                $this->writeUploadSuccess($request, ["UPDATED_NODE" => $createdNode]);
            }else{
                $this->writeUploadSuccess($request, ["CREATED_NODE" => $createdNode]);
            }
        }

    }

    /**
     * @param ServerRequestInterface $request
     * @param $message
     * @param $code
     */
    protected function writeUploadError(ServerRequestInterface &$request, $message, $code){
        $request = $request->withAttribute("upload_process_result", ["ERROR" => ["CODE" => $code, "MESSAGE" => $message]]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param $nodeData
     */
    protected function writeUploadSuccess(ServerRequestInterface &$request, $nodeData){
        $arr = array_merge(["SUCCESS" => true], $nodeData);
        $request = $request->withAttribute("upload_process_result", $arr);
    }



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws PydioException
     * @throws \Exception
     */
    public function downloadAction(ServerRequestInterface &$request, ResponseInterface &$response){

        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        $httpVars = $request->getParsedBody();
        $selection = UserSelection::fromContext($ctx, $httpVars);
        if (!$selection->isEmpty()) {
            $this->filterUserSelectionToHidden($ctx, $selection->getFiles());
        }

        $action = $request->getAttribute("action");

        switch ($action){

            case "download":

                $this->logInfo("Download", ["files"=>$this->addSlugToPath($selection)]);
                @set_error_handler(["Pydio\\Core\\Controller\\HTMLWriter", "javascriptErrorHandler"], E_ALL & ~ E_NOTICE);
                @register_shutdown_function("restore_error_handler");
                $zip = false;
                $dir = "";
                if (!$selection->isUnique()) {
                    if(isset($httpVars["dir"])){
                        $dir = InputFilter::decodeSecureMagic($httpVars["dir"], InputFilter::SANITIZE_DIRNAME);
                    }else{
                        $dir = $selection->commonDirFromSelection();
                    }
                    $base = basename(PathUtils::forwardSlashDirname($selection->getUniqueFile()));
                    throw new PydioException("Multiple Selection Download-as-Zip Is Not Implemented In Backend - Should use the API");
                }

                if (!$selection->getUniqueNode()->exists()) {
                    throw new \Exception("Cannot find file!");
                }
                $node = $selection->getUniqueNode();

                $localName = "";
                Controller::applyHook("dl.localname", [$selection->getUniqueNode(), &$localName]);
                $fileReader = new FileReaderResponse($selection->getUniqueNode());
                $fileReader->setLocalName($localName);
                $response = $response->withBody($fileReader);

                break;

            case "get_content":

                $node = $selection->getUniqueNode();
                $dlFile = $node->getUrl();
                if(!$this->isReadable($node)){
                    throw new \Exception("Cannot access file!");
                }
                $this->logInfo("Get_content", ["files"=>$this->addSlugToPath($selection)]);

                if (StatHelper::getStreamingMimeType(basename($dlFile)) !==false) {
                    $readMode  = "stream_content";
                } else {
                    $readMode  = "plain";
                }
                $fileReader = new FileReaderResponse($node);
                $fileReader->setHeaderType($readMode);
                $response = $response->withBody($fileReader);
                Controller::applyHook("node.read", [&$node]);

                break;

            case "prepare_chunk_dl" :

                $chunkCount = intval($httpVars["chunk_count"]);
                $node = $selection->getUniqueNode();

                $fileId = $node->getUrl();
                $sessionKey = "chunk_file_".md5($fileId.time());
                $totalSize = filesize($fileId);
                $chunkSize = intval ( $totalSize / $chunkCount );
                $realFile  = MetaStreamWrapper::getRealFSReference($fileId, true);
                $chunkData = [
                    "localname"	  => basename($fileId),
                    "chunk_count" => $chunkCount,
                    "chunk_size"  => $chunkSize,
                    "total_size"  => $totalSize,
                    "file_id"	  => $sessionKey
                ];
                SessionService::save($sessionKey, array_merge($chunkData, ["file" => $realFile]));
                $response = $response->withHeader("Content-type", "application/json; charset=UTF-8");
                $response->getBody()->write(json_encode($chunkData));

                Controller::applyHook("node.read", [&$node]);

                break;

            case "download_chunk" :

                $chunkIndex = intval($httpVars["chunk_index"]);
                $chunkKey = $httpVars["file_id"];
                $sessData = SessionService::fetch($chunkKey);
                $realFile = $sessData["file"];
                $chunkSize = $sessData["chunk_size"];
                $offset = $chunkSize * $chunkIndex;
                if ($chunkIndex == $sessData["chunk_count"]-1) {
                    // Compute the last chunk real length
                    $chunkSize = $sessData["total_size"] - ($chunkSize * ($sessData["chunk_count"]-1));
                    if ($selection->nodeForPath("/")->wrapperIsRemote()) {
                        register_shutdown_function("unlink", $realFile);
                    }
                }
                $fileReader = new FileReaderResponse($realFile);
                $fileReader->setLocalName($sessData["localname"].".".sprintf("%03d", $chunkIndex+1));
                $fileReader->setPartial($offset, $chunkSize);
                $response = $response->withBody($fileReader);

                break;

            default:
                break;
        }

    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws PydioException
     * @throws \Exception
     */
    public function switchAction(ServerRequestInterface &$request, ResponseInterface &$response)
    {
        parent::accessPreprocess($request);
        $this->filterByApi($request);

        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        $action = $request->getAttribute("action");
        $httpVars = $request->getParsedBody();

        $selection = UserSelection::fromContext($ctx, $httpVars);
        if (!$selection->isEmpty()) {
            $this->filterUserSelectionToHidden($ctx, $selection->getFiles());
            RecycleBinManager::filterActions($action, $selection, $httpVars);
        }
        $mess = LocaleService::getMessages();
        $nodesDiffs = new NodesDiff();

        switch ($action) {

            case "stat" :

                clearstatcache();
                $jsonData = new \stdClass;
                if($selection->isUnique()){
                    $stat = @stat($selection->getUniqueNode()->getUrl());
                    if ($stat !== false && $this->isReadable($selection->getUniqueNode())) {
                        $jsonData = $stat;
                    }
                }else{
                    $nodes = $selection->buildNodes();
                    foreach($nodes as $node){
                        $stat = @stat($node->getUrl());
                        if(!$stat || !$this->isReadable($node)) {
                            $stat = new \stdClass();
                        }
                        $path = $node->getPath();
                        $jsonData->$path = $stat;
                    }
                }
                $response = new JsonResponse($jsonData);

            break;


            //------------------------------------
            //	ONLINE EDIT
            //------------------------------------
            case "put_content":

                if(!isset($httpVars["content"])) break;
                // Load "code" variable directly from POST array, do not "securePath" or "sanitize"...
                $code = $httpVars["content"];
                $currentNode = $selection->getUniqueNode();
                $fileName = $currentNode->getUrl();
                $this->logInfo("Online Edition", ["files"=> $this->addSlugToPath($fileName)]);
                if (isSet($httpVars["encode"]) && $httpVars["encode"] == "base64") {
                    $code = base64_decode($code);
                } else {
                    $code=str_replace("&lt;","<", InputFilter::magicDequote($code));
                }
                try {
                    $currentNode->loadNodeInfo();
                } catch (\Exception $e){
                    throw new FileNotWriteableException($currentNode);
                }
                $fp=fopen($fileName,"w");
                fputs ($fp,$code);
                fclose($fp);
                clearstatcache(true, $fileName);
                $logMessage = new UserMessage($mess[115]);
                $nodesDiffs->update([$currentNode]);

            break;

            //------------------------------------
            //	DELETE
            //  Warning, must be kept BEFORE copy/move
            //  as recyclebin filtering can transform
            //  it move action.
            //------------------------------------
            case "delete":

                if ($selection->isEmpty()) {
                    throw new PydioException("", 113);
                }
                $size = 0;
                $nodes = $selection->buildNodes();
                $bgSizeThreshold = 10*1024*1024;
                $bgWorkerThreshold = 80*1024*1024;
                if(!MetaStreamWrapper::wrapperIsRemote($selection->currentBaseUrl())){
                    foreach($nodes as $node){
                        $size += $node->getSizeRecursive();
                    }
                }else if(!$selection->isUnique() || !$selection->getUniqueNode()->isLeaf()){
                    $size = -1;
                }

                $logMessages = [];
                $errorMessage = $this->delete($selection, $logMessages);
                if (count($logMessages)) {
                    $logMessage = new UserMessage(join("\n", $logMessages));
                }
                if($errorMessage) {
                    throw new PydioException($errorMessage);
                }
                $this->logInfo("Delete", ["files"=>$this->addSlugToPath($selection)]);
                $nodesDiffs->remove($selection->getFiles());

            break;

            case "empty_recycle":

                // List recycle content
                $fakeResp = new Response();
                $recycleBin = RecycleBinManager::getRelativeRecycle();
                $newRequest = $request->withAttribute("action", "ls");
                $newRequest = $newRequest->withParsedBody(["dir" => $recycleBin]);
                $this->switchAction($newRequest, $fakeResp);
                $b = $fakeResp->getBody();
                if($b instanceof SerializableResponseStream){
                    foreach($b->getChunks() as $chunk){
                        if($chunk instanceof NodesList){
                            $list = $chunk;
                        }
                    }
                }
                if(!isSet($list)){
                    throw new PydioException("Could not retrieve recycle bin content");
                }
                $selection = UserSelection::fromContext($ctx, []);
                $selection->initFromNodes($list->getChildren());
                $logMessages = [];
                $errorMessage = $this->delete($selection, $logMessages);
                if (count($logMessages)) {
                    $logMessage = new UserMessage(join("\n", $logMessages));
                }
                if($errorMessage) {
                    throw new PydioException($errorMessage);
                }
                $this->logInfo("Delete", ["files"=>$this->addSlugToPath($selection)]);
                $nodesDiffs->remove($selection->getFiles());

                break;

            //------------------------------------
            //	COPY / MOVE
            //------------------------------------
            case "copy":
            case "move":

                if ($selection->isEmpty()) {
                    throw new PydioException("", 113);
                }
                // Compute copy size
                $size = 0;
                $nodes = $selection->buildNodes();
                $bgSizeThreshold = 10*1024*1024;
                $bgWorkerThreshold = 80*1024*1024;
                if(!MetaStreamWrapper::wrapperIsRemote($selection->currentBaseUrl())){
                    foreach($nodes as $node){
                        $size += $node->getSizeRecursive();
                    }
                }else if(!$selection->isUnique() || !$selection->getUniqueNode()->isLeaf()){
                    $size = -1;
                }
                $loggedUser = $ctx->getUser();
                $success = $error = [];
                $destPath = InputFilter::decodeSecureMagic($httpVars["dest"]);
                $targetBaseName = null;
                if($selection->isUnique() && isSet($httpVars["targetBaseName"])){
                    $targetBaseName = $httpVars["targetBaseName"];
                }
                if(isSet($httpVars["recycle_restore"]) && !$selection->nodeForPath($destPath)->exists()){
                    $this->mkDir($selection->nodeForPath(PathUtils::forwardSlashDirname($destPath)), basename($destPath), false, true);
                }
                $this->filterUserSelectionToHidden($ctx, [$httpVars["dest"]]);

                $move = ($action == "move" ? true : false);
                if ($move && isSet($httpVars["force_copy_delete"])) {
                    $move = false;
                }
                $this->copyOrMove($destPath, $selection, $error, $success, $move, $targetBaseName, null);

                if (count($error)) {
                    throw new PydioException(join("\n", $error));
                } else {
                    if (isSet($httpVars["force_copy_delete"])) {
                        $errorMessage = $this->delete($selection, $logMessages, null);
                        if($errorMessage) {
                            throw new PydioException($errorMessage);
                        }
                        $this->logInfo("Copy/Delete", ["files"=>$this->addSlugToPath($selection), "destination" => $this->addSlugToPath($destPath)]);
                    } else {
                        $this->logInfo(($action=="move"?"Move":"Copy"), ["files"=>$this->addSlugToPath($selection), "destination"=>$this->addSlugToPath($destPath)]);
                    }
                    $logMessage = new UserMessage(join("\n", $success));
                }

                break;


            //------------------------------------
            //	RENAME
            //------------------------------------
            case "rename":

                $originalNode = $selection->getUniqueNode();
                $destNode = null;
                $filename_new = "";
                if (isSet($httpVars["dest"])) {
                    $dest = InputFilter::decodeSecureMagic($httpVars["dest"]);
                    $destNode = $selection->nodeForPath($dest);
                    $this->filterUserSelectionToHidden($ctx, [$destNode->getLabel()]);
                }else if(isSet($httpVars["filename_new"])){
                    $filename_new = InputFilter::decodeSecureMagic($httpVars["filename_new"]);
                    $this->filterUserSelectionToHidden($ctx, [$filename_new]);
                }
                $originalNode->loadNodeInfo();
                $renamedNode = $this->rename($originalNode, $destNode, $filename_new);

                $logMessage = new UserMessage($originalNode->getLabel()." $mess[41] ".$renamedNode->getLabel());
                $this->logInfo("Rename", [
                    "files"     => $this->addSlugToPath($originalNode->getUrl()),
                    "original"  => $this->addSlugToPath($originalNode->getUrl()),
                    "new"       => $this->addSlugToPath($renamedNode->getUrl())
                ]);

            break;

            //------------------------------------
            //	CREER UN REPERTOIRE / CREATE DIR
            //------------------------------------
            case "mkdir":

                $messtmp="";
                $files = $selection->getFiles();
                if(isSet($httpVars["dir"]) && isSet($httpVars["dirname"])){
                    $files[] =
                        rtrim(InputFilter::decodeSecureMagic($httpVars["dir"], InputFilter::SANITIZE_DIRNAME), "/")
                        ."/".
                        InputFilter::decodeSecureMagic($httpVars["dirname"], InputFilter::SANITIZE_FILENAME);
                }
                $messages = [];
                $errors = [];
                $max_length = ConfService::getContextConf($ctx, "NODENAME_MAX_LENGTH");
                foreach($files as $newDirPath){
                    $parentDir = PathUtils::forwardSlashDirname($newDirPath);
                    $basename = PathUtils::forwardSlashBasename($newDirPath);
                    $basename = cropFilename($basename, $max_length);
                    $this->filterUserSelectionToHidden($ctx, [$basename]);
                    $parentNode = $selection->nodeForPath($parentDir);
                    try{
                        $newNode = $this->mkDir(
                            $parentNode,
                            $basename,
                            (isSet($httpVars["ignore_exists"]) && $httpVars["ignore_exists"] === "true"),
                            (isSet($httpVars["recursive"]) && $httpVars["recursive"] === "true")
                        );
                    }catch(PydioException $ex){
                        $errors[] = $ex->getMessage();
                        continue;
                    }
                    if(empty($newNode)){
                        continue;
                    }
                    $messtmp.="$mess[38] ".$basename." $mess[39] ";
                    if ($parentDir=="") {$messtmp.="/";} else {$messtmp.= $parentDir;}
                    $messages[] = $messtmp;
                    $this->logInfo("Create Dir", ["dir"=>$this->addSlugToPath($parentDir)."/".$basename, "files"=>$this->addSlugToPath($parentDir)."/".$basename]);
                }
                if(count($errors)){
                    if(!count($messages)){
                        throw new PydioException(implode('', $errors));
                    }else{
                        $messages = array_merge($messages, $errors);
                    }
                }
                $logMessage = new UserMessage(implode("<br>", $messages));


            break;

            //------------------------------------
            //	CREER UN FICHIER / CREATE FILE
            //------------------------------------
            case "mkfile":

                if(empty($httpVars["filename"]) && isSet($httpVars["node"])){
                    $filename= InputFilter::decodeSecureMagic($httpVars["node"]);
                }else{
                    $parent = rtrim(InputFilter::decodeSecureMagic($httpVars["dir"], InputFilter::SANITIZE_DIRNAME), "/");
                    $filename = $parent ."/" . InputFilter::decodeSecureMagic($httpVars["filename"], InputFilter::SANITIZE_FILENAME);
                }
                $filename = cropFilename($filename, ConfService::getContextConf($ctx, "NODENAME_MAX_LENGTH"));
                $this->filterUserSelectionToHidden($ctx, [$filename]);
                $node = $selection->nodeForPath($filename);
                $content = " ";
                if (isSet($httpVars["content"])) {
                    $content = $httpVars["content"];
                }
                $forceCreation = false;
                if (isSet($httpVars["force"]) && $httpVars["force"] == "true"){
                    $forceCreation = true;
                }
                $this->createEmptyFile($node, $content, $forceCreation);
                $logMessage = new UserMessage($mess[34]." ".$node->getLabel()." ".$mess[39]." ". $node->getParent()->getPath());
                $this->logInfo("Create File", ["files"=>$this->addSlugToPath($node->getPath())]);
                $node->loadNodeInfo();

            break;

            //------------------------------------
            //	XML LISTING
            //------------------------------------
            case "ls":

                $nodesList = new NodesList();

                if($selection->isUnique() && $request->getAttribute("api") == "v2" && !empty($httpVars["children"])){
                    $dir = $selection->getUniqueFile();
                    $selection->setFiles([]);
                }else{
                    $dir = InputFilter::sanitize($httpVars["dir"], InputFilter::SANITIZE_DIRNAME) OR "";
                }
                $patch = false;
                if (MetaStreamWrapper::actualRepositoryWrapperClass(new Node($selection->currentBaseUrl())) === "Pydio\\Access\\Driver\\StreamProvider\\FS\\FsAccessWrapper") {
                    $dir = PathUtils::patchPathForBaseDir($dir);
                    $patch = true;
                }
                $dir = InputFilter::securePath($dir);

                // FILTER DIR PAGINATION ANCHOR
                $page = null;
                if (isSet($dir) && strstr($dir, "%23")!==false) {
                    $parts = explode("%23", $dir);
                    $dir = $parts[0];
                    $page = $parts[1];
                }

                if(!isSet($dir) || $dir == "/") $dir = "";
                $lsOptions = $this->parseLsOptions((isSet($httpVars["options"])?$httpVars["options"]:"a"));

                $startTime = microtime();
                $dirNode = $selection->nodeForPath(($dir!= ""?($dir[0]=="/"?"":"/").$dir:""));
                $path = $dirNode->getUrl();
                $nonPatchedPath = $path;
                if ($patch) {
                    $nonPatchedPath = PathUtils::unPatchPathForBaseDir($path);
                }
                if(!$dirNode->exists()){
                    throw new \Exception("There was a problem trying to open folder ". $path. ", please check your Administrator");
                }
                if(!$this->isReadable($dirNode) && !$this->isWriteable($dirNode)){
                    throw new \Exception("You are not allowed to access folder " . $path);
                }
                // Backward compat
                if($selection->isUnique() && strpos($selection->getUniqueFile(), "/") !== 0){
                    $selection->setFiles([$dir . "/" . $selection->getUniqueFile()]);
                }

                $orderField = $orderDirection = null;
                $threshold          = 500;
                $limitPerPage       = 200;
                $defaultOrder       = $ctx->getRepository()->getContextOption($ctx, "REMOTE_SORTING_DEFAULT_COLUMN");
                $defaultDirection   = $ctx->getRepository()->getContextOption($ctx, "REMOTE_SORTING_DEFAULT_DIRECTION");
                if ($ctx->getRepository()->getContextOption($ctx, "REMOTE_SORTING")) {
                    $orderDirection = isSet($httpVars["order_direction"])?strtolower($httpVars["order_direction"]):$defaultDirection;
                    $orderField = isSet($httpVars["order_column"])?$httpVars["order_column"]:$defaultOrder;
                    if ($orderField != null && !in_array($orderField, ["ajxp_label", "filesize", "ajxp_modiftime", "mimestring"])) {
                        $orderField = $defaultOrder;
                    }
                }
                if(!isSet($httpVars["recursive"]) || $httpVars["recursive"] != "true"){
                    $threshold = $ctx->getRepository()->getContextOption($ctx, "PAGINATION_THRESHOLD");
                    if(!isSet($threshold) || intval($threshold) == 0) $threshold = 500;
                    $limitPerPage = $ctx->getRepository()->getContextOption($ctx, "PAGINATION_NUMBER");
                    if(!isset($limitPerPage) || intval($limitPerPage) == 0) $limitPerPage = 200;
                }

                if(!$selection->isEmpty()){
                    $uniqueNodes = $selection->buildNodes();
                    $parentNode = $selection->nodeForPath("/");
                    Controller::applyHook("node.read", [&$parentNode]);
                    $nodesList->setParentNode($parentNode);
                    foreach($uniqueNodes as $node){
                        if(!$node->exists()) continue;
                        $nodeName = $node->getLabel();
                        if (!$this->filterNodeName($ctx, $node->getPath(), $nodeName, $isLeaf, $lsOptions)) {
                            continue;
                        }
                        if (RecycleBinManager::recycleEnabled() && $node->getPath() == RecycleBinManager::getRecyclePath()) {
                            continue;
                        }
                        $node->loadNodeInfo(false, false, ($lsOptions["l"]?"all":"minimal"));
                        if (!empty($node->metaData["nodeName"]) && $node->metaData["nodeName"] != $nodeName) {
                            $node->setUrl(PathUtils::forwardSlashDirname($node->getUrl())."/".$node->metaData["nodeName"]);
                        }
                        if (!empty($node->metaData["hidden"]) && $node->metaData["hidden"] === true) {
                            continue;
                        }
                        if (!empty($node->metaData["mimestring_id"]) && array_key_exists($node->metaData["mimestring_id"], $mess)) {
                            $node->mergeMetadata(["mimestring" =>  $mess[$node->metaData["mimestring_id"]]]);
                        }
                        if(isSet($httpVars["page_position"]) && $httpVars["page_position"] == "true"){
                            // Detect page position: we have to loading "siblings"
                            $parentPath = PathUtils::forwardSlashDirname($node->getPath());
                            $siblings = scandir($selection->currentBaseUrl().$parentPath);
                            foreach($siblings as $i => $s){
                                if($this->filterFile($ctx, $s, true)) unset($siblings[$i]);
                                if($this->filterFolder($ctx, $s)) unset($siblings[$i]);
                            }
                            if(count($siblings) > $threshold){
                                //usort($siblings, "strcasecmp");
                                $siblings = $this->orderNodes($siblings, $selection->currentBaseUrl().$parentPath, $orderField, $orderDirection);
                                $index = array_search($node->getLabel(), $siblings);
                                $node->mergeMetadata(["page_position" => floor($index / $limitPerPage) +1]);
                            }
                        }
                        $nodesList->addBranch($node);
                    }
                    break;
                }

                $metaData = [];
                if (RecycleBinManager::recycleEnabled() && $dir == "") {
                    $metaData["repo_has_recycle"] = RecycleBinManager::getRelativeRecycle();
                }
                $parentNode = new Node($nonPatchedPath, $metaData);
                $parentNode->loadNodeInfo(false, true, ($lsOptions["l"]?"all":"minimal"));
                Controller::applyHook("node.read", [&$parentNode]);

                $streamIsSeekable = MetaStreamWrapper::wrapperIsSeekable($path);

                $sharedHandle = null; $handle = null;
                if($streamIsSeekable){
                    $handle = opendir($path);
                    $sharedHandle = $handle;
                }
                // TODO : How to handle this without sending tons of list requests?
                //$countFiles = $this->countChildren($parentAjxpNode, !$lsOptions["f"], false, $sharedHandle);
                if(isSet($sharedHandle)){
                    rewind($handle);
                }
                $totalPages = $crtPage = 1;
                if (isSet($threshold) && isSet($limitPerPage) && $countFiles > $threshold) {
                    $offset = 0;
                    $crtPage = 1;
                    if (isSet($page)) {
                        $offset = (intval($page)-1)*$limitPerPage;
                        $crtPage = $page;
                    }
                    $totalPages = floor($countFiles / $limitPerPage) + 1;
                } else {
                    $offset = $limitPerPage = 0;
                }

                $nodesList->setParentNode($parentNode);
                if (isSet($totalPages) && isSet($crtPage) && ($totalPages > 1 || !UserAgent::userAgentIsNativePydioApp())) {
                    $remoteOptions = null;
                    if ($this->getContextualOption($ctx, "REMOTE_SORTING")) {
                        $remoteOptions = [
                            "remote_order" => "true",
                            "currentOrderCol" => isSet($orderField)?$orderField:$defaultOrder,
                            "currentOrderDir"=> isSet($orderDirection)?$orderDirection:$defaultDirection
                        ];
                    }
                    // TODO : How to handle this without sending tons of list requests?
                    //$foldersCounts = $this->countChildren($parentAjxpNode, TRUE, false, $sharedHandle);
                    if(isSet($sharedHandle)) {
                        rewind($sharedHandle);
                    }
                    $nodesList->setPaginationData($countFiles, $crtPage, $totalPages, $foldersCounts, $remoteOptions);
                    if ($totalPages > 1 && !$lsOptions["f"]) {
                        if(isSet($sharedHandle)) {
                            closedir($sharedHandle);
                        }
                        break;
                    }
                }

                $cursor = 0;
                if(isSet($sharedHandle)){
                    $handle = $sharedHandle;
                }else{
                    $handle = opendir($path);
                }
                if (!$handle) {
                    throw new PydioException("Cannot open dir ".$nonPatchedPath);
                }
                $nodes = [];
                while(false !== ($file = readdir($handle))){
                    $nodes[] = $file;
                }
                closedir($handle);
                $fullList = ["d" => [], "z" => [], "f" => []];

                //$nodes = scandir($path);
                $nodes = $this->orderNodes($nodes, $nonPatchedPath, $orderField, $orderDirection);

                foreach ($nodes as $nodeName) {
                    if($nodeName == "." || $nodeName == "..") {
                        continue;
                    }
                    $isLeaf = "";
                    if (!$this->filterNodeName($ctx, $path, $nodeName, $isLeaf, $lsOptions)) {
                        continue;
                    }
                    if (RecycleBinManager::recycleEnabled() && $dir == "" && "/".$nodeName == RecycleBinManager::getRecyclePath()) {
                        continue;
                    }
                    if ($offset > 0 && $cursor < $offset) {
                        $cursor ++;
                        continue;
                    }

                    if ($limitPerPage > 0 && ($cursor - $offset) >= $limitPerPage) {
                        break;
                    }

                    $currentFile = $nonPatchedPath."/".$nodeName;
                    $meta = [];
                    if($isLeaf != "") $meta = ["is_file" => ($isLeaf?"1":"0")];
                    $node = new Node($currentFile, $meta);
                    $node->setLabel($nodeName);
                    $node->loadNodeInfo(false, false, ($lsOptions["l"]?"all":"minimal"));
                    if (!empty($node->metaData["nodeName"]) && $node->metaData["nodeName"] != $nodeName) {
                        $node->setUrl($nonPatchedPath."/".$node->metaData["nodeName"]);
                    }
                    if (!empty($node->metaData["hidden"]) && $node->metaData["hidden"] === true) {
                           continue;
                       }
                    if (!empty($node->metaData["mimestring_id"]) && array_key_exists($node->metaData["mimestring_id"], $mess)) {
                        $node->mergeMetadata(["mimestring" =>  $mess[$node->metaData["mimestring_id"]]]);
                    }
                    if (isSet($originalLimitPerPage) && $cursor > $originalLimitPerPage) {
                        $node->mergeMetadata(["page_position" => floor($cursor / $originalLimitPerPage) +1]);
                    }

                    $nodeType = "d";
                    if ($node->isLeaf()) {
                        if (StatHelper::isBrowsableArchive($nodeName)) {
                            if ($lsOptions["f"] && $lsOptions["z"]) {
                                $nodeType = "f";
                            } else {
                                $nodeType = "z";
                            }
                        } else $nodeType = "f";
                    }
                    // There is a special sorting, cancel the reordering of files & folders.
                    if(isSet($orderField) && $orderField != "ajxp_label" && !(isSet($httpVars["recursive"]) && $httpVars["recursive"] == "true" )) {
                        $nodeType = "f";
                    }
                    $fullList[$nodeType][$nodeName] = $node;
                    $cursor ++;
                }
                if (isSet($httpVars["recursive"]) && $httpVars["recursive"] == "true") {

                    $max_depth = (isSet($httpVars["max_depth"])?intval($httpVars["max_depth"]):0);
                    $max_nodes = (isSet($httpVars["max_nodes"])?intval($httpVars["max_nodes"]):0);
                    $crt_depth = (isSet($httpVars["crt_depth"])?intval($httpVars["crt_depth"])+1:1);
                    $crt_nodes = (isSet($httpVars["crt_nodes"])?intval($httpVars["crt_nodes"]):0);
                    $crt_nodes += $countFiles;

                    $breakNow = false;
                    if(isSet($max_depth) && $max_depth > 0 && $crt_depth >= $max_depth) $breakNow = true;
                    if(isSet($max_nodes) && $max_nodes > 0 && $crt_nodes >= $max_nodes) $breakNow = true;
                    /**
                     * @var $nodeDir Node
                     */
                    foreach ($fullList["d"] as &$nodeDir) {
                        if($breakNow){
                            $nodeDir->mergeMetadata(["ajxp_has_children" => $this->countChildren($nodeDir, false, true)?"true":"false"]);
                            $nodesList->addBranch($nodeDir);
                            continue;
                        }
                        $newBody = [
                            "dir" => $nodeDir->getPath(),
                            "options"=> $httpVars["options"],
                            "recursive" => "true",
                            "max_depth"=> $max_depth,
                            "max_nodes"=> $max_nodes,
                            "crt_depth"=> $crt_depth,
                            "crt_nodes"=> $crt_nodes,
                        ];
                        $fakeRequest = Controller::executableRequest($request->getAttribute("ctx"), "ls", $newBody);
                        $fakeRequest = $fakeRequest->withAttribute("parent_node_list", $nodesList);
                        $this->switchAction($fakeRequest, new Response());
                    }

                } else {

                    array_map([$nodesList, "addBranch"], $fullList["d"]);

                }
                array_map([$nodesList, "addBranch"], $fullList["z"]);
                array_map([$nodesList, "addBranch"], $fullList["f"]);

                // ADD RECYCLE BIN TO THE LIST
                if ($dir == ""  && $lsOptions["d"] && RecycleBinManager::recycleEnabled() && $this->getContextualOption($ctx, "HIDE_RECYCLE") !== true) {
                    $recycleBinOption = RecycleBinManager::getRelativeRecycle();
                    $recycleNode = $selection->nodeForPath("/".$recycleBinOption);
                    if ($recycleNode->exists()) {
                        $recycleNode->loadNodeInfo();
                        $nodesList->addBranch($recycleNode);
                    }
                }

                $this->logDebug("LS Time : ".intval((microtime()-$startTime)*1000)."ms");

                $parentList = $request->getAttribute("parent_node_list", null);
                if($parentList !== null){
                    $parentList->addBranch($nodesList);
                }

            break;
        }


        if(isSet($logMessage) || !$nodesDiffs->isEmpty() || isSet($nodesList)){
            $body = new SerializableResponseStream();
            if(isSet($logMessage)) {
                $body->addChunk($logMessage);
            }
            if(!$nodesDiffs->isEmpty()) {
                $body->addChunk($nodesDiffs);
            }
            if(isSet($nodesList)) {
                $body->addChunk($nodesList);
            }
            $response = $response->withBody($body);
        }

    }

    /**
     * @param $nodes
     * @param $path
     * @param $orderField
     * @param $orderDirection
     * @return array
     */
    protected function orderNodes($nodes, $path, $orderField, $orderDirection){

        usort($nodes, "strcasecmp");
        if (!empty($orderField) && !empty($orderDirection) && $orderField == "ajxp_label" && $orderDirection == "desc") {
            $nodes = array_reverse($nodes);
        }
        if (!empty($this->driverConf["SCANDIR_RESULT_SORTFONC"])) {
            usort($nodes, $this->driverConf["SCANDIR_RESULT_SORTFONC"]);
        }
        if (!empty($orderField) && !empty($orderDirection) && $orderField != "ajxp_label") {
            $toSort = [];
            foreach ($nodes as $node) {
                if($orderField == "filesize") $toSort[$node] = is_file($path."/".$node) ? filesize($path."/".$node) : 0;
                else if($orderField == "ajxp_modiftime") $toSort[$node] = filemtime($path."/".$node);
                else if($orderField == "mimestring") $toSort[$node] = pathinfo($node, PATHINFO_EXTENSION);
            }
            if($orderDirection == "asc") asort($toSort);
            else arsort($toSort);
            $nodes = array_keys($toSort);
        }
        return $nodes;

    }

    /**
     * @param $optionString
     * @return array
     */
    public function parseLsOptions($optionString)
    {
        // LS OPTIONS : dz , a, d, z, all of these with or without l
        // d : directories
        // z : archives
        // f : files
        // => a : all, alias to dzf
        // l : list metadata
        $allowed = ["a", "d", "z", "f", "l"];
        $lsOptions = [];
        foreach ($allowed as $key) {
            if (strchr($optionString, $key)!==false) {
                $lsOptions[$key] = true;
            } else {
                $lsOptions[$key] = false;
            }
        }
        if ($lsOptions["a"]) {
            $lsOptions["d"] = $lsOptions["z"] = $lsOptions["f"] = true;
        }
        return $lsOptions;
    }

    /**
     * Update node metadata with core FS metadata.
     * @param \Pydio\Access\Core\Model\Node $node
     * @param bool $parentNode
     * @param bool $details
     * @return void
     */
    public function loadNodeInfo(&$node, $parentNode = false, $details = false)
    {
        $nodeName = basename($node->getPath());
        $metaData = $node->metadata;
        if (!isSet($metaData["is_file"])) {
            $isLeaf = is_file($node->getUrl()) || StatHelper::isBrowsableArchive($nodeName);
            $metaData["is_file"] = ($isLeaf?"1":"0");
        } else {
            $isLeaf = $metaData["is_file"] == "1" ? true : false;
        }
        $metaData["filename"] = $node->getPath();

        if (RecycleBinManager::recycleEnabled() && $node->getPath() == RecycleBinManager::getRelativeRecycle()) {
            $recycleIcon = ($this->countChildren($node, false, true)>0?"trashcan_full.png":"trashcan.png");
            $metaData["icon"] = $recycleIcon;
            $metaData["fonticon"] = "delete";
            $metaData["mimestring_id"] = 122;
            //$ajxpNode->setLabel($mess[122]);
            $metaData["ajxp_mime"] = "ajxp_recycle";
        } else {
            $mimeData = StatHelper::getMimeInfo($node, !$isLeaf);
            $metaData["mimestring_id"] = $mimeData[0];
            $metaData["icon"] = $mimeData[1];
            if(!empty($mimeData[2])){
                $metaData["fonticon"] = $mimeData[2];
            }
            if ($metaData["icon"] == "folder.png") {
                $metaData["openicon"] = "folder_open.png";
            }
            if (!$isLeaf) {
                $metaData["ajxp_mime"] = "ajxp_folder";
            }
        }

        $metaData["file_group"] = @filegroup($node->getUrl()) || "unknown";
        $metaData["file_owner"] = @fileowner($node->getUrl()) || "unknown";
        $metaData["node_readonly"] = "false";
        if (!@$this->isWriteable($node)) {
           $metaData["node_readonly"] = "true";
        }
        $fPerms = @fileperms($node->getUrl());
        if ($fPerms !== false) {
            $fPerms = substr(decoct( $fPerms ), ($isLeaf?2:1));
        } else {
            $fPerms = '0000';
        }
        $metaData["file_perms"] = $fPerms;
        $datemodif = $this->date_modif($node->getUrl());
        $metaData["ajxp_modiftime"] = ($datemodif ? $datemodif : "0");
        $metaData["bytesize"] = 0;
        if ($isLeaf) {
            $metaData["bytesize"] = filesize($node->getUrl());
        }
        //$metaData["filesize"] = StatHelper::roundSize($metaData["bytesize"]);
        if (StatHelper::isBrowsableArchive($nodeName)) {
            $metaData["ajxp_mime"] = "ajxp_browsable_archive";
        }

        if ($details == "minimal") {
            $miniMeta = [
                "is_file" => $metaData["is_file"],
                "filename" => $metaData["filename"],
                "bytesize" => $metaData["bytesize"],
                "ajxp_modiftime" => $metaData["ajxp_modiftime"],
            ];
            $node->mergeMetadata($miniMeta);
        } else {
            $node->mergeMetadata($metaData);
        }

    }

    /**
     * Update nodes metadata with localized info (will NOT be cached)
     * Hooked to node.info.nocache
     * @param Node $node
     * @param bool $parentNode
     * @param bool $details
     */
    public function localizeNodeInfo(&$node, $parentNode = false, $details = false){

        $messages = LocaleService::getMessages();
        $localMeta = [];

        // Recompute "Modifed on ... " string
        $currentMeta = $node->getNodeInfoMeta();
        if(!empty($currentMeta["ajxp_modiftime"])){
            $dateModif = $currentMeta["ajxp_modiftime"];
            $localMeta["ajxp_relativetime"] = StatHelper::relativeDate($dateModif, $messages);
        }

        // Recompute human readable size
        if(!empty($currentMeta["bytesize"])){
            $localMeta["filesize"] = StatHelper::roundSize($currentMeta["bytesize"]);
        }

        // Update Recycle Bin label
        if ($currentMeta["ajxp_mime"] === "ajxp_recycle"){
            $node->setLabel($messages[122]);
        }

        // Now remerge in node
        if(count($localMeta)){
            $node->mergeMetadata($localMeta);
        }

    }

    /**
     * @param array|UploadedFileInterface $uploadData Php-upload array
     * @param String $destination Full path to destination file, including stream data
     * @param array $messages Application messages table
     * @return bool
     * @throws \Exception
     */
    protected function copyUploadedData($uploadData, $destination, $messages){
        if(is_array($uploadData)){
            $isInputStream = isSet($uploadData["input_upload"]);
            $newFileSize = $uploadData["size"];
        }else{
            $isInputStream = $uploadData->getStream() !== null;
            $newFileSize = $uploadData->getSize();
        }

        if ($isInputStream) {
            try {
                $this->logDebug("Begining reading INPUT stream");
                if(is_array($uploadData)){
                    $input = fopen("php://input", "r");
                }else{
                    $input = $uploadData->getStream()->detach();
                }
                $output = fopen($destination, "w");
                $sizeRead = 0;
                while ($sizeRead < intval($newFileSize)) {
                    $chunk = fread($input, 4096);
                    $sizeRead += strlen($chunk);
                    fwrite($output, $chunk, strlen($chunk));
                }
                fclose($input);
                fclose($output);
                $this->logDebug("End reading INPUT stream");
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), 411);
            }
        } else {
            if(is_array($uploadData)){
                $result = @move_uploaded_file($uploadData["tmp_name"], $destination);
                if (!$result) {
                    $realPath = MetaStreamWrapper::getRealFSReference($destination);
                    $result = move_uploaded_file($uploadData["tmp_name"], $realPath);
                }
            }else{
                $clone = clone $uploadData;
                try{
                    $uploadData->moveTo($destination);
                    $result = true;
                }catch(\Exception $e){
                    // Can be blocked by open_basedir, try to perform the move again, with the
                    // real FS reference.
                    $realPath = MetaStreamWrapper::getRealFSReference($destination);
                    try{
                        $clone->moveTo($realPath);
                        $result = true;
                    }catch(\Exception $e){
                        $result = false;
                    }
                }
            }
            if (!$result) {
                $errorMessage="$messages[33] ". PathUtils::forwardSlashBasename($destination);
                throw new \Exception($errorMessage, 411);
            }
        }
        return true;
    }

    /**
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
        if (file_exists($folder ."/" . $target)) {
            $already_existed = true;
            $this->logDebug("Should copy stream from $source to $target");
            $partO = fopen($folder."/".$source, "r");
            $appendF = fopen($folder ."/". $target, "a+");
            while (!feof($partO)) {
                $buf = fread($partO, 1024);
                fwrite($appendF, $buf, strlen($buf));
            }
            fclose($partO);
            fclose($appendF);
            $this->logDebug("Done, closing streams!");
        }
        @unlink($folder."/".$source);
        return $already_existed;

    }

    /**
     * @param Node $dirNode
     * @param bool $foldersOnly
     * @param bool $nonEmptyCheckOnly
     * @param null $dirHANDLE
     * @return int
     * @throws \Exception
     */
    public function countChildren(Node $dirNode, $foldersOnly = false, $nonEmptyCheckOnly = false, $dirHANDLE = null)
    {
        $dirName = $dirNode->getUrl();
        if(is_resource($dirHANDLE)){
            $handle = $dirHANDLE;
        }else{
            $handle=@opendir($dirName);
        }
        if ($handle === false) {
            throw new \Exception("Error while trying to open directory ".$dirName);
        }
        if ($foldersOnly && !$dirNode->wrapperIsRemote()) {
            if($dirHANDLE == null || !is_resource($dirHANDLE)){
                closedir($handle);
            }
            $path = $dirNode->getRealFile();
            $dirs = glob($path."/*", GLOB_ONLYDIR|GLOB_NOSORT);
            if($dirs === false) return 0;
            return count($dirs);
        }
        $count = 0;
        $showHiddenFiles = $this->getContextualOption($dirNode->getContext(), "SHOW_HIDDEN_FILES");
        while (false !== ($file = readdir($handle))) {
            if($file != "." && $file !=".."
                && !(StatHelper::isHidden($file) && !$showHiddenFiles)){
                if($foldersOnly && is_file($dirName."/".$file)) continue;
                $count++;
                if($nonEmptyCheckOnly) break;
            }
        }
        if($dirHANDLE == null || !is_resource($dirHANDLE)){
            closedir($handle);
        }
        return $count;
    }

    /**
     * @param $file
     * @return int
     */
    public function date_modif($file)
    {
        $tmp = @filemtime($file) or 0;
        return $tmp;// date("d,m L Y H:i:s",$tmp);
    }

    /**
     * @param string $destDir
     * @param UserSelection $selection
     * @param array $error
     * @param array $success
     * @param bool $move
     * @param string|null $targetBaseName
     * @param string|null $taskId
     * @throws \Exception
     */
    public function copyOrMove($destDir, $selection, &$error, &$success, $move = false, $targetBaseName = null, $taskId = null)
    {
        $selectedNodes = $selection->buildNodes();
        $selectedFiles = $selection->getFiles();
        $this->logDebug("CopyMove", ["dest"=>$this->addSlugToPath($destDir), "selection" => $this->addSlugToPath($selectedFiles)]);
        $mess = LocaleService::getMessages();
        if (!$this->isWriteable($selection->nodeForPath($destDir))) {
            $error[] = $mess[38]." ".$destDir." ".$mess[99];
            return ;
        }
        $repoData = [
            'base_url'      => $selection->currentBaseUrl(),
            'chmod'         => $selection->getContext()->getRepository()->getContextOption($selection->getContext(), 'CHMOD_VALUE'),
            'recycle'       => $selection->getContext()->getRepository()->getContextOption($selection->getContext(), 'RECYCLE_BIN')
        ];
        foreach ($selectedNodes as $selectedNode) {
            $selectedFile = $selectedNode->getPath();
            if ($move && !$this->isWriteable($selection->nodeForPath(PathUtils::forwardSlashDirname($selectedFile)))) {
                $error[] = "\n".$mess[38]." ".PathUtils::forwardSlashDirname($selectedFile)." ".$mess[99];
                continue;
            }
            if( !empty ($targetBaseName)){
                $destFile = $destDir ."/" . $targetBaseName;
            }else{
                $bName = basename($selectedFile);
                $localName = '';
                Controller::applyHook("dl.localname", [$selectedNode, &$localName]);
                if(!empty($localName)) $bName = $localName;
                $destFile = $destDir ."/". $bName;
            }
            $this->copyOrMoveFile($destFile, $selectedFile, $error, $success, $move, $repoData, $repoData, $taskId);
        }
    }

    /**
     * @param Node $originalNode
     * @param Node $dest
     * @param string $filename_new
     * @return Node
     * @throws PydioException
     * @throws \Exception
     */
    public function rename($originalNode, $dest = null, $filename_new = null)
    {
        $mess = LocaleService::getMessages();

        if(!empty($filename_new)){
            $filename_new= InputFilter::sanitize(InputFilter::magicDequote($filename_new), InputFilter::SANITIZE_FILENAME, true);
            $filename_new = cropFilename($filename_new, ConfService::getContextConf($originalNode->getContext(), "NODENAME_MAX_LENGTH"));
        }

        if (empty($filename_new) && empty($dest)) {
            throw new PydioException("$mess[37]");
        }
        $originalNode->loadNodeInfo();

        if (!$originalNode->isWriteable()) {
            throw new PydioException($mess[34]." ".$originalNode->getLabel()." ".$mess[99]);
        }

        if($dest == null) {
            $newNode = $originalNode->getParent()->createChildNode($filename_new);
        } else {
            $newNode = $dest;
        }

        $caseChange = ($newNode->getPath() !== $originalNode->getPath() && strtolower($newNode->getPath()) === strtolower($originalNode->getPath()));
        if (!$caseChange) {
            try{
                $newNode->loadNodeInfo();
                throw new PydioException($newNode->getPath()." $mess[43]");
            }catch (\Exception $e){}
        }
        $test = rename($originalNode->getUrl(),$newNode->getUrl());
        if($test === false){
            throw new \Exception("Error while renaming ".$originalNode->getPath()." to ".$newNode->getPath());
        }
        return $newNode;

    }

    /**
     * @param $destination
     * @param $fileName
     * @return string
     */
    public static function autoRenameForDest($destination, $fileName)
    {
        if(!is_file($destination."/".$fileName)) return $fileName;
        $i = 1;
        $ext = "";
        $split = explode(".", $fileName);
        if (count($split) > 1) {
            $ext = ".".$split[count($split)-1];
            array_pop($split);
            $name = join(".", $split);
        } else {
            $name = $fileName;
        }
        while (is_file($destination."/".$name."-$i".$ext)) {
            $i++; // increment i until finding a non existing file.
        }
        return $name."-$i".$ext;
    }

    /**
     * @param Node $parentNode
     * @param String $newDirName
     * @param bool $ignoreExists
     * @param bool $createRecursive
     * @return Node
     * @throws PydioException
     * @throws \Exception
     */
    public function mkDir($parentNode, $newDirName, $ignoreExists = false, $createRecursive = false)
    {
        /*
        if(!$parentNode->exists() && $createRecursive){
            $this->mkDir($parentNode->getParent(), basename($parentNode->getUrl()), $ignoreExists, true);
        }
        */

        $mess = LocaleService::getMessages();
        if ($newDirName=="") {
            throw new PydioException($mess[37]);
        }
        $childNode = $parentNode->createChildNode($newDirName);
        if ($childNode->exists()) {
            if($ignoreExists) {
                return $parentNode->createChildNode($newDirName);
            }
            throw new PydioException($mess[40]);
        }
        /*
        if (!$parentNode->exists()){
            throw new PydioException($mess[103]." ".$parentNode->getPath());
        }
        */

        $dirMode = 0775;
        $ctx = $parentNode->getContext();
        $chmodValue = $ctx->getRepository()->getContextOption($ctx, "CHMOD_VALUE");
        if (isSet($chmodValue) && $chmodValue != "") {
            $dirMode = octdec(ltrim($chmodValue, "0"));
            if ($dirMode & 0400) $dirMode |= 0100; // User is allowed to read, allow to list the directory
            if ($dirMode & 0040) $dirMode |= 0010; // Group is allowed to read, allow to list the directory
            if ($dirMode & 0004) $dirMode |= 0001; // Other are allowed to read, allow to list the directory
        }
        $old = umask(0);
        mkdir($childNode->getUrl(), $dirMode);
        umask($old);
        $newNode =  $childNode;
        $childNode->setLeaf(false);
        return $newNode;

    }

    /**
     * @param Node $node
     * @param string $content
     * @param bool $force
     * @throws \Exception
     */
    public function createEmptyFile(Node $node, $content = "", $force = false)
    {
        $mess = LocaleService::getMessages();

        if (!$force && $node->exists()) {
            throw new PydioException($mess[71], 71);
        }
        if (!$this->isWriteable($node->getParent())) {
            throw new PydioException("$mess[38] ".$node->getParent()->getPath()." $mess[99]", 71);
        }
        $ctx = $node->getContext();
        $repoData = [
            'chmod'     => $ctx->getRepository()->getContextOption($ctx, 'CHMOD_VALUE'),
            'recycle'   => $ctx->getRepository()->getContextOption($ctx, 'RECYCLE_BIN')
        ];
        $fp=fopen($node->getUrl(),"w");
        if ($fp) {
            if ($content != "") {
                fputs($fp, $content);
            }
            $this->changeMode($node->getUrl(), $repoData);
            fflush($fp);
            fclose($fp);
            $node->loadNodeInfo();
        } else {
            throw new PydioException("$mess[102] ".$node->getPath()." (".$fp.")");
        }
    }


    /**
     * @param UserSelection $selection
     * @param $logMessages
     * @param null $taskId
     * @return null
     * @throws PydioException
     * @throws \Exception
     */
    public function delete(UserSelection $selection, &$logMessages, $taskId = null)
    {
        $ctx = $selection->getContext();
        $repoData = [
            'chmod'         => $ctx->getRepository()->getContextOption($ctx, 'CHMOD_VALUE'),
            'recycle'       => $ctx->getRepository()->getContextOption($ctx, 'RECYCLE_BIN')
        ];
        $mess = LocaleService::getMessages();
        $selectedNodes = $selection->buildNodes();
        foreach ($selectedNodes as $selectedNode) {
            try{
                $selectedNode->loadNodeInfo();
            } catch (\Exception $e){
                continue;
            }
            $fileUrl = $selectedNode->getUrl();
            $filePath = $selectedNode->getPath();
            if (!$selectedNode->isLeaf()) {
                $logMessages[]="$mess[38] ".$filePath." $mess[44].";
            } else {
                $logMessages[]="$mess[34] ".$filePath." $mess[44].";
            }
            $this->deldir($fileUrl, $repoData, !$selectedNode->isLeaf());
        }
        return null;
    }

    /**
     * @param $origFile
     * @param $destFile
     * @return bool
     */
    public function simpleCopy($origFile, $destFile)
    {
        return copy($origFile, $destFile);
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isWriteable(Node $node)
    {
        return is_writable($node->getUrl());
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isReadable(Node $node)
    {
        try{
            $node->loadNodeInfo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Change file permissions
     *
     * @param Node $node
     * @param String $chmodValue
     * @param Boolean $recursive
     * @param String $nodeType "both", "file", "dir"
     * @param $changedFiles
     * @return void
     */
    public function chmod(Node $node, $chmodValue, $recursive, $nodeType, &$changedFiles)
    {
        $realValue = octdec(ltrim($chmodValue, "0"));
        $nodeUrl = $node->getUrl();
        if (is_file($nodeUrl)) {
            if ($nodeType=="both" || $nodeType=="file") {
                MetaStreamWrapper::changeMode($nodeUrl, $realValue);
                $changedFiles[] = $node->getPath();
            }
        } else {
            if ($nodeType=="both" || $nodeType=="dir") {
                MetaStreamWrapper::changeMode($nodeUrl, $realValue);
                $changedFiles[] = $node->getPath();
            }
            if ($recursive) {
                $handler = opendir($nodeUrl);
                while ($child=readdir($handler)) {
                    if($child == "." || $child == "..") continue;
                    // do not pass realValue or it will be re-decoded.
                    $this->chmod($node->createChildNode($child), $chmodValue, $recursive, $nodeType, $changedFiles);
                }
                closedir($handler);
            }
        }
    }

}

function cropFilename($filename, $max_length)
{
    if(mb_strlen($filename, "8bit") <= $max_length) return $filename;
    $utf8_name = SystemTextEncoding::toUTF8($filename);
    $utf8_name = mb_substr($utf8_name, 0, $max_length, "8bit");
    $utf8_name = rtrim(iconv("UTF-8", "UTF-8//IGNORE", $utf8_name));
    return SystemTextEncoding::fromUTF8($utf8_name);
}

