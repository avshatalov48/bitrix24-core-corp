import {MenuManager} from "main.popup";
import {Menu as LayoutMenu} from "../../layout/menu";
const MenuId = 'timeline-more-button-menu';
import {AdditionalButton, AdditionalButtonColor, AdditionalButtonIcon} from './add-button';

export const Menu = {
	components: {
		AdditionalButton,
	},
	props: {
		buttons: Array, // buttons that didn't fit into footer
		items: Object, // real menu items
	},
	inject: [
		'isReadOnly',
	],
	computed: {
		isMenuFilled(): boolean
		{
			const menuItems = this.menuItems;

			return menuItems.length > 0;
		},

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
		},

		buttonProps()
		{
			return {
				color: AdditionalButtonColor.DEFAULT,
				icon: AdditionalButtonIcon.DOTS,
			}
		},
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
	template: `
		<div v-if="isMenuFilled" class="crm-timeline__card-action_menu-item" @click="showMenu">
			<AdditionalButton iconName="dots" color="default"></AdditionalButton>
		</div>
	`
};
