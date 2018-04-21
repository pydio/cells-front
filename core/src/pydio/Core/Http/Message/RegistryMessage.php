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
namespace Pydio\Core\Http\Message;

use Pydio\Core\Http\Response\JSONSerializableResponseChunk;
use Pydio\Core\Http\Response\XMLDocSerializableResponseChunk;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Utils\Vars\XMLFilter;
use Pydio\Core\Utils\XMLHelper;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class RegistryMessage
 * Send a piece or a full registry as XML or JSON
 * @package Pydio\Core\Http\Message
 */
class RegistryMessage implements XMLDocSerializableResponseChunk, JSONSerializableResponseChunk
{
    /**
     * @var \DOMDocument
     */
    protected $registry;

    /**
     * @var string|null
     */
    protected $xPath;

    /**
     * @var \DOMXPath|null
     */
    protected $xPathObject;

    /**
     * @var String
     */
    protected $renderedXML;

    /**
     * RegistryMessage constructor.
     * @param $registry
     * @param null $xPath
     * @param null $xPathObject
     */
    public function __construct($registry, $xPath = null, $xPathObject = null)
    {
        $this->registry = $registry;
        $this->xPath = $xPath;
        $this->xPathObject = $xPathObject;
    }


    /**
     * @return string
     */
    public function getCharset()
    {
        return "UTF-8";
    }

    /**
     * @return string
     */
    public function toXML()
    {
        if(!empty($this->renderedXML)){
            return $this->renderedXML;
        }
        if (empty($this->xPathObject)) {
            $this->xPathObject = new \DOMXPath($this->registry);
        }
        if(!PYDIO_SERVER_DEBUG){
            $serverCallbacks = $this->xPathObject->query("//serverCallback|//hooks|//server_settings|//class_definition|//class_stream_wrapper|//dependencies");
            foreach ($serverCallbacks as $callback) {
                if($callback->nodeName === "server_settings") {
                    // Do not remove it there are parameters with expose = true
                    $exposed = $this->xPathObject->query("param[@expose='true']|global_param[@expose='true']", $callback);
                    if($exposed->length > 0) {
                        // remove Not Exposed only, leaving server_settings branch with exposed ones
                        $notExposed = $this->xPathObject->query("*[not(@expose) or @expose!='true']", $callback);
                        foreach($notExposed as $notX){
                            $callback->removeChild($notX);
                        }
                    } else {
                        // Remove whole branch
                        $callback->parentNode->removeChild($callback);
                    }
                } else {
                    $callback->parentNode->removeChild($callback);
                }
            }
        }
        if (!empty($this->xPath)) {

            // Warning dirty hack for legacy iOS application : leave the space
            // after the xPath value (before closing >).
            $xml = "<pydio_registry_part xPath=\"".$this->xPath."\" >";
            $nodes = $this->xPathObject->query($this->xPath);
            if ($nodes->length) {
                $xml .= XMLFilter::resolveKeywords($this->registry->saveXML($nodes->item(0)));
            }
            $xml .= "</pydio_registry_part>";

        } else {

            ApplicationState::safeIniSet("zlib.output_compression", "4096");
            $xml = XMLFilter::resolveKeywords($this->registry->saveXML());

        }
        $this->renderedXML = $xml;
        return $xml;
    }

    /**
     * @return mixed
     */
    public function jsonSerializableData()
    {
        if(!empty($this->xPath)){
            if(empty($this->xPathObject)){
                $this->xPathObject = new \DOMXPath($this->registry);
            }
            $nodes = $this->xPathObject->query($this->xPath);
            $data = [];
            if($nodes->length){
                $data = XMLHelper::xmlToArray($nodes->item(0));
            }
            return $data;
        }else{
            return XMLHelper::xmlToArray($this->registry);
        }
    }

    /**
     * @return string
     */
    public function jsonSerializableKey()
    {
        return null;
    }
}