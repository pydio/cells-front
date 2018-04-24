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

const React = require('react');
const PydioApi = require('pydio/http/api');
const BootUI = require('pydio/http/resources-manager').requireLib('boot');
const {ActionDialogMixin, SubmitButtonProviderMixin, Loader} = BootUI;
import AboutCellsCard from './AboutCellsCard'
import {Card, CardTitle, CardText} from 'material-ui'

const SplashDialog = React.createClass({

    mixins:[
        ActionDialogMixin,
        SubmitButtonProviderMixin
    ],

    getDefaultProps: function(){
        return {
            dialogTitle: '',
            dialogSize:'lg',
            dialogIsModal: false,
            dialogPadding: false,
            dialogScrollBody: true
        };
    },
    submit(){
        this.dismiss();
    },

    getInitialState: function(){
        return {aboutContent: null};
    },

    componentDidMount: function(){

        PydioApi.getClient().request({
            get_action:'display_doc',
            doc_file:'CREDITS'
        }, function(transport){
            this.setState({
                aboutContent: transport.responseText
            });
        }.bind(this));

    },

    render: function(){
        let credit;
        if (this.state.aboutContent) {
            let ct = () => {
                return {__html: this.state.aboutContent}
            };
            credit = <div dangerouslySetInnerHTML={ct()}/>;
        } else {
            credit = <Loader style={{minHeight: 200}}/>;
        }
        credit = (
            <Card style={{margin:10}}>
                <CardTitle
                    title="Version Information"
                    subtitle="Details about version, licensing and how to get help"
                />
                <CardText>
                    {credit}
                </CardText>
            </Card>
        );
        return (
            <div style={{height:'100%', backgroundColor: '#CFD8DC'}}>
                <AboutCellsCard style={{margin:10}}/>
                {credit}
            </div>
        );
    }

});

export default SplashDialog