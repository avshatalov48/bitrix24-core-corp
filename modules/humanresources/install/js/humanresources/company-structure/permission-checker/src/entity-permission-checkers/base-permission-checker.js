export class BasePermissionChecker
{
	isCheckerAction(action): boolean
	{
		throw new Error('The method isCheckerAction must be implemented in the subclass');
	}

	hasPermission(action: string, nodeId: number, permissionValue: any, minLevel: ?any): boolean
	{
		throw new Error('The method hasPermission must be implemented in the subclass');
	}
}
