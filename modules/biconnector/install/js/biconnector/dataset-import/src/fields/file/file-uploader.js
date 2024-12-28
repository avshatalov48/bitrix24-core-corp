import { FormatSelector } from '../../steps/data-type/format-selector';
import { SliderButton } from '../../steps/data-type/slider-button';
import type { FieldValueChangeEvent } from '../../types/field-value-change-event';
import { SwitcherField } from '../type/switcher-field';
import { StackWidgetComponent as FileUploadWidget } from 'ui.uploader.stack-widget';
import { CustomField } from '../type/custom-field';
import { DropdownField } from '../type/dropdown-field';
import '../../css/dataset-file-upload.css';

export const FileUploader = {
	components: {
		FileUploadWidget,
		CustomField,
		DropdownField,
		SwitcherField,
		SliderButton,
	},
	props: {
		defaultEncoding: {
			type: String,
			required: false,
			default: '',
		},
		defaultSeparator: {
			type: String,
			required: false,
			default: '',
		},
		defaultFirstLineHeader: {
			type: Boolean,
			required: false,
			default: true,
		},
		encodings: {
			type: Array,
			required: true,
		},
		separators: {
			type: Array,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: false,
			default: false,
		},
		unvalidatedFields: {
			type: Object,
			required: false,
			default: {},
		},
		dataFormatTemplates: {
			type: Object,
		},
	},
	data()
	{
		return {
			inputElement: null,
			areValidationErrorsShown: false,
		};
	},
	emits: [
		'valueChange',
		'uploadError',
		'parsingOptionsChanged',
	],
	computed: {
		uploaderOptions()
		{
			return {
				controller: 'biconnector.integration.ui.fileUploaderController.datasetUploaderController',
				controllerOptions: {},
				multiple: false,
				acceptOnlyImages: false,
				maxFileSize: 1024 * 1024 * 60,
				autoUpload: true,
				acceptedFileTypes: ['.csv'],
				events: {
					onUploadComplete: () => {
						this.onUploadComplete();
					},
					'File:onRemove': () => {
						this.onFileRemoved();
					},
					onError: () => {
						this.onUploadError();
					},
					'File:onError': () => {
						this.onUploadError();
					},
				},
			};
		},
		widgetOptions()
		{
			return {
				size: 'large',
			};
		},
	},
	methods: {
		onValueChange(event: FieldValueChangeEvent)
		{
			this.$emit('valueChange', event);
		},
		onUploadComplete()
		{
			const file = this.$refs.uploader.uploader.getFiles()[0];
			if (file && file.isComplete())
			{
				this.areValidationErrorsShown = false;
				this.$emit('valueChange', {
					newValue: file.getServerFileId(),
					fieldName: 'fileToken',
				});
				this.$emit('valueChange', {
					newValue: file.getName(),
					fieldName: 'fileName',
				});
			}
		},
		onFileRemoved()
		{
			this.$emit('valueChange', {
				newValue: null,
				fieldName: 'fileToken',
			});
		},
		onUploadError()
		{
			this.$emit('uploadError');
		},
		showValidationErrors()
		{
			this.areValidationErrorsShown = true;
		},
		openDataFormatSlider()
		{
			FormatSelector.openSlider(this.$store.state.config.dataFormats, this.dataFormatTemplates, (selectedFormats) => {
				this.$store.commit('setDataFormats', selectedFormats);
				this.$emit('parsingOptionsChanged');
			});
		},
	},
	template: `
		<div class="ui-form">
			<CustomField :title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_FIELD')" name="file">
				<template #field-content>
					<div class="dataset-file-upload">
						<FileUploadWidget
							:uploaderOptions="uploaderOptions"
							:widgetOptions="widgetOptions"
							ref="uploader"
						/>
						<p class="dataset-import-field__error-text" v-if="areValidationErrorsShown && !unvalidatedFields.file?.result">
							{{ unvalidatedFields.file?.message }}
						</p>
					</div>
				</template>
			</CustomField>
			<div class="ui-form-row-inline">
				<DropdownField
					:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_ENCODING')"
					name="encoding"
					:options="encodings"
					:default-value="defaultEncoding"
					@value-change="onValueChange"
				/>
				<DropdownField
					:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_SEPARATOR')"
					name="separator"
					:options="separators"
					:default-value="defaultSeparator"
					@value-change="onValueChange"
				/>
			</div>
			<SliderButton @click="openDataFormatSlider" />
			<SwitcherField
				:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_FIRST_ROW_HEADER')"
				name="firstLineHeader"
				:default-value="defaultFirstLineHeader"
				@value-change="onValueChange"
			/>
		</div>
	`,
};
