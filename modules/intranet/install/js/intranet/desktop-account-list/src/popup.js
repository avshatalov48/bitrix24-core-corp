import { EventEmitter } from 'main.core.events';
import { Cache } from 'main.core';
import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';
import { Content } from './content';
import type { ConfigOptions } from './types';

type PopupOptions = {
	bindElement: HTMLElement,
};

export class Popup extends EventEmitter
{
	#cache = new Cache.MemoryCache();
	#popupsShowAfterBasePopup: Array<Popup> = [];

	constructor(options: PopupOptions)
	{
		super();
		this.#setOptions(options);
		this.setEventNamespace('BX.Intranet.DesktopPortalList.Popup');
		this.#setEventHandlers();
	}

	show(): void
	{
		if (!this.#getBasePopup().isShown())
		{
			this.#getBasePopup().show();
		}
	}

	#setOptions(options: PopupOptions): Popup
	{
		this.#cache.set('options', options);

		return this;
	}

	#getOptions(): PopupOptions
	{
		return this.#cache.get('options', {});
	}

	#getBasePopup(): PopupComponentsMaker
	{
		return this.#cache.remember('popup', () => {
			this.emit('beforeInit');

			const popup = new PopupComponentsMaker({
				target: this.#getOptions().bindElement,
				width: 450,
				content: this.#getContent(),
				offsetTop: -50,
				offsetLeft: 50,
				useAngle: false,
			});
			this.#cache.set('popup', popup);

			this.emit('afterInit');

			return popup;
		});
	}

	#getContent(): Array<ConfigOptions>
	{
		return this.#cache.remember('content', () => {
			return (new Content()).getContent();
		});
	}

	#cleanCache(): void
	{
		this.#cache.delete('popup');
		this.#cache.delete('content');
	}

	#setEventHandlers(): void
	{
		this.subscribe('beforeInit', () => {
			EventEmitter.subscribe('BX.Intranet.DesktopPortalList.Item:change', () => {
				this.#getBasePopup().close();
				this.#cleanCache();
			});
			EventEmitter.subscribe('BX.Intranet.DesktopPortalList:openConnector', () => {
				this.#getBasePopup().close();
				this.#cleanCache();
			});
			this.subscribe('afterInit', () => {
				this.#setAutoHideEventHandlers();
			});
		});
	}

	#setAutoHideEventHandlers(): void
	{
		EventEmitter.subscribe('BX.Main.Popup:onShow', (event) => {
			if (!this.#getBasePopup().getPopup().isShown())
			{
				return;
			}

			const popup = event.getTarget();

			if (popup && popup.getId() !== this.#getBasePopup().getPopup().getId())
			{
				this.#getBasePopup().getPopup().setAutoHide(false);

				if (!this.#popupsShowAfterBasePopup.includes(popup))
				{
					this.#popupsShowAfterBasePopup.push(popup);
				}

				const handler = () => {
					this.#popupsShowAfterBasePopup = this.#popupsShowAfterBasePopup.filter((item) => item !== popup);

					if (this.#popupsShowAfterBasePopup.length === 0)
					{
						this.#getBasePopup().getPopup().setAutoHide(true);
					}
				};

				popup.subscribeOnce('onClose', handler);
				popup.subscribeOnce('onDestroy', handler);
			}
		});
	}
}
