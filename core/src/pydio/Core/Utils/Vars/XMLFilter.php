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
namespace Pydio\Core\Utils\Vars;

use Pydio\Core\Controller\Controller;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\LocaleService;


defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Filters XML data with pydio specific keywords
 * @package Pydio
 * @subpackage Core
 */
class XMLFilter
{
    
    /**
     * Dynamically replace XML keywords with their live values.
     * @static
     * @param string $xml
     * @param bool $stripSpaces
     * @return mixed
     */
    public static function resolveKeywords($xml, $stripSpaces = false)
    {
        $messages = LocaleService::getMessages();
        $confMessages = LocaleService::getConfigMessages();
        $matches = array();
        if(strpos($xml, "PYDIO_APPLICATION_TITLE") !== false) {
            $xml = str_replace("PYDIO_APPLICATION_TITLE", ConfService::getGlobalConf("APPLICATION_TITLE"), $xml);
        }
        if(strpos($xml, "PYDIO_MIMES_EDITABLE") !== false){
            $xml = str_replace("PYDIO_MIMES_EDITABLE", StatHelper::getPydioMimes("editable"), $xml);
        }
        if(strpos($xml, "PYDIO_MIMES_IMAGE") !== false) {
            $xml = str_replace("PYDIO_MIMES_IMAGE", StatHelper::getPydioMimes("image"), $xml);
        }
        if(strpos($xml, "PYDIO_MIMES_AUDIO") !== false) {
            $xml = str_replace("PYDIO_MIMES_AUDIO", StatHelper::getPydioMimes("audio"), $xml);
        }
        if(strpos($xml, "PYDIO_MIMES_ZIP") !== false) {
            $xml = str_replace("PYDIO_MIMES_ZIP", StatHelper::getPydioMimes("zip"), $xml);
        }
        if(strpos($xml, "PYDIO_LOGIN_REDIRECT") !== false){
            $authDriver = ConfService::getAuthDriverImpl();
            if ($authDriver != NULL) {
                $loginRedirect = $authDriver->getLoginRedirect();
                $xml = str_replace("PYDIO_LOGIN_REDIRECT", ($loginRedirect!==false?"'".$loginRedirect."'":"false"), $xml);
            }
        }
        $xml = str_replace("PYDIO_REMOTE_AUTH", "false", $xml);
        $xml = str_replace("PYDIO_NOT_REMOTE_AUTH", "true", $xml);
        if(strpos($xml, "PYDIO_ALL_MESSAGES") !== false) {
            $xml = str_replace("PYDIO_ALL_MESSAGES", "MessageHash=" . json_encode($message) . ";", $xml);
        }
        if (preg_match_all("/PYDIO_MESSAGE(\[.*?\])/", $xml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $messId = str_replace("]", "", str_replace("[", "", $match[1]));
                $xml = str_replace("PYDIO_MESSAGE[$messId]", $messages[$messId], $xml);
            }
        }
        if (preg_match_all("/CONF_MESSAGE(\[.*?\])/", $xml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $messId = str_replace(array("[", "]"), "", $match[1]);
                $message = $messId;
                if (array_key_exists($messId, $confMessages)) {
                    $message = $confMessages[$messId];
                }
                $xml = str_replace("CONF_MESSAGE[$messId]", StringHelper::xmlEntities($message), $xml);
            }
        }
        if (preg_match_all("/MIXIN_MESSAGE(\[.*?\])/", $xml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $messId = str_replace(array("[", "]"), "", $match[1]);
                $message = $messId;
                if (array_key_exists($messId, $confMessages)) {
                    $message = $confMessages[$messId];
                }
                $xml = str_replace("MIXIN_MESSAGE[$messId]", StringHelper::xmlEntities($message), $xml);
            }
        }
        if ($stripSpaces) {
            $xml = preg_replace("/[\n\r]?/", "", $xml);
            $xml = preg_replace("/\t/", " ", $xml);
        }
        $xml = str_replace(array('xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"','xsi:noNamespaceSchemaLocation="file:../core.pydio/pydio_registry.xsd"'), "", $xml);
        $tab = array(&$xml);
        Controller::applyIncludeHook("xml.filter", $tab);
        return $xml;
    }
    
}
