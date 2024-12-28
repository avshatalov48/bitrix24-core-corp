import { DateTimeFormat } from 'main.date';

import '../css/copilot-chat-messages-date-group.css';

export const CopilotChatMessagesDateGroup = {
	props: {
		date: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		formattedDate(): string
		{
			const format = [
				['today', 'today'],
				['yesterday', 'yesterday'],
				['m', 'l, d F'],
				['', 'l, d F Y'],
			];

			return DateTimeFormat.format(format, new Date(this.date), new Date());
		},
	},
	template: `
		<div class="ai__copilot-chat-messages-date-group">
			<div class="ai__copilot-chat-messages-date-group__date">
				<div class="ai__copilot-chat-messages-date-group__date-label">
					{{ formattedDate }}
				</div>
			</div>
			<div class="ai__copilot-chat-messages-date-group__content">
				<slot></slot>
			</div>
		</div>
	`,
};
