import { Loc } from 'main.core';
import { Actions } from 'ui.icon-set.api.core';
import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';

export class ConnectModelMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			icon: Actions.PLUS_50,
			text: Loc.getMessage('AI_COPILOT_COMMAND_CONNECT_AI'),
			disabled: true,
			...options,
		});
	}
}
