import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { Type } from 'main.core';
import { AddEmployeeMenuItem } from './items/employees/add-employee-menu-item';
import { EditEmployeeMenuItem } from './items/employees/edit-employee-menu-item';

export class UsersTabActionMenu
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
			// ToDo: pass proper entity type
			new AddEmployeeMenuItem(null, this.role),
			new EditEmployeeMenuItem(null, this.role),
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
