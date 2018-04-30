<?php
/**
 * JobsTaskStatus
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swaagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * Pydio Cells Rest API
 *
 * No description provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: 1.0
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Model;

/**
 * JobsTaskStatus Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class JobsTaskStatus
{
    /**
     * Possible values of this enum
     */
    const UNKNOWN = 'Unknown';
    const IDLE = 'Idle';
    const RUNNING = 'Running';
    const FINISHED = 'Finished';
    const INTERRUPTED = 'Interrupted';
    const PAUSED = 'Paused';
    const ANY = 'Any';
    const ERROR = 'Error';
    const QUEUED = 'Queued';
    
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::UNKNOWN,
            self::IDLE,
            self::RUNNING,
            self::FINISHED,
            self::INTERRUPTED,
            self::PAUSED,
            self::ANY,
            self::ERROR,
            self::QUEUED,
        ];
    }
}


