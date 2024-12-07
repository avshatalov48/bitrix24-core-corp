import type { CopilotInput } from 'ai.copilot';
import { Dom } from 'main.core';
import type { BaseCommandOptions } from './base-command';
import { BaseCommand } from './base-command';

type CancelCommandOptions = {
	inputField: CopilotInput;
	copilotContainer: HTMLElement;
} | BaseCommandOptions;

export class CancelCommand extends BaseCommand
{
	#copilotContainer: HTMLElement;
	#inputField: CopilotInput;

	constructor(options: CancelCommandOptions)
	{
		super(options);

		this.#copilotContainer = options.copilotContainer;
		this.#inputField = options.inputField;
	}

	execute()
	{
		this.copilotTextController.destroyAllMenus();
		this.copilotTextController.openGeneralMenu();
		this.copilotTextController.clearResultStack();
		this.#inputField.clearErrors();
		this.#inputField.clear();
		if (this.copilotTextController.isReadonly() === false)
		{
			this.#inputField.enable();
		}
		this.copilotTextController.clearResultField();
		Dom.removeClass(this.#copilotContainer, '--error');
		// this.#selectedCommand = null;
		this.copilotTextController.emit('cancel');
		this.#inputField.focus();
		this.copilotTextController.getAnalytics().sendEventCancel();
	}
}
