import UseLocalize from '../mixins/uselocalize';

export default {
	props: {
		from: {required: true, type: String},
		to: {required: true, type: String},
	},
	mixins: [UseLocalize],
	template: `
		<div class="crm-entity-stream-content-delivery-order-item">
			<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
				<div class="crm-entity-stream-content-delivery-order-box">
					<div class="crm-entity-stream-content-delivery-order-box-label">
						{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_FROM}}
					</div>
					<span v-html="from"></span>
				</div>
				<div class="crm-entity-stream-content-delivery-order-box">
					<div class="crm-entity-stream-content-delivery-order-box-label">
						{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_TO}}
					</div>
					<span v-html="to"></span>
				</div>
			</div>
		</div>
	`
};
