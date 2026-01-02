import { Cache } from 'main.core';

export class BaseTool
{
	cache = new Cache.MemoryCache();

	constructor(options = {})
	{
		this.options = options;
	}

	getLayout(): HTMLElement
	{
		throw new Error('Must be implemented in a child class');
	}

	getIconClass(): string
	{
		throw new Error('Must be implemented in a child class');
	}

	onClick(): void
	{
		throw new Error('Must be implemented in a child class');
	}

	getIconElement(): HTMLElement
	{
		throw new Error('Must be implemented in a child class');
	}

	getTitle(): string
	{
		return this.options.title || this.options.text || '';
	}
}
