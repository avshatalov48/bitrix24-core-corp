import { FileExport } from 'biconnector.dataset-import.file-export';
import { AppLayout } from '../layout/app-layout';
import { ImportConfig } from '../layout/import-config';
import { FileErrorsPopup } from '../popups/file-checking/file-errors-popup';
import { CheckingProgressPopup } from '../popups/file-checking/checking-progress-popup';
import { CheckingSuccessPopup } from '../popups/file-checking/checking-success-popup';
import { CheckingFailedPopup } from '../popups/file-checking/checking-failed-popup';
import { GenericPopup } from '../popups/generic-popup';
import { ImportFailurePopup } from '../popups/saving/import-failure-popup';
import { ImportProgressPopup } from '../popups/saving/import-progress-popup';
import { ImportSuccessPopup } from '../popups/saving/import-success-popup';
import { DatasetPropertiesStep } from '../steps/dataset-properties';
import { FieldsSettingsStep } from '../steps/fields-settings';
import { FileStep } from '../steps/file';
import { ImportPreview } from '../steps/import-preview';
import { BaseApp } from './base-app';
import { ajax as Ajax, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';

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
				checkFileProgress: false,
				checkFileSuccess: false,
				checkFileFailure: false,
				fileErrors: false,
			},
			isValidationComplete: true,
			popupParams: {
				savingSuccess: {},
				fileErrors: {
					isSavingMode: false,
				},
			},
			lastReloadSource: null,
			initialPreviewData: {},
			initialFieldsSettings: [],
			previewError: '',
			isEditModeSaveConfirmed: false,
			isDataLoadingAnimationDisplayed: false,
			hasMinimalLoadingAnimationTimePassed: true,
			checkFileErrors: [],
			isErrorsChecked: false,
			exportDownloadLink: null,
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
		sourceType(): string
		{
			return 'csv';
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
			this.isErrorsChecked = false;
		},
		onDatasetReloadNeeded(reloadSource)
		{
			this.markAsChanged();
			this.previewError = '';
			this.lastReloadSource = reloadSource;
			this.isErrorsChecked = false;

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
				this.toggleCheckFileButton(true);
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
				name: header.name && header.name.length > 0 ? header.name : `FIELD_${index}`,
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
			this.toggleCheckFileButton(false);
		},
		onLoadError(response)
		{
			this.stopPreviewLoadingAnimation();
			this.previewError = response.errors[0]?.message ?? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PREVIEW_ERROR_FILE');
		},
		onSaveStart(): Promise
		{
			if (!this.isValidatedForSave)
			{
				this.$refs.fileStep.showValidationErrors();
				this.$refs.fieldsStep.showValidationErrors();
				this.$refs.propertiesStep.showValidationErrors();

				return Promise.reject();
			}

			if (this.isEditMode && !this.isEditModeSaveConfirmed && this.$store.state.config.fileProperties.fileToken)
			{
				this.togglePopup('editModeFileReplacement', true);

				return Promise.reject();
			}

			this.togglePopup('savingProgress', true);
			if (this.isEditMode && !this.$store.state.config.fileProperties.fileToken)
			{
				return Promise.resolve();
			}

			this.popupParams.fileErrors.isSavingMode = true;

			return new Promise((resolve, reject) => {
				this.checkFile()
					.then(() => {
						resolve();
					})
					.catch(() => {
						this.togglePopup('savingProgress', false);
						this.togglePopup('fileErrors', true);
						reject();
					});
			});
		},
		onSaveEnd(response)
		{
			const datasetName = response.data.name ?? this.$store.state.config.datasetProperties.name;
			const datasetId = response.data.id ?? this.$store.state.config.datasetProperties.id;
			this.popupParams.savingSuccess = {
				title: datasetName,
				datasetId,
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
		onCheckFileClick()
		{
			this.popupParams.fileErrors.isSavingMode = false;
			if (this.previewError)
			{
				return;
			}
			this.togglePopup('checkFileProgress', true);
			this.checkFile()
				.then(() => {
					this.togglePopup('checkFileProgress', false);
					this.togglePopup('checkFileSuccess', true);
				})
				.catch(() => {
					this.togglePopup('checkFileProgress', false);
					this.togglePopup('fileErrors', true);
				})
			;
		},
		onExportFileClick()
		{
			const button = document.querySelector('.biconnector-export-file-button');

			if (!button)
			{
				return;
			}

			Dom.addClass(button, 'ui-btn-wait');
			Dom.attr(button, { disabled: true });

			FileExport.getInstance().download({
				id: this.datasetId,
				title: this.$store.state.config.datasetProperties.name ?? 'dataset',
			})
				.then(() => {
					Dom.removeClass(button, 'ui-btn-wait');
					Dom.attr(button, { disabled: null });
				})
				.catch(() => {
					Dom.removeClass(button, 'ui-btn-wait');
					Dom.attr(button, { disabled: null });
				})
			;
		},
		saveIgnoringErrors()
		{
			this.togglePopup('fileErrors', false);
			this.togglePopup('savingProgress', true);
			this.handleSaveAction();
		},
		checkFile(): Promise
		{
			if (this.isErrorsChecked)
			{
				if (this.checkFileErrors.length > 0)
				{
					return Promise.reject();
				}

				return Promise.resolve();
			}

			return new Promise((resolve, reject) => {
				Ajax.runAction('biconnector.externalsource.dataset.checkFile', {
					data: {
						type: this.sourceCode,
						fields: this.loadParams,
					},
				})
					.then((response) => {
						this.checkFileErrors = this.prepareCheckFileErrors(response.data.checkFileErrors);
						this.isErrorsChecked = true;
						if (this.checkFileErrors.length > 0)
						{
							reject();
						}
						else
						{
							resolve();
						}
					})
					.catch((response) => {
						console.error(response);
						this.togglePopup('checkFileProgress', false);
						this.togglePopup('savingProgress', false);
						this.togglePopup('checkFileFailure', true);
					})
				;
			});
		},
		prepareCheckFileErrors(errors: Object): Array
		{
			const result = [];
			Object.keys(errors).forEach((lineNumber) => {
				errors[lineNumber].forEach((error) => {
					result.push({
						lineNumber,
						errorMessage: error.message,
						columnName: this.$store.state.config.fieldsSettings[error.customData.field].name,
					});
				});
			});

			return result;
		},
		toggleCheckFileButton(disabled: boolean): void
		{
			const button = document.querySelector('.biconnector-check-file-button');
			if (button)
			{
				if (disabled)
				{
					Dom.addClass(button, 'ui-btn-disabled');
					Dom.attr(button, 'disabled', true);
				}
				else
				{
					Dom.removeClass(button, 'ui-btn-disabled');
					Dom.attr(button, 'disabled', null);
				}
			}
		},
	},
	mounted()
	{
		if (this.isEditMode)
		{
			this.initialPreviewData = this.$store.state.previewData.rows;
			this.initialFieldsSettings = this.$store.state.config.fieldsSettings;
		}

		EventEmitter.subscribe('biconnector:dataset-import:onCheckFileClick', this.onCheckFileClick);
		EventEmitter.subscribe('biconnector:dataset-import:onExportFileClick', this.onExportFileClick);
		EventEmitter.subscribe('SidePanel.Slider:onClose', () => {
			URL.revokeObjectURL(this.exportDownloadLink);
		});
	},
	beforeUnmount()
	{
		EventEmitter.unsubscribe('biconnector:dataset-import:onCheckFileClick', this.onCheckFileClick);
		EventEmitter.unsubscribe('biconnector:dataset-import:onExportFileClick', this.onExportFileClick);
		URL.revokeObjectURL(this.exportDownloadLink);
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
		FileErrorsPopup,
		CheckingProgressPopup,
		CheckingSuccessPopup,
		CheckingFailedPopup,
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
						:name-max-length=30
						ref="propertiesStep"
						@validation="onStepValidation('properties', $event)"
						@properties-changed="onDatasetPropertiesChanged"
						:dataset-source-code="sourceCode"
					/>
					<FieldsSettingsStep
						:is-open-initially="isEditMode"
						:disabled="steps.fields.disabled"
						:source-type="sourceType"
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

		<FileErrorsPopup
			v-if="shownPopups.fileErrors"
			@close="togglePopup('fileErrors', false);"
			@save-ignoring-errors="saveIgnoringErrors()"
			:errors="checkFileErrors"
			:is-edit-mode="this.isEditMode"
			:is-saving-mode="popupParams.fileErrors.isSavingMode"
		/>
		<CheckingProgressPopup
			v-if="shownPopups.checkFileProgress"
			@close="togglePopup('checkFileProgress', false)"
		/>
		<CheckingSuccessPopup
			v-if="shownPopups.checkFileSuccess"
			@close="togglePopup('checkFileSuccess', false)"
		/>
		<CheckingFailedPopup
			v-if="shownPopups.checkFileFailure"
			@close="togglePopup('checkFileFailure', false)"
		/>
	`,
};
