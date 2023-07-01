import {ajax, Loc, Type} from 'main.core';
import {MessageBox} from 'ui.dialogs.messagebox';

type ErrorResponse = {
	data: string,
	errors: Array,
	status: string
}

export class RequestSender
{
	constructor(options = {})
	{
		this.signedParameters = (options.signedParameters ? options.signedParameters : '');
		this.debugMode = options.debugMode;
	}

	getSignedParameters(): string
	{
		return this.signedParameters;
	}

	sendRequest(action: string, data = {}): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(action, {
				signedParameters: this.signedParameters,
				data: data
			}).then(resolve, reject);
		});
	}

	sendRequestToComponent(data = {}, action, analyticsLabel = {}): Promise
	{
		data.debugMode = this.debugMode;
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:tasks.scrum', action, {
				mode: 'class',
				signedParameters: this.signedParameters,
				data: data,
				analyticsLabel: analyticsLabel
			}).then(resolve, reject);
		});
	}

	removeItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeItems');
	}

	updateItemSort(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItemSort');
	}

	addTag(data): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:tasks.tag.list', 'addTag', {
				mode: 'class',
				data: data,
			}).then(resolve, reject);
		});
	}

	updateSprintSort(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateSprintSort');
	}

	createSprint(data): Promise
	{
		return this.sendRequestToComponent(data, 'createSprint');
	}

	startSprint(data): Promise
	{
		return this.sendRequestToComponent(data, 'startSprint');
	}

	completeSprint(data): Promise
	{
		return this.sendRequestToComponent(data, 'completeSprint');
	}

	createTask(data): Promise
	{
		return this.sendRequestToComponent(
			data,
			'createTask',
			{
				scrum: 'Y',
				action: 'create_task',
			}
		);
	}

	updateItem(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItem');
	}

	changeTaskResponsible(data): Promise
	{
		return this.sendRequestToComponent(data, 'changeTaskResponsible');
	}

	getCurrentState(data): Promise
	{
		return this.sendRequestToComponent(data, 'getCurrentState');
	}

	hasTaskInFilter(data): Promise
	{
		return this.sendRequestToComponent(data, 'hasTaskInFilter');
	}

	removeSprint(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeSprint');
	}

	changeSprintName(data): Promise
	{
		return this.sendRequestToComponent(data, 'changeSprintName');
	}

	changeSprintDeadline(data): Promise
	{
		return this.sendRequestToComponent(data, 'changeSprintDeadline');
	}

	getSprintCompletedItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSprintCompletedItems');
	}

	getCompletedSprints(data): Promise
	{
		return this.sendRequestToComponent(data, 'getCompletedSprints');
	}

	getCompletedSprintsStats(data): Promise
	{
		return this.sendRequestToComponent(data, 'getCompletedSprintsStats');
	}

	getItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'getItems');
	}

	saveShortView(data): Promise
	{
		return this.sendRequestToComponent(data, 'saveShortView');
	}

	saveDisplayPriority(data): Promise
	{
		return this.sendRequestToComponent(data, 'saveDisplayPriority');
	}

	getEntityCounters(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEntityCounters');
	}

	attachFilesToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'attachFilesToTask');
	}

	updateTaskTags(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateTaskTags');
	}

	removeTaskTags(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeTaskTags');
	}

	updateItemEpics(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItemEpics');
	}

	updateBorderColorToLinkedItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateBorderColorToLinkedItems');
	}

	applyFilter(data): Promise
	{
		return this.sendRequestToComponent(data, 'applyFilter');
	}

	showLinkedTasks(data): Promise
	{
		return this.sendRequestToComponent(data, 'showLinkedTasks');
	}

	getAllUsedItemBorderColors(data): Promise
	{
		return this.sendRequestToComponent(data, 'getAllUsedItemBorderColors');
	}

	getSubTaskItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSubTaskItems');
	}

	createEpic(data): Promise
	{
		return this.sendRequest('bitrix:tasks.scrum.epic.createEpic', data);
	}

	getEpic(data): Promise
	{
		return this.sendRequest('bitrix:tasks.scrum.epic.getEpic', data);
	}

	getItemData(data): Promise
	{
		return this.sendRequestToComponent(data, 'getItemData');
	}

	getSprintData(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSprintData');
	}

	saveSprintVisibility(data): Promise
	{
		return this.sendRequestToComponent(data, 'saveSprintVisibility');
	}

	showErrorAlert(response: ErrorResponse, alertTitle?: string)
	{
		if (Type.isUndefined(response.errors))
		{
			return;
		}

		if (response.errors.length)
		{
			const firstError = response.errors.shift();
			if (firstError)
			{
				const errorCode = (firstError.code ? firstError.code : '');

				const message = firstError.message + ' ' + errorCode;
				const title = (alertTitle ? alertTitle : Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));

				MessageBox.alert(message, title);
			}
		}
	}
}
