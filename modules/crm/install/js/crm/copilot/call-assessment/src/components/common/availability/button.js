export const Button = {
	props: {
		action: {
			type: String,
			required: true,
		},
		index: {
			type: Number,
			required: true,
		},
	},

	computed: {
		classList(): Object
		{
			return {
				'crm-copilot__call-assessment-availability-selector-button': true,
				'--add': this.action === 'add',
				'--remove': this.action !== 'add',
			};
		},
	},

	methods: {
		onClick(): void
		{
			const { action, index } = this;

			this.$emit('button-click', {
				index,
				action,
			});
		},
	},

	template: `
		<div :class="classList" @click="onClick"></div>
	`,
};
