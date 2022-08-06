import {Loc, Type, ajax} from 'main.core';
import {MessageBox} from 'ui.dialogs.messagebox';

type ErrorResponse = {
	data: string,
	errors: Array,
	status: string
}

export class RequestSender
{
	sendRequest(controller: string, action: string, data = {}, analyticsLabel = {}): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'bitrix:tasks.scrum.' + controller + '.' + action,
				{
					data: data,
					analyticsLabel: analyticsLabel
				}
			).then(resolve, reject);
		});
	}

	getDataForSprintStartForm(data): Promise
	{
		return this.sendRequest('sprint', 'getDataForSprintStartForm', data);
	}

	startSprint(data): Promise
	{
		return this.sendRequest(
			'sprint',
			'startSprint',
			data,
			{
				scrum: 'Y',
				action: 'sprint_start',
			}
		);
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
				const title = (alertTitle ? alertTitle : Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));

				MessageBox.alert(message, title);
			}
		}
	}
}