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
* The AuthLdapSearchFilter model module.
* @module model/AuthLdapSearchFilter
* @version 1.0
*/
export default class AuthLdapSearchFilter {
    /**
    * Constructs a new <code>AuthLdapSearchFilter</code>.
    * @alias module:model/AuthLdapSearchFilter
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>AuthLdapSearchFilter</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/AuthLdapSearchFilter} obj Optional instance to populate.
    * @return {module:model/AuthLdapSearchFilter} The populated <code>AuthLdapSearchFilter</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new AuthLdapSearchFilter();

            
            
            

            if (data.hasOwnProperty('DNs')) {
                obj['DNs'] = ApiClient.convertToType(data['DNs'], ['String']);
            }
            if (data.hasOwnProperty('Filter')) {
                obj['Filter'] = ApiClient.convertToType(data['Filter'], 'String');
            }
            if (data.hasOwnProperty('IDAttribute')) {
                obj['IDAttribute'] = ApiClient.convertToType(data['IDAttribute'], 'String');
            }
            if (data.hasOwnProperty('DisplayAttribute')) {
                obj['DisplayAttribute'] = ApiClient.convertToType(data['DisplayAttribute'], 'String');
            }
            if (data.hasOwnProperty('Scope')) {
                obj['Scope'] = ApiClient.convertToType(data['Scope'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {Array.<String>} DNs
    */
    DNs = undefined;
    /**
    * @member {String} Filter
    */
    Filter = undefined;
    /**
    * @member {String} IDAttribute
    */
    IDAttribute = undefined;
    /**
    * @member {String} DisplayAttribute
    */
    DisplayAttribute = undefined;
    /**
    * @member {String} Scope
    */
    Scope = undefined;








}


