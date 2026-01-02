import { EventEmitter } from 'main.core.events';
import { Cache } from 'main.core';
import type { ConfigOptions } from '../types/options';

export class Content extends EventEmitter
{
	cache = new Cache.MemoryCache();

	constructor(options: Object)
	{
		super();
		this.setOptions(options);
		this.setEventNamespace('BX.Intranet.LicenseWidget.Content');
	}

	setOptions(options: Object): Content
	{
		this.cache.set('options', options);

		return this;
	}

	getOptions(): Object
	{
		return this.cache.get('options', {});
	}

	getLayout(): HTMLElement
	{
		throw new Error('Must be implemented in a child class');
	}

	getConfig(): ConfigOptions
	{
		return {
			html: this.getLayout(),
			minHeight: '58px',
		};
	}
}