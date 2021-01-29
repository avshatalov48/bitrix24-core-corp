import {SendMode} from "./send-mode";

const SendModeDisabled = {
	props: {
		resend: {
			type: Boolean,
			required: true
		}
	},
	components:
		{
			'send-mode-block'	:	SendMode,
		},

	methods:
		{
			onSend(event)
			{
				this.$emit('stage-block-send-mode-disabled-send', event)
			}
		},
	template: `
		<send-mode-block v-on:stage-block-send-mode="onClick($event)" :resend="resend"/>
	`
};
export {
	SendModeDisabled
}