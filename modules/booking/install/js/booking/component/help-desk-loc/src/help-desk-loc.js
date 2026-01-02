import { EmptyRichLoc } from './empty-rich-loc';

const DEFAULT_RULES = ['helpdesk'];

// @vue/component
export const HelpDeskLoc = {
	name: 'HelpDeskLoc',
	components: {
		EmptyRichLoc,
	},
	props: {
		message: {
			type: String,
			required: true,
		},
		rules: {
			type: Array,
			default: () => [],
		},
	},
	computed: {
		enabledRules(): Array<string>
		{
			return [...DEFAULT_RULES, ...this.rules];
		},
	},
	template: `
		<EmptyRichLoc
			:message="message"
			:rules="enabledRules"
			v-bind="$attrs"
		/>
	`,
};
