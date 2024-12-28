import '../css/copilot-chat-message.css';

export type CopilotChatMessageDataButton = {
	text: string;
	isSelected: boolean;
}

export const CopilotChatMessage = {
	props: {
		avatar: String,
		avatarAlt: String,
		messageTitle: String,
		messageText: String,
		time: String,
		buttons: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		colorScheme: String,
		status: {
			type: String,
			required: false,
		},
	},
	computed: {
		messageButtons(): CopilotChatMessageDataButton[] {
			return this.buttons;
		},
		formattedTime(): string {
			const date = (new Date(this.time));

			return `${date.getHours()}:${date.getMinutes()}`;
		},
		isUserMessage(): boolean {
			return true; // replace with the actual code
		},
	},
	template: `
		<div
			class="ai__copilot-chat-message"
			:class="'--color-schema-' + colorScheme"
		>
			<div class="ai__copilot-chat-message_avatar-wrapper">
				<img
					class="ai__copilot-chat-message_avatar"
					:src="avatar"
					:alt="avatarAlt"
					:title="avatarAlt"
				>
			</div>
			<div class="ai__copilot-chat-message-content-wrapper">
				<div class="ai__copilot-chat-message-content">
					<div class="ai__copilot-chat-message-content-main">
						<div
							v-if="messageTitle"
							class="ai__copilot-chat-message-title"
						>
							{{ messageTitle }}
						</div>
						<div class="ai__copilot-chat-message-text">
							{{ messageText }}
						</div>
					</div>
					<div class="ai__copilot-chat-message_time">
						{{ formattedTime }}
					</div>
					<div
						v-if="status"
						class="ai__copilot-chat-message_status"
						:class="'--' + status"
					></div>
				</div>
			</div>
			<div v-if="messageButtons.length > 0" class="ai__copilot-chat-message_action-buttons">
				<button
					v-for="button in messageButtons"
					class="ai__copilot-chat-message_action-button"
					:class="{'--selected': button.isSelected}"
				>
					{{ button.text }}
				</button>
			</div>
		</div>
	`,
};
