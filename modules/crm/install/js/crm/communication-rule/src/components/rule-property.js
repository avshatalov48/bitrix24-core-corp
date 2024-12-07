import { Loc } from 'main.core';

export const RuleProperty = {
	emits: [
		'appendValue',
		'removeValue',
		'inputValue',
		'removePropertyBlock',
	],

	props: {
		id: {
			type: Symbol,
			required: true,
		},
		property: {
			type: Object,
			required: true,
		},
		values: {
			type: Array,
			required: false,
			default: [null],
		},
	},

	methods: {
		appendValue(): void
		{
			this.$emit('appendValue', this.id);
		},
		removeValue(index: number): void
		{
			this.$emit('removeValue', this.id, index);
		},
		inputValue(value: string, index: number): void
		{
			this.$emit('inputValue', this.id, index, value);
		},
		removePropertyBlock(): void
		{
			this.$emit('removePropertyBlock', this.id);
		},
	},

	template: `
		<div class="ui-form-row communication-rule-property-wrapper">
			<div 
				class="communication-rule-property-close"
				@click="removePropertyBlock"
			>
				X
			</div>
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">
					{{ property.title }}
				</div>
			</div>
			<div class="ui-form-content">
				<div
					v-for="(value, index) in values"
					key="index"
					class="ui-ctl ui-ctl-row ui-ctl-w100"
				>
					<div
						v-if="index > 0"
						class="communication-rule-label-or"
					>
						${Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_OR')}
					</div>
					<select
						v-if="property.type === 'enumeration'"
						ref="values"
						class="ui-ctl-element"
						@input="inputValue($event.target.value, index)"
						:value="value ?? ''"
					>
						<option
							v-for="(elementValue, elementIndex) in property.params.list"
							:key="elementIndex"
							:value="elementIndex"
						>
							{{ elementValue }}
						</option>
					</select>
					<input
						v-else
						ref="values"
						type="text"
						class="ui-ctl-element"
						@input="inputValue($event.target.value, index)"
						:value="value ?? ''"
					>
					<div 
						class="communication-rule-rule-value-remove"
						ref="remove"
						@click="removeValue(index)"
					>
						${Loc.getMessage('CRM_COMMUNICATION_RULE_REMOVE_RULE_VALUE')}
					</div>
				</div>
				
				<div class="communication-rule-rule-value-add-wrapper">
					<div
						class="communication-rule-rule-value-add"
						ref="add"
						@click="appendValue"
					>
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_RULE_VALUE')}
					</div>
				</div>
			</div>
		</div>
	`,
};
