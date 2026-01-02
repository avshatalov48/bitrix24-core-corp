import { useChartStore } from 'humanresources.company-structure.chart-store';
import { BasePermissionChecker } from './base-permission-checker';
import { PermissionActions, PermissionLevels } from '../consts';
import { Type } from 'main.core';

export class DepartmentPermissionChecker extends BasePermissionChecker
{
	isCheckerAction(action: string): boolean
	{
		const departmentActions = [
			PermissionActions.structureView,
			PermissionActions.departmentCreate,
			PermissionActions.departmentDelete,
			PermissionActions.departmentEdit,
			PermissionActions.employeeAddToDepartment,
			PermissionActions.employeeRemoveFromDepartment,
			PermissionActions.employeeFire,
			PermissionActions.departmentSettingsEdit,
			PermissionActions.departmentChatEdit,
			PermissionActions.departmentChannelEdit,
			PermissionActions.departmentCollabEdit,
			PermissionActions.inviteToDepartment,
		];

		return departmentActions.includes(action);
	}

	hasPermission(action: string, departmentId: number, permissionValue: number, minLevel?: number): boolean
	{
		if (
			!this.isCheckerAction(action)
			&& !Type.isNumber(permissionValue)
		)
		{
			return false;
		}

		if (
			minLevel !== null
			&& permissionValue < minLevel
		)
		{
			return false;
		}

		const departments = useChartStore().structureMap;
		if (action === PermissionActions.departmentDelete)
		{
			const rootId = [...departments.values()].find((department) => department.parentId === 0).id;
			if (departmentId === rootId)
			{
				return false;
			}
		}

		const userDepartments = useChartStore().currentDepartments;
		switch (permissionValue)
		{
			case PermissionLevels.fullCompany:
			{
				return true;
			}

			case PermissionLevels.selfAndSub:
			{
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

			case PermissionLevels.self:
			{
				return userDepartments.includes(departmentId);
			}

			case PermissionLevels.none:
			default:
			{
				return false;
			}
		}
	}
}
