import { RouteActionMenu, ConfirmationPopup, MoveUserPopup } from 'humanresources.company-structure.structure-components';
import { Text } from 'main.core';
import { UserListActionMenu, MenuActions } from 'humanresources.company-structure.org-chart';
import { DepartmentAPI } from '../../../api';
import { memberRoles } from 'humanresources.company-structure.api';
import { useChartStore, UserService } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { UI } from 'ui.notification';
import { EntityTypes } from 'humanresources.company-structure.utils';

import './styles/action-button.css';

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
		MoveUserPopup,
	},

	data(): Object
	{
		return {
			menuVisible: {},
			showRemoveUserConfirmationPopup: false,
			showRemoveUserConfirmationActionLoader: false,
			showMoveUserPopup: false,
			showMoveUserPopupOnlyMoveMode: false,
			showFireUserPopup: false,
			fireUserLoad: false,
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
			if (actionId === MenuActions.removeUserFromDepartment)
			{
				if (this.isTeamEntity)
				{
					this.showRemoveUserConfirmationPopup = true;
				}
				else
				{
					this.showMoveUserPopupOnlyMoveMode = false;
					this.showMoveUserPopup = true;
				}
			}

			if (actionId === MenuActions.moveUserToAnotherDepartment)
			{
				this.showMoveUserPopupOnlyMoveMode = true;
				this.showMoveUserPopup = true;
			}

			if (actionId === MenuActions.fireUserFromCompany)
			{
				this.showFireUserPopup = true;
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
			this.showMoveUserPopup = false;

			try
			{
				await DepartmentAPI.removeUserFromDepartment(departmentId, userId);
			}
			catch
			{
				const phraseCode = this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_ERROR')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_ERROR')
				;
				UI.Notification.Center.notify({
					content: phraseCode,
					autoHideDelay: 2000,
				});

				return;
			}

			const phraseCode = this.isTeamEntity
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_SUCCESS'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUCCESS';
			const departmentName = Text.encode(this.departments.get(this.focusedNode).name ?? '');
			UI.Notification.Center.notify({
				content: this.loc(phraseCode, { '#DEPARTMENT_NAME#': departmentName }),
				autoHideDelay: 2000,
			});

			const role = this.user.role;
			if (isUserInMultipleDepartments || this.isTeamEntity)
			{
				UserService.removeUserFromEntity(departmentId, userId, role);

				return;
			}

			const rootDepartment = [...this.departments.values()].find((department) => department.parentId === 0);
			if (!rootDepartment)
			{
				return;
			}

			UserService.moveUserToEntity(
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
		async fireUser(): void
		{
			this.fireUserLoad = true;

			const userId = this.user.id;

			try
			{
				await DepartmentAPI.fireUser(userId);
			}
			catch (error)
			{
				if (error.code !== 'STRUCTURE_ACCESS_DENIED')
				{
					UI.Notification.Center.notify({
						content: this.user.isInvited
							? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_ERROR')
							: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_ERROR'),
						autoHideDelay: 2000,
					});
				}

				this.showFireUserPopup = false;
				this.fireUserLoad = false;

				return;
			}

			UserService.removeUserFromEntity(this.focusedNode, userId, this.user.role);
			await UserService.removeUserFromAllEntities(userId);
			this.showFireUserPopup = false;
			this.fireUserLoad = false;
		},
		cancelFireUser(): void
		{
			this.showFireUserPopup = false;
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
		this.menu = new UserListActionMenu(this.focusedNode, this.entityType, this.user.isInvited);
	},

	computed: {
		...mapState(useChartStore, ['focusedNode', 'departments']),
		memberRoles(): typeof memberRoles
		{
			return memberRoles;
		},
		getFirePopupDescription(): string
		{
			const userName = Text.encode(this.user.name ?? '');
			const userUrl = Text.encode(this.user.url ?? '');
			const genderSuffix = this.user.gender === 'F' ? '_F' : '_M';

			const phrase = this.user.isInvited
				? `HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_DESCRIPTION${genderSuffix}`
				: `HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_DESCRIPTION${genderSuffix}`
			;

			const description = this.loc(phrase, {
				'#USER_NAME#': userName,
			});

			return description.replace('[link]', `<a class="hr-department-detail-content__fire-user-link" href="${userUrl}">`)
				.replace('[/link]', '</a>')
			;
		},
		getFirePopupTitle(): string
		{
			return this.user.isInvited
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_TITLE');
		},
		getFirePopupButtonText(): string
		{
			return this.user.isInvited
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_CONFIRM_BUTTON')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_CONFIRM_BUTTON');
		},
		getRemovePopupTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_POPUP_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_TITLE');
		},
		getRemovePopupDescription(): string
		{
			const departmentName = Text.encode(this.departments.get(this.focusedNode).name ?? '');
			const userName = Text.encode(this.user.name ?? '');

			if (this.isTeamEntity)
			{
				const phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_POPUP_DESCRIPTION';
				const genderSuffix = this.user.gender === 'F' ? '_F' : '_M';

				return this.loc(
					phraseCode + genderSuffix,
					{
						'#USER_NAME#': userName,
						'#DEPARTMENT_NAME#': departmentName,
					},
				)
					.replace(
						'[link]',
						`<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`,
					)
					.replace('[/link]', '</a>')
				;
			}

			return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_DESCRIPTION');
		},
		entityType(): string
		{
			return this.departments.get(this.focusedNode)?.entityType;
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
	},

	template: `
		<button
			v-if="menu.items.length"
			class="ui-icon-set --more hr-department-detail-content__user-action-btn"
			:class="{ '--focused': menuVisible[user.id] }"
			@click.stop="toggleMenu(user.id)"
			ref="actionUserButton"
			:data-id="'hr-department-detail-content__'+ getMemberKeyByValue(user.role) + '-list_user-' + user.id + '-action-btn'"
		/>
		<RouteActionMenu
			v-if="menuVisible[user.id]"
			:id="'tree-node-department-menu-' + user.id"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.actionUserButton"
			@action="onActionMenuItemClick"
			@close="menuVisible[user.id] = false"
		/>
		<ConfirmationPopup
			ref="removeUserConfirmationPopup"
			v-if="showRemoveUserConfirmationPopup"
			:showActionButtonLoader="showRemoveUserConfirmationActionLoader"
			:title="getRemovePopupTitle"
			:confirmBtnText="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			:confirmButtonClass="isTeamEntity ? 'ui-btn-danger' : 'ui-btn-primary'"
			@action="removeUser"
			@close="cancelRemoveUser"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
				<div
					v-html="getRemovePopupDescription"
				/>
				</div>
			</template>
		</ConfirmationPopup>
		<ConfirmationPopup
			ref="fireUserConfirmationPopup"
			v-if="showFireUserPopup"
			:showActionButtonLoader="fireUserLoad"
			:title="getFirePopupTitle"
			:confirmBtnText="getFirePopupButtonText"
			:confirmButtonClass="'ui-btn-danger'"
			@action="fireUser"
			@close="cancelFireUser"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div
						v-html="getFirePopupDescription"
					/>
				</div>
			</template>
		</ConfirmationPopup>
		<MoveUserPopup
			v-if="showMoveUserPopup"
			:originalNodeId="focusedNode"
			:user="user"
			:entityType="entityType"
			:onlyMove="showMoveUserPopupOnlyMoveMode"
			@action="handleMoveUserAction"
			@close="handleMoveUserClose"
			@remove="removeUser"
		/>
	`,
};
