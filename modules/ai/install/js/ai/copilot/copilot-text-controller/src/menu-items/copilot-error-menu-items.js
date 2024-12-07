import type { CopilotInput, CopilotMenuItem } from 'ai.copilot';
import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';
import { Loc } from 'main.core';
import { CancelCommand, EditResultCommand, RepeatCommand } from '../menu-item-commands/index';

type CopilotErrorMenuItemsOptions = {
	inputField: CopilotInput;
	copilotTextController: CopilotTextController;
	copilotContainer: HTMLElement;
}

export class CopilotErrorMenuItems
{
	static getMenuItems(options: CopilotErrorMenuItemsOptions): CopilotMenuItem
	{
		const {
			inputField,
			copilotTextController,
			copilotContainer,
		} = options;

		return [
			{
				code: 'repeat',
				text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
				icon: 'left-semicircular-anticlockwise-arrow-1',
				command: new RepeatCommand({
					copilotTextController,
				}),
				notHighlight: true,
			},
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
				code: 'edit',
				icon: 'pencil-60',
				command: new EditResultCommand({
					inputField,
					copilotTextController,
					copilotContainer,
				}),
				notHighlight: true,

			},
			{
				code: 'cancel',
				text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
				icon: 'cross-45',
				command: new CancelCommand({
					copilotTextController,
					inputField,
					copilotContainer,
				}),
				notHighlight: true,
			},
		];
	}
}
