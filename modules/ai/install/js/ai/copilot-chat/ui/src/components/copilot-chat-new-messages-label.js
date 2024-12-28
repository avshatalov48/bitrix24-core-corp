import '../css/copilot-chat-new-messages-label.css';

export const containerClassname = 'ai__copilot-chat_new-messages-label';

export const CopilotChatNewMessagesLabel = {
	template: `
		<div class="${containerClassname}">
			{{ $Bitrix.Loc.getMessage('AI_COPILOT_CHAT_NEW_MESSAGES_LABEL') }}
		</div>
	`,
};
