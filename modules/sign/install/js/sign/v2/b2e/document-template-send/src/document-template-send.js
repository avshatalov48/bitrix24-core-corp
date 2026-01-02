import { EventEmitter } from 'main.core.events';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { TemplateSendApp } from './app';

import './style.css';

export class DocumentTemplateSend extends EventEmitter
{
	#app: VueCreateAppResult | null;
	#vueApp: Object | null;
	#container: HTMLElement;
	#store: Object;

	constructor(store: Object)
	{
		super();
		this.setEventNamespace('BX.V2.B2e.DocumentTemplateSend');
		this.#store = store;
	}

	#createApp(container: HTMLElement): void
	{
		this.#app = BitrixVue.createApp(TemplateSendApp, {});
		this.#app.use(this.#store);
		this.#vueApp = this.#app.mount(container);
		this.#vueApp.$Bitrix.eventEmitter.subscribe('sign:document-template-send:close', () => this.emit('close'));
	}

	getLayout(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = BX.Tag.render`<div class="sign-settings_templates_send"></div>`;
		this.#createApp(this.#container);

		return this.#container;
	}

	unmount(): void
	{
		this.#app.unmount();
	}
}
