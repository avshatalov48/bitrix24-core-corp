import {Loc} from 'main.core';
import {Block} from 'salescenter.component.stage-block';
import {UserAvatar, MessageEditor} from 'salescenter.component.stage-block.sms-message';
import {StageMixin} from './stage-mixin';

const ChatMessage = {
	props: {
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
		},
	},
	mixins: [StageMixin],
	components: {
		'stage-block-item':	Block,
		'chat-user-avatar-block': UserAvatar,
		'chat-message-editor-block': MessageEditor,
	},
	data()
	{
		return {
			currentSenderCode: null,
			senders: [],
			pushedToUseBitrix24Notifications: null,
			smsSenderListComponentKey: 0,
		};
	},
	computed: {
		configForBlock()
		{
			return {
				counter: this.counter,
				checked: this.counterCheckedMixin,
				showHint: true,
			};
		},
		editor()
		{
			return {
				template: this.editorTemplate,
				url: this.editorUrl
			};
		},
		title()
		{
			return this.titleTemplate;
		},
		isMessageReadOnly()
		{
			return this.$root.$app.context !== 'sms';
		},
	},
	created() {
	},
	methods: {
		onItemHint(e)
		{
			BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {width: 1200} );
		},
		openBitrix24NotificationsHelp(event)
		{
			BX.Salescenter.Manager.openBitrix24NotificationsHelp(event);
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
					<div class="salescenter-app-payment-by-sms-item-container-sms">
						<chat-user-avatar-block :manager="manager"/>
						<div class="salescenter-app-payment-by-sms-item-container-sms-content">
							<chat-message-editor-block :editor="editor" :isReadOnly="isMessageReadOnly" :selectedMode="selectedMode"/>
						</div>
					</div>
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	ChatMessage
};
