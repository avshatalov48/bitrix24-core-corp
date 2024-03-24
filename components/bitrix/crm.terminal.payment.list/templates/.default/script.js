/* eslint-disable */
(function (exports,main_core,crm_terminal,main_core_events,ui_dialogs_messagebox) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var TerminalPaymentList = /*#__PURE__*/function () {
	  function TerminalPaymentList() {
	    var _this = this;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, TerminalPaymentList);
	    babelHelpers.defineProperty(this, "grid", null);
	    babelHelpers.defineProperty(this, "settingsSliderUrl", '');
	    this.gridId = options.gridId;
	    if (BX.Main.gridManager) {
	      this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	    }
	    this.settingsSliderUrl = options.settingsSliderUrl;
	    main_core_events.EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler.bind(this));
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	      var eventId = event.getData()[0].eventId;
	      if (eventId === 'salescenter.app:onterminalpaymentupdated') {
	        _this.grid.reload();
	      }
	    });
	  }
	  babelHelpers.createClass(TerminalPaymentList, [{
	    key: "setPaidStatus",
	    value: function setPaidStatus(id, value) {
	      var _this2 = this;
	      this.grid.tableFade();
	      main_core.ajax.runAction('crm.order.payment.setPaid', {
	        data: {
	          id: id,
	          value: value
	        }
	      }).then(function (response) {
	        _this2.grid.reload();
	      }, function (response) {
	        _this2.grid.tableUnfade();
	        response.errors.forEach(function (error) {
	          BX.UI.Notification.Center.notify({
	            content: main_core.Text.encode(error.message)
	          });
	        });
	      });
	    }
	  }, {
	    key: "deletePayment",
	    value: function deletePayment(id) {
	      var _this3 = this;
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_CONTENT'), function (messageBox, button) {
	        button.setWaiting();
	        main_core.ajax.runAction('crm.order.payment.delete', {
	          data: {
	            id: id
	          }
	        }).then(function (response) {
	          messageBox.close();
	          _this3.grid.reload();
	        })["catch"](function (response) {
	          if (response.errors) {
	            BX.UI.Notification.Center.notify({
	              content: main_core.Text.encode(response.errors[0].message)
	            });
	          }
	          messageBox.close();
	        });
	      }, main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CONFIRM'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_BACK'));
	    }
	  }, {
	    key: "deletePayments",
	    value: function deletePayments() {
	      var _this4 = this;
	      var paymentIds = this.grid.getRows().getSelectedIds();
	      main_core.ajax.runAction('crm.order.payment.deleteList', {
	        data: {
	          ids: paymentIds
	        }
	      }).then(function (response) {
	        _this4.grid.reload();
	      })["catch"](function (response) {
	        if (response.errors) {
	          response.errors.forEach(function (error) {
	            if (error.message) {
	              BX.UI.Notification.Center.notify({
	                content: BX.util.htmlspecialchars(error.message)
	              });
	            }
	          });
	        }
	        _this4.grid.reload();
	      });
	    }
	  }, {
	    key: "openTerminalSettingsSlider",
	    value: function openTerminalSettingsSlider(event, menuItem) {
	      BX.SidePanel.Instance.open(this.settingsSliderUrl, {
	        width: 900,
	        cacheable: false,
	        allowChangeTitle: false,
	        allowChangeHistory: false
	      });
	      menuItem.getMenuWindow().close();
	    }
	  }, {
	    key: "openQrAuthPopup",
	    value: function openQrAuthPopup() {
	      new crm_terminal.QrAuth().show();
	    }
	  }, {
	    key: "openHelpdesk",
	    value: function openHelpdesk(event, menuItem) {
	      if (top.BX.Helper) {
	        top.BX.Helper.show('redirect=detail&code=17603024');
	        event.preventDefault();
	      }
	      menuItem.getMenuWindow().close();
	    }
	  }, {
	    key: "onGridUpdatedHandler",
	    value: function onGridUpdatedHandler(event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	        grid = _event$getCompatData2[0];
	      if (grid && grid.getId() === this.gridId && grid.getRows().getCountDisplayed() === 0) {
	        main_core.ajax.runComponentAction('bitrix:crm.terminal.payment.list', 'isRowsExists', {
	          mode: 'class',
	          data: {}
	        }).then(function (response) {
	          if (response.data.IS_ROWS_EXIST === false) {
	            window.location.reload();
	          }
	        }, function () {
	          window.location.reload();
	        });
	      }
	    }
	  }, {
	    key: "openPaymentInSalescenter",
	    value: function openPaymentInSalescenter(params) {
	      var options = {
	        context: 'terminal_list',
	        mode: 'terminal_payment',
	        analyticsLabel: 'terminal_payment_list_view_payment',
	        templateMode: 'view',
	        orderId: params.orderId,
	        paymentId: params.paymentId
	      };
	      BX.Salescenter.Manager.openApplication(options);
	    }
	  }]);
	  return TerminalPaymentList;
	}();
	namespace.TerminalPaymentList = TerminalPaymentList;

}((this.window = this.window || {}),BX,BX.Crm,BX.Event,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map
