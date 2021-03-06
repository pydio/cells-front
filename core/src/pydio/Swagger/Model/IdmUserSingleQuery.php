<?php
/**
 * IdmUserSingleQuery
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
 * IdmUserSingleQuery Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class IdmUserSingleQuery implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'idmUserSingleQuery';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'uuid' => 'string',
        'login' => 'string',
        'password' => 'string',
        'group_path' => 'string',
        'recursive' => 'bool',
        'full_path' => 'string',
        'attribute_name' => 'string',
        'attribute_value' => 'string',
        'attribute_any_value' => 'bool',
        'has_role' => 'string',
        'node_type' => '\Swagger\Client\Model\IdmNodeType',
        'not' => 'bool'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerFormats = [
        'uuid' => null,
        'login' => null,
        'password' => null,
        'group_path' => null,
        'recursive' => 'boolean',
        'full_path' => null,
        'attribute_name' => null,
        'attribute_value' => null,
        'attribute_any_value' => 'boolean',
        'has_role' => null,
        'node_type' => null,
        'not' => 'boolean'
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
        'uuid' => 'Uuid',
        'login' => 'Login',
        'password' => 'Password',
        'group_path' => 'GroupPath',
        'recursive' => 'Recursive',
        'full_path' => 'FullPath',
        'attribute_name' => 'AttributeName',
        'attribute_value' => 'AttributeValue',
        'attribute_any_value' => 'AttributeAnyValue',
        'has_role' => 'HasRole',
        'node_type' => 'NodeType',
        'not' => 'not'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'uuid' => 'setUuid',
        'login' => 'setLogin',
        'password' => 'setPassword',
        'group_path' => 'setGroupPath',
        'recursive' => 'setRecursive',
        'full_path' => 'setFullPath',
        'attribute_name' => 'setAttributeName',
        'attribute_value' => 'setAttributeValue',
        'attribute_any_value' => 'setAttributeAnyValue',
        'has_role' => 'setHasRole',
        'node_type' => 'setNodeType',
        'not' => 'setNot'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'uuid' => 'getUuid',
        'login' => 'getLogin',
        'password' => 'getPassword',
        'group_path' => 'getGroupPath',
        'recursive' => 'getRecursive',
        'full_path' => 'getFullPath',
        'attribute_name' => 'getAttributeName',
        'attribute_value' => 'getAttributeValue',
        'attribute_any_value' => 'getAttributeAnyValue',
        'has_role' => 'getHasRole',
        'node_type' => 'getNodeType',
        'not' => 'getNot'
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
        $this->container['uuid'] = isset($data['uuid']) ? $data['uuid'] : null;
        $this->container['login'] = isset($data['login']) ? $data['login'] : null;
        $this->container['password'] = isset($data['password']) ? $data['password'] : null;
        $this->container['group_path'] = isset($data['group_path']) ? $data['group_path'] : null;
        $this->container['recursive'] = isset($data['recursive']) ? $data['recursive'] : null;
        $this->container['full_path'] = isset($data['full_path']) ? $data['full_path'] : null;
        $this->container['attribute_name'] = isset($data['attribute_name']) ? $data['attribute_name'] : null;
        $this->container['attribute_value'] = isset($data['attribute_value']) ? $data['attribute_value'] : null;
        $this->container['attribute_any_value'] = isset($data['attribute_any_value']) ? $data['attribute_any_value'] : null;
        $this->container['has_role'] = isset($data['has_role']) ? $data['has_role'] : null;
        $this->container['node_type'] = isset($data['node_type']) ? $data['node_type'] : null;
        $this->container['not'] = isset($data['not']) ? $data['not'] : null;
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
     * Gets uuid
     * @return string
     */
    public function getUuid()
    {
        return $this->container['uuid'];
    }

    /**
     * Sets uuid
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->container['uuid'] = $uuid;

        return $this;
    }

    /**
     * Gets login
     * @return string
     */
    public function getLogin()
    {
        return $this->container['login'];
    }

    /**
     * Sets login
     * @param string $login
     * @return $this
     */
    public function setLogin($login)
    {
        $this->container['login'] = $login;

        return $this;
    }

    /**
     * Gets password
     * @return string
     */
    public function getPassword()
    {
        return $this->container['password'];
    }

    /**
     * Sets password
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->container['password'] = $password;

        return $this;
    }

    /**
     * Gets group_path
     * @return string
     */
    public function getGroupPath()
    {
        return $this->container['group_path'];
    }

    /**
     * Sets group_path
     * @param string $group_path
     * @return $this
     */
    public function setGroupPath($group_path)
    {
        $this->container['group_path'] = $group_path;

        return $this;
    }

    /**
     * Gets recursive
     * @return bool
     */
    public function getRecursive()
    {
        return $this->container['recursive'];
    }

    /**
     * Sets recursive
     * @param bool $recursive
     * @return $this
     */
    public function setRecursive($recursive)
    {
        $this->container['recursive'] = $recursive;

        return $this;
    }

    /**
     * Gets full_path
     * @return string
     */
    public function getFullPath()
    {
        return $this->container['full_path'];
    }

    /**
     * Sets full_path
     * @param string $full_path
     * @return $this
     */
    public function setFullPath($full_path)
    {
        $this->container['full_path'] = $full_path;

        return $this;
    }

    /**
     * Gets attribute_name
     * @return string
     */
    public function getAttributeName()
    {
        return $this->container['attribute_name'];
    }

    /**
     * Sets attribute_name
     * @param string $attribute_name
     * @return $this
     */
    public function setAttributeName($attribute_name)
    {
        $this->container['attribute_name'] = $attribute_name;

        return $this;
    }

    /**
     * Gets attribute_value
     * @return string
     */
    public function getAttributeValue()
    {
        return $this->container['attribute_value'];
    }

    /**
     * Sets attribute_value
     * @param string $attribute_value
     * @return $this
     */
    public function setAttributeValue($attribute_value)
    {
        $this->container['attribute_value'] = $attribute_value;

        return $this;
    }

    /**
     * Gets attribute_any_value
     * @return bool
     */
    public function getAttributeAnyValue()
    {
        return $this->container['attribute_any_value'];
    }

    /**
     * Sets attribute_any_value
     * @param bool $attribute_any_value
     * @return $this
     */
    public function setAttributeAnyValue($attribute_any_value)
    {
        $this->container['attribute_any_value'] = $attribute_any_value;

        return $this;
    }

    /**
     * Gets has_role
     * @return string
     */
    public function getHasRole()
    {
        return $this->container['has_role'];
    }

    /**
     * Sets has_role
     * @param string $has_role
     * @return $this
     */
    public function setHasRole($has_role)
    {
        $this->container['has_role'] = $has_role;

        return $this;
    }

    /**
     * Gets node_type
     * @return \Swagger\Client\Model\IdmNodeType
     */
    public function getNodeType()
    {
        return $this->container['node_type'];
    }

    /**
     * Sets node_type
     * @param \Swagger\Client\Model\IdmNodeType $node_type
     * @return $this
     */
    public function setNodeType($node_type)
    {
        $this->container['node_type'] = $node_type;

        return $this;
    }

    /**
     * Gets not
     * @return bool
     */
    public function getNot()
    {
        return $this->container['not'];
    }

    /**
     * Sets not
     * @param bool $not
     * @return $this
     */
    public function setNot($not)
    {
        $this->container['not'] = $not;

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


