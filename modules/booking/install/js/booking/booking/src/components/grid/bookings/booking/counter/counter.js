import { Counter as UiCounter, CounterSize, CounterColor } from 'booking.component.counter';
import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';
import './counter.css';

export const Counter = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		counterOptions(): Object
		{
			return Object.freeze({
				color: CounterColor.DANGER,
				size: CounterSize.LARGE,
			});
		},
	},
	components: {
		UiCounter,
	},
	template: `
		<UiCounter
			v-if="booking.counter > 0"
			:value="booking.counter"
			:color="counterOptions.color"
			:size="counterOptions.size"
			border
			counter-class="booking--counter"
		/>
	`,
};
