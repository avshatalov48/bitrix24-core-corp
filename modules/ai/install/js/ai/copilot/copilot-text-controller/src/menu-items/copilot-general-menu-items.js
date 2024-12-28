import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';
import {
	GenerateWithRequiredUserMessageCommand,
	GenerateWithoutRequiredUserMessage,
	OpenFeedbackFormCommand,
	OpenImageConfigurator, OpenAboutCopilot,
} from '../menu-item-commands/index';
import { CopilotMenuItems } from './copilot-menu-items';
import type { CopilotMenuItem } from 'ai.copilot';
import type { EngineInfo } from '../types/engine-info';
import type { Prompt } from 'ai.engine';
import { Loc } from 'main.core';
import { CopilotProvidersMenuItems } from './copilot-providers-menu-items';
import { Main as MainIconSet, Main } from 'ui.icon-set.api.core';

type CopilotGeneralMenuItemsOptions = {
	engines: EngineInfo[],
	prompts: Prompt[],
	selectedEngineCode: string,
	canEditSettings: boolean,
	copilotTextController: CopilotTextController,
	addImageMenuItem: boolean;
	systemPrompts: Prompt[];
	userPrompts: Prompt[];
}

export class CopilotGeneralMenuItems extends CopilotMenuItems
{
	static getMenuItems(options: CopilotGeneralMenuItemsOptions): CopilotMenuItem[] {
		const {
			engines,
			selectedEngineCode,
			canEditSettings = false,
			copilotTextController,
			addImageMenuItem = false,
			userPrompts,
			systemPrompts,
			favouritePrompts,
		} = options;

		const favouriteSectionSeparator = favouritePrompts.length > 0
			? CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem()
			: null
		;

		const imageMenuItem = addImageMenuItem
			? [{
				code: 'image',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_IMAGE'),
				icon: Main.MAGIC_IMAGE,
				command: new OpenImageConfigurator({
					copilotTextController,
				}),
				labelText: Loc.getMessage('AI_COPILOT_MENU_ITEM_LABEL_NEW'),
			}] : [];

		return [
			...imageMenuItem,
			favouriteSectionSeparator,
			...getGeneralMenuItemsFromPrompts(favouritePrompts, copilotTextController, true),
			...(copilotTextController.isReadonly() === false ? [
				{
					code: 'user-prompt-separator',
					separator: true,
					title: Loc.getMessage('AI_COPILOT_USER_PROMPTS_MENU_SECTION'),
					text: Loc.getMessage('AI_COPILOT_USER_PROMPTS_MENU_SECTION'),
					isNew: true,
				},
				...getGeneralMenuItemsFromPrompts(userPrompts, copilotTextController, false),
				{
					code: 'promptLib',
					text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_PROMPT_LIB'),
					icon: Main.PROMPTS_LIBRARY,
					highlightText: true,
					command: async () => {
						if (BX.SidePanel)
						{
							copilotTextController.getAnalytics().setCategoryPromptSaving();
							copilotTextController.getAnalytics().sendEventOpenPromptLibrary();

							BX.SidePanel.Instance.open('/bitrix/components/bitrix/ai.prompt.library.grid/slider.php', {
								cacheable: false,
								events: {
									onCloseStart: () => {
										copilotTextController.getAnalytics().setCategoryText();
										copilotTextController.updateGeneralMenuPrompts();
									},
								},
							});
						}
						else
						{
							window.location.href = '/bitrix/components/bitrix/ai.prompt.library.grid/slider.php';
						}
					},
				},
			] : []),
			...getGeneralMenuItemsFromPrompts(systemPrompts, copilotTextController),
			...getSelectedEngineMenuItem(engines, selectedEngineCode, copilotTextController, canEditSettings),
			{
				code: 'about_open_copilot',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_ABOUT_COPILOT'),
				icon: MainIconSet.INFO,
				command: new OpenAboutCopilot(),
			},
			{
				code: 'feedback',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
				icon: Main.FEEDBACK,
				command: (new OpenFeedbackFormCommand({
					copilotTextController,
					category: copilotTextController.getCategory(),
					isBeforeGeneration: false,
				})),
			},
		].filter((item) => item);
	}

	static getMenuItem(
		prompt: Prompt,
		prompts: Prompt[],
		copilotTextController: CopilotTextController,
		isFavouriteSection: boolean = false,
	): CopilotMenuItem
	{
		let command = null;
		if (prompt.required)
		{
			command = prompt.type === 'simpleTemplate'
				? new GenerateWithRequiredUserMessageCommand({
					copilotTextController,
					commandCode: prompt.code,
				})
				: new GenerateWithoutRequiredUserMessage({
					copilotTextController,
					prompts,
					commandCode: copilotTextController.getMenuItemCodeFromPrompt(prompt.code),
				});
		}

		const code = isFavouriteSection
			? copilotTextController.getMenuItemCodeFromFavouritePrompt(prompt.code)
			: prompt.code
		;

		return {
			id: code,
			command,
			code: prompt.code,
			text: prompt.title,
			children: getGeneralMenuItemsFromPrompts(prompt.children || [], copilotTextController),
			separator: prompt.separator,
			title: prompt.title,
			icon: prompt.icon,
			section: prompt.section,
			isFavourite: copilotTextController.isReadonly() === true ? null : prompt.isFavorite,
			isShowFavouriteIconOnHover: isFavouriteSection && copilotTextController.isReadonly() === false,
		};
	}

	static getFavouritePromptsSeparatorMenuItem(): CopilotMenuItem
	{
		return {
			code: 'favourite-prompts-items-separator',
			separator: true,
			title: Loc.getMessage('AI_COPILOT_FAVOURITE_PROMPTS_MENU_SECTION'),
			text: Loc.getMessage('AI_COPILOT_FAVOURITE_PROMPTS_MENU_SECTION'),
		};
	}
}

function getGeneralMenuItemsFromPrompts(
	prompts: Prompt[],
	copilotTextController: CopilotTextController,
	isFavouriteSection: boolean = false,
): CopilotMenuItem[]
{
	return prompts.map((prompt: Prompt): CopilotMenuItem => {
		return CopilotGeneralMenuItems.getMenuItem(
			prompt,
			prompts,
			copilotTextController,
			isFavouriteSection,
		);
	}).filter((item) => item.code !== 'zero_prompt');
}

function getSelectedEngineMenuItem(
	engines: EngineInfo[],
	selectedEngineCode: string,
	copilotTextController: CopilotTextController,
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
			id: 'provider',
			code: 'provider',
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_OPEN_COPILOT'),
			children: CopilotProvidersMenuItems.getMenuItems({
				engines,
				selectedEngineCode,
				canEditSettings,
				copilotTextController,
			}),
			icon: Main.COPILOT_AI,
		},
	];
}
