import { grid } from 'booking.lib.grid';
import { BaseCell } from '../../base-cell/base-cell';
import './cell.css';

/**
 * @typedef {Object} Cell
 * @property {string} id
 * @property {number} fromTs
 * @property {number} toTs
 * @property {number} resourceId
 * @property {boolean} boundedToBottom
 */
export const Cell = {
	props: {
		/** @type {Cell} */
		cell: {
			type: Object,
			required: true,
		},
	},
	computed: {
		left(): number
		{
			return grid.calculateLeft(this.cell.resourceId);
		},
		top(): number
		{
			return grid.calculateTop(this.cell.fromTs);
		},
		height(): number
		{
			return grid.calculateHeight(this.cell.fromTs, this.cell.toTs);
		},
	},
	components: {
		BaseCell,
	},
	template: `
		<div
			v-if="left >= 0"
			class="booking-booking-selected-cell"
			:style="{
				'--left': left + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
			}"
			@mouseleave="$store.dispatch('interface/setHoveredCell', null)"
		>
			<BaseCell
				:cell="cell"
			/>
		</div>
	`,
};
