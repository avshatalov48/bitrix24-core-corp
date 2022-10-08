export default {
	props: {
		from: String,
		to: String,
	},
	// language=Vue
	template: `<div class="crm-entity-stream-content-detail-info">
	<span class="crm-entity-stream-content-detain-info-status" v-if="from">{{from}}</span>
	<span class="crm-entity-stream-content-detail-info-separator-icon" v-if="from"></span>
	<span class="crm-entity-stream-content-detain-info-status" v-if="to">{{to}}</span>
	</div>`
};
