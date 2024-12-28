import { StepBlock } from '../layout/step-block';
import { BaseStep } from './base-step';
import { DatasetProperties } from '../fields/dataset-properties';
import type { FieldValueChangeEvent } from '../types/field-value-change-event';

export const DatasetPropertiesStep = {
	extends: BaseStep,
	props: {
		datasetSourceCode: {
			type: String,
			required: true,
		},
		reservedNames: {
			type: Array,
			required: false,
			default: [],
		},
	},
	emits: ['propertiesChanged'],
	computed: {
		datasetProperties()
		{
			return this.$store.state.config.datasetProperties;
		},
		defaultTitle()
		{
			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PROPERTIES_HEADER');
		},
		defaultHint()
		{
			if (this.isEditMode)
			{
				return '';
			}

			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_PROPERTIES_HINT');
		},
		disabledFields()
		{
			return {
				name: this.$store.getters.isEditMode,
			};
		},
		unvalidatedFields()
		{
			const result = {};
			const nameValidationResult = this.validateName();
			if (!nameValidationResult.result)
			{
				result.name = nameValidationResult;
			}

			return result;
		},
		isEditMode()
		{
			return this.$store.getters.isEditMode;
		},
	},
	components: {
		Step: StepBlock,
		DatasetProperties,
	},
	methods: {
		onDatasetPropertiesFieldsChange(event: FieldValueChangeEvent)
		{
			const datasetProperties = this.datasetProperties;
			datasetProperties[event.fieldName] = event.newValue;
			this.$store.commit('setDatasetProperties', datasetProperties);
			this.validate();

			this.$emit('propertiesChanged');
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
		validateName()
		{
			const name = this.$store.state.config.datasetProperties.name;
			if (!name)
			{
				return {
					result: true,
				};
			}

			const isReserved = this.reservedNames.includes(name);
			if (isReserved)
			{
				return {
					result: false,
					message: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_VALIDATION_DATASET_EXISTS'),
				};
			}

			if (name.length > 30)
			{
				return {
					result: false,
					message: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_VALIDATION_DATASET_TOO_LONG'),
				};
			}

			if (!/^[a-z][\d_a-z]*$/.test(name))
			{
				return {
					result: false,
					message: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_VALIDATION_DATASET_INVALID_CHARS'),
				};
			}

			return {
				result: true,
			};
		},
		showValidationErrors()
		{
			this.$refs.datasetProperties.showValidationErrors();
		},
	},
	template: `
		<Step
			:title="displayedTitle"
			:hint="displayedHint"
			:is-open-initially="isOpenInitially"
			:disabled="disabled"
			ref="stepBlock"
		>
			<slot name="additional-properties"></slot>
			<DatasetProperties
				@value-change="onDatasetPropertiesFieldsChange"
				:default-name="datasetProperties.name"
				:default-description="datasetProperties.description"
				ref="datasetProperties"
				:disabled-fields="disabledFields"
				:unvalidated-fields="unvalidatedFields"
				:dataset-source-code="datasetSourceCode"
			/>
		</Step>
	`,
};
