import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Model, DraggedElementKind } from 'booking.const';
import { RemoveBooking } from 'booking.lib.remove-booking';
import { RemoveWaitListItem } from 'booking.lib.remove-wait-list-item';
import './drag-delete.css';

export const DragDelete = {
	data(): Object
	{
		return {
			IconSet,
		};
	},
	computed: {
		...mapGetters({
			draggedDataTransfer: `${Model.Interface}/draggedDataTransfer`,
		}),
	},
	methods: {
		onMouseUp(): void
		{
			if (this.draggedDataTransfer.kind === DraggedElementKind.Booking)
			{
				new RemoveBooking(this.draggedDataTransfer.id);

				return;
			}

			if (this.draggedDataTransfer.kind === DraggedElementKind.WaitListItem)
			{
				new RemoveWaitListItem(this.draggedDataTransfer.id);
			}
		},
	},
	components: {
		Icon,
	},
	template: `
		<div v-if="draggedDataTransfer.id > 0" class="booking-booking-drag-delete">
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
