import { Loc } from 'main.core';
import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { Actions } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';
export class RepeatCopilotMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			icon: Actions.LEFT_SEMICIRCULAR_ANTICLOCKWISE_ARROW_1,
			text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
			...options,
		});
	}
}
