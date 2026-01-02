import { mapGetters } from 'ui.vue3.vuex';
import { BIcon } from 'ui.icon-set.api.vue';
import { Animated, Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.animated';
import 'ui.icon-set.main';

import { Counter as UiCounter, CounterSize, CounterColor } from 'booking.component.counter';
import { Model, VisitStatus } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';
import './counter.css';

// @vue/component
export const Counter = {
	components: {
		BIcon,
		UiCounter,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		nowTs: {
			type: Number,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Animated,
			Main,
			CounterColor,
			CounterSize,
		};
	},
	computed: {
		...mapGetters({
			notificationTypes: `${Model.Dictionary}/getNotifications`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		showClocking(): boolean
		{
			if (this.showCounter || this.isExpiredBooking || this.hasVisitStatus || this.isNotVisited)
			{
				return false;
			}

			const notificationTypes = Object.fromEntries(
				Object.entries(this.notificationTypes).map(([type, { value }]) => [type, value]),
			);

			const confirmationSent = this.booking.messages?.some(
				({ notificationType }) => notificationType === notificationTypes.Confirmation,
			);

			return !this.booking.isConfirmed && confirmationSent;
		},
		showConfirmed(): boolean
		{
			if (this.showCounter || this.isExpiredBooking || this.isNotVisited)
			{
				return false;
			}

			return this.booking.isConfirmed;
		},
		showCounter(): boolean
		{
			return this.booking.counter > 0;
		},
		isExpiredBooking(): boolean
		{
			return this.nowTs > this.booking.dateToTs;
		},
		hasVisitStatus(): boolean
		{
			return [VisitStatus.Visited, VisitStatus.NotVisited].includes(this.booking.visitStatus);
		},
		isNotVisited(): boolean
		{
			const started = this.nowTs > this.booking.dateFromTs;
			const statusUnknown = this.booking.visitStatus === VisitStatus.Unknown;
			const statusNotVisited = this.booking.visitStatus === VisitStatus.NotVisited;

			return (started && statusUnknown) || statusNotVisited;
		},
	},
	template: `
		<div class="booking--counter">
			<div v-if="showClocking" class="booking-counter-icon --clocking">
				<BIcon :name="Animated.LOADER_CLOCK" :hoverable="false"/>
			</div>
			<div v-else-if="showConfirmed" class="booking-counter-icon --confirmed">
				<BIcon :name="Main.CHECK" :hoverable="false"/>
			</div>
			<UiCounter
				v-else-if="showCounter"
				:value="booking.counter"
				:color="CounterColor.DANGER"
				:size="CounterSize.LARGE"
				border
			/>
		</div>
	`,
};
