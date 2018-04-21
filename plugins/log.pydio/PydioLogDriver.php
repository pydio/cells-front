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

namespace Pydio\Log\Implementation;

use Pydio\Core\Http\Client\MicroApi;
use Pydio\Core\Model\ContextInterface;
use Pydio\Log\Core\AbstractLogDriver;
use Swagger\Client\Model\RestFrontLogMessage;
use Swagger\Client\Model\RestLogLevel;

defined('PYDIO_EXEC') or die('Access not allowed');

/**
 * Class PydioLogDriver
 * @package Pydio\Log\Implementation
 */
class PydioLogDriver extends AbstractLogDriver {

    /**
     * Write an entry to the log.
     *
     * @param String $level Log severity: one of LOG_LEVEL_* (DEBUG,INFO,NOTICE,WARNING,ERROR)
     * @param String $ip The client ip
     * @param String $user The user login
     * @param String $source The source of the message
     * @param String $prefix  The prefix of the message
     * @param String $message The message to log
     *
     */
    public function write2($level, $ip, $user, $repoId, $source, $prefix, $message, $nodePathes = array()) {
        if($level !== "Error"){
            return;
        }
        $api = MicroApi::GetFrontendServiceApi();
        $logMessage = new RestFrontLogMessage();
        $logMessage->setLevel($this->innerLevelToApiLevels($level));
        $logMessage->setIp($ip);
        $logMessage->setUserId($user);
        $logMessage->setWorkspaceId($repoId);
        $logMessage->setSource($source);
        $logMessage->setMessage($message);
        $logMessage->setNodes($nodePathes);
        $api->frontLog($logMessage);
    }

    /**
     * List available log files in XML
     *
     * @param string $nodeName
     * @param null $year
     * @param null $month
     * @param string $rootPath
     * @return \String[]
     */
    public function listLogFiles($nodeName = "file", $year = null, $month = null, $rootPath = "/logs") {
        return ["$rootPath/see" => [
            "date" => "", "icon" => "", "display" => "Logs are not readable via this GUI, they are sent directly to your system logger daemon.",
            "text" => "Logs are not readable via this GUI, they are sent directly to your system logger daemon.", "is_file" => 1, "filename" => "$rootPath/see"
        ]];
    }

    /**
     * List log contents in XML
     *
     * @param $parentDir
     * @param String $date Assumed to be m-d-y format.
     * @param string $nodeName
     * @param string $rootPath
     * @param int $cursor
     * @return
     */
    public function listLogs($parentDir, $date, $nodeName = "log", $rootPath = "/logs", $cursor = -1) {

    }


    protected function innerLevelToApiLevels($level){
        if ($level === LOG_LEVEL_DEBUG) {
            return RestLogLevel::DEBUG;
        } else if ($level === LOG_LEVEL_WARNING) {
            return RestLogLevel::WARNING;
        } else if ($level === LOG_LEVEL_INFO) {
            return RestLogLevel::INFO;
        } else if ($level === LOG_LEVEL_NOTICE) {
            return RestLogLevel::NOTICE;
        } else if ($level === LOG_LEVEL_ERROR) {
            return RestLogLevel::ERROR;
        }
    }

}
