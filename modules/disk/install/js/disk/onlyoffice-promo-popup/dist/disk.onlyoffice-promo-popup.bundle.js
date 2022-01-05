this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,main_core_events,ui_dialogs_messagebox) {
	'use strict';

	var _templateObject, _templateObject2;

	var PromoPopup = /*#__PURE__*/function () {
	  function PromoPopup() {
	    babelHelpers.classCallCheck(this, PromoPopup);
	    babelHelpers.defineProperty(this, "userBoxNode", null);
	  }

	  babelHelpers.createClass(PromoPopup, null, [{
	    key: "shouldBlockViewAndEdit",
	    value: function shouldBlockViewAndEdit() {
	      if (!BX.Disk.isAvailableOnlyOffice()) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_can_view'] === undefined) {
	        return false;
	      }

	      return BX.message['disk_onlyoffice_can_view'] == false;
	    }
	  }, {
	    key: "shouldShowViewPromo",
	    value: function shouldShowViewPromo() {
	      if (!BX.Disk.isAvailableOnlyOffice()) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_got_promo_about'] === undefined) {
	        return false;
	      }

	      return BX.message['disk_onlyoffice_got_promo_about'] == false;
	    }
	  }, {
	    key: "shouldShowEndDemo",
	    value: function shouldShowEndDemo() {
	      if (!BX.Disk.isAvailableOnlyOffice()) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_demo_ended'] == false) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_got_end_demo'] === undefined) {
	        return false;
	      }

	      return BX.message['disk_onlyoffice_got_end_demo'] == false;
	    }
	  }, {
	    key: "shouldShowEditPromo",
	    value: function shouldShowEditPromo() {
	      if (!BX.Disk.isAvailableOnlyOffice()) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_can_edit'] === undefined) {
	        return false;
	      }

	      return BX.message['disk_onlyoffice_can_edit'] == false;
	    }
	  }, {
	    key: "canEdit",
	    value: function canEdit() {
	      if (!BX.Disk.isAvailableOnlyOffice()) {
	        return false;
	      }

	      if (BX.message['disk_onlyoffice_can_edit'] === undefined) {
	        return false;
	      }

	      return BX.message['disk_onlyoffice_can_edit'] == true;
	    }
	  }, {
	    key: "registerView",
	    value: function registerView(optionName) {
	      BX.userOptions.save('disk', optionName, 'v', 1);
	      BX.userOptions.send(null);

	      if (optionName === 'got_promo_onlyoffice') {
	        BX.message['disk_onlyoffice_got_promo_about'] = true;
	      } else if (optionName === 'got_end_demo_onlyoffice') {
	        BX.message['disk_onlyoffice_got_end_demo'] = true;
	      }
	    }
	  }, {
	    key: "showCommonPromoForNonPaid",
	    value: function showCommonPromoForNonPaid() {
	      if (this.shouldShowEndDemo()) {
	        this.showEndOfDemo();
	      } else if (main_core.Reflection.getClass('BX.UI.InfoHelper')) {
	        BX.UI.InfoHelper.show('limit_office_no_document', {
	          featureId: 'disk_onlyoffice_edit'
	        });
	        main_core_events.EventEmitter.subscribeOnce('BX.UI.InfoHelper:onActivateTrialFeatureSuccess', function () {
	          main_core.ajax.runAction('disk.api.onlyoffice.handleTrialFeatureActivation', {});
	        });
	      }
	    }
	  }, {
	    key: "showEditPromo",
	    value: function showEditPromo() {
	      if (this.shouldShowEndDemo()) {
	        this.showEndOfDemo();
	      } else if (main_core.Reflection.getClass('BX.UI.InfoHelper')) {
	        BX.UI.InfoHelper.show('limit_office_small_documents', {
	          featureId: 'disk_onlyoffice_edit'
	        });
	      }
	    }
	  }, {
	    key: "showViewPromo",
	    value: function showViewPromo() {
	      var _this = this;

	      var firstRow = this.canEdit() ? main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_EDIT_POPUP_1') : main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_1');
	      var content = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div>", "</div>\n\t\t\t\t<div style=\"padding-top: 15px;\">", "</div>\n\t\t\t\t<div style=\"padding-top: 15px;\">", "</div>\n\t\t\t</div>\n\t\t"])), firstRow, main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_2'), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_3'));
	      ui_dialogs_messagebox.MessageBox.show({
	        title: this.canEdit() ? main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_EDIT_POPUP_TITLE') : main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_TITLE'),
	        message: content,
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK,
	        okCaption: main_core.Loc.getMessage('DISK_JS_BTN_CLOSE'),
	        popupOptions: {
	          events: {
	            onPopupShow: function onPopupShow() {
	              _this.registerView('got_promo_onlyoffice');
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "showEndOfDemo",
	    value: function showEndOfDemo() {
	      var _this2 = this;

	      var content = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div>", "</div>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>", "</li>\n\t\t\t\t\t<li>", "</li>\n\t\t\t\t\t<li>", "</li>\n\t\t\t\t</ul>\n\t\t\t\t<div>", "</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_1'), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_1'), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_2'), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_3'), main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_NOTICE'));
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_TITLE'),
	        message: content,
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_CONTINUE_WORK_WITH_DOCS'),
	        onOk: function onOk() {
	          _this2.showEditPromo();

	          return true;
	        },
	        cancelCaption: main_core.Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_SETUP_WORK'),
	        onCancel: function onCancel() {
	          BX.Disk.InformationPopups.openWindowForSelectDocumentService({});
	          return true;
	        },
	        popupOptions: {
	          events: {
	            onPopupShow: function onPopupShow() {
	              _this2.registerView('got_end_demo_onlyoffice');

	              BX.message.disk_document_service = null;
	              main_core.ajax.runAction('disk.api.onlyoffice.handleEndOfTrialFeature', {});
	            }
	          }
	        }
	      });
	    }
	  }]);
	  return PromoPopup;
	}();

	exports.PromoPopup = PromoPopup;

}((this.BX.Disk.OnlyOfficePromo = this.BX.Disk.OnlyOfficePromo || {}),BX,BX.Event,BX.UI.Dialogs));
//# sourceMappingURL=disk.onlyoffice-promo-popup.bundle.js.map
