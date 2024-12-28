import { Base } from './base';
import { Tag, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonIcon } from 'ui.buttons';
import { TextField } from './text-field';
import { Popup } from 'main.popup';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';

import '../css/ui/text-message.css';
import '../css/ui/submit-btn.css';

export const TextMessageSubmitButtonIcon = Object.freeze({
	PENCIL: 'pencil',
	BRUSH: 'brush',
});

type TextMessageProps = {
	submitButtonIcon: string;
	message: string;
	placeholder: string;
	hint: TextMessageHint;
	isLoading: boolean;
}

type TextMessageHint = {
	title: string;
	text: string;
}

export class TextMessage extends Base
{
	#submitBtn: Button | null;
	#textField: TextField | null;
	#hintPopup: Popup | null;
	#buttonIcon: 'pencil' | 'brush';
	#container: HTMLElement | null;
	#submitBtnContainer: HTMLElement;
	#isLoading: boolean;

	constructor(props: TextMessageProps)
	{
		super(props);

		this.setEventNamespace('AI:Picker:TextMessage');

		this.#hintPopup = null;
		this.#container = null;
		this.#buttonIcon = this.#isValidButtonIcon(props.submitButtonIcon) ? props.submitButtonIcon : 'pencil';
		this.#isLoading = props.isLoading;
	}

	#isValidButtonIcon(buttonIcon: string): boolean
	{
		return Object.values(TextMessageSubmitButtonIcon).includes(buttonIcon);
	}

	focus(): void
	{
		if (!this.#textField)
		{
			return;
		}

		this.#textField.focus();
	}

	#renderButton(): void
	{
		if (this.#submitBtnContainer)
		{
			this.#submitBtnContainer.innerHTML = '';
			Dom.append(this.getButton(), this.#submitBtnContainer);
		}
	}

	#getTextArea(): TextField
	{
		const placeholder = this.getMessage('placeholder');
		const textarea = new TextField({
			value: this.props.message,
			placeholder,
		});

		this.#textField = textarea;

		EventEmitter.subscribe(textarea, 'input', this.#handleTextareaInput.bind(this));

		return textarea;
	}

	#handleTextareaInput(event)
	{
		if (event.data.value && this.#isLoading === false)
		{
			this.#setSubmitBtnState(null);
		}
		else
		{
			this.#setSubmitBtnState(Button.State.DISABLED);
		}
	}

	getButton(): HTMLButtonElement
	{
		const btn = new Button({
			text: this.getMessage('submit'),
			round: true,
			color: Button.Color.PRIMARY,
			icon: ButtonIcon.SEARCH,
			onclick: (button: Button) => {
				if (button.getState() === null && this.#textField.getValue() !== '')
				{
					this.emit('submit', {
						text: this.#textField.getValue(),
					});
				}
			},
			state: this.props.message ? '' : Button.State.DISABLED,

			className: `ai__picker_submit-btn --${this.#buttonIcon}`,
		});

		this.#submitBtn = btn;

		return btn.render();
	}

	closeMenu()
	{
		if (this.#hintPopup)
		{
			this.#hintPopup.close();
		}
	}

	#getButtonState(): string | null
	{
		if (this.#isLoading)
		{
			return Button.State.CLOCKING;
		}

		if (!this.#textField.getValue())
		{
			return Button.State.DISABLED;
		}

		return null;
	}

	render(): HTMLElement
	{
		this.#submitBtnContainer = Tag.render`<div></div>`;

		this.#container = Tag.render`
			<div class="ai__picker_text-message">
				<div class="ai__picker_text-message_text-field-wrapper">
					${this.#getTextArea().render()}
				</div>
				${this.#submitBtnContainer}
			</div>
		`;

		this.#renderButton();

		return this.#container;
	}

	disable(): void
	{
		if (this.#textField)
		{
			this.#textField.disable();
		}
		this.#setSubmitBtnState(Button.State.DISABLED);
	}

	enable(): void
	{
		this.#textField.enable();
		if (this.#textField.getValue())
		{
			this.#setSubmitBtnState(null);
		}
	}

	startLoading(): void
	{
		this.#isLoading = true;
		this.#textField.disable();
		this.#setSubmitBtnState(Button.State.CLOCKING);
	}

	finishLoading(): void
	{
		this.#isLoading = false;
		this.#textField.enable();
		const btnState = this.#getButtonState();
		this.#setSubmitBtnState(btnState);
	}

	#setSubmitBtnState(state: string | null): void
	{
		if (this.#submitBtn)
		{
			this.#submitBtn.getContainer().blur();
			this.#submitBtn.setState(state);
		}
	}
}
