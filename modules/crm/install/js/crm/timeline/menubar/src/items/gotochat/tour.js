import { Reflection } from 'main.core';

import { BaseTour } from '../tools/base-tour';

const UserOptions = Reflection.namespace('BX.userOptions');

/** @memberof BX.Crm.Timeline.MenuBar.GoToChat */
export default class Tour extends BaseTour
{
	/**
	 * @override
	 * */
	saveUserOption(optionName: ?string = null): void
	{
		UserOptions.save('crm', 'gotochat', 'isTimelineTourViewedInWeb', 1);
	}
}
