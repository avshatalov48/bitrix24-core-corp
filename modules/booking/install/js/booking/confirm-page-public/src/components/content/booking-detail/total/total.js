export const Total = {
	name: 'BookingDetailTotal',
	props: {
		booking: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {};
	},
	template: `
		<div class="booking-confirm-page-content-booking-detail-line"></div>
		<div class="booking-confirm-page-content-booking-detail-service">
			<div class="booking-confirm-page-content-booking-detail-service-item">
				<div class="booking-confirm-page-content-booking-detail-service-item-total-title">Итого</div>
				<div class="booking-confirm-page-content-booking-detail-service-item-total-price">3800 ₽</div>
			</div>
		</div>
	`,
};
