import { TagSelector } from 'ui.entity-selector';
import { Loc, Dom, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import 'ui.icon-set.actions';

import '../styles/employee-item.css';

export const PersonItem = {
	name: 'PersonItem',

	props: {
		config: {
			required: true,
			type: {
				companyId: Number,
				mode: String,
				isHideInfoAlert: Boolean,
			},
		},
		mappedUserIds: {
			required: true,
			type: Array,
		},
		suggestId: {
			required: false,
			type: [Number, null],
		},
	},

	data(): Object {
		return {
			isBorderedEmployee: this.config.mode === 'direct',
		};
	},

	emits: [
		'addEntity',
		'removeEntity',
	],

	mounted(): void
	{
		const selector = this.config.mode === 'direct'
			? this.getPersonTagSelector()
			: this.getUserTagSelector()
		;

		selector.renderTo(this.$refs.container);
	},

	methods: {
		getUserTagSelector(): TagSelector
		{
			let preselectedItem = [];
			if (Type.isNumber(this.suggestId))
			{
				preselectedItem = ['user', this.suggestId];
				this.$emit('addEntity', { id: this.suggestId });
			}

			const selector = new TagSelector({
				multiple: false,
				events: {
					onTagRemove: (event) => {
						const { tag } = event.getData();
						this.handleItemRemove(tag);
					},
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.handleItemSelect(tag);
					},
				},
				addButtonCaption: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SELECTOR_BUTTON_CAPTION'),
				showCreateButton: false,
				dialogOptions: {
					id: 'hcmlink-user-dialog',
					width: 380,
					searchOptions: {
						allowCreateItem: false,
					},
					entities: [
						{
							id: 'user',
							options: {
								'!userId': this.mappedUserIds,
								inviteEmployeeLink: false,
								intranetUsersOnly: true,
							},
						},
					],
					preselectedItems: [preselectedItem],
					tabs: [
						{
							id: 'user',
							title: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_USER'),
						},
					],
					recentTabOptions: {
						visible: false,
					},
				},
			});

			Dom.addClass(selector.getOuterContainer(), 'hr-hcmlink-item-employee__user-container');

			return selector;
		},

		getPersonTagSelector(): TagSelector
		{
			let preselectedItem = [];
			if (Type.isNumber(this.suggestId))
			{
				preselectedItem = ['hcmlink-person-data', this.suggestId];
				this.$emit('addEntity', { id: this.suggestId });
			}

			const selector = new TagSelector({
				multiple: false,
				events: {
					onTagRemove: (event) => {
						const { tag } = event.getData();
						this.handleItemRemove(tag);
					},
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.handleItemSelect(tag);
					},
				},
				addButtonCaption: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SELECTOR_BUTTON_CAPTION'),
				showCreateButton: false,
				tagTextColor: '#333',
				tagBgColor: '#FFF1D6',
				tagFontWeight: '400',
				dialogOptions: {
					id: 'hcmlink-person-dialog',
					enableSearch: true,
					width: 380,
					searchOptions: {
						allowCreateItem: false,
					},
					entities: [
						{
							id: 'hcmlink-person-data',
							options: {
								companyId: this.config.companyId,
								inviteEmployeeLink: false,
							},
							dynamicLoad: true,
							dynamicSearch: true,
							enableSearch: true,
						},
					],
					preselectedItems: [preselectedItem],
					tabs: [
						{
							id: 'persons',
							title: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_PERSON'),
						},
					],
					recentTabOptions: {
						visible: false,
					},
				},
			});

			Dom.addClass(selector.getOuterContainer(), 'hr-hcmlink-item-employee__person-container');

			return selector;
		},

		handleItemRemove(tag)
		{
			this.$emit('removeEntity', { id: tag.id });
		},

		handleItemSelect(tag)
		{
			this.$emit('addEntity', { id: tag.id });
		},
	},

	template: `
		<div 
			class="hr-hcmlink-item-employee__container"
			ref="container"
		></div>
	`,
};
