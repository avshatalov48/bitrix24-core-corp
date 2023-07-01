import ReleaseSlider from './release-slider';
import ReleaseEar from './release-ear';
import { Type, ajax as Ajax } from 'main.core';
import type { BaseEvent } from 'main.core.events';

export default class PolarStar
{
	#deactivated = false;

	constructor(options)
	{
		options = Type.isPlainObject(options) ? options : {};

		if (!Type.isStringFilled(options.url))
		{
			throw new Error('PolarStar: the "url" parameter is required.');
		}

		this.slider = new ReleaseSlider({
			url: options.url,
			sliderOptions: options.sliderOptions,
			events: {
				onCloseComplete: this.#handleSliderClose.bind(this),
				onMessage: this.#handleFrameMessage.bind(this),
			},
		});

		this.ear = new ReleaseEar({
			zone: options.zone,
			events: {
				onClick: this.#handleEarClick.bind(this),
			},
		});
	}

	show(mode = 'ear'): void
	{
		if (mode === 'slider')
		{
			this.getSlider().show();
			this.#runAction('show', { context: 'auto' });
		}
		else
		{
			this.getEar().show();
		}
	}

	getSlider(): ReleaseSlider
	{
		return this.slider;
	}

	getEar(): ReleaseEar
	{
		return this.ear;
	}

	#runAction(action, labels = {}, data = {}): Promise
	{
		return Ajax.runComponentAction('bitrix:intranet.bitrix24.polar-star', action, {
			mode: 'class',
			data,
			analyticsLabel: Object.assign({
				module: 'intranet',
				service: 'polar-star',
				action: action,
			}, labels),
		})
	}

	#handleSliderClose(): void
	{
		this.getEar().show(true);
		this.#runAction('close');
	}

	#handleEarClick(): void
	{
		this.getEar().hide();
		this.getSlider().show();
		this.#runAction('show', { context: 'ear-click' });
	}

	#handleFrameMessage(event: BaseEvent): void
	{
		const { message } = event.getData();
		if (!Type.isPlainObject(message))
		{
			return;
		}

		if (message.command === 'endOfScroll' && this.#deactivated === false)
		{
			this.#deactivated = true;
			this.#runAction('deactivate').catch(() => {
				this.#deactivated = false;
			});
		}

		if (message.command === 'openHelper')
		{
			if (BX.Helper)
			{
				BX.Helper.show(message.options);
			}
		}
	}
}