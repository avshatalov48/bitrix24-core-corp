import { Dom } from 'main.core';
import type { CopilotInput } from 'ai.copilot';
import type { BaseCommandOptions } from './base-command';
import { BaseCommand } from './base-command';

type CancelImageCommandOptions = {
	inputField: CopilotInput;
	copilotContainer: HTMLElement;
} | BaseCommandOptions;

export class CancelImageCommand extends BaseCommand
{
	#inputField: CopilotInput;
	#copilotContainer: HTMLElement;

	constructor(options: CancelImageCommandOptions)
	{
		super(options);

		this.#copilotContainer = options.copilotContainer;
		this.#inputField = options.inputField;
	}

	execute()
	{
		this.copilotImageController.emit('cancel');
		this.copilotImageController.destroyAllMenus();
		this.copilotImageController.showImageConfigurator();
		this.#inputField.clearErrors();
		this.#inputField.clear();
		this.#inputField.enable();
		Dom.removeClass(this.#copilotContainer, '--error');
		// this.#selectedCommand = null;
		// this.#resultStack = [];
		this.#inputField.focus();
		this.copilotImageController.getAnalytics().sendEventCancel();
	}
}
