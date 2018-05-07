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
import TreeNode from './TreeNode';





/**
* The RestNodesCollection model module.
* @module model/RestNodesCollection
* @version 1.0
*/
export default class RestNodesCollection {
    /**
    * Constructs a new <code>RestNodesCollection</code>.
    * @alias module:model/RestNodesCollection
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>RestNodesCollection</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/RestNodesCollection} obj Optional instance to populate.
    * @return {module:model/RestNodesCollection} The populated <code>RestNodesCollection</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new RestNodesCollection();

            
            
            

            if (data.hasOwnProperty('Parent')) {
                obj['Parent'] = TreeNode.constructFromObject(data['Parent']);
            }
            if (data.hasOwnProperty('Children')) {
                obj['Children'] = ApiClient.convertToType(data['Children'], [TreeNode]);
            }
        }
        return obj;
    }

    /**
    * @member {module:model/TreeNode} Parent
    */
    Parent = undefined;
    /**
    * @member {Array.<module:model/TreeNode>} Children
    */
    Children = undefined;








}


