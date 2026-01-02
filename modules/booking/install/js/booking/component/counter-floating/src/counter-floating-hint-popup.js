import { Loc } from 'main.core';
import type { PopupOptions } from 'main.popup';

import { Popup } from 'booking.component.popup';
import { AhaMoment } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';

import './counter-floating-hint-popup.css';

// @vue/component
export const CounterFloatingHintPopup = {
	name: 'CounterFloatingHintPopup',
	components: {
		Popup,
	},
	props: {
		count: {
			type: Number,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	emits: ['close'],
	computed: {
		popupId(): string
		{
			return 'booking-counter-floating-hint-popup';
		},
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				minWidth: 200,
				offsetTop: this.bindElement.offsetHeight * -1.1,
				offsetLeft: (this.bindElement.offsetWidth * -1) - 90,
				background: '#2878ca',
				padding: 13,
				angle: {
					offset: 20,
					position: 'right',
				},
				angleBorderRadius: '4px 0',
			};
		},
		title(): string
		{
			return Loc.getMessagePlural('BOOKING_BOOKING_FILTER_COUNTER_FLOATING_AHA_MOMENT_TITLE', this.count, {
				'#COUNT#': this.count,
			});
		},
	},
	methods: {
		closePopup(): void
		{
			ahaMoments.setShown(AhaMoment.SearchNavigation);
			this.$emit('close');
		},
	},
	template: `
		<Popup
			:id="popupId"
			:config
			ref="popup"
			@close="closePopup"
		>
			<div class="booking--booking--counter-floating-hint-popup-content">
				<div class="booking--booking--counter-floating-hint-popup-content__title">{{ title }}</div>
				<div class="booking--booking--counter-floating-hint-popup-content__subtitle">
					{{ loc('BOOKING_BOOKING_FILTER_COUNTER_FLOATING_AHA_MOMENT_SUBTITLE') }}
				</div>
			</div>
		</Popup>
	`,
};
