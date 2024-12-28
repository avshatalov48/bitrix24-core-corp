import { Dom } from 'main.core';
import { isMessageFromCopilot } from '../helpers/is-message-from-copilot';
import { CopilotChatMessage } from './copilot-chat-message';
import { CopilotChatWelcomeMessage } from './copilot-chat-welcome-message';
import { CopilotChatMessagesDateGroup } from './copilot-chat-messages-date-group';
import { CopilotChatMessagesAuthorGroup } from './copilot-chat-messages-author-group';
import { type CopilotChatMessage as CopilotChatMessageData, CopilotChatMessageType } from '../types';
import { CopilotChatNewMessageVisibilityObserver } from './directives/copilot-chat-new-message-visibility-observer';
import {
	CopilotChatMessageDefault,
	CopilotChatMessageWelcome,
	CopilotChatMessageSiteWithAi,
	CopilotChatMessageWelcomeFlows, CopilotChatMessageColor,
} from './message';

import '../css/copilot-chat-messages.css';

export const CopilotChatMessages = {
	components: {
		CopilotChatMessage,
		CopilotChatWelcomeMessage,
		CopilotChatMessageDefault,
		CopilotChatMessageWelcome,
		CopilotChatMessageSiteWithAi,
		CopilotChatMessageWelcomeFlows,
		CopilotChatMessagesDateGroup,
		CopilotChatMessagesAuthorGroup,
	},
	emits: ['clickMessageButton'],
	props: {
		messages: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		copilotAvatar: String,
		userAvatar: String,
		copilotMessageTitle: {
			type: String,
			required: false,
			default: '',
		},
		userMessageMenuItems: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		copilotMessageMenuItems: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		welcomeMessageHtmlElement: HTMLElement,
	},
	inject: ['observer'],
	directives: {
		CopilotChatNewMessageVisibilityObserver,
	},
	computed: {
		messagesList(): CopilotChatMessageData[] {
			return this.messages;
		},
		messagesGroupedByDayAndAuthor(): Array {
			const groupsOfMessages = this.groupMessagesByDay(this.messagesList);

			const result = {};

			Object.entries(groupsOfMessages).forEach(([date: string, { messages, isNewMessagesStartHere }]) => {
				result[date] = this.groupMessagesByAuthor(messages, isNewMessagesStartHere);
			});

			return result;
		},
		sortedByDateMessagesGroups(): string[] {
			return Object.keys(this.messagesGroupedByDayAndAuthor).sort();
		},
	},
	methods: {
		groupMessagesByDay(messages: CopilotChatMessageData[]): { [string]: CopilotChatMessageData[] } {
			let isMessagesContainsUnread = false;

			return messages.reduce((groupedMessages, message) => {
				const messageDeliveryDate = new Date(message.dateCreated);
				const messageIsoDate = this.formatISODate(messageDeliveryDate);

				if (groupedMessages[messageIsoDate] === undefined)
				{
					// eslint-disable-next-line no-param-reassign
					groupedMessages[messageIsoDate] = {
						messages: [],
						isNewMessagesStartHere: false,
					};
				}

				if (message.viewed === false && isMessagesContainsUnread === false)
				{
					// eslint-disable-next-line no-param-reassign
					groupedMessages[messageIsoDate].isNewMessagesStartHere = true;
					isMessagesContainsUnread = true;
				}

				groupedMessages[messageIsoDate].messages.push(message);

				return groupedMessages;
			}, {});
		},
		groupMessagesByAuthor(messages: CopilotChatMessageData[], isNewMessagesStartHere: boolean): Array {
			let currentAuthor: number | null = -Infinity;

			let isNewMessagesLabelWasAdded = false;

			return messages.reduce((messagesGroupedByAuthor: Array, message) => {
				if (message.viewed === false && isNewMessagesLabelWasAdded === false && isNewMessagesStartHere === true)
				{
					messagesGroupedByAuthor.push([
						message.authorId,
						[],
						true,
					]);

					isNewMessagesLabelWasAdded = true;
				}
				else if (message.authorId !== currentAuthor)
				{
					messagesGroupedByAuthor.push([
						message.authorId,
						[],
					]);

					currentAuthor = message.authorId;
				}

				messagesGroupedByAuthor.at(-1)[1].push(message);

				return messagesGroupedByAuthor;
			}, []);
		},
		getMessageMenuItems(message: CopilotChatMessageData): Array {
			if (message.authorId === null || message.authorId === undefined)
			{
				return [];
			}

			return isMessageFromCopilot(message.authorId) ? this.copilotMessageMenuItems : this.userMessageMenuItems;
		},
		formatISODate(date: Date): string {
			const year = date.getFullYear();
			const month = (date.getMonth() + 1).toString().padStart(2, '0');
			const day = date.getDate().toString().padStart(2, '0');

			return `${year}-${month}-${day}`;
		},
		getMessageColor(message: CopilotChatMessageData): string {
			if (message.type === CopilotChatMessageType.BUTTON_CLICK_MESSAGE)
			{
				return CopilotChatMessageColor.USER_WITH_HIGHLIGHT_TEXT;
			}

			if (isMessageFromCopilot(message.authorId))
			{
				return CopilotChatMessageColor.COPILOT;
			}

			return CopilotChatMessageColor.USER;
		},
		getMessageComponent(message: CopilotChatMessageData): Object {
			switch (message.type)
			{
				case CopilotChatMessageType.DEFAULT: return CopilotChatMessageDefault;
				case CopilotChatMessageType.WELCOME_FLOWS: return CopilotChatMessageWelcomeFlows;
				case CopilotChatMessageType.WELCOME_SITE_WITH_AI: return CopilotChatMessageSiteWithAi;
				default: return CopilotChatMessageDefault;
			}
		},
		getMessageTitle(authorId: string): ?string {
			return this.isMessageFromCopilot(authorId)
				? this.copilotMessageTitle
				: ''
			;
		},
		getMessageAvatarByAuthorId(authorId: number): ?string {
			return this.isMessageFromCopilot(authorId)
				? this.copilotAvatar
				: this.userAvatar
			;
		},
		getAuthorMessagesGroupAvatar(authorId: number, messages: CopilotChatMessageData[]): string
		{
			const lastMessage = messages.at(-1);
			const isLastMessageIsWelcome = lastMessage.type !== 'Default' && lastMessage.type !== 'ButtonClicked';

			if (authorId === null || authorId === undefined || isLastMessageIsWelcome)
			{
				return null;
			}

			return this.getMessageAvatarByAuthorId(authorId);
		},
		isMessageFromCopilot(authorId: number): boolean {
			return authorId < 1;
		},
		isMessageHaveButtons(message: CopilotChatMessageData): boolean {
			return message?.params?.buttons?.length > 0;
		},
		handleMessageButtonClick(messageId, buttonId): void {
			this.$emit('clickMessageButton', {
				messageId,
				buttonId,
			});
		},
		isLastMessage(dateGroupIndex: number, authorGroupIndex: number, messageIndexAtAuthorGroup: number): boolean {
			return (dateGroupIndex + 1) * (authorGroupIndex + 1) + messageIndexAtAuthorGroup === this.messages.length;
		},
	},
	mounted() {
		Dom.append(this.welcomeMessageHtmlElement, this.$refs.welcomeMessage);
	},
	template: `
		<CopilotChatMessagesDateGroup
			v-for="(date, dateGroupIndex) of sortedByDateMessagesGroups"
			:date="date"
		>
			<CopilotChatMessagesAuthorGroup
				v-for="([authorId, messagesFromCurrentAuthor, showNewMessagesLabel], authorGroupIndex) in messagesGroupedByDayAndAuthor[date]"
				:avatar="getAuthorMessagesGroupAvatar(authorId, messagesFromCurrentAuthor)"
				:show-new-messages-label="showNewMessagesLabel"
			>
				<ul class="ai__copilot-chat-messages">
					<li
						v-for="(message, index) of messagesFromCurrentAuthor"
						class="ai__copilot-chat-messages_message-wrapper"
					>
						<component :is="getMessageComponent(message)" 
								v-copilot-chat-new-message-visibility-observer="message.viewed"
								@buttonClick="handleMessageButtonClick(message.id, $event)"
								:message="message"
								:title="getMessageTitle(message.authorId)"
								:color="getMessageColor(message)"
								:avatar="getMessageAvatarByAuthorId(message.authorId)"
								:useAvatarTail="index === messagesFromCurrentAuthor.length - 1 && isMessageHaveButtons(message) === false"
								:disable-all-actions="isLastMessage(dateGroupIndex, authorGroupIndex, index) === false"
								:menu-items="getMessageMenuItems(message)"
						></component>
					</li>
				</ul>
			</CopilotChatMessagesAuthorGroup>
	
		</CopilotChatMessagesDateGroup>
	`,
};
