<?php
/**
 * IdmUser
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
 * IdmUser Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class IdmUser implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'idmUser';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'uuid' => 'string',
        'group_path' => 'string',
        'attributes' => 'map[string,string]',
        'roles' => '\Swagger\Client\Model\IdmRole[]',
        'login' => 'string',
        'password' => 'string',
        'is_group' => 'bool',
        'group_label' => 'string',
        'policies' => '\Swagger\Client\Model\ServiceResourcePolicy[]',
        'policies_context_editable' => 'bool'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerFormats = [
        'uuid' => null,
        'group_path' => null,
        'attributes' => null,
        'roles' => null,
        'login' => null,
        'password' => null,
        'is_group' => 'boolean',
        'group_label' => null,
        'policies' => null,
        'policies_context_editable' => 'boolean'
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
        'group_path' => 'GroupPath',
        'attributes' => 'Attributes',
        'roles' => 'Roles',
        'login' => 'Login',
        'password' => 'Password',
        'is_group' => 'IsGroup',
        'group_label' => 'GroupLabel',
        'policies' => 'Policies',
        'policies_context_editable' => 'PoliciesContextEditable'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'uuid' => 'setUuid',
        'group_path' => 'setGroupPath',
        'attributes' => 'setAttributes',
        'roles' => 'setRoles',
        'login' => 'setLogin',
        'password' => 'setPassword',
        'is_group' => 'setIsGroup',
        'group_label' => 'setGroupLabel',
        'policies' => 'setPolicies',
        'policies_context_editable' => 'setPoliciesContextEditable'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'uuid' => 'getUuid',
        'group_path' => 'getGroupPath',
        'attributes' => 'getAttributes',
        'roles' => 'getRoles',
        'login' => 'getLogin',
        'password' => 'getPassword',
        'is_group' => 'getIsGroup',
        'group_label' => 'getGroupLabel',
        'policies' => 'getPolicies',
        'policies_context_editable' => 'getPoliciesContextEditable'
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
        $this->container['group_path'] = isset($data['group_path']) ? $data['group_path'] : null;
        $this->container['attributes'] = isset($data['attributes']) ? $data['attributes'] : null;
        $this->container['roles'] = isset($data['roles']) ? $data['roles'] : null;
        $this->container['login'] = isset($data['login']) ? $data['login'] : null;
        $this->container['password'] = isset($data['password']) ? $data['password'] : null;
        $this->container['is_group'] = isset($data['is_group']) ? $data['is_group'] : null;
        $this->container['group_label'] = isset($data['group_label']) ? $data['group_label'] : null;
        $this->container['policies'] = isset($data['policies']) ? $data['policies'] : null;
        $this->container['policies_context_editable'] = isset($data['policies_context_editable']) ? $data['policies_context_editable'] : null;
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
     * Gets attributes
     * @return map[string,string]
     */
    public function getAttributes()
    {
        return $this->container['attributes'];
    }

    /**
     * Sets attributes
     * @param map[string,string] $attributes
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->container['attributes'] = $attributes;

        return $this;
    }

    /**
     * Gets roles
     * @return \Swagger\Client\Model\IdmRole[]
     */
    public function getRoles()
    {
        return $this->container['roles'];
    }

    /**
     * Sets roles
     * @param \Swagger\Client\Model\IdmRole[] $roles
     * @return $this
     */
    public function setRoles($roles)
    {
        $this->container['roles'] = $roles;

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
     * Gets is_group
     * @return bool
     */
    public function getIsGroup()
    {
        return $this->container['is_group'];
    }

    /**
     * Sets is_group
     * @param bool $is_group
     * @return $this
     */
    public function setIsGroup($is_group)
    {
        $this->container['is_group'] = $is_group;

        return $this;
    }

    /**
     * Gets group_label
     * @return string
     */
    public function getGroupLabel()
    {
        return $this->container['group_label'];
    }

    /**
     * Sets group_label
     * @param string $group_label
     * @return $this
     */
    public function setGroupLabel($group_label)
    {
        $this->container['group_label'] = $group_label;

        return $this;
    }

    /**
     * Gets policies
     * @return \Swagger\Client\Model\ServiceResourcePolicy[]
     */
    public function getPolicies()
    {
        return $this->container['policies'];
    }

    /**
     * Sets policies
     * @param \Swagger\Client\Model\ServiceResourcePolicy[] $policies
     * @return $this
     */
    public function setPolicies($policies)
    {
        $this->container['policies'] = $policies;

        return $this;
    }

    /**
     * Gets policies_context_editable
     * @return bool
     */
    public function getPoliciesContextEditable()
    {
        return $this->container['policies_context_editable'];
    }

    /**
     * Sets policies_context_editable
     * @param bool $policies_context_editable
     * @return $this
     */
    public function setPoliciesContextEditable($policies_context_editable)
    {
        $this->container['policies_context_editable'] = $policies_context_editable;

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


