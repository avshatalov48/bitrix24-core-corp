import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { Main, CRM } from 'ui.icon-set.api.core';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { getColorCode } from 'humanresources.company-structure.utils';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';

import '../../style.css';
import 'ui.icon-set.main';
import 'ui.icon-set.crm';

const MenuOption = Object.freeze({
	editDepartment: 'editDepartment',
	addDepartment: 'addDepartment',
	editEmployee: 'editEmployee',
	moveEmployee: 'moveEmployee',
	userInvite: 'userInvite',
	addEmployee: 'addEmployee',
	removeDepartment: 'removeDepartment',
});

export const DetailPanelEditButton = {
	name: 'detailPanelEditButton',
	emits: ['editDepartment', 'addDepartment', 'editEmployee', 'addEmployee', 'removeDepartment', 'moveEmployee', 'userInvite'],

	components: {
		RouteActionMenu,
		BIcon,
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();
	},

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.$emit(actionId, { role: this.role, bindElement: this.$refs.detailPanelEditButton });
		},
	},

	computed: {
		...mapState(useChartStore, ['focusedNode']),
		set(): Set
		{
			return Set;
		},
		menuItems(): Array
		{
			if (!this.permissionChecker)
			{
				return [];
			}

			return [
				{
					id: MenuOption.editDepartment,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_SUBTITLE'),
					bIcon: {
						name: Main.EDIT_PENCIL,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.departmentEdit },
				},
				{
					id: MenuOption.addDepartment,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE'),
					bIcon: {
						name: Main.CUBE_PLUS,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.departmentCreate },
				},
				{
					id: MenuOption.editEmployee,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE'),
					bIcon: {
						name: Main.EDIT_MENU,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.employeeAddToDepartment },
				},
				{
					id: MenuOption.moveEmployee,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
					bIcon: {
						name: Main.PERSON_ARROW_LEFT_1,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.employeeAddToDepartment },
				},
				{
					id: MenuOption.userInvite,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_SUBTITLE'),
					bIcon: {
						name: Main.PERSON_LETTER,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.inviteToDepartment },
				},
				{
					id: MenuOption.addEmployee,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
					bIcon: {
						name: CRM.PERSON_PLUS_2,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.employeeAddToDepartment },
				},
				{
					id: MenuOption.removeDepartment,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE'),
					bIcon: {
						name: Main.TRASH_BIN,
						size: 20,
						color: getColorCode('paletteRed40'),
					},
					permission: { action: PermissionActions.departmentDelete },
				},
			].filter((item) => {
				if (!item.permission)
				{
					return false;
				}

				return this.permissionChecker.hasPermission(item.permission.action, this.focusedNode);
			});
		},
	},

	template: `
		<div
			v-if="menuItems.length"
			class="humanresources-detail-panel__edit-button"
			:class="{ '--focused': menuVisible }"
			:ref="'detailPanelEditButton'"
			data-id="hr-department-detail-panel__edit-menu-button"
			@click.stop="menuVisible = true"
		>
			<BIcon
				class="humanresources-detail-panel__edit-button-icon"
				:name="set.MORE"
				:size="20"
			/>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			id="department-detail-content-edit-menu"
			:items="menuItems"
			:width="302"
			:bindElement="$refs.detailPanelEditButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
