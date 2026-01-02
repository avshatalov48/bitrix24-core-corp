import { crmFormService } from 'booking.provider.service.crm-form-service';

import type { ResourceModel } from 'booking.model.resources';

class ResourceStore
{
	#resourcesById: Map<number, ResourceModel> = new Map();

	async ensure(ids: number[]): Promise<void>
	{
		if (ids.length === 0)
		{
			return;
		}

		const needToLoadIds: number[] = ids.filter((id: number) => !this.#isLoaded(id));
		if (needToLoadIds.length === 0)
		{
			return;
		}

		const loaded: ResourceModel[] = await crmFormService.getResources(needToLoadIds);
		for (const resource: ResourceModel of loaded)
		{
			this.#resourcesById.set(resource.id, resource);
		}
	}

	#isLoaded(id: number): boolean
	{
		return this.#resourcesById.has(id);
	}

	getByIds(ids: number[]): ResourceModel[]
	{
		return ids
			.map((id: number): ResourceModel | undefined => this.#resourcesById.get(id))
			.filter(Boolean)
		;
	}

	getAll(): ResourceModel[]
	{
		return [...this.#resourcesById.values()];
	}
}

export const resourceStore: ResourceStore = new ResourceStore();
