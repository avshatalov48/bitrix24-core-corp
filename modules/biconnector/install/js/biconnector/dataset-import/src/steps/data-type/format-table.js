import { TableHeader } from './table-header';
import { TableRow } from './table-row';
import '../../css/format-table.css';

export const FormatTable = {
	props: {
		fieldsSettings: {
			type: Array,
			required: false,
		},
		unvalidatedRows: {
			type: Object,
			required: false,
		},
		isEditMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	emits: [
		'rowToggle',
		'headerToggle',
		'rowFieldChanged',
	],
	computed: {
		areAllRowsVisible()
		{
			return this.$store.getters.areAllRowsVisible;
		},
		areNoRowsVisible()
		{
			return this.$store.getters.areNoRowsVisible;
		},
		areSomeRowsVisible()
		{
			return this.$store.getters.areSomeRowsVisible;
		},
	},
	methods: {
		onRowCheckboxClicked(event)
		{
			this.$emit('rowToggle', event);
		},
		onHeaderCheckboxClicked()
		{
			this.$emit('headerToggle');
		},
		onRowFieldChanged(event)
		{
			this.$emit('rowFieldChanged', event);
		},
		showValidationErrors()
		{
			const rowIndices = Object.keys(this.unvalidatedRows);
			rowIndices.forEach((index) => {
				if (this.$refs.row[index])
				{
					this.$refs.row[index].showValidationErrors();
				}
			});
		},
	},
	components: {
		TableHeader,
		TableRow,
	},
	// language=Vue
	template: `
		<table class="format-table">
			<thead>
				<TableHeader :enabled="areAllRowsVisible" :indeterminate="areSomeRowsVisible" @checkbox-click="onHeaderCheckboxClicked" />
			</thead>
			<tbody>
				<template v-for="(field, index) in fieldsSettings" :key="index">
					<TableRow
						ref="row"
						:index="index"
						:enabled="field.visible"
						:field-settings="field"
						@checkbox-click="onRowCheckboxClicked"
						@field-change="onRowFieldChanged"
						:invalid-fields="unvalidatedRows[index] ?? []"
						:is-edit-mode="isEditMode"
					/>
				</template>
			</tbody>
		</table>
	`,
};
