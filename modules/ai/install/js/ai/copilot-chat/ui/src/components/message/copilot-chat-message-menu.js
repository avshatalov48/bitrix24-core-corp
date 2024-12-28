import { bind, Dom } from 'main.core';
import { Menu, type MenuItemOptions } from 'main.popup';
import { BIcon, Set } from 'ui.icon-set.api.vue';

import '../../css/copilot-chat-message-menu.css';

export type MenuItemOnClickCustomData = {
	message: MenuItemMessageData;
};

export type MenuItemMessageData = {
	id: number;
	content: string;
	dateCreated: string;
};

export const CopilotChatMessageMenu = {
	components: {
		BIcon,
	},
	props: {
		menuItems: {
			type: Array,
			required: true,
			default: () => ([]),
		},
		message: {
			type: Object,
			required: true,
			default: () => ({}),
		},
	},
	data(): {isMenuOpen: boolean} {
		return {
			isMenuOpen: false,
		};
	},
	computed: {
		items(): MenuItemOptions[] {
			return this.menuItems.map((item: MenuItemOptions) => {
				return {
					...item,
					onclick: (event: PointerEvent, menuItem: MenuItem) => {
						const myCustomData: MenuItemOnClickCustomData = {
							message: {
								id: this.message.id,
								content: this.message.content,
								dateCreated: this.message.dateCreated,
							},
						};

						this.hideMenu();

						return item.onclick(event, menuItem, myCustomData);
					},
				};
			});
		},
		menuIconProps(): { name: string, size: number} {
			return {
				name: Set.MORE,
				size: 22,
			};
		},
		menuButtonClassname(): Object {
			return {
				'ai__copilot-chat-message-menu': true,
				'--open': this.isMenuOpen,
			};
		},
	},
	methods: {
		toggleMenu(): void {
			if (this.isMenuOpen)
			{
				this.hideMenu();
			}
			else
			{
				this.showMenu();
			}
		},
		showMenu(): void {
			if (!this.menu)
			{
				this.initMenu();
			}

			this.menu?.show();
		},
		hideMenu(): void {
			this.menu?.close();
		},
		initMenu(): Menu {
			this.menu = new Menu({
				items: this.items,
				angle: {
					offset: Dom.getPosition(this.$refs.menuButton).width / 2 + 23,
				},
				events: {
					onPopupShow: () => {
						this.isMenuOpen = true;
					},
					onPopupClose: () => {
						this.isMenuOpen = false;
					},
				},
				bindElement: this.$refs.menuButton,
			});

			bind(document.body.querySelector('.ai__copilot-chat_main'), 'scroll', () => {
				this.hideMenu();
			});

			return this.menu;
		},
	},
	beforeUnmount() {
		this.menu?.destroy();
	},
	template: `
		<button
			ref="menuButton"
			@click="toggleMenu"
			:class="menuButtonClassname"
		>
			<BIcon v-bind="menuIconProps"></BIcon>
		</button>
	`,
};
