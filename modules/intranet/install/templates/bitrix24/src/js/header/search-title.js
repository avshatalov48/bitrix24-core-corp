import { Event, Runtime, Dom, Type, Reflection, type JsonObject } from 'main.core';

export class SearchTitle
{
	#searchOptions: JsonObject = {};
	#extensionLoaded: boolean = false;
	#container: HTMLElement = null;
	#button: HTMLElement = null;
	#input: HTMLInputElement = null;
	#searchTitleInstance = null;

	constructor(options)
	{
		this.#container = document.getElementById(options.containerId);
		this.#button = document.getElementById(options.buttonId);
		this.#input = document.getElementById(options.inputId);

		this.#searchOptions = options.searchOptions;

		Event.bind(this.#button, 'click', this.#handleButtonClick.bind(this));
		Event.bind(this.#input, 'focusout', this.#handleInputFocusOut.bind(this));
	}

	open(): void
	{
		Dom.addClass(this.#container, '--active');

		this.#input.disabled = false;

		setTimeout(() => {
			this.#input.focus();
		}, 200);
	}

	close(): void
	{
		Dom.removeClass(this.#container, '--active');
		this.#input.disabled = true;

		if (this.#searchTitleInstance !== null)
		{
			this.#searchTitleInstance.clearSearch();
			this.#searchTitleInstance.closeResult();
		}
	}

	#handleButtonClick(): void
	{
		if (Dom.hasClass(this.#container, '--active'))
		{
			this.close();
		}
		else
		{
			this.open();
		}

		if (this.#extensionLoaded)
		{
			return;
		}

		this.#extensionLoaded = true;
		Runtime.loadExtension('intranet.search_title').then(() => {
			const SearchTitleClass = Reflection.getClass('BX.Intranet.SearchTitle');
			this.#searchTitleInstance = new SearchTitleClass(this.#searchOptions);
		}).catch((error) => {
			console.error(error);
		});
	}

	#handleInputFocusOut(event): void
	{
		if (!Type.isStringFilled(this.#input.value) && event.relatedTarget !== this.#button)
		{
			this.close();
		}
	}
}
