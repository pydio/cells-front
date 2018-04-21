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

use Pydio\Access\Core\Model\Node;

use Pydio\Access\Driver\StreamProvider\FS\FsAccessWrapper;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\SessionService;
use Pydio\Core\Utils\FileHelper;
use Pydio\Core\Utils\Vars\StringHelper;
use Pydio\Core\Utils\Vars\UrlUtils;
use Pydio\Log\Core\Logger;

use Aws\Middleware;
use Psr\Http\Message\RequestInterface;
use Swagger\Client\ApiException;
use Swagger\Client\Model\RestUserJobRequest;

defined('PYDIO_EXEC') or die('Access not allowed');

require_once(PYDIO_INSTALL_PATH . "/plugins/access.fs/FsAccessWrapper.php");


/**
 * Encapsulation of the PEAR webDAV client
 * @package AjaXplorer_Plugins
 * @subpackage Access
 */
class S3AccessWrapper extends FsAccessWrapper
{
    public static $lastException;
    protected static $clients = [];

    /**
     * @param Node $node
     * @return array
     */
    public static function getResolvedOptionsForNode($node)
    {
        $context = $node->getContext();
        $repository = $node->getRepository();

        $options = [
            "TYPE" => "s3"
        ];
        $optKeys = ["API_KEY", "SECRET_KEY", "CONTAINER", "SIGNATURE_VERSION", "STORAGE_URL", "REGION", "PROXY", "API_VERSION", "VHOST_NOT_SUPPORTED"];
        foreach($optKeys as $key){
            $options[$key] = $repository->getContextOption($context, $key);
        }
        if(empty($options["API_VERSION"])) {
            $options["API_VERSION"] = "latest";
        }
        return $options;
    }

    /**
     * @param ContextInterface $ctx
     * @param boolean $registerStream
     * @return S3Client
     */
    public static function getClientForContext(ContextInterface $ctx, $registerStream = true)
    {
        $repoObject = $ctx->getRepository();
        if (!isSet(self::$clients[$repoObject->getId()])) {
            // Get a client
            $options = array(
                'key'       => $repoObject->getContextOption($ctx, "API_KEY"),
                'secret'    => $repoObject->getContextOption($ctx, "SECRET_KEY")
            );
            $signatureVersion = $repoObject->getContextOption($ctx, "SIGNATURE_VERSION");
            if (!empty($signatureVersion)) {
                $options['signature'] = $signatureVersion;
            }
            $apiVersion = $repoObject->getContextOption($ctx, "API_VERSION");
            if ($apiVersion === "") {
                $apiVersion = "latest";
            }
            $config = [
                "version" => $apiVersion,
                "credentials" => $options
            ];
            $region = $repoObject->getContextOption($ctx, "REGION");
            if (!empty($region)) {
                $config["region"] = $region;
            }
            $proxy = $repoObject->getContextOption($ctx, "PROXY");
            if (!empty($proxy)) {
                $config['http'] = array('proxy' => $proxy);
            }
            $skip = ConfService::bootstrapCoreConf("SKIP_SSL_VERIFY");
            if($skip){
                if(empty($config['http'])) $config['http'] = [];
                $config['http']['verify'] = false;
            }
            $baseURL = $repoObject->getContextOption($ctx, "STORAGE_URL");
            if (!empty($baseURL)) {
                if ($baseURL == "core.conf/ENDPOINT_S3_GATEWAY") {
                    $baseURL = ConfService::getGlobalConf("ENDPOINT_S3_GATEWAY", "conf");
                }
                $config["endpoint"] = $baseURL;
                $config["use_path_style_endpoint"] = true;
            }
            require_once("S3Client.php");
            $s3Client = new S3Client($config, $repoObject->getId());

            // Apply a custom middleware named "add-pydio-header" to the "build" lifecycle step
            $s3Client->getHandlerList()->appendBuild(
                Middleware::mapRequest(function (RequestInterface $request) {
                    MicroApi::buildHeaders($request, true);
                    return $request;
                }),
                'add-pydio-header'
            );

            $s3Client->registerStreamWrapper();
            self::$clients[$repoObject->getId()] = $s3Client;
        }
        return self::$clients[$repoObject->getId()];
    }

