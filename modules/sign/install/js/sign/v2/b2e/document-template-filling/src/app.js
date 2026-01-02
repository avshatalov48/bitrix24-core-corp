import { Extension, Loc } from 'main.core';
import { Timezone } from 'main.date';
import { ProviderCode } from 'sign.type';
import { type LoadedDocumentData } from 'sign.v2.api';
import type { DocumentSettings } from 'sign.v2.b2e.sign-settings-templates';
import {
	DocumentSettingsByTemplateDocumentUid,
	useDocumentTemplateFillingStore,
} from 'sign.v2.b2e.sign-settings-templates';
import { DateSelector, LocMixin } from 'sign.v2.b2e.vue-util';
import { mapState } from 'ui.vue3.pinia';
import { DocumentPreviewList } from '../../vue-util/src/components/document-preview-list/document-preview-list';
import { DocumentPreview } from '../../vue-util/src/components/document-preview/document-preview';
import { Switcher } from '../../vue-util/src/components/switcher/switcher';
import type { DocumentPreviewData } from './type';

const ExternalSourceType = {
	HCMLINK: 'hcmlink',
	MANUAL: 'manual',
};
const defaultSigningDateMonth = 3;

// @vue/component
export const DocumentFillingApp = {
	name: 'DocumentFillingApp',
	components: {
		DateSelector,
		DocumentPreview,
		Switcher,
		DocumentPreviewList,
	},
	mixins: [LocMixin],
	data(): {
		isApplySettingsForAll: boolean,
		commonSettings: DocumentSettings,
		documentsSettings: DocumentSettingsByTemplateDocumentUid,
		signUntilDateErrorCommon: string,
		signUntilDateErrorByDoc: Record<string, string>,
		isValid: boolean,
		isValidMap: Record<string, boolean>,
		}
	{
		return {
			isApplySettingsForAll: false,
			commonSettings: null,
			documentsSettings: {},
			signUntilDateErrorByDoc: {},
			signUntilDateErrorCommon: '',
			isValid: true,
			isValidMap: {},
		};
	},
	computed: {
		documents(): LoadedDocumentData[]
		{
			return mapState(useDocumentTemplateFillingStore, ['documents']).documents();
		},
		ruRegionFieldsVisible(): boolean
		{
			return mapState(useDocumentTemplateFillingStore, ['ruRegionFieldsVisible']).ruRegionFieldsVisible();
		},
		documentPreviewList(): DocumentPreviewData[]
		{
			return this.documents.map((doc: LoadedDocumentData) => ({
				uid: doc.uid,
				documentId: this.getSmartDocumentId(doc),
				previewUrl: this.getPreviewUrl(doc),
				title: doc.title,
			}));
		},
		signingMinMinutes(): number
		{
			const settings = Extension.getSettings('sign.v2.b2e.document-template-filling');

			return settings.get('signingMinMinutes', 5);
		},
		signingMaxMonth(): number
		{
			const settings = Extension.getSettings('sign.v2.b2e.document-template-filling');

			return settings.get('signingMaxMonth', 3);
		},
		switcherAllHint(): string
		{
			return this.ruRegionFieldsVisible ? this.loc('SIGN_B2E_DOCUMENT_FILLING_APPLY_FOR_ALL_HINT') : '';
		},
	},
	watch: {
		documents: {
			immediate: true,
			handler(newDocs: LoadedDocumentData[]): void
			{
				const newSettings: DocumentSettingsByTemplateDocumentUid = {};
				newDocs.forEach((doc: LoadedDocumentData) => {
					const defaultRegistrationNumber = this.getDefaultRegistrationNumber(doc);

					newSettings[doc.uid] = {
						...this.makeDefaultDocumentSettings(doc),
						registrationNumber: defaultRegistrationNumber,
					};
				});
				this.documentsSettings = newSettings;
			},
		},
		isApplySettingsForAll(newVal: boolean): void
		{
			if (newVal)
			{
				this.signUntilDateErrorByDoc = {};
				this.signUntilDateErrorCommon = '';
				this.isValid = true;
				this.isValidMap = {};
				this.setStorageSettingsFromCommon();
			}
		},
		documentsSettings: {
			handler(newValue: DocumentSettingsByTemplateDocumentUid): void
			{
				useDocumentTemplateFillingStore().setSettings(newValue);
			},
			immediate: true,
			deep: true,
		},
		commonSettings: {
			handler(): void
			{
				if (this.isApplySettingsForAll)
				{
					this.setStorageSettingsFromCommon();
				}
			},
			deep: true,
		},
	},
	created(): void
	{
		this.commonSettings = this.makeDefaultDocumentSettings();
	},
	methods: {
		removeDocument(uid: string): void
		{
			useDocumentTemplateFillingStore().removeDocument(uid);
		},
		selectCreationDate(uid: string, date: Date): void
		{
			if (this.documentsSettings[uid])
			{
				this.documentsSettings[uid].creationDate = date;
			}
		},
		async selectSigningDate(uid: string, date: Date): Promise<void>
		{
			const isValid = this.validateDateSignUntil(date, uid);
			if (!isValid)
			{
				return;
			}

			if (this.documentsSettings[uid])
			{
				this.documentsSettings[uid].signingDate = this.convertFromUTCtoUserDate(date);
			}
		},
		selectCreationDateForAll(date: Date): void
		{
			this.commonSettings.creationDate = date;
		},
		async selectSigningDateForAll(date: Date): Promise<void>
		{
			const isValid = this.validateDateSignUntil(date);
			if (!isValid)
			{
				return;
			}

			this.commonSettings.signingDate = this.convertFromUTCtoUserDate(date);
		},
		getSmartDocumentId(doc: LoadedDocumentData): number
		{
			return doc?.id ?? 0;
		},
		getPreviewUrl(doc: LoadedDocumentData): string
		{
			return doc?.previewUrl ?? '';
		},
		getCurrentSettings(uid: string): DocumentSettings
		{
			return this.isApplySettingsForAll ? this.commonSettings : this.documentsSettings[uid];
		},
		isExternalIdFromIntegration(doc: LoadedDocumentData): boolean
		{
			return doc.externalIdSourceType === ExternalSourceType.HCMLINK;
		},
		isExternalDateFromIntegration(doc: LoadedDocumentData): boolean
		{
			return doc.externalDateCreateSourceType === ExternalSourceType.HCMLINK;
		},
		areAllExternalIdsFromIntegration(): boolean
		{
			return this.documents.every(
				(doc: LoadedDocumentData) => doc.externalIdSourceType === ExternalSourceType.HCMLINK,
			);
		},
		areAllExternalDatesFromIntegration(): boolean
		{
			return this.documents.every(
				(doc: LoadedDocumentData) => doc.externalDateCreateSourceType === ExternalSourceType.HCMLINK,
			);
		},
		validateDateSignUntil(date: Date, uid?: string): boolean
		{
			if (uid)
			{
				this.signUntilDateErrorByDoc[uid] = '';
			}
			else
			{
				this.signUntilDateErrorCommon = '';
			}

			const creationDateSource = uid
				? this.documentsSettings[uid].creationDate
				: this.commonSettings.creationDate
			;

			let validationErrorMessage = '';

			if (!creationDateSource)
			{
				console.error('Creation date is not available');

				return false;
			}

			const creationDate = new Date(creationDateSource);
			const selectedUserDate = this.convertFromUTCtoUserDate(date);

			const minValidDateTime = new Date(creationDate.getTime());
			minValidDateTime.setMinutes(minValidDateTime.getMinutes() + this.signingMinMinutes);

			if (selectedUserDate.getTime() < minValidDateTime.getTime())
			{
				validationErrorMessage = Loc.getMessagePlural('PERIOD_TOO_SHORT', this.signingMinMinutes, {
					'#MIN_PERIOD#': this.signingMinMinutes,
				});
			}
			else
			{
				const maxValidDate = new Date(creationDate.getTime());
				maxValidDate.setMonth(maxValidDate.getMonth() + this.signingMaxMonth);

				if (selectedUserDate.getTime() > maxValidDate.getTime())
				{
					validationErrorMessage = Loc.getMessagePlural('PERIOD_TOO_LONG', this.signingMaxMonth, {
						'#MAX_PERIOD#': this.signingMaxMonth,
					});
				}
			}

			if (validationErrorMessage)
			{
				if (this.isApplySettingsForAll)
				{
					this.signUntilDateErrorCommon = validationErrorMessage;
				}
				else
				{
					this.signUntilDateErrorByDoc[uid] = validationErrorMessage;
				}

				return false;
			}

			return true;
		},
		makeDefaultDocumentSettings(doc: LoadedDocumentData | null): DocumentSettings
		{
			return {
				registrationNumber: this.getDefaultRegistrationNumber(),
				creationDate: Timezone.UserTime.getDate(),
				signingDate: doc && doc.dateSignUntilUserTime
					? new Date(doc.dateSignUntilUserTime)
					: this.getFallbackSignigningDate()
				,
			};
		},
		getFallbackSignigningDate(): Date
		{
			const signingDate = Timezone.UserTime.getDate();
			signingDate.setMonth(signingDate.getMonth() + defaultSigningDateMonth);

			return signingDate;
		},
		getDefaultRegistrationNumber(doc?: LoadedDocumentData): string
		{
			const shouldUseHcmLinkPlaceholder = doc
				? this.isExternalIdFromIntegration(doc)
				: this.areAllExternalIdsFromIntegration();

			return this.loc(shouldUseHcmLinkPlaceholder
				? 'SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_PLACEHOLDER_HCMLINK'
				: 'SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_PLACEHOLDER');
		},
		setStorageSettingsFromCommon(): void
		{
			const newSettings: DocumentSettingsByTemplateDocumentUid = {};
			this.documents.forEach((doc: LoadedDocumentData) => {
				newSettings[doc.uid] = {
					registrationNumber: this.commonSettings.registrationNumber,
					signingDate: this.commonSettings.signingDate,
					creationDate: this.commonSettings.creationDate,
				};
			});
			useDocumentTemplateFillingStore().setSettings(newSettings);
		},
		validate(): boolean
		{
			if (this.isApplySettingsForAll)
			{
				const value = this.commonSettings.registrationNumber;
				this.isValid = Boolean(value) && value.trim() !== '';

				return this.isValid;
			}

			this.isValid = true;
			this.isValidMap = {};
			for (const doc of this.documents)
			{
				const value = this.documentsSettings[doc.uid]?.registrationNumber;
				const isFilled = Boolean(value) && value.trim() !== '';
				this.isValidMap[doc.uid] = isFilled;

				if (!isFilled)
				{
					this.isValid = false;
				}
			}

			return this.isValid;
		},
		convertFromUTCtoUserDate(dateInUTC: Date): Date
		{
			const year = dateInUTC.getUTCFullYear();
			const month = dateInUTC.getUTCMonth();
			const day = dateInUTC.getUTCDate();
			const hours = dateInUTC.getUTCHours();
			const minutes = dateInUTC.getUTCMinutes();

			return new Date(year, month, day, hours, minutes, 0, 0);
		},
		isGosKeyProvider(doc?: LoadedDocumentData): boolean
		{
			if (doc)
			{
				return doc.providerCode === ProviderCode.goskey;
			}

			return this.documents.some((item: LoadedDocumentData) => item.providerCode === ProviderCode.goskey);
		},
	},
	template: `
		<h1 class="sign-b2e-settings__header">
			{{ loc('SIGN_B2E_DOCUMENT_FILLING_TITLE_HEAD_LABEL') }}
		</h1>

		<template v-if="documents.length > 1">
			<div class="sign-b2e-document-filling__item">
				<Switcher
					v-model="isApplySettingsForAll"
					:title="loc('SIGN_B2E_DOCUMENT_FILLING_APPLY_FOR_ALL_TITLE')"
					:hint="switcherAllHint"
				/>

				<template v-if="isApplySettingsForAll">
					<div class="sign-b2e-document-filling__item-text">
						{{ loc('SIGN_B2E_DOCUMENT_FILLING_DOCUMENT_IN_PROCESS') }}
					</div>
					<DocumentPreviewList
						:document-list="documentPreviewList"
						:max-preview-count="7"
					/>
					<div class="sign-b2e-document-filling__item-content">
						<div v-if="ruRegionFieldsVisible">
							<span class="sign-b2e-document-filling__item-text">
								{{ loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_LABEL') }}
							</span>
							<input
								v-model="commonSettings.registrationNumber"
								:title="areAllExternalIdsFromIntegration()
									? loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_HCMLINK_HINT')
									: ''"
								:disabled="areAllExternalIdsFromIntegration()"
								type="text"
								class="ui-ctl-element sign-b2e-document-filling-reg-number-input"
								:class="{ '--error': isApplySettingsForAll && !isValid }"
								maxlength="255"
							>
						</div>
						<div class="sign-b2e-settings__date-row">
							<div class="sign-b2e-settings__date-item" v-if="ruRegionFieldsVisible">
								<span class="sign-b2e-document-filling__item-text-date">
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_LABEL') }}
								</span>
								<span
									v-if="areAllExternalDatesFromIntegration()"
									class="sign-b2e-settings__date-selector-text"
									:title="areAllExternalDatesFromIntegration() 
									? loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK_HINT')
									: ''"
								>
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK') }}
								</span>
								<DateSelector
									v-else
									:value="commonSettings.creationDate"
									@onSelect="date => selectCreationDateForAll(date)"
									class="sign-b2e-settings__date-selector"
								/>
							</div>
							<div class="sign-b2e-settings__date-item">
								<span class="sign-b2e-document-filling__item-text-date">
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_EXPIRE_LABEL') }}
								</span>
								<DateSelector
									:value="commonSettings.signingDate"
									showTime
									@onSelect="date => selectSigningDateForAll(date)"
									class="sign-b2e-settings__date-selector"
									:class="{ '--error': signUntilDateErrorCommon }"
								/>
							</div>
							<div v-if="signUntilDateErrorCommon" class="sign-b2e-settings__date-error">
								{{ signUntilDateErrorCommon }}
							</div>
						</div>
						<p v-if="isGosKeyProvider()" class="sign-b2e-document-filling__notice">
							{{ loc('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_GOSKEY_ALERT') }}
						</p>
					</div>
				</template>
			</div>
		</template>

		<template v-if="!isApplySettingsForAll">
			<div
				v-for="doc in documents"
				:key="doc.uid"
				class="sign-b2e-document-filling__item"
			>
				<div class="sign-b2e-document-filling__item-container">
					<div class="sign-b2e-document-filling__item-preview">
						<DocumentPreview
							:document-id="getSmartDocumentId(doc)"
							:preview-src="getPreviewUrl(doc)"
							:is-icon-visible="true"
							class="sign-b2e_document_preview_container"
							preview-image-class="sign-b2e_document_preview_image sign-b2e-document-filling__preview"
						/>
					</div>
					<div class="sign-b2e-document-filling__item-content">
						<div class="sign-b2e-document-filling__header">
							<span class="sign-b2e-document-filling__item-title">
								{{ doc.title }}
							</span>
							<button
								v-if="documents.length > 1"
								class="sign-b2e-document-filling__remove-btn"
								@click="removeDocument(doc.uid)"
								:title="loc('SIGN_B2E_DOCUMENT_FILLING_REMOVE_TEMPLATE')"
							></button>
						</div>
						<div v-if="ruRegionFieldsVisible">
							<span class="sign-b2e-document-filling__item-text">
								{{ loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_LABEL') }}
							</span>
							<input
								v-model="documentsSettings[doc.uid].registrationNumber"
								:title="isExternalIdFromIntegration(doc) 
								? loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_HCMLINK_HINT')
								: ''"
								:disabled="isExternalIdFromIntegration(doc)"
								type="text"
								class="ui-ctl-element sign-b2e-document-filling-reg-number-input"
								:class="{ '--error': !isApplySettingsForAll && isValidMap[doc.uid] === false }"
								maxlength="255"
							/>
						</div>

						<div class="sign-b2e-settings__date-row">
							<div class="sign-b2e-settings__date-item" v-if="ruRegionFieldsVisible">
								<span class="sign-b2e-document-filling__item-text-date">
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_LABEL') }}
								</span>
								<span
									v-if="isExternalDateFromIntegration(doc)"
									class="sign-b2e-settings__date-selector-text"
									:title="isExternalIdFromIntegration(doc) 
									? loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK_HINT')
									: ''"
								>
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK') }}
								</span>
								<DateSelector
									v-else
									:value="documentsSettings[doc.uid].creationDate"
									@onSelect="date => selectCreationDate(doc.uid, date)"
									class="sign-b2e-settings__date-selector"
								/>
							</div>
							<div class="sign-b2e-settings__date-item">
								<span class="sign-b2e-document-filling__item-text-date">
									{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_EXPIRE_LABEL') }}
								</span>
								<DateSelector
									:value="documentsSettings[doc.uid].signingDate"
									showTime
									@onSelect="date => selectSigningDate(doc.uid, date)"
									:class="{ '--error': signUntilDateErrorByDoc[doc.uid] }"
									class="sign-b2e-settings__date-selector"
								/>
							</div>
						</div>
						<p v-if="isGosKeyProvider(doc)" class="sign-b2e-document-filling__notice">
							{{ loc('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_GOSKEY_ALERT') }}
						</p>
						<div v-if="signUntilDateErrorByDoc[doc.uid]" class="sign-b2e-settings__date-error --individual">
							{{ signUntilDateErrorByDoc[doc.uid] }}
						</div>
					</div>
				</div>
			</div>
		</template>
	`,
};
