import { Main, Icon } from 'ui.icon-set.api.core';
import { PopupMenu } from 'main.popup';
import 'ui.icon-set.actions';

export const MoreOptions = {
	name: 'MoreOption',

	methods: {
		showMenu() {
			const popupMenu = PopupMenu.create({
				id: 'humanresources-mapper-menu-line-option',
				autoHide: true,
				bindElement: this.$refs.container,
				items: [
					{
						text: 'first',
						onclick: (event, item) => {
							item.menuWindow.close();
						},
					},
					{
						text: 'second',
						onclick: (event, item) => {
							item.menuWindow.close();
						},
					},
				],
			});

			popupMenu.show();
		},
	},

	mounted()
	{
		new Icon({
			icon: Main.MORE_INFORMATION,
			size: 24,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-30'),
		}).renderTo(this.$refs.container);
	},

	template: `
		<div class="hr-hcmlink-more-options__container">
			<span 
				ref="container"
				@click="showMenu"
			></span>
		</div>
	`,
};
