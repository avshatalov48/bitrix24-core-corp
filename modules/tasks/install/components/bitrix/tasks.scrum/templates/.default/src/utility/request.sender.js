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

	sendRequest(data = {}, action): Promise
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

	batchUpdateItem(data): Promise
	{
		return this.sendRequest(data, 'batchUpdateItem');
	}

	batchRemoveItem(data): Promise
	{
		return this.sendRequest(data, 'batchRemoveItem');
	}

	updateItemSort(data): Promise
	{
		return this.sendRequest(data, 'updateItemSort');
	}

	updateSprintSort(data): Promise
	{
		return this.sendRequest(data, 'updateSprintSort');
	}

	createSprint(data): Promise
	{
		return this.sendRequest(data, 'createSprint');
	}

	startSprint(data): Promise
	{
		return this.sendRequest(data, 'startSprint');
	}

	completeSprint(data): Promise
	{
		return this.sendRequest(data, 'completeSprint');
	}

	createTask(data): Promise
	{
		return this.sendRequest(data, 'createTask');
	}

	updateItem(data): Promise
	{
		return this.sendRequest(data, 'updateItem');
	}

	removeItem(data): Promise
	{
		return this.sendRequest(data, 'removeItem');
	}

	changeTaskResponsible(data): Promise
	{
		return this.sendRequest(data, 'changeTaskResponsible');
	}

	removeSprint(data): Promise
	{
		return this.sendRequest(data, 'removeSprint');
	}

	changeSprintName(data): Promise
	{
		return this.sendRequest(data, 'changeSprintName');
	}

	changeSprintDeadline(data): Promise
	{
		return this.sendRequest(data, 'changeSprintDeadline');
	}

	getSprintCompletedItems(data): Promise
	{
		return this.sendRequest(data, 'getSprintCompletedItems');
	}

	getEpicDescriptionEditor(data): Promise
	{
		return this.sendRequest(data, 'getEpicDescriptionEditor');
	}

	getEpicDescription(data): Promise
	{
		return this.sendRequest(data, 'getEpicDescription');
	}

	getEpicFiles(data): Promise
	{
		return this.sendRequest(data, 'getEpicFiles');
	}

	getAddEpicFormButtons(data): Promise
	{
		return this.sendRequest(data, 'getAddEpicFormButtons');
	}

	getViewEpicFormButtonsAction(data): Promise
	{
		return this.sendRequest(data, 'getViewEpicFormButtons');
	}

	createEpic(data): Promise
	{
		return this.sendRequest(data, 'createEpic');
	}

	getEpicsList(data): Promise
	{
		return this.sendRequest(data, 'getEpicsList');
	}

	getEpicListUrl(): string
	{
		return '/bitrix/services/main/ajax.php?mode=class&c=bitrix:tasks.scrum&action=getEpicsList';
	}

	attachFilesToTask(data): Promise
	{
		return this.sendRequest(data, 'attachFilesToTask');
	}

	attachTagToTask(data): Promise
	{
		return this.sendRequest(data, 'attachTagToTask');
	}

	batchAttachTagToTask(data): Promise
	{
		return this.sendRequest(data, 'batchAttachTagToTask');
	}

	deAttachTagToTask(data): Promise
	{
		return this.sendRequest(data, 'deAttachTagToTask');
	}

	batchDeattachTagToTask(data): Promise
	{
		return this.sendRequest(data, 'batchDeattachTagToTask');
	}

	updateItemEpic(data): Promise
	{
		return this.sendRequest(data, 'updateItemEpic');
	}

	batchUpdateItemEpic(data): Promise
	{
		return this.sendRequest(data, 'batchUpdateItemEpic');
	}

	getEpic(data): Promise
	{
		return this.sendRequest(data, 'getEpic');
	}

	editEpic(data): Promise
	{
		return this.sendRequest(data, 'editEpic');
	}

	removeEpic(data): Promise
	{
		return this.sendRequest(data, 'removeEpic');
	}

	applyFilter(data): Promise
	{
		return this.sendRequest(data, 'applyFilter');
	}

	getSprintStartButtons(data): Promise
	{
		return this.sendRequest(data, 'getSprintStartButtons');
	}

	getSprintCompleteButtons(data): Promise
	{
		return this.sendRequest(data, 'getSprintCompleteButtons');
	}

	getBurnDownChartData(data): Promise
	{
		return this.sendRequest(data, 'getBurnDownChartData');
	}

	getTeamSpeedChartData(data): Promise
	{
		return this.sendRequest(data, 'getTeamSpeedChartData');
	}

	getDodPanelData(data): Promise
	{
		return this.sendRequest(data, 'getDodPanelData');
	}

	getDodComponent(data): Promise
	{
		return this.sendRequest(data, 'getDodComponent');
	}

	getDodButtons(data): Promise
	{
		return this.sendRequest(data, 'getDodButtons');
	}

	saveDod(data): Promise
	{
		return this.sendRequest(data, 'saveDod');
	}

	showErrorAlert(response: ErrorResponse, alertTitle?: string)
	{
		if (Type.isUndefined(response.errors))
		{
			console.log(response);
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