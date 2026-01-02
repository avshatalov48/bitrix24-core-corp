import { RichLoc } from 'ui.vue3.components.rich-loc';

// @vue/component
export const BaseRichLoc = {
	name: 'BaseRichLoc',
	components: {
		RichLoc,
	},
	props: {
		message: {
			type: String,
			required: true,
		},
		enabledRules: {
			type: Array,
			default: () => [],
		},
	},
	computed: {
		activePlaceholders(): Array<string>
		{
			return this.enabledRules.flatMap((rule) => `[${rule.name}]`);
		},
		rulePropsMap(): Object
		{
			const result = {};

			this.enabledRules.forEach((rule) => {
				const ruleComponent = rule.component;
				result[rule.name] = {};

				if (ruleComponent.props)
				{
					Object.keys(ruleComponent.props).forEach((propName) => {
						if (propName in this.$attrs)
						{
							result[rule.name][propName] = this.$attrs[propName];
						}
					});
				}
			});

			return result;
		},
	},
	template: `
		<RichLoc :text="message" :placeholder="activePlaceholders">
			<template v-for="rule in enabledRules" #[rule.name]="{ text }">
				<component
					:is="rule.component"
					:text="text"
					v-bind="rulePropsMap[rule.name]"
				/>
			</template>
		</RichLoc>
	`,
};
