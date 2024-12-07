import { Loc } from 'main.core';
import { RuleAction } from './rule-action';

export const RuleActions = {
	components: {
		RuleAction,
	},

	props: {
		actions: {
			type: Array,
			required: true,
			default: [],
		},
		entities: {
			type: Array,
			required: true,
			default: [],
		},
	},

	data(): Object
	{
		return {
			preparedActions: this.getPreparedActions(),
		};
	},

	computed: {
		currentEntity(): ?Object
		{
			return this.getEntityById(this.currentSelectedEntityId);
		},
	},

	methods: {
		reset(): void
		{
			this.preparedActions = this.getPreparedActions();
		},
		getPreparedActions(): Object[]
		{
			const preparedActions = [];
			this.actions.forEach((action) => {
				preparedActions.push({
					id: Symbol('actionId'),
					...action,
				});
			});

			return preparedActions;
		},
		getEntityById(entityTypeId: number): ?Object
		{
			return this.entities.find((entity) => entity.entityTypeId === entityTypeId);
		},
		addAction(): void
		{
			this.preparedActions.push({
				id: Symbol('actionId'),
				type: 'entity',
				data: {},
			});
		},
		removeActionBlock(id: Symbol): void
		{
			const index = this.preparedActions.findIndex((action) => action.id === id);
			if (index >= 0)
			{
				this.preparedActions.splice(index, 1);
			}
		},
		changeActionBlock(id: Symbol, data: Object): void
		{
			const action = this.preparedActions.find((item) => item.id === id);
			if (action)
			{
				action.data = data;
			}
		},
		getData(): Object[]
		{
			const data = [];
			this.preparedActions.forEach((action) => {
				data.push({
					type: action.type,
					data: action.data,
				});
			});

			return data;
		},
	},

	template: `
		<div>
			<div class="communication-rule-title">
				<span class="communication-rule-title-text">
					${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_ACTIONS_SETTINGS_TITLE')}
				</span>
			</div>
			<div class="ui-form">
				<RuleAction
					v-for="action in preparedActions"
					:id="action.id"
					:type="action.type"
					:data="action.data"
					:entities="entities"
					@changeActionBlock="changeActionBlock"
					@removeActionBlock="removeActionBlock"
				/>
				<span
					class="communication-rule-add-rule-property"
					@click="addAction"
				>
					${Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_ACTION')}
				</span>
			</div>	
		</div>
	`,
};