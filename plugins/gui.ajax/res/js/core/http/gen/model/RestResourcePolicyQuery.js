/**
 * Pydio Cells Rest API
 * No description provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: 1.0
 * 
 *
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen.git
 * Do not edit the class manually.
 *
 */


import ApiClient from '../ApiClient';
import ResourcePolicyQueryQueryType from './ResourcePolicyQueryQueryType';





/**
* The RestResourcePolicyQuery model module.
* @module model/RestResourcePolicyQuery
* @version 1.0
*/
export default class RestResourcePolicyQuery {
    /**
    * Constructs a new <code>RestResourcePolicyQuery</code>.
    * @alias module:model/RestResourcePolicyQuery
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>RestResourcePolicyQuery</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/RestResourcePolicyQuery} obj Optional instance to populate.
    * @return {module:model/RestResourcePolicyQuery} The populated <code>RestResourcePolicyQuery</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new RestResourcePolicyQuery();

            
            
            

            if (data.hasOwnProperty('Type')) {
                obj['Type'] = ResourcePolicyQueryQueryType.constructFromObject(data['Type']);
            }
            if (data.hasOwnProperty('UserId')) {
                obj['UserId'] = ApiClient.convertToType(data['UserId'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {module:model/ResourcePolicyQueryQueryType} Type
    */
    Type = undefined;
    /**
    * @member {String} UserId
    */
    UserId = undefined;








}


