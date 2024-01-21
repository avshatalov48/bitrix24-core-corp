const TimeLineItemCustomBlock = {
	props: ['item'],
	template: `
		<div class="salescenter-app-payment-by-sms-timeline-item"
			:class="{
				'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled
			}"
		>
			<div class="salescenter-app-payment-by-sms-item-counter">
				<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
				<div class="salescenter-app-payment-by-sms-item-counter-icon " 
					:class="'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon"></div>
			</div>
			<div class="salescenter-app-payment-by-sms-timeline-content" v-html="item.content">
			</div>
		</div>
	`,
};

export {
	TimeLineItemCustomBlock,
};
