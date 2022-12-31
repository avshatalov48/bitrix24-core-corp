import { Cache, Type, Tag, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';

export default class ReleaseSlider extends EventEmitter
{
	#id:string = 'intranet:polar-star';
	#windowMessageHandler: Function = null;

	constructor(options)
	{
		super();
		this.setEventNamespace('BX.Intranet.Bitrix24.ReleaseSlider');

		options = Type.isPlainObject(options) ? options : {};
		this.subscribeFromOptions(options.events);

		this.url = Type.isStringFilled(options.url) ? options.url : 'about:blank';
		this.sliderOptions = Type.isPlainObject(options.sliderOptions) ? options.sliderOptions : {};

		this.#windowMessageHandler = this.#handleWindowMessage.bind(this);
		this.html = new Cache.MemoryCache();
	}

	show(): void
	{
		if (this.isOpen())
		{
			return;
		}

		const defaultOptions = {
			width: 1100,
			customLeftBoundary: 0,
		};
		const options = Object.assign({}, defaultOptions, this.sliderOptions);
		const userEvents = Type.isPlainObject(options.events) ? options.events : {};

		options.events = {
			onCloseComplete: () => {
				Event.unbind(window, 'message', this.#windowMessageHandler);
				this.emit('onCloseComplete');
			},
			onOpenComplete: () => {
				Event.bind(window, 'message', this.#windowMessageHandler);
			}
		};

		options.contentCallback = (slider) => {
			for (const eventName in userEvents)
			{
				if (Type.isFunction(userEvents[eventName]))
				{
					EventEmitter.subscribe(
						slider,
						BX.SidePanel.Slider.getEventFullName(eventName),
						userEvents[eventName]
					);
				}
			}

			return new Promise((resolve, reject) => {
				if (this.getFrame().src !== this.url)
				{
					this.getFrame().src = this.url;
				}

				Event.bind(this.getFrame(), 'load', this.#handleFrameLoad.bind(this));

				resolve(this.#getContent());
			});
		};

		BX.SidePanel.Instance.open(this.getId(), options);
	}

	hide(): void
	{
		const slider = this.getSlider();
		if (slider)
		{
			slider.close();
		}
	}

	isOpen(): boolean
	{
		return this.getSlider() && this.getSlider().isOpen();
	}

	getId(): string
	{
		return this.#id;
	}

	getSlider(): Object
	{
		return BX.SidePanel.Instance.getSlider(this.getId());
	}

	getFrame(): HTMLIFrameElement
	{
		return this.html.remember('frame', () => {
			return Tag.render`<iframe src="about:blank" class="intranet-polar-star-iframe"></iframe>`;
		});
	}

	#getContent(): HTMLElement
	{
		return this.html.remember('content', () => {
			return Tag.render`<div class="intranet-polar-star-container">${this.getFrame()}</div>`;
		});
	}

	#handleFrameLoad()
	{
		this.emit('onLoad');
	}

	#handleWindowMessage(event)
	{
		const frameOrigin = new URL(this.url);
		if (event.origin !== frameOrigin.origin)
		{
			return;
		}

		this.emit('onMessage', { message: event.data, event });
	}
}