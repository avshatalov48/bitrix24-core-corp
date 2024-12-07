import { Event, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';

export type CopilotInputErrorInfo = {
	code: string;
	message: string;
	customData: any;
}

type CopilotInputErrorOptions = {
	errors: CopilotInputErrorInfo[];
}

export class CopilotInputError
{
	#errors: CopilotInputErrorInfo[];
	#detailedErrorInfoPopup: Popup | null;

	constructor(options: CopilotInputErrorOptions)
	{
		this.#errors = options.errors;
	}

	render(): HTMLElement
	{
		let message: string = this.#errors[this.#errors.length - 1].message;
		const code: string = this.#errors[this.#errors.length - 1].code;

		let detailBlock: HTMLElement = null;
		if (this.#errors[0]?.code === 'AI_ENGINE_ERROR_OTHER')
		{
			message = Loc.getMessage('AI_COPILOT_ERROR_OTHER');
			message = message.replace('[feedback_form]', '<span class="ai__copilot_input-field-error-detail">');
			message = message.replace('[/feedback_form]', '</span>');

			detailBlock = Tag.render`
				<div class="ai__copilot_input-field-error">
					${message}
				</div>
			`;

			Event.bind(detailBlock.getElementsByClassName('ai__copilot_input-field-error-detail')[0], 'click', this.#errors[0]?.customData?.clickHandler);
		}
		else if (top.BX && top.BX.Helper && code === 'AI_ENGINE_ERROR_PROVIDER')
		{
			message = Loc.getMessage('AI_COPILOT_ERROR_PROVIDER');
			message = message.replace('[link]', '<span class="ai__copilot_input-field-error-detail">');
			message = message.replace('[/link]', '</span>');

			detailBlock = Tag.render`
				<div class="ai__copilot_input-field-error">
					${message}
				</div>
			`;

			Event.bind(detailBlock.getElementsByClassName('ai__copilot_input-field-error-detail')[0], 'click', (e: MouseEvent) => {
				top.BX.Helper.show('redirect=detail&code=20267044');
			});
		}
		else
		{
			detailBlock = Tag.render`
				<div class="ai__copilot_input-field-error">
					${message}
				</div>
			`;
		}

		return Tag.render`
			<div class="ai__copilot_input-field-error">
				<span class="ai__copilot_input-field-error-title">
					${detailBlock}
				</span>
			</div>
		`;
	}

	setErrors(errors: CopilotInputError[]): void
	{
		this.#errors = errors;
	}

	getErrors(): CopilotInputError[]
	{
		return this.#errors;
	}

	#initDetailerErrorInfoPopup(): void
	{
		if (this.#detailedErrorInfoPopup)
		{
			return;
		}

		this.#detailedErrorInfoPopup = new Popup({
			id: 'ai__copilot_error-popup',
			content: this.#getDetailedErrorInfoPopupContent(),
			darkMode: true,
			maxWidth: 300,
			autoHide: true,
			closeByEsc: true,
			closeIcon: true,
			cacheable: true,
		});
	}

	#getDetailedErrorInfoPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="ai__copilot_error-popup-content">
				${this.#errors[this.#errors.length - 1].message}
			</div>
		`;
	}
}
