this.BX = this.BX || {};
(function (exports,main_popup,ui_dialogs_messagebox,main_core,main_core_events,ui_label,currency_currencyCore) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var DocumentManager = /*#__PURE__*/function () {
	  function DocumentManager() {
	    babelHelpers.classCallCheck(this, DocumentManager);
	  }
	  babelHelpers.createClass(DocumentManager, null, [{
	    key: "openRealizationDetailDocument",
	    value: function openRealizationDetailDocument(id) {
	      var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var url = DocumentManager.getRealizationDocumentDetailUrl(id, params);
	      var sliderOptions = params.hasOwnProperty('sliderOptions') ? params.sliderOptions : {};
	      return new Promise(function (resolve, reject) {
	        DocumentManager.openSlider(url.toString(), sliderOptions).then(function (slider) {
	          resolve(slider.getData());
	        })["catch"](function (reason) {});
	      });
	    }
	  }, {
	    key: "openNewRealizationDocument",
	    value: function openNewRealizationDocument() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var sliderOptions = {};
	      if (params.hasOwnProperty('sliderOptions')) {
	        sliderOptions = params.sliderOptions;
	        delete params.sliderOptions;
	      }
	      var url = DocumentManager.getNewRealizationDocumentUrl(params);
	      return new Promise(function (resolve, reject) {
	        DocumentManager.openSlider(url.toString(), sliderOptions).then(function (slider) {
	          resolve(slider.getData());
	        })["catch"](function (reason) {});
	      });
	    }
	  }, {
	    key: "openSlider",
	    value: function openSlider(url, options) {
	      if (!main_core.Type.isPlainObject(options)) {
	        options = {};
	      }
	      options = _objectSpread(_objectSpread({}, {
	        cacheable: false,
	        allowChangeHistory: false,
	        events: {}
	      }), options);
	      return new Promise(function (resolve) {
	        if (main_core.Type.isString(url) && url.length > 1) {
	          options.events.onClose = function (event) {
	            resolve(event.getSlider());
	          };
	          BX.SidePanel.Instance.open(url, options);
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "getRealizationDocumentDetailUrl",
	    value: function getRealizationDocumentDetailUrl(id, params) {
	      var url = new main_core.Uri('/shop/documents/details/sales_order/' + id + '/');
	      if (main_core.Type.isPlainObject(params)) {
	        url.setQueryParams(params);
	      }
	      return url;
	    }
	  }, {
	    key: "getNewRealizationDocumentUrl",
	    value: function getNewRealizationDocumentUrl(params) {
	      var url = new main_core.Uri('/shop/documents/details/sales_order/0/?DOCUMENT_TYPE=W');
	      if (main_core.Type.isPlainObject(params)) {
	        url.setQueryParams(params);
	      }
	      return url;
	    }
	  }]);
	  return DocumentManager;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	var SPECIFIC_REALIZATION_ERROR_CODES = ['REALIZATION_ACCESS_DENIED', 'REALIZATION_CANNOT_DELETE', 'REALIZATION_ALREADY_DEDUCTED', 'REALIZATION_NOT_DEDUCTED', 'REALIZATION_PRODUCT_NOT_FOUND', 'SHIPMENT_ACCESS_DENIED', 'PAYMENT_ACCESS_DENIED', 'CRM_REALIZATION_NOT_ENOUGH_PRODUCTS'];
	var SPECIFIC_ERROR_CODES = SPECIFIC_REALIZATION_ERROR_CODES.concat(['DEDUCTION_STORE_ERROR1', 'SALE_PROVIDER_SHIPMENT_QUANTITY_NOT_ENOUGH', 'SALE_SHIPMENT_EXIST_SHIPPED', 'SALE_PAYMENT_DELETE_EXIST_PAID', 'DDCT_DEDUCTION_QUANTITY_STORE_ERROR', 'CRM_REALIZATION_NOT_ENOUGH_PRODUCTS']);
	var EntityEditorPaymentDocuments = /*#__PURE__*/function () {
	  function EntityEditorPaymentDocuments(options) {
	    babelHelpers.classCallCheck(this, EntityEditorPaymentDocuments);
	    this._options = options;
	    this._phrases = {};
	    if (main_core.Type.isPlainObject(options.PHRASES)) {
	      this._phrases = options.PHRASES;
	    }
	    this._isDeliveryAvailable = this._options.IS_DELIVERY_AVAILABLE;
	    this._parentContext = options.PARENT_CONTEXT;
	    this._callContext = options.CONTEXT;
	    this._rootNode = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"></div>"])), this.constructor._rootNodeClass);
	    this._menus = [];
	    this._isUsedInventoryManagement = this._options.IS_USED_INVENTORY_MANAGEMENT;
	    this._salesOrderRights = this._options.SALES_ORDERS_RIGHTS;
	    this._isInventoryManagementRestricted = this._options.IS_INVENTORY_MANAGEMENT_RESTRICTED;
	    this._isWithOrdersMode = this._options.IS_WITH_ORDERS_MODE;
	    this._subscribeToGlobalEvents();
	  }
	  babelHelpers.createClass(EntityEditorPaymentDocuments, [{
	    key: "hasContent",
	    value: function hasContent() {
	      return this._docs().length > 0;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this._menus.forEach(function (menu) {
	        return menu.destroy();
	      });
	      this._rootNode.innerHTML = '';
	      this._setupCurrencyFormat();
	      if (this._isUsedInventoryManagement || this.hasContent()) {
	        this._rootNode.classList.remove('is-hidden');
	        this._rootNode.append(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-entity-widget-content-block-inner-container\">\n\t\t\t\t\t<div class=\"crm-entity-widget-payment\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), this._renderTitle(), this._renderDocuments(), this._renderAddDocument(), this._renderTotalSum()));
	      } else {
	        this._rootNode.classList.add('is-hidden');
	      }
	      return this._rootNode;
	    }
	  }, {
	    key: "setOptions",
	    value: function setOptions(options) {
	      this._options = options;
	    }
	  }, {
	    key: "reloadModel",
	    value: function reloadModel(onSuccess, onError) {
	      var _this = this;
	      if (!this._options.OWNER_ID) {
	        return;
	      }
	      var data = {
	        data: {
	          ownerTypeId: this._options.OWNER_TYPE_ID,
	          ownerId: this._options.OWNER_ID
	        }
	      };
	      var successCallback = function successCallback(response) {
	        _this._loading(false);
	        if (response.data) {
	          _this.setOptions(response.data);
	          _this.render();
	          if (onSuccess && main_core.Type.isFunction(onSuccess)) {
	            onSuccess(response);
	          }
	          _this._emitChangeDocumentsEvent();
	        } else {
	          _this._showCommonError();
	          if (onError && main_core.Type.isFunction(onError)) {
	            onError();
	          }
	        }
	      };
	      var errorCallback = function errorCallback() {
	        _this._loading(false);
	        _this._showCommonError();
	        if (onError && main_core.Type.isFunction(onError)) {
	          onError();
	        }
	      };
	      this._loading(true);
	      main_core.ajax.runAction('crm.api.entity.fetchPaymentDocuments', data).then(successCallback, errorCallback);
	    }
	  }, {
	    key: "_renderTitle",
	    value: function _renderTitle() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-caption\">", "</div>\n\t\t\t</div>\n\t\t"])), this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TITLE_MSGVER_1'));
	    }
	  }, {
	    key: "_renderDocuments",
	    value: function _renderDocuments() {
	      var _this2 = this;
	      var nodes = [];
	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'PAYMENT') {
	          nodes.push(_this2._renderPaymentDocument(doc));
	        } else if (doc.TYPE === 'SHIPMENT') {
	          nodes.push(_this2._renderDeliveryDocument(doc));
	        } else if (doc.TYPE === 'SHIPMENT_DOCUMENT') {
	          nodes.push(_this2._renderRealizationDocument(doc));
	        }
	      });
	      return nodes;
	    }
	  }, {
	    key: "_renderPaymentDocument",
	    value: function _renderPaymentDocument(doc) {
	      var _this3 = this;
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE_MSGVER_1').replace(/#DATE#/gi, doc.FORMATTED_DATE).replace(/#ACCOUNT_NUMBER#/gi, doc.ACCOUNT_NUMBER);
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var labelOptions = {
	        text: main_core.Loc.getMessage("CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_".concat(doc.STAGE)),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };
	      if (doc.STAGE && doc.STAGE === 'PAID') {
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      } else if (doc.STAGE && doc.STAGE === 'VIEWED_NO_PAID') {
	        labelOptions.color = ui_label.LabelColor.LIGHT_BLUE;
	      }
	      if (!labelOptions.text) {
	        labelOptions.text = doc.PAID === 'Y' ? main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_PAID') : main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_NOT_PAID');
	      }
	      var popupMenu;
	      var menuItems = [];
	      if (this._isDeliveryAvailable) {
	        menuItems.push({
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
	          title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
	          onclick: function onclick() {
	            return _this3._chooseDeliverySlider(doc.ORDER_ID);
	          }
	        });
	      }
	      var realizationMenuItem = this._getRealizationMenuItem(main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_REALIZATION'), function () {
	        return _this3._createRealizationSlider({
	          paymentId: doc.ID
	        });
	      });
	      if (realizationMenuItem) {
	        menuItems.push(realizationMenuItem);
	      }
	      menuItems.push({
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_RESEND'),
	        onclick: function onclick() {
	          return _this3._resendPaymentSlider(doc.ORDER_ID, doc.ID);
	        }
	      });
	      menuItems.push({
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_PAYMENT_STATUS'),
	        items: [{
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_PAID'),
	          className: doc.PAID === 'Y' ? 'menu-popup-item-accept-sm' : '',
	          onclick: function onclick() {
	            _this3._setPaymentPaidStatus(doc, true);
	            popupMenu.close();
	          }
	        }, {
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_NOT_PAID'),
	          className: doc.PAID === 'Y' ? '' : 'menu-popup-item-accept-sm',
	          onclick: function onclick() {
	            _this3._setPaymentPaidStatus(doc, false);
	            popupMenu.close();
	          }
	        }]
	      });
	      menuItems.push({
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CASHBOX_CHECKS'),
	        onclick: function onclick() {
	          return _this3._openPaymentChecksListSlider(doc.ID);
	        }
	      });
	      menuItems.push({
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
	        className: doc.PAID === 'Y' ? 'menu-popup-no-icon crm-entity-widget-payment-menu-item-remove' : '',
	        onclick: function onclick() {
	          if (doc.PAID === 'Y') {
	            return false;
	          }
	          popupMenu.close();
	          ui_dialogs_messagebox.MessageBox.show({
	            title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE_MSGVER_1'),
	            message: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_CONFIRM_TEXT_MSGVER_1'),
	            modal: true,
	            buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	            onOk: function onOk(messageBox) {
	              messageBox.close();
	              _this3._removeDocument(doc);
	            },
	            onCancel: function onCancel(messageBox) {
	              messageBox.close();
	            }
	          });
	        }
	      });
	      var openSlider = function openSlider() {
	        return _this3._resendPaymentSlider(doc.ORDER_ID, doc.ID);
	      };
	      var openMenu = function openMenu(event) {
	        event.preventDefault();
	        popupMenu = main_popup.MenuManager.create({
	          id: 'payment-documents-payment-action-' + doc.ID,
	          bindElement: event.target,
	          items: menuItems
	        });
	        popupMenu.show();
	        var removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-payment-menu-item-remove');
	        if (removeDocumentMenuItem) {
	          removeDocumentMenuItem.setAttribute('data-hint', main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_REMOVE_TIP_MSGVER_1'));
	          removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
	          BX.UI.Hint.init(popupMenu.itemsContainer);
	        }
	        _this3._menus.push(popupMenu);
	      };
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">", " (", ")</a>\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-inner\">\n\t\t\t\t\t<div class=\"ui-label ui-label-md ui-label-light crm-entity-widget-payment-action\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, sum, openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU'), new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderDeliveryDocument",
	    value: function _renderDeliveryDocument(doc) {
	      var _this4 = this;
	      var labelOptions = {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING_MSGVER_1'),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };
	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      }
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE_MSGVER_1').replace(/#DATE#/gi, doc.FORMATTED_DATE).replace(/#ACCOUNT_NUMBER#/gi, doc.ACCOUNT_NUMBER);
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var popupMenu;
	      var menuItems = [{
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_DELIVERY_STATUS'),
	        items: [{
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED'),
	          className: doc.DEDUCTED === 'Y' ? 'menu-popup-item-accept-sm' : '',
	          onclick: function onclick() {
	            _this4._setShipmentShippedStatus(doc, true);
	            popupMenu.close();
	          }
	        }, {
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING_MSGVER_1'),
	          className: doc.DEDUCTED === 'Y' ? '' : 'menu-popup-item-accept-sm',
	          onclick: function onclick() {
	            _this4._setShipmentShippedStatus(doc, false);
	            popupMenu.close();
	          }
	        }]
	      }, {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
	        className: doc.DEDUCTED === 'Y' ? 'menu-popup-no-icon crm-entity-widget-shipment-menu-item-remove' : '',
	        onclick: function onclick() {
	          if (doc.DEDUCTED === 'Y') {
	            return false;
	          }
	          popupMenu.close();
	          ui_dialogs_messagebox.MessageBox.show({
	            title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE_MSGVER_1'),
	            message: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_CONFIRM_TEXT_MSGVER_1'),
	            modal: true,
	            buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	            onOk: function onOk(messageBox) {
	              messageBox.close();
	              _this4._removeDocument(doc);
	            },
	            onCancel: function onCancel(messageBox) {
	              messageBox.close();
	            }
	          });
	        }
	      }];
	      var openSlider = function openSlider() {
	        return _this4._viewDeliverySlider(doc.ORDER_ID, doc.ID);
	      };
	      var openMenu = function openMenu(event) {
	        event.preventDefault();
	        popupMenu = main_popup.MenuManager.create({
	          id: 'payment-documents-delivery-action-' + doc.ID,
	          bindElement: event.target,
	          items: menuItems
	        });
	        popupMenu.show();
	        var removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-shipment-menu-item-remove');
	        if (removeDocumentMenuItem) {
	          removeDocumentMenuItem.setAttribute('data-hint', main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_REMOVE_TIP_MSGVER_1'));
	          removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
	          BX.UI.Hint.init(popupMenu.itemsContainer);
	        }
	        _this4._menus.push(popupMenu);
	      };
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t", " (", ", ", ")\n\t\t\t\t</a>\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-inner\">\n\t\t\t\t\t<div class=\"ui-label ui-label-md ui-label-light crm-entity-widget-payment-action\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, doc.DELIVERY_NAME, sum, openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU'), new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderRealizationDocument",
	    value: function _renderRealizationDocument(doc) {
	      var _this$_salesOrderRigh,
	        _this5 = this;
	      var labelOptions = {
	        customClass: 'crm-entity-widget-payment-label',
	        fill: true
	      };
	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DEDUCTED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      } else {
	        if (doc.EMP_DEDUCTED_ID) {
	          labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCELLED');
	          labelOptions.color = ui_label.LabelColor.LIGHT_ORANGE;
	        } else {
	          labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DRAFT');
	          labelOptions.color = ui_label.LabelColor.LIGHT;
	        }
	      }
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_DATE_MSGVER_1').replace(/#DATE#/gi, doc.FORMATTED_DATE);
	      title = title.replace(/#DOCUMENT_ID#/gi, doc.ACCOUNT_NUMBER);
	      title = BX.util.htmlspecialchars(title);
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var popupMenu;
	      var menuItems = [];
	      if ((_this$_salesOrderRigh = this._salesOrderRights) !== null && _this$_salesOrderRigh !== void 0 && _this$_salesOrderRigh.view) {
	        var _this$_salesOrderRigh2, _this$_salesOrderRigh4, _this$_salesOrderRigh5;
	        if (doc.DEDUCTED === 'Y' && (_this$_salesOrderRigh2 = this._salesOrderRights) !== null && _this$_salesOrderRigh2 !== void 0 && _this$_salesOrderRigh2.conduct) {
	          var _this$_salesOrderRigh3;
	          if ((_this$_salesOrderRigh3 = this._salesOrderRights) !== null && _this$_salesOrderRigh3 !== void 0 && _this$_salesOrderRigh3.conduct) {
	            menuItems.push({
	              text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCEL'),
	              className: doc.DEDUCTED === 'Y' ? '' : 'menu-popup-item-accept-sm',
	              onclick: function onclick() {
	                _this5._setRealizationDeductedStatus(doc, false);
	                popupMenu.close();
	              }
	            });
	          }
	        } else if (doc.DEDUCTED !== 'Y' && (_this$_salesOrderRigh4 = this._salesOrderRights) !== null && _this$_salesOrderRigh4 !== void 0 && _this$_salesOrderRigh4.cancel) {
	          menuItems.push({
	            text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CONDUCT'),
	            className: doc.DEDUCTED === 'Y' ? 'menu-popup-item-accept-sm' : '',
	            onclick: function onclick() {
	              _this5._setRealizationDeductedStatus(doc, true);
	              popupMenu.close();
	            }
	          });
	        }
	        if ((_this$_salesOrderRigh5 = this._salesOrderRights) !== null && _this$_salesOrderRigh5 !== void 0 && _this$_salesOrderRigh5["delete"]) {
	          menuItems.push({
	            text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
	            className: doc.DEDUCTED === 'Y' ? 'menu-popup-no-icon crm-entity-widget-realization-menu-item-remove' : '',
	            onclick: function onclick() {
	              if (doc.DEDUCTED === 'Y') {
	                return false;
	              }
	              popupMenu.close();
	              ui_dialogs_messagebox.MessageBox.show({
	                title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE_MSGVER_1'),
	                message: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_CONFIRM_REMOVE_TEXT'),
	                modal: true,
	                buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	                onOk: function onOk(messageBox) {
	                  messageBox.close();
	                  _this5._removeDocument(doc);
	                },
	                onCancel: function onCancel(messageBox) {
	                  messageBox.close();
	                }
	              });
	            }
	          });
	        }
	      }
	      var openSlider = function openSlider() {
	        return _this5._viewRealizationSlider(doc.ID);
	      };
	      var openMenu = function openMenu(event) {
	        event.preventDefault();
	        popupMenu = main_popup.MenuManager.create({
	          id: 'payment-documents-realization-action-' + doc.ID,
	          bindElement: event.target,
	          items: menuItems
	        });
	        popupMenu.show();
	        var removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-realization-menu-item-remove');
	        if (removeDocumentMenuItem) {
	          removeDocumentMenuItem.setAttribute('data-hint', main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REALIZATION_REMOVE_TIP_MSGVER_1'));
	          removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
	          BX.UI.Hint.init(popupMenu.itemsContainer);
	        }
	        _this5._menus.push(popupMenu);
	      };
	      var actionMenu = menuItems.length > 0 ? main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-label ui-label-md ui-label-light crm-entity-widget-payment-action\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t"])), openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU')) : '';
	      return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t", " (", ")\n\t\t\t\t</a>\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-inner\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, sum, actionMenu, new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderAddDocument",
	    value: function _renderAddDocument() {
	      var _this6 = this;
	      var latestOrderId = this._latestOrderId();
	      var menuItems = [{
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT'),
	        onclick: function onclick() {
	          return _this6._createPaymentSlider(latestOrderId);
	        }
	      }];
	      if (this._isDeliveryAvailable) {
	        menuItems.push({
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_DELIVERY'),
	          onclick: function onclick() {
	            return _this6._createDeliverySlider(latestOrderId);
	          }
	        });
	        menuItems.push({
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT_DELIVERY'),
	          onclick: function onclick() {
	            return _this6._createPaymentDeliverySlider(latestOrderId);
	          }
	        });
	      }
	      var realizationMenuItem = this._getRealizationMenuItem(main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_SHIPMENT_DOCUMENT'), function () {
	        return _this6._createRealizationSlider({
	          orderId: latestOrderId
	        });
	      });
	      if (realizationMenuItem) {
	        menuItems.push(realizationMenuItem);
	      }
	      var openMenu = function openMenu(event) {
	        event.preventDefault();
	        var popupMenu = main_popup.MenuManager.create({
	          id: 'payment-documents-create-document-action',
	          bindElement: event.target,
	          items: menuItems
	        });
	        popupMenu.show();
	        _this6._menus.push(popupMenu);
	      };
	      return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-add-box\">\n\t\t\t\t<a href=\"#\" class=\"crm-entity-widget-payment-add\" onclick=\"", "\">\n\t\t\t\t\t+ ", "\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t"])), openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_DOCUMENT_MSGVER_1'));
	    }
	  }, {
	    key: "_getRealizationMenuItem",
	    value: function _getRealizationMenuItem(text, onclick) {
	      var _this$_salesOrderRigh6;
	      var isAvailableInventoryManagement = this._isUsedInventoryManagement && !this._isWithOrdersMode;
	      if (isAvailableInventoryManagement && (_this$_salesOrderRigh6 = this._salesOrderRights) !== null && _this$_salesOrderRigh6 !== void 0 && _this$_salesOrderRigh6.modify) {
	        var menuItem = {
	          text: text
	        };
	        if (this._isInventoryManagementRestricted) {
	          menuItem.onclick = function () {
	            return top.BX.UI.InfoHelper.show('limit_store_crm_integration');
	          };
	          menuItem.className = 'realization-document-tariff-lock';
	        } else {
	          menuItem.onclick = onclick;
	        }
	        return menuItem;
	      }
	      return null;
	    }
	  }, {
	    key: "_renderTotalSum",
	    value: function _renderTotalSum() {
	      var totalSum = this._options.TOTAL_AMOUNT;
	      var node = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-total\">\n\t\t\t\t<span>\n\t\t\t\t\t", "\n\t\t\t\t\t<span data-hint=\"", "\"></span>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"crm-entity-widget-payment-text\">", "</span>\n\t\t\t</div>\n\t\t"])), this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM'), this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM_TOOLTIP'), this._renderMoney(totalSum));
	      BX.UI.Hint.init(node);
	      return node;
	    }
	  }, {
	    key: "_renderMoney",
	    value: function _renderMoney(sum) {
	      var fullPrice = currency_currencyCore.CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, true);
	      var onlyPrice = currency_currencyCore.CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, false);
	      var currency = fullPrice.replace(onlyPrice, '').trim();
	      return fullPrice.replace(currency, "<span class=\"crm-entity-widget-payment-currency\">".concat(currency, "</span>"));
	    }
	  }, {
	    key: "_docs",
	    value: function _docs() {
	      if (this._options && this._options.DOCUMENTS && this._options.DOCUMENTS.length) {
	        return this._options.DOCUMENTS;
	      }
	      return [];
	    }
	  }, {
	    key: "_orders",
	    value: function _orders() {
	      if (this._options && this._options.ORDERS && this._options.ORDERS.length) {
	        return this._options.ORDERS;
	      }
	      return [];
	    }
	  }, {
	    key: "_context",
	    value: function _context() {
	      return this._parentContext;
	    }
	  }, {
	    key: "_orderIds",
	    value: function _orderIds() {
	      if (this._options && this._options.ORDER_IDS && this._options.ORDER_IDS.length) {
	        return this._options.ORDER_IDS.map(function (id) {
	          return parseInt(id);
	        });
	      }
	      return [];
	    } // @todo: provide test
	  }, {
	    key: "_latestOrderId",
	    value: function _latestOrderId() {
	      var latestOrder = parseInt(Math.max.apply(Math, babelHelpers.toConsumableArray(this._orderIds())));
	      return latestOrder > 0 ? latestOrder : 0;
	    }
	  }, {
	    key: "_ownerTypeId",
	    value: function _ownerTypeId() {
	      return this._options.OWNER_TYPE_ID || BX.CrmEntityType.enumeration.deal;
	    }
	  }, {
	    key: "_defaultCreatePaymentDocumentOptions",
	    value: function _defaultCreatePaymentDocumentOptions() {
	      return {
	        context: this._callContext,
	        templateMode: 'create',
	        ownerTypeId: this._ownerTypeId(),
	        ownerId: this._options.OWNER_ID
	      };
	    }
	    /**
	     *
	     * @param labelMode converting to PascalCase before inserting into label template
	     * @returns {string} final analytics label
	     * @private
	     *
	     * Label template: crm#TYPE#PaymentDocuments#MODE#Slider
	     * Type: Deal or DynamicType
	     * Mode: create_payment, create_delivery, view_realization etc.
	     *
	     * Mode converts to PascalCase and inserts into label template
	     *
	     * Example: crmDealPaymentDocumentsCreateDeliverySlider
	     */
	  }, {
	    key: "_generateAnalyticsLabel",
	    value: function _generateAnalyticsLabel(labelMode) {
	      var labelTemplate = 'crm#TYPE#PaymentDocuments#MODE#Slider';
	      var labelEntityType = 'Deal';
	      if (BX.CrmEntityType.isDynamicTypeByTypeId(this._ownerTypeId())) {
	        labelEntityType = 'DynamicType';
	      }
	      var mode = main_core.Text.toPascalCase(labelMode);
	      return labelTemplate.replace('#TYPE#', labelEntityType).replace('#MODE#', mode);
	    }
	  }, {
	    key: "_createPaymentSlider",
	    value: function _createPaymentSlider(orderId) {
	      var options = this._defaultCreatePaymentDocumentOptions();
	      options.mode = 'payment';
	      options.analyticsLabel = this._generateAnalyticsLabel('create_payment');
	      options.orderId = orderId;
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_createDeliverySlider",
	    value: function _createDeliverySlider(orderId) {
	      var options = this._defaultCreatePaymentDocumentOptions();
	      options.mode = 'delivery';
	      options.analyticsLabel = this._generateAnalyticsLabel('create_delivery');
	      options.orderId = orderId;
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_createPaymentDeliverySlider",
	    value: function _createPaymentDeliverySlider(orderId) {
	      var options = this._defaultCreatePaymentDocumentOptions();
	      options.mode = 'payment_delivery';
	      options.analyticsLabel = this._generateAnalyticsLabel('create_payment_delivery');
	      options.orderId = orderId;
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_createRealizationSlider",
	    value: function _createRealizationSlider(createSliderOptions) {
	      var options = {
	        context: {
	          OWNER_TYPE_ID: this._ownerTypeId(),
	          OWNER_ID: this._options.OWNER_ID,
	          ORDER_ID: createSliderOptions.orderId || 0,
	          PAYMENT_ID: createSliderOptions.paymentId || 0
	        },
	        analyticsLabel: this._generateAnalyticsLabel('create_realization'),
	        documentType: 'W',
	        sliderOptions: {
	          customLeftBoundary: 0,
	          loader: 'crm-entity-details-loader',
	          requestMethod: 'post'
	        }
	      };
	      DocumentManager.openNewRealizationDocument(options).then(function (result) {
	        this.reloadModel();
	        this._reloadOwner();
	      }.bind(this));
	    }
	  }, {
	    key: "_chooseDeliverySlider",
	    value: function _chooseDeliverySlider(orderId) {
	      var options = this._defaultCreatePaymentDocumentOptions();
	      options.mode = 'delivery';
	      options.analyticsLabel = this._generateAnalyticsLabel('choose_delivery');
	      options.orderId = orderId;
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_openPaymentChecksListSlider",
	    value: function _openPaymentChecksListSlider(paymentId) {
	      BX.SidePanel.Instance.open(BX.Uri.addParam('/crm/payment/checks/list.php', {
	        'owner_id': paymentId,
	        'owner_type': BX.CrmEntityType.enumeration.orderpayment
	      }), {
	        width: 1500,
	        allowChangeHistory: false,
	        cacheable: false
	      });
	    }
	  }, {
	    key: "_resendPaymentSlider",
	    value: function _resendPaymentSlider(orderId, paymentId) {
	      var options = {
	        disableSendButton: '',
	        context: 'deal',
	        mode: this._ownerTypeId() === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
	        analyticsLabel: this._generateAnalyticsLabel('resend_payment'),
	        templateMode: 'view',
	        ownerTypeId: this._ownerTypeId(),
	        ownerId: this._options.OWNER_ID,
	        orderId: orderId,
	        paymentId: paymentId
	      };
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_viewDeliverySlider",
	    value: function _viewDeliverySlider(orderId, shipmentId) {
	      var options = {
	        context: this._callContext,
	        templateMode: 'view',
	        mode: 'delivery',
	        analyticsLabel: this._generateAnalyticsLabel('view_delivery'),
	        ownerTypeId: this._ownerTypeId(),
	        ownerId: this._options.OWNER_ID,
	        orderId: orderId,
	        shipmentId: shipmentId
	      };
	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_viewRealizationSlider",
	    value: function _viewRealizationSlider(documentId) {
	      var options = {
	        ownerTypeId: this._ownerTypeId(),
	        ownerId: this._options.OWNER_ID,
	        analyticsLabel: this._generateAnalyticsLabel('view_realization'),
	        documentId: documentId,
	        sliderOptions: {
	          customLeftBoundary: 0,
	          loader: 'crm-entity-details-loader'
	        }
	      };
	      DocumentManager.openRealizationDetailDocument(documentId, options).then(function (result) {
	        this._reloadOwner();
	      }.bind(this));
	    }
	  }, {
	    key: "_setPaymentPaidStatus",
	    value: function _setPaymentPaidStatus(payment, isPaid) {
	      var _this7 = this;
	      var strPaid = isPaid ? 'Y' : 'N';
	      var stage = isPaid ? 'PAID' : 'CANCEL';
	      if (payment.PAID && payment.PAID === strPaid) {
	        return;
	      }

	      // positive approach - render success first, then do actual query
	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'PAYMENT' && doc.ID === payment.ID) {
	          doc.PAID = strPaid;
	          doc.STAGE = stage;
	        }
	      });
	      this.render();
	      var callEventOnSuccess = function callEventOnSuccess(response) {
	        main_core_events.EventEmitter.emit('PaymentDocuments.EntityEditor:changePaymentPaidStatus', {
	          entityTypeId: _this7._options.OWNER_TYPE_ID,
	          entityId: _this7._options.OWNER_ID
	        });
	        _this7._emitChangeDocumentsEvent();
	      };
	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this7._showErrorOnAction(response);
	        _this7.reloadModel();
	      };
	      main_core.ajax.runAction('crm.order.payment.setPaid', {
	        data: {
	          id: payment.ID,
	          value: strPaid
	        }
	      }).then(callEventOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_setShipmentShippedStatus",
	    value: function _setShipmentShippedStatus(shipment, isShipped) {
	      var _this8 = this;
	      var strShipped = isShipped ? 'Y' : 'N';
	      if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped) {
	        return;
	      }
	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'SHIPMENT' && doc.ID === shipment.ID) {
	          doc.DEDUCTED = strShipped;
	        }
	      });
	      this.render();
	      var callEventOnSuccess = function callEventOnSuccess(response) {
	        main_core_events.EventEmitter.emit('PaymentDocuments.EntityEditor:changeShipmentShippedStatus', {
	          entityTypeId: _this8._options.OWNER_TYPE_ID,
	          entityId: _this8._options.OWNER_ID
	        });
	        _this8._emitChangeDocumentsEvent();
	      };
	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this8._showShipmentStatusError(response, shipment.ID);
	        _this8.reloadModel();
	      };
	      var actionName = 'crm.order.shipment.setShipped';
	      if (this._isUsedInventoryManagement) {
	        actionName = 'crm.api.realizationdocument.setShipped';
	      }
	      main_core.ajax.runAction(actionName, {
	        data: {
	          id: shipment.ID,
	          value: strShipped
	        }
	      }).then(callEventOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_setRealizationDeductedStatus",
	    value: function _setRealizationDeductedStatus(shipment, isShipped) {
	      var _this9 = this;
	      var strShipped = isShipped ? 'Y' : 'N';
	      if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped) {
	        return;
	      }
	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'SHIPMENT_DOCUMENT' && doc.ID === shipment.ID) {
	          doc.DEDUCTED = strShipped;
	        }
	      });
	      this.render();
	      var callEventOnSuccess = function callEventOnSuccess(response) {
	        main_core_events.EventEmitter.emit('PaymentDocuments.EntityEditor:changeRealizationDeductedStatus', {
	          entityTypeId: _this9._options.OWNER_TYPE_ID,
	          entityId: _this9._options.OWNER_ID
	        });
	        _this9._emitChangeDocumentsEvent();
	      };
	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this9._showErrorOnAction(response);
	        _this9.reloadModel();
	      };
	      main_core.ajax.runAction('crm.api.realizationdocument.setShipped', {
	        data: {
	          id: shipment.ID,
	          value: strShipped
	        }
	      }).then(callEventOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_removeDocument",
	    value: function _removeDocument(doc) {
	      var _this10 = this;
	      var action;
	      var data = {
	        id: doc.ID
	      };
	      action = this._resolveRemoveDocumentActionName(doc.TYPE);
	      if (!action) {
	        return;
	      }
	      if (doc.TYPE === 'SHIPMENT_DOCUMENT') {
	        data.value = 'N';
	      }

	      // positive approach - render success first, then do actual query
	      this._options.DOCUMENTS = this._options.DOCUMENTS.filter(function (item) {
	        return !(item.TYPE === doc.TYPE && item.ID === doc.ID);
	      });
	      this.render();
	      var onSuccess = function onSuccess(response) {
	        _this10._reloadOwner();
	        _this10._emitChangeDocumentsEvent();
	      };
	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this10._showErrorOnAction(response);
	        _this10.reloadModel();
	      };
	      main_core.ajax.runAction(action, {
	        data: data
	      }).then(onSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_resolveRemoveDocumentActionName",
	    value: function _resolveRemoveDocumentActionName(type) {
	      var action = '';
	      if (type === 'PAYMENT') {
	        action = 'crm.order.payment.delete';
	      } else if (type === 'SHIPMENT') {
	        action = 'crm.order.shipment.delete';
	      } else if (type === 'SHIPMENT_DOCUMENT') {
	        action = 'crm.api.realizationdocument.setRealization';
	      }
	      return action;
	    }
	  }, {
	    key: "_showShipmentStatusError",
	    value: function _showShipmentStatusError(response, shipmentId) {
	      var _this11 = this;
	      var showCommonError = true;
	      if (this._isUsedInventoryManagement) {
	        response.errors.forEach(function (error) {
	          if (SPECIFIC_ERROR_CODES.indexOf(error.code) > -1) {
	            showCommonError = false;
	            var notifyMessage = error.message;
	            if (SPECIFIC_REALIZATION_ERROR_CODES.indexOf(error.code) === -1) {
	              notifyMessage = BX.util.htmlspecialchars(notifyMessage);
	            }
	            BX.UI.Notification.Center.notify({
	              content: notifyMessage,
	              width: 'auto',
	              actions: [{
	                title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_OPEN_REALIZATION_DOCUMENT'),
	                events: {
	                  click: function click(event, balloon, action) {
	                    _this11._viewRealizationSlider(shipmentId);
	                    balloon.close();
	                  }
	                }
	              }]
	            });
	          }
	        });
	      }
	      if (showCommonError) {
	        this._showCommonError();
	      }
	    }
	  }, {
	    key: "_showErrorOnAction",
	    value: function _showErrorOnAction(response) {
	      var _this12 = this;
	      var showCommonError = true;
	      response.errors.forEach(function (error) {
	        if (SPECIFIC_ERROR_CODES.indexOf(error.code) > -1) {
	          showCommonError = false;
	          _this12._showSpecificError(error.code, error.message);
	        }
	      });
	      if (showCommonError) {
	        this._showCommonError();
	      }
	    }
	  }, {
	    key: "_showSpecificError",
	    value: function _showSpecificError(code, message) {
	      var notifyMessage = message;
	      if (SPECIFIC_REALIZATION_ERROR_CODES.indexOf(code) === -1) {
	        notifyMessage = BX.util.htmlspecialchars(notifyMessage);
	      }
	      BX.UI.Notification.Center.notify({
	        content: notifyMessage
	      });
	    }
	  }, {
	    key: "_showCommonError",
	    value: function _showCommonError() {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_COMMON_ERROR_MSGVER_1')
	      });
	    }
	  }, {
	    key: "_loading",
	    value: function _loading(isLoading) {
	      if (this._rootNode && this._rootNode.classList) {
	        if (isLoading) {
	          this._rootNode.classList.add('is-loading');
	        } else {
	          this._rootNode.classList.remove('is-loading');
	        }
	      }
	    }
	  }, {
	    key: "_emitChangeDocumentsEvent",
	    value: function _emitChangeDocumentsEvent() {
	      main_core_events.EventEmitter.emit('PaymentDocuments.EntityEditor:changeDocuments', {
	        entityTypeId: this._options.OWNER_TYPE_ID,
	        entityId: this._options.OWNER_ID
	      });
	    }
	  }, {
	    key: "_subscribeToGlobalEvents",
	    value: function _subscribeToGlobalEvents() {
	      var _this13 = this;
	      var events = ['salescenter.app:onshipmentcreated', 'salescenter.app:onpaymentcreated', 'salescenter.app:onpaymentresend'];
	      var timeout = 500;
	      var reloadWidget = main_core.debounce(function () {
	        _this13.reloadModel();
	      }, timeout);
	      var inCompatMode = {
	        compatMode: true
	      };
	      var sliderJustClosed = false;
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	        var eventId = event.getEventId();
	        if (events.indexOf(eventId) > -1) {
	          reloadWidget();
	          sliderJustClosed = true;
	          setTimeout(function () {
	            sliderJustClosed = false;
	          }, 2000);
	        }
	      }, inCompatMode);
	      main_core_events.EventEmitter.subscribe('oncrmentityupdate', function () {
	        reloadWidget();
	      }, inCompatMode);
	      main_core_events.EventEmitter.subscribe('onPullEvent-crm', function (command, params) {
	        if (command !== 'onOrderSave' || sliderJustClosed) {
	          return;
	        }
	        reloadWidget();
	      }, inCompatMode);
	      main_core_events.EventEmitter.subscribe('onPullEvent-salescenter', function (command, params) {
	        if (command !== 'onOrderPaymentViewed') {
	          return;
	        }
	        var orderId = false;
	        var orderIds = _this13._orderIds();
	        if (params && params.ORDER_ID) {
	          orderId = parseInt(params.ORDER_ID);
	          if (orderIds.indexOf(orderId) > -1) {
	            reloadWidget();
	          }
	        }
	      }, inCompatMode);
	    }
	  }, {
	    key: "_setupCurrencyFormat",
	    value: function _setupCurrencyFormat() {
	      if (this._options) {
	        if (this._options.CURRENCY_ID && this._options.CURRENCY_FORMAT) {
	          currency_currencyCore.CurrencyCore.setCurrencyFormat(this._options.CURRENCY_ID, this._options.CURRENCY_FORMAT);
	        }
	      }
	    }
	  }, {
	    key: "_reloadOwner",
	    value: function _reloadOwner() {
	      if (this._parentContext instanceof BX.Crm.EntityEditorMoneyPay) {
	        this._parentContext._editor.reload();
	        this._parentContext._editor.tapController('PRODUCT_LIST', function (controller) {
	          controller.reinitializeProductList();
	        });
	      }
	    }
	  }, {
	    key: "_getMessage",
	    value: function _getMessage(phrase) {
	      if (main_core.Type.isPlainObject(this._phrases) && main_core.Type.isString(this._phrases[phrase])) {
	        phrase = this._phrases[phrase];
	      }
	      return main_core.Loc.getMessage(phrase);
	    }
	  }]);
	  return EntityEditorPaymentDocuments;
	}();
	babelHelpers.defineProperty(EntityEditorPaymentDocuments, "_rootNodeClass", 'crm-entity-widget-inner crm-entity-widget-inner--payment');

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1, _templateObject7$1, _templateObject8$1, _templateObject9$1, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14;
	var TimelineSummaryDocuments = /*#__PURE__*/function (_EntityEditorPaymentD) {
	  babelHelpers.inherits(TimelineSummaryDocuments, _EntityEditorPaymentD);
	  function TimelineSummaryDocuments() {
	    babelHelpers.classCallCheck(this, TimelineSummaryDocuments);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TimelineSummaryDocuments).apply(this, arguments));
	  }
	  babelHelpers.createClass(TimelineSummaryDocuments, [{
	    key: "render",
	    value: function render() {
	      this._menus.forEach(function (menu) {
	        return menu.destroy();
	      });
	      this._rootNode.innerHTML = '';
	      this._setupCurrencyFormat();
	      if (this.hasContent()) {
	        this._filterSuccessfulDocuments();
	        this._rootNode.classList.remove('is-hidden');
	        if (this._isWithOrdersMode) {
	          this._renderDocumentWithOrdersMode();
	        } else {
	          this._renderDocumentWithoutOrdersMode();
	        }
	        var checkExists = this._isCheckExists();
	        if (checkExists) {
	          this._rootNode.append(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-entity-stream-content-document-table-group\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), this._renderChecksDocument()));
	        }
	      } else {
	        this._rootNode.classList.add('is-hidden');
	      }
	      main_core_events.EventEmitter.emit('PaymentDocuments:render', [this]);
	      return this._rootNode;
	    }
	  }, {
	    key: "_renderDocumentWithOrdersMode",
	    value: function _renderDocumentWithOrdersMode() {
	      var _this = this;
	      var orderDocument = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	      this._orders().forEach(function (order) {
	        var documents = _this._renderDocumentsByOrder(order.ID);
	        if (documents.length > 0) {
	          orderDocument.append(_this._renderOrderDetailBlock(order));
	          documents.forEach(function (document) {
	            orderDocument.append(document);
	          });
	        }
	      });
	      this._rootNode.append(main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-entity-stream-content-document-table-group\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-document-table-group\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), orderDocument, this._renderEntityTotalSum(), this._renderEntityPaidSum(), this._renderTotalSum()));
	    }
	  }, {
	    key: "_renderDocumentWithoutOrdersMode",
	    value: function _renderDocumentWithoutOrdersMode() {
	      this._rootNode.append(main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-entity-stream-content-document-table-group\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-document-table-group\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this._renderDocuments(), this._renderEntityTotalSum(), this._renderEntityPaidSum(), this._renderTotalSum()));
	    }
	  }, {
	    key: "_renderDocumentsByOrder",
	    value: function _renderDocumentsByOrder(orderId) {
	      var _this2 = this;
	      var nodes = [];
	      var orderDocs = this._docs().filter(function (item) {
	        return item.ORDER_ID === orderId;
	      });
	      orderDocs.forEach(function (doc) {
	        if (doc.TYPE === 'PAYMENT') {
	          nodes.push(_this2._renderPaymentDocument(doc));
	        } else if (doc.TYPE === 'SHIPMENT') {
	          nodes.push(_this2._renderDeliveryDocument(doc));
	        } else if (doc.TYPE === 'SHIPMENT_DOCUMENT') {
	          nodes.push(_this2._renderRealizationDocument(doc));
	        }
	      });
	      return nodes;
	    }
	  }, {
	    key: "_renderEntityTotalSum",
	    value: function _renderEntityTotalSum() {
	      var phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DEAL_SUM';
	      if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice) {
	        phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_INVOICE_SUM';
	      }
	      return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage(phrase), this._renderMoney(this._options.ENTITY_AMOUNT));
	    }
	  }, {
	    key: "_renderEntityPaidSum",
	    value: function _renderEntityPaidSum() {
	      return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ENTITY_PAID_SUM'), this._renderMoney(this._options.PAID_AMOUNT));
	    }
	  }, {
	    key: "_renderPaymentDocument",
	    value: function _renderPaymentDocument(doc) {
	      var _this3 = this;
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE_MSGVER_1', {
	        '#DATE#': doc.FORMATTED_DATE,
	        '#ACCOUNT_NUMBER#': doc.ACCOUNT_NUMBER
	      });
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var labelOptions = {
	        text: main_core.Loc.getMessage("CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_".concat(doc.STAGE)),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };
	      if (doc.STAGE && doc.STAGE === 'PAID') {
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      } else if (doc.STAGE && doc.STAGE === 'VIEWED_NO_PAID') {
	        labelOptions.color = ui_label.LabelColor.LIGHT_BLUE;
	      }
	      if (!labelOptions.text) {
	        labelOptions.text = doc.PAID === 'Y' ? main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_PAID') : main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_NOT_PAID');
	      }
	      var openSlider = function openSlider() {
	        return _this3._resendPaymentSlider(doc.ORDER_ID, doc.ID);
	      };
	      return main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-document-description\">\n\t\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">", " (", ")</a>\n\t\t\t\t\t<span class=\"crm-entity-stream-content-document-description__label\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, sum, new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderDeliveryDocument",
	    value: function _renderDeliveryDocument(doc) {
	      var _this4 = this;
	      var labelOptions = {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING_MSGVER_1'),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };
	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      }
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE_MSGVER_1', {
	        '#DATE#': doc.FORMATTED_DATE,
	        '#ACCOUNT_NUMBER#': doc.ACCOUNT_NUMBER
	      });
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var openSlider = function openSlider() {
	        return _this4._viewDeliverySlider(doc.ORDER_ID, doc.ID);
	      };
	      return main_core.Tag.render(_templateObject8$1 || (_templateObject8$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-document-description\">\n\t\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t\t", " (", ", ", ")\n\t\t\t\t\t</a>\n\t\t\t\t\t<span class=\"crm-entity-stream-content-document-description__label\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, doc.DELIVERY_NAME, sum, new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderRealizationDocument",
	    value: function _renderRealizationDocument(doc) {
	      var _this5 = this;
	      var labelOptions = {
	        fill: true,
	        customClass: 'crm-entity-widget-payment-label'
	      };
	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DEDUCTED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      } else {
	        if (doc.EMP_DEDUCTED_ID) {
	          labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCELLED');
	          labelOptions.color = ui_label.LabelColor.LIGHT_ORANGE;
	        } else {
	          labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DRAFT');
	          labelOptions.color = ui_label.LabelColor.LIGHT;
	        }
	      }
	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_DATE_MSGVER_1').replace(/#DATE#/gi, doc.FORMATTED_DATE);
	      title = title.replace(/#DOCUMENT_ID#/gi, doc.ACCOUNT_NUMBER);
	      title = BX.util.htmlspecialchars(title);
	      var sum = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
	      var openSlider = function openSlider() {
	        return _this5._viewRealizationSlider(doc.ID);
	      };
	      return main_core.Tag.render(_templateObject9$1 || (_templateObject9$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-document-description\">\n\t\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t\t", " (", ")\n\t\t\t\t\t</a>\n\t\t\t\t\t<span class=\"crm-entity-stream-content-document-description__label\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openSlider, title, sum, new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderTotalSum",
	    value: function _renderTotalSum() {
	      var phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM';
	      if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice) {
	        phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_INVOICE_SUM';
	      }
	      return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row crm-entity-stream-content-detail-table-row-total\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage(phrase), this._renderTotalMoney(this._options.TOTAL_AMOUNT));
	    }
	  }, {
	    key: "_renderTotalMoney",
	    value: function _renderTotalMoney(sum) {
	      var fullPrice = currency_currencyCore.CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, true);
	      var onlyPrice = currency_currencyCore.CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, false);
	      var currency = fullPrice.replace(onlyPrice, '').trim();
	      fullPrice = fullPrice.replace(currency, "<span class=\"crm-entity-widget-payment-currency\">".concat(currency, "</span>"));
	      fullPrice = fullPrice.replace(onlyPrice, "<b>".concat(onlyPrice, "</b>"));
	      return fullPrice;
	    }
	  }, {
	    key: "_renderChecksDocument",
	    value: function _renderChecksDocument() {
	      var _this6 = this;
	      var nodes = [];
	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'CHECK') {
	          nodes.push(_this6._renderCheckDocument(doc));
	        }
	      });
	      return nodes;
	    }
	  }, {
	    key: "_renderCheckDocument",
	    value: function _renderCheckDocument(doc) {
	      var link;
	      if (doc.URL) {
	        link = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<a href=\"", "\" target=\"_blank\">", "</a>"])), doc.URL, doc.TITLE);
	      } else {
	        link = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), doc.TITLE);
	      }
	      return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-entity-stream-content-detail-notice\">", "</div>"])), link);
	    }
	  }, {
	    key: "_renderOrderDetailBlock",
	    value: function _renderOrderDetailBlock(doc) {
	      return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-document-table-order-group crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t<span>", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span class=\"crm-entity-stream-content-detail-cost-current\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), doc.TITLE, doc.PRICE_FORMAT);
	    }
	  }, {
	    key: "_filterSuccessfulDocuments",
	    value: function _filterSuccessfulDocuments() {
	      this._options.DOCUMENTS = this._options.DOCUMENTS.filter(function (item) {
	        return item.TYPE === 'PAYMENT' && item.PAID === 'Y' || item.TYPE === 'SHIPMENT' && item.DEDUCTED === 'Y' || item.TYPE === 'SHIPMENT_DOCUMENT' && item.DEDUCTED === 'Y' || item.TYPE === 'CHECK' && item.STATUS === 'Y';
	      });
	    }
	  }, {
	    key: "_isCheckExists",
	    value: function _isCheckExists() {
	      var checks = this._options.DOCUMENTS.filter(function (item) {
	        return item.TYPE === 'CHECK' && item.STATUS === 'Y';
	      });
	      return checks.length > 1;
	    }
	  }]);
	  return TimelineSummaryDocuments;
	}(EntityEditorPaymentDocuments);
	babelHelpers.defineProperty(TimelineSummaryDocuments, "_rootNodeClass", 'crm-entity-stream-content-detail-table crm-entity-stream-content-documents-table');

	exports.EntityEditorPaymentDocuments = EntityEditorPaymentDocuments;
	exports.TimelineSummaryDocuments = TimelineSummaryDocuments;

}((this.BX.Crm = this.BX.Crm || {}),BX.Main,BX.UI.Dialogs,BX,BX.Event,BX.UI,BX.Currency));
//# sourceMappingURL=payment-documents.bundle.js.map
