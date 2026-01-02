import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Main } from 'ui.icon-set.api.core';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class EditDepartmentMenuItem extends AbstractMenuItem
{
	entityType: null;

	constructor(entityType: string)
	{
		const title = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_TITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_TITLE');

		const description = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_SUBTITLE_MSGVER_1')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_SUBTITLE_MSGVER_1');

		super({
			id: MenuActions.editDepartment,
			title,
			description,
			bIcon: {
				name: Main.EDIT_PENCIL,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: null,
			dataTestId: 'hr-company-structure_menu__edit-department-item',
		});

		this.entityType = entityType;
	}

	invoke({ entityId, analyticSource, refToFocus = 'title' }): void
	{
		EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
			nodeId: entityId,
			isEditMode: true,
			showEntitySelector: false,
			type: 'department',
			entityType: this.entityType,
			source: analyticSource,
			refToFocus,
		});
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		if (this.entityType === EntityTypes.team)
		{
			return permissionChecker.hasPermission(PermissionActions.teamEdit, entityId)
				|| permissionChecker.hasPermission(PermissionActions.teamAddMember, entityId)
				|| permissionChecker.hasPermission(PermissionActions.teamChatEdit, entityId)
				|| permissionChecker.hasPermission(PermissionActions.teamChannelEdit, entityId)
				|| permissionChecker.hasPermission(PermissionActions.teamCollabEdit, entityId)
				|| permissionChecker.hasPermission(PermissionActions.teamSettingsEdit, entityId)
			;
		}

		return permissionChecker.hasPermission(PermissionActions.departmentEdit, entityId)
			|| permissionChecker.hasPermission(PermissionActions.employeeAddToDepartment, entityId)
			|| permissionChecker.hasPermission(PermissionActions.departmentChatEdit, entityId)
			|| permissionChecker.hasPermission(PermissionActions.departmentChannelEdit, entityId)
			|| permissionChecker.hasPermission(PermissionActions.departmentCollabEdit, entityId)
		;
	}
}
