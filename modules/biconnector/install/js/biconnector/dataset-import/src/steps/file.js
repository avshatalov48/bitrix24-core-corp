import { FileUploader } from '../fields/file/file-uploader';
import type { FieldValueChangeEvent } from '../types/field-value-change-event';
import { BaseStep } from './base-step';
import { StepBlock } from '../layout/step-block';

export const FileStep = {
	extends: BaseStep,
	props: {
		encodings: {
			type: Array,
			required: true,
		},
		separators: {
			type: Array,
			required: true,
		},
		dataFormatTemplates: {
			type: Object,
		},
	},
	data()
	{
		return {
			fileProperties: this.$store.state.config.fileProperties,
			isErrorDisplayed: false,
		};
	},
	emits: [
		'filePropertiesChange',
		'parsingOptionsChanged',
	],
	computed: {
		defaultTitle()
		{
			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_HEADER');
		},
		defaultHint()
		{
			if (this.isErrorDisplayed)
			{
				return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_HINT_ERROR');
			}

			if (this.isEditMode)
			{
				return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_HINT_EDIT')
					.replace('[link]', '<a onclick="top.BX.Helper.show(`redirect=detail&code=23378680`)">')
					.replace('[/link]', '</a>')
				;
			}

			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_HINT')
				.replace('[link]', '<a onclick="top.BX.Helper.show(`redirect=detail&code=23378680`)">')
				.replace('[/link]', '</a>')
			;
		},
		hintClass()
		{
			return this.isErrorDisplayed ? 'ui-alert-danger' : 'ui-alert-primary';
		},
		isEditMode()
		{
			return this.$store.getters.isEditMode;
		},
		unvalidatedFields()
		{
			const result = {};

			const fileValidationResult = this.validateFile();
			if (!fileValidationResult.result)
			{
				result.file = fileValidationResult;
			}

			return result;
		},
	},
	methods: {
		onFilePropertiesChange(event: FieldValueChangeEvent)
		{
			if (event.fieldName === 'fileToken')
			{
				this.isErrorDisplayed = false;
			}

			this.fileProperties[event.fieldName] = event.newValue;
			this.$store.commit('setFileProperties', this.fileProperties);
			if (event.fieldName !== 'fileName')
			{
				this.$emit('filePropertiesChange');
			}
			this.validate();
		},
		onUploadError()
		{
			this.isErrorDisplayed = true;
		},
		onParsingOptionsChanged()
		{
			this.$emit('parsingOptionsChanged');
		},
		validate()
		{
			let result = true;
			if (!this.isEditMode)
			{
				result = Object.keys(this.unvalidatedFields).length === 0;
			}

			this.$emit('validation', result);

			return result;
		},
		validateFile()
		{
			if (!this.isEditMode && !this.$store.state.config.fileProperties.fileToken)
			{
				return {
					result: false,
					message: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FILE_NOT_SELECTED'),
				};
			}

			return {
				result: true,
			};
		},
		showValidationErrors()
		{
			this.$refs.fileUploader.showValidationErrors();
		},
	},
	components: {
		Step: StepBlock,
		FileUploader,
	},
	template: `
		<Step
			:title="displayedTitle"
			:hint="displayedHint"
			:is-open-initially="isOpenInitially"
			:disabled="disabled"
			:hint-class="hintClass"
			ref="stepBlock"
		>
			<FileUploader
				@value-change="onFilePropertiesChange"
				@upload-error="onUploadError"
				@parsing-options-changed="onParsingOptionsChanged"
				ref="fileUploader"
				:encodings="encodings"
				:separators="separators"
				:default-encoding="fileProperties.encoding"
				:default-first-line-header="fileProperties.firstLineHeader"
				:default-separator="fileProperties.separator"
				:is-edit-mode="isEditMode"
				:unvalidated-fields="unvalidatedFields"
				:data-format-templates="dataFormatTemplates"
			/>
		</Step>
	`,
};
