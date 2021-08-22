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

	sendRequestToComponent(data = {}, action): Promise
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
		return this.sendRequestToComponent(data, 'batchUpdateItem');
	}

	batchRemoveItem(data): Promise
	{
		return this.sendRequestToComponent(data, 'batchRemoveItem');
	}

	updateItemSort(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItemSort');
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
		return this.sendRequestToComponent(data, 'createTask');
	}

	updateItem(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItem');
	}

	removeItem(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeItem');
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

	getItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'getItems');
	}

	getEntityCounters(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEntityCounters');
	}

	getEpicDescriptionEditor(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEpicDescriptionEditor');
	}

	getEpicDescription(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEpicDescription');
	}

	getEpicFiles(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEpicFiles');
	}

	getAddEpicFormButtons(data): Promise
	{
		return this.sendRequestToComponent(data, 'getAddEpicFormButtons');
	}

	getViewEpicFormButtonsAction(data): Promise
	{
		return this.sendRequestToComponent(data, 'getViewEpicFormButtons');
	}

	createEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'createEpic');
	}

	getEpicsList(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEpicsList');
	}

	getEpicListUrl(): string
	{
		return '/bitrix/services/main/ajax.php?mode=class&c=bitrix:tasks.scrum&action=getEpicsList';
	}

	attachFilesToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'attachFilesToTask');
	}

	attachTagToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'attachTagToTask');
	}

	batchAttachTagToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'batchAttachTagToTask');
	}

	deAttachTagToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'deAttachTagToTask');
	}

	batchDeattachTagToTask(data): Promise
	{
		return this.sendRequestToComponent(data, 'batchDeattachTagToTask');
	}

	updateItemEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateItemEpic');
	}

	batchUpdateItemEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'batchUpdateItemEpic');
	}

	getEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'getEpic');
	}

	editEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'editEpic');
	}

	removeEpic(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeEpic');
	}

	applyFilter(data): Promise
	{
		return this.sendRequestToComponent(data, 'applyFilter');
	}

	getSprintStartButtons(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSprintStartButtons');
	}

	getSprintCompleteButtons(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSprintCompleteButtons');
	}

	getBurnDownChartData(data): Promise
	{
		return this.sendRequestToComponent(data, 'getBurnDownChartData');
	}

	getTeamSpeedChartData(data): Promise
	{
		return this.sendRequestToComponent(data, 'getTeamSpeedChartData');
	}

	getDodSettings(data): Promise
	{
		return this.sendRequestToComponent(data, 'getDodSettings');
	}

	getDodChecklist(data): Promise
	{
		return this.sendRequestToComponent(data, 'getDodChecklist');
	}

	saveDodSettings(data): Promise
	{
		return this.sendRequestToComponent(data, 'saveDodSettings');
	}

	updateBorderColorToLinkedItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'updateBorderColorToLinkedItems');
	}

	getAllUsedItemBorderColors(data): Promise
	{
		return this.sendRequestToComponent(data, 'getAllUsedItemBorderColors');
	}

	getSubTaskItems(data): Promise
	{
		return this.sendRequestToComponent(data, 'getSubTaskItems');
	}

	createType(data): Promise
	{
		return this.sendRequestToComponent(data, 'createType');
	}

	changeTypeName(data): Promise
	{
		return this.sendRequestToComponent(data, 'changeTypeName');
	}

	removeType(data): Promise
	{
		return this.sendRequestToComponent(data, 'removeType');
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