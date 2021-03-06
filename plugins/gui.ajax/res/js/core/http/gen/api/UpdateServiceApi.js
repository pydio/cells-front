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
import UpdateApplyUpdateResponse from '../model/UpdateApplyUpdateResponse';
import UpdateUpdateResponse from '../model/UpdateUpdateResponse';

/**
* UpdateService service.
* @module api/UpdateServiceApi
* @version 1.0
*/
export default class UpdateServiceApi {

    /**
    * Constructs a new UpdateServiceApi. 
    * @alias module:api/UpdateServiceApi
    * @class
    * @param {module:ApiClient} apiClient Optional API client implementation to use,
    * default to {@link module:ApiClient#instance} if unspecified.
    */
    constructor(apiClient) {
        this.apiClient = apiClient || ApiClient.instance;
    }



    /**
     * Apply an update to a given version
     * @param {String} targetVersion 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/UpdateApplyUpdateResponse} and HTTP response
     */
    applyUpdateWithHttpInfo(targetVersion) {
      let postBody = null;

      // verify the required parameter 'targetVersion' is set
      if (targetVersion === undefined || targetVersion === null) {
        throw new Error("Missing the required parameter 'targetVersion' when calling applyUpdate");
      }


      let pathParams = {
        'TargetVersion': targetVersion
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
      let returnType = UpdateApplyUpdateResponse;

      return this.apiClient.callApi(
        '/update/{TargetVersion}', 'GET',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Apply an update to a given version
     * @param {String} targetVersion 
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/UpdateApplyUpdateResponse}
     */
    applyUpdate(targetVersion) {
      return this.applyUpdateWithHttpInfo(targetVersion)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


    /**
     * Check the remote server to see if there are available binaries
     * @param {Object} opts Optional parameters
     * @param {String} opts.channel Channel name.
     * @param {String} opts.packageName Name of the currently running application.
     * @param {String} opts.currentVersion Current version of the application.
     * @param {String} opts.GOOS Current GOOS.
     * @param {String} opts.GOARCH Current GOARCH.
     * @param {String} opts.serviceName Not Used : specific service to get updates for.
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with an object containing data of type {@link module:model/UpdateUpdateResponse} and HTTP response
     */
    updateRequiredWithHttpInfo(opts) {
      opts = opts || {};
      let postBody = null;


      let pathParams = {
      };
      let queryParams = {
        'Channel': opts['channel'],
        'PackageName': opts['packageName'],
        'CurrentVersion': opts['currentVersion'],
        'GOOS': opts['GOOS'],
        'GOARCH': opts['GOARCH'],
        'ServiceName': opts['serviceName']
      };
      let headerParams = {
      };
      let formParams = {
      };

      let authNames = [];
      let contentTypes = ['application/json'];
      let accepts = ['application/json'];
      let returnType = UpdateUpdateResponse;

      return this.apiClient.callApi(
        '/update', 'GET',
        pathParams, queryParams, headerParams, formParams, postBody,
        authNames, contentTypes, accepts, returnType
      );
    }

    /**
     * Check the remote server to see if there are available binaries
     * @param {Object} opts Optional parameters
     * @param {String} opts.channel Channel name.
     * @param {String} opts.packageName Name of the currently running application.
     * @param {String} opts.currentVersion Current version of the application.
     * @param {String} opts.GOOS Current GOOS.
     * @param {String} opts.GOARCH Current GOARCH.
     * @param {String} opts.serviceName Not Used : specific service to get updates for.
     * @return {Promise} a {@link https://www.promisejs.org/|Promise}, with data of type {@link module:model/UpdateUpdateResponse}
     */
    updateRequired(opts) {
      return this.updateRequiredWithHttpInfo(opts)
        .then(function(response_and_data) {
          return response_and_data.data;
        });
    }


}
