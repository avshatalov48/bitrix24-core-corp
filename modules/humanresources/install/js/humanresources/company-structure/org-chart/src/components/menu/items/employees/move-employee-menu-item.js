import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';
import { UserManagementDialog } from 'humanresources.company-structure.user-management-dialog';

export class MoveEmployeeMenuItem extends AbstractMenuItem
{
	constructor()
	{
		super({
			id: MenuActions.moveEmployee,
			title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
			description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
			bIcon: {
				name: Main.PERSON_ARROW_LEFT_1,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: PermissionActions.employeeAddToDepartment,
			dataTestId: 'hr-company-structure_menu__move-employee-item',
		});
	}

	invoke({ entityId, analyticSource }): void
	{
		UserManagementDialog.openDialog({
			nodeId: entityId,
			type: 'move',
		});
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		return permissionChecker.currentUserPermissions.ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT
			&& permissionChecker.hasPermission(this.permissionAction, entityId)
		;
	}
}
