import { CopilotCommands } from '../copilot-commands';
import { CopilotMenuItems } from './copilot-menu-items';
import type { CopilotMenuItem } from '../copilot-menu';
import type { EngineInfo } from '../types/engine-info';
import type { Prompt } from '../types/prompt';
import { Loc } from 'main.core';
import { CopilotProvidersMenuItems } from './copilot-providers-menu-items';
import { Main } from 'ui.icon-set.api.core';

type CopilotGeneralMenuItemsOptions = {
	engines: EngineInfo[],
	prompts: Prompt[],
	selectedEngineCode: string,
	canEditSettings: boolean
}
export class CopilotGeneralMenuItems extends CopilotMenuItems
{
	static getMenuItems(options: CopilotGeneralMenuItemsOptions): CopilotMenuItem[] {
		const {
			prompts,
			engines,
			selectedEngineCode,
			canEditSettings = false,
		} = options;

		return [
			...getGeneralMenuItemsFromPrompts(prompts),
			...getSelectedEngineMenuItem(engines, selectedEngineCode, canEditSettings),
			{
				code: CopilotCommands.feedback,
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
				icon: Main.FEEDBACK,
			},
		];
	}
}

function getGeneralMenuItemsFromPrompts(prompts): CopilotMenuItem[]
{
	return prompts.map((prompt) => {
		return {
			code: prompt.code,
			text: prompt.title,
			children: getGeneralMenuItemsFromPrompts(prompt.children || []),
			separator: prompt.separator,
			title: prompt.title,
			icon: prompt.icon,
			section: prompt.section,
		};
	}).filter((item) => item.code !== 'zero_prompt');
}

function getSelectedEngineMenuItem(
	engines: EngineInfo[],
	selectedEngineCode: string,
	canEditSettings: boolean = false,
): CopilotMenuItem[]
{
	return [
		{
			separator: true,
			title: Loc.getMessage('AI_COPILOT_PROVIDER_MENU_SECTION'),
			text: Loc.getMessage('AI_COPILOT_PROVIDER_MENU_SECTION'),
		},
		{
			code: 'provider',
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_OPEN_COPILOT'),
			children: CopilotProvidersMenuItems.getMenuItems({
				engines,
				selectedEngineCode,
				canEditSettings,
			}),
			icon: Main.COPILOT_AI,
		},
	];
}
