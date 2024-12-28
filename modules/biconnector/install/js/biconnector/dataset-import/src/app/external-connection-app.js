import { ajax as Ajax } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { AppLayout } from '../layout/app-layout';
import { ImportConfig } from '../layout/import-config';
import { GenericPopup } from '../popups/generic-popup';
import { ImportFailurePopup } from '../popups/saving/import-failure-popup';
import { ImportProgressPopup } from '../popups/saving/import-progress-popup';
import { ImportSuccessPopup } from '../popups/saving/import-success-popup';
import { ConnectionStep } from '../steps/connection';
import { FieldsSettingsStep } from '../steps/fields-settings';
import { ImportPreview } from '../steps/import-preview';
import { DatasetPropertiesStep } from '../steps/dataset-properties';
import { BaseApp } from './base-app';

export const ExternalConnectionApp = {
	extends: BaseApp,
	data(): Object
	{
		return {
			steps: {
				connection: {
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
				loadFailure: false,
			},
			isValidationComplete: true,
			popupParams: {
				savingSuccess: {},
				loadFailure: {
					messages: [],
				},
			},
			isLoading: false,
			previewError: '',
			previewDataLoaded: false,
		};
	},
	computed: {
		sourceCode(): string
		{
			return this.$store.state.config.connectionProperties?.connectionType ?? '';
		},
		isEditMode(): boolean
		{
			return this.$store.getters.isEditMode;
		},
		loadParams(): Object
		{
			return {
				datasetProperties: this.$store.state.config.datasetProperties,
				fieldsSettings: this.$store.state.config.fieldsSettings,
				dataFormats: this.$store.state.config.dataFormats,
				tableName: this.$store.state.config.connectionProperties.tableName,
				connectionType: this.$store.state.config.connectionProperties.connectionType,
			};
		},
		saveParams(): Object
		{
			return {
				datasetProperties: this.$store.state.config.datasetProperties,
				fieldsSettings: this.$store.state.config.fieldsSettings,
				dataFormats: this.$store.state.config.dataFormats,
				connectionSettings: this.$store.state.config.connectionProperties,
			};
		},
		isValidatedForSave(): boolean
		{
			return this.steps.fields.valid && this.steps.properties.valid && this.steps.connection.valid;
		},
		importSuccessPopupTitle(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_HEADER_EDIT').replace('#DATASET_TITLE#', this.popupParams.savingSuccess.title)
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_HEADER').replace('#DATASET_TITLE#', this.popupParams.savingSuccess.title)
			;
		},
		loadFailurePopupTitle(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_ERROR_EDIT_TITLE')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_ERROR_TITLE')
			;
		},
		fieldsSettingsStepHint(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELDS_SETTINGS_HINT_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELDS_SETTINGS_HINT_EXTERNAL')
					.replace('[link]', '<a onclick="top.BX.Helper.show(`redirect=detail&code=23508958`)">')
					.replace('[/link]', '</a>')
			;
		},
		unsavedChangesPopupTitle(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TITLE_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TITLE_EXTERNAL')
			;
		},
		unsavedChangesPopupText(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TEXT_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TEXT_EXTERNAL')
			;
		},
		emptyStateText(): string
		{
			return this.previewDataLoaded
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PREVIEW_ERROR_EMPTY_TABLE')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PREVIEW_EMPTY_STATE_EXTERNAL')
			;
		},
	},
	mounted()
	{
		if (
			!this.$store.getters.hasData
			&& this.$store.state.config.connectionProperties?.connectionId
		)
		{
			this.loadDataset();
		}

		EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onSliderEvent);
	},
	beforeUnmount()
	{
		EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onSliderEvent);
	},
	methods: {
		onSliderEvent(event)
		{
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionCreated')
			{
				this.onConnectionCreated();
			}
			else if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionCreationError')
			{
				this.onConnectionCreationError();
			}
		},
		onConnectionCreated()
		{
			this.sendAnalytics({
				event: 'connection',
				status: 'success',
			});
		},
		onConnectionCreationError()
		{
			this.sendAnalytics({
				event: 'connection',
				status: 'error',
			});
		},
		onTableSelected()
		{
			this.sendConnectionSelectorAnalytics();
			this.markAsChanged();
			this.loadDataset();
			this.$refs.propertiesStep.showValidationErrors();
		},
		onTableDeselected()
		{
			this.sendConnectionSelectorAnalytics();
			this.$refs.propertiesStep.close();
			this.$refs.fieldsStep.close();
			this.toggleStepState('properties', true);
			this.toggleStepState('fields', true);
			this.$store.commit('setPreviewData', []);
			this.$store.commit('setFieldsSettings', []);
			this.$refs.propertiesStep.validate();
			this.previewError = '';
		},
		sendConnectionSelectorAnalytics()
		{
			if (this.lastChangedStep !== 'connection' && !this.isEditMode)
			{
				this.sendAnalytics({
					event: 'creation_start',
					c_element: 'step_1',
				});
			}

			this.lastChangedStep = 'connection';
		},
		onDatasetPropertiesChanged()
		{
			this.markAsChanged();
			if (this.lastChangedStep !== 'properties' && !this.isEditMode)
			{
				this.sendAnalytics({
					event: 'creation_start',
					c_element: 'step_2',
				});
			}

			this.lastChangedStep = 'properties';
		},
		onParsingOptionsChanged()
		{
			this.markAsChanged();
			this.loadDataset();
		},
		onFieldsChanged()
		{
			this.markAsChanged();
			if (this.lastChangedStep !== 'fields' && !this.isEditMode)
			{
				this.sendAnalytics({
					event: 'creation_start',
					c_element: 'step_3',
				});
			}

			this.lastChangedStep = 'fields';
		},
		loadDataset()
		{
			this.isLoading = true;
			Ajax.runAction('biconnector.externalsource.dataset.view', {
				data: {
					type: this.sourceCode,
					fields: this.loadParams,
					sourceId: this.$store.state.config.connectionProperties.connectionId,
				},
			})
				.then((response) => {
					this.isLoading = false;
					this.onLoadSuccess(response);
				})
				.catch((response) => {
					this.processLoadResponse(response);
					this.isLoading = false;
					this.previewError = response.errors[0]?.message ?? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PREVIEW_ERROR_EXTERNAL');
				});
		},
		onLoadSuccess(response)
		{
			this.processLoadResponse(response);

			this.$refs.propertiesStep.open();
			this.$refs.fieldsStep.open();
			this.toggleStepState('properties', false);
			this.toggleStepState('fields', false);
			this.$refs.propertiesStep.validate();
			this.$refs.fieldsStep.validate();
		},
		processLoadResponse(response)
		{
			const responseData = response.data;

			if (!responseData)
			{
				return;
			}

			this.previewDataLoaded = true;
			const headers = [];
			responseData.headers.forEach((header, index) => {
				headers.push(this.prepareHeader(header, index));
			});

			this.$store.commit('setFieldsSettings', headers);
			this.$store.commit('setPreviewData', responseData.data);
		},
		prepareHeader(header, index)
		{
			return {
				id: header.id,
				visible: header.visible,
				type: header.type,
				name: header.name && header.name.length > 0 ? header.name : `FIELD_${index}`,
				originalType: header.type,
				originalName: header.externalCode,
				externalCode: header.externalCode,
			};
		},
		onSaveStart(): boolean
		{
			if (!this.isValidatedForSave)
			{
				this.$refs.fieldsStep.showValidationErrors();
				this.$refs.propertiesStep.showValidationErrors();

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
				link: response.data.url,
			};

			this.togglePopup('savingProgress', false);
			this.togglePopup('savingSuccess', true);
			this.isChanged = false;
			this.sendAnalytics({
				event: this.isEditMode ? 'dataset_editing' : 'creation_end',
				status: 'success',
				p1: `datasetName_${datasetName.replaceAll('_', '')}`,
			});
			BX.SidePanel.Instance.postMessage(window, 'BIConnector.dataset-import:onDatasetCreated', {});
		},
		onSaveError()
		{
			this.togglePopup('savingProgress', false);
			this.togglePopup('savingFailure', true);
			this.sendAnalytics({
				event: this.isEditMode ? 'dataset_editing' : 'creation_end',
				status: 'error',
			});
			BX.SidePanel.Instance.postMessage(window, 'BIConnector.dataset-import:onDatasetCreated', {});
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
	},
	components: {
		AppLayout,
		ImportConfig,
		ImportPreview,
		ConnectionStep,
		DatasetPropertiesStep,
		FieldsSettingsStep,
		ImportProgressPopup,
		ImportSuccessPopup,
		ImportFailurePopup,
		GenericPopup,
	},
	// language=Vue
	template: `
		<AppLayout
			ref="appLayout"
			:save-locked="!isSaveEnabled"
			:is-edit-mode="isEditMode"
		>
			<template v-slot:left-panel>
				<ImportConfig>
					<ConnectionStep
						:disabled="steps.connection.disabled"
						:connections="appParams.connections"
						ref="connectionsStep"
						@table-selected="onTableSelected"
						@table-deselected="onTableDeselected"
						@validation="onStepValidation('connection', $event)"
					/>
					<DatasetPropertiesStep
						:is-open-initially="isEditMode"
						:disabled="steps.properties.disabled"
						:reserved-names="appParams.reservedNames"
						ref="propertiesStep"
						@validation="onStepValidation('properties', $event)"
						@properties-changed="onDatasetPropertiesChanged"
						dataset-source-code="external"
					/>
					<FieldsSettingsStep
						:is-open-initially="isEditMode"
						:disabled="steps.fields.disabled"
						ref="fieldsStep"
						@validation="onStepValidation('fields', $event)"
						@parsing-options-changed="onParsingOptionsChanged"
						@settings-changed="onFieldsChanged"
						:hint="fieldsSettingsStepHint"
					/>
				</ImportConfig>
			</template>
			<template v-slot:right-panel>
				<ImportPreview 
					:empty-state-text="emptyStateText"
					:is-loading="isLoading"
					:error="previewError"
				/>
			</template>
		</AppLayout>

		<ImportProgressPopup
			v-if="shownPopups.savingProgress"
			:description="$Bitrix.Loc.getMessage('DATASET_IMPORT_PROGRESS_POPUP_DESCRIPTION')"
		/>

		<ImportSuccessPopup
			v-if="shownPopups.savingSuccess"
			@close="onSuccessPopupClose"
			@click="closeApp"
			:title="importSuccessPopupTitle"
			:description="''"
			:dataset-id="popupParams.savingSuccess.datasetId"
			:dataset-link="popupParams.savingSuccess.link"
			:show-more-button="!isEditMode"
			@one-more-click="reload"
		/>

		<ImportFailurePopup
			v-if="shownPopups.savingFailure"
			@close="closeFailurePopup"
			@click="closeFailurePopup"
			:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FAILURE_POPUP_HEADER')"
			:description="$Bitrix.Loc.getMessage('DATASET_IMPORT_EXTERNAL_FAILURE_POPUP_DESCRIPTION').replace('[link]', '<a>').replace('[/link]', '</a>')"
		/>

		<GenericPopup
			v-if="shownPopups.loadFailure"
			:title="loadFailurePopupTitle"
			@close="togglePopup('loadFailure')"
		>
			<template v-slot:content>
				<p v-for="message in popupParams.loadFailure.messages">
					{{ message }}
				</p>
			</template>
			<template v-slot:buttons>
				<button @click="togglePopup('loadFailure')" class="ui-btn ui-btn-md ui-btn-primary">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_ERROR_POPUP_CLOSE') }}</button>
			</template>
		</GenericPopup>
	`,
};
