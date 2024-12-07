import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

import { BaseMenuItem } from '../../../src/copilot-menu/copilot-menu-item';
import type { BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';

export class OpenCopilotMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			id: 'open-copilot',
			code: 'open-copilot',
			icon: Main.COPILOT_AI,
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_OPEN_COPILOT'),
			...options,
		});
	}
}
