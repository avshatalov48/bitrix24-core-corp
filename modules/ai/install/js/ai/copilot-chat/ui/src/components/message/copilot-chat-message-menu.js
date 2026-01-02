import { bind, Dom } from 'main.core';
import { Menu, type MenuItemOptions } from 'main.popup';
import { BIcon, Set } from 'ui.icon-set.api.vue';

import { CopilotChatMessageButton } from '../copilot-chat-message-button';
import '../../css/copilot-chat-message-menu.css';

export type MenuItemOnClickCustomData = {
	message: MenuItemMessageData;
};

export type MenuItemMessageData = {
	id: number;
	content: string;
	dateCreate: string;
};

export const CopilotChatMessageMenu = {
	components: {
		BIcon,
		CopilotChatMessageButton,
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
		icon: {
			type: String,
			required: false,
			default: Set.MORE,
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
								dateCreate: this.message.dateCreate,
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
				name: this.icon,
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
					offset: Dom.getPosition(this.$refs.menuButton.$el).width / 2 + 23,
				},
				events: {
					onPopupShow: () => {
						this.isMenuOpen = true;
					},
					onPopupClose: () => {
						this.isMenuOpen = false;
					},
				},
				bindElement: this.$refs.menuButton.$el,
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
		<CopilotChatMessageButton
			ref="menuButton"
			@click="toggleMenu"
			:icon="menuIconProps.name"
			:class="menuButtonClassname"
		/>
	`,
};
