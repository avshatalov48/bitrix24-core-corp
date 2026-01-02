import { MenuManager, Menu } from 'main.popup';
import 'ui.forms';
import './style.css';

export type DropdownItem = {
	value: string,
	text: string,
	default?: boolean
};

/**
 * Dropdown pseudo-element with reactivity
 */
export const HrDropdown = {
	name: 'HrDropdown',

	props: {
		items: {
			type: Array,
			required: true,
		},
		id: {
			type: String,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['itemChange'],

	data(): Object
	{
		return {
			selectedElement: '',
			menuOpened: false,
		};
	},

	created(): void
	{
		this.menuInstance = null;
		if (this.defaultItem)
		{
			this.selectedElement = this.defaultItem.value;
		}
	},

	beforeUnmount(): void
	{
		this.menuInstance?.destroy();
	},

	computed:
	{
		formattedItems(): {[value: string]: DropdownItem}
		{
			const map = {};
			this.items.forEach((item) => {
				map[item.value] = item;
			});

			return map;
		},
		defaultItem(): DropdownItem
		{
			return this.items.find((item) => {
				return item.default === true;
			});
		},
	},

	methods:
	{
		toggleMenu(): void
		{
			if (!this.menuInstance)
			{
				this.menuInstance = this.getMenuInstance();
			}

			if (this.menuOpened)
			{
				this.menuInstance.close();

				return;
			}

			this.menuInstance.show();
			const width = this.$refs.container.clientWidth;
			this.menuInstance.getPopupWindow().setWidth(width);
			this.menuOpened = true;
		},
		getMenuInstance(): Menu
		{
			return MenuManager.create({
				id: this.id,
				bindOptions: { forceBindPosition: true, position: 'bottom' },
				targetContainer: document.body,
				bindElement: this.$refs.container,
				items: this.getMenuItems(),
				events: {
					onClose: () => {
						this.menuOpened = false;
					},
				},
			});
		},
		getMenuItems(): Array<{ text: string, onclick: Function }>
		{
			return Object.values(this.formattedItems).map((item) => {
				return {
					text: item.text,
					onclick: () => {
						this.selectedElement = item.value;
						this.$emit('itemChange', item.value);
						this.menuInstance.close();
					},
				};
			});
		},
	},

	template: `
		<div class="hr-im-dropdown__container">
			<div @click="!disabled && toggleMenu" class="ui-ctl ui-ctl-xl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown" ref="container">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<div class="ui-ctl-element" :class="{ 'hr-im-dropdown__text_disabled': disabled }">{{ formattedItems[selectedElement].text }}</div>
			</div>
		</div>
	`,
};
