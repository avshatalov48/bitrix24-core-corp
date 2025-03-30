import { Dom, Event, Tag, Loc, Text } from 'main.core';
import { BaseHeader, type Dialog, type Tab, type HeadOptions } from 'ui.entity-selector';
import { Menu, PopupManager } from 'main.popup';
import { BaseUserManagementDialogFooter } from './footer';
import { memberRoles } from 'humanresources.company-structure.api';

export class BaseUserManagementDialogHeader extends BaseHeader
{
	title: string;
	description: string;
	role: string;

	constructor(context: Dialog | Tab, options: HeadOptions)
	{
		super(context, options);

		this.tiltle = Text.encode(this.getOption('title') ?? '');
		this.description = Text.encode(this.getOption('description') ?? '');
		this.role = this.getOption('role') ?? memberRoles.employee;
	}

	render(): HTMLElement
	{
		const { header, headerCloseButton } = Tag.render`
			<div ref="header" class="hr-user-management-dialog__header">
				<div ref="headerCloseButton" class="hr-user-management-dialog__header-close_button"></div>
				<span class="hr-user-management-dialog__header-title">
					${this.tiltle}
				</span>
			</div>
		`;

		Event.bind(headerCloseButton, 'click', () => {
			this.getDialog().hide();
		});

		this.header = header;
		if (this.role === memberRoles.employee)
		{
			const employeeAddSubtitle = Tag.render`
				<span class="hr-user-management-dialog__header-description">
					${this.description}
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
			<div ref="roleSwitcherContainer" class="hr-user-management-dialog__role_switcher-container">
				<span class="hr-user-management-dialog__role_switcher_title">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ROLE_PICKER_TEXT')}
					</span>
				<div ref="roleSwitcher" class="hr-user-management-dialog__role_switcher">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_HEAD_ROLE_TITLE')}
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
				html: Tag.render`
					<div 
						data-test-id="hr-company-structure_user-management-dialog__role-switcher-head"
					>
						${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_HEAD_ROLE_TITLE')}
					</div>
				`,
				onclick: () => {
					this.roleSwitcher.innerText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_HEAD_ROLE_TITLE');
					this.#changeRole(memberRoles.head);
					roleSwitcherMenu.destroy();
				},
			},
			{
				html: Tag.render`
					<div 
						data-test-id="hr-company-structure_user-management-dialog__role-switcher-deputy"
					>
						${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_DEPUTY_ROLE_TITLE')}
					</div>
				`,
				onclick: () => {
					this.roleSwitcher.innerText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_DEPUTY_ROLE_TITLE');
					this.#changeRole(memberRoles.deputyHead);
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
		const currentFooterOptions = this.getDialog().getFooter().getOptions();
		currentFooterOptions.role = role;
		this.getDialog().setFooter(BaseUserManagementDialogFooter, currentFooterOptions);
	}
}
