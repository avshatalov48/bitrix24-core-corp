import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Main } from 'ui.icon-set.api.core';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class RemoveDepartmentMenuItem extends AbstractMenuItem
{
	constructor(entityType: string)
	{
		const title = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_TEAM_TITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE');

		const description = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_TEAM_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE');

		const permissionAction = entityType === EntityTypes.team
			? PermissionActions.teamDelete
			: PermissionActions.departmentDelete
		;

		super({
			id: MenuActions.removeDepartment,
			title,
			description,
			bIcon: {
				name: Main.TRASH_BIN,
				size: 20,
				color: getColorCode('paletteRed40'),
			},
			permissionAction,
			dataTestId: 'hr-company-structure_menu__remove-department-item',
		});
	}

	invoke({ entityId, entityType }): void
	{
		EventEmitter.emit(events.HR_ENTITY_REMOVE, { nodeId: entityId, entityType });
	}
}
