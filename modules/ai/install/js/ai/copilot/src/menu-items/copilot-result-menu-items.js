import { CopilotMenuItems } from './copilot-menu-items';
import { CopilotMenuItem } from '../copilot-menu';
import { Loc } from 'main.core';
import { CopilotCommands } from '../copilot-commands';
import { Actions, Main } from 'ui.icon-set.api.core';

type CopilotResultMenuItemsOptions = {
	prompts: Prompt[];
	selectedText: string;
}

export class CopilotResultMenuItems extends CopilotMenuItems
{
	static getMenuItems(options: CopilotResultMenuItemsOptions): CopilotMenuItem[]
	{
		const { prompts, selectedText } = options;
		const saveMenuItemText = selectedText ? 'AI_COPILOT_COMMAND_REPLACE' : 'AI_COPILOT_COMMAND_SAVE';
		const saveMenuItem = {
			text: Loc.getMessage(saveMenuItemText),
			code: 'save',
			icon: 'check',
		};

		const editMenuItem = {
			text: Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
			code: CopilotCommands.edit,
			icon: 'pencil-60',
		};

		const addBelowMenuItem = selectedText
			? {
				text: Loc.getMessage('AI_COPILOT_COMMAND_ADD_BELOW'),
				code: 'add_below',
				icon: 'download',
			}
			: null;

		return [
			saveMenuItem,
			addBelowMenuItem,
			{
				separator: true,
			},
			editMenuItem,
			...getResultMenuPromptItems(prompts),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
				code: 'repeat',
				icon: 'left-semicircular-anticlockwise-arrow-1',
			},
			{
				separator: true,
			},
			getFeedbackMenuItem(),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
				code: 'cancel',
				icon: 'cross-45',
			},
		].filter((item) => item);
	}

	static getMenuItemsForReadonlyResult(): CopilotMenuItem[]
	{
		return [
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_COPY'),
				code: 'copy',
				icon: Actions.COPY_PLATES,
			},
			{
				separator: true,
			},
			getFeedbackMenuItem(),
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_CLOSE'),
				code: 'close',
				icon: Actions.CROSS_45,
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

function getFeedbackMenuItem(): CopilotMenuItem
{
	return {
		code: CopilotCommands.feedback,
		text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
		icon: Main.FEEDBACK,
		notHighlight: true,
	};
}
