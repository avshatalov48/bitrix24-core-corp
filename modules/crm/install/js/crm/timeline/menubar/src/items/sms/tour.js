import { Reflection } from 'main.core';

import { BaseTour } from '../tools/base-tour';

const UserOptions = Reflection.namespace('BX.userOptions');

/** @memberof BX.Crm.Timeline.MenuBar.Whatsapp */
export default class Tour extends BaseTour
{
	static USER_OPTION_PROVIDER_OFF: string = 'is_tour_provider_off_viewed';
	static USER_OPTION_TEMPLATES_READY: string = 'is_tour_templates_ready_viewed';
	static USER_OPTION_PROVIDER_ON: string = 'is_tour_provider_on_viewed';

	/**
	 * @override
	 * */
	saveUserOption(optionName: ?string = null): void
	{
		if (![
			Tour.USER_OPTION_PROVIDER_OFF,
			Tour.USER_OPTION_TEMPLATES_READY,
			Tour.USER_OPTION_PROVIDER_ON,
		].includes(optionName))
		{
			throw new Error(`User option with name: ${optionName} unsupported`);
		}

		UserOptions.save('crm', 'whatsapp', optionName, 1);
	}
}
