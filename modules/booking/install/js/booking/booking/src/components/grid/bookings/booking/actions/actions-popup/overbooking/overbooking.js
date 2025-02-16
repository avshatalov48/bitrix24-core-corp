import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import './overbooking.css';

export const Overbooking = {
	name: 'BookingActionsPopupOverbooking',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Icon,
	},
	methods: {
		openOverbooking(): void {},
		sendToOverbookingList(): void {},
	},
	computed: {
		plusIcon(): string
		{
			return IconSet.PLUS_20;
		},
		plusIconSize(): number
		{
			return 20;
		},
		plusIconColor(): string
		{
			return 'var(--ui-color-palette-gray-20)';
		},
	},
	template: `
		<div class="booking-actions-popup__item-overbooking-icon">
			<Icon :name="plusIcon" :size="plusIconSize" :color="plusIconColor"/>
			<div class="booking-actions-popup__item-overbooking-label">
				{{loc('BB_ACTIONS_POPUP_OVERBOOKING_LABEL')}}
			</div>
		</div>
	`,
};
