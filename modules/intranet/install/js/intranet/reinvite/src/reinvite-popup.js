import { Tag, Type, Loc } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { Button } from 'ui.buttons';
import { Form } from './form';
import type { ReinvitePopupOptions } from './types';
import { FormFactory } from './form-factory';
import './style.css';

export class ReinvitePopup
{
	#popup: Popup;
	#transport: function;
	#userId: number;
	#id: string;
	#inputValue: string;
	#bindElement: ?HTMLElement;
	#form: Form;
	#width : number;

	constructor(options: ReinvitePopupOptions)
	{
		if (options.userId <= 0)
		{
			throw new Error('Invalide "userId" parameter');
		}
		this.#userId = options.userId;
		this.#id = 'reinvite-popup-' + options.userId;
		this.#bindElement  = Type.isElementNode(options.bindElement) ? options.bindElement : null;
		this.#transport = Type.isFunction(options.transport) ? options.transport : null;
		this.#width = 348;

		this.#form = FormFactory.create(options.formType, {
			id: this.#id,
			userId: this.#userId,
			inputValue: options.inputValue,
		});
	}

	show(): void
	{
		this.getPopup().show();
	}

	getPopup(): Popup
	{
		if (this.#popup)
		{
			return this.#popup;
		}

		this.#popup = this.#createPopup();

		return this.#popup;
	}

	#createPopup(options): Popup
	{
		if (PopupManager.isPopupExists(this.#id))
		{
			return PopupManager.getPopupById(this.#id);
		}

		return new Popup(
			this.#id,
			this.#bindElement,
			{
				content: this.#form.render(),
				autoHide: true,
				angle: {
					offset: this.#width / 2 - 16.5,
				},
				width: this.#width,
				padding: 18,
				offsetLeft: (((this.#bindElement.offsetWidth / 2) - this.#width / 2) / 2) - 10,
				closeIcon: false,
				closeByEsc: true,
				overlay: false,
				className: 'reinvite-popup-container',
				bindOptions: { position: 'top' },
				animation: "fading-slide",
				buttons: [
					new Button({
						text: Loc.getMessage('INTRANET_JS_BTN_SEND'),
						color: Button.Color.PRIMARY,
						round: true,
						noCaps: true,
						onclick: (button: Button) => {
							this.send();
							this.getPopup().close();
						},
					}),
					new Button({
						text: Loc.getMessage('INTRANET_JS_BTN_CANCEL'),
						color: Button.Color.LIGHT_BORDER,
						round: true,
						noCaps: true,
						onclick: (button: Button) => {
							this.getPopup().close();
						},
					}),
				],
			},
		);
	}

	send(): void
	{
		this.#transport(this.#form.getData());
	}
}
