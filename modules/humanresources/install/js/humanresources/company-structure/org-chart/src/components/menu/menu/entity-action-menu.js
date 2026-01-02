import { EntityTypes } from 'humanresources.company-structure.utils';
import { AbstractActionMenu } from '../abstract-action-menu';
import { AddDepartmentMenuItem } from '../items/department/add-department-menu-item';
import { EditDepartmentMenuItem } from '../items/department/edit-department-menu-item';
import { RemoveDepartmentMenuItem } from '../items/department/remove-department-menu-item';
import { TeamRightsMenuItem } from '../items/department/team-rights-menu-item';
import { AddEmployeeMenuItem } from '../items/employees/add-employee-menu-item';
import { EditEmployeesMenuItem } from '../items/department/edit-employees-menu-item';
import { MoveEmployeeMenuItem } from '../items/employees/move-employee-menu-item';
import { UserInviteMenuItem } from '../items/employees/user-invite-menu-item';

export class EntityActionMenu extends AbstractActionMenu
{
	constructor(entityId: number, entityType: string, analyticSource: string)
	{
		super(entityId);
		this.entityType = entityType;
		this.analyticSource = analyticSource;
		this.items = this.getFilteredItems();
	}

	getItems(): Array
	{
		if (this.entityType === EntityTypes.team)
		{
			return [
				new EditDepartmentMenuItem(this.entityType),
				new AddDepartmentMenuItem(this.entityType),
				new AddEmployeeMenuItem(this.entityType),
				new RemoveDepartmentMenuItem(this.entityType),
			];
		}

		return [
			new EditDepartmentMenuItem(this.entityType),
			new AddDepartmentMenuItem(this.entityType),
			new AddEmployeeMenuItem(this.entityType),
			new MoveEmployeeMenuItem(),
			new UserInviteMenuItem(),
			new RemoveDepartmentMenuItem(this.entityType),
		];
	}
}
