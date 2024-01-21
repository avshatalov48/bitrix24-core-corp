import { Vue } from 'ui.vue';

const TimeLineItemContentBlock = {
	props: ['item'],
	computed: {
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_TIMELINE_ITEM_CONTENT_');
		},
	},
	template: `
		<div class="salescenter-app-payment-by-sms-timeline-content">
			<span class="salescenter-app-payment-by-sms-timeline-content-text">
				<slot name="timeline-content-text"></slot>				
				<a :href="item.url" v-if="item.url" target="_blank">
					{{localize.SALESCENTER_TIMELINE_ITEM_CONTENT_VIEW}}
				</a>
			</span>
		</div>
	`,
};

export {
	TimeLineItemContentBlock,
};
