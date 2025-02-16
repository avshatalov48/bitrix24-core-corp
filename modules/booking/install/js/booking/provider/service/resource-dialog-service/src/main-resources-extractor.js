import type { ResourceDto } from 'booking.provider.service.resources-service';

export class MainResourcesExtractor
{
	#data: ResourceDto[];

	constructor(data: ResourceDto[])
	{
		this.#data = data;
	}

	getMainResourceIds(): number[]
	{
		return this.#data.map((resource: ResourceDto) => resource.id);
	}
}
