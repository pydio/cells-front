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
namespace Pydio\Notification\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\PluginFramework\Plugin;
use Pydio\Log\Core\Logger;
use Swagger\Client\ApiException;
use Swagger\Client\Model\ActivityOwnerType;
use Swagger\Client\Model\ActivitySearchSubscriptionsRequest;
use Swagger\Client\Model\ActivityStreamActivitiesRequest;
use Swagger\Client\Model\ActivityStreamContext;
use Swagger\Client\Model\ActivitySubscription;
use Swagger\Client\Model\ActivitySummaryPointOfView;
use Zend\Diactoros\Response\JsonResponse;

defined('PYDIO_EXEC') or die('Access not allowed');

class ActivityCenter extends Plugin
{
    const SUBSCRIBE_CHANGE = "change";
    const SUBSCRIBE_READ = "read";

    public static $META_WATCH_CHANGE = "META_WATCH_CHANGE";
    public static $META_WATCH_READ = "META_WATCH_READ";
    public static $META_WATCH_BOTH = "META_WATCH_BOTH";

    /**
     * @param $userId string
     * @param $nodeId string
     * @param $events array
     * @return bool
     */
    public static function UserSubscribeToNode($userId, $nodeId, $events){
        $acApi = MicroApi::GetActivityServiceApi();
        $sub = new ActivitySubscription();
        $sub->setObjectType(ActivityOwnerType::NODE)
            ->setObjectId($nodeId)
            ->setEvents($events)
            ->setUserId($userId);
        try{
            $acApi->subscribe($sub);
        }catch (ApiException $e){
            Logger::log2(LOG_LEVEL_ERROR, "core.activitystreams", $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param $userId string
     * @param $nodeId string
     * @return $events array
     */
    public static function UserIsSubscribedToNode($userId, $nodeId){

        $acApi = MicroApi::GetActivityServiceApi();
        $searchRequest = new ActivitySearchSubscriptionsRequest();
        $searchRequest->setObjectTypes([ActivityOwnerType::NODE]);
        $searchRequest->setObjectIds([$nodeId]);
        $searchRequest->setUserIds([$userId]);
        try{
            $response = $acApi->searchSubscriptions($searchRequest);
        }catch (ApiException $e){
            Logger::log2(LOG_LEVEL_ERROR, "core.activitystreams", $e->getMessage());
            return [];
        }
        if($response->getSubscriptions() != null && count($response->getSubscriptions()) > 0){
            $sub = $response->getSubscriptions()[0];
            if($sub->getEvents() != null) {
                return $sub->getEvents();
            }
        }
        return [];
    }

    /**
     * @param Node $node
     */
    public function enrichNode(&$node)
    {
        if(!$node->getContext()->hasUser()) {
            return;
        }
        $uuid = $node->getUuid();
        if(empty($uuid)){
            return;
        }
        $events = [];
        $userId = $node->getContext()->getUserId();
        if($node->metadataLoaded) {
            $subMeta = $node->user_subscriptions;
            if(!empty($subMeta)) {
                $events = explode(",", $subMeta);
            }
        } else {
            // Warning, will trigger a query to the REST API
            $events = self::UserIsSubscribedToNode($userId, $uuid);
        }

        if (count($events)){
            $read = in_array(ActivityCenter::SUBSCRIBE_READ, $events);
            $change = in_array(ActivityCenter::SUBSCRIBE_CHANGE, $events);
            if ($read && $change) {
                $value = self::$META_WATCH_BOTH;
            } else if ($change) {
                $value = self::$META_WATCH_CHANGE;
            } else if ($read) {
                $value = self::$META_WATCH_READ;
            }
            $node->mergeMetadata(array(
                "meta_watched" => $value,
                "overlay_class" => "icon-eye-open"
            ), true);
        }
    }


}