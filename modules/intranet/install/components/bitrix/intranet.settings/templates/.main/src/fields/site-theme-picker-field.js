import { Event, Loc, Tag, Dom, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { SettingsField } from "ui.form-elements.field";
import { BaseField } from 'ui.form-elements.view';
import { Ears } from 'ui.ears'
import { setPortalSettings, setPortalThemeSettings } from './site-utils';
import { ThemePickerDialog } from 'intranet.theme-picker.dialog';


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
	#themePickerDialog: ThemePickerDialog;

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
		this.#themePicker = new top.BX.Intranet.Bitrix24.ThemePicker(themePickerSettings);
		this.#themePicker.setThemes(themePickerSettings.themes);
		this.#themePicker.setBaseThemes(themePickerSettings.baseThemes);
		this.#themePickerDialog = new ThemePickerDialog(this.#themePicker);
		this.#themePickerDialog.applyThemeAssets = () => {};
		this.#themePickerDialog.getContentContainer = () => {
			return this.render().querySelector('div[data-role="theme-container"]');
		};

		const closure = this.#themePickerDialog.handleRemoveBtnClick.bind(this.#themePickerDialog);
		this.#themePickerDialog.handleRemoveBtnClick = (event: Event) => {
			const item = this.#themePickerDialog.getItemNode(event);
			if (!item)
			{
				return;
			}
			closure(event);
			this.applyPortalThemePreview(this.#themePicker.getTheme(this.#themePicker.getThemeId()));
			this.showSaveButton();
			//TODO Shift all <td>
		};

		const handleItemClick = this.#themePickerDialog.handleItemClick.bind(this.#themePickerDialog);
		this.#themePickerDialog.handleItemClick = (event: Event) => {
			handleItemClick(event);
			this.applyTheme(event);
		};

		const addItem = this.#themePickerDialog.addItem.bind(this.#themePickerDialog);
		this.#themePickerDialog.addItem = (theme) => {
			addItem(theme);
			this.applyPortalThemePreview(theme);
			this.showSaveButton();
		}
	}

	applyTheme(event: ?Event)
	{
		const themeNode = event ? this.#themePickerDialog.getItemNode(event) : null;
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
			const itemNode = this.#themePickerDialog.createItem(theme);
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

		this.#themePickerDialog.getNewThemeDialog().show();
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
