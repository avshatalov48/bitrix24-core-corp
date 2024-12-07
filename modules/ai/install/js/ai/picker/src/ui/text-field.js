import { Base } from './base';
import { EventEmitter } from 'main.core.events';
import { Tag, Type, bind } from 'main.core';
import 'ui.icon-set.actions';

export type TextFieldProps = {
	value: string;
	placeholder: string;
}

export class TextField extends Base
{
	#textarea: HTMLTextAreaElement | null;
	#text: string;
	#placeholder: string;

	constructor(props: TextFieldProps) {
		super();
		this.#textarea = null;
		this.#text = Type.isString(props.value) ? props.value : '';
		this.#placeholder = Type.isString(props.placeholder) ? props.placeholder : '';
	}

	getValue(): string
	{
		return this.#textarea.value;
	}

	setValue(text: string): void
	{
		if (Type.isString(text) && this.#textarea)
		{
			this.#textarea.value = text;
		}
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker_textarea_wrapper">
				${this.#renderTextArea()}
			</div>
		`;
	}

	disable(): void
	{
		this.#textarea.disabled = true;
	}

	enable(): void
	{
		this.#textarea.disabled = false;
	}

	focus(): void
	{
		const contentLength = this.#textarea.value.length;

		this.#textarea.setSelectionRange(contentLength, contentLength);
		this.#textarea.focus();
	}

	isDisabled(): void
	{
		return this.#textarea.disabled;
	}

	#renderTextArea(): HTMLTextAreaElement
	{
		this.#textarea = Tag.render`
			<textarea
				class="ai__picker_textarea"
				placeholder="${this.#placeholder}"
			>
				${this.#text}
			</textarea>
		`;

		bind(this.#textarea, 'input', this.#handleInput.bind(this));

		this.setValue(this.#text);

		return this.#textarea;
	}

	#handleInput(e: InputEvent): void
	{
		const { value } = e.target;

		EventEmitter.emit(this, 'input', {
			value,
		});

		this.setValue(value);
	}
}
