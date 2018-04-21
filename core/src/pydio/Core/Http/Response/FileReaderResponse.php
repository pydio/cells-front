<?php
/*
 * Copyright 2007-2017 Abstrium <contact (at) pydio.com>
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
 * The latest code can be found at <https://pydio.com/>.
 */
namespace Pydio\Core\Http\Response;

use Pydio\Access\Core\Exception\FileNotFoundException;
use Pydio\Access\Core\MetaStreamWrapper;
use Pydio\Access\Core\Model\Node;
use Pydio\Core\Controller\HTMLWriter;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Utils\FileHelper;
use Pydio\Core\Utils\Vars\PathUtils;
use Pydio\Core\Utils\Vars\StatHelper;
use Pydio\Log\Core\Logger;
use Zend\Diactoros\ServerRequestFactory;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class FileReaderResponse
 * Async class that can be used as a Body for a ResponseInterface. Will read the file or data
 * when the reponse is emitted.
 *
 * @package Pydio\Core\Http\Response
 */
class FileReaderResponse extends AsyncResponseStream
{
    /** @var  Node */
    private $node;

    /** @var  string */
    private $file;

    /** @var string */
    private $data;

    /** @var  string */
    private $localName = "";

    /** @var  string */
    private $headerType = "force-download";

    /** @var  integer */
    private $offset = -1;

    /** @var  integer */
    private $length = -1;

    /** @var  callable */
    private $preRead;

    /** @var  callable */
    private $postRead;

    /** @var  boolean */
    private $unlinkAfterRead;

    /**
     * FileReaderResponse constructor.
     * @param Node|string $nodeOrFile
     * @param string $data
     */
    public function __construct($nodeOrFile = null, $data = null)
    {
        $callable = array($this, "readData");
        if($nodeOrFile instanceof  Node){
            $this->node = $nodeOrFile;
        }else{
            $this->file = $nodeOrFile;
        }
        if($data !== null){
            $this->data = $data;
        }
        parent::__construct($callable);
    }

    /**
     * Simple debug instruction
     * @param $message
     * @param array $params
     */
    protected function logDebug($message, $params = []){
        Logger::getInstance()->logDebug("FileReader", $message, $params);
    }

    /**
     * Set a possible header type "plain", "image" if it's force attachement.
     * @param $headerType
     */
    public function setHeaderType($headerType){
        $this->headerType = $headerType;
    }

    /**
     * Set the name as it should appear in the download window,
     * if it's not the file basename
     * @param $localName
     */
    public function setLocalName($localName){
        $this->localName = $localName;
    }

    /**
     * @param integer $offset
     * @param integer $length
     */
    public function setPartial($offset, $length){
        $this->offset = $offset;
        $this->length = $length;
    }

    /**
     * Callback to be triggered just before reading the file
     * @param callable $pre
     */
    public function setPreReadCallback(callable $pre){
        $this->preRead = $pre;
    }

    /**
     * Callback to be triggered at the end of the file read operation
     * @param callable $post
     */
    public function setPostReadCallback(callable $post){
        $this->postRead = $post;
    }

    /**
     * Set a flag to trigger an unlink after reading the file.
     */
    public function setUnlinkAfterRead(){
        $this->unlinkAfterRead = true;
    }

    /**
     * Actually read the data to the output
     * @throws \Exception
     */
    protected function readData(){
        if($this->preRead){
            call_user_func($this->preRead);
        }
        try{
            $this->readFile($this->node, $this->file, $this->data, $this->headerType, $this->localName, $this->offset, $this->length);
            if($this->postRead){
                call_user_func($this->postRead);
            }
        }catch (PydioException $e){
            Logger::error('FileReader', 'Read Data', $e->getMessage()." ".$e->getTraceAsString());
        }

    }

