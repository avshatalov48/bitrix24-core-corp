import { DocumentTypeSelector } from './document-type-selector';
import { HcmLinkDocumentTypeSelector } from './hcmlink-document-type-selector';
import { RegistrationNumberSettings } from './registration-number-settings';
import { DateSettings } from './date-settings';
import { DocumentPreview } from '../../../vue-util/src/components/document-preview/document-preview';
import { LocMixin } from 'sign.v2.b2e.vue-util';

import type {
	DateSettings as DateSettingsType, DocumentSettings,
	ExternalIdSettings,
} from '../types';

// @vue/component
export const DocumentRegionalSettings = {
	name: 'DocumentRegionalSettings',
	components: {
		DocumentTypeSelector,
		HcmLinkDocumentTypeSelector,
		RegistrationNumberSettings,
		DateSettings,
		DocumentPreview,
	},
	mixins: [LocMixin],
	props: {
		templateMode: {
			type: Boolean,
			required: false,
			default: false,
		},
		documentTypeList: {
			type: Array,
			required: true,
		},
		externalIdTypeList: {
			type: Array,
			required: true,
		},
		dateTypeList: {
			type: Array,
			required: true,
		},
		hcmLinkDocumentTypeList: {
			type: Array,
			required: true,
		},
		previewSrc: {
			type: [String, null],
			required: false,
			default: null,
		},
		documentId: {
			type: Number,
			required: false,
			default: 0,
		},
		uid: {
			type: [String, null],
			required: false,
			default: null,
		},
		title: {
			type: [String, null],
			required: false,
			default: null,
		},
		isPreviewVisible: {
			type: Boolean,
			required: false,
			default: true,
		},
		settings: {
			/** @type DocumentSettings */
			type: Object,
			required: true,
		},
		isIntegrationEnabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		isHcmLinkDocumentTypeVisible: {
			type: Boolean,
			required: false,
			default: true,
		},
		isDocumentTypeVisible: {
			type: Boolean,
			required: false,
			default: true,
		},
		isRegistrationNumberVisible: {
			type: Boolean,
			required: false,
			default: true,
		},
		isDateVisible: {
			type: Boolean,
			required: false,
			default: true,
		},
		isIntegrationDisabledByProvider: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data(): Object
	{
		return {
			currentValue: {},
		};
	},
	watch: {
		isIntegrationEnabled(newVal: boolean): void
		{
			if (newVal === false)
			{
				this.currentValue.hcmLinkDocumentTypeSettingId = 0;

				return;
			}

			this.setDefaultValues(this.settings);
		},
	},
	created(): void
	{
		this.setDefaultValues(this.settings);
	},
	methods: {
		modifyDocumentType(code: string): void
		{
			this.currentValue.documentType = code;
			this.$emit('on-change-document-type', this.currentValue, this.uid);
			this.$emit('on-change', this.currentValue, this.uid);
		},
		modifyDateSettings(settings: DateSettingsType): void
		{
			this.currentValue.date = settings;
			this.$emit('on-change-date-created', this.currentValue, this.uid);
			this.$emit('on-change', this.currentValue, this.uid);
		},
		modifyRegistrationNumberSettings(settings: ExternalIdSettings): void
		{
			this.currentValue.externalId = settings;
			this.$emit('on-change-registration-number', this.currentValue, this.uid);
			this.$emit('on-change', this.currentValue, this.uid);
		},
		modifyHcmLinkDocumentType(id: number | null): void
		{
			this.currentValue.hcmLinkDocumentTypeSettingId = id;
			this.$emit('on-change-hcm-link-document-type', this.currentValue, this.uid);
			this.$emit('on-change', this.currentValue, this.uid);
		},
		validate(): boolean
		{
			if (!this.isRegistrationNumberVisible)
			{
				return true;
			}

			return this.$refs.registrationNumberSettings.validate();
		},
		setDefaultValues(settings: DocumentSettings): void
		{
			this.currentValue = this.getDefaultValues(settings);
			this.$emit('on-change', this.currentValue, this.uid);
		},
		getDefaultValues(settings: DocumentSettings): DocumentSettings
		{
			return {
				date: this.getDateDefaultValue(settings),
				externalId: this.getExternalIdDefaultValue(settings),
				hcmLinkDocumentTypeSettingId: this.getHcmLinkDocumentTypeSettingIdDefaultValue(settings),
				documentType: this.getDocumentTypeDefaultValue(settings),
			};
		},
		getHcmLinkDocumentTypeSettingIdDefaultValue(settings: DocumentSettings): number | null
		{
			return settings.hcmLinkDocumentTypeSettingId ?? null;
		},
		getDocumentTypeDefaultValue(settings: DocumentSettings): string | null
		{
			return settings.documentType ?? this.documentTypeList[0]?.code;
		},
		getExternalIdDefaultValue(settings: DocumentSettings): ExternalIdSettings
		{
			return {
				sourceType: settings.externalId.sourceType,
				value: settings.externalId.value ?? this.loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_WITHOUT_NUMBER'),
				hcmLinkSettingId: settings.externalId.hcmLinkSettingId ?? this.externalIdTypeList[0]?.id,
			};
		},
		getDateDefaultValue(settings: DocumentSettings): DateSettings
		{
			return {
				sourceType: settings.date.sourceType,
				value: settings.date.value ?? new Date(),
				hcmLinkSettingId: settings.date.hcmLinkSettingId ?? this.dateTypeList[0]?.id,
			};
		},
	},
	template: `
		<div :class="{'sign-b2e-regional-settings__step_settings_wrapper': uid}" v-if="currentValue" >
			<div>
				<DocumentPreview
					v-if="isPreviewVisible"
					:title="title ?? ''"
					:preview-src="previewSrc ?? ''"
					:document-id="documentId"
					:is-icon-visible="true"
					class="sign-b2e_document_preview_container"
					preview-image-class="sign-b2e_document_preview_image">
				</DocumentPreview>
			</div>
			<div class="sign-b2e-regional-settings-gap">
				<span v-if="title" class="sign-b2e-regional-settings-company_selector_title">
					{{ title }}
				</span>
				<HcmLinkDocumentTypeSelector v-if="isHcmLinkDocumentTypeVisible && hcmLinkDocumentTypeList.length > 0 && isIntegrationEnabled"
					:typeList="hcmLinkDocumentTypeList"
					:selectedId="currentValue.hcmLinkDocumentTypeSettingId"
					:is-integration-enabled="isIntegrationEnabled"
					@onSelected="modifyHcmLinkDocumentType"
				/>
				<DocumentTypeSelector v-if="isDocumentTypeVisible && documentTypeList.length > 0"
					:typeList="documentTypeList"
					:selectedId="currentValue.documentType"
					@onSelected="modifyDocumentType"
				/>
				<RegistrationNumberSettings
					v-if="isRegistrationNumberVisible"
					ref="registrationNumberSettings"
					:hcmLinkTypeList="externalIdTypeList"
					:templateMode="templateMode"
					:settings="currentValue.externalId"
					:is-integration-enabled="isIntegrationEnabled"
					:is-integration-disabled-by-provider="isIntegrationDisabledByProvider"
					@onChange="modifyRegistrationNumberSettings"
				/>
				<DateSettings
					v-if="isDateVisible"
					:hcmLinkTypeList="dateTypeList"
		    		:templateMode="templateMode"
					:settings="currentValue.date"
					:is-integration-enabled="isIntegrationEnabled"
					:is-integration-disabled-by-provider="isIntegrationDisabledByProvider"
					@onChange="modifyDateSettings"
				/>
			</div>
		</div>
	`,
};
