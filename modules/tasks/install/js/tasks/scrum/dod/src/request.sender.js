import {ajax} from 'main.core';

type RequestParams = {
	groupId: number,
	taskId: number,
	typeId?: number
}

export class RequestSender
{
	sendRequest(action: string, data = {}): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('bitrix:tasks.scrum.service.definitionOfDoneService.' + action, {
				data: data
			}).then(resolve, reject);
		});
	}

	getSettings(data: RequestParams): Promise
	{
		return this.sendRequest('getSettings', data);
	}

	getList(data: RequestParams): Promise
	{
		return this.sendRequest('getList', data);
	}

	saveList(data: RequestParams): Promise
	{
		return this.sendRequest('saveList', data);
	}
}