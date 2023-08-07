export default {
	props: {
		contactBlocks: Array,
	},
	template: `
	  	<div class="crm-timeline-block-mail-contacts-wrapper">
			<div class="crm-timeline-block-mail-contact" v-for="(block, index) in contactBlocks">
			  <component :is="block.rendererName" v-bind="block.properties"></component>
			</div>
		</div>
	`
}