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
* The RestDeleteResponse model module.
* @module model/RestDeleteResponse
* @version 1.0
*/
export default class RestDeleteResponse {
    /**
    * Constructs a new <code>RestDeleteResponse</code>.
    * @alias module:model/RestDeleteResponse
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>RestDeleteResponse</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/RestDeleteResponse} obj Optional instance to populate.
    * @return {module:model/RestDeleteResponse} The populated <code>RestDeleteResponse</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new RestDeleteResponse();

            
            
            

            if (data.hasOwnProperty('Success')) {
                obj['Success'] = ApiClient.convertToType(data['Success'], 'Boolean');
            }
            if (data.hasOwnProperty('NumRows')) {
                obj['NumRows'] = ApiClient.convertToType(data['NumRows'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {Boolean} Success
    */
    Success = undefined;
    /**
    * @member {String} NumRows
    */
    NumRows = undefined;








}


