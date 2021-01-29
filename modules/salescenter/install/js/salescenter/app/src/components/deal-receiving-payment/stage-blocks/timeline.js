import {
	TimeLineItemBlock as Item,
	TimeLineItemPaymentBlock as PaymentItem
} 												from 'salescenter.component.stage-block.timeline';
import {Payment} 								from 'salescenter.timeline';

const TimeLine = {
	props:{
		timelineItems: {
			type: Array,
			required: true
		},
	},
	components: {
		'timeline-item-block'			: Item,
		'timeline-item-payment-block'	: PaymentItem,
	},

	methods:
		{
			isPayment(item)
			{
				return item.type === Payment.type();
			}
		},
	template: `
		<div class="salescenter-app-payment-by-sms-timeline">
			<template v-for="(item) in timelineItems">
				<timeline-item-payment-block	:item="item"	v-if="isPayment(item)"/>
				<timeline-item-block			:item="item" 	v-else/>
			</template>
		</div>
	`
};

export {
	TimeLine,
}