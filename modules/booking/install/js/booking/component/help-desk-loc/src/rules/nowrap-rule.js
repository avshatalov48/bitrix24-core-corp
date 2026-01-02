// @vue/component
export const NoWrapRule = {
	name: 'NoWrapRule',
	props: {
		// default property for every rule
		text: String,
	},
	template: `
		{{ text }}
	`,
};
