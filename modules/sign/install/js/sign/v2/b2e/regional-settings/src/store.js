import { DateTimeFormat } from 'main.date';
import { defineStore } from 'ui.vue3.pinia';
import type { DocumentSettings } from './types';
import type { DocumentDetails } from 'sign.v2.document-setup';
import { Api } from 'sign.v2.api';

export type RegionalSettingsStoreState = {
	companyId: null | number,
	isIntegrationEnabled: boolean,
	isIntegrationVisible: boolean,
	hcmLinkCompanyId: null,
	hcmLinkAvailableSettings: {
		documentTypeList: Array<{ id: number, title: string }>,
		externalIdTypeList: Array<{ id: number, title: string }>,
		dateTypeList: Array<{ id: number, title: string }>,
	},
	documentTypeList: Array<{ code: string, description: string }>,
	documentSettingsMap: Map<string, DocumentSettings>,
	currentDocumentUid: string | null,
	documentsGroup: Map<string, DocumentDetails>,
};

export type RegionalSettingsStoreInitOptions = {
	documentTypeList: Array<{ id: string, title: string }>
}

export const DefaultDocumentSettings: DocumentSettings = {
	date: {
		sourceType: 'manual',
		value: new Date(),
		hcmLinkSettingId: null,
	},
	externalId: {
		sourceType: 'manual',
		value: null,
		hcmLinkSettingId: null,
	},
	hcmLinkDocumentTypeSettingId: null,
	documentType: null,
};

