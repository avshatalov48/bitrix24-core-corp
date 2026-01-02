import {
	PermissionActions,
	PermissionChecker,
	PermissionCheckerClass,
} from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Main } from 'ui.icon-set.api.core';
import { UI } from 'ui.notification';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class AddDepartmentMenuItem extends AbstractMenuItem
{
	permissionChecker: PermissionCheckerClass;
	entityType: null;

	constructor(entityType: string)
	{
		let title = '';
		let description = '';

		// temporary check for option
		const permissionChecker = PermissionChecker.getInstance();
		if (permissionChecker.isTeamsAvailable)
		{
			title = entityType === EntityTypes.team
				? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_TITLE')
				: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE_MSGVER_1');

			description = entityType === EntityTypes.team
				? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_SUBTITLE')
				: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE_MSGVER_1');
		}
		else
		{
			title = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE_NO_TEAM');
			description = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE_NO_TEAM');
		}

		super({
			id: MenuActions.addDepartment,
			title,
			description,
			bIcon: {
				name: Main.CUBE_PLUS,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: null,
			dataTestId: 'hr-company-structure_menu__add-department-item',
		});

		this.entityType = entityType;
		this.permissionChecker = permissionChecker;
		this.entityType = entityType;
	}

	invoke({ entityId, analyticSource, entityType }): void
	{
		// for a team we can create only another team
		if (entityType === EntityTypes.team)
		{
			if (!this.permissionChecker.isTeamsAvailable)
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_TEAMS_DISABLED_ERROR_MSGVER_1'),
					autoHideDelay: 2000,
				});

				return;
			}
			EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
				nodeId: entityId,
				isEditMode: false,
				showEntitySelector: false,
				source: analyticSource,
				entityType,
			});
		}
		// for a department we offer to chose entity
		else
		{
			EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
				nodeId: entityId,
				isEditMode: false,
				showEntitySelector: true,
				source: analyticSource,
				entityType,
			});
		}
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		if (this.entityType === EntityTypes.team)
		{
			return permissionChecker.hasPermission(PermissionActions.teamCreate, entityId);
		}

		return permissionChecker.hasPermission(PermissionActions.departmentCreate, entityId)
			|| permissionChecker.hasPermission(PermissionActions.teamCreate, entityId)
		;
	}
}
