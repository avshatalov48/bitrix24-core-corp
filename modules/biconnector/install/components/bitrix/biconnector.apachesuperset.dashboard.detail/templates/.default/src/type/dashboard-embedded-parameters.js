import type { SourceDashboardInfo } from 'biconnector.apache-superset-dashboard-manager';

export type DashboardEmbeddedParameters = {
	uuid: string,
	id: number,
	guestToken: string,
	supersetDomain: string,
	nativeFilters: string,

	editUrl: string,
	type: string,
	appId: string,

	sourceDashboard: SourceDashboardInfo
}
