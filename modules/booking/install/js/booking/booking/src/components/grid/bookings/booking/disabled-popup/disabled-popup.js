import { Event } from 'main.core';
import { PopupOptions } from 'main.popup';
import { Popup } from 'booking.component.popup';
import './disabled-popup.css';

export const DisabledPopup = {
	emits: ['close'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
	},
	mounted(): void
	{
		this.adjustPosition();
		setTimeout(() => this.closePopup(), 3000);
		Event.bind(document, 'scroll', this.adjustPosition, true);
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, true);
	},
	computed: {
		popupId(): string
		{
			return `booking-booking-disabled-popup-${this.bookingId}-${this.resourceId}`;
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-booking-disabled-popup',
				bindElement: this.bindElement(),
				width: this.bindElement().offsetWidth,
				offsetTop: -10,
				bindOptions: {
					forceBindPosition: true,
					position: 'top',
				},
				autoHide: true,
				darkMode: true,
			};
		},
	},
	methods: {
		adjustPosition(): void
		{
			this.$refs.popup.adjustPosition();
		},
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	components: {
		Popup,
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div class="booking-booking-disabled-popup-content">
				{{ loc('BOOKING_BOOKING_YOU_CANNOT_EDIT_THIS_BOOKING') }}
			</div>
		</Popup>
	`,
};
