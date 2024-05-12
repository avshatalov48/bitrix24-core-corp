/* eslint-disable */
(function (exports,crm_conversion,crm_integration_analytics,crm_itemDetailsComponent,main_core,main_core_events,main_popup,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2;
	var printWindowWidth = 900;
	var printWindowHeight = 600;
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var QuoteDetailsComponent = /*#__PURE__*/function (_ItemDetailsComponent) {
	  babelHelpers.inherits(QuoteDetailsComponent, _ItemDetailsComponent);
	  function QuoteDetailsComponent(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, QuoteDetailsComponent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(QuoteDetailsComponent).call(this, params));
	    if (main_core.Type.isPlainObject(params)) {
	      _this.activityEditorId = params.activityEditorId;
	      if (main_core.Type.isPlainObject(params.emailSettings)) {
	        _this.emailSettings = params.emailSettings;
	      }
	      if (main_core.Type.isArray(params.printTemplates)) {
	        _this.printTemplates = params.printTemplates;
	        _this.isMultipleTemplates = Boolean(_this.printTemplates.length > 1);
	      }
	      if (main_core.Type.isPlainObject(params.conversion)) {
	        _this.conversionSettings = params.conversion;
	      }
	    }
	    return _this;
	  }
	  babelHelpers.createClass(QuoteDetailsComponent, [{
	    key: "init",
	    value: function init() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(QuoteDetailsComponent.prototype), "init", this).call(this);
	      if (this.conversionSettings) {
	        this.initConversionApi();
	      }
	    }
	  }, {
	    key: "initConversionApi",
	    value: function initConversionApi() {
	      var _this2 = this;
	      var converter = crm_conversion.Conversion.Manager.Instance.initializeConverter(this.entityTypeId, this.conversionSettings.converter);
	      var schemeSelector = new crm_conversion.Conversion.SchemeSelector(converter, this.conversionSettings.schemeSelector);
	      if (this.conversionSettings.lockScript) {
	        schemeSelector.subscribe('onSchemeSelected', this.conversionSettings.lockScript);
	        schemeSelector.subscribe('onContainerClick', this.conversionSettings.lockScript);
	        main_core_events.EventEmitter.subscribe('CrmCreateDealFromQuote', this.conversionSettings.lockScript);
	        main_core_events.EventEmitter.subscribe('CrmCreateInvoiceFromQuote', this.conversionSettings.lockScript);
	      } else {
	        schemeSelector.enableAutoConversion();
	        var convertByEvent = function convertByEvent(dstEntityTypeId) {
	          var schemeItem = converter.getConfig().getScheme().getItemForSingleEntityTypeId(dstEntityTypeId);
	          if (!schemeItem) {
	            console.error('SchemeItem with single entityTypeId ' + dstEntityTypeId + ' is not found');
	            return;
	          }
	          converter.getConfig().updateFromSchemeItem(schemeItem);
	          converter.setAnalyticsElement(crm_integration_analytics.Dictionary.ELEMENT_CREATE_LINKED_ENTITY_BUTTON);
	          converter.convert(_this2.id);
	        };
	        main_core_events.EventEmitter.subscribe('CrmCreateDealFromQuote', function () {
	          convertByEvent(BX.CrmEntityType.enumeration.deal);
	        });
	        main_core_events.EventEmitter.subscribe('CrmCreateInvoiceFromQuote', function () {
	          convertByEvent(BX.CrmEntityType.enumeration.invoice);
	        });
	        main_core_events.EventEmitter.subscribe('BX.Crm.ItemListComponent:onAddNewItemButtonClick', function (event) {
	          var dstEntityTypeId = Number(event.getData().entityTypeId);
	          if (dstEntityTypeId > 0) {
	            convertByEvent(dstEntityTypeId);
	          }
	        });
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(QuoteDetailsComponent.prototype), "bindEvents", this).call(this);
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickPrint', this.handlePrintOrPdf.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickPdf', this.handlePrintOrPdf.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickEmail', this.handleEmail.bind(this));
	    }
	  }, {
	    key: "handlePrintOrPdf",
	    value: function handlePrintOrPdf(event) {
	      var _this3 = this;
	      if (!this.validatePrintTemplates()) {
	        return;
	      }
	      var link = this.normalizeUrl(new main_core.Uri(event.getData().link));
	      var openInNewWindow = Boolean(event.getData().openInNewWindow);
	      if (this.isMultipleTemplates) {
	        this.openTemplateSelectDialog().then(function (templateId) {
	          _this3.openPrintWindow(link, templateId, openInNewWindow);
	        })["catch"](function () {});
	      } else {
	        var selectedPrintTemplate = this.getSinglePrintTemplate();
	        this.openPrintWindow(link, selectedPrintTemplate.id, openInNewWindow);
	      }
	    }
	  }, {
	    key: "validatePrintTemplates",
	    value: function validatePrintTemplates() {
	      if (!main_core.Type.isArray(this.printTemplates) || this.printTemplates.length <= 0) {
	        this.showError(this.messages.errorNoPrintTemplates);
	        return false;
	      }
	      return true;
	    }
	  }, {
	    key: "getSinglePrintTemplate",
	    value: function getSinglePrintTemplate() {
	      return this.printTemplates[this.printTemplates.length - 1];
	    }
	  }, {
	    key: "openTemplateSelectDialog",
	    value: function openTemplateSelectDialog() {
	      var _this4 = this;
	      return new Promise(function (resolve, reject) {
	        var templateSelectDialogContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form ui-form-line\">\n\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t<select class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this4.messages.template);
	        var select = templateSelectDialogContent.querySelector('select');
	        _this4.printTemplates.forEach(function (_ref) {
	          var id = _ref.id,
	            name = _ref.name;
	          select.appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral([" <option value=\"", "\">", "</option> "])), main_core.Text.encode(id), main_core.Text.encode(name)));
	        });
	        var popup = new main_popup.Popup({
	          titleBar: _this4.messages.selectTemplate,
	          content: templateSelectDialogContent,
	          closeByEsc: true,
	          closeIcon: true,
	          buttons: [new ui_buttons.Button({
	            text: _this4.messages.print,
	            onclick: function onclick(button, event) {
	              var selectedTemplateId = select.value;
	              popup.destroy();
	              resolve(Number(selectedTemplateId));
	            }
	          })],
	          events: {
	            onClose: function onClose() {
	              reject('Template select dialog was closed');
	            }
	          }
	        });
	        popup.show();
	      });
	    }
	  }, {
	    key: "openPrintWindow",
	    value: function openPrintWindow(link, templateId, openInNewWindow) {
	      link.setQueryParam('PAY_SYSTEM_ID', templateId);
	      if (openInNewWindow) {
	        jsUtils.OpenWindow(link.toString(), printWindowWidth, printWindowHeight);
	      } else {
	        jsUtils.Redirect([], link.toString());
	      }
	    }
	  }, {
	    key: "handleEmail",
	    value: function handleEmail() {
	      var _this5 = this;
	      if (!this.validatePrintTemplates()) {
	        return;
	      }
	      if (!this.emailSettings) {
	        this.showError(this.messages.errorNoEmailSettings);
	        return;
	      }
	      if (this.isMultipleTemplates) {
	        this.openTemplateSelectDialog().then(function (templateId) {
	          _this5.sendViaEmail(templateId);
	        })["catch"](function () {});
	      } else {
	        var selectedPrintTemplate = this.getSinglePrintTemplate();
	        this.sendViaEmail(selectedPrintTemplate.id);
	      }
	    }
	  }, {
	    key: "sendViaEmail",
	    value: function sendViaEmail(templateId) {
	      var _this6 = this;
	      this.emailSettings.ownerPSID = templateId;
	      if (!top.BX.SidePanel.Instance) {
	        this.modifyEmailSettings(this.emailSettings).then(function (emailSettings) {
	          _this6.getActivityEditor().addEmail(emailSettings);
	        })["catch"](this.showErrorsFromResponse.bind(this));
	        return;
	      }
	      this.getActivityEditor().addEmail(this.emailSettings);
	    }
	  }, {
	    key: "modifyEmailSettings",
	    value: function modifyEmailSettings(emailSettings) {
	      return main_core.ajax.runComponentAction('bitrix:crm.quote.details', 'createEmailAttachment', {
	        mode: 'class',
	        signedParameters: this.signedParameters,
	        analyticsLabel: 'crmQuoteDetailsSendViaEmail',
	        data: {
	          entityTypeId: this.entityTypeId,
	          id: this.id,
	          paymentSystemId: emailSettings.ownerPSID
	        }
	      }).then(function (response) {
	        var data = response.data;
	        emailSettings.storageTypeID = data['STORAGE_TYPE_ID'];
	        if (emailSettings.storageTypeID === BX.CrmActivityStorageType.webdav) {
	          emailSettings.webdavelements = [data];
	        } else if (emailSettings.storageTypeID === BX.CrmActivityStorageType.disk) {
	          emailSettings.diskfiles = [Number(data.ID)];
	        } else if (emailSettings.storageTypeID === BX.CrmActivityStorageType.file) {
	          emailSettings.files = [data];
	        }
	        return emailSettings;
	      });
	    }
	  }, {
	    key: "getActivityEditor",
	    value: function getActivityEditor() {
	      return BX.CrmActivityEditor.items[this.activityEditorId];
	    }
	  }]);
	  return QuoteDetailsComponent;
	}(crm_itemDetailsComponent.ItemDetailsComponent);
	namespace.QuoteDetailsComponent = QuoteDetailsComponent;

}((this.window = this.window || {}),BX.Crm,BX.Crm.Integration.Analytics,BX.Crm,BX,BX.Event,BX.Main,BX.UI));
//# sourceMappingURL=script.js.map
