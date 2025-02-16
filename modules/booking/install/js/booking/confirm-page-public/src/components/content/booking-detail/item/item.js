export const Item = {
	name: 'BookingDetailItem',
	props: {
		item: {
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
				<div class="booking-confirm-page-content-booking-detail-service-item-summary">
					<div class="booking-confirm-page-content-booking-detail-service-item-summary-title">Массаж расслабл…</div>
					<div class="booking-confirm-page-content-booking-detail-service-item-summary-duration">1 час</div>
				</div>
				<div class="booking-confirm-page-content-booking-detail-service-item-price">1800 ₽</div>
			</div>
		</div>
	`,
};
