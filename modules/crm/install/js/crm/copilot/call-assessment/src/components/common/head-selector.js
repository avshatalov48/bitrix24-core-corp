import { Loc } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import { hint } from 'ui.vue3.directives.hint';
import { headOfDepartment } from './head-department';

export const HeadSelector = {
	directives: { hint },

	props: {
		selectedItems: {
			type: Array,
			required: true,
			default: null,
		},
		isReadOnly: {
			type: Boolean,
			default: false,
		},
	},

	data(): Object
	{
		return {
			currentHeadTitle: this.selectedItems[0]?.title ?? headOfDepartment.title,
		};
	},

	methods: {
		showDialog(): void
		{
			if (this.isReadOnly)
			{
				return;
			}

			if (!this.headSelectorDialog)
			{
				let selectedItems = [];
				let preselectedItems = [];

				if (this.selectedItems === null)
				{
					selectedItems = [headOfDepartment];
				}
				else
				{
					preselectedItems = this.selectedItems;
				}

				this.headSelectorDialog = new Dialog({
					targetNode: this.$refs.dialog,
					context: 'CRM_COPILOT_CALL_ASSESSMENT_HEAD_SELECTOR',
					multiple: false,
					dropdownMode: true,
					showAvatars: true,
					enableSearch: true,
					width: 450,
					zIndex: 2500,
					entities: [
						{
							id: 'user',
							options: {
								inviteEmployeeLink: false,
							},
						},
					],
					items: [headOfDepartment],
					searchOptions: {
						allowCreateItem: false,
					},
					selectedItems,
					preselectedItems,
					events: {
						'Item:onSelect': (event: BaseEvent) => {
							const { item: { id, entityId, title } } = event.getData();
							this.currentHeadTitle = title.text;
							this.emitSelectItem([entityId, id]);
						},
					},
				});
			}

			this.headSelectorDialog.show();
		},
		emitSelectItem(selectedItem: Array): void
		{
			this.$emit('onSelectItem', selectedItem);
		},
	},

	computed: {
		title(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HEAD');
		},
		titleChange(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HEAD_CHANGE');
		},
		classList(): Object
		{
			return {
				'crm-copilot__call-assessment-head-title': true,
				'--readonly': this.isReadOnly,
			};
		},
		hint(): ?Object
		{
			if (!this.isReadOnly)
			{
				return null;
			}

			return {
				text: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_HEAD_INFO'),
				popupOptions: {
					angle: {
						offset: 30,
						position: 'top',
					},
					offsetTop: 2,
				},
			};
		},
	},

	template: `
		<div>
			<span class="crm-copilot__call-assessment-head-label">{{ title }}</span>
			<span 
				ref="dialog"
				@click="showDialog"
				:class="classList"
			>{{ currentHeadTitle }}</span>
			<button
				v-if="!isReadOnly"
				@click="showDialog"
				class="ui-btn ui-btn-xs ui-btn-round ui-btn-light-border ui-btn-no-caps"
			>{{ titleChange }}</button>
			<i 
				v-if="isReadOnly"
				class="crm-copilot__call-assessment-head-info"
				v-hint="hint"
			>
			</i>
		</div>
	`,
};
