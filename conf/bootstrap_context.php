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
 * This is the main configuration file for configuring the core of the application.
 * In a standard usage, you should not have to change any variables.
 */
@date_default_timezone_set(@date_default_timezone_get());
if (function_exists("xdebug_disable")) {
    xdebug_disable();
}
@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
//Windows users may have to uncomment this
//setlocale(LC_ALL, '');
@libxml_disable_entity_loader(false);

@include_once("VERSION.php");
if(!defined("PYDIO_VERSION")){
    list($vNmber,$vDate,$vRevision) = explode("__",file_get_contents(PYDIO_CONF_PATH."/VERSION"));
    define("PYDIO_VERSION", $vNmber);
    define("PYDIO_VERSION_DATE", $vDate);
    if(!empty($vRevision)) define("PYDIO_VERSION_REV", $vRevision);
}

define("PYDIO_EXEC", true);

// APPLICATION PATHES CONFIGURATION
define("PYDIO_DATA_PATH", PYDIO_INSTALL_PATH."/data");
define("PYDIO_CACHE_DIR", PYDIO_DATA_PATH."/cache");
define("PYDIO_SHARED_CACHE_DIR", PYDIO_INSTALL_PATH."/data/cache");
define("PYDIO_PLUGINS_CACHE_FILE", PYDIO_CACHE_DIR."/plugins_cache.ser");
define("PYDIO_PLUGINS_REQUIRES_FILE", PYDIO_CACHE_DIR."/plugins_requires.ser");
define("PYDIO_PLUGINS_QUERIES_CACHE", PYDIO_CACHE_DIR."/plugins_queries.ser");
define("PYDIO_PLUGINS_BOOTSTRAP_CACHE", PYDIO_CACHE_DIR."/plugins_bootstrap.php");
define("PYDIO_PLUGINS_REPOSITORIES_CACHE", PYDIO_CACHE_DIR."/plugins_repositories.php");
define("PYDIO_PLUGINS_MESSAGES_FILE", PYDIO_CACHE_DIR."/plugins_messages.ser");
define("PYDIO_SERVER_ACCESS", "index.php");
define("PYDIO_PLUGINS_FOLDER", "plugins");
define("PYDIO_BIN_FOLDER_REL", "core/src");
define("PYDIO_VENDOR_FOLDER_REL", "core/vendor");
define("PYDIO_BIN_FOLDER", PYDIO_INSTALL_PATH."/core/src");
define("PYDIO_VENDOR_FOLDER", PYDIO_INSTALL_PATH."/core/vendor");
define("PYDIO_DOCS_FOLDER", "core/doc");
define("PYDIO_COREI18N_FOLDER", PYDIO_INSTALL_PATH."/plugins/core.pydio/i18n");
define("PYDIO_TESTS_RESULT_FILE", PYDIO_DATA_PATH."/plugins/boot.conf/diag_result.php");
define("PYDIO_TESTS_RESULT_FILE_LEGACY", PYDIO_CACHE_DIR."/diag_result.php");
define("PYDIO_TESTS_FOLDER", PYDIO_BIN_FOLDER."/pydio/Tests");

// DEBUG OPTIONS
define("PYDIO_CLIENT_DEBUG"  ,	false);
define("PYDIO_SERVER_DEBUG"  ,	false);
define("PYDIO_SKIP_CACHE"    ,  false);

// PBKDF2 CONSTANTS FOR A SECURE STORAGE OF PASSWORDS
// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha256");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTE_SIZE", 24);
define("PBKDF2_HASH_BYTE_SIZE", 24);

define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);

// Used to identify the booster admin tasks
define("PYDIO_BOOSTER_TASK_IDENTIFIER", "pydio-booster");

// CAN BE SWITCHED TO TRUE TO MAKE THE SECURE TOKEN MORE SAFE
// MAKE SURE YOU HAVE PHP.5.3, OPENSSL, AND THAT IT DOES NOT DEGRADE PERFORMANCES
define("USE_OPENSSL_RANDOM", false);

require_once (PYDIO_VENDOR_FOLDER . "/autoload.php");
$corePlugAutoloads = glob(PYDIO_INSTALL_PATH."/".PYDIO_PLUGINS_FOLDER."/core.*/vendor/autoload.php", GLOB_NOSORT);
if ($corePlugAutoloads !== false && count($corePlugAutoloads)) {
    foreach($corePlugAutoloads as $autoloader){
        require_once ($autoloader);
    }
}

/**
 * Used as autoloader
 * @param $className
 */
function pydioAutoloader($className)
{
    // Temp : super dummy autoloader, take only class name
    $parts = explode("\\", $className);
    $className = array_pop($parts);

    $corePlugClass = glob(PYDIO_INSTALL_PATH."/".PYDIO_PLUGINS_FOLDER."/core.*/".$className.".php", GLOB_NOSORT);
    if ($corePlugClass !== false && count($corePlugClass)) {
        require_once($corePlugClass[0]);
        return;
    }
}
spl_autoload_register('pydioAutoloader');

use Pydio\Core\Services\ApplicationState;

ApplicationState::safeIniSet("session.cookie_httponly", 1);

if (is_file(PYDIO_CONF_PATH."/bootstrap_conf.php")) {
    include(PYDIO_CONF_PATH."/bootstrap_conf.php");
    if (isSet($PYDIO_INISET)) {
        foreach($PYDIO_INISET as $key => $value) ApplicationState::safeIniSet($key, $value);
    }
    if (defined('PYDIO_LOCALE')) {
        setlocale(LC_CTYPE, PYDIO_LOCALE);
    }else if(file_exists(PYDIO_DATA_PATH."/plugins/boot.conf/encoding.php")){
        require_once(PYDIO_DATA_PATH."/plugins/boot.conf/encoding.php");
        if(isSet($ROOT_ENCODING)){
            setlocale(LC_CTYPE, $ROOT_ENCODING);
        }
    }
}

if(!is_file(PYDIO_PLUGINS_BOOTSTRAP_CACHE)){
    $content = "<?php \n";
    $boots = glob(PYDIO_INSTALL_PATH."/".PYDIO_PLUGINS_FOLDER."/*/bootstrap.php");
    if($boots !== false){
        foreach($boots as $b){
            $content .= 'require_once("'.$b.'");'."\n";
        }
    }
    $resWriteBootstrapCache = @file_put_contents(PYDIO_PLUGINS_BOOTSTRAP_CACHE, $content);
}
if(!isSet($resWriteBootstrapCache) || $resWriteBootstrapCache !== false){
    require_once(PYDIO_PLUGINS_BOOTSTRAP_CACHE);
}
