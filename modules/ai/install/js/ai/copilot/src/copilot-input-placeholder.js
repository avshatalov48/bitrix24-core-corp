import { Tag, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';

type CopilotInputPlaceholderOptions = {
	readonly: boolean;
}

export const CopilotInputPlaceholderEvents = Object.freeze({});

export class CopilotInputPlaceholder extends EventEmitter
{
	#container: HTMLElement;
	#readonly: boolean = false;

	constructor(options: CopilotInputPlaceholderOptions) {
		super(options);

		this.setEventNamespace('AI.Copilot.InputPlaceholder');

		this.#readonly = options.readonly === true;
	}

	render(): HTMLElement
	{
		this.#container = this.getContainer();

		return this.#container;
	}

	getContainer(): HTMLElement
	{
		if (!this.#container)
		{
			const placeholderText = this.#getPlaceholderText();

			this.#container = Tag.render`
				<div class="ai_copilot_placeholder">
					<span>${placeholderText}</span>
				</div>
			`;
		}

		return this.#container;
	}

	#getPlaceholderText(): string
	{
		if (this.#readonly)
		{
			return Loc.getMessage('AI_COPILOT_SELECT_COMMAND_BELOW');
		}

		return Loc.getMessage('AI_COPILOT_INPUT_START_PLACEHOLDER');
	}
}
