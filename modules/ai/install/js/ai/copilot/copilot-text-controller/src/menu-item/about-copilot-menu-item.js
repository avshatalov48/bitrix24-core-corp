import { Loc, Reflection } from 'main.core';
import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { Main } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

export class AboutCopilotMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_ABOUT_COPILOT'),
			icon: Main.INFO,
			onClick: () => {
				const articleCode = '19092894';
				const Helper = Reflection.getClass('top.BX.Helper');

				if (Helper)
				{
					Helper.show(`redirect=detail&code=${articleCode}`);
				}
			},
			...options,
		});
	}
}
