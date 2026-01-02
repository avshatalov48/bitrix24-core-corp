import { Extension } from 'main.core';
import { Api } from 'sign.v2.api';
import type { LoadedDocumentData, TemplateCreatedDocument } from 'sign.v2.api';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { UserPartyApp } from './app';
import { UserParty } from 'sign.v2.b2e.user-party';
import { useDocumentTemplateFillingStore } from 'sign.v2.b2e.sign-settings-templates';

import './style.css';

export class DocumentTemplateUserParty
{
	#app: VueCreateAppResult | null;
	#vueApp: Object | null;
	#container: HTMLElement;
	#userParty: UserParty;
	#store: Object;
	#api: Api;

	constructor(store: Object)
	{
		const b2eSignersLimitCount = this.#getB2eSignersCountLimit();
		const region = this.#getRegion();

		this.#store = store;
		this.#userParty = new UserParty({ mode: 'edit', b2eSignersLimitCount, region });
		this.#api = new Api();
	}

	#createApp(container: HTMLElement): void
	{
		this.#app = BitrixVue.createApp(UserPartyApp, {
			userParty: this.#userParty,
			region: this.#getRegion(),
		});
		this.#app.use(this.#store);
		this.#vueApp = this.#app.mount(container);
	}

	async syncMembers(): Promise<void>
	{
		const documentStore = useDocumentTemplateFillingStore();
		const storeDocuments = documentStore.createdDocuments;
		const ids = storeDocuments.map((value: TemplateCreatedDocument) => value.document.id);

		const {
			shouldCheckDepartmentsSync,
			documents,
		} = await this.#api.template.setupSigners(
			ids,
			this.#userParty.getEntities(),
			this.#userParty.isRejectExcludedEnabled(),
		);

		this.#updatePartiesCountInStore(documents); // can rid of this if make syncDepartmentForSigners method
		if (shouldCheckDepartmentsSync)
		{
			await this.#waitForDepartmentSync();
		}
	}

	#updatePartiesCountInStore(documents: LoadedDocumentData[]): void
	{
		useDocumentTemplateFillingStore()
			.createdDocuments
			.forEach((templateCreatedDocument: TemplateCreatedDocument) => {
				const storeDocument = templateCreatedDocument.document;
				const id = storeDocument.id;
				const document = documents.find(
					(value: LoadedDocumentData) => value.id === id,
				);
				if (!document)
				{
					throw new Error('Created document not found in update parties documents');
				}

				storeDocument.parties = document.parties;
			});
	}

	async #waitForDepartmentSync(): Promise<void>
	{
		const createdDocuments: TemplateCreatedDocument[] = useDocumentTemplateFillingStore().createdDocuments;

		const syncMemberPromises = createdDocuments.map(
			(value: TemplateCreatedDocument) => this.#syncMembersWithDepartments(value.document.uid, value.document.parties),
		);

		await Promise.all(syncMemberPromises);
	}

	async #syncMembersWithDepartments(uid: string, signerParty: number): Promise<void>
	{
		let syncFinished = false;
		while (!syncFinished)
		{
			// eslint-disable-next-line no-await-in-loop
			const response = await this.#api.syncB2eMembersWithDepartments(
				uid,
				signerParty,
				this.#userParty.isRejectExcludedEnabled(),
			);
			syncFinished = response.syncFinished;
			// eslint-disable-next-line no-await-in-loop
			await this.#sleep(1000);
		}
	}

	#sleep(ms: Number): Promise
	{
		return new Promise((resolve) => {
			setTimeout(resolve, ms);
		});
	}

	getLayout(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = BX.Tag.render`<div></div>`;
		this.#createApp(this.#container);

		return this.#container;
	}

	validate(): boolean
	{
		if (!this.#userParty.validate())
		{
			return false;
		}

		const limit = this.#getB2eSignersCountLimit();
		if (limit > 0 && this.#userParty.getUniqueUsersCount() > limit)
		{
			top.BX.UI.InfoHelper.show('limit_office_e_signature');

			return false;
		}

		return true;
	}

	#getB2eSignersCountLimit(): number | null
	{
		return Extension.getSettings('sign.v2.b2e.document-template-user-party').get('signersLimitCount');
	}

	#getRegion(): string
	{
		return Extension.getSettings('sign.v2.b2e.document-template-user-party').get('region');
	}

	unmount(): void
	{
		this.closeCounterGuide();
		this.#app?.unmount();
	}

	closeCounterGuide(): void
	{
		this.#userParty.closeCounterGuide();
	}

	isRejectExcludedEnabled(): boolean
	{
		return this.#userParty.isRejectExcludedEnabled();
	}
}
