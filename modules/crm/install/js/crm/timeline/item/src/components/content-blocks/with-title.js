export default {
	props: {
		title: String,
		inline: Boolean,
		contentBlock: Object,
	},
	computed: {
		className(): Object
		{
			return {
				'crm-timeline__card-container_info': true,
				'--inline': this.inline,
			}
		}
	},

	template:
		`
			<div :class="className">
				<div class="crm-timeline__card-container_info-title">{{ title }}</div>
				<div class="crm-timeline__card-container_info-value">
					<component :is="contentBlock.rendererName" v-bind="contentBlock.properties"></component>
				</div>
			</div>
		`
};
