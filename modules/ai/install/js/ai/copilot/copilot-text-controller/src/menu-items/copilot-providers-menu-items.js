import { Extension, Loc } from 'main.core';
import type { CopilotMenuItem } from 'ai.copilot';
import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';
import { Main as MainIconSet, Actions as ActionsIconSet } from 'ui.icon-set.api.core';
import { SetEngineCommand } from '../menu-item-commands/index';
import type { EngineInfo } from '../types/engine-info';

type CopilotProvidersMenuItemsOptions = {
	selectedEngineCode: string;
	engines: EngineInfo[];
	canEditSettings?: boolean;
	copilotTextController: CopilotTextController;
}

export class CopilotProvidersMenuItems
{
	static getMenuItems(options: CopilotProvidersMenuItemsOptions): CopilotMenuItem[]
	{
		const { engines, selectedEngineCode, canEditSettings = false, copilotTextController } = options;

		const connectAiMenuItem: CopilotMenuItem = {
			text: Loc.getMessage('AI_COPILOT_COMMAND_CONNECT_AI'),
			disabled: true,
			icon: ActionsIconSet.PLUS_50,
		};

		let result = [
			...getMenuItemsFromEngines(engines, selectedEngineCode, copilotTextController),
			connectAiMenuItem,
			{ separator: true },
			getMarketMenuItem(),
		];

		if (canEditSettings)
		{
			const settingsPageLink = Extension.getSettings('ai.copilot.copilot-text-controller').settingsPageLink;

			result = [...result, {
				code: 'ai_settings',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_SETTINGS'),
				icon: ActionsIconSet.SETTINGS_4,
				href: settingsPageLink,
			}];
		}

		return result;
	}
}

function getMenuItemsFromEngines(
	engines: EngineInfo[],
	selectedEngineCode: string,
	copilotTextController: CopilotTextController,
): CopilotMenuItem[]
{
	return engines.map((engine): CopilotMenuItem => {
		return {
			code: engine.code,
			text: engine.title,
			icon: MainIconSet.ROBOT,
			selected: selectedEngineCode === engine.code,
			command: new SetEngineCommand({
				engines,
				copilotTextController,
				engineCode: engine.code,
			}),
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
