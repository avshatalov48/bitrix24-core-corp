import { PopupOptions } from 'main.popup';
import { RichLoc } from 'ui.vue3.components.rich-loc';
import { StickyPopup } from 'booking.component.popup';
import './help-popup.css';

export const HelpPopup = {
	emits: ['close'],
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	computed: {
		popupId(): string
		{
			return 'booking-quick-filter-help-popup';
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-quick-filter-help-popup',
				bindElement: this.bindElement,
				offsetLeft: this.bindElement.offsetWidth,
				offsetTop: this.bindElement.offsetHeight,
				maxWidth: 220,
			};
		},
	},
	methods: {
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	components: {
		StickyPopup,
		RichLoc,
	},
	template: `
		<StickyPopup
			:id="popupId"
			:config="config"
			@close="closePopup"
		>
			<div class="booking-quick-filter-help-popup-content">
				<div class="booking-quick-filter-help-popup-icon-container">
					<div class="booking-quick-filter-help-popup-icon"></div>
				</div>
				<div class="booking-quick-filter-help-popup-description">
					<RichLoc :text="loc('BOOKING_QUICK_FILTER_HELP')" placeholder="[bold]">
						<template #bold="{ text }">
							<span>{{ text }}</span>
						</template>
					</RichLoc>
				</div>
			</div>
		</StickyPopup>
	`,
};
