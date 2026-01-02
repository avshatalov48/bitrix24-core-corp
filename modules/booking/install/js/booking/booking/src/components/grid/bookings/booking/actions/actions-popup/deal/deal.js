import 'ui.icon-set.main';

import { CrmEntity, Model } from 'booking.const';
import { BookingDealHelper } from 'booking.lib.deal-helper';
import { Deal } from 'booking.component.actions-popup';
import type { BookingModel, DealData } from 'booking.model.bookings';

type BookingDealData = {
	dealHelper: BookingDealHelper;
}

// @vue/component
export const BookingDeal = {
	name: 'BookingActionsPopupDeal',
	components: {
		Deal,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	setup(props): BookingDealData
	{
		const dealHelper = new BookingDealHelper(props.bookingId);

		return {
			dealHelper,
		};
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		deal(): DealData | null
		{
			return this.booking.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
		},
	},
	template: `
		<Deal
			:deal="deal"
			:dealHelper="dealHelper"
			:dataId="booking.id"
			:dataAttributes="{
				'data-booking-id': bookingId,
			}"
			dataElementPrefix="booking"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`,
};
