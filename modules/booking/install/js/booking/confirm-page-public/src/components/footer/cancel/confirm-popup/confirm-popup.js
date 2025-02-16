import { Loc } from 'main.core';
import { PopupOptions } from 'main.popup';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { Popup } from 'booking.component.popup';

import 'ui.icon-set.actions';

export const ConfirmPopup = {
	name: 'ConfirmPopup',
	emits: ['bookingConfirmed', 'bookingCanceled', 'closePopup'],
	components: {
		Popup,
		Button,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
		showPopup: {
			type: Boolean,
			required: true,

		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			btnWaiting: false,
		};
	},
	methods: {
		confirmBookingHandler(): void
		{
			this.btnWaiting = true;
			this.$emit('bookingConfirmed');
		},
		cancelBookingHandler(): void
		{
			this.btnWaiting = true;
			this.$emit('bookingCanceled');
		},
	},
	computed: {
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
				autoHide: false,
			};
		},
	},
	watch: {
		booking:
			{
				handler(booking)
				{
					if (booking.isConfirmed === true)
					{
						this.btnWaiting = false;
						this.$emit('closePopup');
					}
				},
				deep: true,
			},
	},
	template: `
		<Popup
			v-if="showPopup"
			:id="popupId"
			:config="popupConfig"
			@close="closePopup"
		>
			<div class="cancel-booking-popup-content">
				<div class="cancel-booking-popup-content-title">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_CONFIRM_TILE') }}</div>
				<div class="cancel-booking-popup-content-text">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_CONFIRM_TEXT') }}</div>
				<div class="cancel-booking-popup-content-buttons">
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_NOT_CONFIRM')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT_BORDER"
						:buttonClass="'cancel-booking-popup-content-buttons-no'"
						@click="cancelBookingHandler"
					/>
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_CONFIRM')"
						:size="ButtonSize.EXTRA_SMALL"
						:buttonClass="'cancel-booking-popup-content-buttons-yes --confirm'"
						:waiting="btnWaiting"
						@click="confirmBookingHandler"
					/>
				</div>
			</div>
		</Popup>
	`,
};