export const useRegionalSettingsStore = defineStore('sign-b2e-regional-settings-store', {
	state: (): RegionalSettingsStoreState => ({
		currentDocumentUid: null,
		companyId: null,
		isIntegrationEnabled: false,
		isIntegrationVisible: true,
		documentTypeList: [],
		hcmLinkCompanyId: null,
		hcmLinkAvailableSettings: {
			documentTypeList: [],
			externalIdTypeList: [],
			dateTypeList: [],
		},
		documentSettingsMap: new Map(),
		documentsGroup: new Map(),
	}),
	getters: {
		currentDocumentSettings(state): DocumentSettings
		{
			if (!state.currentDocumentUid || !state.documentSettingsMap.has(state.currentDocumentUid))
			{
				return DefaultDocumentSettings;
			}

			return state.documentSettingsMap.get(state.currentDocumentUid);
		},
	},
	actions: {
		init(options: RegionalSettingsStoreInitOptions): void
		{
			this.documentTypeList = options.documentTypeList;
		},
		updateDocumentsGroup(documentsGroup: Map<string, DocumentDetails>): void
		{
			const documentsToDelete = [...this.documentsGroup.keys()].filter((key) => !documentsGroup.has(key));
			documentsToDelete.forEach((key): void => {
				this.documentsGroup.delete(key);
				this.documentSettingsMap.delete(key);
			});

			for (const [uid, documentDetail] of documentsGroup)
			{
				const documentDetailPrevious = this.documentsGroup.has(uid) ? this.documentsGroup.get(uid) : {};

				this.documentsGroup.set(uid, {
					...documentDetailPrevious,
					...documentDetail,
				});
				if (this.documentSettingsMap.has(uid))
				{
					continue;
				}

				const settings = {
					date: {
						sourceType: documentDetail.externalDateCreateSourceType,
						hcmLinkSettingId: documentDetail.hcmLinkDateSettingId,
						value: documentDetail.externalDateCreate ? new Date(documentDetail.externalDateCreate) : new Date(),
					},
					externalId: {
						sourceType: documentDetail.externalIdSourceType,
						hcmLinkSettingId: documentDetail.hcmLinkExternalIdSettingId,
						value: documentDetail.externalId,
					},
					hcmLinkDocumentTypeSettingId: documentDetail.hcmLinkDocumentTypeSettingId,
					documentType: documentDetail.regionDocumentType,
				};

				this.documentSettingsMap.set(uid, settings);
				if (this.hcmLinkCompanyId === null)
				{
					this.modifyHcmLinkCompanyId(documentDetail.hcmLinkCompanyId);
				}
			}

			if (documentsToDelete.includes(this.currentDocumentUid))
			{
				this.currentDocumentUid = null;
			}

			const documentsToAdd = [...documentsGroup.keys()].filter((key) => !this.documentSettingsMap.has(key));
			documentsToAdd.forEach((key) => {
				this.documentSettingsMap.set(key, {
					...DefaultDocumentSettings,
					documentType: this.documentTypeList[0].code ?? null,
				});
			});

			if (this.currentDocumentUid === null)
			{
				this.currentDocumentUid = documentsToAdd[0] ?? null;
			}
		},
		modifyHcmLinkCompanyId(id: number | null): void
		{
			if (id === null)
			{
				for (const [uid, settings] of this.documentSettingsMap)
				{
					this.documentSettingsMap.set(uid, {
						...settings,
						date: {
							...settings.date,
							sourceType: 'manual',
							hcmLinkSettingId: null,
						},
						externalId: {
							...settings.externalId,
							sourceType: 'manual',
							hcmLinkSettingId: null,
						},
						hcmLinkDocumentTypeSettingId: null,
					});
				}
			}
			this.hcmLinkCompanyId = id;
		},
		modifyAvailableHcmLinkSettings(settings: RegionalSettingsStoreState['hcmLinkAvailableSettings']): void
		{
			this.hcmLinkAvailableSettings = settings;
			const documentUids = [...this.documentSettingsMap.keys()];

			documentUids.forEach((uid: string) => {
				if (!this.documentSettingsMap.has(uid))
				{
					return;
				}

				const stored = this.documentSettingsMap.get(uid);
				if (!this.documentsGroup.has(uid))
				{
					return;
				}

				const documentDetails = this.documentsGroup.get(uid);

				if (documentDetails.hcmLinkCompanyId === this.hcmLinkCompanyId)
				{
					return;
				}

				this.documentSettingsMap.set(uid, {
					...stored,
					date: {
						...stored.date,
						hcmLinkSettingId: settings?.dateTypeList[0]?.id ?? null,
					},
					externalId: {
						...stored.externalId,
						hcmLinkSettingId: settings?.externalIdTypeList[0]?.id ?? null,
					},
					hcmLinkDocumentTypeSettingId: null,
				});
			});
		},
		modifyCurrentDocumentSettings(settings: Partial<DocumentSettings>): void
		{
			if (!this.currentDocumentUid)
			{
				return;
			}

			const storedSettings = this.documentSettingsMap.get(this.currentDocumentUid);
			this.documentSettingsMap.set(this.currentDocumentUid, {
				...storedSettings,
				...settings,
			});
		},
		setDocumentSettings(uid: string, settings: DocumentSettings): void
		{
			if (!uid)
			{
				return;
			}

			if (!this.documentSettingsMap.has(uid))
			{
				return;
			}

			const storedSettings = this.documentSettingsMap.get(uid);
			this.documentSettingsMap.set(uid, {
				...storedSettings,
				...settings,
			});
		},
		async save(): Promise<void>
		{
			const api = new Api();

			const documentUids = [...this.documentSettingsMap.keys()];

			await Promise.all(documentUids.map((uid: string) => {
				return api.changeIntegrationId(uid, this.hcmLinkCompanyId);
			}));

			await Promise.all(documentUids.map((uid: string) => {
				const { documentType, externalId, date, hcmLinkDocumentTypeSettingId } = this.documentSettingsMap.get(uid);
				let dateValue = date.value;
				if (date.value)
				{
					dateValue = DateTimeFormat.format(DateTimeFormat.getFormat('SHORT_DATE_FORMAT'), date.value);
				}

				const apiPromises = [
					api.changeRegionDocumentType(uid, documentType),
				];

				if (this.isIntegrationVisible)
				{
					apiPromises.push(
						api.changeHcmLinkDocumentType(uid, hcmLinkDocumentTypeSettingId),
						api.changeExternalDate(uid, dateValue, date.sourceType, date.hcmLinkSettingId),
						api.changeExternalId(uid, externalId.value, externalId.sourceType, externalId.hcmLinkSettingId),
					);
				}

				return Promise.all(apiPromises);
			}));
		},
	},
});
