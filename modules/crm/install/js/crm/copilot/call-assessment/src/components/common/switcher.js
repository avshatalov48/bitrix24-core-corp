export const Switcher = {
	props: {
		id: {
			type: String,
			default: null,
		},
		isChecked: {
			type: Boolean,
			default: true,
		},
		leftItem: {
			type: String,
			required: true,
		},
		rightItem: {
			type: String,
			required: true,
		},
	},

	data(): Object
	{
		return {
			status: this.isChecked ?? false,
		};
	},

	methods: {
		onToggle(): void
		{
			this.status = !this.status;
			this.$emit('onToggle', this.status);
		},
	},

	computed: {
		containerClassList(): []
		{
			return [
				'crm-copilot__call-assessment-switcher',
				{ '--checked': this.status },
			];
		},
		testId(): string
		{
			return `crm-copilot__call-assessment-switcher-${this.id}`;
		},
	},

	template: `
		<div class="crm-copilot__call-assessment-switcher-container">
			<div 
				:class="containerClassList"
				:data-testid="testId"
				@click="onToggle"
			>
				<div class="crm-copilot__call-assessment-switcher-runner"></div>
				<div class="crm-copilot__call-assessment-switcher-text">
					<div class="crm-copilot__call-assessment-switcher-text-item-1">
						{{ leftItem }}
					</div>
					<div class="crm-copilot__call-assessment-switcher-text-item-2">
						{{ rightItem }}
					</div>
				</div>
			</div>
		</div>
	`,
};
