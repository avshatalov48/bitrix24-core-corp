import { BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import type { TagSelectorOptions } from 'ui.entity-selector';

// @vue/component
export const SettingsSelector = {
	name: 'SettingsSelector',
	props: {
		values: {
			type: Array,
			required: true,
		},
		entitiesId: {
			type: String,
			required: true,
		},
		multiple: {
			type: Boolean,
			default: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		tab: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'change',
	],
	watch: {
		disabled: {
			handler(disabled: boolean): void
			{
				this.selector?.setLocked(disabled);
			},
			immediate: true,
		},
	},
	created(): void
	{
		this.selector = this.createSelector();
	},
	mounted(): void
	{
		this.mountSelector();
	},
	beforeUnmount(): void
	{
		this.destroySelector();
	},
	methods: {
		createSelector(): TagSelector
		{
			const tagSelectionOptions: TagSelectorOptions = {
				multiple: this.multiple,
				addButtonCaption: this.loc('BRCW_SETTINGS_CARD_INTEGRATION_SELECTOR_BTN'),
				showCreateButton: false,
				maxHeight: 40,
				dialogOptions: {
					context: 'bookingResourceIntersection',
					width: 290,
					height: 340,
					dropdownMode: true,
					compactView: true,
					enableSearch: true,
					cacheable: true,
					showAvatars: false,
					popupOptions: {
						targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
					},
					entities: [
						{
							id: this.entitiesId,
							dynamicLoad: true,
							dynamicSearch: true,
						},
					],
					preselectedItems: this.values.map((id) => ([this.entitiesId, id])),
					events: {
						'Item:onSelect': (event: BaseEvent) => {
							this.select(event.getData().item.id);
						},
						'Item:onDeselect': (event: BaseEvent) => {
							this.deselect(event.getData().item.id);
						},
					},
				},
			};

			if (this.tab)
			{
				tagSelectionOptions.dialogOptions.tabs = [{ id: this.entitiesId }];
			}

			return new TagSelector(tagSelectionOptions);
		},
		select(itemId: number): void
		{
			const itemsSet = new Set(this.values);
			itemsSet.add(itemId);

			this.$emit('change', [...itemsSet]);
		},
		deselect(itemId: number): void
		{
			this.$emit('change', this.values.filter((id) => id !== itemId));
		},
		mountSelector(): void
		{
			this.selector.renderTo(this.$refs.settingsSelector);

			if (this.disabled)
			{
				this.selector.setLocked(this.disabled);
			}
		},
		destroySelector(): void
		{
			this.selector.getDialog().destroy();
			this.selector = null;
			this.$refs.settingsSelector.innerHTML = '';
		},
	},
	template: `
		<div
			ref="settingsSelector"
			class="resource-creation-wizard__integration-block-settings-selector"
		></div>
	`,
};
