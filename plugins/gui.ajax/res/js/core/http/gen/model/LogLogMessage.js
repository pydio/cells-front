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
* The LogLogMessage model module.
* @module model/LogLogMessage
* @version 1.0
*/
export default class LogLogMessage {
    /**
    * Constructs a new <code>LogLogMessage</code>.
    * LogMessage is the format used to transmit log messages to clients via the REST API.
    * @alias module:model/LogLogMessage
    * @class
    */

    constructor() {
        

        
        

        

        
    }

    /**
    * Constructs a <code>LogLogMessage</code> from a plain JavaScript object, optionally creating a new instance.
    * Copies all relevant properties from <code>data</code> to <code>obj</code> if supplied or a new instance if not.
    * @param {Object} data The plain JavaScript object bearing properties of interest.
    * @param {module:model/LogLogMessage} obj Optional instance to populate.
    * @return {module:model/LogLogMessage} The populated <code>LogLogMessage</code> instance.
    */
    static constructFromObject(data, obj) {
        if (data) {
            obj = obj || new LogLogMessage();

            
            
            

            if (data.hasOwnProperty('Ts')) {
                obj['Ts'] = ApiClient.convertToType(data['Ts'], 'Number');
            }
            if (data.hasOwnProperty('Level')) {
                obj['Level'] = ApiClient.convertToType(data['Level'], 'String');
            }
            if (data.hasOwnProperty('Logger')) {
                obj['Logger'] = ApiClient.convertToType(data['Logger'], 'String');
            }
            if (data.hasOwnProperty('Msg')) {
                obj['Msg'] = ApiClient.convertToType(data['Msg'], 'String');
            }
            if (data.hasOwnProperty('MsgId')) {
                obj['MsgId'] = ApiClient.convertToType(data['MsgId'], 'String');
            }
            if (data.hasOwnProperty('UserName')) {
                obj['UserName'] = ApiClient.convertToType(data['UserName'], 'String');
            }
            if (data.hasOwnProperty('UserUuid')) {
                obj['UserUuid'] = ApiClient.convertToType(data['UserUuid'], 'String');
            }
            if (data.hasOwnProperty('GroupPath')) {
                obj['GroupPath'] = ApiClient.convertToType(data['GroupPath'], 'String');
            }
            if (data.hasOwnProperty('Profile')) {
                obj['Profile'] = ApiClient.convertToType(data['Profile'], 'String');
            }
            if (data.hasOwnProperty('RoleUuids')) {
                obj['RoleUuids'] = ApiClient.convertToType(data['RoleUuids'], ['String']);
            }
            if (data.hasOwnProperty('RemoteAddress')) {
                obj['RemoteAddress'] = ApiClient.convertToType(data['RemoteAddress'], 'String');
            }
            if (data.hasOwnProperty('UserAgent')) {
                obj['UserAgent'] = ApiClient.convertToType(data['UserAgent'], 'String');
            }
            if (data.hasOwnProperty('HttpProtocol')) {
                obj['HttpProtocol'] = ApiClient.convertToType(data['HttpProtocol'], 'String');
            }
            if (data.hasOwnProperty('NodeUuid')) {
                obj['NodeUuid'] = ApiClient.convertToType(data['NodeUuid'], 'String');
            }
            if (data.hasOwnProperty('NodePath')) {
                obj['NodePath'] = ApiClient.convertToType(data['NodePath'], 'String');
            }
            if (data.hasOwnProperty('WsUuid')) {
                obj['WsUuid'] = ApiClient.convertToType(data['WsUuid'], 'String');
            }
            if (data.hasOwnProperty('WsScope')) {
                obj['WsScope'] = ApiClient.convertToType(data['WsScope'], 'String');
            }
            if (data.hasOwnProperty('SpanUuid')) {
                obj['SpanUuid'] = ApiClient.convertToType(data['SpanUuid'], 'String');
            }
            if (data.hasOwnProperty('SpanParentUuid')) {
                obj['SpanParentUuid'] = ApiClient.convertToType(data['SpanParentUuid'], 'String');
            }
            if (data.hasOwnProperty('SpanRootUuid')) {
                obj['SpanRootUuid'] = ApiClient.convertToType(data['SpanRootUuid'], 'String');
            }
        }
        return obj;
    }

    /**
    * @member {Number} Ts
    */
    Ts = undefined;
    /**
    * @member {String} Level
    */
    Level = undefined;
    /**
    * @member {String} Logger
    */
    Logger = undefined;
    /**
    * @member {String} Msg
    */
    Msg = undefined;
    /**
    * @member {String} MsgId
    */
    MsgId = undefined;
    /**
    * @member {String} UserName
    */
    UserName = undefined;
    /**
    * @member {String} UserUuid
    */
    UserUuid = undefined;
    /**
    * @member {String} GroupPath
    */
    GroupPath = undefined;
    /**
    * @member {String} Profile
    */
    Profile = undefined;
    /**
    * @member {Array.<String>} RoleUuids
    */
    RoleUuids = undefined;
    /**
    * @member {String} RemoteAddress
    */
    RemoteAddress = undefined;
    /**
    * @member {String} UserAgent
    */
    UserAgent = undefined;
    /**
    * @member {String} HttpProtocol
    */
    HttpProtocol = undefined;
    /**
    * @member {String} NodeUuid
    */
    NodeUuid = undefined;
    /**
    * @member {String} NodePath
    */
    NodePath = undefined;
    /**
    * @member {String} WsUuid
    */
    WsUuid = undefined;
    /**
    * @member {String} WsScope
    */
    WsScope = undefined;
    /**
    * @member {String} SpanUuid
    */
    SpanUuid = undefined;
    /**
    * @member {String} SpanParentUuid
    */
    SpanParentUuid = undefined;
    /**
    * @member {String} SpanRootUuid
    */
    SpanRootUuid = undefined;








}


