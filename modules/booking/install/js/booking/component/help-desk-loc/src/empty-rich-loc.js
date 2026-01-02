import { BaseRichLoc } from './base-rich-loc';
import { ruleRegistry } from './rules/rule-registry';

// @vue/component
export const EmptyRichLoc = {
	name: 'EmptyRichLoc',
	components: {
		BaseRichLoc,
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
			return [...this.rules]
				.map((ruleName) => ruleRegistry[ruleName])
				.filter(Boolean);
		},
	},
	template: `
		<BaseRichLoc
			:message="message"
			:enabled-rules="enabledRules"
			v-bind="$attrs"
		/>
	`,
};
