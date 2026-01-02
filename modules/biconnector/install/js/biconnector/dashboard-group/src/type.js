export type User = {
	id: number,
	isAdmin: boolean,
	hasAccessToPermission: boolean,
	accessibleGroupIds: string[],
};

export type GroupAppParams = {
	groupId: string,
	groups: Group[],
	dashboards: Map<number, Dashboard>,
	saveEnabled: boolean,
	user: User,
};

export type GroupAppState = {
	group: Group,
	otherGroups: Group[],
	dashboards: Map<number, Dashboard>,
	saveEnabled: boolean,
	isLoading: boolean,
	user: User,
};

export const GroupType = {
	system: 'SYSTEM',
	custom: 'CUSTOM',
};

export const DashboardType = {
	system: 'SYSTEM',
	market: 'MARKET',
	custom: 'CUSTOM',
};

export type Group = {
	id: string,
	name: string,
	type: GroupType.system | GroupType.custom,
	scopes: Scope[],
	dashboardIds: number[],
}

export type Dashboard = {
	id: number,
	name: string,
	type: DashboardType.system | DashboardType.market | DashboardType.custom,
	scopes: Scope[],
	createdById: number,
	ownerId: number,
};

export type Scope = {
	code: string,
	name: string,
};
