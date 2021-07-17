import {Loc, ajax as Ajax} from 'main.core';
import {Block} from 'salescenter.component.stage-block';
import {StageMixin} from './stage-mixin';
import {Error, SenderList, UserAvatar, MessageEdit, MessageView, MessageEditor, MessageControl} from 'salescenter.component.stage-block.sms-message';
import {Manager} from "salescenter.manager";

const SmsMessage = {
	props: {
		initSenders: {
			type: Array,
			required: true
		},
		initCurrentSenderCode: {
			type: String,
			required: false
		},
		initPushedToUseBitrix24Notifications: {
			type: String,
			required: false
		},
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
		selectedSmsSender: {
			type: String,
			required: false
		},
		phone: {
			type: String,
			required: true
		},
		titleTemplate: {
			type: String,
			required: true
		},
		showHint: {
			type: Boolean,
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
	mixins: [StageMixin],
	components: {
		'stage-block-item':	Block,
		'sms-error-block': Error,
		'sms-sender-list-block': SenderList,
		'sms-user-avatar-block': UserAvatar,
		'sms-message-edit-block': MessageEdit,
		'sms-message-view-block': MessageView,
		'sms-message-editor-block': MessageEditor,
		'sms-message-control-block': MessageControl,
	},
	data()
	{
		return {
			currentSenderCode: null,
			senders: [],
			pushedToUseBitrix24Notifications: null,
			smsSenderListComponentKey: 0,
		}
	},
	computed: {
		configForBlock()
		{
			return {
				counter: this.counter,
				checked: this.counterCheckedMixin,
				showHint: true,
			}
		},
		editor()
		{
			return {
				template: this.editorTemplate,
				url: this.editorUrl
			};
		},
		currentSender()
		{
			return this.senders.find(sender => sender.code === this.currentSenderCode);
		},
		title()
		{
			return this.titleTemplate.replace('#PHONE#', this.phone);
		},
		errors()
		{
			let result = [];

			let bitrix24ConnectUrlError;

			if (!this.currentSender)
			{
				for (let sender of this.senders)
				{
					if (!sender.isAvailable || sender.isConnected)
					{
						continue;
					}

					result.push({
						text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED'),
						fixUrl: sender.connectUrl,
						fixText: Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE'),
					});

					if (sender.code === 'bitrix24')
					{
						bitrix24ConnectUrlError = sender.connectUrl;
					}
				}
			}
			else
			{
				if (!this.currentSender.isAvailable)
				{
					result.push({
						text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_' + this.currentSender.code.toUpperCase() + '_NOT_AVAILABLE'),
					});
				}
				else
				{
					if (this.currentSender.isConnected)
					{
						result = this.currentSender.usageErrors.map(error => ({ text: error }))
					}
					else
					{
						result.push({
							text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_' + this.currentSender.code.toUpperCase() + '_NOT_CONNECTED'),
							fixUrl: this.currentSender.connectUrl,
							fixText: Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE'),
						});

						if (this.currentSender.code === 'bitrix24')
						{
							bitrix24ConnectUrlError = this.currentSender.connectUrl;
						}
					}
				}
			}

			if (!this.phone)
			{
				result.push({text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY')});
			}

			if (this.pushedToUseBitrix24Notifications === 'N' && bitrix24ConnectUrlError)
			{
				Manager.openSlider(bitrix24ConnectUrlError).then(() => this.handleErrorFix());
				BX.userOptions.save('salescenter', 'payment_sender_options', 'pushed_to_use_bitrix24_notifications', 'Y');
				this.pushedToUseBitrix24Notifications = 'Y';
			}

			return result;
		}
	},
	created() {
		this.initialize(this.initCurrentSenderCode, this.initSenders, this.initPushedToUseBitrix24Notifications);
	},
	methods: {
		onItemHint(e)
		{
			BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {width: 1200} );
		},
		initialize(currentSenderCode, senders, pushedToUseBitrix24Notifications)
		{
			this.currentSenderCode = currentSenderCode;
			this.senders = senders;
			this.pushedToUseBitrix24Notifications = pushedToUseBitrix24Notifications;
		},
		handleOnSmsSenderSelected(value)
		{
			this.$emit('stage-block-sms-send-on-change-provider', value);
		},
		handleErrorFix()
		{
			Ajax.runComponentAction("bitrix:salescenter.app", "refreshSenderSettings", {
				mode: "class"
			})
				.then((resolve)=> {
					if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0)
					{
						this.initialize(
							resolve.data.currentSenderCode,
							resolve.data.senders,
							resolve.data.pushedToUseBitrix24Notifications
						);
						this.smsSenderListComponentKey += 1;
					}
				});
		},
	},
	template: `
		<stage-block-item			
			:config="configForBlock"
			:class="statusClassMixin"
			v-on:on-item-hint="onItemHint"
		>
			<template v-slot:block-title-title>{{title}}</template>
			<template
				v-if="showHint"
				v-slot:block-hint-title
			>
				${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS_SHORTER_VERSION')}
			</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin" class="salescenter-app-payment-by-sms-item-container-offtop">
					<sms-error-block
						v-for="error in errors"
						v-on:on-configure="handleErrorFix"
						:error="error"
					>
					</sms-error-block>
					
					<div class="salescenter-app-payment-by-sms-item-container-sms">
						<sms-user-avatar-block :manager="manager"/>
						<div class="salescenter-app-payment-by-sms-item-container-sms-content">
							<sms-message-editor-block :editor="editor"/>
							<template v-if="currentSenderCode === 'bitrix24'">
								<div class="salescenter-app-payment-by-sms-item-container-sms-content-info">
									${Loc.getMessage('SALESCENTER_SEND_ORDER_VIA_BITRIX24')}
									<span @click="BX.Salescenter.Manager.openBitrix24NotificationsHelp(event)">
										${Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT')}
									</span>
								</div>
							</template>
							<template v-else-if="currentSenderCode === 'sms_provider'">
								<sms-sender-list-block
									:key="smsSenderListComponentKey"
									:list="currentSender.smsSenders"
									:initSelected="selectedSmsSender"
									:settingUrl="currentSender.connectUrl"
									v-on:on-configure="handleErrorFix"
									v-on:on-selected="handleOnSmsSenderSelected"
								>
									<template v-slot:sms-sender-list-text-send-from>
										${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER')}
									</template>
								</sms-sender-list-block>
							</template>
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
