import {Loc, ajax as Ajax} 				from 'main.core';
import {BlockNumberTitleHint as Block} from 'salescenter.component.stage-block';
import
{
	Alert,
	Configure,
	SenderList,
	UserAvatar,
	MessageEdit,
	MessageView,
	MessageEditor,
	MessageControl
} 										from 'salescenter.component.stage-block.sms-message';
import {StageMixin} 					from "./stage-mixin";

const SmsMessage = {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: String,
			required: true
		},
		manager: {
			type: Object,
			required: true
		},
		items: {
			type: Array,
			required: true
		},
		phone: {
			type: String,
			required: true
		},
		senderSettingsUrl: {
			type: String,
			required: true
		},
		editorTemplate: {
			type: String,
			required: true
		},
		editorUrl: {
			type: String,
			required: true
		}
	},
	mixins:[StageMixin],
	components:
		{
			'stage-block-item'			:	Block,
			'sms-alert-block'			:	Alert,
			'sms-configure-block'		:	Configure,
			'sms-sender-list-block'		:	SenderList,
			'sms-user-avatar-block'		:	UserAvatar,
			'sms-message-edit-block'	:	MessageEdit,
			'sms-message-view-block'	:	MessageView,
			'sms-message-editor-block'	: 	MessageEditor,
			'sms-message-control-block'	: 	MessageControl,
		},
	data()
	{
		return {
			phone:			this.phone,
			senders:{
				list: 		this.items,
				settings:{
					url:	this.senderSettingsUrl
				},
			},
			manager:{
				name: 		this.manager.name,
				photo: 		this.manager.photo
			},
			editor:{
				template:	this.editorTemplate,
				url: 		this.editorUrl
			}
		}
	},
	computed:
		{
			hasSender()
			{
				return this.senders.list.length !== 0;
			},

			hasPhone()
			{
				return !(this.phone === '');
			},

			containerClass()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-offtop'	: true
				}
			},

			title()
			{
				return Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_SMS').replace('#PHONE#', this.phone);
			}
		},
	methods:
		{
			onItemHint(e)
			{
				this.$root.$emit("on-show-company-contacts", e);
			},

			smsSenderConfigure()
			{
				Ajax.runComponentAction("bitrix:salescenter.app", "getSmsSenderList", {
					mode: "class"
				})
					.then((resolve)=>{
						if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0)
						{
							this.resetSenderList();

							Object.values(resolve.data).forEach(
								item => this.senders.list
									.push({
										name: item.name,
										id: item.id
									}));

							let value = this.getFirstSender();
							this.setSelectedSender(value);
						}
					});
			},

			resetSenderList()
			{
				this.senders.list = [];
			},

			getFirstSender()
			{
				return this.hasSender ? this.senders.list[0].id : null;
			},

			setSelectedSender(value)
			{
				this.$emit('stage-block-sms-send-on-change-provider', value);
			},
		},
	template: `
		<stage-block-item			
			:counter="counter"
			:class="statusClassMixin"
			:checked="counterCheckedMixin"
			v-on:on-item-hint="onItemHint"
		>
			<template v-slot:block-title-title>{{title}}</template>
			<template v-slot:block-hint-title>${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin" :class="containerClass">
					<template v-if="hasSender">
						<sms-alert-block v-if="hasPhone === false">
							<template v-slot:sms-alert-text>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY')}</template>
						</sms-alert-block>
					</template>
					<template v-else>
						<sms-configure-block 
							:url="senders.settings.url"
							v-on:on-configure="smsSenderConfigure"
						>
							<template v-slot:sms-configure-text-alert>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_NOT_CONFIGURED')}</template>
							<template v-slot:sms-configure-text-setting>${Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE')}</template>
						</sms-configure-block>
					</template> 
					
					<div class="salescenter-app-payment-by-sms-item-container-sms">
						
						<sms-user-avatar-block :manager="manager"/>
						
						<div class="salescenter-app-payment-by-sms-item-container-sms-content">
							
							<sms-message-editor-block :editor="editor"/>
							
							<sms-sender-list-block
								:list="items"
								:selected="getFirstSender()"
								:settingUrl="senders.settings.url"
								v-on:on-configure="smsSenderConfigure"
								v-on:on-selected="setSelectedSender"
							>
								<template v-slot:sms-sender-list-text-send-from>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER')}</template>
							</sms-sender-list-block>
						</div>
					</div>
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	SmsMessage
}