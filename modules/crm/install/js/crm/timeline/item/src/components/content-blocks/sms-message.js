export const SmsMessage = {
	props: {
		text: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		messageHtml(): string
		{
			return BX.util.htmlspecialchars(this.text).replace(/\r\n|\r|\n/g, '<br/>');
		}
	},
	template: `
		<div
			class="crm-timeline__item_sms-message">
			<span v-if="messageHtml" v-html="messageHtml"></span>
		</div>
	`
}
