import {Loc, Type} from 'main.core';

type RequestParams = {
	groupId: number,
	taskId: number,
	typeId?: number
}

type ErrorResponse = {
	data: string,
	errors: Array,
	status: string
}

export class RequestSender
{
	sendRequest(controller: string, action: string, data = {}): Promise
	{
		return new Promise((resolve, reject) => {
			top.BX.ajax.runAction(
				'bitrix:tasks.scrum.' + controller + '.' + action,
				{
					data: data
				}
			).then(resolve, reject);
		});
	}

	getSettings(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'getSettings', data);
	}

	getChecklist(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'getChecklist', data);
	}

	saveSettings(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'saveSettings', data);
	}

	getList(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'getList', data);
	}

	saveList(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'saveList', data);
	}

	createType(data): Promise
	{
		return this.sendRequest('type', 'createType', data);
	}

	changeTypeName(data): Promise
	{
		return this.sendRequest('type', 'changeTypeName', data);
	}

	removeType(data): Promise
	{
		return this.sendRequest('type', 'removeType', data);
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
				const title = (alertTitle ? alertTitle : Loc.getMessage('TSD_ERROR_POPUP_TITLE'));

				top.BX.UI.Dialogs.MessageBox.alert(message, title);
			}
		}
	}
}