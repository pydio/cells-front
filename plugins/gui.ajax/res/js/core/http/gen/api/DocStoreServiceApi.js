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


import ApiClient from "../ApiClient";
import DocstoreDeleteDocumentsRequest from '../model/DocstoreDeleteDocumentsRequest';
import DocstoreDeleteDocumentsResponse from '../model/DocstoreDeleteDocumentsResponse';
import DocstoreGetDocumentResponse from '../model/DocstoreGetDocumentResponse';
import DocstorePutDocumentRequest from '../model/DocstorePutDocumentRequest';
import DocstorePutDocumentResponse from '../model/DocstorePutDocumentResponse';
import RestDocstoreCollection from '../model/RestDocstoreCollection';
import RestListDocstoreRequest from '../model/RestListDocstoreRequest';

/**
* DocStoreService service.
* @module api/DocStoreServiceApi
* @version 1.0
*/
export default class DocStoreServiceApi {

    /**
    * Constructs a new DocStoreServiceApi. 
    * @alias module:api/DocStoreServiceApi
    * @class
    * @param {module:ApiClient} apiClient Optional API client implementation to use,
    * default to {@link module:ApiClient#instance} if unspecified.
    */
    constructor(apiClient) {
        this.apiClient = apiClient || ApiClient.instance;
    }



    /**
     * Delete one or more docs inside a given store
     * @param {String} storeID 
     * @param {module:model/DocstoreDeleteDocumentsRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/DocstoreDeleteDocumentsResponse} and HTTP response
     */
    deleteDocWithHttpInfo(storeID, body) {
      let postBody = body;

      // verify the required parameter 'storeID' is set
      if (storeID === undefined || storeID === null) {
        throw new Error("Missing the required parameter 'storeID' when calling deleteDoc");
      }

      // verify the required parameter 'body' is set
      if (body === undefined || body === null) {
        throw new Error("Missing the required parameter 'body' when calling deleteDoc");
      }


      let pathParams = {
        'StoreID': storeID
      };
      let queryParams = {
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = DocstoreDeleteDocumentsResponse;

      return this.apiClient.callApi(
        '/docstore/bulk_delete/{StoreID}', 'POST',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Delete one or more docs inside a given store
     * @param {String} storeID 
     * @param {module:model/DocstoreDeleteDocumentsRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/DocstoreDeleteDocumentsResponse}
     */
    deleteDoc(storeID, body) {
      return this.deleteDocWithHttpInfo(storeID, body)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


    /**
     * Load one document by ID from a given store
     * @param {String} storeID 
     * @param {String} documentID 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/DocstoreGetDocumentResponse} and HTTP response
     */
    getDocWithHttpInfo(storeID, documentID) {
      let postBody = null;

      // verify the required parameter 'storeID' is set
      if (storeID === undefined || storeID === null) {
        throw new Error("Missing the required parameter 'storeID' when calling getDoc");
      }

      // verify the required parameter 'documentID' is set
      if (documentID === undefined || documentID === null) {
        throw new Error("Missing the required parameter 'documentID' when calling getDoc");
      }


      let pathParams = {
        'StoreID': storeID,
        'DocumentID': documentID
      };
      let queryParams = {
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = DocstoreGetDocumentResponse;

      return this.apiClient.callApi(
        '/docstore/{StoreID}/{DocumentID}', 'GET',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Load one document by ID from a given store
     * @param {String} storeID 
     * @param {String} documentID 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/DocstoreGetDocumentResponse}
     */
    getDoc(storeID, documentID) {
      return this.getDocWithHttpInfo(storeID, documentID)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


    /**
     * List all docs of a given store
     * @param {String} storeID 
     * @param {module:model/RestListDocstoreRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/RestDocstoreCollection} and HTTP response
     */
    listDocsWithHttpInfo(storeID, body) {
      let postBody = body;

      // verify the required parameter 'storeID' is set
      if (storeID === undefined || storeID === null) {
        throw new Error("Missing the required parameter 'storeID' when calling listDocs");
      }

      // verify the required parameter 'body' is set
      if (body === undefined || body === null) {
        throw new Error("Missing the required parameter 'body' when calling listDocs");
      }


      let pathParams = {
        'StoreID': storeID
      };
      let queryParams = {
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = RestDocstoreCollection;

      return this.apiClient.callApi(
        '/docstore/{StoreID}', 'POST',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * List all docs of a given store
     * @param {String} storeID 
     * @param {module:model/RestListDocstoreRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/RestDocstoreCollection}
     */
    listDocs(storeID, body) {
      return this.listDocsWithHttpInfo(storeID, body)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


    /**
     * Put a document inside a given store
     * @param {String} storeID 
     * @param {String} documentID 
     * @param {module:model/DocstorePutDocumentRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/DocstorePutDocumentResponse} and HTTP response
     */
    putDocWithHttpInfo(storeID, documentID, body) {
      let postBody = body;

      // verify the required parameter 'storeID' is set
      if (storeID === undefined || storeID === null) {
        throw new Error("Missing the required parameter 'storeID' when calling putDoc");
      }

      // verify the required parameter 'documentID' is set
      if (documentID === undefined || documentID === null) {
        throw new Error("Missing the required parameter 'documentID' when calling putDoc");
      }

      // verify the required parameter 'body' is set
      if (body === undefined || body === null) {
        throw new Error("Missing the required parameter 'body' when calling putDoc");
      }


      let pathParams = {
        'StoreID': storeID,
        'DocumentID': documentID
      };
      let queryParams = {
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = DocstorePutDocumentResponse;

      return this.apiClient.callApi(
        '/docstore/{StoreID}/{DocumentID}', 'PUT',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Put a document inside a given store
     * @param {String} storeID 
     * @param {String} documentID 
     * @param {module:model/DocstorePutDocumentRequest} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/DocstorePutDocumentResponse}
     */
    putDoc(storeID, documentID, body) {
      return this.putDocWithHttpInfo(storeID, documentID, body)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


}
