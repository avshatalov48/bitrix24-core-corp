(function (exports,main_core,main_popup,salescenter_manager) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	var namespace = main_core.Reflection.namespace('BX.Salescenter');

	var ControlPanel = /*#__PURE__*/function () {
	  function ControlPanel() {
	    babelHelpers.classCallCheck(this, ControlPanel);
	  }

	  babelHelpers.createClass(ControlPanel, null, [{
	    key: "init",
	    value: function init(options) {
	      var _this = this;

	      if (main_core.Type.isPlainObject(options)) {
	        this.constructor.shopRoot = options.shopRoot;
	      }

	      main_core.Event.ready(function () {
	        if (BX.SidePanel.Instance) {
	          BX.SidePanel.Instance.bindAnchors(top.BX.clone({
	            rules: [{
	              condition: [_this.constructor.shopRoot + "sale_delivery_service_edit/", _this.constructor.shopRoot + "sale_pay_system_edit/"],
	              handler: _this.constructor.adjustSidePanelOpener
	            }, {
	              condition: ["/shop/orders/details/(\\d+)/", "/shop/orders/payment/details/(\\d+)/", "/shop/orders/shipment/details/(\\d+)/"]
	            }, {
	              condition: ["/crm/configs/sale/"]
	            }]
	          }));
	        }

	        var adminSidePanel = top.BX.adminSidePanel || BX.adminSidePanel;

	        if (adminSidePanel) {
	          if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], adminSidePanel)) {
	            top.window["adminSidePanel"] = new adminSidePanel({
	              publicMode: true
	            });
	          }
	        }
	      });
	    }
	  }, {
	    key: "addCommonConnectionDependentTile",
	    value: function addCommonConnectionDependentTile(tile) {
	      ControlPanel.commonConnectionDependentTiles.push(tile);
	    }
	  }, {
	    key: "addPageMenuTile",
	    value: function addPageMenuTile(tile) {
	      ControlPanel.pageMenuTiles.push(tile);
	    }
	  }, {
	    key: "adjustSidePanelOpener",
	    value: function adjustSidePanelOpener(event, link) {
	      if (BX.SidePanel.Instance) {
	        var isSidePanelParams = link.url.indexOf("IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER") >= 0;

	        if (!isSidePanelParams || isSidePanelParams && !BX.SidePanel.Instance.getTopSlider()) {
	          event.preventDefault();
	          link.url = BX.util.add_url_param(link.url, {
	            "publicSidePanel": "Y"
	          });
	          BX.SidePanel.Instance.open(link.url, {
	            allowChangeHistory: false
	          });
	        }
	      }
	    }
	  }, {
	    key: "connectShop",
	    value: function connectShop(id) {
	      salescenter_manager.Manager.startConnection({
	        context: id
	      }).then(function () {
	        salescenter_manager.Manager.loadConfig().then(function (result) {
	          if (result.isSiteExists) {
	            salescenter_manager.Manager.showAfterConnectPopup();
	            ControlPanel.commonConnectionDependentTiles.forEach(function (item) {
	              item.data.active = true;
	              item.dropMenu();
	              item.rerender();
	            });
	          }
	        });
	      });
	    }
	  }, {
	    key: "paymentSystemsTileClick",
	    value: function paymentSystemsTileClick() {
	      if (ControlPanel.paymentSystemsTile) {
	        ControlPanel.paymentSystemsTile.onClick();
	      }
	    }
	  }, {
	    key: "closeMenu",
	    value: function closeMenu() {
	      var menu = main_popup.PopupMenu.getCurrentMenu();

	      if (menu) {
	        menu.destroy();
	      }
	    }
	  }, {
	    key: "dropPageMenus",
	    value: function dropPageMenus() {
	      ControlPanel.pageMenuTiles.forEach(function (item) {
	        item.dropMenu();
	      });
	    }
	  }, {
	    key: "reloadUserConsentTile",
	    value: function reloadUserConsentTile() {
	      if (ControlPanel.userConsentTile) {
	        ControlPanel.userConsentTile.reloadTile();
	      }
	    }
	  }]);
	  return ControlPanel;
	}();

	babelHelpers.defineProperty(ControlPanel, "shopRoot", '/shop/settings/');
	babelHelpers.defineProperty(ControlPanel, "commonConnectionDependentTiles", []);
	babelHelpers.defineProperty(ControlPanel, "pageMenuTiles", []);

	var BaseItem = /*#__PURE__*/function (_BX$TileGrid$Item) {
	  babelHelpers.inherits(BaseItem, _BX$TileGrid$Item);

	  function BaseItem(options) {
	    var _this2;

	    babelHelpers.classCallCheck(this, BaseItem);
	    _this2 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BaseItem).call(this, options));
	    _this2.title = options.title;
	    _this2.image = options.image;
	    _this2.data = options.data || {};

	    if (_this2.isDependsOnConnection()) {
	      ControlPanel.addCommonConnectionDependentTile(babelHelpers.assertThisInitialized(_this2));
	    }

	    if (_this2.hasPagesMenu()) {
	      ControlPanel.addPageMenuTile(babelHelpers.assertThisInitialized(_this2));
	    }

	    if (_this2.id === 'payment-systems') {
	      ControlPanel.paymentSystemsTile = babelHelpers.assertThisInitialized(_this2);
	    } else if (_this2.id === 'userconsent') {
	      ControlPanel.userConsentTile = babelHelpers.assertThisInitialized(_this2);
	    }

	    return _this2;
	  }

	  babelHelpers.createClass(BaseItem, [{
	    key: "isDependsOnConnection",
	    value: function isDependsOnConnection() {
	      return this.data.isDependsOnConnection === true;
	    }
	  }, {
	    key: "hasPagesMenu",
	    value: function hasPagesMenu() {
	      return this.data.hasPagesMenu === true;
	    }
	  }, {
	    key: "getContent",
	    value: function getContent() {
	      this.layout.innerContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"salescenter-item ", "\" onclick=\"", "\" style=\"", "\">\n\t\t\t<div class=\"salescenter-item-content\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>"])), this.getAdditionalContentClass(), this.onClick.bind(this), this.getContentStyles(), this.getImage(), this.getTitle(), this.isActive() ? this.getStatus() : '', this.getLabel());
	      return this.layout.innerContent;
	    }
	  }, {
	    key: "rerender",
	    value: function rerender() {
	      if (!this.layout.innerContent) {
	        return;
	      }

	      var contentNode = this.layout.innerContent.parentNode;
	      contentNode.removeChild(this.layout.innerContent);
	      contentNode.appendChild(this.getContent());
	    }
	  }, {
	    key: "getAdditionalContentClass",
	    value: function getAdditionalContentClass() {
	      if (this.isActive()) {
	        return 'salescenter-item-selected';
	      }

	      return '';
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.data.active === true;
	    }
	  }, {
	    key: "getLoadMenuItemsAction",
	    value: function getLoadMenuItemsAction() {
	      return null;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      var _this3 = this;

	      if (!this.isActive()) {
	        ControlPanel.connectShop(this.id);
	      } else {
	        var menu = this.getMenuItems();

	        if (!menu) {
	          this.reloadTile(true).then(function (response) {
	            menu = _this3.getMenuItems();

	            if (_this3.isActive() && menu) {
	              _this3.showMenu();
	            } else {
	              _this3.onClick();
	            }
	          });
	        } else {
	          this.showMenu();
	        }
	      }
	    }
	  }, {
	    key: "getContentStyles",
	    value: function getContentStyles() {
	      var styles = '';

	      if (this.isActive() && this.data.activeColor && !this.isMarketplaceAll()) {
	        styles = 'background-color: ' + this.data.activeColor;
	      }

	      return styles;
	    }
	  }, {
	    key: "getImage",
	    value: function getImage() {
	      var path = '';
	      var className = 'salescenter-item-image';

	      if (this.image) {
	        path = this.image;
	      }

	      if (this.isActive() && this.data.activeImage) {
	        path = this.data.activeImage;
	      }

	      path = encodeURI(path);

	      if (this.isMarketplaceAll() && this.data.hasOwnProperty('hasOwnIcon') && this.data.hasOwnIcon) {
	        className = 'salescenter-marketplace-item-image';
	      }

	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\" style=\"background-image:url(", ")\"></div>"])), className, path);
	    }
	  }, {
	    key: "getStatus",
	    value: function getStatus() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"salescenter-item-status-selected\"></div>"])));
	    }
	  }, {
	    key: "getLabel",
	    value: function getLabel() {
	      if (this.needNewLabel()) {
	        var className = 'salescenter-item-label-new';
	        var classNameText = 'salescenter-item-label-new-text';

	        if (this.isActive() && this.data.hasOwnProperty('activeColor')) {
	          className = 'salescenter-item-label-new-active';
	          classNameText = 'salescenter-item-label-new-text-active';
	        }

	        return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"><div class=\"", "\">", "</div></div>"])), className, classNameText, BX.message('SALESCENTER_CONTROL_PANEL_ITEM_LABEL_NEW'));
	      }

	      return '';
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var className = this.isMarketplaceAll() ? 'salescenter-marketplace-item-title' : 'salescenter-item-title';
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\">", "</div>"])), className, this.title);
	    }
	  }, {
	    key: "getMenuItems",
	    value: function getMenuItems() {
	      return this.data.menu;
	    }
	  }, {
	    key: "hasMenu",
	    value: function hasMenu() {
	      return main_core.Type.isArrayFilled(this.data.menu);
	    }
	  }, {
	    key: "dropMenu",
	    value: function dropMenu() {
	      delete this.data.menu;
	      return this;
	    }
	  }, {
	    key: "showMenu",
	    value: function showMenu() {
	      main_popup.PopupMenu.show(this.id + '-menu', this.layout.container, this.getMenuItems(), {
	        offsetLeft: 0,
	        offsetTop: 0,
	        closeByEsc: true,
	        className: 'salescenter-panel-menu'
	      });
	    }
	  }, {
	    key: "getUrl",
	    value: function getUrl() {
	      if (main_core.Type.isString(this.data.url)) {
	        return this.data.url;
	      }

	      return null;
	    }
	  }, {
	    key: "getSliderOptions",
	    value: function getSliderOptions() {
	      if (main_core.Type.isPlainObject(this.data.sliderOptions)) {
	        return this.data.sliderOptions;
	      }

	      return null;
	    }
	  }, {
	    key: "reloadTile",
	    value: function reloadTile() {
	      var _this4 = this;

	      var isClick = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      return new Promise(function (resolve) {
	        if (main_core.Type.isString(_this4.data.reloadAction)) {
	          main_core.ajax.runComponentAction('bitrix:salescenter.control_panel', _this4.data.reloadAction, {
	            analyticsLabel: isClick ? 'salescenterControlPanelReloadTile' : null,
	            getParameters: isClick ? {
	              tileId: _this4.id
	            } : null,
	            mode: 'class',
	            data: {
	              id: _this4.id
	            }
	          }).then(function (response) {
	            if (!main_core.Type.isNil(response.data.active)) {
	              _this4.data.active = response.data.active;
	            }

	            if (!main_core.Type.isNil(response.data.menu)) {
	              _this4.data.menu = response.data.menu;
	            }

	            _this4.rerender();

	            resolve();
	          });
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "isMarketplaceAll",
	    value: function isMarketplaceAll() {
	      return this.data.hasOwnProperty('itemSubType') && this.data.itemSubType === 'marketplaceApp';
	    }
	  }, {
	    key: "needNewLabel",
	    value: function needNewLabel() {
	      return this.data.hasOwnProperty('label') && this.data.label === 'new';
	    }
	  }, {
	    key: "openRestAppLayout",
	    value: function openRestAppLayout(applicationId, appCode) {
	      main_core.ajax.runComponentAction("bitrix:salescenter.control_panel", "getRestApp", {
	        data: {
	          code: appCode
	        }
	      }).then(function (response) {
	        var app = response.data;

	        if (app.TYPE === "A") {
	          this.showRestApplication(appCode);
	        } else {
	          BX.rest.AppLayout.openApplication(applicationId);
	        }
	      }.bind(this))["catch"](function (response) {
	        this.restAppErrorPopup(" ", response.errors.pop().message);
	      }.bind(this));
	    }
	  }, {
	    key: "showRestApplication",
	    value: function showRestApplication(appCode) {
	      var applicationUrlTemplate = "/marketplace/detail/#app#/";
	      var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
	      salescenter_manager.Manager.openSlider(url).then(this.reloadTile.bind(this));
	    }
	  }, {
	    key: "restAppErrorPopup",
	    value: function restAppErrorPopup(title, text) {
	      var popup = new BX.PopupWindow('rest-app-error-alert', null, {
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
	        buttons: [new BX.PopupWindowButton({
	          'id': 'close',
	          'text': BX.message('SALESCENTER_CONTROL_PANEL_POPUP_CLOSE'),
	          'events': {
	            'click': function click() {
	              popup.close();
	            }
	          }
	        })],
	        events: {
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            popup = null;
	          }
	        }
	      });
	      popup.show();
	    }
	  }]);
	  return BaseItem;
	}(BX.TileGrid.Item);

	var PaymentItem = /*#__PURE__*/function (_BaseItem) {
	  babelHelpers.inherits(PaymentItem, _BaseItem);

	  function PaymentItem() {
	    babelHelpers.classCallCheck(this, PaymentItem);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PaymentItem).apply(this, arguments));
	  }

	  babelHelpers.createClass(PaymentItem, [{
	    key: "dropMenu",
	    value: function dropMenu() {
	      return this;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.isMarketplaceAll()) {
	        if (this.data.active) {
	          this.openRestAppLayout(this.data.appId, this.data.code);
	        } else {
	          this.showRestApplication(this.data.code);
	        }
	      } else if (this.opensSlider()) {
	        var url = this.getUrl();
	        var options = this.getSliderOptions();

	        if (url) {
	          salescenter_manager.Manager.openSlider(url, options).then(this.reloadTile.bind(this));
	        }
	      } else if (this.isRecommendTile()) {
	        salescenter_manager.Manager.openFeedbackPayOrderForm();
	      } else {
	        babelHelpers.get(babelHelpers.getPrototypeOf(PaymentItem.prototype), "onClick", this).call(this);
	      }
	    }
	  }, {
	    key: "opensSlider",
	    value: function opensSlider() {
	      var tileHasSlider = this.isCrmStoreTile() || this.isCrmWithEshopTile() || this.isCrmFormTile() || this.isTerminalTile();
	      var tileHasUrl = this.getUrl();
	      var tileHasMenu = this.hasMenu();
	      return tileHasSlider && tileHasUrl && !tileHasMenu;
	    }
	  }, {
	    key: "isRecommendTile",
	    value: function isRecommendTile() {
	      return this.id === 'recommendation';
	    }
	  }, {
	    key: "isCrmStoreTile",
	    value: function isCrmStoreTile() {
	      return this.id === 'crmstore';
	    }
	  }, {
	    key: "isCrmWithEshopTile",
	    value: function isCrmWithEshopTile() {
	      return this.id === 'crm-with-eshop';
	    }
	  }, {
	    key: "isCrmFormTile",
	    value: function isCrmFormTile() {
	      return this.id && this.id === 'crmform';
	    }
	  }, {
	    key: "isTerminalTile",
	    value: function isTerminalTile() {
	      return this.id && this.id === 'terminal';
	    }
	  }]);
	  return PaymentItem;
	}(BaseItem);

	var PaymentSystemItem = /*#__PURE__*/function (_BaseItem2) {
	  babelHelpers.inherits(PaymentSystemItem, _BaseItem2);

	  function PaymentSystemItem() {
	    babelHelpers.classCallCheck(this, PaymentSystemItem);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PaymentSystemItem).apply(this, arguments));
	  }

	  babelHelpers.createClass(PaymentSystemItem, [{
	    key: "onClick",
	    value: function onClick() {
	      if (this.isDependsOnConnection()) {
	        babelHelpers.get(babelHelpers.getPrototypeOf(PaymentSystemItem.prototype), "onClick", this).call(this);
	      } else if (this.id === 'userconsent') {
	        if (!this.isActive()) {
	          var url = this.getUrl();

	          if (url) {
	            salescenter_manager.Manager.openSlider(url).then(this.reloadTile.bind(this));
	          }
	        } else {
	          this.showMenu();
	        }
	      } else {
	        var _url = this.getUrl();

	        if (_url) {
	          salescenter_manager.Manager.openSlider(_url).then(this.reloadTile.bind(this));
	        }
	      }
	    }
	  }]);
	  return PaymentSystemItem;
	}(BaseItem);

	namespace.ControlPanel = ControlPanel;
	namespace.PaymentItem = PaymentItem;
	namespace.PaymentSystemItem = PaymentSystemItem;

}((this.window = this.window || {}),BX,BX.Main,BX.Salescenter));
//# sourceMappingURL=script.js.map
