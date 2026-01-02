import { AbstractActionMenu } from '../abstract-action-menu';
import { AddEmployeeMenuItem } from '../items/employees/add-employee-menu-item';
import { EditEmployeesMenuItem } from '../items/department/edit-employees-menu-item';

export class UsersTabActionMenu extends AbstractActionMenu
{
	constructor(entityId: number, analyticSource: string, role: string, entityType: string)
	{
		super(entityId);
		this.analyticSource = analyticSource;
		this.role = role;
		this.entityType = entityType;
		this.items = this.getFilteredItems();
	}

	getItems(): Array
	{
		return [
			new AddEmployeeMenuItem(this.entityType, this.role),
			new EditEmployeesMenuItem(this.entityType, this.role),
		];
	}
}
