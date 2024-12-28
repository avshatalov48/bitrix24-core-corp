import { TagSelector } from 'ui.entity-selector';
import { Loc } from 'main.core';
import 'ui.icon-set.actions';

import '../styles/employee-item.css';
import { BaseEvent } from 'main.core.events';

export const PersonItem = {
	name: 'PersonItem',

	props: {
		config: {
			required: true,
			type: Object,
		},
		mappedUserIds: {
			required: true,
			type: Array,
		},
	},

	data() {
		return {
			isBorderedEmployee: this.config.mode === 'direct',
		};
	},

	emits: [
		'addEntity',
		'removeEntity',
	],

	mounted()
	{
		const selector = this.config.mode === 'direct'
			? this.getPersonTagSelector()
			: this.getUserTagSelector()
		;

		selector.renderTo(this.$refs.container);
	},

	methods: {
		getUserTagSelector()
		{
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

			selector.getOuterContainer().style.width = '100%';

			return selector;
		},

		getPersonTagSelector()
		{
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

			selector.getOuterContainer().style.border = 'none';
			selector.getOuterContainer().style.width = '100%';

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
			:class="{'hr-hcmlink-selector-entity__border': isBorderedEmployee}"
			ref="container"
		></div>
	`,
};
