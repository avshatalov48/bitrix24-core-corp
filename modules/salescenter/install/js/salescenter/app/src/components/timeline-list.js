import {Vue} from "ui.vue";
import {TimeLineItemBlock} from "./timeline-item";
import {TimeLineItemPaymentBlock} from "./timeline-item-payment";

const TimeLineListBlock = {
	props:['items'],
	components: {
		'timeline-item-block': TimeLineItemBlock,
		'timeline-item-payment-block': TimeLineItemPaymentBlock,
	},
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_TIMELINE_');
		},
	},
	data()
	{
		return {};
	},	
	template: `
		<div class="salescenter-app-payment-by-sms-timeline">
			<template v-for="(item) in items" >
				<component v-if="item.type == 'payment'" :is="'timeline-item-payment-block'" :item="item"/>
				<component v-else :is="'timeline-item-block'" :item="item"/>
			</template>
		</div>
	`
};

export {
	TimeLineListBlock,
}