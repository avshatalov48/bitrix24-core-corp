import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Main } from 'ui.icon-set.api.core';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class EditEmployeeMenuItem extends AbstractMenuItem
{
	role: ?string;

	constructor(entityType: string, role: ?string)
	{
		super({
			id: MenuActions.editEmployee,
			imageClass: '-hr-department-org-chart-menu-edit-list',
			bIcon: {
				name: Main.EDIT_MENU,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: PermissionActions.employeeAddToDepartment,
		});

		this.localize(entityType, role);
	}

	localize(entityType: string, role: ?string): [string, string]
	{
		const i18nRole = ['head', 'employee'].includes(role) ? role : 'default';
		const i18nType = entityType === EntityTypes.team ? 'team' : 'default';

		const i18nMap = {
			head: {
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_SUBTITLE'),
				},
			},
			employee: {
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_SUBTITLE'),
				},
			},
			default: {
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE'),
				},
			},
		};

		this.title = i18nMap[i18nRole][i18nType].title;
		this.description = i18nMap[i18nRole][i18nType].description;
	}

	invoke({ entityId, analyticSource }): void
	{
		EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
			nodeId: entityId,
			isEditMode: true,
			type: 'employees',
			source: analyticSource,
		});
	}
}
