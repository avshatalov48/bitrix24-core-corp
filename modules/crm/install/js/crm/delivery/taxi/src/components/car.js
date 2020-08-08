import UseLocalize from '../mixins/uselocalize';

export default {
	props: {
		car: {required: true, type: String},
	},
	mixins: [UseLocalize],
	template: `
		<div class="crm-entity-stream-content-delivery-order-item">
			<div class="crm-entity-stream-content-delivery-order-label">
				{{localize.TIMELINE_DELIVERY_TAXI_CAR}}
			</div>
			<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
				{{car}}
			</div>
		</div>
	`
};
