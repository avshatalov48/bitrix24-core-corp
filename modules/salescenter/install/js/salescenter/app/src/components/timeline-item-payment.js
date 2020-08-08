const TimeLineItemPaymentBlock = {
	props:['item'],
	template: `
		<div class="salescenter-app-payment-by-sms-timeline-item salescenter-app-payment-by-sms-timeline-item-payment"
			:class="{
				'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled
			}"
		>
			<div class="salescenter-app-payment-by-sms-item-counter">
				<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
				<div class="salescenter-app-payment-by-sms-item-counter-icon " 
					:class="'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon"></div>
			</div>
			
			<div class="salescenter-app-payment-by-sms-timeline-content">
				<span class="salescenter-app-payment-by-sms-timeline-content-price">
					<span v-html="item.sum"></span>
					<span class="salescenter-app-payment-by-sms-timeline-content-price-cur" v-html="item.currency"></span>
				</span>
				<span class="salescenter-app-payment-by-sms-timeline-content-text-strong">
					{{item.title}}
				</span>
				<span class="salescenter-app-payment-by-sms-timeline-content-text">
					{{item.content}}
				</span>
			</div>
		</div>
	`
};

export {
	TimeLineItemPaymentBlock,
}