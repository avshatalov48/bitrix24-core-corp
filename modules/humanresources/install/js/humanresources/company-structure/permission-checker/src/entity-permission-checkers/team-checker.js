import { useChartStore } from 'humanresources.company-structure.chart-store';
import { BasePermissionChecker } from './base-permission-checker';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { PermissionActions, PermissionLevels } from '../consts';
import { EntityTypes } from 'humanresources.company-structure.utils';

export class TeamPermissionChecker extends BasePermissionChecker
{
	constructor(departmentChecker, currentUserPermissions)
	{
		super();
		this.departmentChecker = departmentChecker;
		this.currentUserPermissions = currentUserPermissions;
	}

	isCheckerAction(action: string): boolean
	{
		const teamActions = [
			PermissionActions.teamView,
			PermissionActions.teamCreate,
			PermissionActions.teamEdit,
			PermissionActions.teamDelete,
			PermissionActions.teamAddMember,
			PermissionActions.teamRemoveMember,
			PermissionActions.teamSettingsEdit,
			PermissionActions.teamChatEdit,
			PermissionActions.teamChannelEdit,
			PermissionActions.teamCollabEdit,
		];

		return teamActions.includes(action);
	}

	hasPermission(
		action: string,
		entityId: number,
		permissionValue: {TEAM: number, DEPARTMENT: number},
		minValue?: { TEAM?: number, DEPARTMENT?: number } | null,
	): boolean
	{
		if (minValue)
		{
			const isTeamPermissionInsufficient = permissionValue.TEAM < (minValue?.TEAM || 0);
			const isDepartmentPermissionInsufficient = permissionValue.DEPARTMENT < (minValue?.DEPARTMENT || 0);

			if (isTeamPermissionInsufficient || isDepartmentPermissionInsufficient)
			{
				return false;
			}
		}

		if (permissionValue.TEAM === PermissionLevels.fullCompany)
		{
			const entities = useChartStore().structureMap;
			const currentNode = entities.get(entityId);

			if (
				(action === PermissionActions.teamCreate || action === PermissionActions.teamEdit)
				&& currentNode.entityType === EntityTypes.department
			)
			{
				return PermissionChecker.hasPermission(PermissionActions.structureView, currentNode.id);
			}

			return true;
		}

		if (this.hasPermissionByTeamPermission(entityId, permissionValue.TEAM))
		{
			return true;
		}

		return this.hasPermissionByDepartmentPermission(entityId, permissionValue.DEPARTMENT, action);
	}

	hasPermissionByTeamPermission(nodeId: number, level: number = PermissionLevels.none): boolean
	{
		if (level === PermissionLevels.none)
		{
			return false;
		}

		const entities = useChartStore().structureMap;
		const userEntities = useChartStore().currentDepartments;
		const userTeams = new Set(
			userEntities.filter((id) => {
				const department = entities.get(id);

				return department && department.entityType === EntityTypes.team;
			}),
		);

		if (userTeams.has(nodeId))
		{
			return true;
		}

		if (level === PermissionLevels.self)
		{
			return false;
		}

		let currentDepartment = entities.get(nodeId);
		while (currentDepartment)
		{
			if (currentDepartment.entityType === EntityTypes.department)
			{
				return false;
			}

			if (userTeams.has(currentDepartment.id))
			{
				return true;
			}

			currentDepartment = entities.get(currentDepartment.parentId);
		}

		return false;
	}

	hasPermissionByDepartmentPermission(nodeId, level = PermissionLevels.none, action = ''): boolean
	{
		if (level === PermissionLevels.none)
		{
			return false;
		}

		const entities = useChartStore().structureMap;
		const userEntities = useChartStore().currentDepartments;
		const userDepartments = new Set(
			userEntities.filter((id) => {
				const department = entities.get(id);

				return department && department.entityType === EntityTypes.department;
			}),
		);

		if (userDepartments.has(nodeId))
		{
			return true;
		}

		let currentDepartment = entities.get(nodeId);
		while (currentDepartment)
		{
			if (userDepartments.has(currentDepartment.id))
			{
				return true;
			}

			if (level === PermissionLevels.self && currentDepartment.entityType === EntityTypes.department)
			{
				if (action === PermissionActions.teamCreate || action === PermissionActions.teamEdit)
				{
					return userDepartments.has(currentDepartment.id);
				}

				return false;
			}

			currentDepartment = entities.get(currentDepartment.parentId);
		}

		return false;
	}
}
