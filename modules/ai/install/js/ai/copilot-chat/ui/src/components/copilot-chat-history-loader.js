import { Loc } from 'main.core';
import { Loader } from 'main.loader';

import '../css/copilot-chat-history-loader.css';

export const CopilotChatHistoryLoader = {
	props: {
		text: {
			type: String,
			required: false,
			default: Loc.getMessage('AI_COPILOT_CHAT_MESSAGES_HISTORY_LOADER_TEXT'),
		},
	},
	beforeMount() {
		const color = getComputedStyle(document.body).getPropertyValue('--ui-color-base-02');

		this.loader = new Loader({
			color,
			size: 60,
		});
	},
	mounted() {
		this.loader.show(this.$refs.loaderContainer);
	},
	unmounted() {
		this.loader.hide();
		this.loader = null;
	},
	template: `
		<div class="ai__copilot-chat-history-loader">
			<div ref="loaderContainer" class="ai__copilot-chat-history-loader_animation-container"></div>
			<div class="ai__copilot-chat_history-loader_text">
				{{ text }}
			</div>
		</div>
	`,
};
