import { bind, Loc, Tag } from 'main.core';
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
		const detailBlock = Tag.render`
			<span class="ai__copilot_input-field-error-detail">
				${Loc.getMessage('AI_COPILOT_ERROR_DETAIL')}
			</span>
		`;

		bind(detailBlock, 'click', (e: MouseEvent) => {
			this.#initDetailerErrorInfoPopup();

			this.#detailedErrorInfoPopup.setContent(this.#getDetailedErrorInfoPopupContent());

			this.#detailedErrorInfoPopup.setBindElement(e.target);
			this.#detailedErrorInfoPopup.setOffset({
				offsetTop: 5,
				offsetLeft: -20,
			});
			this.#detailedErrorInfoPopup.show();
		});

		return Tag.render`
			<div class="ai__copilot_input-field-error">
				<span class="ai__copilot_input-field-error-title">
					${Loc.getMessage('AI_COPILOT_ERROR')} 
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
