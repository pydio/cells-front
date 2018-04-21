/*
 * Copyright 2007-2017 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <https://pydio.com>.
 */

import React from 'react'
import {FontIcon, FlatButton, TextField} from 'material-ui'
import {muiThemeable} from 'material-ui/styles'
import SharedUsers from './SharedUsers'
import NodesPicker from './NodesPicker'
import CellModel from 'pydio/model/cell'

/**
 * Dialog for letting users create a workspace
 */
let CreateCellDialog = React.createClass({

    childContextTypes: {
        messages:React.PropTypes.object,
        getMessage:React.PropTypes.func,
        isReadonly:React.PropTypes.func
    },

    getChildContext() {
        const messages = this.props.pydio.MessageHash;
        return {
            messages: messages,
            getMessage: function(messageId, namespace='share_center'){
                try{
                    return messages[namespace + (namespace?".":"") + messageId] || messageId;
                }catch(e){
                    return messageId;
                }
            },
            isReadonly: function(){
                return false;
            }.bind(this)
        };
    },

    getInitialState(){
        return {step:'users', model:new CellModel()};
    },

    componentDidMount(){
        this.refs.title.focus();
        this.state.model.observe('update', ()=>{this.forceUpdate()});
    },

    componentWillUnmount(){
        this.state.model.stopObserving('update');
    },

    submit(){
        const {model} = this.state;
        model.save().then(result => {
            this.props.onDismiss();
        }).catch(reason => {
            pydio.UI.displayMessage('ERROR', reason.message);
        });
    },

    computeSummaryString(){
        const {model} = this.state;
        const template = "You will create a cell shared with %USERS. Pick some folders you want to share or just let Pydio create an empty folder for you!";
        let users = 0;
        let groups = 0;
        let teams = 0;
        let userString = [];
        const objs = model.getAcls();
        Object.keys(objs).map(k => {
            const acl = objs[k];
            if(acl.Group) groups ++;
            else if(acl.Role) teams ++;
            else users ++;
        });
        if(users) userString.push( users + ' users');
        if(groups) userString.push( groups + ' groups');
        if(teams) userString.push( teams + ' teams');
        let finalString;
        if (userString.length === 3) {
            finalString = userString[0] + ', ' + userString[1] + ' and ' + userString[3];
        } else if (userString.length === 0) {
            finalString = 'nobody';
        } else {
            finalString = userString.join(' and ');
        }
        return template.replace('%USERS', finalString);
    },

    render: function(){

        let buttons = [];
        let content;
        const {pydio, muiTheme} = this.props;
        const {step, model} = this.state;

        if (step === 'users'){

            content = (
                <div>
                    <div>You can create a cell to share data with some users. First start by describing the object of this cell.</div>
                    <TextField ref={"title"} floatingLabelText={"Title of your cell"} value={model.getLabel()} onChange={(e,v)=>{model.setLabel(v)}} fullWidth={true}/>
                    <TextField floatingLabelText={"Optionally add a description"} value={model.getDescription()} onChange={(e,v)=>{model.setDescription(v)}} fullWidth={true}/>
                </div>
            );

            buttons.push(<FlatButton
                key="next1"
                disabled={!model.getLabel()}
                primary={true}
                label="Next"
                onTouchTap={()=>{this.setState({step:'data'})}} />
            );

        } else if(step === 'data') {

            content = (
                <div>
                    <div>Choose who you will share data with</div>
                    <SharedUsers
                        pydio={pydio}
                        cellAcls={model.getAcls()}

                        excludes={[pydio.user.id]}
                        onUserObjectAdd={model.addUser.bind(model)}
                        onUserObjectRemove={model.removeUser.bind(model)}
                        onUserObjectUpdateRight={model.updateUserRight.bind(model)}
                    />
                </div>
            );

            buttons.push(<FlatButton key="prev1" primary={false} label="Back" onTouchTap={()=>{this.setState({step:'users'})}} />);
            buttons.push(<FlatButton key="next2" primary={true} label="Next" onTouchTap={()=>this.setState({step:'label'})} />);

        } else {

            content = (
                <div>
                    <div>{this.computeSummaryString()}</div>
                    <div style={{paddingTop: 24}}>
                        <NodesPicker pydio={pydio} model={model}/>
                    </div>
                </div>
            );

            buttons.push(<FlatButton key="prev2" primary={false} label="Back" onTouchTap={()=>{this.setState({step:'data'})}} />);
            buttons.push(<FlatButton key="submit" primary={true} label="Go!" onTouchTap={this.submit.bind(this)} />);

        }

        const {primary1Color} = muiTheme.palette;

        return (
            <div style={{width: 380, fontSize: 13, color: 'rgba(0,0,0,.87)', display:'flex', flexDirection:'column', minHeight: 300}}>
                <div style={{backgroundColor: primary1Color, color: 'white', display:'flex', alignItems:'center', paddingLeft: 20}}>
                    <FontIcon className={"icomoon-cells-full-plus"} color={"white"}/>
                    <div style={{padding: 20, fontSize: 22}}>Create a Cell</div>
                </div>
                <div style={{padding: '20px 20px 10px', flex:1}}>
                    {content}
                </div>
                <div style={{padding:8, textAlign:'right'}}>
                    {buttons}
                </div>
            </div>
        );

    }

});

CreateCellDialog = muiThemeable()(CreateCellDialog);
export {CreateCellDialog as default}