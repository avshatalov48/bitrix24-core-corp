import { ajax as Ajax, Dom, Event, Loc, Runtime, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Menu, Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import 'ui.buttons';
import 'ui.icon.set';
import { BaseSettingsPage, SettingsSection } from 'ui.form-elements.field';
import { Row } from 'ui.section';
import 'sidepanel';

export type MainpageOptions = {
	urlCreate: ?string,
	urlEdit: ?string,
	urlPublic: ?string,
	urlPartners: ?string,
	urlImport: ?string,
	urlExport: ?string,
	previewImg: ?string,
	isSiteExists: ?boolean,
	isPageExists: ?boolean,
	isPublished: ?boolean,
	isEnable: ?boolean;
	feedbackParams: ?{};
};

export class MainpagePage extends BaseSettingsPage
{
	titlePage: string = '';
	descriptionPage: string = '';

	#urlCreate: ?string = null;
	#urlEdit: ?string = null;
	#urlPublic: ?string = null;
	#urlPartners: ?string = null;
	#urlImport: ?string = null;
	#urlExport: ?string = null;

	#previewImg: ?string = null;
	#title: ?string = null;

	#feedbackParams: ?{} = null;

	#buttonEdit: ?HTMLElement = null;
	#buttonPartners: ?HTMLElement = null;
	#buttonMarket: ?HTMLElement = null;
	#buttonWithdraw: ?HTMLElement = null;
	#buttonPublish: ?HTMLElement = null;
	#mainTemplate: ?HTMLElement = null;
	#secondaryTemplate: ?HTMLElement = null;

	#buttonMainSettings: ?HTMLElement = null;
	#buttonSecondarySettings: ?HTMLElement = null;

	#importPopup: ?Menu = null;
	#exportPopup: ?Menu = null;
	#popupShare: ?Popup = null;
	#popupWithdraw: ?Popup = null;

	#isSiteExists: boolean = false;
	#isPageExists: boolean = false;
	#isPublished: boolean = false;
	#isEnable: boolean = true;

	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_MAINPAGE');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_TITLE_DESCRIPTION_PAGE_MAINPAGE');
	}

	getType(): string
	{
		return 'mainpage';
	}

	appendSections(contentNode: HTMLElement): void
	{
		const options: MainpageOptions = this.getValue('main-page');
		this.#urlCreate = options.urlCreate || null;
		this.#urlEdit = options.urlEdit || null;
		this.#urlPublic = options.urlPublic || null;
		this.#urlPartners = options.urlPartners || null;
		this.#urlImport = options.urlImport || null;
		this.#urlExport = options.urlExport || null;
		this.#previewImg = options.previewImg || null;
		this.#feedbackParams = options.feedbackParams || null;
		this.#isSiteExists = options.isSiteExists ?? false;
		this.#isPageExists = options.isPageExists ?? false;
		this.#isPublished = options.isPublished ?? false;
		this.#title = options.title ?? null;
		this.#isEnable = options.isEnable ?? false;
		const section = new SettingsSection({
			parent: this,
			section: {
				canCollapse: false,
				isOpen: true,
			},
		});

		const secondarySection = new SettingsSection({
			parent: this,
			section: {
				canCollapse: false,
				isOpen: true,
			},
		});

		const content = Tag.render`<div>		
			${this.#getMainTemplate()}		
		</div>`;
		section.getSectionView().append(
			(new Row({
				content: content,
			})).render())
		;

		if (this.#isPageExists)
		{
			const secondaryContent = Tag.render`<div>
				${this.#getSecondaryTemplate()}			
			</div>`;
			secondarySection.getSectionView().append(
				(new Row({
					content: secondaryContent,
				})).render())
			;
		}

		this.#bindButtonEvents();
		this.#bindSliderCloseEvent();

		secondarySection.renderTo(contentNode);
		section.renderTo(contentNode);
	}

	#getMainTemplate(): HTMLElement
	{
		if (!this.#mainTemplate)
		{
			this.#mainTemplate = Tag.render`
				<div class="intranet-settings__main-page-template">
					<div class="intranet-settings__main-page-icon-box">
						<div class="intranet-settings__main-page-icon"></div>
					</div>
					<div class="intranet-settings__main-page-content">
						<ul class="intranet-settings__main-page-list">
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_1')}
								</div>																																
							</li>
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_2')}
								</div>								
							</li>
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_3')}
								</div>
							</li>
						</ul>
						<div class="intranet-settings__main-page-button-box">
							${this.#getButtonCreate()}
							<div class="intranet-settings__main-page-button-box-right">
								${this.#getButtonPartners()}
								${this.#getButtonMainSettings()}
							</div>
						</div>
					</div>
				</div>
			`;
		}

		return this.#mainTemplate;
	}

	#getSecondaryTemplate(): HTMLElement
	{
		if (!this.#secondaryTemplate)
		{
			const previewImg = this.#previewImg
				? Tag.render`<img 
						src="${this.#previewImg}"
						class="intranet-settings__main-page-preview" 
					/>`
				: ''
			;
			this.#secondaryTemplate = Tag.render`
				<div class="intranet-settings__main-page-template --secondary-template">
					<div class="intranet-settings__main-page-preview-box">
						${previewImg}
					</div>
					<div class="intranet-settings__main-page-content">
						<div class="intranet-settings__main-page-title">
							${this.#title ?? ''}
						</div>
						<div class="intranet-settings__main-page-info-template">
							${this.#isPublished ? this.getInfoSuccessTemplate() : this.getInfoTemplate()}
						</div>					
						<div class="intranet-settings__main-page-button-box">
							${this.#getButtonEdit()}
							<div class="intranet-settings__main-page-button-box-right">
								${this.#isPublished && this.#isEnable  ? this.#getButtonWithdraw() : this.#getButtonPublish()}
								${this.#getButtonSecondarySettings()}
							</div>
						</div>
					</div>
				</div>			
			`;
		}

		return this.#secondaryTemplate;
	}

	getInfoTemplate(): HTMLElement
	{
		this.infoTemplate = Tag.render`
			<div class="intranet-settings__main-page-info">
				<div class="intranet-settings__main-page-info-title">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_TITLE')}
				</div>
				<div class="intranet-settings__main-page-info-subtitle">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUBTITLE')}
					<div class="ui-icon-set --help intranet-settings__main-page-info-help"></div>
				</div>
			</div>
		`;

		Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', (event) => {
			const width = this.infoTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
			this.warningHintPopup = new Popup({
				angle: true,
				autoHide: true,
				content: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_HINT_WARNING'),
				cacheable: false,
				animation: 'fading-slide',
				bindElement: event.target,
				offsetTop: 0,
				offsetLeft: parseInt(width/2),
				bindOptions: {
					position: 'top',
				},
				darkMode: true,
			});

			this.warningHintPopup.show();
		});
		Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', () => {
			if (this.warningHintPopup)
			{
				setTimeout(() => {
					this.warningHintPopup.destroy();
					this.warningHintPopup = null;
				}, 300);
			}
		});

		return this.infoTemplate;
	}

	getInfoSuccessTemplate(): HTMLElement
	{
		this.infoSuccessTemplate = Tag.render`
			<div class="intranet-settings__main-page-info --success">
				<div class="intranet-settings__main-page-info-title">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_TITLE')}				
				</div>
				<div class="intranet-settings__main-page-info-subtitle">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_SUBTITLE')}
					<div class="ui-icon-set --help intranet-settings__main-page-info-help"></div>
				</div>
			</div>
		`;

		Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', (event) => {
			const width = this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
			this.successHintPopup = new Popup({
				angle: true,
				autoHide: true,
				content: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_HINT_SUCCESS'),
				cacheable: false,
				animation: 'fading-slide',
				bindElement: event.target,
				offsetTop: 0,
				offsetLeft: parseInt(width/2),
				bindOptions: {
					position: 'top',
				},
				darkMode: true,
			});

			this.successHintPopup.show();
		});
		Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', () => {
			if (this.successHintPopup)
			{
				setTimeout(() => {
					this.successHintPopup.destroy();
					this.successHintPopup = null;
				}, 300);
			}
		});

		return this.infoSuccessTemplate;
	}

	#getButtonMainSettings(): HTMLElement
	{
		if (!this.#buttonMainSettings)
		{
			this.#buttonMainSettings = Tag.render`
				<button class="intranet-settings-btn-settings">
					<div class="ui-icon-set --more"></div>
				</button>
			`;
		}

		return this.#buttonMainSettings;
	}

	#getButtonSecondarySettings(): HTMLElement
	{
		if (!this.#buttonSecondarySettings)
		{
			this.#buttonSecondarySettings = Tag.render`
			<button class="intranet-settings-btn-settings">
				<div class="ui-icon-set --more"></div>
			</button>`;
		}

		return this.#buttonSecondarySettings;
	}

	#showImportPopup(): void
	{
		if (!this.#importPopup)
		{
			const htmlContent = this.#isEnable
				? Tag.render`<span>${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP')}</span>`
				: Tag.render`<span class="intranet-settings-mp-popup-item">${Loc.getMessage(
					'INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP')} ${this.renderLockElement()}</span>`
			;
			this.#importPopup = new Menu({
				angle: true,
				animation: 'fading-slide',
				bindElement: this.#buttonMainSettings,
				className: this.#isEnable ? '' : '--disabled',
				items: [
					{
						id: 'importPopup',
						html: htmlContent,
						onclick: this.#showImportSlider.bind(this),
					},
				],
				offsetLeft: 20,
				events: {
					onPopupClose: () => {
					},
					onPopupShow: () => {
					},
				},
			});
		}

		this.#importPopup?.show();
	}

	#showExportPopup(): void
	{
		if (!this.#exportPopup)
		{
			const htmlContent = this.#isEnable
				? Tag.render`<span>${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP')}</span>`
				: Tag.render`<span class="intranet-settings-mp-popup-item --disabled">${Loc.getMessage(
					'INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP')} ${this.renderLockElement()}</span>`
			;
			this.#exportPopup = new Menu({
				angle: true,
				animation: 'fading-slide',
				bindElement: this.#buttonSecondarySettings,
				className: this.#isEnable ? '' : '--disabled',
				items: [
					{
						id: 'exportPopup',
						html: htmlContent,
						onclick: this.#showExportSlider.bind(this),
					},
				],
				offsetLeft: 20,
				events: {
					onPopupClose: () => {
					},
					onPopupShow: () => {
					},
				},
			});
		}

		this.#exportPopup?.show();
	}

	#showImportSlider()
	{
		if (!this.#isEnable)
		{
			BX.UI.InfoHelper.show("limit_office_vibe");
			return;
		}

		if (typeof BX.SidePanel === 'undefined')
		{
			return;
		}

		if (typeof BX.SidePanel!== 'undefined' && this.#urlImport)
		{
			const onOK = () => {
				BX.SidePanel.Instance.open(
					this.#urlImport,
					{
						width: 491,
						allowChangeHistory: false,
						cacheable: false,
						data: {
							rightBoundary: 0,
						},
					},
				);
			};

			if (!this.#isPageExists)
			{
				onOK();
				return;
			}

			BX.Runtime.loadExtension('ui.dialogs.messagebox').then(() =>
			{
				const messageBox = new BX.UI.Dialogs.MessageBox({
					message: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_MESSAGE'),
					title: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_TITLE'),
					buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
					okCaption: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_OK_BUTTON'),
					cancelCaption: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_CANCEL_BUTTON'),
					onOk: () => {
						onOK();
						return true;
					},
					onCancel: () => {
						return true;
					},
				});
				messageBox.show();
				if (messageBox.popupWindow && messageBox.popupWindow.popupContainer)
				{
					messageBox.popupWindow.popupContainer.classList.add('intranet-settings__main-page-popup');
				}
			});
		}
	}

	#showExportSlider()
	{
		if (!this.#isEnable)
		{
			BX.UI.InfoHelper.show("limit_office_vibe");
			return;
		}

		if (typeof BX.SidePanel === 'undefined')
		{
			return;
		}

		if (
			typeof BX.SidePanel!== 'undefined'
			&& this.#urlExport
		)
		{
			BX.SidePanel.Instance.open(
				this.#urlExport,
				{
					width: 491,
					allowChangeHistory: false,
					cacheable: false,
					data: {
						rightBoundary: 0,
					},
				},
			);
		}
	}

	#showSharePopup(): void
	{
		if (!this.#popupShare)
		{
			this.#popupShare = new Popup({
				titleBar: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_TITLE_MSGVER_1'),
				content: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_CONTENT'),
				width: 350,
				closeIcon: true,
				closeByEsc: true,
				animation: 'fading-slide',
				buttons: [
					new Button({
						text: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_BTN_CONFIRM'),
						color: Button.Color.PRIMARY,
						onclick: () => {
							const newTemplate = this.getInfoSuccessTemplate();
							const wrapper = this.#secondaryTemplate.querySelector('.intranet-settings__main-page-info-template');
							const innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info:not(.--success)');

							Dom.replace(innerWrapper, newTemplate);
							Ajax.runAction('intranet.mainpage.publish').then(() => {
								this.emit('publish');
								if (this.#urlPublic)
								{
									this.#isPublished = true;
								}
							});

							this.#popupShare.close();
							BX.UI.Analytics.sendData({
								tool: 'landing',
								category: 'vibe',
								event: 'publish_page',
								c_sub_section: 'from_settings',
								status: 'success',
							});
						},
					}),
					new Button({
						text: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_POPUP_BTN_CANCEL'),
						color: Button.Color.LIGHT_BORDER,
						onclick: () => {
							this.#popupShare.close();
						},
					}),
				],
				events: {
					onClose: () => {

					},
				},
			});

			this.#popupShare?.show();
		}
		else
		{
			this.#popupShare?.show();
		}
	}

	#showWithdrawPopup(): void
	{
		if (!this.#popupWithdraw)
		{
			this.#popupWithdraw = new Popup({
				titleBar: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_TITLE'),
				content: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_CONTENT'),
				width: 350,
				closeIcon: true,
				closeByEsc: true,
				animation: 'fading-slide',
				buttons: [
					new Button({
						text: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_BTN_CONFIRM'),
						color: Button.Color.DANGER_DARK,
						onclick: () => {
							const newTemplate = this.getInfoTemplate();
							const wrapper = this.#secondaryTemplate.querySelector('.intranet-settings__main-page-info-template');
							const innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info');

							Dom.replace(innerWrapper, newTemplate);
							Ajax.runAction('intranet.mainpage.withdraw').then(() => {
								this.emit('withdraw');
								this.#isPublished = false;
							});

							this.#popupWithdraw.close();
							BX.UI.Analytics.sendData({
								tool: 'landing',
								category: 'vibe',
								event: 'unpublish_page',
								c_sub_section: 'from_settings',
							});
						},
					}),
					new Button({
						text: Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_POPUP_BTN_CANCEL'),
						color: Button.Color.LIGHT_BORDER,
						onclick: () => {
							this.#popupWithdraw.close();
						},
					}),
				],
				events: {
					onClose: () => {

					},
				},
			});

			this.#popupWithdraw?.show();
		}
		else
		{
			this.#popupWithdraw?.show();
		}
	}

	#getButtonEdit(): ?HTMLElement
	{
		if (!this.#urlEdit)
		{
			return null;
		}

		if (!this.#buttonEdit)
		{
			this.#buttonEdit = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-blue">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_EDIT')}
				</button>`;
		}

		return this.#buttonEdit;
	}

	#getButtonPublish(): HTMLElement
	{
		if (!this.#buttonPublish)
		{
			const renderNode = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps
						${this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC')}
				</button>
			`;
			const renderNodeLock = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled
						${this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC')}
					${this.renderLockElement()}
				</button>
			`;
			this.#buttonPublish = this.#isEnable ? renderNode : renderNodeLock;
		}

		return this.#buttonPublish;
	}

	#getButtonWithdraw(): HTMLElement
	{
		if (!this.#buttonWithdraw)
		{
			const renderNode = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps
						${this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_UNPUBLIC')}
				</button>
			`;
			const renderNodeLock = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled
						${this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_UNPUBLIC')}
					${this.renderLockElement()}
				</button>
			`;
			this.#buttonWithdraw = this.#isEnable ? renderNode : renderNodeLock;
		}

		return this.#buttonWithdraw;
	}

	#getButtonPartners(): ?HTMLElement
	{
		if (!this.#urlPartners)
		{
			return null;
		}

		if (!this.#buttonPartners)
		{
			this.#buttonPartners = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-gray">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS')}
				</button>
			`;
		}

		return this.#buttonPartners;
	}

	#getButtonCreate(): ?HTMLElement
	{
		if (!this.#urlCreate)
		{
			return null;
		}

		if (!this.#buttonMarket)
		{
			const renderNode = Tag.render`			
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps 
						${!this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET')}
				</button>
			`;
			const renderNodeLock = Tag.render`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled
						${!this.#isPageExists ? 'ui-btn-primary' : '--light-blue'}">
					${Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET')}
					${this.renderLockElement()}
				</button>
			`;
			this.#buttonMarket = this.#isEnable ? renderNode : renderNodeLock;
		}

		return this.#buttonMarket;
	}

	#bindButtonEvents()
	{
		if (typeof BX.SidePanel === 'undefined')
		{
			return;
		}

		Event.bind(this.#getButtonMainSettings(), 'click', this.#showImportPopup.bind(this));
		Event.bind(this.#getButtonSecondarySettings(), 'click', this.#showExportPopup.bind(this));

		if (this.#getButtonCreate())
		{
			Event.bind(this.#getButtonCreate(), 'click', () => {
				if (this.#isEnable)
				{
					BX.SidePanel.Instance.open(this.#urlCreate);
				}
				else
				{
					BX.UI.InfoHelper.show("limit_office_vibe");
				}

				BX.UI.Analytics.sendData({
					tool: 'landing',
					category: 'vibe',
					event: 'open_market',
					status: this.#isEnable ? 'success' : 'error_limit',
				});
			});
		}

		if (this.#getButtonEdit())
		{
			Event.bind(this.#getButtonEdit(), 'click', () => {
				BX.SidePanel.Instance.open(
					this.#urlEdit,
					{
						customLeftBoundary: 66,
						events: {
							onCloseComplete: () => {
								if (this.#urlPublic)
								{
									window.top.location = this.#urlPublic;
								}
							}
						},
					},
				);
				BX.UI.Analytics.sendData({
					tool: 'landing',
					category: 'vibe',
					event: 'open_editor',
				});
			});
		}

		if (this.#getButtonPartners())
		{
			Event.bind(this.#getButtonPartners(), 'click', () => {
				if (!this.#feedbackParams)
				{
					return;
				}

				Runtime.loadExtension('ui.feedback.form').then(() => {
					this.#feedbackParams.title = Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS');
					BX.UI.Feedback.Form.open(this.#feedbackParams);
				});
			});

		}

		this.subscribe('publish', () => {
			Dom.replace(this.#getButtonPublish(), this.#getButtonWithdraw());
		});
		this.subscribe('withdraw', () => {
			Dom.replace(this.#getButtonWithdraw(), this.#getButtonPublish());
		});
		Event.bind(this.#getButtonPublish(), 'click', () => {
			if (!this.#isEnable)
			{
				BX.UI.InfoHelper.show("limit_office_vibe");
				BX.UI.Analytics.sendData({
					tool: 'landing',
					category: 'vibe',
					event: 'publish_page',
					c_sub_section: 'from_settings',
					status: 'error_limit',
				});
				return;
			}

			this.#showSharePopup()
		});
		Event.bind(this.#getButtonWithdraw(), 'click', this.#showWithdrawPopup.bind(this));
	}

	#bindSliderCloseEvent()
	{
		const isPublishedBefore = this.#isPublished;

		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			'SidePanel.Slider:onClose',
			() => {
				if (this.#isPublished !== isPublishedBefore)
				{
					const location = this.#isPublished
						? this.#urlPublic
						: '/'
					;
					window.top.location = location;
				}
			}
		);
	}

	renderLockElement(): HTMLElement
	{
		return Tag.render`<span class="intranet-settings-mp-icon ui-icon-set --lock"></span>`;
	}
}
