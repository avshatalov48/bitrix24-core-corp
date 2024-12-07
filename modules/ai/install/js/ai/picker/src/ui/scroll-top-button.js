import { bind, bindOnce, Tag, Dom } from 'main.core';
import { Icon, Actions } from 'ui.icon-set.api.core';

import { Base } from './base';

import '../css/ui/scroll-top-button.css';

export class ScrollTopButton extends Base
{
	#button: HTMLElement | null;
	#isShow: boolean;

	constructor(props) {
		super(props);
		this.setEventNamespace('AI:Picker:ScrollTopButton');

		this.#button = null;
		this.#isShow = true;
	}

	render(): HTMLElement
	{
		const icon = new Icon({
			icon: Actions.CHEVRON_UP,
			size: 26,
		});

		this.#button = Tag.render`
			<button class="ai__picker_go-top-btn">
				${icon.render()}
			</button>
		`;

		Dom.style(this.#button, {
			visibility: this.#isShow ? '' : 'hidden',
		});

		bind(this.#button, 'click', () => {
			this.emit('click');
		});

		return this.#button;
	}

	show()
	{
		if (this.#isShow)
		{
			return;
		}

		this.#isShow = true;

		if (this.#button)
		{
			Dom.style(this.#button, 'visibility', null);
			setTimeout(() => {
				Dom.style(this.#button, 'opacity', 1);
			}, 10);
		}
	}

	hide()
	{
		if (!this.#isShow)
		{
			return;
		}

		this.#isShow = false;

		if (this.#button)
		{
			Dom.style(this.#button, 'opacity', 0);
			bindOnce(this.#button, 'transitionend', () => {
				Dom.style(this.#button, 'visibility', 'hidden');
			});
		}
	}

	isShow()
	{
		this.#isShow = true;
	}
}
