import { LicenseNotifier } from './notifiers/license-notifier';
import { NotifyPopup } from './notifiers/notify-popup';
import type { NotifyManagerOptions } from './types/options';
import { Cache } from 'main.core';
import './style.css';
import 'ui.design-tokens';
import { NotifyPanel } from './notifiers/notify-panel';

export class LicenseNotify
{
	#cache = new Cache.MemoryCache();
	static componentName: string = 'bitrix:intranet.notify.panel';

	constructor(options: NotifyManagerOptions)
	{
		this.setOptions(options);
	}

	getProvider(): ?LicenseNotifier
	{
		return this.#cache.remember(this.getOptions().notify.type, () => {
			if (this.getOptions().notify.type === 'panel')
			{
				return new NotifyPanel(this.getOptions().notify);
			}

			if (this.getOptions().notify.type === 'popup')
			{
				return new NotifyPopup(this.getOptions().notify);
			}

			return null;
		});
	}

	setOptions(options: NotifyManagerOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): ?NotifyManagerOptions
	{
		return this.#cache.get('options', null);
	}
}