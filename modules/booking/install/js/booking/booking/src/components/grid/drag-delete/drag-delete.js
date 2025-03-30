import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Model } from 'booking.const';
import { RemoveBooking } from 'booking.lib.remove-booking';
import './drag-delete.css';

export const DragDelete = {
	data(): Object
	{
		return {
			IconSet,
		};
	},
	computed: mapGetters({
		draggedBookingId: `${Model.Interface}/draggedBookingId`,
	}),
	methods: {
		onMouseUp(): void
		{
			new RemoveBooking(this.draggedBookingId);
		},
	},
	components: {
		Icon,
	},
	template: `
		<div v-if="draggedBookingId" class="booking-booking-drag-delete">
			<div
				class="booking-booking-drag-delete-button"
				data-element="booking-drag-delete"
				@mouseup.capture="onMouseUp"
			>
				<Icon :name="IconSet.TRASH_BIN"/>
				<div class="booking-booking-drag-delete-button-text">
					{{ loc('BOOKING_BOOKING_DRAG_DELETE') }}
				</div>
			</div>
		</div>
	`,
};
