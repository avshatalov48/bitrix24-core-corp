import { Tag, Text } from 'main.core';
import { Popup } from 'main.popup';

export class InputPopup
{
	#popup: Popup = null;
	#bindElement: HTMLElement = null;
	#title: string = null;
	#placeholder: string = null;
	#buttonTitle: string = null;
	#input: HTMLInputElement = null;
	#onSubmit: Function = null;

	constructor(params: Object)
	{
		this.#bindElement = params.bindElement;
		this.#title = params.title;
		this.#placeholder = params.placeholder;
		this.#buttonTitle = params.buttonTitle || 'OK';
		this.#onSubmit = params.onSubmit;

		this.onClickHandler = this.onClickHandler.bind(this);
		this.onKeyUpHandler = this.onKeyUpHandler.bind(this);
	}

	show(): void
	{
		this.#getPopup().show();

		setTimeout(() => {
			this.#input.focus();
		});
	}

	destroy(): void
	{
		if (this.#popup)
		{
			this.#popup.destroy();
		}
	}

	setValue(value: string): InputPopup
	{
		if (this.#input)
		{
			this.#input.value = value;
		}
	}

	#getPopup(): Popup
	{
		if (!this.#popup)
		{
			this.#popup = new Popup({
				id: `crm-todo-link-input-popup-${Text.getRandom()}`,
				bindElement: this.#bindElement,
				content: this.#getContent(),
				closeByEsc: false,
				closeIcon: false,
				draggable: false,
				width: 466,
				padding: 0,
			});
		}

		return this.#popup;
	}

	#getContent(): HTMLElement
	{
		this.#input = Tag.render`
			<input 
				type="text" 
				placeholder="${Text.encode(this.#placeholder)}"
				class="ui-ctl-element"
				onkeyup="${this.onKeyUpHandler}"
			>
		`;

		return Tag.render`
			<div class="crm-activity__todo-editor-v2_block-popup-wrapper --link">
				<div class="crm-activity__todo-editor-v2_block-popup-title">
					${Text.encode(this.#title)}
				</div>
				<div class="crm-activity__todo-editor-v2_block-popup-content">
					${this.#input}
					<button 
						onclick="${this.onClickHandler}" 
						class="ui-btn ui-btn-primary"
					>
						${Text.encode(this.#buttonTitle)}
					</button>
				</div>
			</div>
		`;
	}

	onClickHandler(event): void
	{
		this.#getPopup().close();
		this.#onSubmit(this.#input.value);
	}

	onKeyUpHandler(event): void
	{
		if (event.keyCode === 13)
		{
			this.onClickHandler();
		}
	}
}
