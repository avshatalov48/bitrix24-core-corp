import type { CopilotInput, CopilotMenu } from 'ai.copilot';
import { Dom } from 'main.core';
import type { BaseCommandOptions } from './base-command';
import { BaseCommand } from './base-command';

type EditResultCommandOptions = {
	inputField: CopilotInput;
	generalMenu: CopilotMenu;
	copilotContainer: HTMLElement;
} | BaseCommandOptions;

export class EditResultCommand extends BaseCommand
{
	#inputField: CopilotInput;
	#copilotContainer: HTMLElement;

	constructor(options: EditResultCommandOptions)
	{
		super(options);

		this.#inputField = options.inputField;
		this.#copilotContainer = options.copilotContainer;
	}

	execute(): void
	{
		this.copilotTextController.destroyAllMenus();
		this.#inputField.enable();
		this.#inputField.clearErrors();
		Dom.removeClass(this.#copilotContainer, '--error');
		// this.#resultField.clearResult();
		this.copilotTextController.openGeneralMenu();
		this.#inputField.focus();
		this.copilotTextController.getAnalytics().sendEventEditResult();
		// this.#selectedCommand = null;
	}
}
