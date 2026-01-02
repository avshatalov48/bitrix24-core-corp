import type { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';

type BIcon = {
	name: string,
	size: number,
	color: string,
};

export class AbstractMenuItem
{
	id: string;
	title: string;
	description: string;
	bIcon: BIcon;
	permissionAction: string;
	dataTestId: string;

	constructor({ id, title, description, bIcon, permissionAction, dataTestId })
	{
		this.id = id;
		this.title = title;
		this.description = description;
		this.bIcon = bIcon;
		this.permissionAction = permissionAction;
		this.dataTestId = dataTestId;
	}

	invoke(options: Object): void
	{
		throw new Error('Must override this function');
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		return permissionChecker.hasPermission(this.permissionAction, entityId);
	}
}
