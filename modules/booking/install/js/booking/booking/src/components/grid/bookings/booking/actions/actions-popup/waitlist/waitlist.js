// @vue/component

import { Text } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Model } from 'booking.const';
import { isRealId } from 'booking.lib.is-real-id';
import { BookingAnalytics } from 'booking.lib.analytics';
import { waitListService } from 'booking.provider.service.wait-list-service';

import './waitlist.css';

export const Waitlist = {
	name: 'BookingActionsPopupWaitlist',
	components: {
		Icon,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
		}),
		clockIcon(): string
		{
			return IconSet.BLACK_CLOCK;
		},
		disabled(): boolean
		{
			return !isRealId(this.bookingId);
		},
	},
	methods: {
		async toWaitList(): Promise<void>
		{
			if (this.disabled)
			{
				return;
			}

			const bookingId = this.bookingId;
			const booking = this.getBookingById(bookingId);

			await this.$store.dispatch(`${Model.Interface}/addDeletingBooking`, bookingId);
			const result = await waitListService.createFromBooking(
				bookingId,
				{
					id: `tmp-id-${Date.now()}-${Text.getRandom(4)}`,
					clients: booking.clients,
					primaryClient: booking.primaryClient,
					externalData: booking.externalData,
					createdAt: Date.now(),
					updatedAt: Date.now(),
				},
			);

			if (result.success && result.waitListItem)
			{
				BookingAnalytics.sendAddWaitListItem();
			}
		},
	},
	template: `
		<div
			class="booking--booking-actions-popup__item-waitlist-btn --end"
			:class="{'--disabled': disabled}"
			@click="toWaitList">
			<Icon :name="clockIcon" :size="20" color="var(--ui-color-palette-gray-60)"/>
			<div class="booking-actions-popup__item-waitlist-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_LIST') }}
			</div>
		</div>
	`,
};
