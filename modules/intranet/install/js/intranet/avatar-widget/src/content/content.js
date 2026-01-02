import { EventEmitter } from 'main.core.events';
import { Cache } from 'main.core';
import type { ConfigOptions, BaseContentOptions } from '../types';

export class Content extends EventEmitter
{
	cache = new Cache.MemoryCache();

	constructor(options: BaseContentOptions)
	{
		super();
		this.setOptions(options);
		this.setEventNamespace('BX.Intranet.AvatarWidget.Content');
	}

	setOptions(options: BaseContentOptions): Content
	{
		this.cache.set('options', options);

		return this;
	}

	getOptions(): BaseContentOptions
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
			minHeight: '50px',
			margin: '0 13px',
		};
	}
}
