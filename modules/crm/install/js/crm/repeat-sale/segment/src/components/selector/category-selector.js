import { DialogWrapperComponent } from '../common/dialog-wrapper-component';
import { TagSelectorWrapperComponent } from '../common/tag-selector-wrapper-component';

export const CategorySelector = {
	components: {
		TagSelectorWrapperComponent,
		DialogWrapperComponent,
	},

	props: {
		categories: {
			type: Object,
			required: true,
		},
		currentCategoryId: {
			type: Number,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	data(): Object
	{
		const items = [];
		this.categories.forEach((category, index) => {
			items.push({
				id: category.id,
				title: category.name,
				entityId: 'category',
				tabs: 'categories',
				selected: category.id === this.currentCategoryId,
			});
		});

		return {
			items,
		};
	},

	created()
	{
		this.tabs = [
			{ id: 'categories', title: '' },
		];
	},

	watch: {
		currentCategoryId(categoryId: string): void
		{
			this.items.forEach((item) => {
				// eslint-disable-next-line no-param-reassign
				item.selected = (item.id === categoryId);
			});
		},
	},

	// language=Vue
	template: `
		<DialogWrapperComponent
			:items="items"
			:tabs="tabs"
			:read-only="readOnly"
		/>
	`,
};
