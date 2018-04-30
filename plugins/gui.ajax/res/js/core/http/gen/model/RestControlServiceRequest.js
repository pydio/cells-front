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
import CtlServiceCommand from './CtlServiceCommand';





/**
* The RestControlServiceRequest model module.
* @module model/RestControlServiceRequest
* @version 1.0
*/
export default class RestControlServiceRequest {
    /**
    * Constructs a new <code>RestControlServiceRequest</code>.
    * @alias module:model/RestControlServiceRequest
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>RestControlServiceRequest</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/RestControlServiceRequest} obj Optional instance to populate.
    * @return {module:model/RestControlServiceRequest} The populated <code>RestControlServiceRequest</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new RestControlServiceRequest();

            
            
            

            if (data.hasOwnProperty('ServiceName')) {
                obj['ServiceName'] = ApiClient.convertToType(data['ServiceName'], 'String');
            }
            if (data.hasOwnProperty('NodeName')) {
                obj['NodeName'] = ApiClient.convertToType(data['NodeName'], 'String');
            }
            if (data.hasOwnProperty('Command')) {
                obj['Command'] = CtlServiceCommand.constructFromObject(data['Command']);
            }
        }
        return obj;
    }

    /**
    * @member {String} ServiceName
    */
    ServiceName = undefined;
    /**
    * @member {String} NodeName
    */
    NodeName = undefined;
    /**
    * @member {module:model/CtlServiceCommand} Command
    */
    Command = undefined;








}


