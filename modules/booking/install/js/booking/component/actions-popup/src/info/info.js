import { BIcon } from 'ui.icon-set.api.vue';
import { Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';

import { AhaMoment, Model, VisitStatus } from 'booking.const';
import { CyclePopup, CardId } from 'booking.component.cycle-popup';
import { ahaMoments } from 'booking.lib.aha-moments';
import type { BookingModel } from 'booking.model.bookings';
import type { WaitListItemModel } from 'booking.model.wait-list';

import './info.css';

// @vue/component
export const Info = {
	components: {
		BIcon,
		CyclePopup,
	},
	props: {
		bookingId: {
			type: [Number, String],
			default: 0,
		},
		waitListItemId: {
			type: [Number, String],
			default: 0,
		},
	},
	emits: ['freeze', 'unfreeze'],
	setup(): Object
	{
		return {
			Main,
		};
	},
	data(): Object
	{
		return {
			nowTs: Date.now(),
			isPopupShown: false,
		};
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		waitListItem(): WaitListItemModel
		{
			return this.$store.getters[`${Model.WaitList}/getById`](this.waitListItemId);
		},
		scrollToCard(): string
		{
			return {
				[this.isUnconfirmed]: CardId.Unconfirmed,
				[this.isConfirmed]: CardId.Confirmed,
				[this.isLate]: CardId.Late,
				[this.isWaitListItem]: CardId.Waitlist,
				[this.isOverbooking]: CardId.Overbooking,
			}.true;
		},
		isUnconfirmed(): boolean
		{
			if (!this.booking || this.isConfirmed)
			{
				return false;
			}

			return this.booking.counter > 0;
		},
		isConfirmed(): boolean
		{
			if (!this.booking)
			{
				return false;
			}

			return this.booking.isConfirmed;
		},
		isLate(): boolean
		{
			if (!this.booking)
			{
				return false;
			}

			const started = this.nowTs > this.booking.dateFromTs;
			const statusUnknown = this.booking.visitStatus === VisitStatus.Unknown;
			const statusNotVisited = this.booking.visitStatus === VisitStatus.NotVisited;

			return (started && statusUnknown) || statusNotVisited;
		},
		isWaitListItem(): boolean
		{
			return Boolean(this.waitListItem);
		},
		isOverbooking(): boolean
		{
			return this.$store.getters[`${Model.Bookings}/overbookingMap`].has(this.bookingId);
		},
	},
	mounted(): void
	{
		this.interval = setInterval(() => {
			this.nowTs = Date.now();
		}, 5 * 1000);

		void this.tryShowAhaMoment();
	},
	beforeUnmount(): void
	{
		clearInterval(this.interval);
	},
	methods: {
		showPopup(): void
		{
			if (ahaMoments.shouldShow(AhaMoment.CyclePopup))
			{
				ahaMoments.setShown(AhaMoment.CyclePopup);
			}

			this.isPopupShown = true;
			this.$emit('freeze');
		},
		hidePopup(): void
		{
			this.isPopupShown = false;
			this.$emit('unfreeze');
		},
		async tryShowAhaMoment(): Promise<void>
		{
			if (ahaMoments.shouldShow(AhaMoment.CyclePopup))
			{
				await ahaMoments.show({
					id: 'booking-cycle-popup',
					ahaMoment: AhaMoment.CyclePopup,
					title: this.loc('BOOKING_ACTIONS_POPUP_INFO_AHA_TITLE'),
					text: this.loc('BOOKING_ACTIONS_POPUP_INFO_AHA_TEXT'),
					target: this.$refs.container,
					top: true,
					isPulsarTransparent: true,
				});

				ahaMoments.setShown(AhaMoment.CyclePopup);
			}
		},
	},
	template: `
		<div class="booking-actions-popup-info" ref="container" @click="showPopup">
			<BIcon :name="Main.INFO_1"/>
		</div>
		<CyclePopup v-if="isPopupShown" :scrollToCard="scrollToCard" @close="hidePopup"/>
	`,
};
