import {Tag, Event, Type, Reflection, ajax as Ajax} from 'main.core';
import {PopupMenu, PopupMenuWindow, PopupWindow, PopupWindowButton} from 'main.popup';

import {Manager} from 'salescenter.manager';

import 'marketplace';
import 'applayout';

const namespace = Reflection.namespace('BX.Salescenter');

class ControlPanel
{
	static shopRoot = '/shop/settings/';
	static commonConnectionDependentTiles = [];
	static pageMenuTiles = [];
	static paymentSystemsTile;
	static userConsentTile;

	static init(options: {
		shopRoot: string,
	})
	{
		if(Type.isPlainObject(options))
		{
			this.constructor.shopRoot = options.shopRoot;
		}

		Event.ready(() =>
		{
			if (BX.SidePanel.Instance)
			{
				BX.SidePanel.Instance.bindAnchors(top.BX.clone({
					rules: [
						{
							condition: [
								this.constructor.shopRoot + "sale_delivery_service_edit/",
								this.constructor.shopRoot + "sale_pay_system_edit/"
							],
							handler: this.constructor.adjustSidePanelOpener
						},
						{
							condition: [
								"/shop/orders/details/(\\d+)/",
								"/shop/orders/payment/details/(\\d+)/",
								"/shop/orders/shipment/details/(\\d+)/"
							]
						},
						{
							condition: [
								"/crm/configs/sale/"
							]
						}
					]
				}));
			}

			var adminSidePanel = top.BX.adminSidePanel || BX.adminSidePanel;
			if (adminSidePanel)
			{
				if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], adminSidePanel))
				{
					top.window["adminSidePanel"] = new adminSidePanel({
						publicMode: true
					});
				}
			}
		});
	}

	static addCommonConnectionDependentTile(tile: BaseItem)
	{
		ControlPanel.commonConnectionDependentTiles.push(tile);
	}

	static addPageMenuTile(tile: BaseItem)
	{
		ControlPanel.pageMenuTiles.push(tile);
	}

	static adjustSidePanelOpener(event, link)
	{
		if (BX.SidePanel.Instance)
		{
			const isSidePanelParams = (link.url.indexOf("IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER") >= 0);
			if (!isSidePanelParams || (isSidePanelParams && !BX.SidePanel.Instance.getTopSlider()))
			{
				event.preventDefault();
				link.url =	BX.util.add_url_param(link.url, {"publicSidePanel": "Y"});
				BX.SidePanel.Instance.open(link.url, {
					allowChangeHistory: false
				});
			}
		}
	}

	static connectShop(id: string)
	{
		Manager.startConnection({
			context: id,
		}).then(() =>
		{
			Manager.loadConfig().then((result) =>
			{
				if(result.isSiteExists)
				{
					Manager.showAfterConnectPopup();
					ControlPanel.commonConnectionDependentTiles.forEach((item) =>
					{
						item.data.active = true;
						item.dropMenu();
						item.rerender();
					})
				}
			});
		});
	}

	static paymentSystemsTileClick()
	{
		if(ControlPanel.paymentSystemsTile)
		{
			ControlPanel.paymentSystemsTile.onClick();
		}
	}

	static closeMenu()
	{
		const menu = PopupMenu.getCurrentMenu();
		if(menu)
		{
			menu.destroy();
		}
	}

	static dropPageMenus()
	{
		ControlPanel.pageMenuTiles.forEach((item: BaseItem) =>
		{
			item.dropMenu();
		});
	}

	static reloadUserConsentTile()
	{
		if(ControlPanel.userConsentTile)
		{
			ControlPanel.userConsentTile.reloadTile();
		}
	}
}

class BaseItem extends BX.TileGrid.Item
{
	constructor(options)
	{
		super(options);

		this.title = options.title;
		this.image = options.image;
		this.data = options.data || {};

		if(this.isDependsOnConnection())
		{
			ControlPanel.addCommonConnectionDependentTile(this);
		}
		if(this.hasPagesMenu())
		{
			ControlPanel.addPageMenuTile(this);
		}
		if(this.id === 'payment-systems')
		{
			ControlPanel.paymentSystemsTile = this;
		}
		else if (this.id === 'userconsent')
		{
			ControlPanel.userConsentTile = this;
		}
	}

	isDependsOnConnection(): boolean
	{
		return (this.data.isDependsOnConnection === true);
	}

	hasPagesMenu(): boolean
	{
		return (this.data.hasPagesMenu === true);
	}

