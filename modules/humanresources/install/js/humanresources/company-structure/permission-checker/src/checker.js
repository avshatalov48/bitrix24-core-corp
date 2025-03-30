/* eslint-disable no-constructor-return */
import { chartAPI } from '../../org-chart/src/api';
import { useChartStore } from 'humanresources.company-structure.chart-store';

export const PermissionActions = Object.freeze({
	structureView: 'ACTION_STRUCTURE_VIEW',
	chanelBindToStructure: 'ACTION_CHANEL_BIND_TO_STRUCTURE',
	chanelUnbindToStructure: 'ACTION_CHANEL_UNBIND_TO_STRUCTURE',
	chatBindToStructure: 'ACTION_CHAT_BIND_TO_STRUCTURE',
	chatUnbindToStructure: 'ACTION_CHAT_UNBIND_TO_STRUCTURE',
	departmentCreate: 'ACTION_DEPARTMENT_CREATE',
	departmentDelete: 'ACTION_DEPARTMENT_DELETE',
	departmentEdit: 'ACTION_DEPARTMENT_EDIT',
	employeeAddToDepartment: 'ACTION_EMPLOYEE_ADD_TO_DEPARTMENT',
	employeeRemoveFromDepartment: 'ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT',
	accessEdit: 'ACTION_USERS_ACCESS_EDIT',
	inviteToDepartment: 'ACTION_USER_INVITE',
});

class PermissionCheckerClass
{
	static FULL_COMPANY = 30;
	static SELF_AND_SUB = 20;
	static SELF = 10;
	static NONE = 0;

	constructor(): PermissionCheckerClass
	{
		if (!PermissionCheckerClass.instance)
		{
			this.currentUserPermissions = {};
			this.permissionVariablesDictionary = [];
			this.isInitialized = false;
			PermissionCheckerClass.instance = this;
		}

		return PermissionCheckerClass.instance;
	}

	getInstance(): PermissionCheckerClass
	{
		return PermissionCheckerClass.instance;
	}

	async init(): Promise<void>
	{
		if (this.isInitialized)
		{
			return;
		}

		const { currentUserPermissions, permissionVariablesDictionary } = await chartAPI.getDictionary();
		this.currentUserPermissions = currentUserPermissions;

		this.permissionVariablesDictionary = permissionVariablesDictionary;

		this.isInitialized = true;
	}

	hasPermission(action: string, departmentId: number): boolean
	{
		const permissionLevel = this.currentUserPermissions[action];

		if (!permissionLevel)
		{
			return false;
		}

		const permissionObject = this.permissionVariablesDictionary.find((item) => item.id === permissionLevel);

		if (!permissionObject)
		{
			return false;
		}

		const departments = useChartStore().departments;
		if (action === PermissionActions.departmentDelete)
		{
			const rootId = [...departments.values()].find((department) => department.parentId === 0).id;
			if (departmentId === rootId)
			{
				return false;
			}
		}

		const userDepartments = useChartStore().currentDepartments;
		switch (permissionObject.id)
		{
			case PermissionCheckerClass.FULL_COMPANY:
				return true;

			case PermissionCheckerClass.SELF_AND_SUB:
			{
				if (userDepartments.includes(departmentId))
				{
					return true;
				}

				let currentDepartment = departments.get(departmentId);

				while (currentDepartment)
				{
					if (userDepartments.includes(currentDepartment.id))
					{
						return true;
					}

					currentDepartment = departments.get(currentDepartment.parentId);
				}

				return false;
			}
			case PermissionCheckerClass.SELF:
				return userDepartments.includes(departmentId);

			case PermissionCheckerClass.NONE:
			default:
				return false;
		}
	}

	hasPermissionOfAction(action: string): boolean
	{
		return this.currentUserPermissions[action] !== undefined
			&& this.currentUserPermissions[action] !== null
			&& this.currentUserPermissions[action] !== PermissionCheckerClass.NONE
		;
	}
}

export const PermissionChecker = new PermissionCheckerClass();
