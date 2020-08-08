export default {
	props: {
		logo: {required: true, type: String}
	},
	template: `
		<div
			class="crm-entity-stream-content-delivery-title-logo"
			:style="'background: url(' + logo +') center no-repeat;'"
		></div>
	`
};
