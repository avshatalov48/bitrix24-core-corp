/* eslint-disable no-param-reassign */
import { createStore } from 'ui.vue3.vuex';
import type { Group, GroupForSave, Dashboard, Scope, PermissionAppState, User } from './type';
import type { Group as PopupGroup } from 'biconnector.dashboard-group';
import { EventEmitter } from 'main.core.events';
import { Runtime } from 'main.core';

export class Store
{
	static initialGroups: Group[];
	static initialDashboards: Map<number, Dashboard>;

	// eslint-disable-next-line max-lines-per-function
	static buildStore(defaultValues: PermissionAppState)
	{
		Store.initialGroups = Runtime.clone(defaultValues.groups);
		Store.initialDashboards = Runtime.clone(defaultValues.dashboards);

		return createStore({
			state(): PermissionAppState
			{
				return defaultValues;
			},
			mutations: {
				addGroup(state: PermissionAppState, group: Group): void
				{
					state.groups.push(group);
				},
				updateGroup(
					state: PermissionAppState,
					data: { group: PopupGroup, dashboards: Map<number, Dashboard> },
				): void
				{
					const updatedGroup = data.group;
					const updatedDashboards = data.dashboards;
					const groupIndex = state.groups.findIndex((item: Group) => item.id === updatedGroup.id);
					if (groupIndex < 0)
					{
						return;
					}

					const group: Group = state.groups[groupIndex];

					group.name = updatedGroup.name;
					group.scopes = updatedGroup.scopes;
					group.dashboardIds = updatedGroup.dashboardIds;
					state.dashboards = updatedDashboards;

					Store.checkModifiedGroups(state.groups, state.dashboards, state.appGuid);
				},
				updateGroupId(
					state: PermissionAppState,
					data: { oldId: string, newId: string },
				): void
				{
					const group: Group = state.groups.find((item: Group) => item.id === data.oldId);
					if (group)
					{
						group.id = data.newId;
					}
				},
				deleteGroup(state: PermissionAppState, groupId: string): void
				{
					state.groups = state.groups.filter((group: Group) => group.id !== groupId);
				},
				resetState(state: PermissionAppState): void
				{
					state.groups = Store.initialGroups;
					state.dashboards = Store.initialDashboards;
				},
				setStateAsInitial(state: PermissionAppState): void
				{
					Store.initialGroups = state.groups;
					Store.initialDashboards = state.dashboards;
				},
			},
			getters: {
				user(state: PermissionAppState): User
				{
					return state.user;
				},
				newGroupPermissions(state: PermissionAppState): Object
				{
					return state.newGroupPermissions;
				},
				groups(state: PermissionAppState): Group[]
				{
					return state.groups;
				},
				group(state: PermissionAppState): (number) => ?Group
				{
					return (groupId) => {
						const group: Group = Runtime.clone(state.groups.find((item: Group) => item.id === groupId));
						if (!group)
						{
							return null;
						}

						return group;
					};
				},
				groupForSave(state: PermissionAppState): (number) => ?GroupForSave
				{
					return (groupId) => {
						const group = Runtime.clone(state.groups.find((item: Group) => item.id === groupId));
						if (!group)
						{
							return null;
						}
						group.dashboards = [];
						for (const dashboardId: number of group.dashboardIds)
						{
							group.dashboards.push(state.dashboards.get(dashboardId));
						}
						delete group.dashboardIds;

						return group;
					};
				},
				dashboards(state: PermissionAppState): Map<number, Dashboard>
				{
					return state.dashboards;
				},
				dashboardScopes(state: PermissionAppState): (number) => Scope[]
				{
					return (dashboardId): Scope[] => state.dashboards.get(dashboardId)?.scopes ?? null;
				},
				appGuid(state: PermissionAppState): string
				{
					return state.appGuid;
				},
			},
		});
	}

	static checkModifiedGroups(currentGroups: Group[], currentDashboards: Dashboard[], appGuid: string)
	{
		const modifiedGroupIds = new Set();
		for (const currentGroup: Group of currentGroups)
		{
			const initialGroup = Store.initialGroups.find((item: Group) => item.id === currentGroup.id);
			if (!initialGroup)
			{
				modifiedGroupIds.add(currentGroup.id);

				continue;
			}

			if (currentGroup.name !== initialGroup.name)
			{
				modifiedGroupIds.add(currentGroup.id);

				continue;
			}

			if (initialGroup.dashboardIds.length !== currentGroup.dashboardIds.length)
			{
				modifiedGroupIds.add(currentGroup.id);

				continue;
			}

			if (!Store.areSetsEqual(
				new Set(initialGroup.scopes.map((scope: Scope) => scope.code)),
				new Set(currentGroup.scopes.map((scope: Scope) => scope.code)),
			))
			{
				modifiedGroupIds.add(currentGroup.id);
			}
		}

		const modifiedDashboardIds = new Set();
		for (const currentDashboard: Dashboard of currentDashboards.values())
		{
			const initialDashboard = Store.initialDashboards.get(currentDashboard.id);
			if (!initialDashboard)
			{
				modifiedDashboardIds.add(currentDashboard.id);

				continue;
			}

			if (!Store.areSetsEqual(
				new Set(initialDashboard.scopes.map((scope: Scope) => scope.code)),
				new Set(currentDashboard.scopes.map((scope: Scope) => scope.code)),
			))
			{
				modifiedDashboardIds.add(currentDashboard.id);
			}
		}

		for (const group: Group of currentGroups)
		{
			let hasModifiedDashboards = false;
			for (const dashboardId: number of group.dashboardIds)
			{
				hasModifiedDashboards = hasModifiedDashboards || modifiedDashboardIds.has(dashboardId);
			}

			EventEmitter.emit('BX.UI.AccessRights.V2:markRightAsModified', {
				guid: appGuid,
				sectionCode: 'SECTION_RIGHTS_GROUP',
				rightId: group.id,
				isModified: modifiedGroupIds.has(group.id) || hasModifiedDashboards,
			});
		}
	}

	static areSetsEqual(setA: Set, setB: Set): boolean
	{
		if (setA.size !== setB.size)
		{
			return false;
		}

		for (const item of setA)
		{
			if (!setB.has(item))
			{
				return false;
			}
		}

		return true;
	}
}
