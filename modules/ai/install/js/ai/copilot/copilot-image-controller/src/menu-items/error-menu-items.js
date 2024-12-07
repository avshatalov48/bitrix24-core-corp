import type { CopilotImageController } from 'ai.copilot.copilot-image-controller';
import type { CopilotInput, CopilotMenuItem } from 'ai.copilot';
import { Loc } from 'main.core';
import { RepeatImageCompletion, CancelImageCommand } from '../menu-items-commands/index';

type CopilotErrorMenuItemsOptions = {
	copilotImageController: CopilotImageController;
	inputField: CopilotInput;
	copilotContainer: HTMLElement;
}

export class ImageConfiguratorErrorMenuItems
{
	static getMenuItems(options: CopilotErrorMenuItemsOptions): CopilotMenuItem[]
	{
		const copilotImageController = options.copilotImageController;
		const inputField = options.inputField;
		const copilotContainer = options.copilotContainer;

		return [
			{
				code: 'repeat',
				text: Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
				icon: 'left-semicircular-anticlockwise-arrow-1',
				notHighlight: true,
				command: new RepeatImageCompletion({
					copilotImageController,
				}),
			},
			{
				code: 'cancel',
				text: Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
				icon: 'cross-45',
				notHighlight: true,
				command: new CancelImageCommand({
					copilotImageController,
					inputField,
					copilotContainer,
				}),
			},
		];
	}
}
