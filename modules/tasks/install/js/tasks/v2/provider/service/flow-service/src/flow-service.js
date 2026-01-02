import { Model } from 'tasks.v2.const';
import { Core } from 'tasks.v2.core';
import { apiClient } from 'tasks.v2.lib.api-client';
import { mapDtoToModel } from './mappers';

class FlowService
{
	getUrl(id: number, userId: number): string
	{
		return `/company/personal/user/${userId}/tasks/flow/?ID_numsel=exact&ID_from=${id}&ID_to=${id}&apply_filter=Y`;
	}

	async getFlow(id: number): Promise<void>
	{
		try
		{
			const data = await apiClient.post('Flow.get', { flow: { id } });

			// TODO: insert group
			console.log(data);

			const flow = mapDtoToModel(data);

			await Core.getStore().dispatch(`${Model.Flows}/insert`, flow);
		}
		catch (error)
		{
			console.error('FlowService: getFlow error', error);
		}
	}
}

export const flowService = new FlowService();
