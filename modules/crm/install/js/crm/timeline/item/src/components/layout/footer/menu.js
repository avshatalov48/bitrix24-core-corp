import {MenuManager} from "main.popup";
import {Menu as LayoutMenu} from "../../layout/menu";
const MenuId = 'timeline-more-button-menu';

export const Menu = {
	props: {
		buttons: Array, // buttons that didn't fit into footer
		items: Object, // real menu items
	},
	inject: [
		'isReadOnly',
	],
	computed: {
		itemsArray(): Array
		{
			if (!this.items)
			{
				return [];
			}

			return Object.values(this.items)
				.filter((item) => (item.state !== 'hidden' && item.scope !== 'mobile' && (!this.isReadOnly || !item.hideIfReadonly)))
				.sort((a, b) => (a.sort - b.sort))
			;
		},
		menuItems(): Array
		{
			let result = this.buttons;
			if (this.buttons.length && this.itemsArray.length)
			{
				result.push({delimiter: true});
			}
			result = [...result, ...this.itemsArray];

			return result;
		}
	},
	beforeUnmount(): void
	{
		const menu = MenuManager.getMenuById(MenuId);
		if (menu)
		{
			menu.destroy();
		}
	},
	methods: {
		showMenu(): void
		{
			LayoutMenu.showMenu(
				this,
				this.menuItems,
				{
					id: MenuId,
					className: 'crm-timeline__card_more-menu',
					width: 230,
					angle: false,
					cacheable: false,
					bindElement: this.$el,
				}
			);
		},
	},
	// language=Vue
	template: `<div class="crm-timeline__card-action_menu-item --dotted" @click="showMenu"><i></i></div>`
};
