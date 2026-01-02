import { Model } from 'booking.const';
import { Confirmation } from 'booking.component.actions-popup';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type { UpdateConfirmationStatusPayload } from 'booking.component.actions-popup';

export const BookingConfirmation = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupConfirmation',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
	},
	methods: {
		updateConfirmationStatus({ isConfirmed }: UpdateConfirmationStatusPayload): void
		{
			void bookingService.update({
				id: this.booking.id,
				isConfirmed,
			});
		},
	},
	components: {
		Confirmation,
	},
	template: `
		<Confirmation
			:id="bookingId"
			:isConfirmed="booking.isConfirmed"
			:counters="booking.counters"
			:dataId="booking.id"
			dataElementPrefix="booking"
			@open="$emit('freeze')"
			@close="$emit('unfreeze')"
			@updateConfirmationStatus="updateConfirmationStatus"
		/>
	`,
};
