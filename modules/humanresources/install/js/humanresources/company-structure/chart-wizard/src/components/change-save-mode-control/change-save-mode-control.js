import { getColorCode } from 'humanresources.company-structure.utils';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { CRM, Main } from 'ui.icon-set.api.core';
import './style.css';
import 'ui.icon-set.crm';

const MenuOption = Object.freeze({
	moveUsers: 'moveUsers',
	addUsers: 'addUsers',
});

export const ChangeSaveModeControl = {
	name: 'changeSaveModeControl',
	emits: ['saveModeChanged'],

	components: {
		RouteActionMenu,
	},

	created(): void
	{
		this.menuItems = this.getMenuItems();
	},

	data(): { menuVisible: boolean; actionId: MenuOption.moveUsers | MenuOption.addUsers }
	{
		return {
			menuVisible: false,
			actionId: MenuOption.moveUsers,
		};
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.actionId = actionId;
			this.$emit('saveModeChanged', actionId);
		},
		getMenuItems(): Array
		{
			return [
				{
					id: MenuOption.moveUsers,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_DESCRIPTION'),
					bIcon: {
						name: Main.PERSON_ARROW_LEFT_1,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
				},
				{
					id: MenuOption.addUsers,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_DESCRIPTION'),
					bIcon: {
						name: CRM.PERSON_PLUS_2,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
				},
			];
		},
	},

	computed: {
		getControlButtonText(): string
		{
			const phraseCode = this.actionId === MenuOption.moveUsers
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_TITLE'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_TITLE';

			return this.loc(phraseCode);
		},
	},

	template: `
		<div
			class="chart-wizard__change-save-mode-control-container"
		>
			<span>{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_CONTROL_TEXT') }}</span>
			<a
				class="chart-wizard__change-save-mode-control-button"
				:class="{ '--focused': menuVisible }"
				ref='changeSaveModeButton'
				@click="menuVisible = true"
			>
				{{ getControlButtonText }}
			</a>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'hr-wizard-save-mode-menu'"
			:items="menuItems"
			:width="302"
			:bindElement="$refs.changeSaveModeButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
