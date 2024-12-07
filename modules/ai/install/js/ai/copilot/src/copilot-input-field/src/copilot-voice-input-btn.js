import { Tag, Event, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Icon, Main, Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';

import './css/copilot-voice-input-btn.css';

export class CopilotVoiceInputBtn extends EventEmitter
{
	#container: HTMLButtonElement | null;
	#stopRecordingButton: HTMLButtonElement;
	#startRecordingButton: HTMLButtonElement;

	#disabled: boolean;

	constructor(options) {
		super(options);
		this.setEventNamespace('AI:Copilot:VoiceButton');

		this.#disabled = false;
		this.#container = null;
	}

	start(): void
	{
		Dom.addClass(this.#container, '--recording');
	}

	stop(): void
	{
		Dom.removeClass(this.#container, '--recording');
	}

	enable(): void
	{
		this.#disabled = false;

		this.#enableStartRecordingButton();
		this.#enableStopRecordingButton();
	}

	disable(): void
	{
		this.#disabled = true;

		this.#disableStartRecordingButton();
		this.#disableStopRecordingButton();
	}

	isDisabled(): boolean
	{
		return this.#disabled;
	}

	getContainer(): HTMLElement
	{
		if (!this.#container)
		{
			this.#initContainer();
		}

		return this.#container;
	}

	render(): HTMLElement
	{
		return this.getContainer();
	}

	#initContainer(): void
	{
		this.#container = Tag.render`
			<div class="ai__copilot-voice-input-btn-container">
				${this.#renderStartRecordingButton()}
				${this.#renderStopRecordingButton()}
			</div>
		`;
	}

	#renderStartRecordingButton(): HTMLButtonElement
	{
		const microphoneIcon = new Icon({
			icon: Main.MICROPHONE_ON,
			size: 20,
		});

		this.#startRecordingButton = Tag.render`
			<button
				class="ai__copilot-voice-input-btn --start"
			>
				${microphoneIcon.render()}
			</button>
		`;

		this.#startRecordingButton.disabled = this.#disabled;

		Event.bind(this.#startRecordingButton, 'click', () => {
			this.emit('start');
		});

		return this.#startRecordingButton;
	}

	#renderStopRecordingButton(): HTMLButtonElement
	{
		const stopIcon = new Icon({
			icon: Actions.STOP,
			size: 17,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-on-primary'),
		});

		this.#stopRecordingButton = Tag.render`
			<button
				class="ai__copilot-voice-input-btn --stop"
			>
				${stopIcon.render()}
			</button>
		`;

		this.#stopRecordingButton.disabled = this.#disabled;

		Event.bind(this.#stopRecordingButton, 'click', () => {
			this.emit('stop');
		});

		return this.#stopRecordingButton;
	}

	#enableStartRecordingButton(): void
	{
		if (this.#startRecordingButton)
		{
			this.#startRecordingButton.disabled = false;
		}
	}

	#disableStartRecordingButton(): void
	{
		if (this.#startRecordingButton)
		{
			this.#startRecordingButton.disabled = true;
		}
	}

	#enableStopRecordingButton(): void
	{
		if (this.#stopRecordingButton)
		{
			this.#stopRecordingButton.disabled = false;
		}
	}

	#disableStopRecordingButton(): void
	{
		if (this.#stopRecordingButton)
		{
			this.#stopRecordingButton.disabled = true;
		}
	}
}
