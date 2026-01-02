import { defineStore } from 'ui.vue3.pinia';
import type { LoadedDocumentData, TemplateCreatedDocument } from 'sign.v2.api';
import type { DocumentSettingsByTemplateDocumentUid } from './types';

export const useDocumentTemplateFillingStore = defineStore('sign-b2e-document-template-filling-store', {
	state: (): {
		documents: LoadedDocumentData[],
		settings: DocumentSettingsByTemplateDocumentUid,
		createdDocuments: TemplateCreatedDocument[],
		sendProgress: number,
		configured: boolean,
		ruRegionFieldsVisible: boolean,
	} => ({
		documents: [],
		settings: {},
		createdDocuments: [],
		sendProgress: 0,
		configured: false,
		ruRegionFieldsVisible: false,
	}),
	actions: {
		setDocuments(documents: LoadedDocumentData[]): void
		{
			this.documents = documents;
		},
		setSettings(settings: DocumentSettingsByTemplateDocumentUid): void
		{
			this.settings = settings;
		},
		setCreatedDocuments(documents: TemplateCreatedDocument[]): void
		{
			this.createdDocuments = documents;
		},
		setSendProgress(value: number): void
		{
			this.sendProgress = value;
		},
		setConfigured(value: boolean): void
		{
			this.configured = value;
		},
		setRuRegionFieldsVisible(value: boolean): void
		{
			this.ruRegionFieldsVisible = value;
		},
		removeDocument(uid: string): void
		{
			this.setDocuments(this.documents.filter((doc) => doc.uid !== uid));

			const updatedSettings = { ...this.settings };
			delete updatedSettings[uid];
			this.setSettings(updatedSettings);
		},
	},
});
