import {Loc} 					from 'main.core';

const SendMode = {
	props: {
		resend: {
			type: Boolean,
			required: true
		}
	},
	methods:
		{
			onClick(event)
			{
				this.$emit('stage-block-send-mode', event)
			}
		},
	template: `
		<div class="salescenter-app-payment-by-sms-item-container">
			<div class="salescenter-app-payment-by-sms-item-container-payment">
				<div class="salescenter-app-payment-by-sms-item-container-payment-inline">
					<div v-if="resend"
						v-on:click="onClick($event)"
						class="ui-btn ui-btn-lg ui-btn-success ui-btn-round">${Loc.getMessage('SALESCENTER_RESEND')}</div>
					<div v-else
						v-on:click="onClick($event)"
						class="ui-btn ui-btn-lg ui-btn-success ui-btn-round">${Loc.getMessage('SALESCENTER_SEND')}</div>
					<slot name="stage-block-send-mode-slot"/>
				</div>
			</div>
		</div>
	`
};
export {
	SendMode
}