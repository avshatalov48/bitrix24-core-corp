import { UI } from 'ui.notification';
import { ajax, Text } from 'main.core';

type ApiOptions = {
	endpoint: string;
	token: string;
};

type MapperConfig = {
	items: Array,
	countMappedPersons: number,
	countUnmappedPersons: number,
	isHideInfoAlert: boolean,
	mappedUserIds: Array,
};

export type {
	ApiOptions,
};

export class Api
{
	// eslint-disable-next-line no-unused-private-class-members
	#options: ApiOptions;

	constructor(options: ApiOptions)
	{
		this.#options = options;
	}

	saveMapping(data)
	{
		return this.#post('humanresources.HcmLink.Mapper.save', data, true);
	}

	loadMapperConfig(data): Promise<MapperConfig>
	{
		return this.#post('humanresources.HcmLink.Mapper.load', data, true);
	}

	getJobStatus(data): Promise<{status?: string, jobId?: number}>
	{
		return this.#post('humanresources.HcmLink.Mapper.getJobStatus', data, true);
	}

	loadCompanyConfig(data)
	{
		return this.#post('humanresources.HcmLink.Company.Config.load', data, true);
	}

	closeInfoAlert()
	{
		return this.#post('humanresources.HcmLink.Mapper.closeInfoAlert');
	}

	removeLinkMapped(data)
	{
		return this.#post('humanresources.HcmLink.Mapper.delete', data, true);
	}

	createUpdateEmployeeListJob(data)
	{
		return this.#post('humanresources.HcmLink.Mapper.start', data, true);
	}

	createCompleteMappingEmployeeListJob(data)
	{
		return this.#post('humanresources.HcmLink.Mapper.end', data, true);
	}

	#get(endpoint: string, displayErrors: boolean = true): Promise
	{
		return this.#request('GET', endpoint, null, displayErrors);
	}

	#post(endpoint: string, data: Object = null, displayErrors: boolean = true): Promise
	{
		return this.#request('POST', endpoint, data, displayErrors);
	}

	async #request(method: string, endpoint: string, data: Object = {}, displayError: boolean = true): Promise
	{
		const config = { method };
		if (method === 'POST')
		{
			Object.assign(config, { data }, {
				preparePost: false,
				headers: [{
					name: 'Content-Type',
					value: 'application/json',
				}],
			});
		}

		try
		{
			const response = await ajax.runAction(endpoint, config);
			if (response.errors?.length > 0)
			{
				throw new Error(response.errors[0].message);
			}

			return response.data;
		}
		catch (ex)
		{
			const { message = `Error in ${endpoint}`, errors = [] } = ex;
			const content = errors[0]?.message ?? message;
			UI.Notification.Center.notify({
				content: Text.encode(content),
				autoHideDelay: 4000,
			});

			throw ex;
		}
	}
}
