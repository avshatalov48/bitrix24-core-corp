import { Type, ajax as Ajax, type JsonObject, Reflection } from 'main.core';
import { type BaseEvent, EventEmitter } from 'main.core.events';

import ReleaseSlider from './release-slider';
import ReleaseEar from './release-ear';

export default class Release
{
	#deactivated = false;
	#id: string = '';

	constructor(releaseOptions: JsonObject)
	{
		const options = Type.isPlainObject(releaseOptions) ? releaseOptions : {};

		if (!Type.isStringFilled(options.url))
		{
			throw new Error('Release: the "url" parameter is required.');
		}

		this.#id = Type.isStringFilled(options.id) ? options.id : '';

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

		EventEmitter.subscribe('SidePanel.Slider:onOpen', () => {
			this.getEar().hide();
		});

		const onClose = () => {
			if (BX.SidePanel.Instance.getOpenSlidersCount() === 0)
			{
				this.getEar().show(true);
			}
		};

		EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', onClose);
		EventEmitter.subscribe('SidePanel.Slider:onDestroy', onClose);
	}

	show(mode = 'ear'): void
	{
		if (mode === 'slider')
		{
			const BannerDispatcher = Reflection.getClass('BX.UI.BannerDispatcher');
			if (BannerDispatcher)
			{
				BannerDispatcher.critical.toQueue(
					() => {
						this.getSlider().show();
					},
					{
						id: this.#id,
					},
				);
			}
			else
			{
				this.getSlider().show();
			}

			void this.#runAction('show', { context: 'auto' });
		}
		else
		{
			if (BX.SidePanel.Instance.getOpenSlidersCount() === 0)
			{
				this.getEar().show();
			}
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
		return Ajax.runComponentAction('bitrix:intranet.bitrix24.release', action, {
			mode: 'class',
			data,
			analyticsLabel: {
				module: 'intranet',
				service: this.#id,
				action,
				...labels,
			},
		});
	}

	#handleSliderClose(): void
	{
		if (BX.SidePanel.Instance.getOpenSlidersCount() === 0)
		{
			this.getEar().show(true);
		}

		const AutoLauncher = Reflection.getClass('BX.UI.AutoLaunch.AutoLauncher');
		if (AutoLauncher)
		{
			setTimeout(() => {
				AutoLauncher.unregister(this.#id);
			}, 1000);
		}

		void this.#runAction('close');
	}

	#handleEarClick(): void
	{
		this.getEar().hide();
		this.getSlider().show();
		void this.#runAction('show', { context: 'ear-click' });
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

		if (message.command === 'openHelper' && BX.Helper)
		{
			BX.Helper.show(message.options);
		}
	}
}
