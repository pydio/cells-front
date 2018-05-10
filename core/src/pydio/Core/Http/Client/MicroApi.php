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
 * The latest code can be found at <https://pydio.com>.
 */
namespace Pydio\Core\Http\Client;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Pydio\Access\Core\Model\Node;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Utils\Vars\StringHelper;
use Swagger\Client\Api\ACLServiceApi;
use Swagger\Client\Api\ActivityServiceApi;
use Swagger\Client\Api\AdminTreeServiceApi;
use Swagger\Client\Api\ChangeServiceApi;
use Swagger\Client\Api\ConfigServiceApi;
use Swagger\Client\Api\FrontendServiceApi;
use Swagger\Client\Api\FrontLogServiceApi;
use Swagger\Client\Api\GraphServiceApi;
use Swagger\Client\Api\JobsServiceApi;
use Swagger\Client\Api\LogServiceApi;
use Swagger\Client\Api\MailerServiceApi;
use Swagger\Client\Api\MetaServiceApi;
use Swagger\Client\Api\PolicyServiceApi;
use Swagger\Client\Api\RoleServiceApi;
use Swagger\Client\Api\SearchServiceApi;
use Swagger\Client\Api\TokenServiceApi;
use Swagger\Client\Api\UserMetaServiceApi;
use Swagger\Client\Api\UserServiceApi;
use Swagger\Client\Api\WorkspaceServiceApi;
use Swagger\Client\ApiClient;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\RestConfiguration;

class MicroApi {

    const CONFIG_NAMESPACE_FRONTEND = "frontend";
    const CONFIG_NAMESPACE_SERVICES = "services";

    protected static $requestID;
    protected $restUrl;
    protected $client;

    public function __construct() {
        $this->restUrl = ConfService::getGlobalConf("ENDPOINT_REST_API", "conf");
        $this->client = new Client();
    }

    protected static function getApiClient() {
        $api = new MicroApi();
        $headers = self::buildHeaders();
        $configuration = new Configuration();
        $skip = ConfService::bootstrapCoreConf("SKIP_SSL_VERIFY");
        if($skip){
            $configuration->setSSLVerification(false);
        }
        $configuration->setHost($api->restUrl);
        foreach($headers as $h => $v) {
            $configuration->addDefaultHeader($h, $v);
        }
        $client = new ApiClient($configuration);
        return $client;
    }

    public static function buildHeaders(RequestInterface &$requestInterface = null, $authAsXPydio = false){

        $token = DexApi::getValidToken();
        $headers = [];
        if(empty(self::$requestID)){
            // Create a unique ID for each PHP request
            self::$requestID = strtolower(StringHelper::createGUID());
        }
        if(!empty($token)){
            $headers["X-Pydio-Front-Client"] = $_SERVER["REMOTE_ADDR"];
            $headers["X-Pydio-Span-Id"] = self::$requestID;
            $headers["User-Agent"] = $_SERVER["HTTP_USER_AGENT"];
            if($authAsXPydio){
                $headers["X-Pydio-Bearer"] = $token;
                $host = ConfService::bootstrapCoreConf("IO_HOST_SIGNATURE_HEADER");
                if(!empty($host)){
                    $headers["Host"] = $host;
                }
            } else {
                $headers["Authorization"] = "Bearer " . $token;
            }
        }
        if($requestInterface != null) {
            foreach($headers as $key => $value){
                $requestInterface = $requestInterface->withHeader($key, $value);
            }
        }
        return $headers;

    }

    /**********************************/
    /* ROLE MANAGEMENT METHODS    */
    /**********************************/
    /**
     * @return RoleServiceApi
     */
    public static function GetRoleServiceApi() {
        return new RoleServiceApi(self::getApiClient());
    }

    /**********************************/
    /* USER MANAGEMENT METHODS    */
    /**********************************/
    /**
     * @return UserServiceApi
     */
    public static function GetUserServiceApi() {
        return new UserServiceApi(self::getApiClient());
    }


