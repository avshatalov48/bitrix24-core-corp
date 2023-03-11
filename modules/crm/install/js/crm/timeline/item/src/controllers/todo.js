import { Type } from 'main.core';
import { FileUploaderPopup } from 'crm.activity.file-uploader-popup';
import { Base } from './base';
import ConfigurableItem from '../configurable-item';

export class ToDo extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'EditableDescription:StartEdit')
		{
			item.highlightContentBlockById('description', true);
		}

		if (action === 'EditableDescription:FinishEdit')
		{
			item.highlightContentBlockById('description', false);
		}

		if (action === 'Activity:ToDo:AddFile' && actionData)
		{
			this.#showFileUploaderPopup(item, actionData);
		}
	}

	#showFileUploaderPopup(item, actionData): void
	{
		const isValidParams = Type.isNumber(actionData.entityId)
			&& Type.isNumber(actionData.entityTypeId)
			&& Type.isNumber(actionData.ownerId)
			&& Type.isNumber(actionData.ownerTypeId);

		if (!isValidParams)
		{
			return;
		}

		actionData.files = actionData.files.split(',').filter(id => Type.isNumber(id));

		const fileList = item.getLayoutContentBlockById('fileList');
		if (fileList)
		{
			fileList.showFileUploaderPopup(actionData);
		}
		else
		{
			const popup = new FileUploaderPopup(actionData);
			popup.show();
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:ToDo');
	}
}
