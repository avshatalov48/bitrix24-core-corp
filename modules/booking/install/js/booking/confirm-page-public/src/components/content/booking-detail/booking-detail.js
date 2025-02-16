import { Mixin } from '../../mixin';
import { Item } from './item/item';
import { Total } from './total/total';
import { Avatar } from './avatar/avatar';
import './booking-detail.css';

export const BookingDetail = {
	name: 'BookingDetail',
	mixins: [Mixin],
	components: {
		Item,
		Total,
		Avatar,
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
		<div class="booking-confirm-page-content-booking-detail-border">
			<div class="booking-confirm-page-content-booking-detail-title">{{ loc('BOOKING_CONFIRM_PAGE_BOOKING_DETAILS') }}</div>
			<div class="booking-confirm-page-content-booking-detail-line"></div>
			<div class="booking-confirm-page-content-booking-detail-master">
				<Avatar :booking="booking" />
				<div class="booking-confirm-page-content-booking-detail-master-info">
					<div class="booking-confirm-page-content-booking-detail-master-name">{{ booking.resources[0].name }}</div>
					<div class="booking-confirm-page-content-booking-detail-master-title">{{ booking.resources[0].type.name }}</div>
				</div>
			</div>
		</div>
	`,
};
