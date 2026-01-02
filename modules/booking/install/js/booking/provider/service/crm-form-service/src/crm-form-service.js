import { ajax } from 'main.core';

import { ApiClient } from 'booking.lib.api-client';
import type { CrmFormResourceModel } from 'booking.model.crm-form';

import { mapDtoToModel } from './mapper';
import { ResourceDto } from './types';

class CrmFormService
{
	#getResourcesRequest: ?Promise<ResourceDto[]>;

	async getResources(ids: number[]): Promise<CrmFormResourceModel[]>
	{
		try
		{
			this.#getResourcesRequest ??= this.#fetchGetResources;
			const { data } = await this.#getResourcesRequest(ids);

			return data.map((resourceDto) => mapDtoToModel(resourceDto));
		}
		catch (error)
		{
			console.error('CrmFormService: get resources error', error);

			return [];
		}
	}

	#fetchGetResources(ids: number[]): Promise<ResourceDto[]>
	{
		const action: string = new ApiClient().buildUrl('CrmForm.getResources');

		return ajax.runAction(action, {
			data: {
				ids,
			},
		});
	}
}

export const crmFormService = new CrmFormService();
