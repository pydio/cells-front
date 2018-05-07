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
import DocstoreDocumentQuery from './DocstoreDocumentQuery';





/**
* The DocstoreDeleteDocumentsRequest model module.
* @module model/DocstoreDeleteDocumentsRequest
* @version 1.0
*/
export default class DocstoreDeleteDocumentsRequest {
    /**
    * Constructs a new <code>DocstoreDeleteDocumentsRequest</code>.
    * @alias module:model/DocstoreDeleteDocumentsRequest
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>DocstoreDeleteDocumentsRequest</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/DocstoreDeleteDocumentsRequest} obj Optional instance to populate.
    * @return {module:model/DocstoreDeleteDocumentsRequest} The populated <code>DocstoreDeleteDocumentsRequest</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new DocstoreDeleteDocumentsRequest();

            
            
            

            if (data.hasOwnProperty('StoreID')) {
                obj['StoreID'] = ApiClient.convertToType(data['StoreID'], 'String');
            }
            if (data.hasOwnProperty('DocumentID')) {
                obj['DocumentID'] = ApiClient.convertToType(data['DocumentID'], 'String');
            }
            if (data.hasOwnProperty('Query')) {
                obj['Query'] = DocstoreDocumentQuery.constructFromObject(data['Query']);
            }
        }
        return obj;
    }

    /**
    * @member {String} StoreID
    */
    StoreID = undefined;
    /**
    * @member {String} DocumentID
    */
    DocumentID = undefined;
    /**
    * @member {module:model/DocstoreDocumentQuery} Query
    */
    Query = undefined;








}


