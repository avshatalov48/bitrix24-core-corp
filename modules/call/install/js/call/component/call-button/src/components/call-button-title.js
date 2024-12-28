// @vue/component
export const CallButtonTitle = {
	name: 'CallButtonTitle',
	props:
	{
		text: {
			type: String,
			required: true,
		},
		compactMode: {
			type: Boolean,
			default: false,
		},
		copilotMode: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		callButtonIconClasses(): Array<String>
		{
			return [
				'bx-call-chat-header-call-button__icon',
				...(this.copilotMode ? ['--copilot'] : []),
				...(this.compactMode ? ['--compact'] : []),
			];
		},
	},
	template: `
		<div :class="callButtonIconClasses"></div>
		<div v-if="!compactMode" class="bx-call-chat-header-call-button__text">
			{{ text }}
		</div>
	`,
};
