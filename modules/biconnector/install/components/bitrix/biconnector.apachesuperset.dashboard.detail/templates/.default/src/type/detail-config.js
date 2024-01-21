import { DashboardEmbeddedParameters } from './dashboard-embedded-parameters';

export type DetailConfig = {
	dashboardEmbeddedParams: DashboardEmbeddedParameters,
	appNodeId: string,
	openLoginPopup: boolean,
	isExportEnabled: 'Y' | 'N',
}
