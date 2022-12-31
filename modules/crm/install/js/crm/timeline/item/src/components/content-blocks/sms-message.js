export const SmsMessage = {
	props: {
		contentBlock: Object,
	},
	template: `
		<div
			class="crm-timeline__item_sms-message">
			<span>
				<component v-if="contentBlock" :is="contentBlock.rendererName" v-bind="contentBlock.properties"></component>
			</span>
		</div>
	`
}