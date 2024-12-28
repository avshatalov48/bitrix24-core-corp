import { bind, Dom, Tag } from 'main.core';
import { type CopilotChat } from 'ai.copilot-chat.ui';
import type { CopilotChatMessage } from '../../types';
import { CopilotChatMessageWelcome } from './copilot-chat-message-welcome';

export const CopilotChatMessageSiteWithAi = {
	components: {
		CopilotChatMessageWelcome,
	},
	props: {
		message: {
			type: Object,
			required: false,
		},
		avatar: {
			type: String,
			required: false,
		},
		disableAllActions: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	inject: ['instance'],
	computed: {
		chatInstance(): CopilotChat {
			return this.instance;
		},
		messageInfo(): CopilotChatMessage {
			return {
				...this.message,
				params: {
					...this.message.params,
					title: 'Привет! Я — Веб-дизайнер',
					subtitle: '',
					content: '',
				},
			};
		},
	},
	methods: {
		renderContent(): void {
			const buttons = this.messageInfo.params?.buttons ?? [];

			const isMessageHaveCreateSiteButton = buttons.length > 0;

			const paragraph2 = isMessageHaveCreateSiteButton
				? this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_WELCOME_MESSAGE_SITE_WITH_AI_2', {
					'#LINK#': `<a href="#" class="${this.disableAllActions ? 'disabled' : ''}" ref="createSiteLink">`,
					'#/LINK#': '</a>',
				}) : ''
			;

			const content = Tag.render`
				<div ref="root">
					<p>${this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_WELCOME_MESSAGE_SITE_WITH_AI_1')}</p>
					<p>${paragraph2}</p>
				</div>
			`;

			if (isMessageHaveCreateSiteButton)
			{
				bind(content.createSiteLink, 'click', () => {
					if (!this.messageInfo.params?.buttons || this.messageInfo.params.buttons.length === 0)
					{
						return;
					}

					this.chatInstance.addUserMessage({
						type: 'ButtonClicked',
						content: this.messageInfo.params?.buttons[0]?.text,
						params: {
							messageId: this.messageInfo.id,
							buttonId: this.messageInfo.params?.buttons[0]?.id,
						},
					});
				});
			}

			this.$refs.content.innerHTML = '';
			Dom.append(content.root, this.$refs.content);
		},
	},
	watch: {
		disableAllActions() {
			this.renderContent();
		},
	},
	mounted() {
		this.renderContent();
	},
	template: `
		<CopilotChatMessageWelcome
			:avatar="avatar"
			:message="messageInfo"
		>
			<template #content>
				<div ref="content"></div>
			</template>
		</CopilotChatMessageWelcome>
	`,
};
