import { ApiClient } from 'booking.lib.api-client';

class OptionService
{
	async setBool(optionName: string, value: boolean): Promise<void>
	{
		await new ApiClient().post('Option.setBool', {
			optionName,
			value,
		});
	}
}

export const optionService = new OptionService();
