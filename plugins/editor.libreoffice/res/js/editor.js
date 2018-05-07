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



import React, {Component} from 'react'
import {compose} from 'redux'

const configs = pydio.getPluginConfigs("editor.libreoffice");
const {withMenu, withLoader, withErrors, withControls} = PydioHOCs;

const Viewer = compose(
    withMenu,
    withLoader,
    withErrors
)(({url, style}) => <iframe src={url} style={{...style, width: "100%", height: "100%", border: 0, flex: 1}}></iframe>)

class Editor extends React.Component {

    constructor(props) {
        super(props)

        this.state = {}
    }

    componentWillMount() {
        let fileName = this.props.node.getPath();
        pydio.ApiClient.request({
            get_action: 'libreoffice_get_file_url',
            file: fileName
        }, ({responseJSON = {}}) => {
            //was (see above): let {host, uri, permission, jwt} = responseJSON;
            let {host, uri, permission, jwt} = responseJSON;
            let fileSrcUrl = encodeURIComponent(`${host}${uri}`);
            let webSocketUrl = encodeURIComponent(`${host}`);
            webSocketUrl = webSocketUrl.replace(/^https/, 'wss');
            webSocketUrl = webSocketUrl.replace(/^http/, 'ws');
            this.setState({url: `/loleaflet/dist/loleaflet.html?host=${webSocketUrl}&WOPISrc=${fileSrcUrl}&access_token=${jwt}&permission=${permission}`});
        });
    }

    render() {
        return (
            <Viewer {...this.props} url={this.state.url} />
        );
    }
}

export default Editor
