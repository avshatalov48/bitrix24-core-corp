import { apiClient } from 'tasks.v2.lib.api-client';

export class OptionService
{
	async set(optionName: string, value: string): Promise<void>
	{
		await apiClient.post('Option.set', { optionName, value });
	}

	async setBool(optionName: string, value: boolean): Promise<void>
	{
		await apiClient.post('Option.setBool', { optionName, value });
	}
}

export const optionService = new OptionService();
