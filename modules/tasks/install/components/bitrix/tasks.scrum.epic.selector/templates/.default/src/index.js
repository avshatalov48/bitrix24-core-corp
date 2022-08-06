import {Type} from 'main.core';

import {ViewSelector} from './view';
import {EditSelector} from './edit';

import '../css/base.css';

type Params = {
	groupId: number,
	taskId: number,
	epic: ?Epic,
	canEdit: 'Y' | 'N',
	mode: 'edit' | 'view',
	inputName: string
}

type Epic = {
	id: number,
	groupId: number,
	name: string,
	description: string,
	createdBy: number,
	modifiedBy: number,
	color: string
}

export class EpicSelector
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);
		this.taskId = parseInt(params.taskId, 10);
		this.epic = Type.isPlainObject(params.epic) ? params.epic : null;
		this.canEdit = params.canEdit === 'Y';
		this.mode = params.mode === 'edit' ? 'edit' : 'view';
		this.inputName = params.inputName;
	}

	renderTo(container: HTMLElement)
	{
		if (this.mode === 'view')
		{
			(new ViewSelector({
				groupId: this.groupId,
				taskId: this.taskId,
				epic: this.epic,
				canEdit: this.canEdit
			}))
				.renderTo(container)
			;
		}
		else
		{
			(new EditSelector({
				groupId: this.groupId,
				taskId: this.taskId,
				epic: this.epic,
				inputName: this.inputName
			}))
				.renderTo(container)
			;
		}
	}
}