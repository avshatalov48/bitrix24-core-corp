const MenuId = 'restapp-dropdown-menu';

import { Type } from 'main.core';
import { MenuManager } from 'main.popup';
import EventType from '../../enums/event-type';
import TextSize from '../../enums/text-size';
import { ITEM_ACTION_EVENT } from '../../layout';

export default {
	inheritAttrs: false,
	props: {
		values: Object,
		id: String,
		selectedValue: {
			required: false,
			default: '',
		},
		size: {
			type: String,
			required: false,
			default: 'md',
		},
	},
	data(): Object
	{
		return {
			currentSelectedValue: this.selectedValue,
		};
	},
	beforeUnmount(): void
	{
		const menu = MenuManager.getMenuById(MenuId);
		if (menu)
		{
			menu.destroy();
		}
	},
	computed: {
		className(): Array
		{
			return [
				'crm-timeline-block-dropdownmenu',
				this.sizeClassname,
			];
		},
		sizeClassname(): String
		{
			const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseWeightProp] ?? TextSize.SM;

			return `--size-${size}`;
		},
		selectedValueCode(): String
		{
			let selectedValue = this.currentSelectedValue;
			if (!Object.hasOwn(this.values, selectedValue))
			{
				const allValues = Object.keys(this.values);
				selectedValue = allValues.length > 0 ? allValues[0] : '';
			}

			return selectedValue;
		},
		selectedValueTitle(): String
		{
			return String(this.values[this.selectedValueCode] ?? '');
		},
		isValid(): Boolean
		{
			return Type.isPlainObject(this.values) && Object.keys(this.values).length > 0;
		},
	},
	watch: {
		selectedValue(newSelectedValue)
		{
			this.currentSelectedValue = newSelectedValue;
		},
	},
	methods: {
		onMenuItemClick(valueId): void
		{
			this.currentSelectedValue = valueId;
			MenuManager.getCurrentMenu()?.close();
			this.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
				event: EventType.VALUE_CHANGED_EVENT,
				value: {
					id: this.id,
					value: valueId,
				},
			});
		},
		showMenu(): void
		{
			const menuItems = [];
			Object.keys(this.values).forEach((valueId) => {
				menuItems.push({
					text: String(this.values[valueId]),
					value: valueId,
					onclick: () => {
						this.onMenuItemClick(valueId);
					},
				});
			});
			MenuManager.show({
				id: MenuId,
				cacheable: false,
				bindElement: this.$el,
				items: menuItems,
			});
		},
	},
	template: `
		<span v-if="isValid" :class="className" @click="showMenu"><span class="crm-timeline-block-dropdownmenu-content">{{selectedValueTitle}}</span><span class="crm-timeline-block-dropdownmenu-arrow"></span></span>`,
};
