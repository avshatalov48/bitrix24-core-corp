(function (exports,main_core,main_popup,ui_buttons,crm_terminal,main_core_events) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var TerminalPaymentList = /*#__PURE__*/function () {
	  function TerminalPaymentList() {
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
	  }
	  babelHelpers.createClass(TerminalPaymentList, [{
	    key: "deletePayment",
	    value: function deletePayment(id) {
	      var _this = this;
	      var popup = new main_popup.Popup({
	        id: 'crm_terminal_payment_list_delete_popup',
	        titleBar: main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_TITLE'),
	        content: main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_CONTENT'),
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CONTINUE'),
	          color: ui_buttons.ButtonColor.SUCCESS,
	          onclick: function onclick(button, event) {
	            button.setDisabled();
	            main_core.ajax.runAction('crm.order.terminalpayment.delete', {
	              data: {
	                id: id
	              }
	            }).then(function (response) {
	              popup.destroy();
	              _this.grid.reload();
	            })["catch"](function (response) {
	              if (response.errors) {
	                BX.UI.Notification.Center.notify({
	                  content: BX.util.htmlspecialchars(response.errors[0].message)
	                });
	              }
	              popup.destroy();
	            });
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CANCEL'),
	          color: ui_buttons.ButtonColor.DANGER,
	          onclick: function onclick(button, event) {
	            popup.destroy();
	          }
	        })]
	      });
	      popup.show();
	    }
	  }, {
	    key: "deletePayments",
	    value: function deletePayments() {
	      var _this2 = this;
	      var paymentIds = this.grid.getRows().getSelectedIds();
	      main_core.ajax.runAction('crm.order.terminalpayment.deleteList', {
	        data: {
	          ids: paymentIds
	        }
	      }).then(function (response) {
	        _this2.grid.reload();
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
	        _this2.grid.reload();
	      });
	    }
	  }, {
	    key: "openSmsSettingsSlider",
	    value: function openSmsSettingsSlider() {
	      BX.SidePanel.Instance.open(this.settingsSliderUrl, {
	        width: 700,
	        cacheable: false,
	        allowChangeTitle: false,
	        allowChangeHistory: false
	      });
	    }
	  }, {
	    key: "openQrAuthPopup",
	    value: function openQrAuthPopup() {
	      new crm_terminal.QrAuth().show();
	    }
	  }, {
	    key: "openHelpdesk",
	    value: function openHelpdesk(event) {
	      if (top.BX.Helper) {
	        top.BX.Helper.show("redirect=detail&code=17603024");
	        event.preventDefault();
	      }
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
	  }]);
	  return TerminalPaymentList;
	}();
	namespace.TerminalPaymentList = TerminalPaymentList;

}((this.window = this.window || {}),BX,BX.Main,BX.UI,BX.Crm,BX.Event));
//# sourceMappingURL=script.js.map
