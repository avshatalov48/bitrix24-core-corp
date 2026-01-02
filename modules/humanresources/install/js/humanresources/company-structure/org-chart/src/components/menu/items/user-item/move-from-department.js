import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class MoveFromDepartmentMenuItem extends AbstractMenuItem
{
	constructor(entityType: string)
	{
		const title = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_TEAM_TITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_TITLE')
		;

		const description = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_TEAM_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUBTITLE')
		;

		super({
			id: MenuActions.moveUserToAnotherDepartment,
			title,
			description,
			bIcon: {
				name: Main.PERSON_ARROW_LEFT_1,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: null,
			dataTestId: 'hr-department-detail-content__user-list_move-from-department',
		});

		this.entityType = entityType;
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		const permissions = permissionChecker.currentUserPermissions;

		const addMemberPermissionAction = this.entityType === EntityTypes.team
			? PermissionActions.teamAddMember
			: PermissionActions.employeeAddToDepartment
		;

		const removeMemberPermissionAction = this.entityType === EntityTypes.team
			? PermissionActions.teamRemoveMember
			: PermissionActions.employeeRemoveFromDepartment
		;

		const addMemberPermissionValue = this.entityType === EntityTypes.team
			? permissions.ACTION_TEAM_MEMBER_ADD
			: permissions.ACTION_EMPLOYEE_ADD_TO_DEPARTMENT
		;

		const removeMemberPermissionValue = this.entityType === EntityTypes.team
			? permissions.ACTION_TEAM_MEMBER_REMOVE
			: permissions.ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT
		;

		const moveUserPermission = addMemberPermissionValue < removeMemberPermissionValue
			? addMemberPermissionAction
			: removeMemberPermissionAction
		;

		return permissionChecker.hasPermission(moveUserPermission, entityId);
	}
}
