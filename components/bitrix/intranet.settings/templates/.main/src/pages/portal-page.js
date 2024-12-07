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
import {Section, Row, SeparatorRow} from 'ui.section';
import 'ui.forms';
import { Alert, AlertColor, AlertSize } from 'ui.alerts';

import type { SiteDomainType } from '../fields/site-domain-field';
import type {SiteTitleInputType, SiteTitleLabelType} from '../fields/site-title-field';
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
				this.getAnalytic()?.addEventChangeTheme(baseEvent.data?.id);
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
				this.getValue('portalSettings'), this.getValue('portalThemeSettings'), this.getValue('portalSettingsLabels')
			),
			this.getValue('portalDomainSettings') ?
				this.buildDomainSection(this.getValue('portalDomainSettings')) : null,
			this.buildThemeSection(this.getValue('portalThemeSettings'), this.getValue('portalSettings'))
		].filter((section) => section instanceof SettingsSection);
	}

	buildSiteTitleSection(portalSettings: SiteTitleInputType, portalThemeSettings: SiteThemePickerOptions, portalSettingsLabels: SiteTitleLabelType): SettingsSection
	{
		if (!this.hasValue('sectionCompanyTitle'))
		{
			return;
		}
		const sectionView = new Section(this.getValue('sectionCompanyTitle'));

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
		const siteLogoRow = new SettingsRow({
			row: new Row({
				className: 'intranet-settings__grid_box',
			}),
			parent: sectionField,
		});

		const previewWidget = new SiteTitlePreviewWidget(portalSettings, portalThemeSettings);
		const tabsRow = new SettingsRow({
			row: new Row({
				className: 'intranet-settings__site-logo_subrow --no-padding --bottom-separator --block',
			}),
			parent: siteLogoRow,
		});

		const tabsField = new TabsField({parent: tabsRow});
		// 2.1 Tab Site name
		const siteTitleTab = new TabField({
			parent: tabsField,
			tabsOptions: this.getValue('tabCompanyTitle')
		});

		const siteTitleRow = new Row({});

		const siteTitleField = new SiteTitleField({
			parent: siteTitleRow,
			siteTitleOptions: portalSettings,
			siteTitleLabels: portalSettingsLabels,
			helpMessages: {
				site: this.helpMessageProviderFactory(),
			}
		});

		new SettingsRow({
			row: siteTitleRow,
			parent: siteTitleTab,
			child: siteTitleField,
		});

		new SettingsRow({
			parent: siteTitleTab,
			child: siteTitleField.getLogo24Field(),
		});

		const siteLogoTab = new TabField({
			parent: tabsField,
			tabsOptions: this.getValue('tabCompanyLogo')
		});

		const siteLogoField = new SiteLogoField({
			siteLogoLabel: this.getValue('portalSettingsLabels').logo,
			siteLogoOptions: this.getValue('portalSettings').logo,
			canUserEditLogo: this.getValue('portalSettings').canUserEditLogo,
		});

		new SettingsRow({
			parent: siteLogoTab,
			child: siteLogoField,
		});

		tabsField.activateTab(siteTitleTab);
		// 2.2 Widget

		new SettingsRow({
			row: new Row({
				content: previewWidget.render(),
				className: 'intranet-settings__site-logo_subrow --no-padding',
			}),
			parent: siteLogoRow,
		});

		// 2.3 site_name

		new SettingsRow({
			row: new SeparatorRow(),
			parent: sectionField,
		});

		new SettingsRow({
			parent: sectionField,
			child: new SettingsField({
				fieldView: (new TextInput({
					inputName: 'name',
					label: this.getValue('portalSettingsLabels').name,
					value: this.getValue('portalSettings').name,
					placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COMPANY_TITLE'),
					inputDefaultWidth: true,
				})),
			}),
		});

		//endregion
		return sectionField;
	}

	#getOwnDomainTabBody(domainSettings): HTMLElement
	{
		const copyButton = Tag.render`<div class="ui-icon-set --copy-plates intranet-settings__domain__list_btn"></div>`;
		const exampleDns = domainSettings.exampleDns.join('<br>');

		BX.clipboard.bindCopyClick(copyButton, {text: () => {
				return exampleDns.replaceAll('<br>', "\n");
			}});

		const res = Tag.render
			`<div class="intranet-settings__domain__list_box">
						<ul class="intranet-settings__domain__list">
							<li class="intranet-settings__domain__list_item">
								${Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP1')}
								<div class="intranet-settings__domain_box">
									${exampleDns}
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

	buildDomainSection(domainSettings: SiteDomainType): SettingsSection
	{
		if (!this.hasValue('sectionSiteDomain'))
		{
			return;
		}
		const sectionView = new Section(this.getValue('sectionSiteDomain'));

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

		const tabsRow = new SettingsRow({
			parent: sectionField,
		});

		//region 2. Tabs
		const tabsField = new TabsField({parent: tabsRow});
		// 2.1 Tab Site name
		const firstTab = new TabField({
			parent: tabsField,
			tabsOptions: this.getValue('tabDomainPrefix')
		});

		const siteDomainField = new SiteDomainField({
			siteDomainOptions: domainSettings,
			helpMessages: {
				site: this.helpMessageProviderFactory(),
			}
		});
		Event.bind(siteDomainField.getFieldView().getInputNode(), 'keydown', () => {
			this.getAnalytic()?.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_SITE);
		});

		const firstTabRow = new Row({
			content: siteDomainField.render(),
		});

		new SettingsRow({
			row: firstTabRow,
			parent: firstTab,
			child: siteDomainField,
		});

		const secondTab = new TabField({
			parent: tabsField,
			tabsOptions: this.getValue('tabDomain')
		});

		const descriptionRow = new Row({
			content: this.#getOwnDomainTabBody(domainSettings),
		});

		new SettingsRow({
			row: descriptionRow,
			parent: secondTab
		});

		tabsField.activateTab(firstTab);
		//endregion

		return sectionField;
	}

	buildThemeSection(themePickerSettings: SiteThemePickerOptions, portalSettings: ?SiteTitleInputType): SettingsSection
	{
		if (!this.hasValue('sectionSiteTheme'))
		{
			return;
		}
		const sectionView = new Section(this.getValue('sectionSiteTheme'));

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
