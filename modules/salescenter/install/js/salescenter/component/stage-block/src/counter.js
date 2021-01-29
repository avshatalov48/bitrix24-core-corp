const Counter = {
	template: `
		<div class="salescenter-app-payment-by-sms-item-counter">
			<div class="salescenter-app-payment-by-sms-item-counter-rounder"></div>
			<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
			<div class="salescenter-app-payment-by-sms-item-counter-number">
				<slot name="block-counter-number"/>
			</div>
		</div>
	`
};

export {
	Counter
}
