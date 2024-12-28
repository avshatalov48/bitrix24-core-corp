import { Event } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import { HelpLink } from './help-link';

export const TextWithDialog = {
	components: {
		HelpLink,
	},

	props: {
		id: {
			type: String,
			required: true,
		},
		content: {
			type: String,
			required: true,
		},
		dialogTargetId: {
			type: String,
			required: false,
			default: null,
		},
		articleCode: {
			type: String,
			required: true,
		},
		value: {
			type: Number,
		},
		items: {
			type: Array,
		},
		tabs: {
			type: Array,
		},
		selectedItems: {
			type: Array,
		},
	},

	data(): Object
	{
		return {
			status: this.isChecked ?? false,
			currentValue: this.value ?? null,
		};
	},

	mounted(): void
	{
		this.initDialog();
	},

	updated(): void
	{
		this.initDialog();
	},

	watch: {
		currentValue(value): void
		{
			this.emitSelectItem(value);
		},
	},

	methods: {
		initDialog(): void
		{
			if (this.dialogTargetId === null)
			{
				return;
			}

			const targetNode = document.getElementById(this.dialogTargetId);

			this.clientSelectorDialog = new Dialog({
				targetNode,
				context: `CRM_COPILOT_CALL_ASSESSMENT_DIALOG_${this.id}`,
				multiple: false,
				dropdownMode: true,
				showAvatars: false,
				enableSearch: false,
				width: 450,
				zIndex: 2500,
				items: this.items ?? [],
				tabs: this.tabs ?? [],
				selectedItems: this.selectedItems ?? [],
				events: {
					'Item:onSelect': (event: BaseEvent) => {
						const { item: { id } } = event.getData();
						this.currentValue = id;
					},
				},
			});

			Event.bind(targetNode, 'click', () => {
				this.clientSelectorDialog.show();
			});
		},
		emitSelectItem(itemId: number): void
		{
			this.$emit('onSelectItem', itemId);
		},
	},

	template: `
		<div>
			<span
				class="crm-copilot__call-assessment_call_count"
				v-html="content"
			></span>
			<HelpLink :articleCode="articleCode" />
		</div>
	`,
};
