import type { DealData } from 'booking.model.bookings';
import './profit.css';

// @vue/component
export const Profit = {
	name: 'Profit',
	props: {
		/**
		 * @type {DealData}
		 */
		deal: {
			type: Object,
			default: null,
		},
		className: {
			type: [Object, String, Array],
			default: '',
		},
		dataAttributes: {
			type: Object,
			default: null,
		},
	},
	template: `
		<div
			v-if="deal"
			class="booking--booking-base-profit"
			:class="className"
			:data-profit="deal.data.opportunity"
			v-bind="$props.dataAttributes"
			v-html="deal.data.formattedOpportunity"
		></div>
	`,
};
