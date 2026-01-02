export const Chevron = {
	props: {
		isExpanded: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		classList(): Object
		{
			return {
				'crm-copilot__call-assessment-availability-selector-chevron': true,
				'--expanded': this.isExpanded,
			};
		},
	},

	template: `
		<div :class="classList"></div>
	`,
};
