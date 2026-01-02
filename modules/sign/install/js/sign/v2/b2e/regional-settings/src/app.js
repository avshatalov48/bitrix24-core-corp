import { Api } from 'sign.v2.api';
import type { DocumentDetails } from 'sign.v2.document-setup';
import { mapState } from 'ui.vue3.pinia';

import { LocMixin, Notice } from 'sign.v2.b2e.vue-util';
import { DefaultDocumentSettings, useRegionalSettingsStore } from './store';

import { DocumentTypeSelector } from './components/document-type-selector';
import { HcmLinkDocumentTypeSelector } from './components/hcmlink-document-type-selector';
import { RegistrationNumberSettings } from './components/registration-number-settings';
import { DateSettings } from './components/date-settings';
import { CompanySelector } from './components/company-selector';
import { DocumentPreview } from '../../vue-util/src/components/document-preview/document-preview';
import { DocumentRegionalSettings } from './components/document-regional-settings';
import { Switcher } from '../../vue-util/src/components/switcher/switcher';
import { DocumentPreviewList } from '../../vue-util/src/components/document-preview-list/document-preview-list';
import type { DocumentSettings } from './types';

const maxPreviewCount = 7;

// @vue/component
export const RegionalSettingsApp = {
	name: 'RegionalSettingsApp',
	components: {
		DocumentTypeSelector,
		HcmLinkDocumentTypeSelector,
		RegistrationNumberSettings,
		DateSettings,
		DocumentRegionalSettings,
		CompanySelector,
		DocumentPreview,
		DocumentPreviewList,
		Notice,
		Switcher,
	},
	mixins: [LocMixin],
	props: {
		templateMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data(): Object
	{
		return {
			selectedCompanyId: 0,
			isApplySettingsForAll: false,
			previewCount: maxPreviewCount,
			api: null,
			previewUrlList: {},
		};
	},
	computed: {
		...mapState(useRegionalSettingsStore, ['companyId']),
		...mapState(useRegionalSettingsStore, ['currentDocumentSettings']),
		...mapState(useRegionalSettingsStore, ['hcmLinkCompanyId']),
		...mapState(useRegionalSettingsStore, ['hcmLinkAvailableSettings']),
		...mapState(useRegionalSettingsStore, ['documentTypeList']),
		...mapState(useRegionalSettingsStore, ['documentSettingsMap']),
		...mapState(useRegionalSettingsStore, ['documentsGroup']),
		...mapState(useRegionalSettingsStore, ['isIntegrationEnabled']),
		...mapState(useRegionalSettingsStore, ['isIntegrationVisible']),
		showMoreCount(): number
		{
			return this.documentCount - maxPreviewCount;
		},
		isShowMoreVisible(): boolean
		{
			return this.showMoreCount > 0;
		},
		documentCount(): number
		{
			return this.documentsGroup.size;
		},
		isHcmLinkCompanyIdSet(): boolean
		{
			return Number(this.hcmLinkCompanyId) > 0;
		},
		documentPreviewList(): Array
		{
			const result = [];
			for (const [uid] of this.documentsGroup)
			{
				result.push({
					uid,
					documentId: this.getDocumentId(uid),
					previewUrl: this.getDocumentPreviewUrl(uid),
					title: this.getDocumentTitle(uid),
				});
			}

			return result;
		},
		documentList(): Array
		{
			const result = [];
			for (const [uid, documentDetails] of this.documentsGroup)
			{
				result.push({
					uid,
					id: documentDetails.id,
					previewUrl: this.getDocumentPreviewUrl(uid),
					title: documentDetails.title,
				});
			}

			return result;
		},
		isIntegrationDisabledByProvider(): boolean
		{
			return this.isHcmLinkCompanyIdSet && !this.isIntegrationEnabled;
		},
	},
	watch: {
		companyId(
			newVal: number | null,
			oldVal: number | null,
		): void
		{
			if (newVal !== oldVal)
			{
				this.selectedCompanyId = newVal;
			}
		},
		isIntegrationEnabled(newVal: boolean): void
		{
			if (newVal)
			{
				return;
			}

			useRegionalSettingsStore().modifyAvailableHcmLinkSettings({
				documentTypeList: [],
				externalIdTypeList: [],
				dateTypeList: [],
			});
		},
		documentCount(newVal: number): void
		{
			if (newVal < 2)
			{
				this.isApplySettingsForAll = false;
			}
		},
	},
	created(): void
	{
		this.api = new Api();
		this.selectedCompanyId = this.companyId;
		this.previewUrlList = new Map();
	},
	methods: {
		getDocumentSettings(uid: string | null = null): DocumentSettings | null
		{
			if (!uid || !this.documentSettingsMap.has(uid))
			{
				return DefaultDocumentSettings;
			}

			return this.documentSettingsMap.get(uid);
		},
		getDocumentDetails(uid: string | null = null): DocumentDetails | null
		{
			if (!uid || !this.documentsGroup.has(uid))
			{
				return null;
			}

			return this.documentsGroup.get(uid);
		},
		getDocumentId(uid: string): number
		{
			const documentDetails = this.getDocumentDetails(uid);
			if (documentDetails === null)
			{
				return 0;
			}

			return documentDetails.id;
		},
		async loadDocumentPreviewUrl(uid: string): void
		{
			if (this.previewUrlList.has(uid))
			{
				return;
			}

			this.previewUrlList.set(uid, '');
			const data = await this.api.getDocumentPreviewUrl(uid);
			this.previewUrlList.set(uid, data?.url);
		},
		getDocumentPreviewUrl(uid: string): string | null
		{
			const documentDetails = this.getDocumentDetails(uid);
			if (documentDetails === null)
			{
				return null;
			}

			if (documentDetails.previewUrl)
			{
				return documentDetails.previewUrl;
			}

			this.loadDocumentPreviewUrl(uid);

			if (!this.previewUrlList.has(uid))
			{
				return null;
			}

			return this.previewUrlList.get(uid);
		},
		getDocumentTitle(uid: string): string
		{
			const documentDetails = this.getDocumentDetails(uid);
			if (documentDetails === null)
			{
				return '';
			}

			return documentDetails.title;
		},
		onCompanySelectorChange(
			id: Number | null,
			documentTypeList: Array,
			externalIdTypeList: Array,
			dateTypeList: Array,
		): void
		{
			useRegionalSettingsStore().modifyHcmLinkCompanyId(id);
			useRegionalSettingsStore().modifyAvailableHcmLinkSettings({
				documentTypeList,
				externalIdTypeList,
				dateTypeList,
			});
		},
		setAllDocumentSettings(settings: DocumentSettings): void
		{
			for (const uid of this.documentSettingsMap.keys())
			{
				useRegionalSettingsStore().setDocumentSettings(uid, settings);
			}
		},
		setSettings(settings: DocumentSettings, uid: string | null): void
		{
			if (this.isApplySettingsForAll)
			{
				this.setAllDocumentSettings(settings);

				return;
			}

			useRegionalSettingsStore().setDocumentSettings(uid, settings);
		},
		validate(): boolean
		{
			let result = true;

			if (this.isApplySettingsForAll)
			{
				return this.$refs.applySettingsForAll.validate();
			}

			for (const uid of this.documentSettingsMap.keys())
			{
				if (!this.$refs[`document_settings_${uid}`][0]?.validate())
				{
					if (result === true && this.documentCount > 1)
					{
						document.getElementById(`document_settings_${uid}`).scrollIntoView({ behavior: 'smooth' });
					}
					result = false;
				}
			}

			return result;
		},
	},
	template: `
		<h1 class="sign-b2e-settings__header">
			{{ loc('SIGN_SETTINGS_B2E_REGIONAL_SETTINGS') }}
		</h1>
		<div v-if="isIntegrationVisible" class="sign-b2e-settings__item">
			<div class="sign-b2e-settings__counter">
				<span class="sign-b2e-settings__counter_num" data-num="1"></span>
			</div>
			<CompanySelector 
				:is-enabled="true" 
				:is-checked="isHcmLinkCompanyIdSet" 
				@onChange="onCompanySelectorChange" 
				:company-id="Number(selectedCompanyId)"
			/>
		</div>
		<div v-if="documentCount > 1 && isIntegrationVisible" class="sign-b2e-settings__item">
			<div class="sign-b2e-settings__counter">
				<span class="sign-b2e-settings__counter_num" :data-num="2"></span>
				<span v-if="isApplySettingsForAll" class="sign-b2e-settings__counter_connect"></span>
			</div>
			<Switcher 
				v-model="isApplySettingsForAll"
				:title="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_APPLY_FOR_ALL_TITLE')"
				:hint="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_APPLY_FOR_ALL_HINT')"
			/>
			<div v-if="isApplySettingsForAll">
				<div class="sign-b2e-regional-settings__item-text sign-b2e-regional-settings_apply_for_all_title">
					{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_APPLY_FOR_ALL_DOCUMENTS_TITLE') }}
				</div>
				<DocumentPreviewList 
					:document-list="documentPreviewList"
					:max-preview-count="maxPreviewCount"
				/>
			</div>
			<DocumentRegionalSettings
				v-if="isApplySettingsForAll"
				ref="applySettingsForAll"
				:is-preview-visible="false"
				:template-mode="templateMode"
				:document-type-list="documentTypeList"
				:external-id-type-list="hcmLinkAvailableSettings.externalIdTypeList"
				:date-type-list="hcmLinkAvailableSettings.dateTypeList"
				:hcm-link-document-type-list="hcmLinkAvailableSettings.documentTypeList"
				:settings="getDocumentSettings()"
				@onChange="setSettings"
				class="sign-b2e-regional-settings_apply_for_all_settings"
				:is-integration-enabled="isIntegrationEnabled && isHcmLinkCompanyIdSet"
				:is-integration-disabled-by-provider="isIntegrationDisabledByProvider"
			/>
		</div>
		<template v-if="!isApplySettingsForAll" v-for="(document, index) in documentList" :key="document.uid">
			<div class="sign-b2e-settings__item">
				<div class="sign-b2e-settings__counter"  :class="{'sign-b2e-regional-settings__counter_connect_with_margin': index > 0 && index < (documentCount - 1), 'sign-b2e-regional-settings__counter_connect_with_left': index === (documentCount - 1)}">
					<span v-if="index === 0" class="sign-b2e-settings__counter_num" :data-num="isIntegrationVisible ? (index + (documentCount > 1 ? 3 : 2)) : 1"></span>
					<span v-if="index === (documentCount - 1)" 
						  class="sign-b2e-settings__counter_connect" 
						  :class="{'sign-b2e-regional-settings__counter_connect': index > 0}">
					</span>
				</div>
				<DocumentRegionalSettings
					:ref="'document_settings_' + document.uid"
					:id="'document_settings_' + document.uid"
					:document-id="document.id"
					:preview-src="document.previewUrl"
					:template-mode="templateMode"
					:document-type-list="documentTypeList"
					:external-id-type-list="hcmLinkAvailableSettings.externalIdTypeList"
					:date-type-list="hcmLinkAvailableSettings.dateTypeList"
					:hcm-link-document-type-list="hcmLinkAvailableSettings.documentTypeList"
					:uid="document.uid"
					:title="document.title"
					:settings="getDocumentSettings(document.uid)"
					@onChange="setSettings"
					:is-integration-enabled="isIntegrationEnabled && isHcmLinkCompanyIdSet"
					:is-hcm-link-document-type-visible="isIntegrationVisible"
					:is-registration-number-visible="isIntegrationVisible"
					:is-date-visible="isIntegrationVisible"
					:is-integration-disabled-by-provider="isIntegrationDisabledByProvider"
				/>
			</div>
		</template>
	`,
};
