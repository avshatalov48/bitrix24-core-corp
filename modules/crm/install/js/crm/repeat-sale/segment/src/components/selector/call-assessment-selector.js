import { Event } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';

export const CallAssessmentSelector = {
	props: {
		callAssessments: {
			type: Object,
			required: true,
		},
		currentCallAssessmentId: {
			type: Number,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	created()
	{
		this.dialog = null;
		this.tabs = [
			{ id: 'call_assessments', title: '' },
		];

		this.items = [];
		this.callAssessments.forEach((callAssessment, index) => {
			this.items.push({
				id: callAssessment.id,
				title: callAssessment.name,
				entityId: 'call_assessment',
				tabs: 'call_assessments',
				selected: callAssessment.id === this.currentCallAssessmentId,
			});
		});
	},

	methods: {
		toggleDialog(): void
		{
			const dialog = this.getDialog();

			if (dialog.isOpen())
			{
				dialog.hide();
			}
			else
			{
				dialog.show();
			}
		},
		getDialog(): Dialog
		{
			if (this.dialog === null)
			{
				const targetNode = this.$refs.dialog;
				const parentPopupContainer = targetNode.closest('body');

				this.dialog = new Dialog({
					targetNode,
					context: 'CRM_REPEAT_SALE_CALL_ASSESSMENT',
					multiple: false,
					dropdownMode: true,
					showAvatars: false,
					enableSearch: true,
					width: 450,
					zIndex: 2500,
					tabs: this.tabs,
					items: this.items,
					searchOptions: {
						allowCreateItem: false,
					},
					events: {
						'Item:onSelect': (event: BaseEvent) => {
							const { item: { id } } = event.getData();
							this.emitSelectItem(id);
						},
						onShow: (event: BaseEvent) => {
							Event.bindOnce(
								parentPopupContainer,
								'click',
								this.onPopupContainerClick.bind(this),
							);
						},
					},
				});
			}

			return this.dialog;
		},
		emitSelectItem(itemId: number): void
		{
			this.$emit('onSelectItem', itemId);
		},
		onPopupContainerClick(): void
		{
			this.getDialog().hide();
		},
	},

	// language=Vue
	template: `
		<div
			ref="dialog"
			@click="toggleDialog"
			class="crm-repeat-sale__segment_call-assessment"
		>
			{{ this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_SELECTOR') }}
		</div>
	`,
};
