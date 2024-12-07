import { CopilotBanner, CopilotBannerEvents, type CopilotBannerOptions } from './copilot-banner';
import { EventEmitter } from 'main.core.events';

export type AppsInstallerBannerOptions = {
	copilotBannerOptions: CopilotBannerOptions;
}

export const AppsInstallerBannerEvents = Object.freeze({
	...CopilotBannerEvents,
});

export class AppsInstallerBanner extends EventEmitter
{
	#copilotBanner: CopilotBanner;
	#copilotBannerOptions: CopilotBannerOptions;

	constructor(options: AppsInstallerBannerOptions)
	{
		super();
		this.setEventNamespace('AI:AppsInstallerBanner');

		this.#copilotBannerOptions = options.copilotBannerOptions ?? {};
		this.#copilotBanner = new CopilotBanner({
			...this.#copilotBannerOptions,
			buttonClickHandler: this.#installApp.bind(this),
		});

		this.#copilotBanner.subscribe(CopilotBannerEvents.actionStart, () => {
			this.emit(AppsInstallerBannerEvents.actionStart);
		});

		this.#copilotBanner.subscribe(CopilotBannerEvents.actionFinishSuccess, () => {
			this.emit(AppsInstallerBannerEvents.actionFinishSuccess);
		});

		this.#copilotBanner.subscribe(CopilotBannerEvents.actionFinishFailed, () => {
			this.emit(AppsInstallerBannerEvents.actionFinishFailed);
		});
	}

	show(): void
	{
		this.#copilotBanner.show();
	}

	hide(): void
	{
		this.#copilotBanner.hide();
	}

	async #installApp(): Promise<void>
	{
		// eslint-disable-next-line no-useless-return
		return;
	}
}
