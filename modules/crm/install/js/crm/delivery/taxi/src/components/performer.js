import UseLocalize from '../mixins/uselocalize';

export default {
	props: {
		name: {required: true, type: String},
		phone: {required: false, type: String},
		phoneExt: {required: false, type: String},
	},
	mixins: [UseLocalize],
	methods: {
		call()
		{
			if (!(this.phone && typeof(top.BXIM)!=='undefined'))
			{
				return;
			}

			top.BXIM.phoneTo(this.phone);
		},
	},
	template: `
		<div class="crm-entity-stream-content-delivery-order-item">
			<div class="crm-entity-stream-content-delivery-order-label">
				{{localize.TIMELINE_DELIVERY_TAXI_DRIVER}}
			</div>
			<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
				<span>
					{{name}}
				</span>
				<span v-if="phone" @click="call" class="crm-entity-stream-content-delivery-link">
					{{localize.TIMELINE_DELIVERY_TAXI_CALL_DRIVER}}
				</span>
				<span
					v-if="phoneExt"
					class="crm-entity-stream-content-delivery-phone-ext"
				>
					{{localize.TIMELINE_DELIVERY_TAXI_CALL_DRIVER_PHONE_EXT_CODE}}: {{phoneExt}}
				</span>
			</div>
		</div>
	`
};
