import { EventEmitter } from 'main.core.events';
import { Popup } from './popup';
import { Cache } from 'main.core';
import './style.css';

type DesktopPortalListOptions = {
	bindElement: HTMLElement,
}

export class DesktopAccountList extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: DesktopPortalListOptions)
	{
		super();
		this.#setOptions(options);
		this.setEventNamespace('BX.Intranet.DesktopPortalList');
	}

	show(): void
	{
		this.#getPopup().show();
	}

	#setOptions(options: DesktopPortalListOptions): DesktopAccountList
	{
		this.#cache.set('options', options);

		return this;
	}

	#getOptions(): DesktopPortalListOptions
	{
		return this.#cache.get('options', {});
	}

	#getPopup(): Popup
	{
		return this.#cache.remember('popup', () => {
			return new Popup({
				bindElement: this.#getOptions().bindElement,
			});
		});
	}
}
