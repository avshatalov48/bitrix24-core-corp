import { Cache } from 'main.core';
import { Popup } from './popup';
import type { GeneralOptions } from './types/options';
import './style.css';

export class LicenseWidget
{
	#cache = new Cache.MemoryCache();
	static #instance: LicenseWidget;

	static getInstance(): LicenseWidget
	{
		if (!this.#instance)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}

	show(): void
	{
		if (this.#getPopup().getBasePopup().isShown())
		{
			return;
		}

		this.#getPopup().show();
	}

	setOptions(options: GeneralOptions): LicenseWidget
	{
		this.#cache.set('options', options);

		return this;
	}

	getOptions(): GeneralOptions
	{
		return this.#cache.get('options', {});
	}

	#getPopup(): Popup
	{
		return this.#cache.remember('popup', () => {
			return new Popup({
				target: this.getOptions().buttonWrapper,
				loader: this.getOptions().loader,
				content: {
					...this.getOptions().data,
				},
			});
		});
	}
}
