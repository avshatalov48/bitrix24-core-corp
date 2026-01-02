/* eslint-disable no-param-reassign */
import { Runtime } from 'main.core';
import { createStore } from 'ui.vue3.vuex';
import { type Group, type Dashboard, type Scope, GroupType, GroupAppState } from './type';

export class Store
{
	static initialGroup: Group;

	// eslint-disable-next-line max-lines-per-function
	static buildStore(defaultValues: GroupAppState)
	{
		Store.initialGroup = Runtime.clone(defaultValues.group);

		return createStore({
			state(): GroupAppState
			{
				return defaultValues;
			},
			mutations: {
				setIsLoading(state: GroupAppState, isLoading: boolean)
				{
					state.isLoading = isLoading;
				},
				setGroupName(state: GroupAppState, groupName: string)
				{
					state.group.name = groupName;
				},
				addDashboard(state: GroupAppState, dashboard: Dashboard)
				{
					state.group.dashboardIds.unshift(dashboard.id);

					const groupScopes = state.group.scopes;
					if (state.dashboards.get(dashboard.id))
					{
						dashboard.scopes = state.dashboards.get(dashboard.id).scopes;
					}

					const dashboardScopeCodes = new Set(dashboard.scopes.map((dashScope: Scope) => dashScope.code));
					for (const groupScope: Scope of groupScopes)
					{
						if (!dashboardScopeCodes.has(groupScope.code))
						{
							dashboard.scopes.push(groupScope);
						}
					}

					state.dashboards.set(dashboard.id, dashboard);
				},
				removeDashboard(state: GroupAppState, dashboardId: number)
				{
					const dashboard: Dashboard = state.dashboards.get(dashboardId);
					if (!dashboard)
					{
						return;
					}

					state.group.dashboardIds = state.group.dashboardIds.filter((id) => id !== dashboardId);
					for (const groupScope: Scope of state.group.scopes)
					{
						let dashboardIsInOtherGroupWithScope = false;
						for (const otherGroup: Group of state.otherGroups)
						{
							if (
								otherGroup.dashboardIds.includes(dashboardId)
								&& otherGroup.scopes.some((item: Scope) => item.code === groupScope.code)
							)
							{
								dashboardIsInOtherGroupWithScope = true;
							}
						}

						if (
							!dashboardIsInOtherGroupWithScope
							&& dashboard.scopes.some((item: Scope) => item.code === groupScope.code)
						)
						{
							dashboard.scopes = dashboard.scopes.filter((item: Scope) => item.code !== groupScope.code);
							state.dashboards.set(dashboardId, dashboard);
						}
					}
				},
				setDashboardScopes(state: GroupAppState, data: {dashboardId: number, scopes: Scope[]})
				{
					const { dashboardId, scopes } = data;
					const dashboard: Dashboard = state.dashboards.get(dashboardId);
					dashboard.scopes = scopes;
				},
				addGroupScope(state: GroupAppState, scope: Scope)
				{
					state.group.scopes.push(scope);
					const newScopeCode = scope.code;
					for (const dashboardId: number of state.group.dashboardIds)
					{
						const dashboard = state.dashboards.get(dashboardId);
						if (!dashboard.scopes.some((item: Scope) => item.code === newScopeCode))
						{
							dashboard.scopes.push(scope);
							state.dashboards.set(dashboardId, dashboard);
						}
					}
				},
				removeGroupScope(state: GroupAppState, scope: Scope)
				{
					state.group.scopes = state.group.scopes.filter((existingScope: Scope) => existingScope.code !== scope.code);
					const deletedScopeCode = scope.code;
					for (const dashboardId: number of state.group.dashboardIds)
					{
						let dashboardIsInOtherGroupWithScope = false;
						for (const otherGroup: Group of state.otherGroups)
						{
							if (
								otherGroup.dashboardIds.includes(dashboardId)
								&& otherGroup.scopes.some((item: Scope) => item.code === deletedScopeCode)
							)
							{
								dashboardIsInOtherGroupWithScope = true;
							}
						}

						if (!dashboardIsInOtherGroupWithScope)
						{
							const dashboard = state.dashboards.get(dashboardId);
							if (dashboard.scopes.some((item: Scope) => item.code === deletedScopeCode))
							{
								dashboard.scopes = dashboard.scopes.filter((item: Scope) => item.code !== deletedScopeCode);
								state.dashboards.set(dashboardId, dashboard);
							}
						}
					}
				},
			},
			getters: {
				user(state: GroupAppState): User
				{
					return state.user;
				},
				groupName(state: GroupAppState): string
				{
					return state.group.name;
				},
				groupScopes(state: GroupAppState): Scope[]
				{
					return state.group.scopes;
				},
				dashboards(state: GroupAppState): Map<number, Dashboard>
				{
					return state.dashboards;
				},
				otherGroups(state: GroupAppState): Group[]
				{
					return state.otherGroups;
				},
				groupDashboardsData(state: GroupAppState): Dashboard[]
				{
					const result = [];
					for (const dashboardId of state.group.dashboardIds)
					{
						result.push(state.dashboards.get(dashboardId));
					}

					return result;
				},
				isSystemGroup(state: GroupAppState): boolean
				{
					return state.group.type === GroupType.system;
				},
				groupData(state: GroupAppState): Group
				{
					return {
						id: state.group.id,
						name: state.group.name,
						type: state.group.type,
						scopes: state.group.scopes,
						dashboardIds: state.group.dashboardIds,
					};
				},
				isSaveEnabled(state: GroupAppState): boolean
				{
					return state.saveEnabled;
				},
				isLoading(state: GroupAppState): boolean
				{
					return state.isLoading;
				},
				isGroupScope(state: GroupAppState): (dashboardId: number, scopeCode: string) => boolean
				{
					return (dashboardId: number, scopeCode: string) => {
						if (state.group.scopes.some((groupScope: Scope) => groupScope.code === scopeCode))
						{
							return true;
						}

						for (const otherGroup of state.otherGroups)
						{
							if (
								otherGroup.dashboardIds.includes(dashboardId)
								&& otherGroup.scopes.some((groupScope: Scope) => groupScope.code === scopeCode)
							)
							{
								return true;
							}
						}

						return false;
					};
				},
				accessibleDashboardIdsSet(state: GroupAppState): Set
				{
					return new Set(
						state.otherGroups
							.filter((group) => state.user.accessibleGroupIds.includes(group.id))
							.flatMap((group) => group.dashboardIds),
					);
				},
				shouldShowDeleteWarning(state: GroupAppState, getters): (dashboardId: number) => boolean
				{
					return (dashboardId: number) => {
						const user = state.user;

						if (user.isAdmin || user.hasAccessToPermission)
						{
							return false;
						}

						const dashboard: Dashboard = state.dashboards.get(dashboardId);

						if (!dashboard)
						{
							return false;
						}

						if (
							dashboard.createdById === user.id
							|| dashboard.ownerId === user.id
						)
						{
							return false;
						}

						return !getters.accessibleDashboardIdsSet.has(dashboardId);
					};
				},
				isTitleEdited(state: GroupAppState): boolean
				{
					return state.group.name !== Store.initialGroup.name;
				},
				isDashboardListEdited(state: GroupAppState): boolean
				{
					return !Store.areSetsEqual(
						new Set(Store.initialGroup.dashboardIds),
						new Set(state.group.dashboardIds),
					);
				},
				isScopeListEdited(state: GroupAppState): boolean
				{
					return !Store.areSetsEqual(
						new Set(Store.initialGroup.scopes.map((scope: Scope) => scope.code)),
						new Set(state.group.scopes.map((scope: Scope) => scope.code)),
					);
				},
			},
		});
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
