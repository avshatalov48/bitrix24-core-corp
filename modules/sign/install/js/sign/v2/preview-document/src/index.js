import { Dom, Type } from 'main.core';
import { Api } from 'sign.v2.api';
import { Preview } from 'sign.v2.preview';

type Options = {
	documentId: string,
	container: HTMLElement
}
export class PreviewDocument
{
	#documentId: Number;
	#container: HTMLElement;
	#preview: Preview;
	#api: Api;

	constructor(options: Options)
	{
		this.#documentId = options.documentId;
		this.#container = options.container;
		this.#preview = new Preview();
		this.#api = new Api();
	}

	async render(): void
	{
		const pagesResponse = await this.#api.getPages(this.#documentId);
		if (Type.isObject(pagesResponse) && Type.isArrayFilled(pagesResponse.pages))
		{
			this.#preview.urls = pagesResponse.pages.map((page) => page.url);
			this.#preview.ready = true;
		}

		Dom.append(this.#preview.getLayout(), this.#container);
	}
}
