import { Tag, Dom } from 'main.core';
import { Button } from 'ui.buttons';

export class ButtonBar
{
	#buttonBarElement: HTMLElement;
	#buttons: [Button];

	constructor(buttons: [Button] = [])
	{
		this.#buttons = buttons;
	}

	render(): HTMLElement
	{
		if (this.#buttonBarElement)
		{
			return this.#buttonBarElement;
		}

		this.#buttonBarElement = Tag.render`<div class="intranet-settings__button_bar"></div>`;

		for (const button of this.#buttons)
		{
			Dom.append(button.getContainer(), this.#buttonBarElement);
		}

		return this.#buttonBarElement;
	}

	getButtons(): [Button]
	{
		return this.#buttons;
	}

	addButton(button: Button)
	{
		this.#buttons.push(button);
		Dom.append(button.getContainer(), this.render());
	}
}