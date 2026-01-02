import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { DocumentFillingApp } from './app';
import type { DocumentFillingOptions } from './type';

import './style.css';

export class DocumentTemplateFilling
{
	#app: VueCreateAppResult | null;
	#vueApp: Object | null;
	#container: HTMLElement;
	#options: DocumentFillingOptions;

	constructor(documentFillingOptions: DocumentFillingOptions)
	{
		this.#options = documentFillingOptions;
	}

	#createApp(container: HTMLElement): void
	{
		this.#app = BitrixVue.createApp(DocumentFillingApp);
		this.#app.use(this.#options.store);
		this.#vueApp = this.#app.mount(container);
	}

	getLayout(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = BX.Tag.render`<div></div>`;
		this.#createApp(this.#container);

		if (BX.UI?.Hint)
		{
			BX.UI.Hint.init(this.#container);
		}

		return this.#container;
	}

	validate(): boolean
	{
		return this.#vueApp.validate();
	}

	unmount(): void
	{
		this.#app?.unmount();
	}
}
