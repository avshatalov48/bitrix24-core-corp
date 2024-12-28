import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { Main } from 'ui.icon-set.api.core';
import { getColorCode } from 'humanresources.company-structure.utils';
import { sendData as analyticsSendData } from 'ui.analytics';

import 'ui.icon-set.main';

const MenuOption = Object.freeze({
	accessRights: 'access-rights',
});

export const BurgerMenuButton = {
	name: 'BurgerMenuButton',

	data(): Object
	{
		return {
			actionMenu: {
				visible: false,
			},
		};
	},

	components: {
		RouteActionMenu,
	},

	mounted(): void
	{
		this.dropdownItems = [
			{
				id: MenuOption.accessRights,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION_DESCR'),
				bIcon: {
					name: Main.SHIELD,
					size: 20,
					color: getColorCode('paletteBlue50'),
				},
			},
		];
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},

		actionMenuItemClickHandler(actionId: string): void
		{
			if (actionId === MenuOption.accessRights)
			{
				analyticsSendData({ tool: 'structure', category: 'structure', event: 'open_roles' });

				BX.SidePanel.Instance.open('/hr/config/permission/', { usePadding: true });
			}
		},
	},

	template: `
		<span
			ref="burgerMenuButton"
			@click="actionMenu.visible = true"
		>
			<svg
				viewBox="0 0 24 24"
				fill="none"
				class="humanresources-title-panel__icon"
				:class="{'--selected': actionMenu.visible }"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M18.7067 15.5577C18.8172 15.5577 18.9067 15.6473 18.9067 15.7577L18.9067 17.2424C18.9067 17.3528 18.8172 17.4424 18.7067 17.4424H5.29375C5.1833 17.4424 5.09375 17.3528 5.09375 17.2424L5.09379 15.7577C5.09379 15.6473 5.18333 15.5577 5.29379 15.5577H18.7067ZM18.7067 11.5577C18.8172 11.5577 18.9067 11.6473 18.9067 11.7577L18.9067 13.2424C18.9067 13.3528 18.8172 13.4424 18.7067 13.4424H5.29375C5.1833 13.4424 5.09375 13.3528 5.09375 13.2424L5.09379 11.7577C5.09379 11.6473 5.18333 11.5577 5.29379 11.5577H18.7067ZM18.7067 7.55774C18.8172 7.55774 18.9067 7.64729 18.9067 7.75774L18.9067 9.24238C18.9067 9.35284 18.8172 9.44238 18.7067 9.44238H5.29375C5.1833 9.44238 5.09375 9.35283 5.09375 9.24237L5.09379 7.75773C5.09379 7.64728 5.18333 7.55774 5.29379 7.55774H18.7067Z" fill="#525C69"/>
			</svg>
		 </span>
		<RouteActionMenu
			v-if="actionMenu.visible"
			id="title-panel-burger-menu"
			:items="dropdownItems"
			:bindElement="this.$refs.burgerMenuButton"
			@action="actionMenuItemClickHandler($event)"
			@close="this.actionMenu.visible = false"
		/>
	`,

};
