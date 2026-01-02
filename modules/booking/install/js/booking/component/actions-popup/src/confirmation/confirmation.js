import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { ref } from 'ui.vue3';

import { Loader } from 'booking.component.loader';
import { BookingCounterType, HelpDesk } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import type { BookingCounter } from 'booking.model.bookings';

import { ConfirmationMenu } from './confirmation-menu/confirmation-menu';
import type { UpdateConfirmationStatusPayload } from './confirmation-menu/confirmation-menu';
import './confirmation.css';

export type { UpdateConfirmationStatusPayload };

type OptionsDictionary = { [string]: string };
type ConfirmationData = {
	iconSet: OptionsDictionary,
	isLoading: boolean,
}

type CounterValue = BookingCounter['value'];

export const Confirmation = {
	name: 'ActionsPopupConfirmation',
	emits: ['open', 'close', 'updateConfirmationStatus'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		isConfirmed: {
			type: Boolean,
			required: true,
		},
		/**
		 * @type BookingCounter[]
		 */
		counters: {
			type: Array,
			default: () => [],
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		dataId: {
			type: [Number, String],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
	},
	setup(): ConfirmationData
	{
		const iconSet = IconSet;
		const isLoading = ref(true);

		return {
			iconSet,
			isLoading,
		};
	},
	async mounted()
	{
		this.isLoading = false;
	},
	computed: {
		delayedCounter(): CounterValue
		{
			return this.counters
				.find((counter) => counter.type === BookingCounterType.Delayed)?.value;
		},
		unconfirmedCounter(): CounterValue
		{
			return this.counters
				.find((counter) => counter.type === BookingCounterType.Unconfirmed)?.value;
		},
		iconColor(): string
		{
			if (
				this.isConfirmed === false
				&& !this.unconfirmedCounter
				&& !this.delayedCounter
			)
			{
				return '#BDC1C6';
			}

			return '#ffffff';
		},
		stateClass(): string
		{
			if (this.isConfirmed)
			{
				return '--confirmed';
			}

			if (this.unconfirmedCounter)
			{
				return '--not-confirmed';
			}

			if (this.delayedCounter)
			{
				return '--delayed';
			}

			return '--awaiting';
		},
		stateText(): string
		{
			if (this.isConfirmed)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_CONFIRMED');
			}

			if (this.unconfirmedCounter)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_NOT_CONFIRMED');
			}

			if (this.delayedCounter)
			{
				return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_DELAYED');
			}

			return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_AWAITING');
		},
		hasBtnCounter(): boolean
		{
			if (this.isConfirmed)
			{
				return false;
			}

			return Boolean(this.unconfirmedCounter || this.delayedCounter);
		},
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
	components: {
		Icon,
		Loader,
		ConfirmationMenu,
	},
	template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-confirmation-content"
			:class="{ '--disabled': disabled }"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-confirmation-loader"/>
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', stateClass]">
					<Icon :name="iconSet.CHECK" :color="iconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_CONFIRMATION_LABEL') }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', stateClass]"
						data-element="booking-menu-confirmation-status"
						:data-booking-id="dataId"
						:data-confirmed="isConfirmed"
					>
						{{ stateText }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<ConfirmationMenu
						:id
						:isConfirmed
						:disabled
						:dataId
						:dataElementPrefix
						@popupShown="$emit('open')"
						@popupClosed="$emit('close')"
						@updateConfirmationStatus="$emit('updateConfirmationStatus', $event)"
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
