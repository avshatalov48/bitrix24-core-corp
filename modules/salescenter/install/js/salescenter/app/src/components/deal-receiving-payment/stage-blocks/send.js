import {BlockCounter as Block} 	from 'salescenter.component.stage-block';
import {
	SendModeEnabled as Enabled,
	SendModeDisabled as Disabled
} 								from 'salescenter.component.stage-block.send';

const Send = {
	props: {
		allowed: {
			type: Boolean,
			required: true
		},
		resend: {
			type: Boolean,
			required: true
		}
	},
	components:
		{
			'stage-block-item'			:	Block,
			'send-mode-enabled-block'	:	Enabled,
			'send-mode-disabled-block'	:	Disabled
		},
	computed:
		{
			classes()
			{
				return {
					'salescenter-app-payment-by-sms-item-disabled'	:	this.allowed === false,
					'salescenter-app-payment-by-sms-item'			:	true,
					'salescenter-app-payment-by-sms-item-send'		:	true,
				};
			}
		},
	methods:
		{
			openWhatClientSee(event)
			{
				BX.Salescenter.Manager.openWhatClientSee(event)
			},
			onSend(event)
			{
				this.$emit('stage-block-send-on-send', event)
			}
		},
	template: `
		<stage-block-item
			:class="classes"
		>
			<template v-slot:block-container>
				<send-mode-enabled-block				v-if="allowed"
					:resend="resend" 
					v-on:stage-block-send-mode-enabled-send="onSend"
					v-on:stage-block-send-mode-enabled-see-client="openWhatClientSee"
				/>
				<send-mode-disabled-block 				v-else
					:resend="resend" 
					v-on:stage-block-send-mode-disabled-send="onSend"
				/>
			</template>
		</stage-block-item>
	`
};
export {
	Send
}