	getContent(): Element
	{
		this.layout.innerContent = Tag.render`<div class="salescenter-item ${this.getAdditionalContentClass()}" onclick="${this.onClick.bind(this)}" style="${this.getContentStyles()}">
			<div class="salescenter-item-content">
				${this.getImage()}
				${this.getTitle()}
				${(this.isActive() ? this.getStatus() : '')}
				${this.getLabel()}
			</div>
		</div>`;

		return this.layout.innerContent;
	}

	rerender()
	{
		if(!this.layout.innerContent)
		{
			return;
		}
		const contentNode = this.layout.innerContent.parentNode;
		contentNode.removeChild(this.layout.innerContent);
		contentNode.appendChild(this.getContent());
	}

	getAdditionalContentClass(): string
	{
		if(this.isActive())
		{
			return 'salescenter-item-selected';
		}

		return '';
	}

	isActive(): boolean
	{
		return (this.data.active === true);
	}

	getLoadMenuItemsAction(): ?string
	{
		return null;
	}

	onClick()
	{
		if(!this.isActive())
		{
			ControlPanel.connectShop(this.id);
		}
		else
		{
			let menu = this.getMenuItems();
			if(!menu)
			{
				this.reloadTile(true).then((response) =>
				{
					menu = this.getMenuItems();
					if(this.isActive() && menu)
					{
						this.showMenu();
					}
					else
					{
						this.onClick();
					}
				});
			}
			else
			{
				this.showMenu();
			}
		}
	}

	getContentStyles(): string
	{
		let styles = '';
		if(this.isActive() && this.data.activeColor && !this.isMarketplaceAll())
		{
			styles = 'background-color: ' + this.data.activeColor;
		}

		return styles;
	}

	getImage()
	{
		let path = '';
		let className = 'salescenter-item-image';
		if(this.image)
		{
			path = this.image;
		}
		if(this.isActive() && this.data.activeImage)
		{
			path = this.data.activeImage;
		}
		path = encodeURI(path);

		if (this.isMarketplaceAll()
			&& this.data.hasOwnProperty('hasOwnIcon')
			&& this.data.hasOwnIcon
		)
		{
			className = 'salescenter-marketplace-item-image';
		}

		return Tag.render`<div class="${className}" style="background-image:url(${path})"></div>`;
	}

	getStatus(): Element
	{
		return Tag.render`<div class="salescenter-item-status-selected"></div>`;
	}

	getLabel(): ?Element
	{
		if (this.needNewLabel())
		{
			let className = 'salescenter-item-label-new';
			let classNameText = 'salescenter-item-label-new-text';
			if (this.isActive() && this.data.hasOwnProperty('activeColor'))
			{
				className = 'salescenter-item-label-new-active';
				classNameText = 'salescenter-item-label-new-text-active';
			}

			return Tag.render`<div class="${className}"><div class="${classNameText}">${BX.message('SALESCENTER_CONTROL_PANEL_ITEM_LABEL_NEW')}</div></div>`;
		}

		return '';
	}

	getTitle(): Element
	{
		let className = this.isMarketplaceAll() ? 'salescenter-marketplace-item-title' : 'salescenter-item-title';
		return Tag.render`<div class="${className}">${this.title}</div>`;
	}

	getMenuItems(): ?Array
	{
		return this.data.menu;
	}

	hasMenu(): boolean
	{
		return Type.isArrayFilled(this.data.menu);
	}

	dropMenu(): BaseItem
	{
		delete this.data.menu;

		return this;
	}

	showMenu()
	{
		PopupMenu.show(this.id + '-menu', this.layout.container, this.getMenuItems(), {
			offsetLeft: 0,
			offsetTop: 0,
			closeByEsc: true,
			className: 'salescenter-panel-menu'
		});
	}

	getUrl(): ?string
	{
		if(Type.isString(this.data.url))
		{
			return this.data.url;
		}

		return null;
	}

	getSliderOptions()
	{
		if (Type.isPlainObject(this.data.sliderOptions))
		{
			return this.data.sliderOptions;
		}

		return null;
	}

