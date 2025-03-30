import { RouteActionMenu, ConfirmationPopup } from 'humanresources.company-structure.structure-components';
import { Main } from 'ui.icon-set.api.core';
import { DepartmentAPI } from '../../../../api';
import { DepartmentContentActions } from '../../../../actions';
import { memberRoles } from 'humanresources.company-structure.api';
import { getColorCode } from 'humanresources.company-structure.utils';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { MoveUserActionPopup } from './move-user-action-popup';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { UI } from 'ui.notification';

import './styles/action-button.css';

const MenuOption = Object.freeze({
	removeUserFromDepartment: 'removeUserFromDepartment',
	moveUserToAnotherDepartment: 'moveUserToAnotherDepartment',
	fireUserFromCompany: 'fireUserFromCompany',
});

export const UserListItemActionButton = {
	name: 'userList',

	props: {
		user: {
			type: Object,
			required: true,
		},
		departmentId: {
			type: Number,
			required: true,
		},
	},

	components: {
		RouteActionMenu,
		ConfirmationPopup,
		MoveUserActionPopup,
	},

	data(): Object
	{
		return {
			menuVisible: {},
			showRemoveUserConfirmationPopup: false,
			showRemoveUserConfirmationActionLoader: false,
			showMoveUserPopup: false,
		};
	},

	methods: {
		toggleMenu(userId)
		{
			this.menuVisible[userId] = !this.menuVisible[userId];
		},
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			if (actionId === MenuOption.removeUserFromDepartment)
			{
				this.showRemoveUserConfirmationPopup = true;
			}

			if (actionId === MenuOption.moveUserToAnotherDepartment)
			{
				this.showMoveUserPopup = true;
			}
		},
		async removeUser(): Promise<void>
		{
			this.showRemoveUserConfirmationActionLoader = true;
			const userId = this.user.id;
			const isUserInMultipleDepartments = await DepartmentAPI.isUserInMultipleDepartments(userId);
			const departmentId = this.focusedNode;
			this.showRemoveUserConfirmationActionLoader = false;
			this.showRemoveUserConfirmationPopup = false;

			try
			{
				await DepartmentAPI.removeUserFromDepartment(departmentId, userId);
			}
			catch
			{
				UI.Notification.Center.notify({
					content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_ERROR'),
					autoHideDelay: 2000,
				});

				return;
			}

			const role = this.user.role;
			if (isUserInMultipleDepartments)
			{
				DepartmentContentActions.removeUserFromDepartment(departmentId, userId, role);

				return;
			}

			const rootDepartment = [...this.departments.values()].find((department) => department.parentId === 0);
			if (!rootDepartment)
			{
				return;
			}

			DepartmentContentActions.moveUserToDepartment(
				departmentId,
				userId,
				rootDepartment.id,
				role,
			);
		},
		cancelRemoveUser(): void
		{
			this.showRemoveUserConfirmationPopup = false;
		},
		handleMoveUserAction(): void
		{
			this.showMoveUserPopup = false;
		},
		handleMoveUserClose(): void
		{
			this.showMoveUserPopup = false;
		},
		getMemberKeyByValue(value: string): string
		{
			return Object.keys(memberRoles).find((key) => memberRoles[key] === value) || '';
		},
	},

	created(): void
	{
		const permissionChecker = PermissionChecker.getInstance();

		const menuItems = [
			{
				id: MenuOption.moveUserToAnotherDepartment,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUBTITLE'),
				bIcon: {
					name: Main.PERSON_ARROW_LEFT_1,
					size: 20,
					color: getColorCode('paletteBlue50'),
				},
				permission: {
					action: PermissionActions.employeeAddToDepartment,
				},
			},
			{
				id: MenuOption.removeUserFromDepartment,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUBTITLE'),
				bIcon: {
					name: Main.TRASH_BIN,
					size: 20,
					color: getColorCode('paletteRed40'),
				},
				permission: {
					action: PermissionActions.employeeRemoveFromDepartment,
				},
			},
			{
				id: MenuOption.fireUserFromCompany,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_FROM_COMPANY_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_FROM_COMPANY_SUBTITLE'),
				bIcon: {
					name: Main.PERSONS_DENY,
					size: 20,
					color: getColorCode('paletteRed40'),
				},
				permission: {
					action: PermissionActions.employeeFire,
				},
			},
		];

		this.menuItems = menuItems.filter((item) => {
			if (!item.permission)
			{
				return false;
			}

			return permissionChecker.hasPermission(item.permission.action, this.departmentId);
		});
	},

	computed: {
		...mapState(useChartStore, ['focusedNode', 'departments']),
		memberRoles(): typeof memberRoles
		{
			return memberRoles;
		},
	},

	template: `
		<button 
			v-if="menuItems.length"
			class="ui-icon-set --more hr-department-detail-content__user-action-btn"
			:class="{ '--focused': menuVisible[user.id] }"
			@click.stop="toggleMenu(user.id)"
			ref="actionUserButton"
			:data-id="'hr-department-detail-content__'+ getMemberKeyByValue(user.role) + '-list_user-' + user.id + '-action-btn'"
		/>
		<RouteActionMenu
			v-if="menuVisible[user.id]"
			:id="'tree-node-department-menu-' + user.id"
			:items="menuItems"
			:width="302"
			:bindElement="$refs.actionUserButton"
			@action="onActionMenuItemClick"
			@close="menuVisible[user.id] = false"
		/>
		<ConfirmationPopup
			ref="removeUserConfirmationPopup"
			v-if="showRemoveUserConfirmationPopup"
			:showActionButtonLoader="showRemoveUserConfirmationActionLoader"
			:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_TITLE')"
			:description="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_DESCRIPTION')"
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			@action="removeUser"
			@close="cancelRemoveUser"
		/>
		<MoveUserActionPopup
			v-if="showMoveUserPopup"
			:parentId="focusedNode"
			:user="user"
			@action="handleMoveUserAction"
			@close="handleMoveUserClose"
		/>
	`,
};
