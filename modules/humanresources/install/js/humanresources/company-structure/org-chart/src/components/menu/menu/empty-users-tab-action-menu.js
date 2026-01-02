import { EntityTypes } from 'humanresources.company-structure.utils';
import { AbstractActionMenu } from '../abstract-action-menu';
import { AddEmployeeMenuItem } from '../items/employees/add-employee-menu-item';
import { MoveEmployeeMenuItem } from '../items/employees/move-employee-menu-item';
import { UserInviteMenuItem } from '../items/employees/user-invite-menu-item';

export class EmptyUsersTabActionMenu extends AbstractActionMenu
{
	constructor(entityId: number, analyticSource: string, role: string, entityType: string)
	{
		super(entityId);
		this.entityType = entityType;
		this.analyticSource = analyticSource;
		this.role = role;
		this.items = this.getFilteredItems();
	}

	getItems(): Array
	{
		if (this.entityType === EntityTypes.team)
		{
			return [
				new AddEmployeeMenuItem(this.entityType, this.role),
			];
		}

		return [
			new MoveEmployeeMenuItem(),
			new UserInviteMenuItem(),
			new AddEmployeeMenuItem(this.entityType, this.role),
		];
	}
}
