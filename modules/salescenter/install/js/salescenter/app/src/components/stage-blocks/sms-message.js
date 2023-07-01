import { Loc, ajax as Ajax } from 'main.core';
import { Block } from 'salescenter.component.stage-block';
import { StageMixin } from './stage-mixin';
import { Error, SenderList, UserAvatar, MessageEdit, MessageView, MessageEditor, MessageControl } from 'salescenter.component.stage-block.sms-message';
import { Manager } from "salescenter.manager";
import { SenderConfig } from "salescenter.lib";
import {UI} from "ui.notification";

const TYPE_PHONE = 'phone';
const TYPE_SENDER = 'sender';

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
			type: Number,
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
		contactEditorUrl: {
			type: String,
			required: true
		},
		ownerTypeId: {
			type: Number,
			required: true
		},
		ownerId: {
			type: Number,
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
		},
		selectedMode: {
			type: String,
			required: true,
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
			contactPhone: this.phone,
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
				hintClassModifier: 'salescenter-app-payment-by-sms-item-title-info--link-gray',
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
			return this.titleTemplate.replace('#PHONE#', this.contactPhone);
		},
		errors()
		{
			let result = [];

			let bitrix24ConnectUrlError;

			if (!this.currentSender)
			{
				for (let sender of this.senders)
				{
					if (!SenderConfig.needConfigure(sender))
					{
						continue;
					}

					result.push({
						text: this.getConnectionErrorText(sender),
						fixer: SenderConfig.openSliderFreeMessages(sender.connectUrl),
						fixText: this.getConnectionErrorFixText(sender),
						type: TYPE_SENDER
					});

					if (sender.code === SenderConfig.BITRIX24)
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
						type: TYPE_SENDER
					});
				}
				else
				{
					if (this.currentSender.isConnected)
					{
						result = this.currentSender.usageErrors.map(error => ({ text: error, type: TYPE_SENDER }))
					}
					else
					{
						result.push({
							text: this.getConnectionErrorText(this.currentSender),
							fixer: this.getFixer(this.currentSender.connectUrl),
							fixText: this.getConnectionErrorFixText(this.currentSender),
							type: TYPE_SENDER
						});

						if (this.currentSender.code === SenderConfig.BITRIX24)
						{
							bitrix24ConnectUrlError = this.currentSender.connectUrl;
						}
					}
				}
			}

			if (!this.contactPhone)
			{
				if(this.contactEditorUrl.length > 0)
				{
					result.push({
						text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'),
						fixer: this.getFixer(this.contactEditorUrl),
						fixText: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY_SETTINGS'),
						type: TYPE_PHONE
					});
				}
				else
				{
					result.push({
						text: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'),
						type: TYPE_PHONE
					});
				}

			}

			if (this.pushedToUseBitrix24Notifications === 'N' && bitrix24ConnectUrlError)
			{
				this.getFixer(bitrix24ConnectUrlError)().then(() => this.handleErrorFix());
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
		onConfigureContactPhone()
		{
			this.$emit('stage-block-sms-message-on-change-contact-phone');
		},
		getConnectionErrorText(sender)
		{
			const messageCode = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED_WARNING';
			const fallback = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED';

			return Loc.getMessage(messageCode) || Loc.getMessage(fallback);
		},
		getConnectionErrorFixText(sender)
		{
			const messageCode = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED_FIX';
			const fallback = 'SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE';

			return Loc.getMessage(messageCode) || Loc.getMessage(fallback);
		},
		getFixer(fixUrl)
		{
			return () => {
				if (typeof fixUrl === 'string')
				{
					return Manager.openSlider(fixUrl, {
						events: {
							onLoad: function(event) {
								const slider = event.getSlider();
								const sliderBx = slider.getFrameWindow().BX;
								sliderBx.addCustomEvent("BX.Crm.EntityEditor:onNothingChanged", () => slider.close());
								sliderBx.addCustomEvent("BX.Crm.EntityEditor:onCancel", () => slider.close());
								sliderBx.addCustomEvent("onCrmEntityUpdate", () => slider.close());
							}
						}
					});
				}

				if (typeof fixUrl === 'object' && fixUrl !== null)
				{
					if (fixUrl.type === 'ui_helper')
					{
						return BX.loadExt('ui.info-helper').then(() =>
						{
							BX.UI.InfoHelper.show(fixUrl.value);
						});
					}
				}

				return Promise.resolve();
			};
		},
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
		handlePhoneErrorFix()
		{
			Ajax.runComponentAction("bitrix:salescenter.app", "refreshContactPhone", {
				mode: "class",
				data: {
					fields:{
						ownerId: this.ownerId,
						ownerTypeId: this.ownerTypeId,
					}
				},
			})
			.then((resolve)=> {
				if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0)
				{
					if(this.contactPhone != resolve.data.contactPhone)
					{
						UI.Notification.Center.notify({
							content: Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_PHONE_CHANGE', {
								'#TITLE#': resolve.data.title
							}),
						});
					}

					this.contactPhone = resolve.data.contactPhone;
					this.onConfigureContactPhone();
				}
			});
		},
		hendleSmsErrorBlock(event)
		{
			if(event.data.type === TYPE_PHONE)
			{
				this.handlePhoneErrorFix()
			}
			else
			{
				this.handleErrorFix()
			}
		},
		openBitrix24NotificationsHelp(event)
		{
			BX.Salescenter.Manager.openBitrix24NotificationsHelp(event);
		},
		openBitrix24NotificationsWorks(event)
		{
			BX.Salescenter.Manager.openBitrix24NotificationsWorks(event);
		},
	},
	//language=Vue
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
				${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS_V3')}
			</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin" class="salescenter-app-payment-by-sms-item-container-offtop">
					<sms-error-block
						v-for="error in errors"
						v-on:on-configure="hendleSmsErrorBlock($event)"
						:error="error"
					>
					</sms-error-block>
					
					<div class="salescenter-app-payment-by-sms-item-container-sms">
						<sms-user-avatar-block :manager="manager"/>
						<div class="salescenter-app-payment-by-sms-item-container-sms-content">
							<div v-if="currentSenderCode === 'bitrix24'" class="salescenter-app-payment-by-sms-item-container-sms-content">
								<div class="salescenter-app-payment-by-sms-item-container-sms-content-message">
									<div contenteditable="false" class="salescenter-app-payment-by-sms-item-container-sms-content-message-text">
										${Loc.getMessage('SALESCENTER_TEMPLATE_BASED_MESSAGE_WILL_BE_SENT')}
										<a @click.stop.prevent="openBitrix24NotificationsHelp(event)" href="#">
											${Loc.getMessage('SALESCENTER_MORE_DETAILS')}
										</a>
									</div>
								</div>
							</div>
							<sms-message-editor-block v-else :editor="editor" :selectedMode="selectedMode"/>
							<template v-if="currentSenderCode === 'bitrix24'">
								<div class="salescenter-app-payment-by-sms-item-container-sms-content-info">
									${Loc.getMessage('SALESCENTER_SEND_ORDER_VIA_BITRIX24')}
									<span @click="openBitrix24NotificationsWorks(event)">
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
