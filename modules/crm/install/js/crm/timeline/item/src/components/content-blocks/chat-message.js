export default {
	props: {
		messageHtml: String,
		isIncoming: Boolean,
	},
	computed: {
		className(): string
		{
			return 'crm-entity-stream-content-detail-IM-message-' + (this.isIncoming ? 'incoming' : 'outgoing');
		},
	},
	// language=Vue
	template: `<div class="crm-entity-stream-content-detail-IM"><div :class="[className]" v-html="messageHtml"></div></div>`
};
