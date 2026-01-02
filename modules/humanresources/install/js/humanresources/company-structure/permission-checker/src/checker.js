/* eslint-disable no-constructor-return */
import { chartAPI } from '../../org-chart/src/api';
import { TeamPermissionChecker } from './entity-permission-checkers/team-checker';
import { DepartmentPermissionChecker } from './entity-permission-checkers/department-checker';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { PermissionLevels, PermissionActions } from './consts';

export class PermissionCheckerClass
{
	constructor(): PermissionCheckerClass
	{
		if (!PermissionCheckerClass.instance)
		{
			this.currentUserPermissions = {};
			this.isTeamsAvailable = false;
			this.isCollabsAvailable = false;
			this.isDeputyApprovesBPAvailable = false;
			this.isInitialized = false;
			this.departmentChecker = new DepartmentPermissionChecker();
			this.teamChecker = new TeamPermissionChecker(this.departmentChecker, this.currentUserPermissions);
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

		const {
			currentUserPermissions,
			teamsAvailable,
			collabsAvailable,
			deputyApprovesBP,
			departmentSettingsAvailable,
		} = await chartAPI.getDictionary();

		this.currentUserPermissions = currentUserPermissions;
		this.isTeamsAvailable = teamsAvailable;
		this.isCollabsAvailable = collabsAvailable;
		this.isDeputyApprovesBPAvailable = deputyApprovesBP;
		this.isDepartmentSettingsAvailable = departmentSettingsAvailable;

		this.isInitialized = true;
	}

	hasPermission(action: string, entityId: number, minLevel: any = null): boolean
	{
		const permissionLevel = this.currentUserPermissions[action];

		if (!permissionLevel || !entityId)
		{
			return false;
		}

		if (this.departmentChecker.isCheckerAction(action))
		{
			return this.departmentChecker.hasPermission(
				action,
				entityId,
				permissionLevel,
				minLevel,
			);
		}

		if (
			this.isTeamsAvailable
			&& this.teamChecker.isCheckerAction(action)
		)
		{
			return this.teamChecker.hasPermission(
				action,
				entityId,
				permissionLevel,
				minLevel,
			);
		}

		return false;
	}

	checkDeputyApprovalBPAvailable(): boolean
	{
		return this.isDeputyApprovesBPAvailable;
	}

	checkDepartmentSettingsAvailable(): boolean
	{
		return this.isDepartmentSettingsAvailable;
	}

	hasPermissionOfAction(action: string): boolean
	{
		const permissionLevel = this.currentUserPermissions[action];

		if (!permissionLevel)
		{
			return false;
		}

		if (this.teamChecker.isCheckerAction(action))
		{
			if (!this.isTeamsAvailable)
			{
				return false;
			}

			return permissionLevel.TEAM > PermissionLevels.none || permissionLevel.DEPARTMENT > PermissionLevels.none;
		}

		return permissionLevel > PermissionLevels.none;
	}

	canSortEntitiesByParentId(entityId: number): boolean
	{
		const entities = useChartStore().departments;
		const entity = entities.get(entityId);
		if (!entity)
		{
			return false;
		}

		const teamTeamMinValue = { TEAM: PermissionLevels.selfAndSub };
		const teamDepartmentMinValue = { DEPARTMENT: PermissionLevels.selfAndSub };

		const teamAction = PermissionActions.teamEdit;
		if (entity.entityType === EntityTypes.team)
		{
			return this.hasPermission(teamAction, entityId, teamTeamMinValue)
				|| this.hasPermission(teamAction, entityId, teamDepartmentMinValue)
			;
		}

		if (entity.entityType === EntityTypes.department)
		{
			const departmentAction = PermissionActions.departmentEdit;

			return this.hasPermission(departmentAction, entityId, PermissionLevels.selfAndSub);
		}

		return false;
	}

	canBeParentForEntity(entityId: number, type: EntityTypes): boolean
	{
		if (type === EntityTypes.department)
		{
			return this.hasPermission(PermissionActions.departmentEdit, entityId);
		}

		if (type === EntityTypes.team)
		{
			return this.hasPermission(PermissionActions.teamEdit, entityId);
		}

		return false;
	}
}

export const PermissionChecker = new PermissionCheckerClass();
