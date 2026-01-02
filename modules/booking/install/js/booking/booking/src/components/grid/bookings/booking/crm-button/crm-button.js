import { CrmButton } from 'booking.component.booking';
import { BookingDealHelper } from 'booking.lib.deal-helper';

export const BookingCrmButton = {
	name: 'BookingCrmButton',
	components: {
		CrmButton,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(props): { dealHelper: BookingDealHelper }
	{
		const dealHelper = new BookingDealHelper(props.bookingId);

		return {
			dealHelper,
		};
	},
	template: `
		<CrmButton
			:dealHelper
			:dataAttributes="{
				'data-booking-id': bookingId,
				'data-element': 'booking-crm-button'
			}"
		/>
	`,
};
