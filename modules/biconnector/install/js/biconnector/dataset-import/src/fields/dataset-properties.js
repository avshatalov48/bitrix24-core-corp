import type { FieldValueChangeEvent } from '../types/field-value-change-event';
import { StringField } from './type/string-field';
import { TextField } from './type/text-field';
import '../css/dataset-properties.css';

export const DatasetProperties = {
	props: {
		defaultName: {
			type: String,
			required: false,
			default: '',
		},
		defaultDescription: {
			type: String,
			required: false,
			default: '',
		},
		unvalidatedFields: {
			type: Object,
			required: false,
			default: {},
		},
		disabledFields: {
			type: Object,
			required: false,
			default: {},
		},
		datasetSourceCode: {
			type: String,
			required: true,
		},
	},
	emits: [
		'valueChange',
		'validationNeeded',
	],
	methods: {
		onValueChange(event: FieldValueChangeEvent)
		{
			this.$emit('valueChange', event);
		},
		showValidationErrors()
		{
			this.$refs.nameField.showValidationErrors();
		},
	},
	components: {
		StringField,
		TextField,
	},
	// language=Vue
	template: `
		<div class="ui-form dataset-properties">
			<StringField
				ref="nameField"
				name="name"
				:defaultValue="defaultName"
				@value-change="onValueChange"
				:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_DATASET_PROPERTIES_CODE')"
				:placeholder="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_DATASET_PROPERTIES_CODE_PLACEHOLDER', { '#CODE#': this.datasetSourceCode })"
				:is-valid="unvalidatedFields.name?.result ?? true"
				:is-disabled="disabledFields.name ?? false"
				:error-message="unvalidatedFields.name?.message ?? ''"
			/>
			<TextField
				name="description"
				:defaultValue="defaultDescription"
				@value-change="onValueChange"
				:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_DATASET_PROPERTIES_DESCRIPTION')"
			/>
		</div>
	`,
};
