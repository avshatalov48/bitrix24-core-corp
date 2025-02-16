import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { Button as UiButton, ButtonSize, ButtonColor } from 'booking.component.button';
import { Duration } from 'booking.lib.duration';

export const MultiBookingItem = {
	name: 'MultiBookingItem',
	emits: ['remove-selected'],
	props: {
		id: {
			type: String,
			required: true,
		},
		fromTs: {
			type: Number,
			required: true,
		},
		toTs: {
			type: Number,
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
	},
	computed: {
		...mapGetters({
			offset: `${Model.Interface}/offset`,
		}),
		label(): string
		{
			return this.loc('BOOKING_MULTI_ITEM_TITLE', {
				'#DATE#': DateTimeFormat.format('d M H:i', (this.fromTs + this.offset) / 1000),
				'#DURATION#': new Duration(this.toTs - this.fromTs).format(),
			});
		},
		buttonColor(): string
		{
			return ButtonColor.LINK;
		},
		buttonSize(): string
		{
			return ButtonSize.EXTRA_SMALL;
		},
	},
	components: {
		UiButton,
	},
	template: `
		<div class="booking--multi-booking--book">
			<label>
				{{ label }}
			</label>
			<button
				:class="[buttonSize, buttonColor, 'ui-btn ui-icon-set --cross-20']"
				type="button"
				@click="$emit('remove-selected', this.id)">
			</button>
		</div>
	`,
};
