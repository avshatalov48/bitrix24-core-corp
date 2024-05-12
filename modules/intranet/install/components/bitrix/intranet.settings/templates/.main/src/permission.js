export class Permission
{
	static READ = 1 << 0;
	static EDIT = 1 << 2;

	#permission: number

	constructor(permission: number = 0)
	{
		this.#permission = permission
	}

	canRead(): boolean
	{
		return !!(this.#permission & Permission.READ);
	}

	canEdit(): boolean
	{
		return !!(this.#permission & Permission.EDIT);
	}

	getPermission(): number
	{
		return this.#permission;
	}
}