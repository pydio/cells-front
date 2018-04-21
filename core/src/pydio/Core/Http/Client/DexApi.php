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

use DateTime;
use Exception;
use GuzzleHttp\Client;
use Pydio\Core\Exception\AuthRequiredException;
use Pydio\Core\Exception\LoginException;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\SessionService;
use Swagger\Client\Api\TokenServiceApi;
use Swagger\Client\Model\RestRevokeRequest;

/**
 * Class DexApi
 * @package Pydio\Core\Http\Client
 */
class DexApi extends MicroApi {

    const SESSION_TOKEN_KEY = "DEX-AUTH-TOKEN";
    const SESSION_TOKEN_TIME_KEY = "DEX-AUTH-TOKEN-TIME";

    protected $dexUrl;
    protected $clientID;
    protected $clientSecret;

    private static $restToken;
    private static $restTokenTime;

    public function __construct()
    {
        parent::__construct();
        $this->dexUrl = ConfService::getGlobalConf("ENDPOINT_DEX", "conf");
        $this->clientID = ConfService::getGlobalConf("ENDPOINT_DEX_CLIENTID", "conf");
        $this->clientSecret = ConfService::getGlobalConf("ENDPOINT_DEX_CLIENTSECRET", "conf");
    }

    public function setDexUrl($dexUrl) {
        $this->dexUrl = $dexUrl;
    }

    /**
     * @param mixed|null|string $clientID
     */
    public function setClientID($clientID)
    {
        $this->clientID = $clientID;
    }

    /**
     * @param mixed|null|string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    public static function getRestTokenData() {
        return ["tokens" => self::$restToken, "time" => self::$restTokenTime];
    }

    public static function presetRestToken($jwtToken, $jwtTokenTime){
        self::$restToken = $jwtToken;
        self::$restTokenTime = $jwtTokenTime;
    }

    /**
     * @param $token
     * @return \stdClass
     */
    private static function claimsFromString($token) {
        $b64urlb64 = function ($str) {
            $padding = strlen($str) % 4;
            if ($padding > 0) {
                $str .= str_repeat("=", 4 -$padding);
            }
            return strtr($str, '-_', '+/');
        };
        $parts = explode(".", $token);
        $claims = json_decode(base64_decode($b64urlb64($parts[1])));
        return $claims;
    }

    /**
     * @param $tokens
     * @return bool
     */
    public static function isExpired($tokens, $issueTime = null) {
        $claims = DexApi::claimsFromString($tokens["id_token"]);
        $expire_in = intval($tokens["expires_in"]);
        $date = new DateTime();
        if($issueTime == null){
            $issueTime = SessionService::fetch(DexApi::SESSION_TOKEN_TIME_KEY);
        }
        $date->setTimeStamp($issueTime + $expire_in);
        return $date < new DateTime("now");
    }

    /**
     * @param $refresh bool
     * @return string
     * @throws AuthRequiredException
     */
    public static function getValidToken($refresh = true) {

        if (!ApplicationState::sapiUsesSession() && isSet(DexApi::$restToken)) {

            return DexApi::$restToken["id_token"];

        } else if (SessionService::has(DexApi::SESSION_TOKEN_KEY)) {

            $tokens = SessionService::fetch(DexApi::SESSION_TOKEN_KEY);
            if (self::isExpired($tokens)) {
                $dex = new DexApi();
                $tokens = $dex->refreshToken($tokens["refresh_token"]);
            }
            return $tokens["id_token"];

        } else {

            return "";

        }

    }

    /**
     * @param $referenceTime
     * @return null
     */
    public static function clientExpirationTime($referenceTime) {

        $issueTime = SessionService::fetch(DexApi::SESSION_TOKEN_TIME_KEY);
        $tokens = SessionService::fetch(DexApi::SESSION_TOKEN_KEY);
        $expireIn = intval($tokens["expires_in"]);
        return $issueTime - time() + $referenceTime + $expireIn;

    }

    /**
     * @param $userId
     * @param $userPass
     * @return mixed|null
     * @throws LoginException
     */
    public function getToken($userId, $userPass) {

        $frontendRequestTime = time();
        $client = new Client();
        $options = [
            "body" => [
                "grant_type" => "password",
                "scope"      => "email profile pydio",
                "username"   => $userId,
                "password"   => $userPass,
                "nonce"      => uniqid(),
            ],
            "headers" => [
                "Authorization" => "Basic ". base64_encode("$this->clientID:$this->clientSecret"), // ClientID + Client Secret as defined is config-dev.yaml
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
            ],
        ];
        $skip = ConfService::bootstrapCoreConf("SKIP_SSL_VERIFY");
        if($skip){
            $options['verify'] = false;
        }
        $response = $client->post($this->dexUrl . '/dex/token', $options);
        $full = $response->getBody()->getContents();
        $json = json_decode($full, true);
        if ($json == null) {
            throw new LoginException(-1);
        }
        if (ApplicationState::sapiUsesSession()) {
            SessionService::save(DexApi::SESSION_TOKEN_KEY, $json);
            SessionService::save(DexApi::SESSION_TOKEN_TIME_KEY, $frontendRequestTime);
        } else {
            DexApi::$restToken = $json;
            DexApi::$restTokenTime = $frontendRequestTime;
        }

        return $json;
    }

    /**
     * @param array
     * @return array
     * @throws AuthRequiredException
     */
    public function refreshToken($refreshToken, $session = true) {
        try{
            $frontendTime = time();
            $client = new Client();
            $options = [
                "body" => [
                    "grant_type" => "refresh_token",
                    "scope"      => "email profile pydio",
                    "nonce"      => uniqid(),
                    "refresh_token" => $refreshToken
                ],
                "headers" => [
                    "Authorization" => "Basic ". base64_encode("$this->clientID:$this->clientSecret"), // ClientID + Client Secret as defined is config-dev.yaml
                    "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                    "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
                ],
            ];
            $skip = ConfService::bootstrapCoreConf("SKIP_SSL_VERIFY");
            if($skip){
                $options['verify'] = false;
            }
            $response = $client->post($this->dexUrl . '/dex/token', $options);
            $full = $response->getBody()->getContents();
            $json = json_decode($full, true);
            if($session){
                SessionService::save(DexApi::SESSION_TOKEN_KEY, $json);
                SessionService::save(DexApi::SESSION_TOKEN_TIME_KEY, $frontendTime);
            } else {
                DexApi::$restToken = $json;
                DexApi::$restTokenTime = $frontendTime;
            }
            return $json;
        }catch (Exception $e){
            session_destroy();
            throw new AuthRequiredException();
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function revokeSessionToken() {

        if(SessionService::has(DexApi::SESSION_TOKEN_KEY)) {
            $token = SessionService::fetch(DexApi::SESSION_TOKEN_KEY)["id_token"];
            $api = new TokenServiceApi(self::getApiClient());
            $request = new RestRevokeRequest();
            $request->setTokenId($token);
            $response = $api->revoke($request);
            return $response;
        }


    }

}
