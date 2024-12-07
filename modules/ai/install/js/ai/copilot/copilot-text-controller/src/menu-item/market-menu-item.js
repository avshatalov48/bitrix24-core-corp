import { Loc } from 'main.core';
import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { Main } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

export class MarketMenuItem extends BaseMenuItem
{
	constructor(options: BaseMenuItemOptions)
	{
		super({
			icon: Main.MARKET_1,
			text: Loc.getMessage('AI_COPILOT_SEARCH_IN_MARKET'),
			href: '/market/collection/ai_provider_partner_crm/',
			...options,
		});
	}
}
