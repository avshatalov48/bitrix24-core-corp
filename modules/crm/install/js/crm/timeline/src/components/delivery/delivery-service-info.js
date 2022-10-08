export default {
	props: {
		deliveryService: {
			required: true,
			type: Object
		},
	},
	computed: {
		isDeliveryServiceProfile()
		{
			return this.deliveryService.IS_PROFILE;
		},
		deliveryServiceName()
		{
			return this.isDeliveryServiceProfile ? this.deliveryService.PARENT_NAME : this.deliveryService.NAME;
		},
		deliveryProfileServiceName()
		{
			return this.deliveryService.NAME;
		},
		deliveryServiceLogoBackgroundUrl()
		{
			return this.isDeliveryServiceProfile ? this.deliveryService.PARENT_LOGO : this.deliveryService.LOGO;

			return logo
				? {
					'background-image': 'url(' + logo + ')'
				}
				: {};
		},
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-content-delivery-title">
			<div
				v-if="isDeliveryServiceProfile && deliveryService.LOGO"
				class="crm-entity-stream-content-delivery-icon"
				:style="{'background-image': 'url(' + deliveryService.LOGO + ')'}"
			>
			</div>
			<div class="crm-entity-stream-content-delivery-title-contnet">
				<div
					v-if="deliveryServiceLogoBackgroundUrl"
					class="crm-entity-stream-content-delivery-title-logo"
					:style="{'background-image': 'url(' + deliveryServiceLogoBackgroundUrl + ')'}"
				></div>
				<div class="crm-entity-stream-content-delivery-title-info">
					<div class="crm-entity-stream-content-delivery-title-name">
						{{deliveryServiceName}}
					</div>
					<div
						v-if="isDeliveryServiceProfile"
						class="crm-entity-stream-content-delivery-title-param"
					>
						{{deliveryProfileServiceName}}
					</div>
				</div>
			</div>
		</div>
	`
};
