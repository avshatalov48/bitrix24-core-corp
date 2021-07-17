this.BX = this.BX || {};
(function (exports,main_core_events,main_popup,currency_currencyCore,ui_dialogs_messagebox,main_core,ui_label) {
	'use strict';

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-total\">\n\t\t\t\t<span>\n\t\t\t\t\t", "\n\t\t\t\t\t<span data-hint=\"", "\"></span>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"crm-entity-widget-payment-text\">", "</span>\n\t\t\t</div>\n\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-add-box\">\n\t\t\t\t<a href=\"#\" class=\"crm-entity-widget-payment-add\" onclick=\"", "\">\n\t\t\t\t\t+ ", "\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t", " (", ", ", ")\n\t\t\t\t</a>\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-inner\">\n\t\t\t\t\t<div class=\"ui-label ui-label-md ui-label-light crm-entity-widget-payment-action\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">", " (", ")</a>\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-inner\">\n\t\t\t\t\t<div class=\"ui-label ui-label-md ui-label-light crm-entity-widget-payment-action\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-payment-detail\">\n\t\t\t\t<div class=\"crm-entity-widget-payment-detail-caption\">", "</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-entity-widget-content-block-inner-container\">\n\t\t\t\t\t<div class=\"crm-entity-widget-payment\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var EntityEditorPaymentDocuments = /*#__PURE__*/function () {
	  function EntityEditorPaymentDocuments(options) {
	    babelHelpers.classCallCheck(this, EntityEditorPaymentDocuments);
	    this._options = options;
	    this._isDeliveryAvailable = this._options.IS_DELIVERY_AVAILABLE;
	    this._parentContext = options.PARENT_CONTEXT;
	    this._rootNode = main_core.Tag.render(_templateObject(), this.constructor._rootNodeClass);
	    this._menus = [];

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

	      if (this.hasContent()) {
	        this._rootNode.classList.remove('is-hidden');

	        this._rootNode.append(main_core.Tag.render(_templateObject2(), this._renderTitle(), this._renderDocuments(), this._renderAddDocument(), this._renderTotalSum()));
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

	      if (!this._options.DEAL_ID) {
	        return;
	      }

	      var data = {
	        data: {
	          dealId: this._options.DEAL_ID
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

	      main_core.ajax.runAction('crm.api.deal.fetchPaymentDocuments', data).then(successCallback, errorCallback);
	    }
	  }, {
	    key: "_renderTitle",
	    value: function _renderTitle() {
	      return main_core.Tag.render(_templateObject3(), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TITLE'));
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
	          nodes.push(_this2._renderShipmentDocument(doc));
	        }
	      });

	      return nodes;
	    }
	  }, {
	    key: "_renderPaymentDocument",
	    value: function _renderPaymentDocument(doc) {
	      var _this3 = this;

	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
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
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
	        className: doc.PAID === 'Y' ? 'menu-popup-no-icon crm-entity-widget-payment-menu-item-remove' : '',
	        onclick: function onclick() {
	          if (doc.PAID === 'Y') {
	            return false;
	          }

	          popupMenu.close();
	          ui_dialogs_messagebox.MessageBox.show({
	            title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE'),
	            message: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_CONFIRM_TEXT'),
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
	          removeDocumentMenuItem.setAttribute('data-hint', main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_REMOVE_TIP'));
	          removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
	          BX.UI.Hint.init(popupMenu.itemsContainer);
	        }

	        _this3._menus.push(popupMenu);
	      };

	      return main_core.Tag.render(_templateObject4(), openSlider, title, sum, openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU'), new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderShipmentDocument",
	    value: function _renderShipmentDocument(doc) {
	      var _this4 = this;

	      var labelOptions = {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };

	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      }

	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
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
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
	          className: doc.DEDUCTED === 'Y' ? '' : 'menu-popup-item-accept-sm',
	          onclick: function onclick() {
	            _this4._setShipmentShippedStatus(doc, false);

	            popupMenu.close();
	          }
	        }]
	      }, {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
	        onclick: function onclick() {
	          popupMenu.close();
	          ui_dialogs_messagebox.MessageBox.show({
	            title: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE'),
	            message: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_CONFIRM_TEXT'),
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

	        _this4._menus.push(popupMenu);
	      };

	      return main_core.Tag.render(_templateObject5(), openSlider, title, doc.DELIVERY_NAME, sum, openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU'), new ui_label.Label(labelOptions).render());
	    }
	  }, {
	    key: "_renderAddDocument",
	    value: function _renderAddDocument() {
	      var _this5 = this;

	      var latestOrderId = this._latestOrderId();

	      var menuItems = [{
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT'),
	        onclick: function onclick() {
	          return _this5._context().startSalescenterApplication(latestOrderId);
	        }
	      }];

	      if (this._isDeliveryAvailable) {
	        menuItems.push({
	          text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_DELIVERY'),
	          onclick: function onclick() {
	            return _this5._createDeliverySlider(latestOrderId);
	          }
	        });
	      }

	      var openMenu = function openMenu(event) {
	        event.preventDefault();
	        var popupMenu = main_popup.MenuManager.create({
	          id: 'payment-documents-create-document-action',
	          bindElement: event.target,
	          items: menuItems
	        });
	        popupMenu.show();

	        _this5._menus.push(popupMenu);
	      };

	      return main_core.Tag.render(_templateObject6(), openMenu, main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_DOCUMENT'));
	    }
	  }, {
	    key: "_calculateTotalSum",
	    value: function _calculateTotalSum() {
	      var totalSum = parseFloat(this._options.DEAL_AMOUNT);

	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'PAYMENT') {
	          if (doc.PAID && doc.PAID === 'Y') {
	            totalSum -= parseFloat(doc.SUM);
	          }
	        }
	      });

	      if (totalSum < 0) {
	        totalSum = 0.0;
	      }

	      return totalSum;
	    }
	  }, {
	    key: "_renderTotalSum",
	    value: function _renderTotalSum() {
	      var totalSum = this._calculateTotalSum();

	      var node = main_core.Tag.render(_templateObject7(), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM'), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM_TOOLTIP'), this._renderMoney(totalSum));
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
	      return Math.max.apply(Math, babelHelpers.toConsumableArray(this._orderIds()));
	    }
	  }, {
	    key: "_dealEntityType",
	    value: function _dealEntityType() {
	      return BX.CrmEntityType.enumeration.deal;
	    }
	  }, {
	    key: "_createDeliverySlider",
	    value: function _createDeliverySlider(orderId) {
	      var options = {
	        context: 'deal',
	        templateMode: 'create',
	        mode: 'delivery',
	        analyticsLabel: 'crmDealPaymentDocumentsCreateDeliverySlider',
	        ownerTypeId: this._dealEntityType(),
	        ownerId: this._options.DEAL_ID,
	        orderId: orderId
	      };

	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_chooseDeliverySlider",
	    value: function _chooseDeliverySlider(orderId) {
	      var options = {
	        context: 'deal',
	        templateMode: 'create',
	        mode: 'delivery',
	        analyticsLabel: 'crmDealPaymentDocumentsChooseDeliverySlider',
	        ownerTypeId: this._dealEntityType(),
	        ownerId: this._options.DEAL_ID,
	        orderId: orderId
	      };

	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_resendPaymentSlider",
	    value: function _resendPaymentSlider(orderId, paymentId) {
	      var options = {
	        disableSendButton: '',
	        context: 'deal',
	        mode: 'payment_delivery',
	        analyticsLabel: 'crmDealPaymentDocumentsResendPaymentSlider',
	        templateMode: 'view',
	        ownerTypeId: this._dealEntityType(),
	        ownerId: this._options.DEAL_ID,
	        orderId: orderId,
	        paymentId: paymentId
	      };

	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_viewDeliverySlider",
	    value: function _viewDeliverySlider(orderId, shipmentId) {
	      var options = {
	        context: 'deal',
	        templateMode: 'view',
	        mode: 'delivery',
	        analyticsLabel: 'crmDealPaymentDocumentsViewDeliverySlider',
	        ownerTypeId: this._dealEntityType(),
	        ownerId: this._options.DEAL_ID,
	        orderId: orderId,
	        shipmentId: shipmentId
	      };

	      this._context().startSalescenterApplication(orderId, options);
	    }
	  }, {
	    key: "_setPaymentPaidStatus",
	    value: function _setPaymentPaidStatus(payment, isPaid) {
	      var _this6 = this;

	      var strPaid = isPaid ? 'Y' : 'N';
	      var stage = isPaid ? 'PAID' : 'CANCEL';

	      if (payment.PAID && payment.PAID === strPaid) {
	        return;
	      } // positive approach - render success first, then do actual query


	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'PAYMENT' && doc.ID === payment.ID) {
	          doc.PAID = strPaid;
	          doc.STAGE = stage;
	        }
	      });

	      this.render();

	      var doNothingOnSuccess = function doNothingOnSuccess(response) {};

	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this6.reloadModel();

	        _this6._showCommonError();
	      };

	      main_core.ajax.runAction('sale.payment.setpaid', {
	        data: {
	          id: payment.ID,
	          value: strPaid
	        }
	      }).then(doNothingOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_setShipmentShippedStatus",
	    value: function _setShipmentShippedStatus(shipment, isShipped) {
	      var _this7 = this;

	      var strShipped = isShipped ? 'Y' : 'N';

	      if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped) {
	        return;
	      } // positive approach - render success first, then do actual query


	      this._docs().forEach(function (doc) {
	        if (doc.TYPE === 'SHIPMENT' && doc.ID === shipment.ID) {
	          doc.DEDUCTED = strShipped;
	        }
	      });

	      this.render();

	      var doNothingOnSuccess = function doNothingOnSuccess(response) {};

	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this7.reloadModel();

	        _this7._showCommonError();
	      };

	      main_core.ajax.runAction('sale.shipment.setshipped', {
	        data: {
	          id: shipment.ID,
	          value: strShipped
	        }
	      }).then(doNothingOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_removeDocument",
	    value: function _removeDocument(doc) {
	      var _this8 = this;

	      var action;

	      if (doc.TYPE === 'PAYMENT') {
	        action = 'sale.payment.delete';
	      } else if (doc.TYPE === 'SHIPMENT') {
	        action = 'sale.shipment.delete';
	      } else {
	        return;
	      } // positive approach - render success first, then do actual query


	      this._options.DOCUMENTS = this._options.DOCUMENTS.filter(function (item) {
	        return !(item.TYPE === doc.TYPE && item.ID === doc.ID);
	      });
	      this.render();

	      var doNothingOnSuccess = function doNothingOnSuccess(response) {};

	      var reloadModelOnError = function reloadModelOnError(response) {
	        _this8.reloadModel();

	        _this8._showCommonError();
	      };

	      main_core.ajax.runAction(action, {
	        data: {
	          id: doc.ID
	        }
	      }).then(doNothingOnSuccess, reloadModelOnError);
	    }
	  }, {
	    key: "_showCommonError",
	    value: function _showCommonError() {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_COMMON_ERROR')
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
	    key: "_subscribeToGlobalEvents",
	    value: function _subscribeToGlobalEvents() {
	      var _this9 = this;

	      var events = ['salescenter.app:onshipmentcreated', 'salescenter.app:onpaymentcreated', 'salescenter.app:onpaymentresend'];
	      var timeout = 500;
	      var reloadWidget = main_core.debounce(function () {
	        _this9.reloadModel();
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

	        var orderId = false;

	        var orderIds = _this9._orderIds();

	        if (params.FIELDS && params.FIELDS.ID) {
	          orderId = parseInt(params.FIELDS.ID);
	        }

	        if (orderId && orderIds.indexOf(orderId) > -1) {
	          reloadWidget();
	        }
	      }, inCompatMode);
	      main_core_events.EventEmitter.subscribe('onPullEvent-salescenter', function (command, params) {
	        if (command !== 'onOrderPaymentViewed') {
	          return;
	        }

	        var orderId = false;

	        var orderIds = _this9._orderIds();

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
	  }]);
	  return EntityEditorPaymentDocuments;
	}();
	babelHelpers.defineProperty(EntityEditorPaymentDocuments, "_rootNodeClass", 'crm-entity-widget-inner crm-entity-widget-inner--payment');

	function _templateObject5$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$1 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-document-description\">\n\t\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">\n\t\t\t\t\t\t", " (", ")\n\t\t\t\t\t</a>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-document-description\">\n\t\t\t\t\t<a class=\"ui-link\" onclick=\"", "\">", "</a>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row\">\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-cost\">\n\t\t\t\t\t<span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TimelineSummaryDocuments = /*#__PURE__*/function (_EntityEditorPaymentD) {
	  babelHelpers.inherits(TimelineSummaryDocuments, _EntityEditorPaymentD);

	  function TimelineSummaryDocuments() {
	    babelHelpers.classCallCheck(this, TimelineSummaryDocuments);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TimelineSummaryDocuments).apply(this, arguments));
	  }

	  babelHelpers.createClass(TimelineSummaryDocuments, [{
	    key: "_renderDealTotalSum",
	    value: function _renderDealTotalSum() {
	      return main_core.Tag.render(_templateObject$1(), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DEAL_SUM'), this._renderMoney(this._options.DEAL_AMOUNT));
	    }
	  }, {
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

	        this._rootNode.append(main_core.Tag.render(_templateObject2$1(), this._renderDealTotalSum(), this._renderDocuments(), this._renderTotalSum()));
	      } else {
	        this._rootNode.classList.add('is-hidden');
	      }

	      return this._rootNode;
	    }
	  }, {
	    key: "_renderPaymentDocument",
	    value: function _renderPaymentDocument(doc) {
	      var _this = this;

	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
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
	        return _this._resendPaymentSlider(doc.ORDER_ID, doc.ID);
	      };

	      return main_core.Tag.render(_templateObject3$1(), openSlider, title, new ui_label.Label(labelOptions).render(), this._renderMoney(doc.SUM));
	    }
	  }, {
	    key: "_renderShipmentDocument",
	    value: function _renderShipmentDocument(doc) {
	      var _this2 = this;

	      var labelOptions = {
	        text: main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
	        customClass: 'crm-entity-widget-payment-label',
	        color: ui_label.LabelColor.LIGHT,
	        fill: true
	      };

	      if (doc.DEDUCTED === 'Y') {
	        labelOptions.text = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
	        labelOptions.color = ui_label.LabelColor.LIGHT_GREEN;
	      }

	      var title = main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);

	      var openSlider = function openSlider() {
	        return _this2._viewDeliverySlider(doc.ORDER_ID, doc.ID);
	      };

	      return main_core.Tag.render(_templateObject4$1(), openSlider, title, doc.DELIVERY_NAME, new ui_label.Label(labelOptions).render(), this._renderMoney(doc.SUM));
	    }
	  }, {
	    key: "_renderTotalSum",
	    value: function _renderTotalSum() {
	      var totalSum = this._calculateTotalSum();

	      return main_core.Tag.render(_templateObject5$1(), main_core.Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM'), this._renderMoney(totalSum));
	    }
	  }, {
	    key: "_filterSuccessfulDocuments",
	    value: function _filterSuccessfulDocuments() {
	      this._options.DOCUMENTS = this._options.DOCUMENTS.filter(function (item) {
	        return item.TYPE === 'PAYMENT' && item.PAID === 'Y' || item.TYPE === 'SHIPMENT' && item.DEDUCTED === 'Y';
	      });
	    }
	  }]);
	  return TimelineSummaryDocuments;
	}(EntityEditorPaymentDocuments);
	babelHelpers.defineProperty(TimelineSummaryDocuments, "_rootNodeClass", 'crm-entity-stream-content-detail-table crm-entity-stream-content-documents-table');

	exports.EntityEditorPaymentDocuments = EntityEditorPaymentDocuments;
	exports.TimelineSummaryDocuments = TimelineSummaryDocuments;

}((this.BX.Crm = this.BX.Crm || {}),BX.Event,BX.Main,BX.Currency,BX.UI.Dialogs,BX,BX.UI));
//# sourceMappingURL=payment-documents.bundle.js.map
