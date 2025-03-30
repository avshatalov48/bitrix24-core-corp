import { Type, Loc } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import type { UserManagementDialogConfiguration } from './types';
import { BaseUserManagementDialogHeader } from './header';
import { BaseUserManagementDialogFooter } from './footer';
import { memberRoles } from 'humanresources.company-structure.api';
import { allowedDialogTypes } from './consts';
import './style.css';

const dialogId = 'hr-user-management-dialog';

export class UserManagementDialog
{
	#dialog: Dialog;
	#nodeId: number;
	#type: string;
	#role: string;

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

		if (Object.values(memberRoles).includes(options.role))
		{
			this.#role = options.role;
		}
		else
		{
			this.#role = memberRoles.employee;
		}

		this.id = `${dialogId}-${this.#type}`;
		this.title = this.#getTitleByTypeAndRole(this.#type, this.#role);
		this.description = this.#getDescriptionByTypeAndRole(this.#type, this.#role);
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
			},
			footer: BaseUserManagementDialogFooter,
			footerOptions: {
				nodeId: this.#nodeId,
				role: this.#role,
				type: this.#type,
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

	#getTitleByTypeAndRole(type: string, role: string): string
	{
		if (type === 'move' && role === memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_TITLE');
		}

		if (type === 'add' && role === memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_TITLE');
		}

		if (type === 'add' && role === memberRoles.head)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_HEAD_TITLE');
		}

		return '';
	}

	#getDescriptionByTypeAndRole(type: string, role: string): string
	{
		if (type === 'move' && role === memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_DESCRIPTION');
		}

		if (type === 'add' && role === memberRoles.employee)
		{
			return Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_DESCRIPTION');
		}

		return '';
	}
}
