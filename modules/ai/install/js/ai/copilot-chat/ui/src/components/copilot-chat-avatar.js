import '../css/copilot-chat-avatar.css';

export const CopilotChatAvatar = {
	props: {
		src: String,
		alt: String,
	},
	template: `
		<img
			class="ai__copilot-chat-avatar"
			:alt="alt"
			:src="src"
		/>
	`,
};
