import { Loader } from 'booking.component.loader';
import { HelpDesk } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import type { BookingModel } from 'booking.model.bookings';
import { ConfirmationMenu } from './confirmation-menu/confirmation-menu';
import './confirmation.css';

export const Confirmation = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupConfirmation',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Icon,
		Loader,
		ConfirmationMenu,
	},
	data(): Object
	{
		return {
			IconSet,
			isLoading: true,
		};
	},
	async mounted()
	{
		this.isLoading = false;
	},
	methods: {
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.BookingActionsConfirmation.code,
				HelpDesk.BookingActionsConfirmation.anchorCode,
			);
		},
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		iconColor(): string
		{
			const unconfirmedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_unconfirmed')?.value;
			const delayedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_delayed')?.value;

			if (
				this.booking.isConfirmed === false
				&& !unconfirmedCounter
				&& !delayedCounter
			)
			{
				return '#BDC1C6';
			}

			return '#ffffff';
		},
		stateClass(): string
		{
			if (this.booking.isConfirmed)
			{
				return '--confirmed';
			}

			const unconfirmedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_unconfirmed')?.value;
			const delayedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_delayed')?.value;

			if (unconfirmedCounter)
			{
				return '--not-confirmed';
			}

			if (delayedCounter)
			{
				return '--delayed';
			}

			return '--awaiting';
		},
		stateText(): string
		{
			if (this.booking.isConfirmed)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_CONFIRMED');
			}

			const unconfirmedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_unconfirmed')?.value;
			const delayedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_delayed')?.value;

			if (unconfirmedCounter)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_NOT_CONFIRMED');
			}

			if (delayedCounter)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_DELAYED');
			}

			return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_AWAITING');
		},
		hasBtnCounter(): boolean
		{
			if (this.booking.isConfirmed)
			{
				return false;
			}

			const unconfirmedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_unconfirmed')?.value;
			const delayedCounter = this.booking.counters
				.find((counter) => counter.type === 'booking_delayed')?.value;

			return Boolean(unconfirmedCounter || delayedCounter);
		},
	},
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-confirmation-content">
			<Loader v-if="isLoading" class="booking-actions-popup__item-confirmation-loader" />
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', stateClass]">
					<Icon :name="IconSet.CHECK" :color="iconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_CONFIRMATION_LABEL') }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk" />
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', stateClass]"
						data-element="booking-menu-confirmation-status"
						:data-booking-id="bookingId"
						:data-confirmed="booking.isConfirmed"
					>
						{{ stateText }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<ConfirmationMenu
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
					<div
						v-if="hasBtnCounter"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
		</div>
	`,
};
