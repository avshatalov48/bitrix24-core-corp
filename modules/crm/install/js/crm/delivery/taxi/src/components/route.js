import UseLocalize from '../mixins/uselocalize';

export default {
	props: {
		from: {required: true, type: String},
		to: {required: true, type: String},
	},
	mixins: [UseLocalize],
	template: `
		<div class="crm-entity-stream-content-delivery-order-item">
			<div class="crm-entity-stream-content-delivery-order-label">
				{{localize.TIMELINE_DELIVERY_TAXI_ROUTE}}
			</div>
			<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm crm-entity-stream-content-delivery-order-value--flex">
				<span v-html="from"></span>
				<span class="crm-entity-stream-content-delivery-order-arrow"></span>
				<span v-html="to"></span>
			</div>
		</div>
	`
};
