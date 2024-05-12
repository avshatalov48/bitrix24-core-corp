import {ajax, Cache, Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {PopupComponentsMaker, PopupComponentsMakerItem} from 'ui.popupcomponentsmaker';
import {BaseEvent, EventEmitter} from 'main.core.events';
import type {SettingsWidgetOptions, SettingsWidgetHoldingOptions} from './types/options';
import {MenuItem, Popup} from 'main.popup';
import { sendData } from 'ui.analytics';
import 'ui.icon-set.actions';
import 'ui.icon-set.main';
import 'ui.icons.b24';
import 'ui.icons.crm';
import 'ui.buttons';

export class SettingsWidget extends EventEmitter
{
	#widgetPopup: PopupComponentsMaker;
	#requisitesPopup:? PopupComponentsMaker;
	#copyLinkPopup:? Popup;
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
	#settingsUrl: string;
	#isRenameable:? boolean;

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

		this.#setOptions(options);

		top.BX.addCustomEvent('onLocalStorageSet', (params) => {
			const eventName = params?.key ?? null;
			if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate')
			{
				this.#getRequisites().then(() => {
					this.#drawItemsList();
				});
			}
		});
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
			Event.unbindAll(this.#getWidget().getPopup().getPopupContainer(), 'click');
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

	toggle(targetNode)
	{
		const popup = this.#getWidget().getPopup();
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
		const popup = this.#getWidget().getPopup();

		popup.setBindElement(targetNode);
		popup.show();

		if (popup.getPopupContainer().getBoundingClientRect().left < 30)
		{
			Dom.style(popup.getPopupContainer(), { left: '30px' });
		}

		this.setTarget(targetNode);
	}

	#getWidget(): PopupComponentsMaker
	{
		return this.#widgetPopup;
	}

	#getItemsList(reload: boolean = false): Promise
	{
		if (reload === true || typeof this.#theme === 'undefined')
		{
			return new Promise((resolve, reject) => {
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

	#getRequisites()
	{
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:intranet.settings.widget', 'getRequisites', { mode: 'class' })
				.then(({ data: { requisite } }) => {
					this.#requisite = requisite;
				})
		});
	}

	#drawItemsList(): void
	{
		const container = this.#getWidget().getPopup().getPopupContainer();
		Dom.clean(container);

		Dom.append(this.#getHeader(), container);

		const content = [
			this.#requisite && this.#isAdmin ? this.#getRequisitesElement() : null,
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
			this.#getWidget().close();
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
			this.#getWidget().close();
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
				this.#getWidget().close();
				top.BX.Helper.show('redirect=detail&code=18371844');
			}
		};

		const onclickSupport = () => {
			if (top.BX.Helper)
			{
				this.#getWidget().close();
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
		const item = this.#getWidget().getItem({ html: element });
		const node = item.getContainer();
		Dom.addClass(node, '--widget-item');

		return node;
	}

	#getRequisitesElement(): HTMLDivElement
	{
		const onclickOpenRequisite = (event) => {
			window.open(this.#requisite.publicUrl, '_blank');
			this.#getWidget().close();
		}

		const onclickCopyLink = (event) => {
			if (BX.clipboard.copy(this.#requisite.publicUrl))
			{
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('INTRANET_SETTINGS_WIDGET_COPIED_POPUP'),
					position: 'top-left',
					autoHideDelay: 3000,
				});
			}
		};

		const onclickCreateLanding = () => {
			requisiteButton.setWaiting(true);
			this.#createLanding().then(() => {
				this.#requisitesPopup = null;
				this.#drawItemsList();

				if (!this.#requisite.isPublic)
				{
					const errorPopup = new Popup('public-landing-error',
						this.#getWidget().getPopup().getPopupContainer().querySelector('[data-role="requisite-widget-title"]'),
						{
							autoHide: true,
							closeByEsc: true,
							angle: true,
							darkMode: true,
							content: Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING_ERROR'),
							events: {
								onShow: () => {
									setTimeout(() => {
										Event.bindOnce(this.#getWidget().getPopup().getPopupContainer(), 'click', () => {
											errorPopup.close();
										});
									}, 0);
								},
								onClose: () => {
									errorPopup.destroy();
								},
							}
						}
					);
					errorPopup.show();
				}
			});
		};

		const onclickCreateCompany = (event) => {
			this.#getWidget().close();
			BX.SidePanel.Instance.open('/crm/company/details/0/?mycompany=y');
		};

		const requisiteButton = new BX.UI.Button({
			id: 'requisite-btn',
			text: this.#requisite.isConnected
				? Loc.getMessage('INTRANET_SETTINGS_WIDGET_REDIRECT_TO_REQUISITE_BUTTON')
				: this.#requisite.isCompanyCreated
					? Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING')
					: Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_REQUISITE_BUTTON'),
			noCaps: true,
			onclick: this.#requisite.isConnected
				? onclickOpenRequisite
				: this.#requisite.isCompanyCreated
					? onclickCreateLanding
					: onclickCreateCompany,
			className: 'ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light',
		});

		const onclickRequisitesSettings = (event) => {
			if (!this.#requisitesPopup)
			{
				const onclickConfigureSite = () => {
					window.open(this.#requisite.editUrl, '_blank');
					this.#requisitesPopup.close();
					this.#getWidget().close();
				};

				let copyLinkButton = null;
				if (this.#requisite.publicUrl)
				{
					copyLinkButton = {
						html: `
							<div class="intranet-settings-widget__popup-item">
								<div class="ui-icon-set --link-3"></div> 
								<div class="intranet-settings-widget__popup-name">${Loc.getMessage('INTRANET_SETTINGS_WIDGET_COPY_LINK_BUTTON')}</div>
							</div>
						`,
						onclick: onclickCopyLink,
					};
				}

				let configureSiteButton = null;
				if (this.#requisite.editUrl)
				{
					configureSiteButton = {
						html: `
							<div class="intranet-settings-widget__popup-item">
								<div class="ui-icon-set --paint-1"></div> 
								<div class="intranet-settings-widget__popup-name">${Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_CUTAWAY_SITE_BUTTON')}</div>
							</div>
						`,
						onclick: onclickConfigureSite,
					};
				}

				const onclickConfigureRequisites = () => {
					if (this.#requisitesPopup)
					{
						this.#requisitesPopup.close();
					}

					this.#getWidget().close();
					BX.SidePanel.Instance.open(this.#settingsUrl + '?page=requisite&analyticContext=widget_settings_settings');
				};

				const configureRequisiteButton = {
					html: `
						<div class="intranet-settings-widget__popup-item">
							<div class="ui-icon-set --pencil-40"></div>
							<div class="intranet-settings-widget__popup-name">${Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_REQUISITE_BUTTON')}</div>
						</div>
					`,
					onclick: onclickConfigureRequisites,
				};

				const popupWidth = 240;

				this.#requisitesPopup = BX.PopupMenu.create(
					'requisites-settings',
					event.currentTarget,
					[copyLinkButton, configureRequisiteButton, configureSiteButton],
					{
						closeByEsc: true,
						autoHide: true,
						width: popupWidth,
						offsetLeft: -72,
						angle: {
							offset: popupWidth / 2 - 15,
						},
						events: {
							onShow: () => {
								setTimeout(() => {
									Event.bindOnce(this.#getWidget().getPopup().getPopupContainer(), 'click', () => {
										this.#requisitesPopup.close();
									});
								}, 0);
							},
						}
					},
				);
			}

			this.#requisitesPopup.show();
		};

		const requisiteSettingsButton = this.#requisite.isCompanyCreated
			? Tag.render`
				<span onclick="${onclickRequisitesSettings}" class="intranet-settings-widget__requisite-btn">
					<i class='ui-icon-set --more-information'></i>
				</span>
			`
			: ``;

		const element = Tag.render`
			<div class="intranet-settings-widget__business-card intranet-settings-widget_box">
				<div class="intranet-settings-widget__business-card_head intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --gray">
						<div class="ui-icon-set --customer-card-1"></div>
					</div>
					<div class="intranet-settings-widget__title" data-role="requisite-widget-title">
						${
							this.#requisite.isConnected
							? Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_SITE_TITLE')
							: Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_TITLE')
						}
					</div>
					<i class="ui-icon-set --help" onclick="BX.Helper.show('redirect=detail&code=18213326')"></i>
				</div>

				<div class="intranet-settings-widget__business-card_footer">
					${requisiteButton.getContainer()}
					${requisiteSettingsButton}
				</div>
			</div>
		`;

		return this.#prepareElement(element);
	}

	#createLanding()
	{
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:intranet.settings.widget', 'createRequisiteLanding', { mode: 'class' })
				.then(({ data: { isConnected, isPublic, publicUrl, editUrl } }) => {
					this.#requisite.isConnected = isConnected;
					this.#requisite.isPublic = isPublic;
					this.#requisite.publicUrl = publicUrl;
					this.#requisite.editUrl = editUrl;
					resolve();
				})
			;
		});
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
			this.#getWidget().close();
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
			this.#getWidget().close();
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
		const onclick = (event) => {
			this.#getWidget().close();
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
		const onclick = (event) => {
			this.#getWidget().close();
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
		const onclick = (event) => {
			this.#getWidget().close();
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
