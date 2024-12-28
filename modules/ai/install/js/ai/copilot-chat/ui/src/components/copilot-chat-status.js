import { BIcon, Set } from 'ui.icon-set.api.vue';

import '../css/copilot-chat-status.css';

export const Status = Object.freeze({
	COPILOT_WRITING: 'copilot-writing',
	NONE: 'none',
});

export const CopilotChatStatus = {
	components: {
		BIcon,
	},
	props: {
		status: {
			type: String,
			required: false,
			default: Status.NONE,
		},
	},
	computed: {
		Status(): Status {
			return Status;
		},
		writingStatusIcon(): {name: string, size: number} {
			return {
				name: Set.PENCIL_60,
				size: 14,
				color: '#fff',
			};
		},
		containerClassname(): string[] {
			return [
				'ai__copilot-chat_status',
				`--${this.status}`,
			];
		},
	},
	template: `
		<div class="ai__copilot-chat_status-wrapper">
			<div class="ai__copilot-chat_status">
				<template v-if="status === Status.COPILOT_WRITING">
					<span class="ai__copilot-chat_status-icon --typing">
						<BIcon
							v-bind="writingStatusIcon"
						/>
					</span>
					<span>{{ $Bitrix.Loc.getMessage('AI_COPILOT_CHAT_STATUS_COPILOT_WRITING') }}</span>
				</template>
			</div>
		</div>
	`,
};
