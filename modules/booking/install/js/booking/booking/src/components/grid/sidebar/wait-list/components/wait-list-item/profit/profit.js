import { CrmEntity } from 'booking.const';
import { Profit } from 'booking.component.booking';
import type { DealData } from 'booking.model.bookings';
import type { WaitListItemModel } from 'booking.model.wait-list';

// @vue/component
export const WaitListItemProfit = {
	name: 'WaitListItemProfit',
	components: {
		Profit,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
			required: true,
		},
	},
	computed: {
		deal(): DealData | null
		{
			return this.waitListItem.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
		},
	},
	template: `
		<Profit
			:deal
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-profit'
			}"
		/>
	`,
};
