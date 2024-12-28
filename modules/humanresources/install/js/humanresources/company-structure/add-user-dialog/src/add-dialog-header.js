import { Dom, Event, Tag, Loc } from 'main.core';
import { Menu, PopupManager } from 'main.popup';
import { Dialog, BaseHeader, Tab, HeadOptions } from 'ui.entity-selector';
import { memberRoles } from 'humanresources.company-structure.api';

const employeeType = memberRoles.employee;
const headType = memberRoles.head;
const deputyHeadType = memberRoles.deputyHead;
import { AddDialogFooter } from './add-dialog-footer';

export class AddDialogHeader extends BaseHeader
{
	constructor(context: Dialog | Tab, options: HeadOptions)
	{
		super(context, options);

		this.role = this.getOption('role') ?? employeeType;
	}

	render(): HTMLElement
	{
		const title = this.role === headType
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_HEAD_DIALOG_HEADER_TITLE')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_EMPLOYEE_DIALOG_HEADER_TITLE')
		;

		const { header, headerCloseButton } = Tag.render`
			<div ref="header" class="hr-add-employee-to-department-dialog__header">
				<div ref="headerCloseButton" class="hr-add-employee-to-department-dialog__header-close_button"></div>
				<span class="hr-add-employee-to-department-dialog__header-title">
					${title}
				</span>
			</div>
		`;

		Event.bind(headerCloseButton, 'click', (event) => {
			this.getDialog().hide();
		});

		this.header = header;
		if (this.role === employeeType)
		{
			const employeeAddSubtitle = Tag.render`
				<span class="hr-add-employee-to-department-dialog__header-subtitle">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_EMPLOYEE_DIALOG_HEADER_SUBTITLE')}
				</span>
			`;
			Dom.append(employeeAddSubtitle, this.header);
		}
		else
		{
			this.#addRoleSwitcher();
		}

		return header;
	}

	#addRoleSwitcher(): void
	{
		const { roleSwitcherContainer, roleSwitcher } = Tag.render`
			<div ref="roleSwitcherContainer" class="hr-add-employee-to-department-dialog__role_switcher-container">
				<span class="hr-add-employee-to-department-dialog__role_switcher_title">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_TITLE')}
					</span>
				<div ref="roleSwitcher" class="hr-add-employee-to-department-dialog__role_switcher">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE')}
				</div>
			</div>
		`;
		Dom.append(roleSwitcherContainer, this.header);
		this.roleSwitcher = roleSwitcher;

		Event.bind(this.roleSwitcher, 'click', () => {
			this.#toggleRoleSwitcherMenu();
		});
	}

	#toggleRoleSwitcherMenu(): void
	{
		const roleSwitcherId = `${this.getDialog().id}-role-switcher`;
		const oldRoleSwitcherMenu = PopupManager.getPopupById(roleSwitcherId);
		if (oldRoleSwitcherMenu)
		{
			oldRoleSwitcherMenu.destroy();

			return;
		}

		const roleSwitcherMenu = new Menu({
			id: roleSwitcherId,
			bindElement: this.roleSwitcher,
			autoHide: true,
			closeByEsc: true,
			maxWidth: 263,
			events: {
				onPopupDestroy: () => {
					Dom.removeClass(this.roleSwitcher, '--focused');
				},
			},
		});

		const menuItems = [
			{
				text: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE'),
				onclick: () => {
					this.roleSwitcher.innerText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE');
					this.#changeRole(headType);
					roleSwitcherMenu.destroy();
				},
			},
			{
				text: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_DEPUTY_HEAD_TITLE'),
				onclick: () => {
					this.roleSwitcher.innerText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_DEPUTY_HEAD_TITLE');
					this.#changeRole(deputyHeadType);
					roleSwitcherMenu.destroy();
				},
			},
		];
		menuItems.forEach((menuItem) => roleSwitcherMenu.addMenuItem(menuItem));

		if (roleSwitcherMenu.isShown)
		{
			roleSwitcherMenu.destroy();

			return;
		}

		roleSwitcherMenu.show();
		Dom.addClass(this.roleSwitcher, '--focused');
	}

	#changeRole(role: string): void
	{
		this.getDialog().getRecentTab().setFooter(AddDialogFooter, { role });
		this.getDialog().setFooter(AddDialogFooter, { role });
	}
}
