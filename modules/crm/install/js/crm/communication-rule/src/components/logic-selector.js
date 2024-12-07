import { Loc } from 'main.core';

export const LogicSelector = {
	props: {
		id: {
			type: Symbol,
			required: true,
		},
		value: {
			type: String,
			required: true,
		},
	},

	methods: {
		changeLogicSelector(value: string): void
		{
			this.$emit('onChange', this.id, value);
		},
	},

	computed: {
		andClass(): []
		{
			return [
				'communication-rule-property-logic-selector',
				{ '--active': this.value === 'AND' },
			];
		},
		orClass(): []
		{
			return [
				'communication-rule-property-logic-selector',
				{ '--active': this.value === 'OR' },
			];
		},
	},

	template: `
		<div class="communication-rule-property-logic-selector-container">
			<div
				:class="andClass"
				@click="changeLogicSelector('AND')"
			>
				${Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_AND')}
			</div>
			<div
				:class="orClass"
				@click="changeLogicSelector('OR')"
			>
				${Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_OR')}
			</div>
		</div>
	`,
};
