import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class FireUserFromCompanyMenuItem extends AbstractMenuItem
{
	constructor(isUserInvited: boolean)
	{
		super({
			id: MenuActions.fireUserFromCompany,
			title: isUserInvited
				? Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_DELETE_FROM_COMPANY_TITLE',
				)
				: Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_FIRE_FROM_COMPANY_TITLE',
				),
			description: Loc.getMessage(
				'HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_FIRE_FROM_COMPANY_SUBTITLE_MSGVER_1',
			),
			bIcon: {
				name: Main.PERSONS_DENY,
				size: 20,
				color: getColorCode('paletteRed40'),
			},
			permissionAction: isUserInvited ? '' : PermissionActions.employeeFire,
			dataTestId: 'hr-department-detail-content__user-list_fire-user-from-department',
		});
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		return permissionChecker.hasPermissionOfAction(this.permissionAction);
	}
}
