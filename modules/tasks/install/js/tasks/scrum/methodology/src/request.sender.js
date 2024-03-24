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

	getEpicInfo(data: RequestParams): Promise
	{
		return this.sendRequest('epic', 'getEpicInfo', data);
	}

	getDodInfo(data: RequestParams): Promise
	{
		return this.sendRequest('dod', 'getDodInfo', data);
	}

	getTeamSpeedInfo(data: RequestParams): Promise
	{
		return this.sendRequest('sprint', 'getTeamSpeedInfo', data);
	}

	getTutorInfo(data: RequestParams): Promise
	{
		return this.sendRequest('info', 'getTutorInfo', data);
	}

	getBurnDownInfo(data: RequestParams): Promise
	{
		return this.sendRequest('sprint', 'getBurnDownInfo', data);
	}

	getMarketPath(): Promise
	{
		return this.sendRequest('info', 'getMarketPath');
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
				const title = (alertTitle ? alertTitle : Loc.getMessage('TSM_ERROR_POPUP_TITLE'));

				MessageBox.alert(message, title);
			}
		}
	}
}