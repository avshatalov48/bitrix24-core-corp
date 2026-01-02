import { memberRoles, teamMemberRoles } from 'humanresources.company-structure.api';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { CRM } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';
import { UserManagementDialog } from 'humanresources.company-structure.user-management-dialog';

export class AddEmployeeMenuItem extends AbstractMenuItem
{
	role: ?string;

	constructor(entityType: string, role: ?string)
	{
		const permissionAction = entityType === EntityTypes.team
			? PermissionActions.teamAddMember
			: PermissionActions.employeeAddToDepartment
		;

		super({
			id: MenuActions.addEmployee,
			bIcon: {
				name: CRM.PERSON_PLUS_2,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction,
			dataTestId: 'hr-company-structure_menu__add-employee-item',
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
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_HEAD_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_HEAD_SUBTITLE'),
					role: teamMemberRoles.head,
				},
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_SUBTITLE'),
					role: memberRoles.head,
				},
			},
			employee: {
				team: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_EMPLOYEE_SUBTITLE'),
					role: teamMemberRoles.employee,
				},
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_SUBTITLE'),
					role: memberRoles.employee,
				},
			},
			default: {
				team: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_EMPLOYEE_SUBTITLE'),
					role: null,
				},
				default: {
					title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
					description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
					role: null,
				},
			},
		};

		this.title = i18nMap[i18nRole][i18nType].title;
		this.description = i18nMap[i18nRole][i18nType].description;
		this.role = i18nMap[i18nRole][i18nType].role;
	}

	invoke({ entityId, entityType }): void
	{
		UserManagementDialog.openDialog({
			nodeId: entityId,
			type: 'add',
			role: this.role,
			entityType,
		});
	}
}
