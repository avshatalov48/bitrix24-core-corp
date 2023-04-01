export default {
	props: {
		from: Object,
		to: Object,
	},
	// language=Vue
	template: `<div class="crm-entity-stream-content-detail-info">
	<component :is="from.rendererName" v-if="from" v-bind="from.properties"></component>
	<span class="crm-entity-stream-content-detail-info-separator-icon" v-if="from"></span>
	<component :is="to.rendererName" v-if="to" v-bind="to.properties"></component>
	</div>`
};
