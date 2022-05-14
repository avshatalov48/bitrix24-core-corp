import {Loc, Type} from 'main.core';

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
			top.BX.ajax.runAction(
				'bitrix:tasks.scrum.' + controller + '.' + action,
				{
					data: data,
					analyticsLabel: analyticsLabel
				}
			).then(resolve, reject);
		});
	}

	getDataForSprintCompletionForm(data): Promise
	{
		return this.sendRequest('sprint', 'getDataForSprintCompletionForm', data);
	}

	completeSprint(data): Promise
	{
		return this.sendRequest(
			'sprint',
			'completeSprint',
			data,
			{
				scrum: 'Y',
				action: 'finish_sprint',
			}
		);
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

				top.BX.UI.Dialogs.MessageBox.alert(message, title);
			}
		}
	}
}