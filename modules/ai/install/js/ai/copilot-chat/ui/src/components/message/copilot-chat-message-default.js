import { Dom } from 'main.core';
import { Set } from 'ui.icon-set.api.core';
import { type MenuItem } from 'main.popup';
import { isMessageFromCopilot } from '../../helpers/is-message-from-copilot';
import { CopilotChatMessageMenu } from './copilot-chat-message-menu';
import type { CopilotChatMessage, CopilotChatMessageButton } from '../../types';
import { HtmlFormatter } from 'ui.bbcode.formatter.html-formatter';
import { CopilotChatMessageStatus } from '../../copilot-chat';

import '../../css/copilot-chat-message.css';

export const CopilotChatMessageDefault = {
	components: {
		CopilotChatMessageMenu,
	},
	emits: ['buttonClick', 'remove', 'retry'],
	props: {
		message: {
			type: Object,
			required: true,
		},
		avatar: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: false,
		},
		useAvatarTail: {
			type: Boolean,
			required: false,
			default: false,
		},
		menuItems: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		color: {
			type: String,
			required: false,
			default: '',
		},
		disableAllActions: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		messageData(): CopilotChatMessage {
			return this.message;
		},
		messageContent(): string {
			return this.messageData.content;
		},
		isMessageFromCopilot(): boolean {
			return isMessageFromCopilot(this.messageData.authorId);
		},
		formattedMessageContent(): DocumentFragment {
			const htmlFormatter = new HtmlFormatter({
				containerMode: 'collapsed',
			});

			return htmlFormatter.format({
				source: this.messageContent,
			});
		},
		formattedDeliveryTime(): string {
			return this.formatTime(this.messageData.dateCreate);
		},
		messageButtons(): CopilotChatMessageButton[] {
			return this.messageData.params?.buttons ?? [];
		},
		isSomeButtonSelected(): boolean {
			return this.messageButtons.some((button) => button.selected);
		},
		showMenuButton(): boolean {
			return this.menuItems?.length > 0;
		},
		isErrorMessage(): boolean {
			return this.messageData.status === CopilotChatMessageStatus.ERROR;
		},
		errorMessageMenuItems(): MenuItem[] {
			return [
				{
					id: 'retry',
					text: this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_RETRY_MESSAGE'),
					onclick: () => {
						this.$emit('retry', this.messageData.id);
					},
				},
				{
					id: 'remove',
					text: this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_REMOVE_MESSAGE'),
					onclick: () => {
						this.$emit('remove', this.messageData.id);
					},
				},
			];
		},
		Icons() {
			return Set;
		},
	},
	methods: {
		formatTime(dateTime: string): string {
			if (!dateTime)
			{
				return '';
			}

			const date = new Date(dateTime);

			const hours: string = date.getHours().toString().padStart(2, '0');
			const minutes: string = date.getMinutes().toString().padStart(2, '0');

			return `${hours}:${minutes}`;
		},
	},
	mounted() {
		if (this.isMessageFromCopilot)
		{
			Dom.append(this.formattedMessageContent, this.$refs.content);
		}
		else
		{
			this.$refs.content.innerText = this.messageContent;
		}
	},
	template: `
		<div
			:data-id="messageData.id"
			class="ai__copilot-chat-message"
			:class="'--color-schema-' + color"
		>
			<div
				class="ai__copilot-chat-message-content-wrapper"
				:class="{ '--with-tail': useAvatarTail }"
			>
				<div class="ai__copilot-chat-message-content">
					<div class="ai__copilot-chat-message-content-main">
						<div
							v-if="title"
							class="ai__copilot-chat-message-title"
						>
							{{ title }}
						</div>
						<div class="ai__copilot-chat-message-text" ref="content"></div>
					</div>
					<div class="ai__copilot-chat-message_status-info">
						<div
							v-if="messageData.dateCreate"
							class="ai__copilot-chat-message_time"
						>
							{{ formattedDeliveryTime }}
						</div>
						<div
							v-if="messageData.status && isMessageFromCopilot === false"
							class="ai__copilot-chat-message_status"
							:class="'--' + messageData.status"
						></div>
					</div>
				</div>
				<div v-if="messageButtons.length > 0" class="ai__copilot-chat-message_action-buttons">
					<button
						v-for="button in messageButtons"
						class="ai__copilot-chat-message_action-button"
						:class="{'--selected': button.selected}"
						:disabled="isSomeButtonSelected || disableAllActions"
						@click="$emit('buttonClick', button.id)"
					>
						{{ button.title }}
					</button>
				</div>
			</div>
			<div
				v-if="showMenuButton"
				class="ai__copilot-chat-message_menu"
			>
				<CopilotChatMessageMenu
					:menu-items="menuItems"
					:message="message"
				/>
			</div>
			<div
				v-else-if="isErrorMessage"
				class="ai__copilot-chat-message_retry-button"
			>
				<CopilotChatMessageMenu
					:menu-items="errorMessageMenuItems"
					:message="message"
					:icon="Icons.REDO_1"
				/>
			</div>
		</div>
	`,
};
