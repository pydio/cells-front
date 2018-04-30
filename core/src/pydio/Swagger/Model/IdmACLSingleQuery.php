<?php
/**
 * IdmACLSingleQuery
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
 * IdmACLSingleQuery Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class IdmACLSingleQuery implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'idmACLSingleQuery';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'actions' => '\Swagger\Client\Model\IdmACLAction[]',
        'role_i_ds' => 'string[]',
        'workspace_i_ds' => 'string[]',
        'node_i_ds' => 'string[]',
        'not' => 'bool'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerFormats = [
        'actions' => null,
        'role_i_ds' => null,
        'workspace_i_ds' => null,
        'node_i_ds' => null,
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
        'actions' => 'Actions',
        'role_i_ds' => 'RoleIDs',
        'workspace_i_ds' => 'WorkspaceIDs',
        'node_i_ds' => 'NodeIDs',
        'not' => 'not'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'actions' => 'setActions',
        'role_i_ds' => 'setRoleIDs',
        'workspace_i_ds' => 'setWorkspaceIDs',
        'node_i_ds' => 'setNodeIDs',
        'not' => 'setNot'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'actions' => 'getActions',
        'role_i_ds' => 'getRoleIDs',
        'workspace_i_ds' => 'getWorkspaceIDs',
        'node_i_ds' => 'getNodeIDs',
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
        $this->container['actions'] = isset($data['actions']) ? $data['actions'] : null;
        $this->container['role_i_ds'] = isset($data['role_i_ds']) ? $data['role_i_ds'] : null;
        $this->container['workspace_i_ds'] = isset($data['workspace_i_ds']) ? $data['workspace_i_ds'] : null;
        $this->container['node_i_ds'] = isset($data['node_i_ds']) ? $data['node_i_ds'] : null;
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
     * Gets actions
     * @return \Swagger\Client\Model\IdmACLAction[]
     */
    public function getActions()
    {
        return $this->container['actions'];
    }

    /**
     * Sets actions
     * @param \Swagger\Client\Model\IdmACLAction[] $actions
     * @return $this
     */
    public function setActions($actions)
    {
        $this->container['actions'] = $actions;

        return $this;
    }

    /**
     * Gets role_i_ds
     * @return string[]
     */
    public function getRoleIDs()
    {
        return $this->container['role_i_ds'];
    }

    /**
     * Sets role_i_ds
     * @param string[] $role_i_ds
     * @return $this
     */
    public function setRoleIDs($role_i_ds)
    {
        $this->container['role_i_ds'] = $role_i_ds;

        return $this;
    }

    /**
     * Gets workspace_i_ds
     * @return string[]
     */
    public function getWorkspaceIDs()
    {
        return $this->container['workspace_i_ds'];
    }

    /**
     * Sets workspace_i_ds
     * @param string[] $workspace_i_ds
     * @return $this
     */
    public function setWorkspaceIDs($workspace_i_ds)
    {
        $this->container['workspace_i_ds'] = $workspace_i_ds;

        return $this;
    }

    /**
     * Gets node_i_ds
     * @return string[]
     */
    public function getNodeIDs()
    {
        return $this->container['node_i_ds'];
    }

    /**
     * Sets node_i_ds
     * @param string[] $node_i_ds
     * @return $this
     */
    public function setNodeIDs($node_i_ds)
    {
        $this->container['node_i_ds'] = $node_i_ds;

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


