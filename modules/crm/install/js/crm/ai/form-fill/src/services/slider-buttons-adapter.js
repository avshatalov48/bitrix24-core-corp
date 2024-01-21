import { Loc, Type } from 'main.core';
import { Button, ButtonColor } from 'ui.buttons';

export default class SliderButtonsAdapter
{
	#onSaveCallback: ?() => void = null;

	#onCancelCallback: ?() => void = null;

	#saveButton: ?Button = null;
	#cancelButton: ?Button = null;

	constructor()
	{
		this.#createButtons();
	}

	set onSaveCallback(cb: () => void): void {
		this.#onSaveCallback = cb;
	}

	set onCancelCallback(cb: () => void): void {
		this.#onCancelCallback = cb;
	}

	get saveButton(): Button {
		return this.#saveButton;
	}

	get cancelButton(): Button {
		return this.#cancelButton;
	}

	#createButtons() {
		this.#saveButton = new Button({
			text: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_SAVE'),
			size: Button.Size.MEDIUM,
			color: Button.Color.SUCCESS,
			dependOnTheme: true,
			onclick: () => {
				if (Type.isFunction(this.#onSaveCallback))
				{
					this.#onSaveCallback();
				}
			},
		});

		this.#cancelButton = new Button({
			text: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_CANCEL'),
			size: Button.Size.MEDIUM,
			color: ButtonColor.LIGHT_BORDER,
			onclick: () => {
				if (Type.isFunction(this.#onCancelCallback))
				{
					this.#onCancelCallback();
				}
			},
		});
	}

	getButtons(): Button[]
	{
		return [this.#saveButton, this.#cancelButton];
	}
}