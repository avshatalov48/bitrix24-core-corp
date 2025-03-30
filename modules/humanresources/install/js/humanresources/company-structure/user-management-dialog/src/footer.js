import { Dom, Event, Tag, Loc, Type, Text } from 'main.core';
import { BaseFooter, type FooterOptions, type ItemOptions, type Tab } from 'ui.entity-selector';
import { getUserDataBySelectorItem } from 'humanresources.company-structure.utils';
import { memberRoles } from 'humanresources.company-structure.api';
import type { BaseEvent } from 'main.core.events';
import { UI } from 'ui.notification';
import { UserManagementDialogActions } from './actions';
import { UserManagementDialogAPI } from './api';
import { allowedDialogTypes } from './consts';

const disabledButtonClass = 'ui-btn-disabled';

export class BaseUserManagementDialogFooter extends BaseFooter
{
	nodeId: number;
	confirmButtonText: ?string;
	type: string;
	role: string;

	constructor(tab: Tab, options: FooterOptions)
	{
		super(tab, options);

		this.nodeId = this.getOption('nodeId');
		if (!Type.isInteger(this.nodeId))
		{
			throw new TypeError("Invalid argument 'nodeId'. An integer value was expected.");
		}

		this.role = this.getOption('role') ?? memberRoles.employee;
		const type = this.getOption('type') ?? '';
		if (Type.isString(type) && allowedDialogTypes.includes(type))
		{
			this.type = type;
		}
		else
		{
			throw new TypeError(`Invalid argument 'type'. Expected one of: ${allowedDialogTypes.join(', ')}`);
		}

		this.#setConfirmButtonText();

		const selectedItems = this.getDialog().getSelectedItems();
		this.userCount = selectedItems.length;
		this.users = [];
		selectedItems.forEach((item) => {
			this.#onUserToggle(item);
		});

		this.getDialog().subscribe('Item:onSelect', this.#handleOnTagAdd.bind(this));
		this.getDialog().subscribe('Item:onDeselect', this.#handleOnTagRemove.bind(this));
	}

	render(): HTMLElement
	{
		const { footer, footerAddButton, footerCloseButton } = Tag.render`
			<div ref="footer" class="hr-user-management-dialog__footer">
				<button ref="footerAddButton" class="ui-btn ui-btn ui-btn-sm ui-btn-primary ${this.users.length === 0 ? disabledButtonClass : ''} ui-btn-round hr-user-management-dialog__footer-btn-width">
					${this.confirmButtonText ?? ''}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-user-management-dialog__footer-btn-width">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_CANCEL_BUTTON')}
				</button>
			</div>
		`;

		this.footerAddButton = footerAddButton;
		Event.bind(footerCloseButton, 'click', (event) => {
			this.dialog.hide();
		});

		Event.bind(footerAddButton, 'click', (event) => {
			const users = this.dialog.getSelectedItems();
			const userIds = users.map((item: Item): void => item.getId());

			if (userIds.length > 0)
			{
				Dom.addClass(footerAddButton, 'ui-btn-wait');
				this.action(userIds);
			}
		});

		return footer;
	}

	destroyDialog(): void
	{
		this.isInProcess = false;
		this.getDialog().destroy();
	}

	#handleOnTagAdd(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onUserToggle(item);
	}

	#handleOnTagRemove(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onUserToggle(item, false);
	}

	#onUserToggle(item: ItemOptions, isSelected: boolean = true): void
	{
		if (!isSelected)
		{
			this.users = this.users.filter((user) => user.id !== item.id);
			this.userCount -= 1;
			this.#toggleAddButton();

			return;
		}

		const userData = getUserDataBySelectorItem(item, this.role);
		this.users = [...this.users, userData];
		this.userCount += 1;
		this.#toggleAddButton();
	}

	#toggleAddButton(): void
	{
		if (this.userCount === 0)
		{
			Dom.addClass(this.footerAddButton, disabledButtonClass);

			return;
		}

		Dom.removeClass(this.footerAddButton, disabledButtonClass);
	}

	#setConfirmButtonText(): void
	{
		if (this.type === 'move')
		{
			this.confirmButtonText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_CONFIRM_BUTTON');

			return;
		}

		if (this.type === 'add')
		{
			this.confirmButtonText = Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_CONFIRM_BUTTON');

			return;
		}

		this.confirmButtonText = '';
	}

	async action(userIds: number[]): void
	{
		if (!this.userCount || this.isInProcess)
		{
			return;
		}

		this.isInProcess = true;
		const departmentUserIds = this.type === 'move' ? { [memberRoles.employee]: userIds } : userIds;

		const data = await this.#saveUsers(departmentUserIds).catch(() => {});
		if (!data)
		{
			this.destroyDialog();

			return;
		}

		if (this.type === 'add')
		{
			UserManagementDialogActions.addUsersToDepartment(
				this.nodeId,
				this.users,
				data.userCount ?? 0,
				this.role ?? memberRoles.employee,
			);
		}

		if (this.type === 'move')
		{
			UserManagementDialogActions.moveUsersToDepartment(
				this.nodeId,
				this.users,
				data.userCount ?? 0,
				data.updatedDepartmentIds ?? [],
			);
		}

		const notificationCode = this.getNotificationMessageCode();
		if (notificationCode)
		{
			this.showNotification(notificationCode);
		}
		this.destroyDialog();
	}

	#saveUsers(departmentUserIds: Array): Promise<void>
	{
		if (this.type === 'move')
		{
			return UserManagementDialogAPI.moveUsersToDepartment(this.nodeId, departmentUserIds);
		}

		return UserManagementDialogAPI.addUsersToDepartment(this.nodeId, departmentUserIds, this.role);
	}

	showNotification(messageCode: string): void
	{
		const departmentName = UserManagementDialogActions.getDepartmentName(this.nodeId);

		UI.Notification.Center.notify({
			content: Text.encode(Loc.getMessage(
				messageCode,
				{
					'#DEPARTMENT#': departmentName,
				},
			)),
			autoHideDelay: 2000,
		});
	}

	getNotificationMessageCode(): ?string
	{
		if (this.type === 'add')
		{
			if (this.users.length > 1)
			{
				this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_ADD_EMPLOYEES_MESSAGE');
			}

			if (this.users.length === 1)
			{
				this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_ADD_EMPLOYEE_MESSAGE');
			}
		}

		if (this.type === 'move')
		{
			if (this.users.length > 1)
			{
				this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_MOVE_EMPLOYEES_MESSAGE');
			}

			if (this.users.length === 1)
			{
				this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_MOVE_EMPLOYEE_MESSAGE');
			}
		}

		return null;
	}
}
