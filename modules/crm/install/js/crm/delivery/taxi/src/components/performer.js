import UseLocalize from '../mixins/uselocalize';

export default {
	props: {
		name: {
			required: true,
			type: String,
		},
		phone: {
			required: false,
			type: String,
		},
		phoneExt: {
			required: false,
			type: String,
		},
		canUseTelephony: {
			required: false,
			type: Boolean,
			default: false,
		},
	},
	mixins: [UseLocalize],
	methods: {
		call()
		{
			if (!this.phone)
			{
				return;
			}

			if (this.canUseTelephony && typeof(top.BXIM)!=='undefined')
			{
				top.BXIM.phoneTo(this.phone);
			}
			else
			{
				window.location.href='tel:' + this.phone;
			}
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
