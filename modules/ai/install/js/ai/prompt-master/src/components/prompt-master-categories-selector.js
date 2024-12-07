import { ajax } from 'main.core';
import { BaseEvent } from 'main.core.events';
import type { ItemOptions } from 'ui.entity-selector';
import { TagSelector, Dialog } from 'ui.entity-selector';

export const PromptMasterCategoriesSelector = {
	props: {
		selectedCategoryIds: {
			type: Array,
			required: false,
			default(): [] {
				return [];
			},
		},
	},
	data(): { allCategories: [], tagSelector: TagSelector } {
		return {
			allCategories: [],
			tagSelector: null,
		};
	},
	computed: {
		dialogItemOptions(): ItemOptions[] {
			return this.allCategories
				.map((category) => {
					return this.getTagSelectorItemFromCategory(category);
				});
		},
	},
	methods: {
		async getAllCategories(): Promise<any> {
			const result = await ajax.runAction('ai.prompt.getCategoriesListWithTranslations');

			return result.data.list;
		},
		getTagSelectorItemFromCategory(category): Object {
			return {
				id: category.code,
				title: category.name,
				tabs: ['prompt-category'],
				entityId: 'prompt-category',
			};
		},
		selectCategory(item) {
			this.$emit('select', item.id);
		},
		deselectCategory(item) {
			this.$emit('deselect', item.id);
		},
		initTagSelector(): TagSelector {
			const tagSelector = new TagSelector({
				items: [],
				tagMaxWidth: 300,
				dialogOptions: {
					id: 'ai-prompt-master-categories-selector-dialog',
					recentItemsLimit: 0,
					compactView: true,
					dropdownMode: true,
					width: 400,
					items: [],
					tabs: [
						{
							id: 'prompt-category',
						},
					],
					events: {
						'Item:onSelect': (event: BaseEvent) => {
							this.selectCategory(event.getData().item);
						},
						'Item:onDeselect': (event: BaseEvent) => {
							this.deselectCategory(event.getData().item);
						},
					},
				},
			});

			tagSelector.renderTo(this.$el);

			return tagSelector;
		},
	},
	async mounted() {
		const tagSelector = this.initTagSelector();

		this.allCategories = await this.getAllCategories();

		this.dialogItemOptions.forEach((item) => {
			const selected = this.selectedCategoryIds.length > 0 ? this.selectedCategoryIds.includes(item.id) : true;

			tagSelector.getDialog()?.addItem({
				...item,
				selected,
			});
		});

		if (this.selectedCategoryIds.length === 0)
		{
			this.allCategories.forEach((item) => {
				this.$emit('select', item.code);
			});
		}
	},
	unmounted() {
		Dialog.getById('ai-prompt-master-categories-selector-dialog')?.destroy();
	},
	template: `
		<div class="ai__prompt-master_prompt-categories-selector"></div>
	`,
};
