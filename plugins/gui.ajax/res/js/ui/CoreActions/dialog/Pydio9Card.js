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

import {Component} from 'react'
const {PydioContextConsumer} = require('pydio').requireLib('boot');
import {Card, CardActions, Divider, FontIcon, CardMedia, CardTitle, CardText, FlatButton} from 'material-ui'

class Pydio9Card extends Component {

    openDocs(){
        open("http://pydio9-tech-preview.readthedocs.io/");
    }

    openSlack(){
        open("https://join.slack.com/t/pydio9/shared_invite/enQtMjgwNjg4NzY3NDQwLTJlNzUxYTZiMDVjMWNjYTg0NDMxZjQxMWM0NmQ1ZmEyYjVjOTI5NjM0ODEwNmFlODkyMDBhYmU4NmJiOWU5MTk");
    }

    openGithub(){
        open("https://github.com/pydio/pydio9-beta-docs/issues");
    }

    render(){
        const {pydio, style} = this.props;
        const imgBase = pydio.Parameters.get('ajxpResourcesFolder') + '/themes/common/images';

        return (
            <Card style={style}>
                <CardMedia><img src={imgBase + "/Pydio9-arch.png"} /></CardMedia>
                <CardTitle title="Welcome to Pydio9 Beta" subtitle="This is a tech preview of Pydio9" />
                <CardText>
                    Thank you for taking the time to take a glance at the future of Pydio! This version is a technology preview,
                    please beware that it is <u>NOT PRODUCTION READY</u>! Particularly on the security level, the backend API's are
                    not secured yet. <br/><br/>
                    Pydio 9 is a full rewrite of the PHP server into #Go, the server language used by Google on their own datacenters all
                    over the world. It is designed with scalability and open standards in mind, and based on a micro-services architecture.
                    Please refer to the dedicated documentation to read more about this new architecture and how you can help testing it.
                </CardText>
                <Divider/>
                <CardActions>
                    <FlatButton primary={true} icon={<FontIcon className="mdi mdi-book-variant" />} label="P9 Docs" onTouchTap={this.openDocs} />
                    <FlatButton primary={true} icon={<FontIcon className="mdi mdi-slack" />} label="Channel" onTouchTap={this.openSlack}/>
                    <FlatButton primary={true} icon={<FontIcon className="mdi mdi-github-box" />} label="Issues" onTouchTap={this.openGithub}/>
                </CardActions>
            </Card>
        );
    }

}

Pydio9Card = PydioContextConsumer(Pydio9Card);

export {Pydio9Card as default}