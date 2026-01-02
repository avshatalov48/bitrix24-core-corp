import { Type, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Page } from './page/page';

export class Navigation extends EventEmitter
{
	#pages: Map;
	#container: ?HTMLElement;
	#first: ?string;
	#current: ?string;
	#history: [string] = [];

	constructor(options)
	{
		super();
		this.setEventNamespace('BX.Intranet.Navigation');
		this.#container = Type.isDomNode(options.container) ? options.container : null;
		if (Type.isMap(options.pages))
		{
			this.#pages = new Map(
				[...options.pages].filter(([k, page]) => page instanceof Page),
			);
		}
		else
		{
			this.#pages = new Map();
		}

		this.#first = Type.isStringFilled(options.first) && this.has(options.first)
			? options.first
			: this.#pages.next().value
		;
	}

	show(code: string): void
	{
		if (!this.#container || !this.has(code))
		{
			return;
		}
		const page = this.get(code);
		this.emit('onBeforeChangePage', {
			current: this.current(),
			new: page,
		});
		Dom.clean(this.#container);
		Dom.append(page.render(), this.#container);
		if (this.#current)
		{
			this.#history.push(this.#current);
		}
		this.#current = code;
		this.emit('onAfterChangePage', {
			current: this.current(),
			previous: this.prev(),
		});
	}

	showFirst(): void
	{
		this.show(this.#first);
	}

	get(code: string): ?Page
	{
		return this.#pages.get(code);
	}

	has(code: string): boolean
	{
		return this.#pages.has(code);
	}

	current(): ?Page
	{
		return this.get(this.#current);
	}

	getCurrentCode(): ?string
	{
		return this.#current;
	}

	prev(): ?Page
	{
		if (this.#history.length > 0)
		{
			const code = this.#history[this.#history.length - 1];

			return this.get(code);
		}

		return null;
	}

	add(code: string, page: Page): void
	{
		if (page instanceof Page)
		{
			this.#pages.set(code, page);
		}
	}

	delete(code: string): void
	{
		this.#pages.delete(code);
	}
}
