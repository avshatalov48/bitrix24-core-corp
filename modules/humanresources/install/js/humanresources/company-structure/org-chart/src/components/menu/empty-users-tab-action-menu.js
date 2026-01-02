import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { Type } from 'main.core';
import { AddEmployeeMenuItem } from './items/employees/add-employee-menu-item';
import { MoveEmployeeMenuItem } from './items/employees/move-employee-menu-item';
import { UserInviteMenuItem } from './items/employees/user-invite-menu-item';

export class EmptyUsersTabActionMenu
{
	constructor(entityId: number, analyticSource: string, role: string)
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.entityId = entityId;
		this.analyticSource = analyticSource;
		this.role = role;
		this.items = this.getFilteredItems();
	}

	getFilteredItems(): Array
	{
		if (!this.permissionChecker)
		{
			return [];
		}

		const items = this.getItems();

		return items.filter((item) => {
			if (Type.isFunction(item.hasPermission))
			{
				return item.hasPermission(this.permissionChecker, this.entityId);
			}

			return false;
		});
	}

	getItems(): Array
	{
		return [
			new MoveEmployeeMenuItem(),
			new UserInviteMenuItem(),
			new AddEmployeeMenuItem(EntityTypes.department, this.role), // ToDo: pass proper entity type
		];
	}

	onActionMenuItemClick(actionId: string): void
	{
		const targetItem = this.items.find((item) => item.id === actionId);
		targetItem?.invoke({
			entityId: this.entityId,
			analyticSource: this.analyticSource,
		});
	}
}
