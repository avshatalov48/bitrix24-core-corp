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
	template: `
		<history-item :author="author" :createdAt="createdAt">
			<template v-slot:title>
				{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLED_BY_DRIVER}}
			</template>
			<template v-slot:status>
				<span class="crm-entity-stream-content-event-process">
					{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLATION}}
				</span>
			</template>			
			<template v-slot:default>
				<car-logo-info
					:logo="fields.DELIVERY_SYSTEM_LOGO"
					:service-name="fields.DELIVERY_SYSTEM_NAME"
					:method-name="fields.DELIVERY_METHOD"
				></car-logo-info>
			</template>
		</history-item>
	`
});
