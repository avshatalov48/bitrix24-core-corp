import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';

import { BaseMenuItem } from '../../../src/copilot-menu/copilot-menu-item';
import type { BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';

export class ChangeRequestMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			text: Loc.getMessage('AI_COPILOT_READONLY_COMMAND_EDIT'),
			icon: Main.EDIT_PENCIL,
			...options,
		});
	}
}
