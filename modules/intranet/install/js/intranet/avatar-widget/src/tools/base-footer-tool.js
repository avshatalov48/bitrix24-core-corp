import { Cache, Tag } from 'main.core';

export class BaseFooterTool
{
	cache = new Cache.MemoryCache();

	constructor(options = {})
	{
		this.options = options;
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
			<div data-testid="bx-avatar-widget-footer-tool-${this.getId()}" onclick="${this.onClick.bind(this)}" class="intranet-avatar-widget-footer__item">
				${this.getTitle()}
			</div>
		`;
	}

	onClick(): void
	{
		throw new Error('Must be implemented in a child class');
	}

	getTitle(): string
	{
		return this.options.title || this.options.text || '';
	}

	getId(): string
	{
		return '';
	}
}
