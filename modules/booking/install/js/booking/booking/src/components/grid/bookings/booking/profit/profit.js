import { CrmEntity, Model } from 'booking.const';
import { Profit } from 'booking.component.booking';
import type { BookingModel, DealData } from 'booking.model.bookings';
import './profit.css';

export const BookingProfit = {
	name: 'BookingProfit',
	components: {
		Profit,
	},
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
		<Profit
			:deal
			:dataAttributes="{
				'data-id': bookingId,
				'data-resource-id': resourceId,
				'data-element': 'booking-booking-profit'
			}"
			className="booking-booking-booking-profit"
		/>
	`,
};
