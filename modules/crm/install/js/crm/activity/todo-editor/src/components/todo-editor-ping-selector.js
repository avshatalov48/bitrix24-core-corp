import { ItemSelector } from 'crm.field.item-selector';

export const TodoEditorPingSelector = {
	props: {
		valuesList: {
			type: Array,
			required: true,
			default: [],
		},
		selectedValues: {
			type: Array,
			default: [],
		},
	},

	methods: {
		getValue(): Array
		{
			if (this.itemSelector)
			{
				return this.itemSelector.getValue();
			}

			return [];
		},

		setValue(values: Array): void
		{
			if (this.itemSelector)
			{
				this.itemSelector.setValue(values);
			}
		},
	},

	mounted(): void
	{
		this.itemSelector = new ItemSelector({
			target: this.$el,
			valuesList: this.valuesList,
			selectedValues: this.selectedValues,
		});
	},

	template: `<div style="width: 100%;"></div>`,
};
