<?php
/**
 * TreeSyncChangeNode
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

use \ArrayAccess;

/**
 * TreeSyncChangeNode Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class TreeSyncChangeNode implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'treeSyncChangeNode';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'bytesize' => 'string',
        'md5' => 'string',
        'mtime' => 'string',
        'node_path' => 'string',
        'repository_identifier' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerFormats = [
        'bytesize' => 'int64',
        'md5' => null,
        'mtime' => 'int64',
        'node_path' => null,
        'repository_identifier' => null
    ];

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    public static function swaggerFormats()
    {
        return self::$swaggerFormats;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = [
        'bytesize' => 'bytesize',
        'md5' => 'md5',
        'mtime' => 'mtime',
        'node_path' => 'nodePath',
        'repository_identifier' => 'repositoryIdentifier'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'bytesize' => 'setBytesize',
        'md5' => 'setMd5',
        'mtime' => 'setMtime',
        'node_path' => 'setNodePath',
        'repository_identifier' => 'setRepositoryIdentifier'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'bytesize' => 'getBytesize',
        'md5' => 'getMd5',
        'mtime' => 'getMtime',
        'node_path' => 'getNodePath',
        'repository_identifier' => 'getRepositoryIdentifier'
    ];

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    public static function setters()
    {
        return self::$setters;
    }

    public static function getters()
    {
        return self::$getters;
    }

    

    

    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['bytesize'] = isset($data['bytesize']) ? $data['bytesize'] : null;
        $this->container['md5'] = isset($data['md5']) ? $data['md5'] : null;
        $this->container['mtime'] = isset($data['mtime']) ? $data['mtime'] : null;
        $this->container['node_path'] = isset($data['node_path']) ? $data['node_path'] : null;
        $this->container['repository_identifier'] = isset($data['repository_identifier']) ? $data['repository_identifier'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];

        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {

        return true;
    }


    /**
     * Gets bytesize
     * @return string
     */
    public function getBytesize()
    {
        return $this->container['bytesize'];
    }

    /**
     * Sets bytesize
     * @param string $bytesize
     * @return $this
     */
    public function setBytesize($bytesize)
    {
        $this->container['bytesize'] = $bytesize;

        return $this;
    }

    /**
     * Gets md5
     * @return string
     */
    public function getMd5()
    {
        return $this->container['md5'];
    }

    /**
     * Sets md5
     * @param string $md5
     * @return $this
     */
    public function setMd5($md5)
    {
        $this->container['md5'] = $md5;

        return $this;
    }

    /**
     * Gets mtime
     * @return string
     */
    public function getMtime()
    {
        return $this->container['mtime'];
    }

    /**
     * Sets mtime
     * @param string $mtime
     * @return $this
     */
    public function setMtime($mtime)
    {
        $this->container['mtime'] = $mtime;

        return $this;
    }

    /**
     * Gets node_path
     * @return string
     */
    public function getNodePath()
    {
        return $this->container['node_path'];
    }

    /**
     * Sets node_path
     * @param string $node_path
     * @return $this
     */
    public function setNodePath($node_path)
    {
        $this->container['node_path'] = $node_path;

        return $this;
    }

    /**
     * Gets repository_identifier
     * @return string
     */
    public function getRepositoryIdentifier()
    {
        return $this->container['repository_identifier'];
    }

    /**
     * Sets repository_identifier
     * @param string $repository_identifier
     * @return $this
     */
    public function setRepositoryIdentifier($repository_identifier)
    {
        $this->container['repository_identifier'] = $repository_identifier;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
    }
}


