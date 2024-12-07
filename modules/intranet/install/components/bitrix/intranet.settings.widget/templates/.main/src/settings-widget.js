import {ajax, Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {PopupComponentsMaker, PopupComponentsMakerItem} from 'ui.popupcomponentsmaker';
import type {SettingsWidgetOptions, SettingsWidgetHoldingOptions, MainPageConfiguration} from './types/options';
import {Label, LabelSize} from 'ui.label';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {MenuItem, Popup} from 'main.popup';
import { sendData } from 'ui.analytics';
import { RequisiteSection } from './requisite-section';
import 'ui.icon-set.actions';
import 'ui.icon-set.main';
import 'ui.icons.b24';
import 'ui.icons.crm';
import 'ui.buttons';

export class SettingsWidget extends EventEmitter
{
	#widgetPopup: PopupComponentsMaker;
	#target: Element;

	#otp;
	static #instance = null;
	#marketUrl: string;
	#theme:? Object;
	#holding: ?SettingsWidgetHoldingOptions = null;
	#holdingWidget;
	#isBitrix24:? boolean = false;
	#isFreeLicense:? boolean = false;
	#isAdmin:? boolean;
	#requisite:? Object;
	#requisiteSection: RequisiteSection;
	#settingsUrl: string;
	#isRenameable:? boolean;
	#mainPage: MainPageConfiguration;

	constructor(options: SettingsWidgetOptions)
	{
		super();
		this.setEventNamespace('BX.Intranet.SettingsWidget');
		this.#marketUrl = options.marketUrl;
		this.#isBitrix24 = options.isBitrix24;
		this.#isFreeLicense = options.isFreeLicense;
		this.#isAdmin = options.isAdmin;
		this.#requisite = options.requisite;
		this.#settingsUrl = options.settingsPath;
		this.#isRenameable = options.isRenameable;
		this.#mainPage = options.mainPage;
		this.#requisiteSection = new RequisiteSection(options.requisite);

		this.#setOptions(options);
	}

	#setOptions(options)
	{
		options.theme ? (this.#theme = options.theme) : null;
		options.otp ? (this.#otp = options.otp) : null;
		options.holding ? this.#setHoldingOptions(options.holding) : null;
	}

	#setHoldingOptions(options: SettingsWidgetHoldingOptions): void
	{
		if (!Type.isPlainObject(options))
		{
			this.#holding = null;
			return;
		}

		this.#holding = {
			isHolding: options.isHolding ?? false,
			affiliate: options.affiliate ?? null,
			canBeHolding: options.canBeHolding ?? false,
			canBeAffiliate: options.canBeAffiliate ?? false,
		};
	}

	setTarget(target)
	{
		this.#target = target;

		return this;
	}

	setWidgetPopup(widgetPopup: PopupComponentsMaker)
	{
		this.#widgetPopup = widgetPopup;

		this.#widgetPopup.getPopup().subscribe('onClose', () => {
			Event.unbindAll(this.getWidget().getPopup().getPopupContainer(), 'click');
		});

		this.#getItemsList()
			.then(() => {
				this.#drawItemsList();
			})
		;

		return this;
	}

	static bindWidget(popup): ?SettingsWidget
	{
		const instance = this.getInstance();

		if (instance)
		{
			instance.setWidgetPopup(popup);
		}

		return instance;
	}

	static bindAndShow(button): ?SettingsWidget
	{
		const instance = this.getInstance();

		if (instance)
		{
			Event.unbindAll(button);
			Event.bind(button, 'click', instance.toggle.bind(instance, button));
			instance.show(button);
		}

		return instance;
	}

	static init(options): SettingsWidget
	{

		if (this.#instance === null)
		{
			this.#instance = new this(options);
		}
		return this.#instance;
	}

	static getInstance(): ?SettingsWidget
	{
		return this.#instance;
	}

	static close(): void
	{
		const instance = this.getInstance();

		if (instance)
		{
			instance.getWidget().close();
		}
	}

	toggle(targetNode)
	{
		const popup = this.getWidget().getPopup();
		if (popup.isShown())
		{
			popup.close();
		}
		else
		{
			this.show(targetNode);
		}
	}

	show(targetNode): void
	{
		const popup = this.getWidget().getPopup();

		popup.setBindElement(targetNode);
		popup.show();

		if (popup.getPopupContainer().getBoundingClientRect().left < 30)
		{
			Dom.style(popup.getPopupContainer(), { left: '30px' });
		}

		this.setTarget(targetNode);
	}

	getWidget(): PopupComponentsMaker
	{
		return this.#widgetPopup;
	}

	#getItemsList(reload: boolean = false): Promise
	{
		if (reload === true || typeof this.#theme === 'undefined')
		{
			return new Promise((resolve) => {
				ajax.runComponentAction('bitrix:intranet.settings.widget', 'getData', { mode: 'class' })
					.then(({ data: { theme, otp, holding } }) => {
						this.#theme = theme;
						this.#otp = otp;
						this.#setHoldingOptions(holding);
						resolve();
					})
				;
			});
		}

		return Promise.resolve();
	}

	#drawItemsList(): void
	{
		const container = this.getWidget().getPopup().getPopupContainer();
		Dom.clean(container);

		Dom.append(this.#getHeader(), container);

		const content = [
			this.#requisite && this.#isAdmin ? this.#getRequisitesElement() : null,
			this.#mainPage.isAvailable ? this.#getMainPageElement() : null,
			this.#isAdmin ? this.#getSecurityAndSettingsElement() : null,
			this.#isBitrix24 ? this.#getHoldingsElement() : null,
			this.#getMigrateElement(),
		];

		content.forEach((element) => {
			Dom.append(element, container);
		});

		Dom.append(this.#getFooter(), container);
	}

	#getLinkHeaderIcon(): HTMLElement
	{
		const onclickCopyLink = () => {
			if (BX.clipboard.copy(window.location.origin))
			{
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('INTRANET_SETTINGS_WIDGET_LINK_COPIED_POPUP'),
					position: 'top-left',
					autoHideDelay: 3000,
				});
			}
		};

		return Tag.render`<span class='ui-icon-set --link-3 intranet-settings-widget__header-btn' onclick="${onclickCopyLink}"></span>`;
	}

	#getEditHeaderIcon(): HTMLElement
	{
		const onclickEditLink = () => {
			this.getWidget().close();
			BX.SidePanel.Instance.open(this.#settingsUrl + '?analyticContext=widget_settings_settings&page=portal&option=subDomainName');
		};

		return Tag.render`<span class='ui-icon-set --pencil-40 intranet-settings-widget__header-btn' onclick="${onclickEditLink}"></span>`;
	}

	#getHeader(): HTMLElement
	{
		const header = Tag.render`
				<div class="intranet-settings-widget__header">
					<div class="intranet-settings-widget__header_inner">
						<span class="intranet-settings-widget__header-name">${window.location.host}</span>
						${this.#isRenameable ? this.#getEditHeaderIcon() : this.#getLinkHeaderIcon()}
					</div>
				</div>
			`;
		this.#applyTheme(header, this.#theme);

		const adaptedEmptyHeader = (new PopupComponentsMakerItem({
			withoutBackground: true,
			html: header,
		})).getContainer();
		Dom.addClass(adaptedEmptyHeader, '--widget-header');

		EventEmitter.subscribe(
			'BX.Intranet.Bitrix24:ThemePicker:onThemeApply',
			({ data: { theme } }) =>
			{
				this.#applyTheme(header, theme);
			},
		);

		return adaptedEmptyHeader;
	}

	#applyTheme(container, theme): void
	{
		const previewImage = `url('${Text.encode(theme.previewImage)}')`;
		Dom.style(container, 'backgroundImage', previewImage);

		Dom.removeClass(container, 'bitrix24-theme-dark bitrix24-theme-light');
		const themeClass = String(theme.id).indexOf('dark:') === 0 ? 'bitrix24-theme-dark' : 'bitrix24-theme-light';
		Dom.addClass(container, themeClass);
	}

	#getFooter(): HTMLElement
	{
		const onclickOpenPartnerOrder = () => {
			this.getWidget().close();
			BX.UI.InfoHelper.show('info_implementation_request');
		};

		const partnerOrder = Tag.render`
			<span class="intranet-settings-widget__footer-item" onclick="${onclickOpenPartnerOrder}">
				${Loc.getMessage('INTRANET_SETTINGS_WIDGET_ORDER_PARTNER_LINK_MSGVER_1')}
			</span>
		`;

		const onclickWhereToBegin = () => {
			if (top.BX.Helper)
			{
				this.getWidget().close();
				top.BX.Helper.show('redirect=detail&code=18371844');
			}
		};

		const onclickSupport = () => {
			if (top.BX.Helper)
			{
				this.getWidget().close();
				if (this.#isFreeLicense)
				{
					BX.UI.InfoHelper.show('limit_support_bitrix');
				}
				else
				{
					BX.Helper.show('redirect=detail&code=12925062');
				}
			}
		};

		return Tag.render`
				<div class="intranet-settings-widget__footer">
					${this.#isBitrix24 ? partnerOrder : ''}
					<span class="intranet-settings-widget__footer-item" onclick="${onclickWhereToBegin}">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_WHERE_TO_BEGIN_LINK')}
					</span>
					<span class="intranet-settings-widget__footer-item" onclick="${onclickSupport}">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_SUPPORT_BUTTON')}
					</span>
				</div>
			`;
	}

	#prepareElement(element): HTMLElement
	{
		const item = this.getWidget().getItem({ html: element });
		const node = item.getContainer();
		Dom.addClass(node, '--widget-item');

		return node;
	}

	#getMainPageElement(): HTMLDivElement
	{
		const onclick = () => {
			this.getWidget().close();
			BX.SidePanel.Instance.open(this.#mainPage.settingsPath);
			BX.UI.Analytics.sendData({
				tool: 'landing',
				category: 'vibe',
				event: 'open_settings_main',
				c_sub_section: 'from_widget_vibe_point',
			});
		};
		const label = new Label({
			text: Loc.getMessage('INTRANET_SETTINGS_WIDGET_LABEL_NEW'),
			customClass: 'ui-label-new',
			size: LabelSize.SM,
			fill: true,
		})
		const labelWrapper = Tag.render`
			<div class="intranet-settings-widget__label-new">
				${label.render()}
			</div>
		`;

		const element = Tag.render`
			<div onclick="${onclick}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --green">
						<div class="ui-icon-set --home-page"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_MAIN_PAGE_TITLE')}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
				${this.#mainPage.isNew ? labelWrapper : null}
			</div>
		`;

		return this.#prepareElement(element);
	}

	#getRequisitesElement(): HTMLDivElement
	{
		return this.#prepareElement(this.#requisiteSection.getElement());
	}

	#getHoldingsElement(): ?HTMLDivElement
	{
		if (this.#isBitrix24 !== true || this.#holding === null)
		{
			return null;
		}

		if (!Type.isPlainObject(this.#holding.affiliate))
		{
			return this.#getEmptyHoldingsElement();
		}

		const affiliate = this.#holding.affiliate;
		const onclickOpen = () => {
			this.getWidget().close();
			this.#getHoldingWidget().show(this.#target);
		};

		const element = Tag.render`
		<div class="intranet-settings-widget__branch" onclick="${onclickOpen}">
			<div class="intranet-settings-widget__branch-icon_box">
				<div class="ui-icon-set intranet-settings-widget__branch-icon --filial-network"></div>
			</div>
			<div class="intranet-settings-widget__branch_content">
				<div class="intranet-settings-widget__branch-title">
					${
						affiliate.isHolding
							? Loc.getMessage('INTRANET_SETTINGS_WIDGET_MAIN_BRANCH')
							: Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECONDARY_BRANCH')
					}
				</div>
				<div class="intranet-settings-widget__title">
					${affiliate.name}
				</div>
			</div>
			<div class="intranet-settings-widget__branch-btn_box">
				<button class="ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light">
					${Loc.getMessage('INTRANET_SETTINGS_WIDGET_BRANCHES')}
				</button>
			</div>
		</div>
		`;

		return this.#prepareElement(element);
	}

	#getHoldingWidget()
	{
		if (!this.#holdingWidget)
		{
			this.#holdingWidget = BX.Intranet.HoldingWidget.getInstance();

			const onclickClose = () => {
				this.#holdingWidget.getWidget().close();
				this.show();
			};

			const holdingWidgetCloseBtn = Tag.render`
				<div class="intranet-settings-widget__close-btn">
					<div onclick="${onclickClose}" class="ui-icon-set --arrow-left intranet-settings-widget__close-btn_icon"></div>
					<div class="intranet-settings-widget__close-btn_name">${Loc.getMessage('INTRANET_SETTINGS_WIDGET_BRANCH_LIST')}</div>
				</div>
			`;

			this.#holdingWidget.getWidget().getPopup().getContentContainer().prepend(holdingWidgetCloseBtn);
		}

		return this.#holdingWidget;
	}

	#getEmptyHoldingsElement(): ?HTMLDivElement
	{
		if (!Type.isPlainObject(this.#holding))
		{
			return null;
		}

		const title = this.#isAdmin
			? Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_NETWORK')
			: Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_NETWORK_UNAVAILABLE')
		;

		const buttonText = this.#isAdmin
			? Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_SETTINGS')
			: Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_ABOUT')
		;

		const onclickOpen = () => {
			this.getWidget().close();
			if (this.#holding.canBeHolding)
			{
				this.#getHoldingWidget().show(this.#target);
			}
			else
			{
				BX.UI.InfoHelper.show('limit_office_multiple_branches');
			}
		};

		const lockIcon = Tag.render`
			<div class="intranet-settings-widget__branch-lock-icon_box">
				<div class="ui-icon-set intranet-settings-widget__branch-lock-icon --lock"></div>
			</div>
		`;

		const element = Tag.render`
			<div class="intranet-settings-widget__branch" onclick="${onclickOpen}">
				<div class="intranet-settings-widget__branch-icon_box">
					<div class="ui-icon-set intranet-settings-widget__branch-icon --filial-network"></div>
					${!this.#holding.canBeHolding ? lockIcon : ''}
				</div>
				<div class="intranet-settings-widget__branch_content">
					<div class="intranet-settings-widget__title">${title}</div>
				</div>
				<div class="intranet-settings-widget__branch-btn_box">
					<button class="ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light">${buttonText}</button>
				</div>
			</div>
		`;

		return this.#prepareElement(element);
	}

	#getSecurityAndSettingsElement(): HTMLDivElement
	{
		return Tag.render`
			<div class="intranet-settings-widget_inline-box">
				${this.#getSecurityElement()}
				${this.#getGeneralSettingsElement()}
			</div>
		`;
	}

	#getSecurityElement(): HTMLElement
	{
		const onclick = () => {
			this.getWidget().close();
			BX.SidePanel.Instance.open(this.#settingsUrl + '?page=security&analyticContext=widget_settings_settings');
		};

		const element = Tag.render`
			<span onclick="${onclick}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box ${this.#otp.IS_ACTIVE === 'Y' ? '--green' : '--yellow'}">
						<div class="ui-icon-set --shield"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_SECURITY_TITLE')}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</span>
		`;

		return this.#prepareElement(element);
	}

	#getGeneralSettingsElement(): HTMLElement
	{
		const onclick = () => {
			this.getWidget().close();
			BX.SidePanel.Instance.open(this.#settingsUrl + '?analyticContext=widget_settings_settings');
		};

		const element = Tag.render`
			<span onclick="${onclick}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --gray">
						<div class="ui-icon-set --settings-2"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_SETTINGS_TITLE')}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</span>
		`;

		return this.#prepareElement(element);
	}

	#getMigrateElement(): HTMLDivElement
	{
		const onclick = () => {
			this.getWidget().close();
			BX.SidePanel.Instance.open(`${this.#marketUrl}category/migration/`);
		};

		const element = Tag.render`
			<div onclick="${onclick}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --gray">
						<div class="ui-icon-set --market-1"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_MIGRATION_TITLE')}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</div>
		`;

		return this.#prepareElement(element);
	}
}
