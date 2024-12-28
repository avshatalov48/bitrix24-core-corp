import '../../css/format-table.css';
import 'ui.hint';

export const TableHeader = {
	props: {
		enabled: {
			type: Boolean,
			required: false,
			default: true,
		},
		indeterminate: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	emits: [
		'checkboxClick',
	],
	computed: {
		isNeedShowOriginalNameHint(): boolean
		{
			return this.$store.state.config.fileProperties?.firstLineHeader ?? true;
		},
	},
	methods: {
		onCheckboxClick(event)
		{
			event.preventDefault();
			this.$emit('checkboxClick');
		},
	},
	// language=Vue
	template: `
		<tr>
			<th class="format-table__header format-table__checkbox-header">
				<input class="format-table__checkbox" type="checkbox" @change="onCheckboxClick" :checked="enabled" :indeterminate.prop="indeterminate">
			</th>
			<th class="format-table__header format-table__type-header format-table__type-subfield-header">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_HEADER') }}</th>
			<th class="format-table__header format-table__header format-table__title-header">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_CODE_HEADER') }}</th>
			<th class="format-table__header format-table__header format-table__title-header" v-if="isNeedShowOriginalNameHint"></th>
		</tr>
	`,
};
