import { EntityTypes } from 'humanresources.company-structure.utils';
import { AbstractActionMenu } from '../abstract-action-menu';
import { FireUserFromCompanyMenuItem } from '../items/user-item/fire-user-from-company';
import { MoveFromDepartmentMenuItem } from '../items/user-item/move-from-department';
import { RemoveFromDepartmentMenuItem } from '../items/user-item/remove-from-department';

export class UserListActionMenu extends AbstractActionMenu
{
	isUserInvited: boolean;

	constructor(entityId: number, entityType: string, isUserInvited: boolean)
	{
		super(entityId);
		this.isUserInvited = isUserInvited;
		this.entityType = entityType;
		this.items = this.getFilteredItems();
	}

	getItems(): Array
	{
		if (this.entityType === EntityTypes.team)
		{
			return [
				new MoveFromDepartmentMenuItem(this.entityType),
				new RemoveFromDepartmentMenuItem(this.entityType),
			];
		}

		return [
			new MoveFromDepartmentMenuItem(this.entityType),
			new RemoveFromDepartmentMenuItem(this.entityType),
			new FireUserFromCompanyMenuItem(this.isUserInvited),
		];
	}
}
