import { CopilotChatNewMessagesLabel } from './copilot-chat-new-messages-label';

import '../css/copilot-chat-messages-author-group.css';

export const CopilotChatMessagesAuthorGroup = {
	components: {
		CopilotChatNewMessagesLabel,
	},
	props: {
		avatar: {
			type: String,
			required: false,
		},
		showNewMessagesLabel: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	template: `
		<CopilotChatNewMessagesLabel v-if="showNewMessagesLabel" />
		<div class="ai__copilot-chat_messages-author-group">
			<div class="ai__copilot-chat_messages-author-group__avatar">
				<img v-if="avatar" :src="avatar" alt="#">
			</div>
			<div class="ai__copilot-chat_messages-author-group__messages">
				<slot></slot>
			</div>
		</div>
	`,
};
