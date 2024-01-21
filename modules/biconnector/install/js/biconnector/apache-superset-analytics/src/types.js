export type DashboardAnalyticInfo = {
	id: number,
	appId: string,
	type: 'system' | 'custom' | 'market',
	from?: string
};
