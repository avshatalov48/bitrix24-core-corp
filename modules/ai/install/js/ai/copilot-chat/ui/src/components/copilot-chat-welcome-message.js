export const CopilotChatWelcomeMessage = {
	props: {
		avatar: String,
		title: String,
		content: String,
	},
	template: `
		<div class="landing__copilot-landing-chat-welcome-message">
			<div class="landing__copilot-landing-chat-welcome-message_avatar-wrapper">
				<img
					class="landing__copilot-landing-chat-welcome-message_avatar"
					src="/dev/ai/copilot-chat/images/avatar-example-4x.png"
					alt="Copilot Designer"
				>
			</div>
			<div class="landing__copilot-landing-chat-welcome-message_content">
				<h6 class="landing__copilot-landing-chat-welcome-message_title">{{ title }}</h6>
				<div v-html="content"></div>
			</div>
		</div>
	`,
};
