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
* The RestListPeerFoldersRequest model module.
* @module model/RestListPeerFoldersRequest
* @version 1.0
*/
export default class RestListPeerFoldersRequest {
    /**
    * Constructs a new <code>RestListPeerFoldersRequest</code>.
    * @alias module:model/RestListPeerFoldersRequest
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>RestListPeerFoldersRequest</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/RestListPeerFoldersRequest} obj Optional instance to populate.
    * @return {module:model/RestListPeerFoldersRequest} The populated <code>RestListPeerFoldersRequest</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new RestListPeerFoldersRequest();

            
            
            

            if (data.hasOwnProperty('PeerAddress')) {
                obj['PeerAddress'] = ApiClient.convertToType(data['PeerAddress'], 'String');
            }
            if (data.hasOwnProperty('Path')) {
                obj['Path'] = ApiClient.convertToType(data['Path'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {String} PeerAddress
    */
    PeerAddress = undefined;
    /**
    * @member {String} Path
    */
    Path = undefined;








}


