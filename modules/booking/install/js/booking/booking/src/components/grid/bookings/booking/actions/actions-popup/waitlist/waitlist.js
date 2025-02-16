import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import './waitlist.css';

export const Waitlist = {
	name: 'BookingActionsPopupWaitlist',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Icon,
	},
	computed: {
		clockIcon(): string
		{
			return IconSet.BLACK_CLOCK;
		},
		clockIconSize(): number
		{
			return 20;
		},
		clockIconColor(): string
		{
			return 'var(--ui-color-palette-gray-20)';
		},
	},
	template: `
		<div class="booking-actions-popup__item-waitlist-icon --end">
			<Icon :name="clockIcon" :size="clockIconSize" :color="clockIconColor"/>
			<div class="booking-actions-popup__item-waitlist-label">
				{{loc('BB_ACTIONS_POPUP_OVERBOOKING_LIST')}}
			</div>
		</div>
	`,
};
