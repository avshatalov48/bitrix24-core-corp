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
	methods: {
		setupSmsProvider()
		{
			if (!this.fields.SMS_PROVIDER_SETUP_LINK)
			{
				return;
			}

			BX.SidePanel.Instance.open(this.fields.SMS_PROVIDER_SETUP_LINK, {cacheable: false});
		}
	},
	template: `
		<history-item :author="author" :createdAt="createdAt">
			<template v-slot:title>
				{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_TITLE}}
			</template>
			<template v-slot:status>
				<span class="crm-entity-stream-content-event-missing">
					{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}
				</span>
			</template>			
			<template v-slot:default>
				<div class="crm-entity-stream-content-delivery-description">
					{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_DETAIL}}
				</div>
				<div class="crm-entity-stream-content-detail-notice">
					<a v-if="fields.SMS_PROVIDER_SETUP_LINK" @click="setupSmsProvider" href="#" class="crm-entity-stream-content-detail-target">
						{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_SETUP}}							
					</a>
				</div>
			</template>
		</history-item>
	`
});
