export type SkeletonConfig = {
	container: HTMLElement,
	dashboardId: number,
	status: string,
	dashboardType: 'SYSTEM' | 'MARKET' | 'CUSTOM',
	isFirstStartup: boolean,
	periodicReload: boolean,
	supersetStatus: 'LOAD' | 'READY' | 'ERROR',
}
