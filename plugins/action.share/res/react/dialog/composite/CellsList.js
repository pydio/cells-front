import React from "react"
import CompositeModel from './CompositeModel'
import SharedUsers from '../cells/SharedUsers'
import Pydio from 'pydio'
import {Paper, Divider, RaisedButton, IconButton, Popover, Menu, List, ListItem, IconMenu, MenuItem} from 'material-ui'

class CellsList extends React.Component {

    constructor(props){
        super(props);
        this.state = {edit: null};
    }

    addToCellsMenuItems(){
        let items = [];
        // List user available cells - Exclude cells where this node is already shared
        const {pydio, compositeModel} = this.props;
        const currentCells = compositeModel.getCells().map(cellModel => cellModel.getUuid());
        pydio.user.getRepositoriesList().forEach(repository => {
            if (repository.getOwner() === 'shared' && currentCells.indexOf(repository.getId()) === -1){
                const touchTap = () => {
                    this.setState({addMenuOpen:false});
                    compositeModel.addToExistingCell(repository.getId());
                };
                items.push(<MenuItem primaryText={repository.getLabel()} onTouchTap={touchTap}/>);
            }
        });
        return items;
    }

    render(){

        const {compositeModel, pydio, usersInvitations} = this.props;
        const {edit} = this.state;
        let cells = [];
        compositeModel.getCells().map(cellModel => {
            const label = cellModel.getLabel();
            const isEdit = (!cellModel.getUuid() && edit==='NEWCELL') || edit === cellModel.getUuid();
            const toggleState = () => {
                if(isEdit && edit === 'NEWCELL'){
                    // Remove new cell if it was created empty
                    const acls = cellModel.getAcls();
                    if(!Object.keys(acls).length){
                        compositeModel.removeNewCell(cellModel);
                    }
                }
                this.setState({edit:isEdit?null:cellModel.getUuid()});
            };

            const removeNode = () => {
                cellModel.removeRootNode(compositeModel.getNode().getMetadata().get('uuid'));
            };
            let rightIcon;
            if(isEdit){
                rightIcon = <IconButton iconClassName={"mdi mdi-close"} tooltip={"Close"} onTouchTap={toggleState}/>;
            } else if (cellModel.isEditable()) {
                rightIcon = (
                    <IconMenu
                        iconButtonElement={<IconButton iconClassName={"mdi mdi-dots-vertical"}/>}
                        anchorOrigin={{horizontal:'right', vertical:'top'}}
                        targetOrigin={{horizontal:'right', vertical:'top'}}
                    >
                        <MenuItem primaryText={"Edit cell users"} onTouchTap={toggleState}/>
                        <MenuItem primaryText={"Remove from this cell"} onTouchTap={removeNode}/>
                    </IconMenu>
                );
            }
            cells.push(
                <ListItem
                    primaryText={label}
                    secondaryText={cellModel.getAclsSubjects()}
                    rightIconButton={rightIcon}
                    onTouchTap={toggleState}
                    style={isEdit?{backgroundColor:'rgb(245, 245, 245)'}:{}}
                    disabled={edit === 'NEWCELL' && !isEdit}
                />
            );
            if(isEdit){
                cells.push(
                    <Paper zDepth={0} style={{backgroundColor:'rgb(245, 245, 245)', margin: '0 0 16px', padding: '0 10px 10px'}}>
                        <SharedUsers
                            pydio={pydio}
                            cellAcls={cellModel.getAcls()}
                            excludes={[pydio.user.id]}
                            onUserObjectAdd={cellModel.addUser.bind(cellModel)}
                            onUserObjectRemove={cellModel.removeUser.bind(cellModel)}
                            onUserObjectUpdateRight={cellModel.updateUserRight.bind(cellModel)}
                            sendInvitations={(targetUsers) => usersInvitations(targetUsers, cellModel)}
                            saveSelectionAsTeam={false}
                            readonly={!cellModel.isEditable()}
                        />
                    </Paper>
                );
            }
            cells.push(<Divider/>);
        });
        cells.pop();

        let legend;
        if(cells.length && edit !== 'NEWCELL') {
            legend = <div>Folder is shared in the following cells. You can edit the users or create a new cell.</div>
        } else if (cells.length && edit==='NEWCELL') {
            legend = <div>Pick users or groups who will have access to this cell</div>
        } else {
            legend = <div style={{padding:'21px 16px'}}>Share this folder with other users by creating a new cell</div>
        }

        const addCellItems = this.addToCellsMenuItems();
        let addToCellMenu;
        if(addCellItems.length){
            addToCellMenu = <span>
                <RaisedButton
                    style={{marginLeft: 10}}
                    primary={true}
                    label={"Add to cell..."}
                    onTouchTap={(event)=>{this.setState({addMenuOpen:true, addMenuAnchor:event.target})}}
                />
                <Popover
                    open={this.state.addMenuOpen}
                    anchorEl={this.state.addMenuAnchor}
                    onRequestClose={()=>{this.setState({addMenuOpen: false})}}
                    anchorOrigin={{horizontal:'left', vertical:'bottom'}}
                    targetOrigin={{horizontal:'left', vertical:'top'}}
                >
                    <Menu>{addCellItems}</Menu>
                </Popover>
            </span>
        }

        return (
            <div>
                <div style={{paddingBottom: 20}}>
                    <RaisedButton label={"+ New Cell"} primary={true} onTouchTap={()=>{compositeModel.createEmptyCell();this.setState({edit:'NEWCELL'})}}/>
                    {addToCellMenu}
                </div>
                <div style={{fontSize: 13, fontWeight: 500, color: 'rgba(0, 0, 0, 0.43)'}}>{legend}</div>
                <List>{cells}</List>
            </div>
        );
    }

}

CellsList.PropTypes = {
    pydio: React.PropTypes.instanceOf(Pydio),
    compositeModel: React.PropTypes.instanceOf(CompositeModel).isRequired,
    usersInvitations: React.PropTypes.func,
};

export {CellsList as default}