import {Vue} from 'ui.vue';
import UseHistoryItem from './mixins/usehistoryitem';
import HistoryItem from './components/history-item';

export default Vue.extend({
	mixins: [UseHistoryItem],
	components: {
		'history-item': HistoryItem,
	},
	computed: {
		isExpectedPriceReceived()
		{
			return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
		}
	},
	template: `
		<history-item :author="author" :createdAt="createdAt">
			<template v-slot:title>
				{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_PRICE_CALCULATION}}
			</template>
			<template v-slot:status>
				<span v-if="!isExpectedPriceReceived" class="crm-entity-stream-content-event-missing">
					{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}
				</span>
			</template>			
			<template v-slot:default>
				<div class="crm-entity-stream-content-detail-description">
					<template v-if="isExpectedPriceReceived">
						{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:
						<span v-html="fields.EXPECTED_PRICE_DELIVERY"></span>
					</template>
					<template v-else>
						{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}
					</template>
				</div>
				<div class="crm-entity-stream-content-detail-description crm-entity-stream-content-delivery-order-value--flex">
					<span v-html="fields.ADDRESS_FROM"></span>
					<span class="crm-entity-stream-content-detail-description--arrow"></span>
					<span v-html="fields.ADDRESS_TO"></span>
				</div>
			</template>
		</history-item>
	`
});
