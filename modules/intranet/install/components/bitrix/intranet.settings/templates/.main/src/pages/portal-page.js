import { BaseEvent, EventEmitter } from 'main.core.events';
import { AnalyticSettingsEvent } from '../analytic';
import { SiteTitleField } from '../fields/site-title-field';
import { SiteDomainField } from '../fields/site-domain-field';
import { SiteLogoField } from '../fields/site-logo-field';
import { SettingsSection, SettingsRow, TabsField, TabField, SettingsField, BaseSettingsPage } from 'ui.form-elements.field';
import { TextInput } from 'ui.form-elements.view';
import { SiteTitlePreviewWidget } from '../fields/site-title-preview-widget';
import { SiteThemePickerField } from '../fields/site-theme-picker-field';
import { setPortalSettings, setPortalThemeSettings } from '../fields/site-utils';

import { Tag, Loc, Runtime, Text, Dom, Type, Event } from 'main.core';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';

import 'ui.icon-set.social';
import { Section, Row } from 'ui.section';
import 'ui.forms';
import { Alert, AlertColor, AlertSize } from 'ui.alerts';

import type { SiteDomainType } from '../fields/site-domain-field';
import type { SiteTitleInputType } from '../fields/site-title-field';
import type { SiteThemeOptions, SiteThemePickerOptions } from '../fields/site-theme-picker-field';

