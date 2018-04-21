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
namespace Pydio\Core\Serializer;

use Pydio\Access\Core\IPydioWrapperProvider;
use Pydio\Access\Core\Model\Node;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Model\UserInterface;
use Pydio\Core\PluginFramework\PluginsService;
use Pydio\Core\Services\ConfService;
use Pydio\Core\Services\RepositoryService;
use Pydio\Core\Services\UsersService;
use Pydio\Core\Utils\Vars\StringHelper;
use Swagger\Client\ApiException;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class UserXML
 * @package Pydio\Core\Serializer
 */
class UserXML
{
    /**
     * List all bookmmarks as XML
     * @static
     * @param $allBookmarks
     * @param ContextInterface $context
     * @param bool $print
     * @param string $format legacy|node_list
     * @return string
     */
    public static function writeBookmarks($allBookmarks, $context, $print = true, $format = "legacy")
    {
        $driver = false;
        $repository = $context->getRepository();
        if ($format == "node_list") {
            $driver = $repository->getDriverInstance($context);
            if (!($driver instanceof IPydioWrapperProvider)) {
                $driver = false;
            }
        }
        $buffer = "";
        foreach ($allBookmarks as $bookmark) {
            $path = "";
            $title = "";
            if (is_array($bookmark)) {
                $path = $bookmark["PATH"];
                $title = $bookmark["TITLE"];
            } else if (is_string($bookmark)) {
                $path = $bookmark;
                $title = basename($bookmark);
            }
            if ($format == "node_list") {
                if ($driver) {
                    $node = new Node($context->getUrlBase() . $path);
                    $buffer .= NodeXML::toXML($node, true);
                } else {
                    $buffer .= NodeXML::toNode($path, $title, false, array('icon' => "mime_empty.png"), true);
                }
            } else {
                $buffer .= "<bookmark path=\"" . StringHelper::xmlEntities($path, true) . "\" title=\"" . StringHelper::xmlEntities($title, true) . "\"/>";
            }
        }
        if ($print) {
            print $buffer;
            return null;
        } else {
            return $buffer;
        }
    }

    /**
     * Extract all the user data and put it in XML
     * @param ContextInterface $ctx
     * @return string
     */
    public function serialize(ContextInterface $ctx)
    {
        $buffer = "";
        $loggedUser = $ctx->getUser();
        $currentRepoId = $ctx->getRepositoryId();
        $confDriver = ConfService::getConfStorageImpl();

        if (!UsersService::usersEnabled()) {
            $buffer.="<user id=\"shared\">";
            $buffer.="<active_repo id=\"".$currentRepoId."\" write=\"1\" read=\"1\"/>";
            $buffer.= $this->writeRepositoriesData($ctx);
            $buffer.="</user>";
        } else if ($loggedUser !== null) {
            $lock = $loggedUser->getLock();
            $buffer.="<user id=\"".StringHelper::xmlEntities($loggedUser->getId())."\">";
            $buffer.="<active_repo id=\"".$currentRepoId."\"/>";
            $buffer.= $this->writeRepositoriesData($ctx);
            $buffer.="<preferences>";
            $preferences = $this->getExposedPreferences($loggedUser);
            foreach ($preferences as $prefName => $prefData) {
                $atts = "";
                if (isSet($prefData["exposed"]) && $prefData["exposed"] == true) {
                    foreach ($prefData as $k => $v) {
                        if($k=="name") continue;
                        if($k == "value") $k = "default";
                        $atts .= "$k='$v' ";
                    }
                }
                if (isset($prefData["pluginId"])) {
                    $atts .=  "pluginId='".$prefData["pluginId"]."' ";
                }
                if ($prefData["type"] == "string") {
                    $buffer.="<pref name=\"".StringHelper::xmlEntities($prefName)."\" value=\"". StringHelper::xmlEntities($prefData["value"])."\" $atts/>";
                } else if ($prefData["type"] == "json") {
                    $buffer.="<pref name=\"".StringHelper::xmlEntities($prefName)."\" $atts><![CDATA[".$prefData["value"]."]]></pref>";
                }
            }
            $buffer.="</preferences>";
            $buffer.="<special_rights is_admin=\"".($loggedUser->isAdmin()?"1":"0")."\"  ".($lock!==false?"lock=\"$lock\"":"")."/>";
            $buffer.="</user>";
        }
        return $buffer;
    }

