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
* The JobsCtrlCommandResponse model module.
* @module model/JobsCtrlCommandResponse
* @version 1.0
*/
export default class JobsCtrlCommandResponse {
    /**
    * Constructs a new <code>JobsCtrlCommandResponse</code>.
    * @alias module:model/JobsCtrlCommandResponse
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>JobsCtrlCommandResponse</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/JobsCtrlCommandResponse} obj Optional instance to populate.
    * @return {module:model/JobsCtrlCommandResponse} The populated <code>JobsCtrlCommandResponse</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new JobsCtrlCommandResponse();

            
            
            

            if (data.hasOwnProperty('Msg')) {
                obj['Msg'] = ApiClient.convertToType(data['Msg'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {String} Msg
    */
    Msg = undefined;








}


