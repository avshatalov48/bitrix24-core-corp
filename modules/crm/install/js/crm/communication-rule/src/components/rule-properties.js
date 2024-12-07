import { Loc, Text } from 'main.core';
import { MenuManager } from 'main.popup';
import { LogicSelector } from './logic-selector';
import { RuleProperty } from './rule-property';

const LOGIC_AND = 'AND';
const LOGIC_OR = 'OR';

export const RuleProperties = {
	components: {
		RuleProperty,
		LogicSelector,
	},

	props: {
		properties: {
			type: Object,
			required: true,
			default: {},
		},
		rules: {
			type: Array,
			required: false,
			default: [],
		},
	},

	data(): Object
	{
		return {
			filledProperties: this.rules || [],
		};
	},

	methods: {
		getPropertyByCode(code: string): ?Object
		{
			return this.properties.find((property) => property.code === code) ?? null;
		},
		showRuleSelector(): void
		{
			const menuItems = [];
			const menuParams = {
				closeByEsc: true,
				autoHide: true,
				//offsetLeft: 60,
				angle: true,
				cacheable: false,
			};

			this.properties.forEach((property) => {
				menuItems.push({
					id: `rule-selector-menu-id-${property.code}`,
					onclick: this.onRuleSelectorItemClick.bind(this, property.code),
					html: Text.encode(property.title),
				});
			});

			this.ruleSelector = MenuManager.create(
				'communication-rule-selector',
				this.$refs.showRuleSelector,
				menuItems,
				menuParams,
			);

			this.ruleSelector.show();
		},
		onRuleSelectorItemClick(code: string)
		{
			const id = Symbol('ruleId');

			this.filledProperties.push({
				id,
				code,
				values: [null],
				logic: LOGIC_AND,
			});

			this.ruleSelector.close();
		},
		appendValue(id: Symbol)
		{
			const filledProperty = this.filledProperties.find((property) => property.id === id);
			filledProperty?.values.push(null);
		},
		removeValue(id: Symbol, index: number)
		{
			const filledProperty = this.filledProperties.find((property) => property.id === id);
			filledProperty?.values.splice(index, 1);
		},
		inputValue(id: Symbol, index: number, value: string)
		{
			const filledProperty = this.filledProperties.find((property) => property.id === id);
			if (filledProperty)
			{
				filledProperty.values[index] = value;
			}
		},
		removePropertyBlock(id: Symbol): void
		{
			const index = this.filledProperties.findIndex((property) => property.id === id);
			if (index >= 0)
			{
				this.filledProperties.splice(index, 1);
			}
		},
		onChangeLogicValue(id: Symbol, value: string): void
		{
			const filledProperty = this.filledProperties.find((property) => property.id === id);
			if (filledProperty)
			{
				filledProperty.logic = value;
			}
		},
		getData(): Object[]
		{
			const data = [];
			this.filledProperties.forEach((property) => {
				data.push({
					values: property.values,
					code: property.code,
					logic: property.logic,
				});
			});

			return data;
		},
	},

	template: `
		<div>
			<div class="communication-rule-title">
				<span class="communication-rule-title-text">
					${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_RULES_SETTINGS_TITLE')}
				</span>
			</div>
			<div class="ui-form">
				<div
					class="communication-rule-property-container"
					v-for="(filledProperty, index) in filledProperties"
				>
					<RuleProperty
						:key="filledProperty.code"
						:id="filledProperty.id"
						:property="getPropertyByCode(filledProperty.code)"
						:values="filledProperty.values"
						@appendValue="appendValue"
						@removeValue="removeValue"
						@inputValue="inputValue"
						@removePropertyBlock="removePropertyBlock"
					/>
					<div
						v-if="index < filledProperties.length - 1"
					>
						<LogicSelector
							:id="filledProperty.id"
							:value="filledProperty.logic"
							@onChange="onChangeLogicValue"
						/>
					</div>
				</div>
				<span
					class="communication-rule-add-rule-property"
					@click="showRuleSelector"
					ref="showRuleSelector"
				>
					${Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_RULE')}
				</span>
			</div>
		</div>
	`,
};
