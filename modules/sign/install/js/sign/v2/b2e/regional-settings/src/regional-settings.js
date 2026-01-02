import { Tag } from 'main.core';
import type { RegionalSettingsOptions } from './types';

import './style.css';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { RegionalSettingsApp } from './app';
import { EventEmitter } from 'main.core.events';
import { createPinia } from 'ui.vue3.pinia';
import { useRegionalSettingsStore } from './store';
import type { DocumentDetails } from 'sign.v2.document-setup';

export class RegionalSettings
{
	#app: VueCreateAppResult | null;
	#vueApp: Object | null;
	#options: RegionalSettingsOptions;
	#companyId: number | null;
	#container: HTMLElement;

	constructor(regionalSettingsOptions: RegionalSettingsOptions)
	{
		this.#options = regionalSettingsOptions;
		this.#container = this.getLayout();
	}

	getLayout(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`<div></div>`;

		this.#createApp(this.#container);

		if (BX.UI.Hint)
		{
			BX.UI.Hint.init(this.#container);
		}

		return this.#container;
	}

	#createApp(container: HTMLElement): void
	{
		const store = createPinia();
		this.#app = BitrixVue.createApp(RegionalSettingsApp, {
			templateMode: this.#options.templateMode,
		});

		this.#app.use(store);
		useRegionalSettingsStore().init({
			documentTypeList: this.#options.regionDocumentTypes,
		});

		this.#vueApp = this.#app.mount(container);

		const onClose = () => {
			EventEmitter.unsubscribe('SidePanel.Slider:onCloseByEsc', onClose);
			EventEmitter.unsubscribe('SidePanel.Slider:onClose', onClose);
			this.#app?.unmount();
		};
		EventEmitter.subscribe('SidePanel.Slider:onCloseByEsc', onClose);
		EventEmitter.subscribe('SidePanel.Slider:onClose', onClose);
	}

	async save(): void
	{
		if (!this.#vueApp.validate())
		{
			throw new Error('Validation error');
		}
		await useRegionalSettingsStore().save();
	}

	set companyId(id: number): void
	{
		this.#companyId = id;

		const store = useRegionalSettingsStore();
		store.companyId = this.#companyId;
	}

	set isIntegrationEnabled(isEnabled: boolean): void
	{
		const store = useRegionalSettingsStore();
		store.isIntegrationEnabled = isEnabled;
	}

	set isIntegrationVisible(isVisible: boolean): void
	{
		const store = useRegionalSettingsStore();
		store.isIntegrationVisible = isVisible;
	}

	set documentsGroup(documentGroup: Map<string, DocumentDetails>): void
	{
		useRegionalSettingsStore().updateDocumentsGroup(documentGroup);
	}

	getSelectedHcmLinkCompanyId(): number | null
	{
		const store = useRegionalSettingsStore();

		return store.hcmLinkCompanyId;
	}

	setLastSavedHcmLinkCompanyId(id: number | null): void
	{
		/* @todo */
		//this.#hcmLinkCompanySelector.setLastSavedId(id);
	}
}
