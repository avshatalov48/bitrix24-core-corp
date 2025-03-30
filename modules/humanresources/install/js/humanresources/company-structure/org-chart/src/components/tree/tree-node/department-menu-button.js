import { EventEmitter } from 'main.core.events';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { Main, CRM } from 'ui.icon-set.api.core';
import { getColorCode } from 'humanresources.company-structure.utils';
import 'ui.icon-set.main';
import { events } from '../../../events';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

export const MenuActions = Object.freeze({
	editDepartment: 'editDepartment',
	addDepartment: 'addDepartment',
	editEmployee: 'editEmployee',
	moveEmployee: 'moveEmployee',
	addEmployee: 'addEmployee',
	userInvite: 'userInvite',
	removeDepartment: 'removeDepartment',
});

export const DepartmentMenuButton = {
	name: 'DepartmentMenuButton',
	emits: ['addDepartment', 'editDepartment', 'moveEmployee', 'addEmployee', 'removeDepartment', 'editEmployee', 'userInvite'],

	props: {
		departmentId: {
			type: Number,
			required: true,
		},
	},

	components: {
		RouteActionMenu,
	},

	created(): void
	{
		this.menuItems = [];
		this.permissionChecker = PermissionChecker.getInstance();

		if (!this.permissionChecker)
		{
			return;
		}

		this.menuItems = [
			{
				id: MenuActions.editDepartment,
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
				id: MenuActions.addDepartment,
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
				id: MenuActions.editEmployee,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE'),
				imageClass: '-hr-department-org-chart-menu-edit-list',
				bIcon: {
					name: Main.EDIT_MENU,
					size: 20,
					color: getColorCode('paletteBlue50'),
				},
				permission: { action: PermissionActions.employeeAddToDepartment },
			},
			{
				id: MenuActions.moveEmployee,
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
				id: MenuActions.userInvite,
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
				id: MenuActions.addEmployee,
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
				id: MenuActions.removeDepartment,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE'),
				bIcon: {
					name: Main.TRASH_BIN,
					size: 20,
					color: getColorCode('paletteRed40'),
				},
				permission: { action: PermissionActions.departmentDelete },
			},
		];

		this.menuItems = this.menuItems.filter((item) => {
			if (!item.permission)
			{
				return false;
			}

			return this.permissionChecker.hasPermission(item.permission.action, this.departmentId);
		});
	},

	data(): Object
	{
		return {
			menu: {
				visible: false,
			},
		};
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.$emit(actionId, actionId);
		},
		closeMenu(): void
		{
			this.menu.visible = false;
			EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
		},
		openMenu(): void
		{
			this.menu.visible = true;
			EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
		},
	},

	template: `
		<div
			v-if="menuItems.length"
			class="ui-icon-set --more humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.menu.visible }"
			ref="departmentMenuButton"
			@click.stop="openMenu"
		>
		</div>

		<RouteActionMenu
			v-if="menu.visible"
			:id="'tree-node-department-menu-' + departmentId"
			:width="302"
			:items="menuItems"
			:bindElement="this.$refs.departmentMenuButton"
			@action="onActionMenuItemClick"
			@close="closeMenu"
		/>
	`,
};
