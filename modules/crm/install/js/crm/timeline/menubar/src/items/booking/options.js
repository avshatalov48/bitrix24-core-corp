import { Reflection } from 'main.core';

const UserOptions = Reflection.namespace('BX.userOptions');

export default class Options
{
	static BOOKING_ADS_OPTION = 'booking_ads';
	static START_BANNER = 'start_banner';
	static BEFORE_FIRST_RESOURCE_AHA_OPTION_NAME = 'before_first_resource';
	static AFTER_FIRST_RESOURCE_AHA_OPTION_NAME = 'after_first_resource';

	saveUserOption(optionName: string)
	{
		this.#checkOption(optionName);
		UserOptions.save('crm', Options.BOOKING_ADS_OPTION, optionName, 1);
	}

	#checkOption(optionName: string)
	{
		if (![
			Options.START_BANNER,
			Options.BEFORE_FIRST_RESOURCE_AHA_OPTION_NAME,
			Options.AFTER_FIRST_RESOURCE_AHA_OPTION_NAME,
		].includes(optionName))
		{
			throw new Error(`User option with name: ${optionName} unsupported`);
		}
	}
}
