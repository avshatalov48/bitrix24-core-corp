this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Store = this.BX.Crm.Store || {};
(function (exports,main_core,catalog_entityCard,main_core_events,ui_buttons) {
	'use strict';

	var _templateObject;

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }

	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }

	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }

	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }

	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	var Document = /*#__PURE__*/function (_BaseCard) {
	  babelHelpers.inherits(Document, _BaseCard);

	  function Document(id, settings) {
	    var _this;

	    babelHelpers.classCallCheck(this, Document);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this, id, settings));
	    _this.isDocumentDeducted = settings.documentStatus === 'Y';
	    _this.isDeductLocked = settings.isDeductLocked;
	    _this.masterSliderUrl = settings.masterSliderUrl;

	    _this.addCopyLinkPopup();

	    main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onFailedValidation', function (event) {
	      main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	        tabId: 'main'
	      });
	    });
	    main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', function (event) {
	      var eventEditor = event.data[0];

	      if (eventEditor && eventEditor._ajaxForm) {
	        var _eventEditor$_ajaxFor, _eventEditor$_ajaxFor2;

	        var action = ((_eventEditor$_ajaxFor = eventEditor._ajaxForm) === null || _eventEditor$_ajaxFor === void 0 ? void 0 : _eventEditor$_ajaxFor._actionName) === 'SAVE' ? 'save' : (_eventEditor$_ajaxFor2 = eventEditor._ajaxForm) === null || _eventEditor$_ajaxFor2 === void 0 ? void 0 : _eventEditor$_ajaxFor2._config.data.ACTION;
	        var urlParams = {
	          isNewDocument: _this.entityId <= 0 ? 'Y' : 'N'
	        };

	        if (action) {
	          urlParams.action = action;
	        }

	        eventEditor._ajaxForm.addUrlParams(urlParams);
	      }
	    });
	    main_core_events.EventEmitter.subscribe('BX.Catalog.EntityCard.TabManager:onSelectItem', function (event) {
	      var tabId = event.data.tabId;

	      if (tabId === 'tab_products' && !_this.isTabAnalyticsSent) {
	        _this.sendAnalyticsData({
	          tab: 'products',
	          isNewDocument: _this.entityId <= 0 ? 'Y' : 'N',
	          documentType: 'W'
	        });

	        _this.isTabAnalyticsSent = true;
	      }
	    });

	    _classStaticPrivateFieldSpecSet(Document, Document, _instance, babelHelpers.assertThisInitialized(_this));

	    BX.UI.SidePanel.Wrapper.setParam("closeAfterSave", true);
	    _this.showNotificationOnClose = false;
	    return _this;
	  }

	  babelHelpers.createClass(Document, [{
	    key: "openMasterSlider",
	    value: function openMasterSlider() {
	      var card = this;
	      BX.SidePanel.Instance.open(this.masterSliderUrl, {
	        cacheable: false,
	        data: {
	          openGridOnDone: false
	        },
	        events: {
	          onCloseComplete: function onCloseComplete(event) {
	            var slider = event.getSlider();

	            if (!slider) {
	              return;
	            }

	            if (slider.getData().get('isInventoryManagementEnabled')) {
	              card.isDeductLocked = false;
	              var sliders = BX.SidePanel.Instance.getOpenSliders();
	              sliders.forEach(function (slider) {
	                var _slider$getWindow, _slider$getWindow$BX$;

	                if ((_slider$getWindow = slider.getWindow()) !== null && _slider$getWindow !== void 0 && (_slider$getWindow$BX$ = _slider$getWindow.BX.Catalog) !== null && _slider$getWindow$BX$ !== void 0 && _slider$getWindow$BX$.DocumentGridManager) {
	                  slider.allowChangeHistory = false;
	                  slider.getWindow().location.reload();
	                }
	              });
	            }
	          }
	        }
	      });
	    }
	    /**
	     * adds the "deduct" and "save and deduct" buttons to the tool panel
	     * using entity-editor's api to preserve the logic
	     */

	  }, {
	    key: "adjustToolPanel",
	    value: function adjustToolPanel() {
	      var _this2 = this;

	      var editor = this.getEditorInstance();

	      if (!editor) {
	        return;
	      }

	      var savePanel = editor._toolPanel;
	      var saveButton = editor._toolPanel._editButton;
	      this.defaultSaveActionName = editor._ajaxForm._config.data.ACTION;
	      this.defaultOnSuccessCallback = editor._ajaxForm._config.onsuccess;

	      saveButton.onclick = function (event) {
	        _this2.showNotificationOnClose = false;
	        editor._ajaxForm._config.data.ACTION = _this2.defaultSaveActionName;
	        editor._ajaxForm._config.onsuccess = _this2.defaultOnSuccessCallback;
	        savePanel.onSaveButtonClick(event);
	      };

	      var deductAndSaveButton = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<button class=\"ui-btn ui-btn-light-border\">", "</button>"])), main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_SAVE_AND_DEDUCT_BUTTON'));

	      deductAndSaveButton.onclick = function (event) {
	        if (_this2.isDeductLocked) {
	          _this2.openMasterSlider();

	          return;
	        }

	        editor._ajaxForm._config.data.ACTION = Document.saveAndDeductAction;

	        editor._ajaxForm._config.onsuccess = function (result) {
	          _this2.showNotificationOnClose = true;
	          var error = BX.prop.getString(result, 'ERROR', '');

	          if (!error) {
	            _this2.setViewModeButtons(editor);
	          }

	          editor.onSaveSuccess(result);
	        };

	        savePanel.onSaveButtonClick(event);
	      };

	      saveButton.after(deductAndSaveButton);
	      this.deductAndSaveButton = deductAndSaveButton;
	      var deductButton = new ui_buttons.Button({
	        text: this.isDocumentDeducted ? main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_CANCEL_DEDUCT_BUTTON') : main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_DEDUCT_BUTTON'),
	        color: ui_buttons.ButtonColor.LIGHT_BORDER,
	        onclick: function onclick(button, event) {
	          if (savePanel.isLocked()) {
	            return;
	          }

	          if (_this2.isDeductLocked) {
	            _this2.openMasterSlider();

	            return;
	          }

	          button.setState(ui_buttons.ButtonState.CLOCKING);
	          savePanel.setLocked(true);
	          var formData = {};

	          if (window.EntityEditorDocumentOrderShipmentController) {
	            formData = window.EntityEditorDocumentOrderShipmentController.demandFormData();
	          }

	          var actionName = _this2.isDocumentDeducted ? Document.cancelDeductAction : Document.deductAction;
	          var deductDocumentAjaxForm = editor.createAjaxForm({
	            actionName: actionName,
	            enableRequiredUserFieldCheck: false,
	            formData: formData
	          }, {
	            onSuccess: function onSuccess(result) {
	              if (!_this2.isDocumentDeducted) {
	                _this2.showNotificationOnClose = true;
	              }

	              button.setState(ui_buttons.ButtonState.ACTIVE);
	              editor.onSaveSuccess(result);
	            },
	            onFailure: function onFailure(result) {
	              button.setState(ui_buttons.ButtonState.ACTIVE);
	              editor.onSaveFailure(result);
	            }
	          });
	          deductDocumentAjaxForm.addUrlParams({
	            action: actionName
	          });
	          deductDocumentAjaxForm.submit();
	        }
	      }).render();
	      saveButton.after(deductButton);
	      this.deductButton = deductButton;
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onControlModeChange', function (event) {
	        var eventEditor = event.data[0];
	        var control = event.data[1].control;

	        if (control.getMode() === BX.Crm.EntityEditorMode.edit) {
	          _this2.setEditModeButtons(eventEditor);
	        } else {
	          _this2.setViewModeButtons(eventEditor);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onControlChange', function (event) {
	        var eventEditor = event.data[0];

	        _this2.setEditModeButtons(eventEditor);
	      });
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onControllerChange', function (event) {
	        var eventEditor = event.data[0];

	        _this2.setEditModeButtons(eventEditor);
	      });
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSwitchToViewMode', function (event) {
	        var eventEditor = event.data[0];

	        _this2.setViewModeButtons(eventEditor);
	      });
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onNothingChanged', function (event) {
	        var eventEditor = event.data[0];

	        _this2.setViewModeButtons(eventEditor);
	      });
	      main_core_events.EventEmitter.subscribe('onEntityCreate', function (event) {
	        var _event$data$;

	        var editor = event === null || event === void 0 ? void 0 : (_event$data$ = event.data[0]) === null || _event$data$ === void 0 ? void 0 : _event$data$.sender;

	        if (editor) {
	          editor._toolPanel.disableSaveButton();

	          editor.hideToolPanel();
	        }
	      });
	      main_core_events.EventEmitter.subscribe('beforeCrmEntityRedirect', function (event) {
	        var _event$data$2;

	        var editor = event === null || event === void 0 ? void 0 : (_event$data$2 = event.data[0]) === null || _event$data$2 === void 0 ? void 0 : _event$data$2.sender;

	        if (editor) {
	          editor._toolPanel.disableSaveButton();

	          editor.hideToolPanel();

	          if (_this2.showNotificationOnClose) {
	            var url = event.data[0].redirectUrl;

	            if (!url) {
	              return;
	            }

	            url = BX.Uri.removeParam(url, 'closeOnSave');
	            window.top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_SAVE_AND_CONDUCT_NOTIFICATION'),
	              actions: [{
	                title: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_OPEN_DOCUMENT'),
	                href: url,
	                events: {
	                  click: function click(event, balloon, action) {
	                    balloon.close();
	                  }
	                }
	              }]
	            });
	          }
	        }
	      });

	      if (editor.isNew()) {
	        this.setEditModeButtons(editor);
	      } else {
	        this.setViewModeButtons(editor);
	      }
	    }
	  }, {
	    key: "setViewModeButtons",
	    value: function setViewModeButtons(editor) {
	      if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_cancelButton')) {
	        BX.hide(editor._toolPanel._cancelButton);
	      }

	      if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_editButton')) {
	        BX.hide(editor._toolPanel._editButton);
	      }

	      if (this.deductAndSaveButton) {
	        BX.hide(this.deductAndSaveButton);
	      }

	      if (this.deductButton) {
	        BX.show(this.deductButton);
	      }
	    }
	  }, {
	    key: "setEditModeButtons",
	    value: function setEditModeButtons(editor) {
	      if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_cancelButton')) {
	        BX.show(editor._toolPanel._cancelButton);
	      }

	      if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_editButton')) {
	        BX.show(editor._toolPanel._editButton);
	      }

	      if (this.deductAndSaveButton && !this.isDocumentDeducted) {
	        BX.show(this.deductAndSaveButton);
	      }

	      if (this.deductButton && !this.isDocumentDeducted) {
	        BX.hide(this.deductButton);
	      }
	    }
	  }, {
	    key: "getEditorInstance",
	    value: function getEditorInstance() {
	      if (main_core.Reflection.getClass('BX.Crm.EntityEditor')) {
	        return BX.Crm.EntityEditor.getDefault();
	      }

	      return null;
	    }
	  }, {
	    key: "addCopyLinkPopup",
	    value: function addCopyLinkPopup() {
	      var _this3 = this;

	      var copyLinkButton = document.getElementById(this.settings.copyLinkButtonId);

	      if (!copyLinkButton) {
	        return;
	      }

	      copyLinkButton.onclick = function () {
	        _this3.copyDocumentLinkToClipboard();
	      };
	    }
	  }, {
	    key: "copyDocumentLinkToClipboard",
	    value: function copyDocumentLinkToClipboard() {
	      var url = BX.util.remove_url_param(window.location.href, ["IFRAME", "IFRAME_TYPE"]);

	      if (!BX.clipboard.copy(url)) {
	        return;
	      }

	      var popup = new BX.PopupWindow('catalog_copy_document_url_to_clipboard', document.getElementById(this.settings.copyLinkButtonId), {
	        content: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_LINK_COPIED'),
	        darkMode: true,
	        autoHide: true,
	        zIndex: 1000,
	        angle: true,
	        bindOptions: {
	          position: "top"
	        }
	      });
	      popup.show();
	      setTimeout(function () {
	        popup.close();
	      }, 1500);
	    }
	  }, {
	    key: "sendAnalyticsData",
	    value: function sendAnalyticsData(data) {
	      BX.ajax.runComponentAction('bitrix:crm.store.document.detail', 'sendAnalytics', {
	        mode: 'class',
	        analyticsLabel: data
	      });
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      return _classStaticPrivateFieldSpecGet(Document, Document, _instance);
	    }
	  }]);
	  return Document;
	}(catalog_entityCard.BaseCard);
	var _instance = {
	  writable: true,
	  value: void 0
	};
	babelHelpers.defineProperty(Document, "saveAndDeductAction", 'saveAndDeduct');
	babelHelpers.defineProperty(Document, "deductAction", 'deduct');
	babelHelpers.defineProperty(Document, "cancelDeductAction", 'cancelDeduct');

	exports.Document = Document;

}((this.BX.Crm.Store.DocumentCard = this.BX.Crm.Store.DocumentCard || {}),BX,BX.Catalog.EntityCard,BX.Event,BX.UI));
//# sourceMappingURL=script.js.map
