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
				const title = (alertTitle ? alertTitle : Loc.getMessage('TSM_ERROR_POPUP_TITLE'));

				top.BX.UI.Dialogs.MessageBox.alert(message, title);
			}
		}
	}
}