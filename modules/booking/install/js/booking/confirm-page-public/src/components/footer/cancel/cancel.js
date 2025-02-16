import { Loc } from 'main.core';
import { PopupOptions } from 'main.popup';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { ConfirmPopup } from './confirm-popup/confirm-popup';
import { CancelPopup } from './cancel-popup/cancel-popup';

import 'ui.icon-set.actions';
import './cancel.css';

export const Cancel = {
	name: 'Cancel',
	emits: ['bookingCanceled', 'bookingConfirmed'],
	components: {
		Icon,
		Button,
		CancelPopup,
		ConfirmPopup,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
		context: {
			type: String,
			required: true,

		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			showPopup: this.context === 'delayed.pub.page',
			btnWaiting: false,
			showLink: true,
		};
	},
	methods: {
		cancelBookingHandler(): void
		{
			this.btnWaiting = true;
			this.$emit('bookingCanceled');
		},
		confirmBookingHandler(): void
		{
			this.btnWaiting = true;
			this.$emit('bookingConfirmed');
		},
		cancelBtnHandler(): void
		{
			if (this.booking.isDeleted === true)
			{
				return;
			}

			this.showPopup = true;
		},
		closePopup(): void
		{
			this.showPopup = false;
		},
	},
	computed: {
		icon(): string
		{
			return IconSet.UNDO_1;
		},
		iconSize(): number
		{
			return 17;
		},
		iconColor(): string
		{
			return '#A8ADB4';
		},
		popupId(): string
		{
			return `booking-confirm-page-popup-${this.booking.id}`;
		},
		popupConfig(): PopupOptions
		{
			return {
				className: 'booking-confirm-page-popup',
				offsetLeft: 0,
				offsetTop: 0,
				overlay: true,
				borderRadius: '5px',
			};
		},
		showCancelBtn(): boolean
		{
			return this.context !== 'manager.view.details';
		},
	},
	watch: {
		booking:
		{
			handler(booking)
			{
				if (booking.isDeleted === true)
				{
					this.showLink = false;
					this.btnWaiting = false;
					this.showPopup = false;
				}

				if (booking.isConfirmed === true)
				{
					this.btnWaiting = false;
					this.showPopup = false;
				}
			},
			deep: true,
		},
	},
	template: `
		<div v-if="showLink" class="cancel-booking">
			<Icon :name="icon" :size="iconSize" :color="iconColor" v-if="showCancelBtn"/>
			<a class="cancel-booking-link" @click="cancelBtnHandler" v-if="showCancelBtn">
				{{ loc('BOOKING_CONFIRM_PAGE_CANCEL_BTN') }}
			</a>
		</div>
		<CancelPopup 
			v-if="context === 'cancel.pub.page'"
			:showPopup="showPopup" 
			:booking="booking"
			@bookingCanceled="cancelBookingHandler"
			@popupClosed="closePopup"
		/>
		<ConfirmPopup
			v-if="context === 'delayed.pub.page'"
			:showPopup="showPopup"
			:booking="booking"
			@bookingCanceled="cancelBookingHandler"
			@bookingConfirmed="confirmBookingHandler"
			@closePopup="closePopup"
		/>
	`,
};
