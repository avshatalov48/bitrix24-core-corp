import '../css/copilot-chat-loading-bar.css';

import type { JsonObject } from 'main.core';

export const CopilotChatLoadingBar = {
	name: 'LoadingBar',
	data(): JsonObject
	{
		return {};
	},
	template: `
		<div class="ai__copilot-chat-loading-bar"></div>
	`,
};