export class PortalPage extends BaseSettingsPage
{
	constructor() {
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_PORTAL');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_PORTAL');

		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':Portal:Change',
			(baseEvent: BaseEvent) => {
				if (!Type.isNil(baseEvent.data.title))
				{
					this.getAnalytic()?.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_NAME);
				}
				else if (!Type.isNil(baseEvent.data.logo))
				{
					this.getAnalytic()?.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_LOGO);
				}
			}
		);
		//BX.Intranet.Settings:ThemePicker:Change
		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':ThemePicker:Change',
			(baseEvent: BaseEvent) => {
				this.getAnalytic()?.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_THEME);
			}
		);
	}

	getType(): string
	{
		return 'portal';
	}

	headerWidgetRender()
	{
		return '';
		// It is used to return #headerWidgetRenderAlternative;
	}

	//TODO delete after autumn 2023
	#headerWidgetRenderAlternative()
	{
		if (!this.hasValue('portalSettings'))
		{
			return '';
		}

		const portalSettings = this.getValue('portalSettings');
		const portalThemeSettings = this.getValue('portalThemeSettings');
		const portalDomainSettings = this.getValue('portalDomainSettings');

		const container = Tag.render`
		<div class="intranet-settings__header-widget_box">
			<div class="intranet-settings__header-widget_main">
				<div class="intranet-settings__header-widget_icon" data-role="logo"></div>
				<div class="intranet-settings__header-widget_name" data-role="title">Bitrix</div>
				<div class="intranet-settings__header-widget_logo24" data-role="logo24">24</div>
			</div>
			<div class="intranet-settings__header-widget__link_box">
				<div class="intranet-settings__header-widget__link_value">${Text.encode(portalDomainSettings.hostname)}</div>
				<div data-role="copy" class="ui-icon-set --link-3 intranet-settings__header-widget__link_btn"></div>
			</div>
		</div>`;

		setPortalSettings(container, portalSettings);
		setPortalThemeSettings(container, portalThemeSettings?.theme);

		const copyButton = container.querySelector('[data-role="copy"]');
		BX.clipboard.bindCopyClick(copyButton, {text: () => {
				return portalDomainSettings.hostname;
			}})
		;

		return container;
	}

	getSections(): SettingsSection[]
	{
		return [
			this.buildSiteTitleSection(
				this.getValue('portalSettings'), this.getValue('portalThemeSettings')
			),
			this.getValue('portalDomainSettings') ?
				this.buildDomainSection(this.getValue('portalDomainSettings')) : null,
			this.buildThemeSection(this.getValue('portalThemeSettings'), this.getValue('portalSettings'))
		].filter((section) => section instanceof SettingsSection);
	}

	buildSiteTitleSection(portalSettings: SiteTitleInputType, portalThemeSettings: SiteThemePickerOptions): SettingsSection
	{
		const sectionView = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE'),
			titleIconClasses: 'ui-icon-set --pencil-draw',
			isOpen: true,
			// isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
			bannerCode: 'ip_access_rights_lock',
		});

		const sectionField = new SettingsSection({
			parent: this,
			section: sectionView,
		});
		// 1. This is a description on blue box
		sectionView.append(
			(new Row({
				content: (new Alert({
					text: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_DESCRIPTION'),
					inline: true,
					size: AlertSize.SMALL,
					color: AlertColor.PRIMARY,
				})).getContainer(),
			})).render())
		;

		//region 2. Tabs
		const previewWidget = new SiteTitlePreviewWidget(portalSettings, portalThemeSettings);

		const tabsField = new TabsField({parent: sectionField});
		const siteNameRow = new Row({});
		// 2.1 Tab Site name
		const siteTitleTab = new TabField({
			parent: tabsField,
			tabsOptions: {
				head: { title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE') },
				body: () => {
					const siteTitleField = new SiteTitleField({
						parent: siteTitleTab,
						siteTitleOptions: portalSettings,
						helpMessages: {
							site: this.helpMessageProviderFactory(),
						}
					});
					return siteTitleField.render();
				}
			}
		});

		const siteLogoTab = new TabField({
			parent: tabsField,
			tabsOptions: {
				restricted: this.getValue('portalSettings').canUserEditLogo === false,
				bannerCode: 'limit_admin_logo',
				head: Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO'),
				body: new Promise((resolve, reject) =>
				{
					Runtime
						.loadExtension('ui.uploader.stack-widget')
						.then((exports) => {
								const siteLogoField = new SiteLogoField({
									parent: siteTitleTab,
									siteLogoOptions: this.getValue('portalSettings').logo,
									canUserEditLogo: this.getValue('portalSettings').canUserEditLogo,
								});
								siteLogoField.initUploader(exports);
								resolve(siteLogoField.render());
							}
						);
				}),

			}
		});
		tabsField.activateTab(siteTitleTab);
		// 2.2 Widget
		sectionView.append(siteNameRow.render());
		siteNameRow.append(
			Tag.render`
			<div class="intranet-settings__grid_box">
				<input type="hidden" name="justToBeThere" value="ofCourse" />
				<div data-role="title-container" class="intranet-settings__grid_item"></div>
				<div class="intranet-settings__grid_item">${previewWidget.render()}</div>
			</div>`);
		setTimeout(() => {
			siteNameRow
				.render()
				.querySelector('div[data-role="title-container"]')
				.appendChild(tabsField.render())
			;
		}, 0);
		// 2.3 site_name

		new SettingsRow({
			row: new Row({
				separator: 'top',
				className: '--block',
			}),
			parent: sectionField,
			child: new SettingsField({
				fieldView: (new TextInput({
					inputName: 'name',
					label: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_NAME'),
					value: this.getValue('portalSettings').name,
					placeholder: window.document.location.hostname,
					inputDefaultWidth: true,
				})),
			}),
		});

		//endregion
		return sectionField;
	}

	buildDomainSection(domainSettings: SiteDomainType): SettingsSection
	{
		const sectionView = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN'),
			titleIconClasses: 'ui-icon-set --globe',
			isOpen: false,
		});
		const sectionField = new SettingsSection({
			parent: this,
			section: sectionView
		});
		// 1. This is a description on blue box
		sectionView.append(
			(new Row({
				content: (new Alert({
					text: `
						${Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN_DESCRIPTION')}
						<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213298')">
							${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
						</a>
					`,
					inline: true,
					size: AlertSize.SMALL,
					color: AlertColor.PRIMARY,
				})).getContainer(),
			})).render())
		;

		//region 2. Tabs
		const tabsField = new TabsField({parent: sectionField});
		// 2.1 Tab Site name
		const firstTab = new TabField({
			parent: tabsField,
			tabsOptions: {
				head: { title: Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME1') },
				body: () => {
					const siteDomainField = new SiteDomainField({
						parent: firstTab,
						siteDomainOptions: domainSettings,
						helpMessages: {
							site: this.helpMessageProviderFactory(),
						}
					});
					Event.bind(siteDomainField.getFieldView().getInputNode(), 'keydown', () => {
						this.getAnalytic()?.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_SITE);
					});

					return siteDomainField.render();
				}
			}
		});

		new TabField({
			parent: tabsField,
			tabsOptions: {
				restricted: domainSettings.isCustomizable === false,
				bannerCode: 'limit_office_own_domain',
				head: Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME2'),
				body: () => {
					const copyButton = Tag.render`<div class="ui-icon-set --copy-plates intranet-settings__domain__list_btn"></div>`;
					BX.clipboard.bindCopyClick(copyButton, {text: () => {
							return Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP_DNS').replaceAll('<br>', "\n");
						}});

					const res = Tag.render
						`<div class="intranet-settings__domain__list_box">
						<ul class="intranet-settings__domain__list">
							<li class="intranet-settings__domain__list_item">
								${Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP1')}
								<div class="intranet-settings__domain_box">
									${Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP_DNS')}
									${copyButton}
								</div>
							</li>
							<li class="intranet-settings__domain__list_item">
								${Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP2')}
							</li>
							<li class="intranet-settings__domain__list_item">
								${Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP3')}
							</li>
						</ul>
						<a target="_blank" href="/settings/support.php" class="settings-tools-description-link">${Loc.getMessage('INTRANET_SETTINGS_WRITE_TO_SUPPORT')}</a>
					</div>`;

					if (domainSettings.isCustomizable !== true)
					{
						Event.bind(res.querySelector('a.settings-tools-description-link'), 'click', (event) => {
							BX.UI.InfoHelper.show('limit_office_own_domain');
							event.preventDefault();
							return false;
						})
					}
					return res;
				}
			}
		});

		const justRow = new Row({});
		sectionView.append(justRow.render());

		justRow.append(
			Tag.render`
			<div class="intranet-settings__grid_box --single-item">
				<div class="intranet-settings__grid_item">${tabsField.render()}</div>
			</div>`)
		;
		tabsField.activateTab(firstTab);
		//endregion

		return sectionField;
	}

	buildThemeSection(themePickerSettings: SiteThemePickerOptions, portalSettings: ?SiteTitleInputType): SettingsSection
	{
		const sectionView = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME'),
			titleIconClasses: 'ui-icon-set --picture',
			isOpen: false,
			isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
			bannerCode: 'ip_access_rights_lock',
		});

		const sectionField = new SettingsSection({
			section: sectionView,
			parent: this,
		});

		// 1. This is a description on blue box
		new SettingsRow({
			row: new Row({
				content: (new Alert({
					text: `
						${Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME_DESCRIPTION')}
						<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18325288')">
							${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
						</a>
					`,
					inline: true,
					size: AlertSize.SMALL,
					color: AlertColor.PRIMARY,
				})).getContainer(),
			}),
			parent: sectionField
		});

		// 2. This is a theme picker
		new SiteThemePickerField({
			parent: sectionField,
			portalSettings,
			themePickerSettings
		});

		return sectionField;
	}
}
