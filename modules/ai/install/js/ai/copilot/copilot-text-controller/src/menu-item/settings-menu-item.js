import { Extension, Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

import { BaseMenuItem } from '../../../src/copilot-menu/copilot-menu-item';
import type { BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';

export class SettingsMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		const settingsPageLink = Extension.getSettings('ai.copilot.copilot-text-controller').settingsPageLink;

		super({
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_SETTINGS'),
			icon: Main.SETTINGS,
			href: settingsPageLink,
			...options,
		});
	}
}
