import { Dom } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';

type ItemId = [
	string,
	string | number,
];

export const TagSelectorWrapperComponent = {
	emits: [
		'change',
		'onSelectItem',
		'onDeselectItem',
	],

	props: {
		items: {
			type: Array,
			default: [],
		},
		preselectedItems: {
			type: Array,
			default: [],
		},
		tabs: {
			type: Array,
			default: [],
		},
		entities: {
			type: Array,
			default: [],
		},
		showAvatars: {
			type: Boolean,
			default: false,
		},
		multiple: {
			type: Boolean,
			default: false,
		},
		context: {
			type: String,
			default: '',
		},
		events: {
			type: Object,
			default: {},
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	mounted() {
		this.initDialog();
	},

	watch: {
		items(items: Array): void
		{
			this.tagSelector.getDialog().destroy();
			Dom.clean(this.$refs.dialog);

			this.initDialog();
		},
	},

	methods: {
		initDialog(): void
		{
			const targetNode = this.$refs.dialog;
			const parentPopupContainer = targetNode.closest('body');

			this.tagSelector = new TagSelector({
				multiple: this.multiple,
				readonly: this.readOnly,
				hideOnSelect: false,
				dialogOptions: {
					targetNode,
					context: this.context,
					multiple: this.multiple,
					hideOnSelect: false,
					dropdownMode: true,
					showAvatars: this.showAvatars,
					enableSearch: true,
					width: 450,
					zIndex: 2500,
					items: this.items,
					preselectedItems: this.preselectedItems,
					entities: this.entities,
					tabs: this.tabs,
					searchOptions: {
						allowCreateItem: false,
					},
					events: {
						'Item:onSelect': (event: BaseEvent) => {
							const { item: selectedItem } = event.getData();
							this.emitSelectItem(selectedItem);
						},
						'Item:onDeselect': (event: BaseEvent) => {
							const { item: deselectedItem } = event.getData();

							this.emitDeselectItem(deselectedItem);
						},
					},
				},
			});

			this.tagSelector.renderTo(this.$refs.dialog);
		},
		emitSelectItem(selectedItem: Array): void
		{
			this.$emit('onSelectItem', selectedItem);
		},
		emitDeselectItem(deselectedItem: Array): void
		{
			this.$emit('onDeselectItem', deselectedItem);
		},
		selectItem(itemId: ItemId): void
		{
			const item = this.tagSelector.getDialog().getItem(itemId);
			item?.select();
		},
	},

	// language=Vue
	template: `
		<div ref="dialog"></div>
	`,
};
