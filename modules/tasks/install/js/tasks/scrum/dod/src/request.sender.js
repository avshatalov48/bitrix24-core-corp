import {Loc, Type, ajax} from 'main.core';
import {MessageBox} from 'ui.dialogs.messagebox';

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
			ajax.runAction(
				'bitrix:tasks.scrum.' + controller + '.' + action,
				{
					data: data
				}
			).then(resolve, reject);
		});
	}

	isNecessary(data: RequestParams): Promise
	{
		return this.sendRequest('doD', 'isNecessary', data);
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
			console.error(response);

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

				MessageBox.alert(message, title);
			}
		}
	}
}