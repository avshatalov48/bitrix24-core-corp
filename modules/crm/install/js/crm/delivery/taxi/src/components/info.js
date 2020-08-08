export default {
	props: {
		name: {required: false, type: String},
		method: {required: false, type: String},
	},
	template: `
		<div class="crm-entity-stream-content-delivery-title-info">
			<div v-if="name" class="crm-entity-stream-content-delivery-title-name">
				{{name}}
			</div>
			<div v-if="method" class="crm-entity-stream-content-delivery-title-param">
				{{method}}
			</div>
		</div>
	`
};
