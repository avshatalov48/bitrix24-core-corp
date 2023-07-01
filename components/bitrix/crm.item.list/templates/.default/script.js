(function (exports,main_core,main_core_events,ui_dialogs_messagebox,crm_router) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var _isUniversalActivityScenarioEnabled = /*#__PURE__*/new WeakMap();
	var _isIframe = /*#__PURE__*/new WeakMap();
	var _smartActivityNotificationSupported = /*#__PURE__*/new WeakMap();
	var _isEmbedded = /*#__PURE__*/new WeakMap();
	var _getToolbarComponent = /*#__PURE__*/new WeakSet();
	var _initPushCrmSettings = /*#__PURE__*/new WeakSet();
	var ItemListComponent = /*#__PURE__*/function () {
	  // is the list is embedded in another entity detail tab

	  function ItemListComponent(params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, ItemListComponent);
	    _classPrivateMethodInitSpec(this, _initPushCrmSettings);
	    _classPrivateMethodInitSpec(this, _getToolbarComponent);
	    _classPrivateFieldInitSpec(this, _isUniversalActivityScenarioEnabled, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _isIframe, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _smartActivityNotificationSupported, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _isEmbedded, {
	      writable: true,
	      value: false
	    });
	    this.exportPopups = {};
	    if (main_core.Type.isPlainObject(params)) {
	      this.entityTypeId = main_core.Text.toInteger(params.entityTypeId);
	      this.entityTypeName = params.entityTypeName;
	      this.categoryId = main_core.Text.toInteger(params.categoryId);
	      babelHelpers.classPrivateFieldSet(this, _smartActivityNotificationSupported, main_core.Text.toBoolean(params.smartActivityNotificationSupported));
	      if (main_core.Type.isString(params.gridId)) {
	        this.gridId = params.gridId;
	      }
	      if (this.gridId && BX.Main.grid && BX.Main.gridManager) {
	        this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	        if (this.grid && params.backendUrl) {
	          BX.addCustomEvent(window, "Grid::beforeRequest", function (gridData, requestParams) {
	            if (!gridData.parent || gridData.parent !== _this.grid) {
	              return;
	            }
	            var currentUrl = new main_core.Uri(requestParams.url);
	            var backendUrl = new main_core.Uri(params.backendUrl);
	            if (currentUrl.getPath() !== backendUrl.getPath()) {
	              currentUrl.setPath(backendUrl.getPath());
	              currentUrl.setQueryParams(_objectSpread(_objectSpread({}, currentUrl.getQueryParams()), backendUrl.getQueryParams()));
	            }
	            requestParams.url = currentUrl.toString();
	          });
	        }
	      }
	      if (main_core.Type.isElementNode(params.errorTextContainer)) {
	        this.errorTextContainer = params.errorTextContainer;
	      }
	      if (main_core.Type.isBoolean(params.isUniversalActivityScenarioEnabled)) {
	        babelHelpers.classPrivateFieldSet(this, _isUniversalActivityScenarioEnabled, params.isUniversalActivityScenarioEnabled);
	      }
	      if (main_core.Type.isBoolean(params.isIframe)) {
	        babelHelpers.classPrivateFieldSet(this, _isIframe, params.isIframe);
	      }
	      if (main_core.Type.isBoolean(params.isEmbedded)) {
	        babelHelpers.classPrivateFieldSet(this, _isEmbedded, params.isEmbedded);
	      }
	    }
	    this.reloadGridTimeoutId = 0;
	  }
	  babelHelpers.createClass(ItemListComponent, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	      _classPrivateMethodGet(this, _initPushCrmSettings, _initPushCrmSettings2).call(this);
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemListComponent:onClickDelete', this.handleItemDelete.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemListComponent:onStartExportCsv', function (event) {
	        _this2.handleStartExport(event, 'csv');
	      });
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemListComponent:onStartExportExcel', function (event) {
	        _this2.handleStartExport(event, 'excel');
	      });
	      var toolbarComponent = _classPrivateMethodGet(this, _getToolbarComponent, _getToolbarComponent2).call(this);
	      if (toolbarComponent) {
	        toolbarComponent.subscribeTypeUpdatedEvent(function () {
	          var newUrl = crm_router.Router.Instance.getItemListUrl(_this2.entityTypeId, _this2.categoryId);
	          if (newUrl) {
	            window.location.href = newUrl;
	            return;
	          }
	          window.location.reload();
	        });
	        if (this.grid) {
	          toolbarComponent.subscribeCategoriesUpdatedEvent(function () {
	            _this2.reloadGridAfterTimeout();
	          });
	        }
	      }
	      main_core_events.EventEmitter.subscribe('Crm.EntityConverter.Converted', function (event) {
	        var parameters = event.data;
	        if (!main_core.Type.isArray(parameters) || !parameters[1]) {
	          return;
	        }
	        var eventData = parameters[1];
	        if (!_this2.entityTypeName || !eventData.entityTypeName) {
	          return;
	        }
	        _this2.reloadGridAfterTimeout();
	      });
	      main_core_events.EventEmitter.subscribe("onLocalStorageSet", function (event) {
	        var parameters = event.data;
	        if (!main_core.Type.isArray(parameters) || !parameters[0]) {
	          return;
	        }
	        var params = parameters[0];
	        var key = params.key || '';
	        if (key !== "onCrmEntityCreate" && key !== "onCrmEntityUpdate" && key !== "onCrmEntityDelete" && key !== "onCrmEntityConvert") {
	          return;
	        }
	        var eventData = params.value;
	        if (!main_core.Type.isPlainObject(eventData)) {
	          return;
	        }
	        if (!_this2.entityTypeName || !eventData.entityTypeName) {
	          return;
	        }
	        _this2.reloadGridAfterTimeout();
	      });
	      var addItemButton = document.querySelector('[data-role="add-new-item-button-' + this.gridId + '"]');
	      if (addItemButton) {
	        var detailUrl = addItemButton.href;
	        addItemButton.href = "javascript: void(0);";
	        main_core.Event.bind(addItemButton, 'click', function (event) {
	          event.preventDefault();
	          event.stopPropagation();
	          main_core_events.EventEmitter.emit("BX.Crm.ItemListComponent:onAddNewItemButtonClick", {
	            detailUrl: detailUrl,
	            entityTypeId: _this2.entityTypeId
	          });
	        });
	      }
	    }
	  }, {
	    key: "reloadGridAfterTimeout",
	    value: function reloadGridAfterTimeout() {
	      var _this3 = this;
	      if (!this.grid) {
	        return;
	      }
	      if (this.reloadGridTimeoutId > 0) {
	        clearTimeout(this.reloadGridTimeoutId);
	        this.reloadGridTimeoutId = 0;
	      }
	      this.reloadGridTimeoutId = setTimeout(function () {
	        _this3.grid.reload();
	      }, 1000);
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var text = '';
	      errors.forEach(function (message) {
	        text = text + message + ' ';
	      });
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = text;
	        this.errorTextContainer.parentElement.style.display = 'block';
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = '';
	        this.errorTextContainer.parentElement.style.display = 'none';
	      }
	    }
	  }, {
	    key: "showErrorsFromResponse",
	    value: function showErrorsFromResponse(_ref) {
	      var errors = _ref.errors;
	      var messages = [];
	      errors.forEach(function (_ref2) {
	        var message = _ref2.message;
	        return messages.push(message);
	      });
	      this.showErrors(messages);
	    } // region EventHandlers
	  }, {
	    key: "handleItemDelete",
	    value: function handleItemDelete(event) {
	      var _this4 = this;
	      var entityTypeId = main_core.Text.toInteger(event.data.entityTypeId);
	      var id = main_core.Text.toInteger(event.data.id);
	      if (!entityTypeId) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
	        return;
	      }
	      if (!id) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_ITEM_NOT_FOUND')]);
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE'),
	        message: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          main_core.ajax.runAction('crm.controller.item.delete', {
	            analyticsLabel: 'crmItemListDeleteItem',
	            data: {
	              entityTypeId: entityTypeId,
	              id: id
	            }
	          }).then(function () {
	            BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_NOTIFICATION')
	            });
	            _this4.reloadGridAfterTimeout();
	          })["catch"](_this4.showErrorsFromResponse.bind(_this4));
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "handleStartExport",
	    value: function handleStartExport(event, exportType) {
	      this.getExportPopup(exportType).then(function (process) {
	        return process.showDialog();
	      });
	    } //endregion
	  }, {
	    key: "getExportPopup",
	    value: function getExportPopup(exportType) {
	      var _this5 = this;
	      if (this.exportPopups[exportType]) {
	        return Promise.resolve(this.exportPopups[exportType]);
	      }
	      return main_core.Runtime.loadExtension('ui.stepprocessing').then(function (exports) {
	        _this5.exportPopups[exportType] = exports.ProcessManager.create({
	          id: 'crm.item.list.export.' + exportType,
	          controller: 'bitrix:crm.api.itemExport',
	          queue: [{
	            action: 'dispatcher'
	          }],
	          params: {
	            SITE_ID: main_core.Loc.getMessage('SITE_ID'),
	            entityTypeId: _this5.entityTypeId,
	            categoryId: _this5.categoryId,
	            EXPORT_TYPE: exportType,
	            COMPONENT_NAME: 'bitrix:crm.item.list'
	          },
	          messages: {
	            DialogTitle: main_core.Loc.getMessage('CRM_ITEM_EXPORT_' + exportType.toUpperCase() + '_TITLE'),
	            DialogSummary: main_core.Loc.getMessage('CRM_ITEM_EXPORT_' + exportType.toUpperCase() + '_SUMMARY')
	          },
	          dialogMaxWidth: '650'
	        });
	        _this5.exportPopups[exportType].setHandler(BX.UI.StepProcessing.ProcessCallback.StepCompleted, function (formatInner) {
	          return function () {
	            delete _this5.exportPopups[formatInner];
	          };
	        }(exportType));
	        return _this5.exportPopups[exportType];
	      });
	    }
	  }]);
	  return ItemListComponent;
	}();
	function _getToolbarComponent2() {
	  var component = main_core.Reflection.getClass('BX.Crm.ToolbarComponent');
	  return component ? component.Instance : null;
	}
	function _initPushCrmSettings2() {
	  var _this6 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _isUniversalActivityScenarioEnabled) || babelHelpers.classPrivateFieldGet(this, _isIframe) || babelHelpers.classPrivateFieldGet(this, _isEmbedded)) {
	    return;
	  }
	  var toolbar = _classPrivateMethodGet(this, _getToolbarComponent, _getToolbarComponent2).call(this);
	  if (!toolbar) {
	    console.error('BX.Crm.ToolbarComponent not found');
	    return;
	  }
	  main_core.Runtime.loadExtension('crm.push-crm-settings').then(function (_ref3) {
	    var _toolbar$getSettingsB;
	    var PushCrmSettings = _ref3.PushCrmSettings;
	    /** @see BX.Crm.PushCrmSettings */
	    new PushCrmSettings({
	      smartActivityNotificationSupported: babelHelpers.classPrivateFieldGet(_this6, _smartActivityNotificationSupported),
	      entityTypeId: _this6.entityTypeId,
	      rootMenu: (_toolbar$getSettingsB = toolbar.getSettingsButton()) === null || _toolbar$getSettingsB === void 0 ? void 0 : _toolbar$getSettingsB.getMenuWindow(),
	      grid: _this6.grid
	    });
	  });
	}
	namespace.ItemListComponent = ItemListComponent;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs,BX.Crm));
//# sourceMappingURL=script.js.map
