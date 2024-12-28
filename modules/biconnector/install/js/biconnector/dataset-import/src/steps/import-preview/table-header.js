import '../../css/dataset-preview-table.css';

export const TableHeader = {
	props: {
		headers: {
			type: Array,
			required: false,
			default: [],
		},
		columnVisibility: {
			type: Array,
			required: false,
			default: [],
		},
	},
	computed: {
		visibleHeaders()
		{
			return this.headers.filter((_, index) => this.columnVisibility[index]);
		},
	},
	// language=Vue
	template: `
		<thead>
			<tr class="dataset-preview-table__header-row">
				<th class="dataset-preview-table__header" v-for="header in visibleHeaders" :title="header">{{ header }}</th>
			</tr>
		</thead>
	`,
};
