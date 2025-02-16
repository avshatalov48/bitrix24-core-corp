export const ResourceCreationWizardHeader = {
	name: 'ResourceCreationWizardHeader',
	computed: {
		title(): string
		{
			return this.$store.state['resource-creation-wizard'].resourceName;
		},
	},
	template: `
		<span class="resource-creation-wizard__header-title">
			{{ title }}
		</span>
	`,
};
