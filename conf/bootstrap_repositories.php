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
 *
 * Description : configuration file
 * BASIC REPOSITORY CONFIGURATION.
 * The standard repository will point to the data path (pydio/data by default), folder "files"
 * Use the GUI to add new repositories.
 *   + Log in as "admin" and open the "Settings" Repository
 */
defined('PYDIO_EXEC') or die( 'Access not allowed');

$REPOSITORIES["pydiogateway"] = array(
    "DISPLAY"		=>	"Gateway Root",
    "PYDIO_SLUG"		=>  "gateway-root",
    "DRIVER"		=>	"s3",

    "DRIVER_OPTIONS"=> array(
        "API_KEY"		        =>	"gateway",
        "SECRET_KEY"	        =>	"gatewaysecret",
        "REGION"                => "use-east-1",
        "CONTAINER"             => "io",
        "SIGNATURE_VERSION"     => "v4",
        "API_VERSION"           => "latest",
        "STORAGE_URL"           => "core.conf/ENDPOINT_S3_GATEWAY",
        "S3_FOLDER_EMPTY_FILE"  => ".pydio",
        "DEFAULT_RIGHTS"        => "read,write",
    ),
);


// DO NOT REMOVE THIS!
$REPOSITORIES["homepage"] = [
    "DISPLAY"		    =>	"Welcome",
    "PYDIO_SLUG"		=>  "welcome",
    "DISPLAY_ID"		=>	"user_home.title",
    "DESCRIPTION_ID"	=>	"user_home.desc",
    "DRIVER"		    =>	"homepage",
    "DRIVER_OPTIONS"    => [
        "DEFAULT_RIGHTS" => "read,write",
        "META_SOURCES" => [
            "index.pydio" => [],
        ]
    ]
];

// ADMIN REPOSITORY
$REPOSITORIES["settings"] = array(
    "DISPLAY"		    =>	"Settings",
    "PYDIO_SLUG"		=>  "settings",
    "DISPLAY_ID"		=>	"165",
    "DESCRIPTION_ID"	=>	"506",
    "DRIVER"		    =>	"settings",
    "DRIVER_OPTIONS"    => array()
);

if(!is_file(PYDIO_PLUGINS_REPOSITORIES_CACHE)){
    $content = "<?php \n";
    $boots = glob(PYDIO_INSTALL_PATH."/".PYDIO_PLUGINS_FOLDER."/*/repositories.php");
    if($boots !== false){
        foreach($boots as $b){
            $content .= 'require_once("'.$b.'");'."\n";
        }
    }
    $resWriteRepoCache = @file_put_contents(PYDIO_PLUGINS_REPOSITORIES_CACHE, $content);
}
if(!isSet($resWriteRepoCache) || $resWriteRepoCache === true){
    include_once(PYDIO_PLUGINS_REPOSITORIES_CACHE);
}
