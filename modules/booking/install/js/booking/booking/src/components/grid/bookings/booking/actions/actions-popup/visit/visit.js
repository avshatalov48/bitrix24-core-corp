import { BIcon as Icon } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Model } from 'booking.const';
import { Loader } from 'booking.component.loader';
import { Visit } from 'booking.component.actions-popup';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type { VisitUpdateVisitStatusPayload } from 'booking.component.actions-popup';

export const BookingVisit = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupVisit',
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
		updateVisitStatus({ visitStatus }: VisitUpdateVisitStatusPayload): void
		{
			void bookingService.update({
				id: this.booking.id,
				visitStatus,
			});
		},
	},
	components: {
		Icon,
		Loader,
		Visit,
	},
	template: `
		<Visit
			:id="booking.id"
			:visitStatus="booking.visitStatus"
			:dataId="booking.id"
			dataElementPrefix="booking"
			:hasClients="booking.clients.length > 0"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@update:visitStatus="updateVisitStatus"
		/>
	`,
};
