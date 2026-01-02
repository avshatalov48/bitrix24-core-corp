import { BitrixVueComponentProps } from 'ui.vue3';
import { Menu, type MenuOptions } from 'main.popup';

import { ServiceLocator } from '../../../lib/service-locator';
import { ShowMore } from './show-more';

export const ShowMoreMenu: BitrixVueComponentProps = {
	name: 'ShowMoreMenu',

	components: {
		ShowMore,
	},

	props: {
		items: {
			/** @type MenuOptions[] */
			type: Array,
			required: true,
		},
	},

	data(): Object
	{
		return {
			/** @type Menu */
			menu: null,
		};
	},

	methods: {
		getMenu(): Menu
		{
			if (this.menu === null)
			{
				this.menu = new Menu({
					items: this.items,
					bindElement: this.$refs.showMoreContainer.$el,
					angle: {
						offset: this.$refs.showMoreContainer.$el.offsetWidth / 2,
					},
				});

				ServiceLocator
					.getInstance()
					.getEventService()
					.registerChildPopup(this.$Bitrix.eventEmitter, this.menu.getPopupWindow());
			}

			return this.menu;
		},

		toggleMenu(): void
		{
			this.getMenu().toggle();
		},
	},

	template: `
		<ShowMore ref="showMoreContainer" :count="items.length" @click="toggleMenu"/>
	`,
};
