import { Loc } from 'main.core';
import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { Actions } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

export class CancelCopilotMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			icon: Actions.CROSS_45,
			text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
			...options,
		});
	}
}
