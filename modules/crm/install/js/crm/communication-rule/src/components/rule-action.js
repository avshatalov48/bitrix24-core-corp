import { Loc } from 'main.core';

export const RuleAction = {
	emits: [
		'removeActionBlock',
		'addActionBlock',
		'changeActionBlock',
	],

	props: {
		id: {
			type: Symbol,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
		data: {
			type: Object,
			required: false,
			default: {},
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
			currentActionCategory: this.data.actionCategory ?? null,
			currentSelectedEntityId: this.data.entityTypeId ?? null,
			currentSelectedCategoryId: this.data.categoryId ?? null,
			entityReuseMode: this.data.entityReuseMode ?? null,
			searchStrategy: this.data.searchStrategy ?? null,
		};
	},

	computed: {
		currentEntity(): ?Object
		{
			return this.getEntityById(this.currentSelectedEntityId);
		},
	},

	methods: {
		getEntityById(entityTypeId: number): ?Object
		{
			return this.entities.find((entity) => entity.entityTypeId === Number(entityTypeId)) ?? null;
		},
		removeActionBlock(): void
		{
			this.$emit('removeActionBlock', this.id);
		},
		emitChanged(): void
		{
			const data = {
				actionCategory: this.currentActionCategory,
				entityTypeId: this.currentSelectedEntityId,
				categoryId: this.currentSelectedCategoryId,
				entityReuseMode: this.entityReuseMode,
				searchStrategy: this.searchStrategy,
			};

			this.$emit('changeActionBlock', this.id, data);
		},
	},

	watch: {
		currentSelectedEntityId(): void
		{
			this.emitChanged();
		},
		currentSelectedCategoryId(): void
		{
			this.emitChanged();
		},
		entityReuseMode(): void
		{
			this.emitChanged();
		},
		searchStrategy(): void
		{
			this.emitChanged();
		},
		currentActionCategory(): void
		{
			this.emitChanged();
		},
	},

	created()
	{
		this.$watch(
			'data',
			(data: Object) => {
				this.currentActionCategory = data.actionCategory ?? null;
				this.currentSelectedEntityId = data.entityTypeId ?? null;
				this.currentSelectedCategoryId = data.categoryId ?? null;
				this.entityReuseMode = data.entityReuseMode ?? null;
				this.searchStrategy = data.searchStrategy ?? null;
			},
			{
				deep: true,
			},
		);
	},

	template: `
		<div class="communication-rule-action-wrapper">
			<div
				class="communication-rule-property-close"
				@click="removeActionBlock"
			>
				X
			</div>
			
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentActionCategory">
							<option value="entity">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_ITEM_CREATE')}</option>
							<option value="exit">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_EXIT')}</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="currentActionCategory === 'entity'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentSelectedEntityId">
							<option
								v-for="entity in entities"
								:key="entity.entityTypeId"
								:value="entity.entityTypeId"
							>
								{{ entity.name }}
							</option>
						</select>
					</div>
				</div>
			</div>
			
			<div
				v-if="currentActionCategory === 'entity' && currentEntity?.categories?.length > 1"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_CATEGORY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentSelectedCategoryId">
							<option
								v-for="category in currentEntity?.categories"
								:key="category.id"
								:value="category.id"
							>
								{{ category.name }}
							</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="currentActionCategory === 'entity'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="entityReuseMode">
							<option value="new">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE_NEW')}</option>
							<option value="exist">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE_EXIST')}</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="entityReuseMode === 'exist'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="searchStrategy">
							<option value="1">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY_MAX_CREATE')}</option>
							<option value="2">${Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY_MAX_UPDATE')}</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	`,
};