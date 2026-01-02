// @vue/component

import { Event } from 'main.core';
import { PopupOptions } from 'main.popup';
import { Popup } from 'booking.component.popup';
import './disabled-popup.css';

export const DisabledPopup = {
	name: 'DisabledPopup',
	components: {
		Popup,
	},
	emits: ['close'],
	props: {
		popupId: {
			type: String,
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
		contentClass: {
			type: [String, Object],
			default: 'booking-booking-disabled-popup-content',
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
	template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div :class="contentClass">
				{{ loc('BOOKING_BOOKING_YOU_CANNOT_EDIT_THIS_BOOKING') }}
			</div>
		</Popup>
	`,
};
