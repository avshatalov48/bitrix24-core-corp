import type { CopilotChatMessage } from '../../types';
import { CopilotChatMessageWelcome } from './copilot-chat-message-welcome';

export const CopilotChatMessageWelcomeFlows = {
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
	},
	computed: {
		messageInfo(): CopilotChatMessage {
			return this.message;
		},
	},
	template: `
		<CopilotChatMessageWelcome
			:avatar="avatar"
			:message="messageInfo"
		/>
	`,
};
