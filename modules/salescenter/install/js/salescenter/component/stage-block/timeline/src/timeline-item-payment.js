import { CurrencyCore } from 'currency.currency-core';
import { Tag } from 'main.core';

const TimeLineItemPaymentBlock = {
	props: ['item'],
	computed:
		{
			formattedSum()
			{
				const element = Tag.render`<span class="salescenter-app-payment-by-sms-timeline-content-sum">${this.item.sum}</span>`;

				return CurrencyCore.getPriceControl(element, this.item.currencyCode);
			},
		},
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
					<span class="salescenter-app-payment-by-sms-timeline-content-price-cur" v-html="formattedSum"></span>
				</span>
				<span class="salescenter-app-payment-by-sms-timeline-content-text-strong">
					{{item.title}}
				</span>
				<span class="salescenter-app-payment-by-sms-timeline-content-text">
					{{item.content}}
				</span>
			</div>
		</div>
	`,
};

export {
	TimeLineItemPaymentBlock,
};
