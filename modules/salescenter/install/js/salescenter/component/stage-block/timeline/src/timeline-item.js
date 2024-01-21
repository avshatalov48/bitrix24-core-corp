import { TimeLineItemContentBlock } from './timeline-item-content';

const TimeLineItemBlock = {
	props: ['item'],
	components: {
		'timeline-item-content-block': TimeLineItemContentBlock,
	},
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
			<component :is="'timeline-item-content-block'" 
				:item="item">
				<template v-slot:timeline-content-text>{{item.content}}</template>
			</component>
		</div>
	`,
};

export {
	TimeLineItemBlock,
};
