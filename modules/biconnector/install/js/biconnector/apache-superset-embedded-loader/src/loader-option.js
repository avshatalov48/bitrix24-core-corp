import { UiConfig } from './ui-config';

export type LoaderOption = {
	id: string,
	supersetDomain: string,
	mountPoint: HTMLElement,
	fetchGuestToken: string,
	dashboardUiConfig: UiConfig,
	debug: boolean
}
