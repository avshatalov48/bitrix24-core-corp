import { Menu, type MenuItemOptions } from 'main.popup';
import { BIcon, Set } from 'ui.icon-set.api.vue';

import '../css/copilot-chat-header-menu.css';

export const CopilotChatHeaderMenu = {
	components: {
		BIcon,
	},
	props: {
		menuItems: {
			type: Array,
			required: true,
			default: () => ([]),
		},
	},
	computed: {
		items(): MenuItemOptions[] {
			return this.menuItems;
		},
		menuIconProps(): { name: string, size: number } {
			return {
				name: Set.MORE,
				size: 24,
			};
		},
	},
	beforeMount() {
		this.menu = new Menu({
			items: this.items,
		});
	},
	mounted() {
		this.menu.getPopupWindow().setBindElement(this.$refs.menuButton);
	},
	beforeUnmount() {
		this.menu.destroy();
	},
	methods: {
		toggleMenu(): void {
			if (this.isMenuOpen())
			{
				this.hideMenu();
			}
			else
			{
				this.showMenu();
			}
		},
		showMenu(): void {
			this.menu.show();
		},
		hideMenu(): void {
			this.menu.close();
		},
		isMenuOpen(): boolean {
			return this.menu.getPopupWindow().isShown();
		},
	},
	template: `
		<button
			ref="menuButton"
			@click="toggleMenu"
			class="ai__copilot-chat-header-menu"
		>
			<span class="ai__copilot-chat-header-menu_icon">
				<b-icon
					v-bind="menuIconProps"
				></b-icon>
			</span>
		</button>
	`,
};
