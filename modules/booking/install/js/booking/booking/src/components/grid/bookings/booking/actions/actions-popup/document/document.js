import 'ui.icon-set.main';

import { Document } from 'booking.component.actions-popup';
import { bookingActionsService } from 'booking.provider.service.booking-actions-service';

export const BookingDocument = {
	name: 'BookingActionsPopupDocument',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): { isLoading: boolean }
	{
		return {
			isLoading: true,
		};
	},
	async mounted()
	{
		await bookingActionsService.getDocData();

		this.isLoading = false;
	},
	components: {
		Document,
	},
	template: `
		<Document
			:id="bookingId"
			:loading="isLoading"
			disabled
		/>
	`,
};
