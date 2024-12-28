import { hint } from 'ui.vue3.directives.hint';

import { Type, Loc } from 'main.core';

import { MessageHeader } from 'im.v2.component.message.elements';
import { BaseMessage } from 'im.v2.component.message.base';
import { DateCode, DateFormatter } from 'im.v2.lib.date-formatter';
import { CallManager } from 'im.v2.lib.call';
import { Messenger } from 'im.public';
import { ChatType } from 'im.v2.const';
import { Analytics } from 'call.lib.analytics';

import './css/call-message.css';

import type { ImModelMessage } from 'im.v2.model';
import type { ImModelChat } from 'im.v2.model';

type ComponentParams = {
	messageType: $Values<typeof MESSAGE_TYPE>,
	messageText: string,
	callId: number,
	initiatorId: number,
}

const MESSAGE_TYPE = {
	start: 'START',
	finish: 'FINISH',
	declined: 'DECLINED',
	busy: 'BUSY',
	missed: 'MISSED',
}

// @vue/component
export const CallMessage = {
	name: 'CallMessage',
	components: { BaseMessage, MessageHeader },
	directives: { hint },
	props:
	{
		item: {
			type: Object,
			required: true,
		},
		dialogId: {
			type: String,
			required: true,
		},
		withTitle: {
			type: Boolean,
			default: true,
		},
	},
	data(): Object
	{
		return {
			showHint: false,
		};
	},
	created()
	{
		this.hintTimeout = null
	},
	computed:
	{
		message(): ImModelMessage
		{
			return this.item;
		},
		componentParams(): ComponentParams
		{
			return this.item.componentParams;
		},
		messageIconClasses(): [string]
		{
			const result = ['bx-call-message__icon'];

			switch (this.componentParams.messageType)
			{
				case MESSAGE_TYPE.start:
					result.push('bx-call-message__icon--secondary');
					break;
				case MESSAGE_TYPE.finish:
					result.push('bx-call-message__icon--primary');
					break;
				case MESSAGE_TYPE.declined:
				case MESSAGE_TYPE.busy:
				case MESSAGE_TYPE.missed:
					result.push('bx-call-message__icon--danger');
					break;
				default:
					result.push('bx-call-message__icon--secondary');
					break;
			}

			return result;
		},
		messageText(): string
		{
			return this.componentParams.messageText
		},
		formattedDate(): string
		{
			return DateFormatter.formatByCode(this.message.date, DateCode.shortTimeFormat);
		},
		currentCall()
		{
			return CallManager.getInstance().getCurrentCall();
		},
		hasActiveCurrentCall(): boolean
		{
			return CallManager.getInstance().hasActiveCurrentCall(this.dialogId);
		},
		hasActiveAnotherCall(): boolean
		{
			return CallManager.getInstance().hasActiveAnotherCall(this.dialogId);
		},
		dialog(): ImModelChat
		{
			return this.$store.getters['chats/get'](this.dialogId, true);
		},
		isConference(): boolean
		{
			return this.dialog.type === ChatType.videoconf;
		},
		hintContent(): Object | null
		{
			if (!this.showHint)
			{
				return null;
			}

			return {
				text: this.loc('CALL_MESSAGE_HAS_ACTIVE_CALL_HINT'),
				popupOptions: {
					bindOptions: {
						position: 'top',
					},
					angle: { position: 'bottom' },
					targetContainer: document.body,
					offsetLeft: 65,
					offsetTop: 0,
				},
			};
		},
	},
	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return Loc.getMessage(phraseCode, replacements);
		},
		onMessageClick()
		{
			if (this.hasActiveAnotherCall)
			{
				this.showHint = true;
				clearTimeout(this.hintTimeout);
				this.hintTimeout = setTimeout(() => this.showHint = false, 10000);

				return;
			}

			this.componentParams.messageType === MESSAGE_TYPE.start
				? Analytics.getInstance().onStartCallMessageClick({ dialog: this.dialog })
				: Analytics.getInstance().onFinishCallMessageClick({ dialog: this.dialog });

			this.isConference
				? Messenger.openConference({ code: this.dialog.public.code })
				: Messenger.startVideoCall(this.dialogId);
		},
	},
	template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withContextMenu="false"
			:withBackground="true"
			:withReactions="false"
			class="bx-call-message__scope"
		>
			<div class="bx-call-message__container">
				<div class="bx-call-message__content-wrapper">
					<MessageHeader :withTitle="withTitle" :item="item" />
					<div :key="showHint" class="bx-call-message__content" v-hint="hintContent" @click="onMessageClick">
						<div :class="messageIconClasses"></div>
						<div class="bx-call-message__text-container">
							<div class="bx-call-message__text">{{ messageText }}</div>
							<div class="bx-im-message-status__date bx-call-message__date">
								{{ formattedDate }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</BaseMessage>
	`,
};
