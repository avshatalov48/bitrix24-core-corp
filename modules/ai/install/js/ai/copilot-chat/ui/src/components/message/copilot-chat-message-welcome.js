import type { CopilotChatMessage } from '../../types';

import '../../css/copilot-chat-message-welcome.css';
export const CopilotChatMessageWelcome = {
	props: {
		message: {
			type: Object,
			required: false,
		},
		avatar: {
			type: String,
			required: false,
		},
	},
	computed: {
		messageInfo(): CopilotChatMessage {
			return this.message;
		},
		title(): string {
			return this.messageInfo.params?.title ?? '';
		},
		subtitle(): string {
			return this.messageInfo.params?.subtitle ?? '';
		},
		content(): string {
			return this.messageInfo.params?.content ?? '';
		},
	},
	template: `
		<div class="ai__copilot-chat-message-welcome">
			<header class="ai__copilot-chat-message-welcome_header">
				<div class="ai__copilot-chat-message-welcome_header-left">
					<img
						:src="avatar"
						alt="#"
						class="ai__copilot-chat-message-welcome_avatar"
					>
				</div>
				<div class="ai__copilot-chat-message-welcome_header-right">
					<h5 class="ai__copilot-chat-message-welcome_title">{{ title }}</h5>
					<p v-if="subtitle" class="ai__copilot-chat-message-welcome_subtitle">{{ subtitle }}</p>
				</div>
			</header>
			<main class="ai__copilot-chat-message-welcome_main">
				<div class="ai__copilot-chat-message-welcome_content">
					<slot name="content">
						{{ content }}
					</slot>
				</div>
			</main>
		</div>
	`,
};