    /**
     * @param UserInterface $userObject
     * @return array()
     */
    public function getExposedPreferences($userObject)
    {
        $stringPrefs = ["lang","history/last_repository","pending_folder","plugins_preferences"];
        $jsonPrefs = ["ls_history","gui_preferences"];
        $prefs = [];
        if ( $userObject->getId()=="guest" ) {
            return [];
        }
        if ( ConfService::getGlobalConf("SKIP_USER_HISTORY", "auth") === true ) {
            $stringPrefs = ["lang","pending_folder", "plugins_preferences"];
            $jsonPrefs = ["gui_preferences"];
            $prefs["SKIP_USER_HISTORY"] = ["value" => "true", "type" => "string"];
        }
        foreach ($stringPrefs as $pref) {
            if (strstr($pref, "/")!==false) {
                $parts = explode("/", $pref);
                $value = $userObject->getArrayPref($parts[0], $parts[1]);
                $pref = str_replace("/", "_", $pref);
            } else {
                $value = $userObject->getPref($pref);
            }
            $prefs[$pref] = ["value" => $value, "type" => "string"];
        }
        foreach ($jsonPrefs as $pref) {
            $prefs[$pref] = ["value" => $userObject->getPref($pref), "type" => "json"];
        }

        $exposed = UsersService::getUsersExposedParameters();
        foreach ($exposed as $exposedProp) {
            if ($exposedProp["SCOPE"] === "user") {
                $value = $userObject->getPersonalAttribute($exposedProp["NAME"]);
            } else {
                $value = $userObject->getMergedRole()->filterParameterValue($exposedProp["PLUGIN_ID"], $exposedProp["NAME"], PYDIO_REPO_SCOPE_ALL, "");
            }
            $prefs[$exposedProp["NAME"]] = ["value" => $value, "type" => "string", "pluginId" => $exposedProp["PLUGIN_ID"]];
        }

        return $prefs;
    }


    /**
     * Write the repositories access rights in XML format
     * @static
     * @param ContextInterface $ctx
     * @return string
     */
    protected function writeRepositoriesData(ContextInterface $ctx)
    {
        $loggedUser = $ctx->getUser();
        $lock = $loggedUser->getLock();
        if(!empty($lock)){
            $accessible = [];
        } else {
            try{
                $accessList = RepositoryService::contextUserRepositories($ctx->getUser(), true);
                $accessible = $accessList->getWorkspaces();
            } catch (ApiException $aE) {
                $accessible = [];
            }
        }
        $streams = PluginsService::detectRepositoriesStreams($accessible);
        $exposed = PluginsService::searchManifestsWithCache("//server_settings/param[contains(@scope,'repository') and @expose='true']", function($nodes){
            $exposedNodes = [];
            /** @var \DOMElement $exposed_prop */
            foreach($nodes as $exposed_prop){
                $pluginId = $exposed_prop->parentNode->parentNode->getAttribute("id");
                $paramName = $exposed_prop->getAttribute("name");
                $paramDefault = $exposed_prop->getAttribute("default");
                $exposedNodes[] = array("PLUGIN_ID" => $pluginId, "NAME" => $paramName, "DEFAULT" => $paramDefault);
            }
            return $exposedNodes;
        });
        $repoSerializer = new RepositoryXML($exposed, $streams, $loggedUser);

        $st = "<repositories>";
        $inboxStatus = 0;
        foreach($accessible as $repoId => $repoObject){
            $accessStatus = $repoObject->getAccessStatus();
            if(empty($accessStatus) && $loggedUser != null){
                $lastConnected = $loggedUser->getArrayPref("repository_last_connected", $repoId);
                if(empty($lastConnected)){
                    $accessStatus = 1;
                }
            }
            if(!empty($accessStatus)){
                $inboxStatus ++;
            }
        }

        foreach ($accessible as $repoId => $repoObject) {
            if($repoId == "pydiogateway"){
                continue;
            }
            $accessStatus = '';
            if($repoObject->getAccessType() == "inbox"){
                $accessStatus = $inboxStatus;
            }
            $xmlString = $repoSerializer->serialize($repoObject, $repoId === $ctx->getRepositoryId(), $accessStatus);
            $st .= $xmlString;
        }

        $st .= "</repositories>";
        return $st;
    }

}