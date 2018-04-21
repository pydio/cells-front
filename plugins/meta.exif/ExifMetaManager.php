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

namespace Pydio\Access\Meta\Exif;

use Pydio\Access\Core\AbstractAccessDriver;

use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Services\ApplicationState;



use Pydio\Core\Utils\TextEncoder;
use Pydio\Access\Meta\Core\AbstractMetaSource;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Extract Exif data from JPEG IMAGES
 * @package AjaXplorer_Plugins
 * @subpackage Meta
 */
class ExifMetaManager extends AbstractMetaSource
{
    protected $metaDefinitions;

    /**
     * @param ContextInterface $contextInterface
     * @param array $options
     */
    public function init(ContextInterface $contextInterface, $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param ContextInterface $ctx
     * @param AbstractAccessDriver $accessDriver
     */
    public function initMeta(ContextInterface $ctx, AbstractAccessDriver $accessDriver)
    {
        parent::initMeta($ctx, $accessDriver);
        $def = $this->getMetaDefinition();
        if (!count($def)) {
            return ;
        }
        $this->exposeConfigInManifest("meta_definitions", $def);

    }

    /**
     * @return array
     */
    protected function getMetaDefinition()
    {
        if (isSet($this->metaDefinitions)) {
            return $this->metaDefinitions;
        }
        $fields = $this->pluginConf["meta_fields"];
        $arrF = explode(",", $fields);
        $labels = $this->pluginConf["meta_labels"];
        $arrL = explode(",", $labels);
        $result = array();
        foreach ($arrF as $index => $value) {
            $value = str_replace(".", "-", $value);
            if (isSet($arrL[$index])) {
                $result[$value] = $arrL[$index];
            } else {
                $result[$value] = $value;
            }
        }
        $this->metaDefinitions = $result;
        return $result;
    }

    protected function serverMetaToSections($exif, Node $node){
        $sections = [];
        $gpsData = [];
        if(!empty($exif)) {
            $sections["EXIF"] = [];
            foreach ($exif as $item=>$value) {
                if(strpos($item, "UndefinedTag:") === 0) continue;
                if (is_array($value)) {
                    $filteredValue = implode(",", $value);
                } else {
                    $filteredValue = $value;
                }
                $filteredValue = preg_replace( '/[^[:print:]]/', '',$filteredValue);

                if(strpos($item, "GPS") === 0) {
                    if(!isSet($sections["GPS"])){
                        $sections["GPS"] = [];
                    }
                    $sections["GPS"][$item] = $filteredValue;
                    $gpsData[$item] = $value;
                }
                $sections["EXIF"][$item] = $filteredValue;
            }
        }
        if (count($gpsData)) {
            $computedData = $this->convertGPSData($gpsData);
            if(count($computedData)){
                $sections["COMPUTED_GPS"] = $computedData;
            }
        }
        if($this->pluginConf["extract_iptc"]) {
            $realFile = $node->getRealFile();
            $iptc = $this->extractIPTC($realFile);
            if(count($iptc)) {
                $sections["IPTC"] = $iptc;
            }
        }

        return $sections;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $requestInterface
     * @param \Psr\Http\Message\ResponseInterface $responseInterface
     */
    public function extractExif(\Psr\Http\Message\ServerRequestInterface $requestInterface, \Psr\Http\Message\ResponseInterface &$responseInterface)
    {
        $httpVars       = $requestInterface->getParsedBody();
        $ctx            = $requestInterface->getAttribute("ctx");
        $userSelection  = UserSelection::fromContext($ctx, $httpVars);
        $selectedNode   = $userSelection->getUniqueNode();

        $selectedNode->loadNodeInfo();
        $exif = $selectedNode->ImageExif;
        $sections = [];
        $gpsData = [];
        if(!empty($exif)) {
            $sections = $this->serverMetaToSections($exif, $selectedNode);
        }

        require_once "ExifXmlMessage.php";
        $x = new \Pydio\Core\Http\Response\SerializableResponseStream();
        $x->addChunk(new ExifXmlMessage($sections));
        $responseInterface = $responseInterface->withBody($x);

        return;


    }

    /**
     * @param \Pydio\Access\Core\Model\Node $node
     */
    public function extractMeta(&$node)
    {
        $currentFile = $node->getUrl();
        if(!$node->isLeaf() || preg_match("/\.zip\//",$currentFile)) return ;
          if(!preg_match("/\.jpg$|\.jpeg$|\.tif$|\.tiff$/i",$currentFile)) return ;
        $definitions = $this->getMetaDefinition();
        if(!count($definitions)) return ;

        $exif = $node->ImageExif;
        if (!empty($exif)) {
            $sections = $this->serverMetaToSections($exif, $node);
            $additionalMeta = array();
            foreach ($definitions as $def => $label) {
                list($exifSection, $exifName) = explode("-", $def);
                if(isSet($sections[$exifSection]) && isSet($sections[$exifSection][$exifName])){
                    $additionalMeta[$def] = $sections[$exifSection][$exifName];
                } else if (isSet($sections["EXIF"]) && isSet($sections["EXIF"][$exifName])){
                    $additionalMeta[$def] = $sections["EXIF"][$exifName];
                }
            }
            $node->mergeMetadata($additionalMeta);
        }

    }

    /**
     * @param $realFile
     * @return array
     */
    private function extractIPTC($realFile){
        $output = array();
        if(!function_exists("iptcparse")) {
            return $output;
        }
        getimagesize($realFile,$info);
        if(!isset($info['APP13'])) {
            return $output;
        }
        $iptcHeaderArray = array
        (
            '2#005'=>'DocumentTitle',
            '2#010'=>'Urgency',
            '2#015'=>'Category',
            '2#020'=>'Subcategories',
            '2#025'=>'Keywords',
            '2#040'=>'SpecialInstructions',
            '2#055'=>'CreationDate',
            '2#080'=>'AuthorByline',
            '2#085'=>'AuthorTitle',
            '2#090'=>'City',
            '2#095'=>'State',
            '2#101'=>'Country',
            '2#103'=>'OTR',
            '2#105'=>'Headline',
            '2#110'=>'Source',
            '2#115'=>'PhotoSource',
            '2#116'=>'Copyright',
            '2#120'=>'Caption',
            '2#122'=>'CaptionWriter'
        );
        $iptc =iptcparse($info['APP13']);
        if (!is_array($iptc)) {
            return $output;
        }
        foreach (array_keys($iptc) as $key) {
            if (isSet($iptcHeaderArray[$key])) {
                $cnt = count($iptc[$key]);
                $val = "";
                for ($i=0; $i < $cnt; $i++) $val .= $iptc[$key][$i] . " ";
                $output[$iptcHeaderArray[$key]] = preg_replace( '/[^[:print:]]/', '',$val);
            }
        }
        return $output;
    }

    /**
     * @param $exif
     * @return array
     */
    private function convertGPSData($exif)
    {
        if(!isSet($exif["GPSLatitude"]) || !isSet($exif["GPSLongitude"])){
            return [];
        }
        require_once(PYDIO_INSTALL_PATH . "/plugins/meta.exif/GeoConversion.php");
        $converter = new GeoConversion();
        $latDeg=@$this->parseGPSValue($exif["GPSLatitude"][0]);
        $latMin=@$this->parseGPSValue($exif["GPSLatitude"][1]);
        $latSec=@$this->parseGPSValue($exif["GPSLatitude"][2]);
        $latHem=$exif["GPSLatitudeRef"];
        $longDeg=@$this->parseGPSValue($exif["GPSLongitude"][0]);
        $longMin=@$this->parseGPSValue($exif["GPSLongitude"][1]);
        $longSec=@$this->parseGPSValue($exif["GPSLongitude"][2]);
        $longRef=$exif["GPSLongitudeRef"];
        $latSign = ($latHem == "S" ? "-":"");
        $longSign = ($longRef == "W" ? "-":"");
        $gpsData = array(
            "GPS_Latitude"=>"$latDeg deg $latMin' $latSec $latHem--".$converter->DMS2Dd($latSign.$latDeg."o$latMin'$latSec"),
            "GPS_Longitude"=>"$longDeg deg $longMin' $longSec $longRef--".$converter->DMS2Dd($longSign.$longDeg."o$longMin'$longSec"),
        );
        if(isSet($exif["GPSAltitude"])){
            $gpsData["GPS_Altitude"] = $exif["GPSAltitude"][0];
        }
        return $gpsData;
    }

    /**
     * @param $value
     * @return float
     */
    private function parseGPSValue($value)
    {
        if (strstr($value, "/") === false) {
            return floatval($value);
        } else {
            $exp = explode("/", $value);
            return round(intval($exp[0])/intval($exp[1]), 4);
        }
    }

}
