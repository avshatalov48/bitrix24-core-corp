import {ajax} from 'main.core';

export class RequestSender
{
	constructor(options = {})
	{
		this.signedParameters = (options.signedParameters ? options.signedParameters : '');
		this.debugMode = options.debugMode;
	}

	getSignedParameters()
	{
		return this.signedParameters;
	}

	sendRequest(data = {}, action)
	{
		data.debugMode = this.debugMode;
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:tasks.scrum', action, {
				mode: 'class',
				signedParameters: this.signedParameters,
				data: data
			}).then(resolve, reject);
		});
	}

	updateItemSort(data)
	{
		return this.sendRequest(data, 'updateItemSort');
	}

	updateSprintSort(data)
	{
		return this.sendRequest(data, 'updateSprintSort');
	}

	createSprint(data)
	{
		return this.sendRequest(data, 'createSprint');
	}

	startSprint(data)
	{
		return this.sendRequest(data, 'startSprint');
	}

	completeSprint(data)
	{
		return this.sendRequest(data, 'completeSprint');
	}

	createTask(data)
	{
		return this.sendRequest(data, 'createTask');
	}

	updateItem(data)
	{
		return this.sendRequest(data, 'updateItem');
	}

	removeItem(data)
	{
		return this.sendRequest(data, 'removeItem');
	}

	changeTaskResponsible(data)
	{
		return this.sendRequest(data, 'changeTaskResponsible');
	}

	removeSprint(data)
	{
		return this.sendRequest(data, 'removeSprint');
	}

	changeSprintName(data)
	{
		return this.sendRequest(data, 'changeSprintName');
	}

	changeSprintDeadline(data)
	{
		return this.sendRequest(data, 'changeSprintDeadline');
	}

	getEpicDescriptionEditor(data)
	{
		return this.sendRequest(data, 'getEpicDescriptionEditor');
	}

	getEpicDescription(data)
	{
		return this.sendRequest(data, 'getEpicDescription');
	}

	getEpicFiles(data)
	{
		return this.sendRequest(data, 'getEpicFiles');
	}

	getAddEpicFormButtons(data)
	{
		return this.sendRequest(data, 'getAddEpicFormButtons');
	}

	getViewEpicFormButtonsAction(data)
	{
		return this.sendRequest(data, 'getViewEpicFormButtons');
	}

	createEpic(data)
	{
		return this.sendRequest(data, 'createEpic');
	}

	getEpicsList(data)
	{
		return this.sendRequest(data, 'getEpicsList');
	}

	getEpicListUrl()
	{
		return '/bitrix/services/main/ajax.php?mode=class&c=bitrix:tasks.scrum&action=getEpicsList';
	}

	attachFilesToTask(data)
	{
		return this.sendRequest(data, 'attachFilesToTask');
	}

	attachTagToTask(data)
	{
		return this.sendRequest(data, 'attachTagToTask');
	}

	deAttachTagToTask(data)
	{
		return this.sendRequest(data, 'deAttachTagToTask');
	}

	attachEpicToItem(data)
	{
		return this.sendRequest(data, 'attachEpicToItem');
	}

	deAttachEpicToItem(data)
	{
		return this.sendRequest(data, 'deAttachEpicToItem');
	}

	getEpic(data)
	{
		return this.sendRequest(data, 'getEpic');
	}

	editEpic(data)
	{
		return this.sendRequest(data, 'editEpic');
	}

	removeEpic(data)
	{
		return this.sendRequest(data, 'removeEpic');
	}

	applyFilter(data)
	{
		return this.sendRequest(data, 'applyFilter');
	}
}