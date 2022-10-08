import Item from './item';
import { MessageBox } from 'ui.dialogs.messagebox';
import {Loc, ajax as Ajax} from "main.core";
import {Options as GridOptions} from "../options";

export default class ItemDelete extends Item
{
	object: {id: number, name: string};

	constructor(trackedObjectId, itemData)
	{
		super(trackedObjectId, itemData);
		this.object = {
			id: itemData['dataset']['objectId'],
			name: itemData['dataset']['objectName'],
		};

		this.data['onclick'] = this.handleClick.bind(this);
	}

	handleClick()
	{
		this.emit('close');

		MessageBox.show({
			title: Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_TITLE'),
			message: Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_MESSAGE', {
				'#NAME#': this.object.name,
			}),
			modal: true,
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('DISK_DOCUMENTS_ACT_DELETE_OK_BUTTON'),
			onOk: this.handleClickDelete.bind(this),
		});
	}

	handleClickDelete(): boolean
	{
		Ajax.runAction('disk.api.commonActions.markDeleted', {
			analyticsLabel: 'folder.list.dd',
			data: {
				objectId: this.object.id
			}
		}).then((response) => {
			if (response.status === 'success')
			{
				const commonGrid = GridOptions.getCommonGrid();
				commonGrid.removeItemById(this.trackedObjectId);
			}
		});

		return true;
	}

	static detect(itemData)
	{
		return itemData['id'] === 'delete';
	}
}

