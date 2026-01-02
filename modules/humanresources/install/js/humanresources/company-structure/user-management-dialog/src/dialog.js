import { EntityTypes } from 'humanresources.company-structure.utils';
import { Type, Loc } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import type { UserManagementDialogConfiguration } from './types';
import { BaseUserManagementDialogHeader } from './header';
import { BaseUserManagementDialogFooter } from './footer';
import { getMemberRoles, type MemberRolesType } from 'humanresources.company-structure.api';
import { allowedDialogTypes } from './consts';
import './style.css';

const dialogId = 'hr-user-management-dialog';

export { UserManagementDialogAPI } from './api';

export class UserManagementDialog
{
	#dialog: Dialog;
	#nodeId: number;
	#type: string;
	#role: string;
	#entityType: string;
	#memberRoles: MemberRolesType;

	constructor(options: UserManagementDialogConfiguration)
	{
		if (Type.isInteger(options.nodeId))
		{
			this.#nodeId = options.nodeId;
		}
		else
		{
			throw new TypeError("Invalid argument 'nodeId'. An integer value was expected.");
		}

		if (Type.isString(options.type) && allowedDialogTypes.includes(options.type))
		{
			this.#type = options.type;
		}
		else
		{
			throw new TypeError(`Invalid argument 'type'. Expected one of: ${allowedDialogTypes.join(', ')}`);
		}

		if (Type.isString(options.entityType) && Object.values(EntityTypes).includes(options.entityType))
		{
			this.#entityType = options.entityType;
			if (this.#entityType === EntityTypes.team)
			{
				this.#type = 'add';
			}
		}
		else
		{
			this.#entityType = EntityTypes.department;
		}
		this.#memberRoles = getMemberRoles(this.#entityType);

		if (Object.values(this.#memberRoles).includes(options.role))
		{
			this.#role = options.role;
		}
		else
		{
			this.#role = this.#memberRoles.employee;
		}

		this.id = `${dialogId}-${this.#type}`;
		this.title = this.#getTitleByTypeAndRoleAndEntity(this.#type, this.#role, this.#entityType);
		this.description = this.#getDescriptionByTypeRoleAndEntity(this.#type, this.#role, this.#entityType);
		this.#createDialog();
	}

	static openDialog(options: UserManagementDialogConfiguration): void
	{
		const instance = new UserManagementDialog(options);
		instance.show();
	}

	show(): void
	{
		this.#dialog.show();
	}

	#createDialog(): void
	{
		this.#dialog = new Dialog({
			id: this.id,
			width: 400,
			height: 511,
			multiple: true,
			cacheable: false,
			dropdownMode: true,
			compactView: false,
			enableSearch: true,
			showAvatars: true,
			autoHide: false,
			header: BaseUserManagementDialogHeader,
			headerOptions: {
				title: this.title,
				role: this.#role,
				description: this.description,
				memberRoles: this.#memberRoles,
				entityType: this.#entityType,
			},
			footer: BaseUserManagementDialogFooter,
			footerOptions: {
				nodeId: this.#nodeId,
				role: this.#role,
				type: this.#type,
				entityType: this.#entityType,
			},
			popupOptions: {
				overlay: { opacity: 40 },
			},
			entities: [
				{
					id: 'user',
					options: {
						intranetUsersOnly: true,
						inviteEmployeeLink: false,
					},
				},
			],
		});
	}

	#getTitleByTypeAndRoleAndEntity(type: string, role: string, entityType: string): string
	{
		if (type === 'add' && role === this.#memberRoles.employee && entityType === EntityTypes.team)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_EMPLOYEE_TITLE');
		}

		if (type === 'move' && role === this.#memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_TITLE');
		}

		if (type === 'add' && role === this.#memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_TITLE');
		}

		if (type === 'add' && role === this.#memberRoles.head && entityType === EntityTypes.team)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_HEAD_TITLE');
		}

		if (type === 'add' && role === this.#memberRoles.head)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_HEAD_TITLE');
		}

		return '';
	}

	#getDescriptionByTypeRoleAndEntity(type: string, role: string, entityType: string): string
	{
		if (type === 'add' && entityType === EntityTypes.team)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_EMPLOYEE_DESCRIPTION');
		}

		if (type === 'move' && role === this.#memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_DESCRIPTION');
		}

		if (type === 'add' && role === this.#memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_DESCRIPTION');
		}

		return '';
	}
}
