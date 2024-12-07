import { Reflection } from 'main.core';
import './tour.css';
import 'ui.design-tokens';
import { BaseTour } from '../tools/base-tour';

const UserOptions = Reflection.namespace('BX.userOptions');

/** @memberof BX.Crm.Timeline.MenuBar.ToDo */
export default class Tour extends BaseTour
{
	saveUserOption(optionName: ?string = null): void
	{
		UserOptions.save('crm', 'todo', 'isTimelineTourViewedInWeb', 1);
	}
}
