import { Tag } from 'main.core';
import {EventEmitter, BaseEvent} from "main.core.events";
import { setPortalSettings, setPortalThemeSettings } from './site-utils';

export class SiteTitlePreviewWidget extends EventEmitter
{
	#container: HTMLElement

	constructor(portalSettings, portalThemeSettings)
	{
		super();
		this.setEventNamespace('BX.Intranet.Settings');

		setPortalSettings(this.render(), portalSettings);

		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':Portal:Change',
			this.onChange.bind(this)
		);

		if (portalThemeSettings)
		{
			setPortalThemeSettings(this.render(), portalThemeSettings?.theme);
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				this.getEventNamespace() + ':ThemePicker:Change',
				this.onSetTheme.bind(this)
			);
		}
	}

	onChange(event: BaseEvent)
	{
		setPortalSettings(this.render(), event.getData());
	}

	onSetTheme(baseEvent: BaseEvent)
	{
		setPortalThemeSettings(this.render(), baseEvent.getData())
	}

	render(): HTMLElement
	{
		if (!this.#container)
		{
			this.#container = Tag.render`
			<section class="intranet-settings__main-widget_section">
				<div class="intranet-settings__main-widget__bang"></div>
					<div class="intranet-settings__main-widget_bg"></div>
					<div class="intranet-settings__main-widget_pos-box">
						<aside class="intranet-settings__main-widget__aside">
							<div class="intranet-settings__main-widget__aside_item --active"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
							<div class="intranet-settings__main-widget__aside_item"></div>
						</aside>
						<main class="intranet-settings__main-widget_main">
						<div class="intranet-settings__main-widget_header"> 
						<!-- statement class. depends of content --with-logo -->
							<div class="intranet-settings__main-widget_logo" data-role="logo"></div>
							<div class="intranet-settings__main-widget_name" data-role="title">Bitrix</div>
							<div class="intranet-settings__main-widget_logo24" data-role="logo24">24</div>
						</div>
						<div class="intranet-settings__main-widget_lane_box">
							<div class="intranet-settings__main-widget_lane_item"></div>
							<div class="intranet-settings__main-widget_lane_inline">
								<div class="intranet-settings__main-widget_lane_item --sm"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
							</div>
							<div class="intranet-settings__main-widget_lane_inner">
								<div class="intranet-settings__main-widget_lane_item"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
							</div>
						</div>
					</main>
					</div>				
			</section>`;
		}

		return this.#container;
	}
}
