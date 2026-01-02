import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Main } from 'ui.icon-set.api.core';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class EditEmployeesMenuItem extends AbstractMenuItem
{
	role: ?string;

	constructor(entityType: string, role: ?string)
	{
		const permissionAction = entityType === EntityTypes.team
			? PermissionActions.teamAddMember
			: PermissionActions.employeeAddToDepartment
		;

		super({
			id: MenuActions.editEmployee,
			imageClass: '-hr-department-org-chart-menu-edit-list',
			bIcon: {
				name: Main.EDIT_MENU,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction,
			dataTestId: 'hr-company-structure_menu__edit-employee-item',
		});

		this.localize(entityType, role);
	}

	localize(entityType: string, role: ?string): [string, string]
	{
		const i18nRole = ['head', 'employee'].includes(role) ? role : 'default';
		const i18nType = entityType === EntityTypes.team ? 'team' : 'default';

		const i18nMap = {
			head: {
				team: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_HEAD_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_HEAD_SUBTITLE'),
				},
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_SUBTITLE'),
				},
			},
			employee: {
				team: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_SUBTITLE'),
				},
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_SUBTITLE'),
				},
			},
			default: {
				team: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_EMPLOYEE_LIST_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_EMPLOYEE_LIST_SUBTITLE'),
				},
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
			showEntitySelector: false,
			type: 'employees',
			source: analyticSource,
		});
	}
}