	reloadTile(isClick: boolean = false)
	{
		return new Promise((resolve) =>
		{
			if(Type.isString(this.data.reloadAction))
			{
				Ajax.runComponentAction(
					'bitrix:salescenter.control_panel',
					this.data.reloadAction,
					{
						analyticsLabel: isClick ? 'salescenterControlPanelReloadTile' : null,
						getParameters: isClick ? {
							tileId: this.id,
						} : null,
						mode: 'class',
						data: {
							id: this.id,
						}
					}
				).then((response) =>
				{
					if(!Type.isNil(response.data.active))
					{
						this.data.active = response.data.active;
					}
					if(!Type.isNil(response.data.menu))
					{
						this.data.menu = response.data.menu;
					}

					this.rerender();
					resolve();
				});
			}
			else
			{
				resolve();
			}
		});
	}

	isMarketplaceAll()
	{
		return this.data.hasOwnProperty('itemSubType')
			&& this.data.itemSubType === 'marketplaceApp';
	}

	needNewLabel()
	{
		return this.data.hasOwnProperty('label')
			&& this.data.label === 'new';
	}

	openRestAppLayout(applicationId, appCode)
	{
		Ajax.runComponentAction(
			"bitrix:salescenter.control_panel",
			"getRestApp",
			{
				data: {
					code: appCode
				}
			}
		).then(function(response)
		{
			let app = response.data;
			if(app.TYPE === "A")
			{
				this.showRestApplication(appCode);
			}
			else
			{
				BX.rest.AppLayout.openApplication(applicationId);
			}
		}.bind(this)).catch(function(response)
		{
			this.restAppErrorPopup(" ", response.errors.pop().message);
		}.bind(this));
	}

	showRestApplication(appCode)
	{
		let applicationUrlTemplate = "/marketplace/detail/#app#/";
		let url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
		Manager.openSlider(url).then(this.reloadTile.bind(this));
	}

	restAppErrorPopup(title, text)
	{
		let popup = new BX.PopupWindow('rest-app-error-alert', null, {
			closeIcon: true,
			closeByEsc: true,
			autoHide: false,
			titleBar: title,
			content: text,
			zIndex: 16000,
			overlay: {
				color: 'gray',
				opacity: 30
			},
			buttons: [
				new BX.PopupWindowButton({
					'id': 'close',
					'text': BX.message('SALESCENTER_CONTROL_PANEL_POPUP_CLOSE'),
					'events': {
						'click': function() {
							popup.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: function() {
					popup = null;
				}
			}
		});

		popup.show();
	}
}

class PaymentItem extends BaseItem
{
	dropMenu(): BaseItem
	{
		return this;
	}

	onClick()
	{
		if (this.isMarketplaceAll())
		{
			if (this.data.active)
			{
				this.openRestAppLayout(this.data.appId, this.data.code);
			}
			else
			{
				this.showRestApplication(this.data.code);
			}
		}
		else if (this.opensSlider())
		{
			const url = this.getUrl();
			const options = this.getSliderOptions();
			if(url)
			{
				Manager.openSlider(url, options).then(this.reloadTile.bind(this));
			}
		}
		else if (this.isRecommendTile())
		{
			Manager.openFeedbackPayOrderForm();
		}
		else
		{
			super.onClick();
		}
	}

	opensSlider(): boolean
	{
		const tileHasSlider =
			this.isCrmStoreTile()
			|| this.isCrmWithEshopTile()
			|| this.isCrmFormTile()
			|| this.isTerminalTile()
		;
		const tileHasUrl = this.getUrl();
		const tileHasMenu = this.hasMenu();

		return tileHasSlider && tileHasUrl && !tileHasMenu;
	}

	isRecommendTile()
	{
		return this.id === 'recommendation';
	}

	isCrmStoreTile()
	{
		return this.id === 'crmstore';
	}

	isCrmWithEshopTile()
	{
		return this.id === 'crm-with-eshop';
	}

	isCrmFormTile()
	{
		return this.id && this.id === 'crmform';
	}

	isTerminalTile()
	{
		return this.id && this.id === 'terminal';
	}
}

class PaymentSystemItem extends BaseItem
{
	onClick()
	{
		if(this.isDependsOnConnection())
		{
			super.onClick();
		}
		else if(this.id === 'userconsent')
		{
			if(!this.isActive())
			{
				const url = this.getUrl();
				if(url)
				{
					Manager.openSlider(url).then(this.reloadTile.bind(this));
				}
			}
			else
			{
				this.showMenu();
			}
		}
		else
		{
			const url = this.getUrl();
			if(url)
			{
				Manager.openSlider(url).then(this.reloadTile.bind(this));
			}
		}
	}
}

namespace.ControlPanel = ControlPanel;
namespace.PaymentItem = PaymentItem;
namespace.PaymentSystemItem = PaymentSystemItem;