    /**
     * @param Node|null $node
     * @param string $filePath
     * @param string|bool $data
     * @param string $headerType
     * @param string $localName
     * @param int $byteOffset
     * @param int $byteLength
     * @throws \Exception
     */
    public function readFile($node = null, $filePath = null, $data = null, $headerType="plain", $localName="", $byteOffset=-1, $byteLength=-1)
    {
        if($node !== null){
            $filePathOrData = $node->getUrl();
            try{
                $node->loadNodeInfo();
            } catch (\Exception $e){
                throw new FileNotFoundException($filePathOrData);
            }
        }else if ($filePath != null){
            $filePathOrData = $filePath;
            if(!file_exists($filePath)){
                throw new FileNotFoundException($filePathOrData);
            }
        } else if($data === null) {
            throw new FileNotFoundException("");
        }

        $confGzip               = ConfService::getGlobalConf("GZIP_COMPRESSION");
        $confGzipLimit          = ConfService::getGlobalConf("GZIP_LIMIT");
        if($this->unlinkAfterRead && $filePathOrData !== null){
            register_shutdown_function(function () use ($filePathOrData){
                FileHelper::silentUnlink($filePathOrData);
            });
        }

        $fakeReq = ServerRequestFactory::fromGlobals();
        $serverParams = $fakeReq->getServerParams();

        if ($node !== null  && !$node->wrapperIsRemote()) {
            $originalFilePath = $filePathOrData;
            $filePathOrData = PathUtils::patchPathForBaseDir($filePathOrData);
        }
        session_write_close();

        restore_error_handler();
        restore_exception_handler();

        set_exception_handler('Pydio\Access\Driver\StreamProvider\FS\download_exception_handler');
        set_error_handler('Pydio\Access\Driver\StreamProvider\FS\download_exception_handler');
        // required for IE, otherwise Content-disposition is ignored
        if (ini_get('zlib.output_compression')) {
            ApplicationState::safeIniSet('zlib.output_compression', 'Off');
        }

        $isFile = ($data === null) && !$confGzip;
        if ($byteLength == -1) {
            if ($data !== null) {
                $size = strlen($data);
            } else if ($node === null) {
                $size = sprintf("%u", filesize($filePathOrData));
            } else {
                $size = floatval($node->getNodeInfoMeta()["bytesize"]);
            }
        } else {
            $size = $byteLength;
        }
        if ($confGzip && ($size > $confGzipLimit || !function_exists("gzencode") || (isSet($serverParams['HTTP_ACCEPT_ENCODING']) && strpos($serverParams['HTTP_ACCEPT_ENCODING'], 'gzip') === FALSE))) {
            $confGzip = false;
        }

        $localName = ($localName =="" ? basename((isSet($originalFilePath)?$originalFilePath:$filePathOrData)) : $localName);

        if ($headerType == "plain") {

            header("Content-type:text/plain");
            header("Content-Length: ".$size);

        } else if ($headerType == "image") {

            header("Content-Type: ". StatHelper::getImageMimeType(basename($filePathOrData)) ."; name=\"".$localName."\"");
            header("Content-Length: ".$size);
            header('Cache-Control: public');

        } else {

            if ($isFile) {
                header("Accept-Ranges: 0-$size");
                $this->logDebug("Sending accept range 0-$size");
            }

            // Check if we have a range header (we are resuming a transfer)
            if ( isset($serverParams['HTTP_RANGE']) && $isFile && $size != 0 ) {

                if ($headerType == "stream_content") {

                    if (extension_loaded('fileinfo')  && (( $node !== null && !$node->wrapperIsRemote()) || $filePath !== null)) {
                        $fInfo = new \fInfo( FILEINFO_MIME );
                        if($node !== null){
                            $realfile = $node->getRealFile();
                        }else{
                            $realfile = $filePathOrData;
                        }
                        $mimeType = $fInfo->file($realfile);
                        $splitChar = explode(";", $mimeType);
                        $mimeType = trim($splitChar[0]);
                        $this->logDebug("Detected mime $mimeType for $realfile");
                    } else {
                        $mimeType = StatHelper::getStreamingMimeType(basename($filePathOrData));
                    }
                    header('Content-type: '.$mimeType);
                }
                // multiple ranges, which can become pretty complex, so ignore it for now
                $ranges = explode('=', $_SERVER['HTTP_RANGE']);
                $offsets = explode('-', $ranges[1]);
                $offset = floatval($offsets[0]);

                $length = floatval($offsets[1]) - $offset;
                if (!$length) $length = $size - $offset;
                if ($length + $offset > $size || $length < 0) $length = $size - $offset;
                $this->logDebug('Content-Range: bytes ' . $offset . '-' . $length . '/' . $size);
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $size);

                header("Content-Length: ". $length);
                $file = fopen($filePathOrData, 'rb');
                if(!is_resource($file)){
                    throw new \Exception("Failed opening file ".$filePathOrData);
                }
                fseek($file, 0);
                $relOffset = $offset;
                while ($relOffset > 2.0E9) {
                    // seek to the requested offset, this is 0 if it's not a partial content request
                    fseek($file, 2000000000, SEEK_CUR);
                    $relOffset -= 2000000000;
                    // This works because we never overcome the PHP 32 bit limit
                }
                fseek($file, $relOffset, SEEK_CUR);

                while(ob_get_level()) ob_end_flush();
                $readSize = 0.0;
                $bufferSize = 1024 * 8;
                while (!feof($file) && $readSize < $length && connection_status() == 0) {
                    $this->logDebug("dl reading $readSize to $length", ["httpRange" => $serverParams["HTTP_RANGE"]]);
                    echo fread($file, $bufferSize);
                    $readSize += $bufferSize;
                    flush();
                }

                fclose($file);
                return;

            } else {

                if ($confGzip) {

                    $gzippedData = ($data?gzencode($filePathOrData,9):gzencode(file_get_contents($filePathOrData), 9));
                    $size = strlen($gzippedData);

                }

                HTMLWriter::emitAttachmentsHeaders($localName, $size, $isFile, $confGzip);

                if ($confGzip && isSet($gzippedData)) {
                    print $gzippedData;
                    return;
                }
            }
        }

        if ($data !== null) {

            print($data);

        } else {

            $stream = fopen("php://output", "a");

            if ($node == null) {

                $this->logDebug("realFS!", array("file"=>$filePathOrData));
                $fp = fopen($filePathOrData, "rb");
                if(!is_resource($fp)){
                    throw new \Exception("Failed opening file ".$filePathOrData);
                }
                if ($byteOffset != -1) {
                    fseek($fp, $byteOffset);
                }
                $sentSize = 0;
                $readChunk = 4096;
                while (!feof($fp)) {
                    if ( $byteLength != -1 &&  ($sentSize + $readChunk) >= $byteLength) {
                        // compute last chunk and break after
                        $readChunk = $byteLength - $sentSize;
                        $break = true;
                    }
                    $data = fread($fp, $readChunk);
                    $dataSize = strlen($data);
                    fwrite($stream, $data, $dataSize);
                    $sentSize += $dataSize;
                    if (isSet($break)) {
                        break;
                    }
                }
                fclose($fp);
            } else {

                MetaStreamWrapper::copyFileInStream($filePathOrData, $stream);

            }
            fflush($stream);
            fclose($stream);

        }
    }

}