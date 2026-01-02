import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { Type } from 'main.core';
import { AddDepartmentMenuItem } from './items/department/add-department-menu-item';
import { EditDepartmentMenuItem } from './items/department/edit-department-menu-item';
import { RemoveDepartmentMenuItem } from './items/department/remove-department-menu-item';
import { AddEmployeeMenuItem } from './items/employees/add-employee-menu-item';
import { EditEmployeeMenuItem } from './items/employees/edit-employee-menu-item';
import { MoveEmployeeMenuItem } from './items/employees/move-employee-menu-item';
import { UserInviteMenuItem } from './items/employees/user-invite-menu-item';

export class EntityActionMenu
{
	constructor(entityId: number, entityType: string, analyticSource: string)
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.entityId = entityId;
		this.entityType = entityType;
		this.analyticSource = analyticSource;
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
			new EditDepartmentMenuItem(this.entityType),
			new AddDepartmentMenuItem(this.entityType),
			new AddEmployeeMenuItem(this.entityType),
			new EditEmployeeMenuItem(this.entityType, null),
			new MoveEmployeeMenuItem(),
			new UserInviteMenuItem(),
			new RemoveDepartmentMenuItem(this.entityType),
		];
	}

	onActionMenuItemClick(actionId: string): void
	{
		const targetItem = this.items.find((item) => item.id === actionId);
		targetItem?.invoke({
			entityId: this.entityId,
			analyticSource: this.analyticSource,
			entityType: this.entityType,
		});
	}
}
