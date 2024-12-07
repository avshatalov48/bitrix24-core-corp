import { EventEmitter } from 'main.core.events';
import {ajax, Loc, Tag, Type, Dom, Event} from 'main.core';
import {PopupComponentsMaker} from "ui.popupcomponentsmaker";
import {SettingsWidget} from "./settings-widget";
export class RequisiteSection extends EventEmitter
{
	#companyId;
	#requisiteId;
	#isConnected: boolean;
	#isPublic: boolean;
	#publicUrl: string;
	#editUrl: string;

	#requisiteElement:? HTMLElement;
	#requisitesPopup:? PopupComponentsMaker;
	#requisiteButton:? BX.UI.Button;

	constructor(options) {
		super();

		if (options)
		{
			this.#updateOptions(options);
			top.BX.addCustomEvent('onLocalStorageSet', (params) => {
				const eventName = params?.key ?? null;

				if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate' || eventName === 'BX.Crm.RequisiteSliderDetails:onSave')
				{
					this.#getRequisites().then(() => {
						this.#updateElement();
					});
				}
			});
		}
	}

	#updateOptions(options): void
	{
		this.#companyId = options.companyId ?? 0;
		this.#requisiteId = options.requisiteId ?? 0;
		this.#isConnected = Type.isBoolean(options.isConnected) ? options.isConnected : false;
		this.#isPublic = Type.isBoolean(options.isPublic) ? options.isPublic : false;
		this.#publicUrl = Type.isString(options.publicUrl) ? options.publicUrl : '';
		this.#editUrl = Type.isString(options.editUrl) ? options.editUrl : '';
	}

	#updateElement(): void
	{
		const currentElement = this.getElement();
		this.#requisiteElement = null;
		this.#requisiteButton = null;
		Dom.replace(currentElement, this.getElement());
	}

	getElement(): HTMLElement
	{
		if (!this.#requisiteElement)
		{
			this.#requisiteElement = Tag.render`
				<div class="intranet-settings-widget__business-card intranet-settings-widget_box">
					<div class="intranet-settings-widget__business-card_head intranet-settings-widget_inner">
						<div class="intranet-settings-widget_icon-box --gray">
							<div class="ui-icon-set --customer-card-1"></div>
						</div>
						<div class="intranet-settings-widget__title" data-role="requisite-widget-title">
							${
								this.#isConnected
									? Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_SITE_TITLE')
									: Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_TITLE')
							}
						</div>
						<i class="ui-icon-set --help" onclick="BX.Helper.show('redirect=detail&code=18213326')"></i>
					</div>

					<div class="intranet-settings-widget__business-card_footer">
						${this.#getRequisiteButton().getContainer()}
						${this.#companyId ? this.#getRequisiteSettingsButton() : ''}
					</div>
				</div>
			`;
		}

		return this.#requisiteElement;
	}

	#getRequisiteSettingsButton(): HTMLElement
	{
		const onclickRequisitesSettings = () => {
			this.#getRequisitesPopup().show();
		};

		return Tag.render`
			<span onclick="${onclickRequisitesSettings}" class="intranet-settings-widget__requisite-btn">
				<i class='ui-icon-set --more-information'></i>
			</span>
		`;
	}

	#getRequisitesPopup()
	{
		if (!this.#requisitesPopup)
		{
			const onclickCopyLink = () => {
				if (BX.clipboard.copy(this.#publicUrl))
				{
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('INTRANET_SETTINGS_WIDGET_COPIED_POPUP'),
						position: 'top-left',
						autoHideDelay: 3000,
					});
				}
			};

			const onclickConfigureSite = () => {
				window.open(this.#editUrl, '_blank');
				this.#requisitesPopup.close();
				SettingsWidget.close();
			};

			let copyLinkButton = null;
			if (this.#publicUrl)
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
			if (this.#editUrl)
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

				SettingsWidget.close();
				BX.SidePanel.Instance.open(`/crm/company/details/${this.#companyId}/?init_mode=edit&rqedit=y`);
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
								Event.bindOnce(SettingsWidget.getInstance().getWidget().getPopup().getPopupContainer(), 'click', () => {
									this.#requisitesPopup.close();
								});
							}, 0);
						},
					}
				},
			);
		}

		return this.#requisitesPopup;
	}

	#getButtonText(): string
	{
		if (this.#isConnected)
		{
			return Loc.getMessage('INTRANET_SETTINGS_WIDGET_REDIRECT_TO_REQUISITE_BUTTON')
		}

		if (this.#companyId > 0 && this.#requisiteId > 0)
		{
			return Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING')
		}

		return Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_REQUISITE_BUTTON');
	}

	#getRequisiteButton(): BX.UI.Button
	{
		if (!this.#requisiteButton)
		{
			this.#requisiteButton = new BX.UI.Button({
				id: 'requisite-btn',
				text: this.#getButtonText(),
				noCaps: true,
				onclick: this.#handleButtonOnclick.bind(this),
				className: 'ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light',
			});
		}

		return this.#requisiteButton;
	}

	#handleButtonOnclick(): void
	{
		if (this.#isConnected)
		{
			this.#handleOpenRequisite();
		}
		else if (this.#companyId > 0)
		{
			if (this.#requisiteId > 0)
			{
				this.#handleCreateLanding();
			}
			else
			{
				this.#handleEditRequisite();
			}
		}
		else
		{
			this.#handleCreateCompany();
		}
	}

	#handleOpenRequisite(): void
	{
		SettingsWidget.close();
		window.open(this.#publicUrl, '_blank');
	}

	#handleCreateLanding(): void
	{
		this.#getRequisiteButton().setWaiting(true);
		this.#createLanding().then(() => {
			this.#requisitesPopup = null;
			this.#requisiteButton = null;
			this.#updateElement();

			if (!this.#isPublic)
			{
				const errorPopup = new Popup('public-landing-error',
					this.getElement().querySelector('[data-role="requisite-widget-title"]'),
					{
						autoHide: true,
						closeByEsc: true,
						angle: true,
						darkMode: true,
						content: Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING_ERROR'),
						events: {
							onShow: () => {
								setTimeout(() => {
									Event.bindOnce(SettingsWidget.getInstance().getWidget().getPopup().getPopupContainer(), 'click', () => {
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
	}

	#createLanding()
	{
		return new Promise((resolve) => {
			ajax.runComponentAction('bitrix:intranet.settings.widget', 'createRequisiteLanding', { mode: 'class' })
				.then(({ data: { isConnected, isPublic, publicUrl, editUrl } }) => {
					this.#isConnected = isConnected;
					this.#isPublic = isPublic;
					this.#publicUrl = publicUrl;
					this.#editUrl = editUrl;
					resolve();
				})
			;
		});
	}

	#handleEditRequisite(): void
	{
		SettingsWidget.close();
		BX.SidePanel.Instance.open(`/crm/company/details/${this.#companyId}/?init_mode=edit&rqedit=y`);
	}

	#handleCreateCompany(): void
	{
		SettingsWidget.close();
		BX.SidePanel.Instance.open('/crm/company/details/0/?mycompany=y&rqedit=y');
	}

	#getRequisites()
	{
		return new Promise((resolve) => {
			ajax.runComponentAction('bitrix:intranet.settings.widget', 'getRequisites', { mode: 'class' })
				.then(({ data: { requisite } }) => {
					this.#updateOptions(requisite);
					resolve();
				})
		});
	}
}