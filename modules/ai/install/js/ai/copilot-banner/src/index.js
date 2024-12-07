import { CopilotBanner, CopilotBannerEvents, type CopilotBannerOptions as cbo } from './copilot-banner';
import { AppsInstallerBanner, AppsInstallerBannerEvents, type AppsInstallerBannerOptions as aibo } from './apps-installer-banner';
import './css/copilot-banner.css';

export type CopilotBannerOptions = cbo;
export type AppsInstallerBannerOptions = aibo;

export {
	CopilotBanner,
	CopilotBannerEvents,
	AppsInstallerBanner,
	AppsInstallerBannerEvents,
};
