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
				<div class="crm-entity-stream-content-detail-description crm-delivery-taxi-caption">
					<template v-if="isExpectedPriceReceived">
						{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:
						<span v-html="fields.EXPECTED_PRICE_DELIVERY"></span>
					</template>
					<template v-else>
						{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}
					</template>
				</div>
				<div class="crm-entity-stream-content-detail-description">
					<div class="crm-entity-stream-content-delivery-order-box">
						<div class="crm-entity-stream-content-delivery-order-box-label">{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_FROM}}</div>
						<span v-html="fields.ADDRESS_FROM"></span>
					</div>
					<div class="crm-entity-stream-content-delivery-order-box">
						<div class="crm-entity-stream-content-delivery-order-box-label">{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_TO}}</div>
						<span v-html="fields.ADDRESS_TO"></span>
					</div>
				</div>
			</template>
		</history-item>
	`
});
