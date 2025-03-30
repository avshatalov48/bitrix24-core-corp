import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { getColorCode } from 'humanresources.company-structure.utils';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { CRM, Main } from 'ui.icon-set.api.core';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

import 'ui.icon-set.crm';
import './styles/list-action-button.css';

const MenuOption = Object.freeze({
	addToDepartment: 'addToDepartment',
	editDepartmentUsers: 'editDepartmentUsers',
});

export const UserListActionButton = {
	name: 'userListActionButton',
	emits: ['addToDepartment', 'editDepartmentUsers'],

	props:
	{
		role: {
			type: String,
			default: 'employee',
		},
		departmentId: {
			type: Number,
			required: true,
		},
	},

	components:
		{
			RouteActionMenu,
		},

	created()
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.menuItems = this.getMenuItems();
	},

	watch:
	{
		departmentId()
		{
			this.menuItems = this.getMenuItems();
		},
	},

	computed:
		{
			...mapState(useChartStore, ['focusedNode']),
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
			this.$emit(actionId);
		},
		getMenuItems(): Array
		{
			if (!this.permissionChecker)
			{
				return [];
			}

			let menuItems = [
				{
					id: MenuOption.addToDepartment,
					bIcon: {
						name: CRM.PERSON_PLUS_2,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: {
						action: PermissionActions.employeeAddToDepartment,
					},
				},
				{
					id: MenuOption.editDepartmentUsers,
					bIcon: {
						name: Main.EDIT_MENU,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: {
						action: PermissionActions.employeeAddToDepartment,
					},
				},
			];

			const menuItemText = {
				[MenuOption.addToDepartment]: {
					head: {
						title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_TITLE'),
						description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_SUBTITLE'),
					},
					employee: {
						title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_TITLE'),
						description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_SUBTITLE'),
					},
				},
				[MenuOption.editDepartmentUsers]: {
					head: {
						title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_TITLE'),
						description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_SUBTITLE'),
					},
					employee: {
						title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_TITLE'),
						description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_SUBTITLE'),
					},
				},
			};

			menuItems = menuItems.map((item) => ({
				...item,
				...menuItemText[item.id][this.role],
			}));

			return menuItems.filter((item) => {
				if (!item.permission)
				{
					return false;
				}

				return this.permissionChecker.hasPermission(item.permission.action, this.departmentId);
			});
		},
	},

	template: `
		<button
			v-if="menuItems.length"
			class="hr-department-detail-content__list-header-button"
			:class="{ '--focused': menuVisible }"
			:ref="'actionMenuButton' + role"
			@click.stop="menuVisible = true"
			:data-ld="'hr-department-detail-content__' + role + '_list-header-button'"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_ACTION_BUTTON_TITLE') }}
		</button>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'tree-node-department-menu-' + role + '-' + focusedNode"
			:items="menuItems"
			:width="302"
			:bindElement="$refs['actionMenuButton' + role]"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
