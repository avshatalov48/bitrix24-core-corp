import { EventEmitter } from 'main.core.events';
import { Event } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { PopupPrefixId } from '../user-mini-profile';

const ShowDelayMs = 1000;
const CloseDelayMs = 500;

export class Tracking extends EventEmitter
{
	#popup: Popup;
	#bindElement: ?HTMLElement = null;

	#showOrCloseTimeout: ?number = null;
	#haveToCloseCheckInterval: ?number = null;

	#handler: {
		onMouseEnter: (event) => void,
		onMouseLeave: (event) => void,
		onBindElementClick: (event) => void,
	} = null;

	constructor(trackingOptions: { popup: Popup, bindElement: ?HTMLElement })
	{
		super();
		this.#popup = trackingOptions.popup;
		this.#bindElement = trackingOptions.bindElement;

		this.#handler = {
			onMouseEnter: (event) => this.#onMouseEnter(event),
			onMouseLeave: (event) => this.#onMouseLeave(event),
			onBindElementClick: (event) => this.#onBindElementClick(event),
		};

		this.setEventNamespace('Intranet.User.MiniProfile.Tracking');
	}

	setBindElement(element: ?HTMLElement): void
	{
		if (this.#bindElement === element)
		{
			return;
		}

		this.unbindTracking();
		this.#bindElement = element;
		if (this.#bindElement)
		{
			this.setupTracking();
		}
	}

	setupTracking(): void
	{
		const { onMouseEnter, onMouseLeave, onBindElementClick } = this.#handler;

		Event.bind(this.#bindElement, 'click', onBindElementClick);
		this.#getTrackingElements().forEach((element) => {
			Event.bind(element, 'mouseenter', onMouseEnter);
			Event.bind(element, 'mouseleave', onMouseLeave);
		});
	}

	unbindTracking(): void
	{
		const { onMouseEnter, onMouseLeave, onBindElementClick } = this.#handler;

		Event.unbind(this.#bindElement, 'click', onBindElementClick);
		this.#getTrackingElements().forEach((element) => {
			Event.unbind(element, 'mouseenter', onMouseEnter);
			Event.unbind(element, 'mouseleave', onMouseLeave);
		});
		clearInterval(this.#showOrCloseTimeout);
		clearInterval(this.#haveToCloseCheckInterval);
	}

	#onBindElementClick(): void
	{
		clearInterval(this.#haveToCloseCheckInterval);
		clearTimeout(this.#showOrCloseTimeout);
		this.emit('close');
	}

	#onMouseEnter(event): void
	{
		clearInterval(this.#haveToCloseCheckInterval);
		this.#scheduleShow();
	}

	#onMouseLeave(event): void
	{
		clearTimeout(this.#showOrCloseTimeout);
		if (this.#haveToClose())
		{
			this.#scheduleClose();
		}
		else if (!this.#isPopupOnTop())
		{
			this.#haveToCloseCheckInterval = setInterval(() => {
				if (!this.#haveToClose())
				{
					return;
				}

				this.emit('close');
				clearInterval(this.#haveToCloseCheckInterval);
			}, CloseDelayMs * 2);
		}
	}

	#haveToClose(): boolean
	{
		if (this.#popup.isShown() && this.#isPopupOnTop())
		{
			return true;
		}

		return false;
	}

	#isPopupOnTop(): boolean
	{
		const popupStack = PopupManager.getPopups();

		for (let i = popupStack.length - 1; i >= 0; --i)
		{
			const popup = popupStack[i];
			if (popup.getId() === this.#popup.getId())
			{
				return true;
			}

			if (popup.isShown() && popup.getId().includes(PopupPrefixId))
			{
				return false;
			}
		}

		return true;
	}

	#scheduleClose(): void
	{
		clearTimeout(this.#showOrCloseTimeout);
		this.#showOrCloseTimeout = setTimeout(() => {
			this.emit('close');
		}, CloseDelayMs);
	}

	#scheduleShow(): void
	{
		clearTimeout(this.#showOrCloseTimeout);
		this.#showOrCloseTimeout = setTimeout(() => {
			this.emit('show');
		}, ShowDelayMs);
	}

	#getTrackingElements(): Array<HTMLElement>
	{
		return [this.#bindElement, this.#popup.getPopupContainer()];
	}
}
