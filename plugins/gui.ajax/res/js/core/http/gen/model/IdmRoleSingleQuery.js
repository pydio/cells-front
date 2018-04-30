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





/**
* The IdmRoleSingleQuery model module.
* @module model/IdmRoleSingleQuery
* @version 1.0
*/
export default class IdmRoleSingleQuery {
    /**
    * Constructs a new <code>IdmRoleSingleQuery</code>.
    * @alias module:model/IdmRoleSingleQuery
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>IdmRoleSingleQuery</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/IdmRoleSingleQuery} obj Optional instance to populate.
    * @return {module:model/IdmRoleSingleQuery} The populated <code>IdmRoleSingleQuery</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new IdmRoleSingleQuery();

            
            
            

            if (data.hasOwnProperty('Uuid')) {
                obj['Uuid'] = ApiClient.convertToType(data['Uuid'], ['String']);
            }
            if (data.hasOwnProperty('Label')) {
                obj['Label'] = ApiClient.convertToType(data['Label'], 'String');
            }
            if (data.hasOwnProperty('IsTeam')) {
                obj['IsTeam'] = ApiClient.convertToType(data['IsTeam'], 'Boolean');
            }
            if (data.hasOwnProperty('IsGroupRole')) {
                obj['IsGroupRole'] = ApiClient.convertToType(data['IsGroupRole'], 'Boolean');
            }
            if (data.hasOwnProperty('IsUserRole')) {
                obj['IsUserRole'] = ApiClient.convertToType(data['IsUserRole'], 'Boolean');
            }
            if (data.hasOwnProperty('HasAutoApply')) {
                obj['HasAutoApply'] = ApiClient.convertToType(data['HasAutoApply'], 'Boolean');
            }
            if (data.hasOwnProperty('not')) {
                obj['not'] = ApiClient.convertToType(data['not'], 'Boolean');
            }
        }
        return obj;
    }

    /**
    * @member {Array.<String>} Uuid
    */
    Uuid = undefined;
    /**
    * @member {String} Label
    */
    Label = undefined;
    /**
    * @member {Boolean} IsTeam
    */
    IsTeam = undefined;
    /**
    * @member {Boolean} IsGroupRole
    */
    IsGroupRole = undefined;
    /**
    * @member {Boolean} IsUserRole
    */
    IsUserRole = undefined;
    /**
    * @member {Boolean} HasAutoApply
    */
    HasAutoApply = undefined;
    /**
    * @member {Boolean} not
    */
    not = undefined;








}


