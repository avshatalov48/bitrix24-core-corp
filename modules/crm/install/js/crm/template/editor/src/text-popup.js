import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { Popup, PopupWindowManager } from 'main.popup';
import { Button, ButtonState } from 'ui.buttons';

export default class TextPopup
{
	#popup: Popup = null;
	#input: HTMLInputElement = null;
	#bindElement: HTMLElement = null;
	#value: string = null;
	#onApply: Function = () => {};

	constructor({ bindElement, value, onApply })
	{
		this.#bindElement = bindElement;
		this.#value = value;
		this.#onApply = onApply;
	}

	destroy(): void
	{
		this.#popup?.destroy();
	}

	show(): void
	{
		this.#getPopup().show();
	}

	#getPopup(): Popup
	{
		if (this.#popup === null)
		{
			this.#popup = PopupWindowManager.create(
				'crm-template-editor-text-popup',
				this.#bindElement,
				{
					autoHide: true,
					content: this.#getContent(),
					closeByEsc: true,
					closeIcon: false,
					buttons: this.#getMenuButtons(),
					cacheable: false,
				},
			);

			this.#popup.subscribe('onShow', () => {
				// Give time for input to render before setting focus.
				setTimeout(() => {
					this.#input.focus();
					this.#setCursorToEnd();
				}, 0);
			});
		}

		return this.#popup;
	}

	#getContent(): HTMLElement
	{
		const content = Tag.render`<div class="crm-template-editor-text-popup-wrapper"></div>`;

		this.#input = Tag.render`
			<input 
				type="text" 
				value="${Text.encode(this.#value)}"
				maxlength="255"
				placeholder="${Loc.getMessage('CRM_TEMPLATE_EDITOR_SELECT_FIELD_PLACEHOLDER')}
			">
		`;
		Dom.append(this.#input, content);

		this.#bindInputEvents();

		return content;
	}

	#bindInputEvents(): void
	{
		Event.bind(this.#input, 'keyup', (event: Event) => {
			const button = this.#getApplyButtonInstance();
			if (!button)
			{
				return;
			}

			const { value } = event.target;
			this.#adjustButtonState(button, value);
		});
	}

	#getMenuButtons(): Button[]
	{
		return [
			this.#getApplyButton(),
			this.#getCancelButton(),
		];
	}

	#getApplyButton(): Button
	{
		const button = new Button({
			id: 'apply-button',
			text: this.#getApplyButtonText(),
			className: 'ui-btn ui-btn-xs ui-btn-primary ui-btn-round',
			onclick: () => {
				this.#onApplyButtonClick();
			},
		});

		const { value } = this.#input;
		this.#adjustButtonState(button, value);

		return button;
	}

	#adjustButtonState(button: Button, value: string): void
	{
		button.setState(
			(Type.isStringFilled(value) && Type.isStringFilled(value.trim()))
				? ButtonState.ACTIVE
				: ButtonState.DISABLED
		);
	}

	#getApplyButtonText(): string
	{
		if (Type.isStringFilled(this.#value))
		{
			return Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_UPDATE');
		}

		return Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_ADD');
	}

	#onApplyButtonClick(): void
	{
		const button = this.#getApplyButtonInstance();
		if (button.getState() !== ButtonState.ACTIVE)
		{
			return;
		}

		this.destroy();

		const { value } = this.#input;
		this.#bindElement.textContent = Text.encode(value);

		this.#onApply(value.trim());
	}

	#getApplyButtonInstance(): Button
	{
		return this.#popup.getButton('apply-button');
	}

	#getCancelButton(): Button
	{
		return new Button({
			text: Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_CANCEL'),
			className: 'ui-btn ui-btn-xs ui-btn-light ui-btn-round',
			onclick: () => {
				this.destroy();
			},
		});
	}

	#setCursorToEnd(): void
	{
		const { length } = this.#input.value;

		this.#input.selectionStart = length;
		this.#input.selectionEnd = length;
	}
}