    /**
     * Initialize the stream from the given path.
     * Concretely, transform ajxp.s3:// into s3://
     *
     * @param string $path
     * @param $streamType
     * @param bool $storeOpenContext
     * @param bool $skipZip
     * @return mixed Real path or -1 if currentListing contains the listing : original path converted to real path
     * @throws \Exception
     */
    protected static function initPath($path, $streamType, $storeOpenContext = false, $skipZip = false)
    {
        $url        = UrlUtils::mbParseUrl($path);
        $node       = new Node($path);
        $repoId     = $node->getRepositoryId();
        $repoObject = $node->getRepository();
        if (!isSet($repoObject)) {
            $e = new \Exception("Cannot find repository with id " . $repoId);
            self::$lastException = $e;
            throw $e;
        }
        // Make sure to register s3:// wrapper
        $client = self::getClientForContext($node->getContext(), true);
        $protocol = "s3://";
        if ($client instanceof S3Client) {
            $protocol = "s3." . $repoId . "://";
        }
        $basePath       = $repoObject->getContextOption($node->getContext(), "PATH");
        $baseContainer  = $repoObject->getContextOption($node->getContext(), "CONTAINER");
        if (!empty($basePath)) {
            $baseContainer .= rtrim($basePath, "/");
        }
        $p = $protocol . $baseContainer . str_replace("//", "/", $url["path"]);
        return $p;
    }

    /**
     * Opens the stream
     * Diff with parent class : do not "securePath", as it removes double slash
     *
     * @param String $path Maybe in the form "ajxp.fs://repositoryId/pathToFile"
     * @param String $mode
     * @param string $options
     * @param resource $context
     * @return resource
     * @internal param string $opened_path
     */
    public function stream_open($path, $mode, $options, &$context)
    {
        try {
            $this->realPath = $this->initPath($path, "file");
        } catch (\Exception $e) {
            Logger::error(__CLASS__, "stream_open", "Error while opening stream $path");
            return false;
        }
        if ($this->realPath == -1) {
            $this->fp = -1;
            return true;
        } else {
            $this->fp = fopen($this->realPath, $mode, $options);
            return ($this->fp !== false);
        }
    }

    /**
     * Stats the given path.
     * Fix PEAR by adding S_ISREG mask when file case.
     *
     * @param string $path
     * @param integer $flags
     * @return array
     */
    public function url_stat($path, $flags)
    {
        // File and zip case
        // Logger::debug(__CLASS__,__FUNCTION__,"Stating $path");
        $innerPath = $this->initPath($path, "file");
        $stat = @stat($innerPath);
        if ($stat == null) return null;
        if ($stat["mode"] == 0666) {
            $stat[2] = $stat["mode"] |= 0100000; // S_ISREG
        }
        if($stat["mode"] === 0040777){
            $node       = new Node($path);
            $ctx = $node->getContext();
            $repoObject = $node->getRepository();
            $nodePath = ltrim($node->getPath(), "/"). '/';
            $basePath = $repoObject->getContextOption($ctx, "PATH");
            $folderPath = ltrim( rtrim($basePath, '/') . '/' . $nodePath, '/');
            $result = $this->getClientForContext($ctx)->listObjects([
                'Bucket'  => $repoObject->getContextOption($ctx, "CONTAINER"),
                'Prefix'  => $folderPath,
                'MaxKeys' => 1
            ]);
            if (isSet($result['Contents']) && isSet($result['Contents'][0]['LastModified'])) {
                $stat = $this->statForDir($result['Contents'][0]['LastModified']);
            }

        }
        return $stat;
    }

    /**
     * @param $lastModified
     * @return array
     */
    protected function statForDir($lastModified){
        $time = strtotime($lastModified);
        return [
            0  => 0,  'dev'     => 0,
            1  => 0,  'ino'     => 0,
            2  => 0040777,  'mode'    => 0040777,
            3  => 0,  'nlink'   => 0,
            4  => 0,  'uid'     => 0,
            5  => 0,  'gid'     => 0,
            6  => -1, 'rdev'    => -1,
            7  => 0,  'size'    => 0,
            8  => $time,  'atime'   => $time,
            9  => $time,  'mtime'   => $time,
            10 => $time,  'ctime'   => $time,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks'  => -1,
        ];

    }

