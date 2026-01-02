import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { Type } from 'main.core';

export class AbstractActionMenu
{
	permissionChecker: PermissionCheckerClass;
	entityId: number;
	analyticSource: string;
	entityType: string;

	constructor(entityId: number)
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.entityId = entityId;
	}

	getItems(): Array
	{
		throw new Error('Must override this function');
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
