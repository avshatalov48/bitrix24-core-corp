import '../../css/dataset-preview-table.css';
import { Ears } from 'ui.ears';
import { TableHeader } from './table-header';
import { TableRow } from './table-row';
import { Dom, debounce } from 'main.core';

export const PreviewTable = {
	props: {
		headers: {
			type: Array,
			required: false,
			default: [],
		},
		rows: {
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
	data()
	{
		return {
			displayedColumnVisibility: this.columnVisibility,
			debouncedRefresh: debounce(this.refreshColumns, 1000),
		};
	},
	mounted()
	{
		const ears = new Ears({
			container: document.querySelector('.dataset-preview-table'),
			smallSize: true,
			noScrollbar: false,
		});

		ears.init();
	},
	methods: {
		refreshColumns(newVisibility)
		{
			this.displayedColumnVisibility = newVisibility;
			Dom.removeClass(this.$refs.table, 'dataset-preview-table--fade');
		},
	},
	watch: {
		columnVisibility(newValue, oldValue)
		{
			Dom.addClass(this.$refs.table, 'dataset-preview-table--fade');
			this.debouncedRefresh(newValue);
		},
	},
	components: {
		TableHeader,
		TableRow,
	},
	// language=Vue
	template: `
		<div class="dataset-preview-table" ref="table">
			<table class="dataset-preview-table__table">
				<TableHeader v-if="headers.length > 0" :headers="headers" :column-visibility="displayedColumnVisibility" />
				<tbody>
					<TableRow v-for="row in rows" :row="row" :column-visibility="displayedColumnVisibility" />
				</tbody>
			</table>
		</div>
	`,
};
