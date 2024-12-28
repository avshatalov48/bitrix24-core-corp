import '../../css/dataset-preview-table.css';

export const TableRow = {
	props: {
		row: {
			type: Array,
			required: true,
		},
		columnVisibility: {
			type: Array,
			required: false,
			default: [],
		},
	},
	computed: {
		visibleValues()
		{
			return this.row.filter((_, index) => this.columnVisibility[index]);
		},
	},
	// language=Vue
	template: `
		<tr>
			<td class="dataset-preview-table__cell" v-for="value in visibleValues" :title="value">{{ value }}</td>
		</tr>
	`,
};
