import { BookingTime } from './booking-time/booking-time';
import { BookingDetail } from './booking-detail/booking-detail';
import './content.css';

export const Content = {
	name: 'Content',
	components: {
		BookingTime,
		BookingDetail,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {};
	},
	template: `
		<div class="confirm-page-content">
			<BookingTime :booking="booking"/>
			<BookingDetail :booking="booking"/>
		</div>
	`,
};
