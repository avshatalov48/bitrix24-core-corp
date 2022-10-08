import {Loc, Type, ajax} from 'main.core';

import {MessageBox} from 'ui.dialogs.messagebox';

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

	needUpdateTask(data): Promise
	{
		return this.sendRequest('task', 'needUpdateTaskStatus', data);
	}

	getTasks(data): Promise
	{
		return this.sendRequest('task', 'getTasks', data);
	}

	completeTask(data): Promise
	{
		return this.sendRequest('task', 'completeTask', data);
	}

	renewTask(data): Promise
	{
		return this.sendRequest('task', 'renewTask', data);
	}

	proceedParentTask(data): Promise
	{
		return this.sendRequest('task', 'proceedParentTask', data);
	}

	isParentScrumTask(data): Promise
	{
		return this.sendRequest('task', 'isParentScrumTask', data);
	}

	getData(data): Promise
	{
		return this.sendRequest('task', 'getData', data);
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
				const title = (alertTitle ? alertTitle : Loc.getMessage('TST_ERROR_POPUP_TITLE'));

				MessageBox.alert(message, title);
			}
		}
	}
}