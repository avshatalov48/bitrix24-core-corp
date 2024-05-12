import { Event, Loc, Tag, Dom, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { SettingsField } from "ui.form-elements.field";
import { BaseField } from 'ui.form-elements.view';
import { Ears } from 'ui.ears'
import { setPortalSettings, setPortalThemeSettings } from './site-utils';


export type SiteThemeOptions = {
	id: string, //light:custom_123,
	css: ?Array,
	prefetchImages: ?Array,
	previewImage:  ?string,
	width:  number,
	height:  number,
	previewColor: string, // "#004982",
	style: string,

	default: ?boolean,
	resizable: boolean,
	removable: boolean,
};

export type SiteThemePickerOptions = {
	theme: SiteThemeOptions,
	themes: SiteThemeOptions[],
	baseThemes: Object,
	ajaxHandlerPath: string,
	allowSetDefaultTheme: boolean,
	isVideo: boolean,
	label: ?string
};

class ThemePickerElement extends BaseField
{
	#themePicker: BX.Intranet.Bitrix24.ThemePicker;

	constructor(themePickerSettings: SiteThemePickerOptions)
	{
		super({
			inputName: 'themeId',
			isEnable: themePickerSettings.allowSetDefaultTheme,
			bannerCode: 'limit_office_background_to_all',
		});
		this.#initThemePicker(themePickerSettings);

		this.applyTheme();
	}

	#initThemePicker(themePickerSettings: SiteThemePickerOptions)
	{
		this.#themePicker = new BX.Intranet.Bitrix24.ThemePicker(themePickerSettings);
		this.#themePicker.setThemes(themePickerSettings.themes);
		this.#themePicker.setBaseThemes(themePickerSettings.baseThemes);
		this.#themePicker.applyThemeAssets = () => {};
		this.#themePicker.getContentContainer = () => {
			return this.render().querySelector('div[data-role="theme-container"]');
		};

		const closure = this.#themePicker.handleRemoveBtnClick.bind(this.#themePicker);
		this.#themePicker.handleRemoveBtnClick = (event: Event) => {
			const item = this.#themePicker.getItemNode(event);
			if (!item)
			{
				return;
			}
			closure(event);
			this.applyPortalThemePreview(this.#themePicker.getTheme(this.#themePicker.getThemeId()));
			this.showSaveButton();
			//TODO Shift all <td>
		};

		const handleItemClick = this.#themePicker.handleItemClick.bind(this.#themePicker);
		this.#themePicker.handleItemClick = (event: Event) => {
			handleItemClick(event);
			this.applyTheme(event);
		};

		const addItem = this.#themePicker.addItem.bind(this.#themePicker);
		this.#themePicker.addItem = (theme) => {
			addItem(theme);
			this.applyPortalThemePreview(theme);
			this.showSaveButton();
		}
	}

	applyTheme(event: ?Event)
	{
		const themeNode = event ? this.#themePicker.getItemNode(event) : null;
		let themeSettings = themeNode ?
			this.#themePicker.getTheme(themeNode.dataset.themeId)
			: this.#themePicker.getAppliedTheme()
		;
		this.applyPortalThemePreview(themeSettings);

		if (event)
		{
			EventEmitter.emit(
				EventEmitter.GLOBAL_TARGET,
				'BX.Intranet.Settings:ThemePicker:Change',
				themeSettings
			);

			this.showSaveButton();
		}
	}

	applyPortalThemePreview(theme)
	{
		const container = this.render().querySelector('[data-role="preview"]');
		setPortalThemeSettings(container, theme);
		this.getInputNode().value = Type.isPlainObject(theme) ? theme['id'] : '';
	}

	showSaveButton()
	{
		this.getInputNode().disabled = false;
		this.getInputNode().form.dispatchEvent(new window.Event('change'));
	}

	getValue(): string
	{
		return this.getInputNode().value;
	}

	getInputNode(): HTMLInputElement
	{
		return this.render().querySelector('input[name="themeId"]');
	}

	applyPortalSettings()
	{

	}

	renderContentField(): HTMLElement
	{
		document.querySelector('.ui-side-panel-content').style.overflow = 'hidden';

		const container = Tag.render`
		<div class="intranet-theme-settings ui-section__row">
			<div class="ui-section__row theme-dialog-preview">
				<section data-role="preview" style="background-color: #0a51ae;" class="intranet-settings__main-widget_section --preview">
					<div class="intranet-settings__main-widget__bang"></div>
					<aside class="intranet-settings__main-widget__aside">
						<div class="intranet-settings__main-widget__aside_item --active"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
					</aside>
					<main class="intranet-settings__main-widget_main">
						<div class="intranet-settings__main-widget_header --with-logo">
							<div class="intranet-settings__main-widget_header_left">
								<div class="intranet-settings__main-widget_logo" data-role="logo"></div>
								<div class="intranet-settings__main-widget_name" data-role="title">Bitrix</div>
								<div class="intranet-settings__main-widget_logo24" data-role="logo24">24</div>
							</div>
							<div class="intranet-settings__main-widget_header_right">
								<div class="intranet-settings__main-widget_lane_item"></div>
								<div class="intranet-settings__main-widget_lane_item"></div>
							</div>
						</div>
						<div class="intranet-settings__main-widget_lane_box">
							<div class="intranet-settings__main-widget_lane_item"></div>
							<div class="intranet-settings__main-widget_lane_inline --space-between">
								<div class="intranet-settings__main-widget_lane_item --sm"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --square"></div>
							</div>
							<div class="intranet-settings__main-widget_lane_inner">
								<div class="intranet-settings__main-widget_lane_item"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
								<div class="intranet-settings__main-widget_lane_item --bg-30"></div>
							</div>
						</div>
					</main>
					<aside class="intranet-settings__main-widget__aside --right-side">
						<div class="intranet-settings__main-widget__aside_item --active"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
						<div class="intranet-settings__main-widget__aside_item"></div>
					</aside>
				</section>
			</div>
			<div class="ui-section__row theme-dialog-content" data-role="theme-container"></div>
			<input type="hidden" name="themeId" value="" disabled>
		</div>
		`;
		const uploadBtn = Tag.render`
			<div class="intranet-settings__theme-btn_box">
				<div class="intranet-settings__theme-btn" onclick="${this.handleNewThemeButtonClick.bind(this)}">${Loc.getMessage('INTRANET_SETTINGS_THEME_UPLOAD_BTN')}</div>
			</div>
		`;
		const themeContainer = container.querySelector('div[data-role="theme-container"]');
		Array.from(this.#themePicker.getThemes()).forEach((theme) => {
			const itemNode = this.#themePicker.createItem(theme);
			if (this.#themePicker.canSetDefaultTheme() !== true)
			{
				Event.unbindAll(itemNode, 'click');
				if (theme['default'] !== true)
				{
					Dom.addClass(itemNode, '--restricted');
					itemNode.appendChild(
						Tag.render `<div class="intranet-settings__theme_lock_box">${this.renderLockElement()}</div>`
					);
					Event.bind(itemNode, 'click', this.showBanner.bind(this));
				}
			}

			if (theme['default'] === true)
			{
				itemNode.setAttribute('data-role', 'ui-ears-active');
			}
			themeContainer.appendChild(itemNode);
		});
		(new Ears({
			container: themeContainer,
			noScrollbar: false
		})).init();

		container.appendChild(uploadBtn);

		return container;
	}

	handleNewThemeButtonClick(event)
	{
		if (this.#themePicker.canSetDefaultTheme() !== true)
		{
			return this.showBanner();
		}
		this.#themePicker.getNewThemeDialog().show();
	}

	handleLockButtonClick()
	{
		if (BX.getClass("BX.UI.InfoHelper"))
		{
			BX.UI.InfoHelper.show("limit_office_background_to_all");
		}
	}
}

export class SiteThemePickerField extends SettingsField
{
	#fieldView: ThemePickerElement;

	constructor(params)
	{
		params.fieldView = new ThemePickerElement(params.themePickerSettings);
		super(params);

		if (params.portalSettings)
		{
			this.setEventNamespace('BX.Intranet.Settings');


			setPortalSettings(this.getFieldView().render(), params.portalSettings);
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				this.getEventNamespace() + ':Portal:Change',
				(baseEvent: BaseEvent) => {
					setPortalSettings(this.getFieldView().render(), baseEvent.getData());
				}
			);
		}
	}
}
