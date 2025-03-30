import { CrmEntity, Model } from 'booking.const';
import type { BookingModel, DealData } from 'booking.model.bookings';
import './profit.css';

export const Profit = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
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
		<div
			v-if="deal"
			class="booking-booking-booking-profit"
			data-element="booking-booking-profit"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			:data-profit="deal.data.opportunity"
			v-html="deal.data.formattedOpportunity"
		></div>
	`,
};
