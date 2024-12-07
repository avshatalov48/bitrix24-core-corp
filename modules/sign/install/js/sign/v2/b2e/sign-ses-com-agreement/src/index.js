import { Tag, Runtime } from 'main.core';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import { Api } from 'sign.v2.api';
import './style.css';

type SignSesComAgreementConfig = {
	body: string;
	title: string;
	buttonText: string;
}

export class SignSesComAgreement
{
	#popup: Popup;
	#data: SignSesComAgreementConfig;

	constructor(data: SignSesComAgreementConfig)
	{
		this.#data = data;
	}

	show(): void
	{
		this.#getPopup().show();
	}

	#getPopup(): Popup
	{
		if (this.#popup)
		{
			return this.#popup;
		}

		const content = Tag.render`${this.#data.body}`;

		this.#popup = new Popup({
			titleBar: this.#data.title,
			content,
			className: 'sign__agreement_popup',
			lightShadow: true,
			maxWidth: 700,
			overlay: true,
			width: 700,
			height: 600,
			autoHide: false,
			closeByEsc: false,
			draggable: false,
			closeIcon: false,
			animation: 'fading-slide',
			cacheable: true,
			buttons: [
				this.#getSuccessButton(),
			],
		});

		this.#subscribeToEndOfScroll();

		return this.#popup;
	}

	#subscribeToEndOfScroll(): void
	{
		const container = this.#getPopup().getContentContainer();

		const detectEndOfScroll = () => {
			const gap = container.offsetHeight / 15;
			const endOfScroll: boolean = container.scrollHeight - container.scrollTop - container.offsetHeight <= gap;
			if (endOfScroll)
			{
				this.#getPopup().getButton('success')
					.setDisabled(false)
					.setActive(true)
				;
			}
		};

		container.addEventListener('scroll', Runtime.throttle(detectEndOfScroll, 200));
	}

	#getSuccessButton(): Button
	{
		return new Button({
			id: 'success',
			size: Button.Size.MEDIUM,
			color: Button.Color.SUCCESS,
			text: this.#data.buttonText,
			round: true,
			events: {
				click: () => {
					return new Api().setDecisionToSesB2eAgreement()
						.then(() => this.#getPopup().close())
					;
				},
			},
		}).setDisabled(true);
	}
}
