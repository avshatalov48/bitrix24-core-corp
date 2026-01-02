// @vue/component
export const BookingCard = {
	template: `
		<div class="booking-cycle-popup-preview-booking">
			<div class="booking-cycle-popup-preview-booking-title">{{ loc('BOOKING_CYCLE_POPUP_CLIENT') }}</div>
			<slot/>
		</div>
	`,
};
