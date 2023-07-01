import type { NotifyManagerOptions } from './types/options';
import { Cache } from 'main.core';
import { LicenseNotificationPopup } from './lib/license-notification-popup';
import './style.css';
import 'ui.design-tokens';
import { NotifyPanel } from "./lib/notify-panel";

export class NotifyManager
{
	#cache = new Cache.MemoryCache();
	static componentName: string = 'bitrix:intranet.notify.panel';

	constructor(options: NotifyManagerOptions)
	{
		this.setOptions(options);
	}

	setOptions(options: NotifyManagerOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): ?NotifyManagerOptions
	{
		return this.#cache.get('options', null);
	}

	getLicenseNotificationPopup(options: Object): LicenseNotificationPopup
	{
		return this.#cache.remember('License-notification-popup', () => {
			return new LicenseNotificationPopup({
				isAdmin: this.getOptions().isAdmin,
				...options
			});
		});
	}

	getNotifyPanel(options: Object): NotifyPanel
	{
		return this.#cache.remember('notify-panel', () => {
			return new NotifyPanel(options);
		});
	}
}
