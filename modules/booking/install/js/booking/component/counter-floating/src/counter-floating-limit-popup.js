import type { PopupOptions } from 'main.popup';

import { Popup } from 'booking.component.popup';

import './counter-floating-limit-popup.css';

// @vue/component
export const CounterFloatingLimitPopup = {
	name: 'CounterFloatingLimitPopup',
	components: {
		Popup,
	},
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	emits: ['close'],
	computed: {
		popupId(): string
		{
			return 'booking-counter-floating-limit-popup';
		},
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				minWidth: 200,
				offsetTop: this.bindElement.offsetHeight * -1.1,
				offsetLeft: (this.bindElement.offsetWidth * -1) - 85,
				background: '#2878ca',
				padding: 13,
				angle: {
					offset: 20,
					position: 'right',
				},
				angleBorderRadius: '4px 0',
			};
		},
	},
	template: `
		<Popup
			:id="popupId"
			:config
			ref="popup"
			@close="$emit('close')"
		>
			<div class="booking--booking--counter-floating-limit-popup-content">
				<div class="booking--booking--counter-floating-limit-popup-content__title">
					{{ loc('BOOKING_BOOKING_FILTER_COUNTER_FLOATING_LIMIT_POPUP_TITLE') }}
				</div>
				<div class="booking--booking--counter-floating-limit-popup-content__subtitle">
					{{ loc('BOOKING_BOOKING_FILTER_COUNTER_FLOATING_LIMIT_POPUP_SUBTITLE') }}
				</div>
			</div>
		</Popup>
	`,
};
