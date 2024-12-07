import { Actions, Icon } from 'ui.icon-set.api.core';
import { Event, Tag, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';

import './css/copilot-submit-btn.css';

export const CopilotSubmitBtnEvents = Object.freeze({
	submit: 'submit',
});

export class CopilotSubmitBtn extends EventEmitter
{
	#submitBtn: HTMLButtonElement;
	#container: HTMLElement;

	constructor(options) {
		super(options);

		this.setEventNamespace('AI:Copilot:SubmitBtn');
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div
				class="ai__copilot_input-submit-btn-container"
			>
				${this.#renderHotKeyTag()}
				${this.#renderSubmitBtn()}
			</div>
		`;

		return this.#container;
	}

	#renderHotKeyTag(): HTMLElement
	{
		const hotKeyIcon = new Icon({
			size: 20,
			icon: Actions.ARROW_TOP_2,
		});

		return Tag.render`
			<div class="ai__copilot_input-submit-hotkey">
				<div class="ai__copilot_input-submit-hotkey-icon">
					${hotKeyIcon.render()}
				</div>
				<div class="ai__copilot_input-submit-hotkey-text">Enter</div>
			</div>
		`;
	}

	#renderSubmitBtn(): HTMLButtonElement
	{
		const btnIcon = new Icon({
			size: 18,
			icon: Actions.ARROW_TOP,
			color: '#fff',
		});
		this.#submitBtn = Tag.render`
			<button class="ai__copilot_input-submit-btn">
				${btnIcon.render()}
			</button>
		`;

		Event.bind(this.#submitBtn, 'click', (e) => {
			this.emit(CopilotSubmitBtnEvents.submit);
			e.preventDefault();
		});

		return this.#submitBtn;
	}

	disable(): void
	{
		Dom.addClass(this.#container, '--disabled');
		this.#submitBtn.disabled = true;
	}

	enable(): void
	{
		Dom.removeClass(this.#container, '--disabled');
		this.#submitBtn.disabled = false;
	}
}
