import { Loc } from 'main.core';
import type { CopilotMenuItem } from '../copilot-menu';
import { CopilotCommands } from '../copilot-commands';

export class CopilotErrorMenuItems
{
	static getMenuItems(): CopilotMenuItem
	{
		return [
			{
				code: 'repeat',
				text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
				icon: 'left-semicircular-anticlockwise-arrow-1',
			},
			{
				text: Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
				code: CopilotCommands.edit,
				icon: 'pencil-60',
			},
			{
				code: 'cancel',
				text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
				icon: 'cross-45',
			},
		];
	}
}
