import {Loc, Type} from 'main.core';
import {MessageBox} from "ui.dialogs.messagebox";

type RequestDataListComponent = {
	groupId: number
}

type RequestDataSaveList = {
	taskId: number,
	items: Array
}

type RequestDataListOptions = {
	groupId: number
}

export class RequestSender
{
	constructor()
	{
		this.BX = window.top.BX;
	}

	sendRequest(action: string, data = {}): Promise
	{
		return new Promise((resolve, reject) => {
			this.BX.ajax.runAction('bitrix:tasks.scrum.service.definitionOfDoneService.' + action, {
				data: data
			}).then(resolve, reject);
		});
	}

	getListComponent(data: RequestDataListComponent): Promise
	{
		return this.sendRequest('getItemComponent', data);
	}

	getListOptions(data: RequestDataListOptions): Promise
	{
		return this.sendRequest('getListOptions', data);
	}

	getListButtons(): Promise
	{
		return this.sendRequest('getTaskCompleteButtons');
	}

	saveList(data: RequestDataSaveList): Promise
	{
		return this.sendRequest('saveList', data);
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