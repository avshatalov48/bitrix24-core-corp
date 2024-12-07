import type { CopilotMenuItem } from '../copilot-menu';
import { Main as MainIconSet, Actions as ActionsIconSet } from 'ui.icon-set.api.core';
import { Loc } from 'main.core';
import type { EngineInfo } from '../types/engine-info';

type CopilotProvidersMenuItemsOptions = {
	selectedEngineCode: string;
	engines: EngineInfo[];
	canEditSettings?: boolean;
}
export class CopilotProvidersMenuItems
{
	static getMenuItems(options: CopilotProvidersMenuItemsOptions): CopilotMenuItem[]
	{
		const { engines, selectedEngineCode, canEditSettings = false } = options;

		const connectAiMenuItem: CopilotMenuItem = {
			text: Loc.getMessage('AI_COPILOT_COMMAND_CONNECT_AI'),
			disabled: true,
			icon: ActionsIconSet.PLUS_50,
		};

		let result = [
			...getMenuItemsFromEngines(engines, selectedEngineCode),
			connectAiMenuItem,
			{ separator: true },
			{
				code: 'about_open_copilot',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_ABOUT_COPILOT'),
				icon: MainIconSet.INFO,
			},
			getMarketMenuItem(),
		];

		if (canEditSettings)
		{
			result = [...result, {
				code: 'ai_settings',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_SETTINGS'),
				icon: ActionsIconSet.SETTINGS_4,
				href: '/settings/configs/?page=ai',
			}];
		}

		return result;
	}
}

function getMenuItemsFromEngines(engines: EngineInfo[], selectedEngineCode: string): CopilotMenuItem[]
{
	return engines.map((engine): CopilotMenuItem => {
		return {
			code: engine.code,
			text: engine.title,
			icon: MainIconSet.ROBOT,
			selected: selectedEngineCode === engine.code,
		};
	});
}

function getMarketMenuItem(): CopilotMenuItem
{
	return {
		code: 'market',
		href: '/market/collection/ai_provider_partner_crm/',
		text: Loc.getMessage('AI_COPILOT_SEARCH_IN_MARKET'),
		icon: MainIconSet.MARKET_1,
		arrow: false,
	};
}
