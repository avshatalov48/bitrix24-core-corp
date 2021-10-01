import {Text} from 'main.core';

const MessageView = {
	props: {
		text: {
			type: String,
			required: true
		},
		orderPublicUrl: {
			type: String,
			required: true
		}
	},
	computed:
		{
			classObject()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-sms-content-message-text': true,
				}
			}
		},
	methods:
		{
			onMouseenter(e)
			{
				this.$emit('view-on-mouseenter', e);
			},
			onMouseleave()
			{
				this.$emit('view-on-mouseleave');
			},

			getSmsMessage()
			{
				let link = `<span class="salescenter-app-payment-by-sms-item-container-sms-content-message-link">${this.orderPublicUrl}</span><span class="salescenter-app-payment-by-sms-item-container-sms-content-message-link-ref">xxxxx</span>` + ` `;
				let text = this.text;

				return Text.encode(text).replace(/#LINK#/g, link);
			},
		},
	template: `
		<div 
			v-html="getSmsMessage()"
			contenteditable="false" 
			:class="classObject" 
			v-on:mouseenter="onMouseenter($event)" 
			v-on:mouseleave="onMouseleave"
		/>
	`
};

export {
	MessageView
}