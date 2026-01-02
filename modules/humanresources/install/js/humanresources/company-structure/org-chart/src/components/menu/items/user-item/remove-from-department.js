import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class RemoveFromDepartmentMenuItem extends AbstractMenuItem
{
	constructor(entityType: string)
	{
		const title = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_TEAM_TITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_DEPARTMENT_TITLE')
		;

		const description = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_TEAM_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUBTITLE')
		;

		const color = entityType === EntityTypes.team
			? getColorCode('paletteRed40')
			: getColorCode('paletteBlue50')
		;

		const permissionAction = entityType === EntityTypes.team
			? PermissionActions.teamRemoveMember
			: PermissionActions.employeeRemoveFromDepartment
		;

		super({
			id: MenuActions.removeUserFromDepartment,
			title,
			description,
			bIcon: {
				name: Main.TRASH_BIN,
				size: 20,
				color,
			},
			permissionAction,
			dataTestId: 'hr-department-detail-content__user-list_remove-from-department',
		});
	}
}
