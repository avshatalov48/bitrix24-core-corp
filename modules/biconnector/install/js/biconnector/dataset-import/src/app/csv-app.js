import { AppLayout } from '../layout/app-layout';
import { ImportConfig } from '../layout/import-config';
import { GenericPopup } from '../popups/generic-popup';
import { ImportFailurePopup } from '../popups/saving/import-failure-popup';
import { ImportProgressPopup } from '../popups/saving/import-progress-popup';
import { ImportSuccessPopup } from '../popups/saving/import-success-popup';
import { DatasetPropertiesStep } from '../steps/dataset-properties';
import { FieldsSettingsStep } from '../steps/fields-settings';
import { FileStep } from '../steps/file';
import { ImportPreview } from '../steps/import-preview';
import { BaseApp } from './base-app';

export const CsvApp = {
	extends: BaseApp,
	data()
	{
		return {
			steps: {
				file: {
					disabled: false,
					valid: this.$store.getters.isEditMode,
				},
				properties: {
					disabled: !this.$store.getters.isEditMode,
					valid: true,
				},
				fields: {
					disabled: !this.$store.getters.isEditMode,
					valid: true,
				},
			},
			shownPopups: {
				savingProgress: false,
				savingSuccess: false,
				savingFailure: false,
				editModeFileReplacement: false,
			},
			isValidationComplete: true,
			popupParams: {
				savingSuccess: {},
			},
			lastReloadSource: null,
			initialPreviewData: {},
			initialFieldsSettings: [],
			previewError: '',
			isEditModeSaveConfirmed: false,
			isDataLoadingAnimationDisplayed: false,
			hasMinimalLoadingAnimationTimePassed: true,
		};
	},
	computed: {
		sourceCode()
		{
			return 'csv';
		},
		isEditMode()
		{
			return this.$store.getters.isEditMode;
		},
		loadParams()
		{
			return {
				fileProperties: this.$store.state.config.fileProperties,
				datasetProperties: this.$store.state.config.datasetProperties,
				fieldsSettings: this.$store.state.config.fieldsSettings,
				dataFormats: this.$store.state.config.dataFormats,
			};
		},
		saveParams()
		{
			return {
				fileProperties: this.$store.state.config.fileProperties,
				datasetProperties: this.$store.state.config.datasetProperties,
				fieldsSettings: this.$store.state.config.fieldsSettings,
				dataFormats: this.$store.state.config.dataFormats,
			};
		},
		datasetId()
		{
			return this.$store.state.config.datasetProperties.id;
		},
		isValidatedForSave()
		{
			return this.steps.fields.valid && this.steps.properties.valid && this.steps.file.valid;
		},
		importFailurePopupTitle()
		{
			return this.isEditMode ? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FAILURE_POPUP_HEADER_EDIT') : this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FAILURE_POPUP_HEADER');
		},
		importSuccessPopupTitle()
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_HEADER_EDIT').replace('#DATASET_TITLE#', this.popupParams.savingSuccess.title)
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_HEADER').replace('#DATASET_TITLE#', this.popupParams.savingSuccess.title);
		},
		importSuccessPopupDescription()
		{
			return this.$store.state.config.fileProperties.fileName
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_DESCRIPTION').replace('#FILE_NAME#', this.popupParams.savingSuccess.fileName)
				: '';
		},
		importProgressPopupDescription()
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PROGRESS_POPUP_DESCRIPTION_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PROGRESS_POPUP_DESCRIPTION');
		},
	},
	methods: {
		markAsChanged()
		{
			this.isChanged = true;
		},
		onDatasetPropertiesChanged()
		{
			this.markAsChanged();
			if (this.lastChangedStep !== 'properties')
			{
				this.sendAnalytics({
					event: this.isEditMode ? 'edit_start' : 'creation_start',
					c_element: 'step_2',
					...(this.isEditMode && { p1: `datasetName_${this.$store.state.config.datasetProperties.name.replaceAll('_', '')}`}),
				});
			}

			this.lastChangedStep = 'properties';
		},
		onFieldsSettingsChanged()
		{
			this.markAsChanged();
			if (this.lastChangedStep !== 'fields')
			{
				this.sendAnalytics({
					event: this.isEditMode ? 'edit_start' : 'creation_start',
					c_element: 'step_3',
					...(this.isEditMode && { p1: `datasetName_${this.$store.state.config.datasetProperties.name.replaceAll('_', '')}`}),
				});
			}

			this.lastChangedStep = 'fields';
		},
		onDatasetReloadNeeded(reloadSource)
		{
			this.markAsChanged();
			this.previewError = '';
			this.lastReloadSource = reloadSource;

			if (this.$store.state.config.fileProperties.fileToken)
			{
				if (reloadSource === 'file')
				{
					this.lastChangedStep = 'file';

					this.sendAnalytics({
						event: this.isEditMode ? 'edit_start' : 'creation_start',
						c_element: 'step_1',
						...(this.isEditMode && { p1: `datasetName_${this.$store.state.config.datasetProperties.name.replaceAll('_', '')}` }),
					});

					this.startPreviewLoadingAnimation();
				}

				this.loadDataset();
			}
			else if (this.isEditMode)
			{
				this.$store.commit('setPreviewData', this.initialPreviewData);
				this.$store.commit('setFieldsSettings', this.initialFieldsSettings);
			}
			else
			{
				this.$store.commit('setPreviewData', []);
				this.$refs.propertiesStep.close();
				this.$refs.fieldsStep.close();
				this.toggleStepState('properties', true);
				this.toggleStepState('fields', true);
			}
		},
		processLoadResponse(response)
		{
			const responseData = response.data;

			if (!responseData || responseData.data.length === 0)
			{
				return;
			}

			if (this.lastReloadSource === 'file' && !this.isEditMode)
			{
				const headers = [];
				responseData.headers.forEach((header, index) => {
					headers.push(this.prepareHeader(header, index));
				});

				this.$store.commit('setFieldsSettings', headers);
			}
			this.$store.commit('setPreviewData', responseData.data);
		},
		prepareHeader(header, index)
		{
			return {
				visible: true,
				type: header.type,
				name: header.name && header.name.length > 0 ? header.name : `field_${index}`,
				originalType: null,
				originalName: header.externalCode,
				externalCode: header.externalCode,
			};
		},
		onLoadSuccess(response)
		{
			this.stopPreviewLoadingAnimation();
			this.processLoadResponse(response);

			this.$refs.propertiesStep.open();
			this.$refs.fieldsStep.open();
			this.toggleStepState('properties', false);
			this.toggleStepState('fields', false);
			this.$refs.fieldsStep.validate();
		},
		onLoadError(response)
		{
			this.stopPreviewLoadingAnimation();
			this.previewError = response.errors[0]?.message ?? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PREVIEW_ERROR_FILE');
		},
		onSaveStart()
		{
			if (!this.isValidatedForSave)
			{
				this.$refs.fileStep.showValidationErrors();
				this.$refs.fieldsStep.showValidationErrors();
				this.$refs.propertiesStep.showValidationErrors();

				return false;
			}

			if (this.isEditMode && !this.isEditModeSaveConfirmed && this.$store.state.config.fileProperties.fileToken)
			{
				this.togglePopup('editModeFileReplacement', true);

				return false;
			}

			this.togglePopup('savingProgress', true);

			return true;
		},
		onSaveEnd(response)
		{
			const datasetName = response.data.name ?? this.$store.state.config.datasetProperties.name;
			this.popupParams.savingSuccess = {
				title: datasetName,
				datasetId: response.data.id,
				fileName: this.$store.state.config.fileProperties.fileName,
			};

			this.togglePopup('savingProgress', false);
			this.togglePopup('savingSuccess', true);
			this.isChanged = false;
			this.isSaveComplete = true;
			BX.SidePanel.Instance.postMessage(window, 'BIConnector.dataset-import:onDatasetCreated', {});
			this.sendAnalytics({
				event: this.isEditMode ? 'edit_end' : 'creation_end',
				status: 'success',
				p1: `datasetName_${datasetName.replaceAll('_', '')}`,
			});
		},
		onSaveError()
		{
			this.togglePopup('savingProgress', false);
			this.togglePopup('savingFailure', true);
			BX.SidePanel.Instance.postMessage(window, 'BIConnector.dataset-import:onDatasetCreated', {});

			this.sendAnalytics({
				event: this.isEditMode ? 'edit_end' : 'creation_end',
				status: 'error',
			});
		},
		onSuccessPopupClose()
		{
			this.togglePopup('savingSuccess', false);
			this.closeApp();
		},
		closeFailurePopup()
		{
			this.togglePopup('savingFailure', false);
		},
		onReplacementButtonClick()
		{
			this.isEditModeSaveConfirmed = true;
			this.togglePopup('editModeFileReplacement', false);
			this.onSaveButtonClick();
		},
		startPreviewLoadingAnimation()
		{
			this.isDataLoadingAnimationDisplayed = true;
			this.hasMinimalLoadingAnimationTimePassed = false;
			setTimeout(() => {
				this.hasMinimalLoadingAnimationTimePassed = true;
			}, 1500);
		},
		stopPreviewLoadingAnimation()
		{
			this.isDataLoadingAnimationDisplayed = false;
		},
	},
	mounted()
	{
		if (this.isEditMode)
		{
			this.initialPreviewData = this.$store.state.previewData.rows;
			this.initialFieldsSettings = this.$store.state.config.fieldsSettings;
		}
	},
	components: {
		AppLayout,
		ImportConfig,
		ImportPreview,
		FileStep,
		DatasetPropertiesStep,
		FieldsSettingsStep,
		ImportProgressPopup,
		ImportSuccessPopup,
		ImportFailurePopup,
		GenericPopup,
	},
	// language=Vue
	template: `
		<AppLayout :save-locked="!isSaveEnabled" ref="appLayout" :is-edit-mode="isEditMode">
			<template v-slot:left-panel>
				<ImportConfig>
					<FileStep
						:separators="appParams.separators"
						:encodings="appParams.encodings"
						:disabled="steps.file.disabled"
						:data-format-templates="appParams.dataFormatTemplates"
						ref="fileStep"
						@validation="onStepValidation('file', $event)"
						@file-properties-change="onDatasetReloadNeeded('file')"
						@parsing-options-changed="onDatasetReloadNeeded('fields')"
					/>
					<DatasetPropertiesStep
						:is-open-initially="isEditMode"
						:disabled="steps.properties.disabled"
						:reserved-names="appParams.reservedNames"
						ref="propertiesStep"
						@validation="onStepValidation('properties', $event)"
						@properties-changed="onDatasetPropertiesChanged"
						:dataset-source-code="sourceCode"
					/>
					<FieldsSettingsStep
						:is-open-initially="isEditMode"
						:disabled="steps.fields.disabled"
						ref="fieldsStep"
						@validation="onStepValidation('fields', $event)"
						@parsing-options-changed="onDatasetReloadNeeded('fields')"
						@settings-changed="onFieldsSettingsChanged"
					/>
				</ImportConfig>
			</template>
			<template v-slot:right-panel>
				<ImportPreview 
					:error="previewError"
					:is-loading="isDataLoadingAnimationDisplayed || !hasMinimalLoadingAnimationTimePassed"
				/>
			</template>
		</AppLayout>

		<ImportProgressPopup
			v-if="shownPopups.savingProgress"
			:description="importProgressPopupDescription"
		/>

		<ImportSuccessPopup
			v-if="shownPopups.savingSuccess"
			@close="onSuccessPopupClose"
			@click="closeApp"
			@one-more-click="reload"
			:title="importSuccessPopupTitle"
			:description="importSuccessPopupDescription"
			:dataset-id="popupParams.savingSuccess.datasetId"
			:show-more-button="!isEditMode"
		/>

		<ImportFailurePopup
			v-if="shownPopups.savingFailure"
			@close="closeFailurePopup"
			@click="closeFailurePopup"
			:title="importFailurePopupTitle"
			:description="$Bitrix.Loc.getMessage('DATASET_IMPORT_FAILURE_POPUP_DESCRIPTION').replace('[link]', '<a>').replace('[/link]', '</a>')"
		/>

		<GenericPopup
			v-if="shownPopups.editModeFileReplacement"
			:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_REPLACEMENT_TITLE')"
			@close="togglePopup('editModeFileReplacement', false)"
		>
			<template v-slot:content>
				{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_REPLACEMENT_TEXT') }}
			</template>
			<template v-slot:buttons>
				<button @click="onReplacementButtonClick" class="ui-btn ui-btn-md ui-btn-primary">{{
					$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_REPLACEMENT_LOAD') }}
				</button>
				<button @click="togglePopup('editModeFileReplacement', false)"
						class="ui-btn ui-btn-md ui-btn-light-border">{{
					$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_REPLACEMENT_CANCEL') }}
				</button>
			</template>
		</GenericPopup>
	`,
};
