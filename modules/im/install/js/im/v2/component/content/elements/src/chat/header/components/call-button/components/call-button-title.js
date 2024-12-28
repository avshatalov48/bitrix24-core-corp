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
	},
	template: `
		<div v-if="compactMode" class="bx-im-chat-header-call-button__icon"></div>
		<div v-else class="bx-im-chat-header-call-button__text">
			{{ text }}
		</div>
	`,
};
