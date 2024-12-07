import type { CopilotImageController } from 'ai.copilot.copilot-image-controller';
import type { CopilotInput, CopilotMenuItem } from 'ai.copilot';
import { Loc } from 'main.core';
import { Main as IconSetMain, Actions as IconSetActions } from 'ui.icon-set.api.core';
import {
	SaveImageCommand,
	PlaceImageAboveCommand,
	PlaceImageUnderCommand,
	CancelImageCommand,
	RepeatImageCompletion,
} from '../menu-items-commands/index';

type CopilotErrorMenuItemsOptions = {
	copilotImageController: CopilotImageController;
	inputField: CopilotInput;
	useInsertAboveAndUnderMenuItems: boolean;
}

export class ImageConfiguratorResultMenuItems
{
	static getMenuItems(options: CopilotErrorMenuItemsOptions): CopilotMenuItem[]
	{
		const copilotImageController = options?.copilotImageController;
		const inputField = options.inputField;
		const useAboveAndUnderTextMenuItems = options.useInsertAboveAndUnderMenuItems;

		return [
			{
				code: 'save',
				text: Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_SAVE'),
				icon: IconSetMain.CHECK,
				notHighlight: true,
				command: new SaveImageCommand({
					copilotImageController,
				}),
			},
			useAboveAndUnderTextMenuItems
				? {
					code: 'place_above',
					text: Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_PLACE_ABOVE_TEXT'),
					icon: IconSetActions.ARROW_TOP,
					notHighlight: true,
					command: new PlaceImageAboveCommand({
						copilotImageController,
					}),
				} : null,
			useAboveAndUnderTextMenuItems
				? {
					code: 'place_under',
					text: Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_PLACE_UNDER_TEXT'),
					icon: IconSetActions.ARROW_DOWN,
					notHighlight: true,
					command: new PlaceImageUnderCommand({
						copilotImageController,
					}),
				} : null,
			{
				code: 'repeat',
				text: Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_REPEAT'),
				icon: IconSetActions.LEFT_SEMICIRCULAR_ANTICLOCKWISE_ARROW_1,
				notHighlight: true,
				command: new RepeatImageCompletion({
					copilotImageController,
				}),
			},
			{
				separator: true,
			},
			{
				code: 'cancel',
				text: Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_CANCEL'),
				icon: IconSetActions.CROSS_45,
				notHighlight: true,
				command: new CancelImageCommand({
					copilotImageController,
					inputField,
				}),
			},
		].filter((item) => Boolean(item));
	}
}
