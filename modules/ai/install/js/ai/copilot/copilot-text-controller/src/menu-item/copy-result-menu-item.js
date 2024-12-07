import { type CopilotMenu } from 'ai.copilot';
import { type MenuItem } from 'main.popup';
import { Loc } from 'main.core';

import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';

type CopyResultMenuItemOptions = BaseMenuItemOptions & {
	getText: Function;
}

export class CopyResultMenuItem extends BaseMenuItem
{
	#getText: Function;

	constructor(options: CopyResultMenuItemOptions)
	{
		super({
			text: Loc.getMessage('AI_COPILOT_COMMAND_COPY'),
			onClick: (event, menuItem: MenuItem, menu: CopilotMenu) => {
				const isCopyingSuccess = BX.clipboard.copy(this.#getText());

				if (isCopyingSuccess === false)
				{
					return;
				}

				menu.markMenuItemSelected(menuItem.getId());
				setTimeout(() => {
					menu.unmarkMenuItemSelected(menuItem.getId());
				}, 800);
			},
			...options,
		});

		this.#getText = options.getText;
	}
}
