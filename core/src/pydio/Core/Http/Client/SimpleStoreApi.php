<?php
/*
 * Copyright 2007-2016 Abstrium <contact (at) pydio.com>
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

namespace Pydio\Core\Http\Client;

use Aws\Middleware;
use Aws\S3\S3Client;
use Psr\Http\Message\RequestInterface;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Utils\Vars\StatHelper;
use Swagger\Client\Api\DocStoreServiceApi;
use Swagger\Client\ApiException;
use Swagger\Client\Model\DocstoreDeleteDocumentsRequest;
use Swagger\Client\Model\DocstoreDocument;
use Swagger\Client\Model\DocstoreDocumentQuery;
use Swagger\Client\Model\DocstorePutDocumentRequest;
use Swagger\Client\Model\RestListDocstoreRequest;

defined('PYDIO_EXEC') or die('Access not allowed');


class SimpleStoreApi extends MicroApi {

    /**
     * @var S3Client
     */
    private $s3client;

    /**
     * SimpleStoreApi constructor.
     * @param $anonymous bool
     */
    public function __construct() {
        parent::__construct();

        // Get a client
        $options = array(
            'version'   => "latest",
            'region'    => "us-east-1",
            'credentials' => [
                'key'       => "gateway",
                'secret'    => "gatewaysecret",
                'signature' => "v4",
            ],
            'endpoint'      => ConfService::getGlobalConf("ENDPOINT_S3_GATEWAY", "conf"),
            'use_path_style_endpoint' => true

        );
        $skip = ConfService::bootstrapCoreConf("SKIP_SSL_VERIFY");
        if($skip){
            $options['http'] = ['verify' => false];
        }
        $this->s3Client = new S3Client($options);
        // Apply a custom middleware named "add-pydio-header" to the "build" lifecycle step
        $this->s3Client->getHandlerList()->appendBuild(
            Middleware::mapRequest(function (RequestInterface $request) {
                MicroApi::buildHeaders($request, true);
                return $request;
            }),
            'add-pydio-header'
        );
    }

    public static function escapeMetaValue($metaQuery){
        return str_replace(["+","-","=","&","|",">","<","!","(",")","{","}","[","]","^","\"","~","*","?",":","/"," "],
            ["\+","\-","\=","\&","\|","\>","\<","\!","\(","\)","\{","\}","\[","\]","\^","\\\"","\~","\*","\?","\:","\/","\ "], $metaQuery);
    }

    public static function buildMetaQuery($metaKeyValue) {
        $parts = [];
        foreach($metaKeyValue as $key => $value){
            $parts[] = "+".$key .":". self::escapeMetaValue($value);
        }
        return implode(" ", $parts);
    }

    /**
     * @param $storeId string
     * @param $documentId string
     * @param $owner string
     * @param $documentData array
     * @param $indexableData array
     * @throws PydioException
     */
    public function storeDocument($storeId, $documentId, $owner, $documentData, $indexableData) {

        // Make sure it's a string
        $documentId .= "";

        $api = new DocStoreServiceApi(self::getApiClient());
        $document = new DocstoreDocument();
        $document->setId($documentId);
        $document->setData(json_encode($documentData));
        $document->setIndexableMeta(json_encode($indexableData));

        $request = new DocstorePutDocumentRequest();
        $request->setStoreId($storeId);
        $request->setDocument($document);
        $request->setDocumentId($documentId);

        try {
            $response = $api->putDoc($storeId, $documentId, $request);
        } catch (ApiException $e) {
            throw new PydioException($e->getMessage());
        }

    }

    /**
     * @param $storeId
     * @param $documentId
     * @return mixed
     * @throws PydioException
     */
    public function loadDocument($storeId, $documentId) {

        $api = new DocStoreServiceApi(self::getApiClient());
        try {
            $response = $api->getDoc($storeId, $documentId . "");
            $document = $response->getDocument();
            if ($document !== null) {
                return json_decode($document->getData(), true);
            } else {
                return [];
            }
        } catch (ApiException $e){
            return [];
        }

    }

    /**
     * @param $storeId
     * @param $documentId
     * @return mixed
     * @throws PydioException
     */
    public function deleteDocuments($storeId, $documentId = "", $searchQuery = "", $owner = "") {

        $api = new DocStoreServiceApi(self::getApiClient());
        $request = new DocstoreDeleteDocumentsRequest();
        $request->setStoreId($storeId);
        if (!empty($documentId)) {
            $request->setDocumentId($documentId . "");
        } else {
            $query = new DocstoreDocumentQuery();
            $query->setMetaQuery($searchQuery);
            $query->setOwner($owner);
            $request->setQuery($query);
        }
        $response = $api->deleteDoc($storeId, $request);
        return $response->getDeletionCount();

    }

    /**
     * @param $storeId
     * @param $searchQuery
     * @param $owner
     * @return array|int
     * @throws PydioException
     */
    public function listDocuments($storeId, $searchQuery, $owner = "", $countOnly = false) {

        $api = new DocStoreServiceApi(self::getApiClient());
        $request = new RestListDocstoreRequest();
        $request->setStoreId($storeId);
        if ($countOnly) {
            $request->setCountOnly(true);
        }
        $query = new DocstoreDocumentQuery();
        $query->setOwner($owner);
        $query->setMetaQuery($searchQuery);
        $request->setQuery($query);

        try {
            $collection = $api->listDocs($storeId, $request);
        } catch (ApiException $e) {
            return [];
        }
        if ($countOnly) {
            return intval($collection->getTotal());
        }
        $docs = [];
        if ($collection->getDocs() != null){
            foreach ($collection->getDocs() as $doc) {
                $docs[$doc->getId()] = json_decode($doc->getData(), true);
            }
        }
        return $docs;
    }


    /**
     * @param $context
     * @return string
     */
    protected function binaryContextToStoreID($context)
    {
        $storage = "binaries";
        if (isSet($context["USER"])) {
            $storage ="users_binaries.".$context["USER"];
        } else if (isSet($context["REPO"])) {
            $storage ="repos_binaries.".$context["REPO"];
        } else if (isSet($context["ROLE"])) {
            $storage ="roles_binaries.".$context["ROLE"];
        } else if (isSet($context["PLUGIN"])) {
            $storage ="plugins_binaries.".$context["PLUGIN"];
        }
        return $storage;
    }
    /**
     * @param array $context
     * @param String $fileName
     * @param String $ID
     * @return String $ID
     */
    public function saveBinary($context, $fileName, $ID = null)
    {
        if (empty($ID)) {
            $ID = substr(md5(microtime()*rand(0,100)), 0, 12);
            $ID .= ".".pathinfo($fileName, PATHINFO_EXTENSION);
        }
        $store = $this->binaryContextToStoreID($context);
        $this->s3Client->putObject([
            'Bucket' => 'io',
            'Key' => "pydio-binaries/" . $store."-".$ID,
            'Body' => file_get_contents($fileName),
        ]);
        return $ID;
    }

    /**
     * @abstract
     * @param array $context
     * @param String $ID
     * @return boolean
     */
    public function deleteBinary($context, $ID)
    {
        $store = $this->binaryContextToStoreID($context);
        $this->s3Client->deleteObject([
            'Bucket' => 'io',
            'Key'   => "pydio-binaries/". $store.'-'.$ID,
        ]);
    }


    /**
     * @param array $context
     * @param String $ID
     * @param Resource $outputStream
     * @return boolean
     */
    public function loadBinary($context, $ID, $outputStream = null)
    {
        $store = $this->binaryContextToStoreID($context);
        $result = $this->s3Client->getObject([
            'Bucket' => 'io',
            'Key'   => "pydio-binaries/". $store.'-'.$ID,
        ]);
        $data = $result['Body'];
        header("Cache-Control: max-age=3600, public");
        header("Content-Length: " . $result["ContentLength"]);
        header("Last-Modified: " . $result["LastModified"]);
        if ($outputStream != null) {
            fwrite($outputStream, $data, strlen($data));
        } else {
            header("Content-Type: ". StatHelper::getImageMimeType($ID));
            echo $data;
        }
    }




}