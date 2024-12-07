import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import { Type, Tag, Loc } from 'main.core';

type AgreementPopupProps = {
	content: string;
	title: string;
	onApply: Function<Promise>;
	context: HTMLElement,
}

export class AgreementPopup
{
	#popup: Popup | null;
	#title: string;
	#content: string;
	#onApply: Function<Promise>;

	constructor(props: AgreementPopupProps)
	{
		this.#content = props.content || '';
		this.#title = props.title || '';
		this.#onApply = props.onApply;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#popup = this.#createPopup();

			this.#popup.show();
		}

		this.#popup.show();
	}

	hide(): void
	{
		if (this.#popup)
		{
			this.#popup.close();
		}
	}

	#createPopup(): Popup
	{
		const maxHeight = window.innerHeight - 60;

		return new Popup({
			closeIcon: true,
			maxWidth: 800,
			maxHeight,
			disableScroll: true,
			titleBar: this.#title,
			content: this.#renderPopupContent(),
			overlay: true,
			cacheable: false,
			className: 'ai__copilot-agreement_popup',
			contentColor: getComputedStyle(document.body).getPropertyValue('--ui-color-base-02'),
			buttons: [
				new Button({
					text: Loc.getMessage('AI_AGREEMENT_ACCEPT'),
					color: Button.Color.SUCCESS,
					onclick: (button: Button) => {
						if (Type.isFunction(this.#onApply))
						{
							button.setState(Button.State.CLOCKING);
							if (Type.isFunction(this.#onApply))
							{
								this.#onApply()
									.then(() => {
										this.hide();
										button.setState(null);
									})
									.catch((err) => {
										console.error(err);
										button.setState(null);
									});
							}
						}
					},
				}),
			],
		});
	}

	#renderPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker_agreement">
				${this.#content}
			</div>
		`;
	}
}
