import '../css/copilot-chat-load-history-error.css';

export const CopilotChatLoadHistoryError = {
	events: ['retryButtonClick'],
	methods: {
		loc(phraseCode: string): ?string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
		handleClickOnRetryButton(event: MouseEvent): void
		{
			const target = event.target;

			target.blur();

			this.$emit('retryButtonClick');
		},
	},
	template: `
		<div class="ai__copilot-chat-load-history-error">
			<div class="ai__copilot-chat-load-history-error_icon-wrapper">
				<div class="ai__copilot-chat-load-history-error_icon"></div>
			</div>
			<div class="ai__copilot-chat-load-history-error_title">
				{{ loc('AI_COPILOT_CHAT_LOAD_HISTORY_ERROR_TITLE') }}
			</div>
			<div class="ai__copilot-chat-load-history-error_text">
				{{ loc('AI_COPILOT_CHAT_LOAD_HISTORY_ERROR_TEXT') }}
			</div>
			<button
				class="ai__copilot-chat-load-history-error_button"
				@click="handleClickOnRetryButton"
			>
				{{ loc('AI_COPILOT_CHAT_LOAD_HISTORY_ERROR_RETRY') }}
			</button>
		</div>
	`,
};
