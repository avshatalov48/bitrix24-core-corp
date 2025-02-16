class ResourcesDateCache
{
	#cache: { [dateTs: number]: number[] } = {};

	upsertIds(dateTs: number, ids: number[]): void
	{
		const currentIds = this.getIdsByDateTs(dateTs);
		const newIds = ids.filter((id: number) => !currentIds.includes(id));

		this.#cache[dateTs].push(...newIds);
	}

	isDateLoaded(dateTs: number, ids: number[]): boolean
	{
		const loadedResourcesIds = this.getIdsByDateTs(dateTs);

		return ids.every((id: number) => loadedResourcesIds.includes(id));
	}

	getIdsByDateTs(dateTs: number): number[]
	{
		this.#cache[dateTs] ??= [];

		return this.#cache[dateTs];
	}
}

export const resourcesDateCache = new ResourcesDateCache();