    /**********************************/
    /* WORKSPACE MANAGEMENT METHODS    */
    /**********************************/
    /**
     * @return WorkspaceServiceApi
     */
    public static function GetWorkspaceServiceApi(){
        return new WorkspaceServiceApi(self::getApiClient());
    }

    /**
     * @return ACLServiceApi
     */
    public static function GetAclServiceApi(){
        return new ACLServiceApi(self::getApiClient());
    }

    /**
     * @return ActivityServiceApi
     */
    public static function GetActivityServiceApi(){
        return new ActivityServiceApi(self::getApiClient());
    }


    /**
     * @return FrontendServiceApi
     */
    public static function GetFrontendServiceApi(){
        return new FrontendServiceApi(self::getApiClient());
    }

    /**
     * @return LogServiceApi
     */
    public static function GetLogServiceApi(){
        return new LogServiceApi(self::getApiClient());
    }

    /**
     * @return ConfigServiceApi
     */
    public static function GetConfigServiceApi(){
        return new ConfigServiceApi(self::getApiClient());
    }

    /**
     * @return MailerServiceApi
     */
    public static function GetMailerServiceApi(){
        return new MailerServiceApi(self::getApiClient());
    }

    /**
     * @return SearchServiceApi
     */
    public static function GetSearchServiceApi(){
        return new SearchServiceApi(self::getApiClient());
    }

    /**
     * @return MetaServiceApi
     */
    public static function GetMetaServiceApi(){
        return new MetaServiceApi(self::getApiClient());
    }

    /**
     * @return AdminTreeServiceApi
     */
    public static function GetAdminTreeServiceApi(){
        return new AdminTreeServiceApi(self::getApiClient());
    }

    /**
     * @return JobsServiceApi
     */
    public static function GetJobsServiceApi(){
        return new JobsServiceApi(self::getApiClient());
    }

    /**
     * @return GraphServiceApi
     */
    public static function GetGraphServiceApi(){
        return new GraphServiceApi(self::getApiClient());
    }

    /**
     * @return PolicyServiceApi
     */
    public static function GetPolicyServiceApi(){
        return new PolicyServiceApi(self::getApiClient());
    }

    /**
     * @return ChangeServiceApi
     */
    public static function GetChangesServiceApi(){
        return new ChangeServiceApi(self::getApiClient());
    }

    /**
     * @return UserMetaServiceApi
     */
    public static function GetUserMetaServiceApi() {
        return new UserMetaServiceApi(self::getApiClient());
    }

    /**
     * @return TokenServiceApi
     */
    public static function GetTokenServiceApi() {
        return new TokenServiceApi(self::getApiClient());
    }

    /**********************************/
    /* METADATA MANAGEMENT METHODS    */
    /**********************************/

    /**
     * @param $ajxpNode Node
     * @param $nameSpace string
     * @param $metaData array
     * @throws PydioException
     */
    public function configSet($namespace, $path, $data)
    {
        $api = new ConfigServiceApi(self::getApiClient());
        $request = new RestConfiguration();
        $fullPath = $namespace."/".$path;
        $request->setFullPath($fullPath);
        $request->setData(json_encode($data));
        $response = $api->putConfig($fullPath, $request);
    }

    /**
     * @param $ajxpNode Node
     * @param $nameSpace string
     * @param $metaData array
     * @throws PydioException
     */
    public function configGet($namespace, $path)
    {
        if(empty($this->restUrl)) {
            return [];
        }
        $api = new ConfigServiceApi(self::getApiClient());
        try{
            $configObject = $api->getConfig($namespace."/".$path);
        } catch(ApiException $e){
            return [];
        }
        $decoded = json_decode($configObject->getData(), true);
        if (is_array($decoded)) {
            return $decoded;
        } else {
            return [];
        }
    }

}
