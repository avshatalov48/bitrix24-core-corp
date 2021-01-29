import {Loc} 		from 'main.core';
import {SendMode}	from "./send-mode";

const SendModeEnabled = {
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
			openWhatClientSee(event)
			{
				this.$emit('stage-block-send-mode-enabled-see-client', event)
			},
			onSend(event)
			{
				this.$emit('stage-block-send-mode-enabled-send', event)
			}
		},
	template: `
		<send-mode-block v-on:stage-block-send-mode="onSend($event)" :resend="resend">
			<template v-slot:stage-block-send-mode-slot>
				<div v-on:click="openWhatClientSee($event)"
					class="salescenter-app-add-item-link">${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE')}</div>
			</template>
		</send-mode-block>
	`
};
export {
	SendModeEnabled
}