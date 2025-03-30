import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { RemoveBooking } from 'booking.lib.remove-booking';
import './remove-btn.css';

export const RemoveBtn = {
	name: 'BookingActionsPopupRemoveBtn',
	emits: ['close'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): Object
	{
		return {
			IconSet,
		};
	},
	methods: {
		removeBooking(): void
		{
			this.$emit('close');

			new RemoveBooking(this.bookingId);
		},
	},
	components: {
		Icon,
	},
	template: `
		<div
			class="booking-actions-popup__item-remove-button"
			data-element="booking-menu-remove-button"
			:data-booking-id="bookingId"
			@click="removeBooking"
		>
			<div class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE') }}
			</div>
			<Icon :name="IconSet.TRASH_BIN"/>
		</div>
	`,
};
