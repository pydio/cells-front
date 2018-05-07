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
import IdmPolicyGroup from '../model/IdmPolicyGroup';
import RestDeleteResponse from '../model/RestDeleteResponse';

/**
* EnterprisePolicyService service.
* @module api/EnterprisePolicyServiceApi
* @version 1.0
*/
export default class EnterprisePolicyServiceApi {

    /**
    * Constructs a new EnterprisePolicyServiceApi. 
    * @alias module:api/EnterprisePolicyServiceApi
    * @class
    * @param {module:ApiClient} apiClient Optional API client implementation to use,
    * default to {@link module:ApiClient#instance} if unspecified.
    */
    constructor(apiClient) {
        this.apiClient = apiClient || ApiClient.instance;
    }



    /**
     * Delete a security policy
     * @param {String} uuid 
     * @param {Object} opts Optional parameters
     * @param {String} opts.name 
     * @param {String} opts.description 
     * @param {String} opts.ownerUuid 
     * @param {module:model/String} opts.resourceGroup  (default to rest)
     * @param {Number} opts.lastUpdated 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/RestDeleteResponse} and HTTP response
     */
    deletePolicyWithHttpInfo(uuid, opts) {
      opts = opts || {};
      let postBody = null;

      // verify the required parameter 'uuid' is set
      if (uuid === undefined || uuid === null) {
        throw new Error("Missing the required parameter 'uuid' when calling deletePolicy");
      }


      let pathParams = {
        'Uuid': uuid
      };
      let queryParams = {
        'Name': opts['name'],
        'Description': opts['description'],
        'OwnerUuid': opts['ownerUuid'],
        'ResourceGroup': opts['resourceGroup'],
        'LastUpdated': opts['lastUpdated']
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = RestDeleteResponse;

      return this.apiClient.callApi(
        '/policy/{Uuid}', 'DELETE',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Delete a security policy
     * @param {String} uuid 
     * @param {Object} opts Optional parameters
     * @param {String} opts.name 
     * @param {String} opts.description 
     * @param {String} opts.ownerUuid 
     * @param {module:model/String} opts.resourceGroup  (default to rest)
     * @param {Number} opts.lastUpdated 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/RestDeleteResponse}
     */
    deletePolicy(uuid, opts) {
      return this.deletePolicyWithHttpInfo(uuid, opts)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


    /**
     * Update or create a security policy
     * @param {module:model/IdmPolicyGroup} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/IdmPolicyGroup} and HTTP response
     */
    putPolicyWithHttpInfo(body) {
      let postBody = body;

      // verify the required parameter 'body' is set
      if (body === undefined || body === null) {
        throw new Error("Missing the required parameter 'body' when calling putPolicy");
      }


      let pathParams = {
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
      let returnType = IdmPolicyGroup;

      return this.apiClient.callApi(
        '/policy', 'PUT',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Update or create a security policy
     * @param {module:model/IdmPolicyGroup} body 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/IdmPolicyGroup}
     */
    putPolicy(body) {
      return this.putPolicyWithHttpInfo(body)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


}