    /**
     * Opens a handle to the dir
     * Fix PEAR by being sure it ends up with "/", to avoid
     * adding the current dir to the children list.
     *
     * @param string $path
     * @param string $options
     * @return resource|bool
     */
    public function dir_opendir($path, $options)
    {
        $this->realPath = $this->initPath($path, "dir", true);
        if ($this->realPath[strlen($this->realPath) - 1] != "/") {
            $this->realPath .= "/";
        }
        if (is_string($this->realPath)) {
            $this->dH = @opendir($this->realPath);
        } else if ($this->realPath == -1) {
            $this->dH = -1;
        }
        return $this->dH !== false;
    }

    public function mkdir($path, $mode, $options){
        $url        = UrlUtils::mbParseUrl($path);
        $node       = new Node($path);
        $repoId     = $node->getRepositoryId();
        $repoObject = $node->getRepository();
        if (!isSet($repoObject)) {
            $e = new \Exception("Cannot find repository with id " . $repoId);
            self::$lastException = $e;
            throw $e;
        }
        $folderEmptyFile       = $repoObject->getContextOption($node->getContext(), "S3_FOLDER_EMPTY_FILE");
        if(!empty($folderEmptyFile)){
            $s3path = $this->initPath($path, "file") . "/" . $folderEmptyFile;
            file_put_contents($s3path, StringHelper::generateRandomString(36));
            return true;
        }else{
            return mkdir($this->initPath($path, "file"), $mode);
        }
    }

    /**
     * DUPLICATE STATIC FUNCTIONS TO BE SURE
     * NOT TO MESS WITH self:: CALLS
     * @param $tmpDir
     * @param $tmpFile
     */
    public static function removeTmpFile($tmpDir, $tmpFile)
    {
        if (is_file($tmpFile)) unlink($tmpFile);
        if (is_dir($tmpDir)) rmdir($tmpDir);
    }

    /**
     * @inheritdoc
     */
    public static function getRealFSReference($path, $persistent = false)
    {
        $tmpFile = ApplicationState::getTemporaryFolder() . "/" . md5(time()) . "." . pathinfo($path, PATHINFO_EXTENSION);
        $tmpHandle = fopen($tmpFile, "wb");
        self::copyFileInStream($path, $tmpHandle);
        fclose($tmpHandle);
        if (!$persistent) {
            register_shutdown_function(function () use ($tmpFile) {
                FileHelper::silentUnlink($tmpFile);
            });
        }
        return $tmpFile;
    }

    /**
     * @inheritdoc
     */
    public static function isRemote($url)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function copyFileInStream($path, $stream)
    {
        Logger::debug(__CLASS__, __FUNCTION__, "Should load " . $path);
        $fp = fopen($path, "r");
        if (!is_resource($fp)) return;
        while (!feof($fp)) {
            $data = fread($fp, 4096);
            fwrite($stream, $data, strlen($data));
        }
        fclose($fp);
    }

    /**
     * @inheritdoc
     */
    public static function changeMode($path, $chmodValue)
    {
    }

    /**
     * @inheritdoc
     */
    public function rename($from, $to)
    {

        $node = new Node($from);
        $node->loadNodeInfo();
        $ctx = $node->getContext();
        $repoObject = $node->getRepository();
        if (!isSet($repoObject)) {
            $e = new \Exception("Cannot find repository with id " . $node->getRepositoryId());
            self::$lastException = $e;
            throw $e;
        }

        if (!$node->isLeaf()) {
            Logger::debug(__CLASS__, __FUNCTION__, "S3 Renaming dir $from to $to");

            $bucket = $repoObject->getContextOption($ctx, "CONTAINER");
            $basePath = $repoObject->getContextOption($ctx, "PATH");
            $fromKeyname = trim(str_replace("//", "/", $basePath . UrlUtils::mbParseUrl($from, PHP_URL_PATH)), '/');
            $toKeyname = trim(str_replace("//", "/", $basePath . UrlUtils::mbParseUrl($to, PHP_URL_PATH)), '/');

            $request = new RestUserJobRequest();
            $request->setJobName("move");
            $request->setJsonParameters(json_encode([
                "nodes"         => [$fromKeyname],
                "target"        => $toKeyname,
            ]));
            $api = MicroApi::GetJobsServiceApi();
            try{
                $response = $api->userCreateJob("move", $request);
            }catch (ApiException $a){
                return false;
            }
            return true;

        } else {
            Logger::debug(__CLASS__, __FUNCTION__, "S3 Execute standard rename on " . $from . " to " . $to);
            return parent::rename($from, $to);
        }
    }

}
