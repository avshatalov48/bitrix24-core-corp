import type { Store } from 'ui.vue3.vuex';

import { Model } from 'tasks.v2.const';
import { Core } from 'tasks.v2.core';
import { apiClient } from 'tasks.v2.lib.api-client';
import type { CrmItemModel } from 'tasks.v2.model.crm-items';

import { mapDtoToModel } from './mappers';
import type { CrmItemDto } from './types';

export const crmService = new class
{
	async list(id: number, crmItemIds: string[]): Promise<void>
	{
		const data = await apiClient.post('Task.CRM.Item.list', { task: { id, crmItemIds } });

		const crmItems = data.map((dto: CrmItemDto): CrmItemModel => mapDtoToModel(dto));

		await this.$store.dispatch(`${Model.CrmItems}/upsertMany`, crmItems);
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}();
