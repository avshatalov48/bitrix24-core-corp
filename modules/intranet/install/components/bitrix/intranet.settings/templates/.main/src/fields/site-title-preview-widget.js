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
			<section class="intranet-settings__preview --preview" data-role="preview">
				<div class="preview__header">
					<div class="preview__header-box">
						<div class="preview__header-left-box">
							<div class="preview__menu-switcher">
								<span class="preview__menu-switcher__icon"></span>
							</div>
							<div class="preview__block-item"></div>
							<div class="preview__block-item"></div>
							<div class="preview__block-item"></div>
						</div>
						<div class="preview__header-right-box">
							<div class="intranet-settings__logo-box">
								<div class="intranet-settings__main-widget_logo" data-role="logo"></div>
								<div class="intranet-settings__main-widget_name" data-role="title">Bitrix</div>
								<div class="intranet-settings__logo24" data-role="logo24">
									24
								</div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>				
						</div>
					</div>
				</div>
				<div class="preview__main">
					<div class="preview__main-left">
						<div class="preview__circle_container">
							<div class="preview__circle_item-outline">
								<div class="preview__circle_item --active"></div>
							</div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
					</div>
					<div class="preview__main-center">
						<div class="preview__main-row">
							<div class="preview__main-row-left">
								<div class="preview__block-item --w145"></div>
								<div class="preview__block-item --opacity80 --w47"></div>
								<div class="preview__block-item --w90"></div>
							</div>
							<div class="preview__main-row-right">
								<div class="preview__block-item --w50"></div>
							</div>
						</div>
						<div class="preview__main-row">
							<div class="preview__main-row-left">
								<div class="preview__block-item --w80"></div>
								<div class="preview__block-item --w50"></div>
							</div>
							<div class="preview__main-row-right">
								<div class="preview__block-item --w90"></div>
							</div>
						</div>
						<div class="preview__main-column">
							<div class="preview__main-header"></div>
							<div class="preview__main-table"></div>
						</div>
					</div>
					<div class="preview__main-right">
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
					</div>
				</div>
			</section>
			`;
		}

		return this.#container;
	}
}
