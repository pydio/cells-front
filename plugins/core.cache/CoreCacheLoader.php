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
namespace Pydio\Cache\Core;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pydio\Access\Core\MetaStreamWrapper;
use Pydio\Access\Core\Model\Repository;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Model\Context;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\PluginFramework\CoreInstanceProvider;
use Pydio\Core\Services\CacheService;
use Pydio\Core\Services\ConfService;
use Pydio\Core\PluginFramework\Plugin;
use Pydio\Core\PluginFramework\PluginsService;
use Pydio\Access\Core\Model\Node;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Utils\Vars\InputFilter;

use Pydio\Log\Core\Logger;
use Zend\Diactoros\Response\JsonResponse;

defined('PYDIO_EXEC') or die( 'Access not allowed');
define('APC_DETECTED', extension_loaded('apc') || extension_loaded('apcu'));
/**
 * @package AjaXplorer_Plugins
 * @subpackage Core
 * @static
 * Provides access to the cache via the Doctrine interface
 */
class CoreCacheLoader extends Plugin implements CoreInstanceProvider
{
    /**
     * @var AbstractCacheDriver
     */
    protected static $cacheInstance;

    /**
     * @param PluginsService|null $pluginsService
     * @return null|AbstractCacheDriver|Plugin
     */
    public function getImplementation($pluginsService = null)
    {

        $pluginInstance = null;
        if(APC_DETECTED){
            $this->pluginConf = [
                "UNIQUE_INSTANCE_CONFIG"=>[
                    "instance_name" => "cache.doctrine",
                    "group_switch_value" => "cache.doctrine",
                    "CACHE_PREFIX" => "pydio-cdbd35fe",
                    "DRIVER" => [
                        "group_switch_value" =>"apc",
                        "driver" => "apc",
                        ]
                     ]
            ];
        }

        if (!isSet(self::$cacheInstance) || (isset($this->pluginConf["UNIQUE_INSTANCE_CONFIG"]["instance_name"]) && self::$cacheInstance->getId() != $this->pluginConf["UNIQUE_INSTANCE_CONFIG"]["instance_name"])) {
            if (isset($this->pluginConf["UNIQUE_INSTANCE_CONFIG"])) {
                if($pluginsService === null){
                    $pluginsService = PluginsService::getInstance(Context::emptyContext());
                }
                $pluginInstance = ConfService::instanciatePluginFromGlobalParams($this->pluginConf["UNIQUE_INSTANCE_CONFIG"], "Pydio\\Cache\\Core\\AbstractCacheDriver", $pluginsService);
            }
            self::$cacheInstance = $pluginInstance;
        }

        return self::$cacheInstance;
    }
    
}
