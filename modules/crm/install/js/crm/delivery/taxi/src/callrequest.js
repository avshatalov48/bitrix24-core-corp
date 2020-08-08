import {Vue} from 'ui.vue';
import UseHistoryItem from './mixins/usehistoryitem';
import HistoryItem from "./components/history-item";
import CarLogoInfo from "./components/carlogoinfo";

export default Vue.extend({
	mixins: [UseHistoryItem],
	components: {
		'history-item': HistoryItem,
		'car-logo-info': CarLogoInfo,
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
				{{localize.TIMELINE_DELIVERY_TAXI_SEND_REQUEST_HISTORY_TITLE}}				
			</template>	
			<template v-slot:default>
				<car-logo-info
					:logo="fields.DELIVERY_SYSTEM_LOGO"
					:service-name="fields.DELIVERY_SYSTEM_NAME"
					:method-name="fields.DELIVERY_METHOD"
				>
					<template v-slot:bottom>
						<div class="crm-entity-stream-content-delivery-description">
							<template v-if="isExpectedPriceReceived">
								{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:
								<span v-html="fields.EXPECTED_PRICE_DELIVERY"></span>
							</template>
							<template v-else>
								{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}
							</template>
						</div>
					</template>
				</car-logo-info>
			</template>
		</history-item>
	`
});
