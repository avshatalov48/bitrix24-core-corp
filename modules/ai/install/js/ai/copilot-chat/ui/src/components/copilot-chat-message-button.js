import { BIcon } from 'ui.icon-set.api.vue';

export const CopilotChatMessageButton = {
	components: {
		BIcon,
	},
	props: {
		icon: {
			type: String,
			required: true,
		},
		class: Array | Object | String,
	},
	computed: {
		menuIconProps(): { name: string, size: number} {
			return {
				name: this.icon,
				size: 22,
			};
		},
		className(): Array | Object | string {
			return this.class;
		},
	},
	template: `
		<button
			class="ai__copilot-chat-message-menu"
			:class="className"
		>
			<BIcon v-bind="menuIconProps"></BIcon>
		</button>
	`,
};
