import type { CopilotInput, CopilotMenu, CopilotMenuItem } from 'ai.copilot';
import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';
import { UI } from 'ui.notification';
import {
	AddBelowCommand,
	CancelCommand,
	EditResultCommand,
	RepeatGenerateCommand,
	OpenFeedbackFormCommand,
	SaveCommand,
	CloseCommand,
} from '../menu-item-commands/index';

import { CopilotMenuItems } from './copilot-menu-items';
import { Loc } from 'main.core';
import { Actions, Main } from 'ui.icon-set.api.core';

type CopilotResultMenuItemsOptions = {
	prompts: Prompt[];
	selectedText: string;
	resultMenu?: CopilotMenu;
	inputField: CopilotInput;
	errorMenu?: CopilotMenu;
	generalMenu?: CopilotMenu;
	copilotTextController: CopilotTextController;
	copilotContainer: HTMLElement,
}

export class CopilotResultMenuItems extends CopilotMenuItems
{
	static getMenuItems(options: CopilotResultMenuItemsOptions, category: string): CopilotMenuItem[]
	{
		const {
			prompts,
			selectedText,
			copilotContainer = null,
		} = options;

		const inputField = options.inputField ?? null;
		const copilotTextController = options.copilotTextController ?? null;

		const saveMenuItemText = selectedText ? 'AI_COPILOT_COMMAND_REPLACE' : 'AI_COPILOT_COMMAND_SAVE';
		const saveMenuItem: CopilotMenuItem = {
			text: Loc.getMessage(saveMenuItemText),
			code: 'save',
			icon: 'check',
			command: new SaveCommand({
				copilotTextController,
			}),
			notHighlight: true,
		};

		const promptMasterMenuItem = (
			copilotTextController.getLastCommandCode() === 'zero_prompt'
			&& copilotTextController.isReadonly() === false
			&& copilotTextController.getSelectedPromptCodeWithSimpleTemplate() === null)
			? {
				code: 'prompt-master',
				text: Loc.getMessage('AI_COPILOT_MENU_ITEM_CREATE_PROMPT'),
				icon: Main.BOOKMARK_1,
				notHighlight: true,
				command: async () => {
					await copilotTextController.showPromptMasterPopup();
				},
			}
			: null;

		const editMenuItem: CopilotMenuItem = {
			text: Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
			code: 'edit',
			icon: 'pencil-60',
			command: new EditResultCommand({
				inputField,
				copilotTextController,
				copilotContainer,
			}),
			notHighlight: true,
		};

		const addBelowMenuItem: CopilotMenuItem | null = selectedText
			? {
				text: Loc.getMessage('AI_COPILOT_COMMAND_ADD_BELOW'),
				code: 'add_below',
				icon: 'download',
				command: new AddBelowCommand({
					copilotTextController,
				}),
				notHighlight: true,
			}
			: null;

		return [
			promptMasterMenuItem,
			{
				separator: true,
			},
			saveMenuItem,
			addBelowMenuItem,
			editMenuItem,
			...getResultMenuPromptItems(prompts),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
				code: 'repeat',
				icon: 'left-semicircular-anticlockwise-arrow-1',
				command: new RepeatGenerateCommand({
					copilotTextController,
				}),
				notHighlight: true,
			},
			{
				separator: true,
			},
			getFeedbackMenuItem(category, copilotTextController),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
				code: 'cancel',
				icon: 'cross-45',
				notHighlight: true,
				command: new CancelCommand({
					inputField,
					copilotTextController,
				}),
			},
		].filter((item) => item);
	}

	static getMenuItemsForReadonlyResult(
		category: string,
		copilotTextController: CopilotTextController,
		inputField: CopilotInput,
		copilotContainer,
	): CopilotMenuItem[]
	{
		return [
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_COPY'),
				code: 'copy',
				icon: Actions.COPY_PLATES,
				notHighlight: true,
				command: {
					execute()
					{
						BX.clipboard.copy(copilotTextController.getAiResultText());
						UI.Notification.Center.notify({
							content: Loc.getMessage('AI_COPILOT_TEXT_IS_COPIED'),
						});
						copilotTextController.getAnalytics().sendEventCopyResult();
					},
				},
			},
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
				code: 'cancel',
				icon: Actions.PENCIL_60,
				command: new CancelCommand({
					inputField,
					copilotTextController,
					copilotContainer,
				}),
				notHighlight: true,
			},
			{
				separator: true,
			},
			getFeedbackMenuItem(category, copilotTextController),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_CLOSE'),
				code: 'close',
				icon: Actions.CROSS_45,
				notHighlight: true,
				command: new CloseCommand({
					copilotTextController,
				}),
			},
		];
	}
}

function getResultMenuPromptItems(prompts: Prompt[]): CopilotMenuItem[]
{
	const workWithResultPrompts = prompts.filter((prompt: Prompt) => {
		return prompt.workWithResult;
	});

	return workWithResultPrompts.map((prompt) => {
		return {
			text: prompt.title,
			code: prompt.code,
			icon: prompt.icon,
		};
	});
}

function getFeedbackMenuItem(category: string, copilotTextController: CopilotTextController): CopilotMenuItem
{
	return {
		code: 'feedback',
		text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
		icon: Main.FEEDBACK,
		notHighlight: true,
		command: new OpenFeedbackFormCommand({
			category,
			isBeforeGeneration: false,
			copilotTextController,
		}),
	};
}
