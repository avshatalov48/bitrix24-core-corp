this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Entity = this.BX.Crm.Entity || {};
(function (exports,main_popup,main_core,main_core_events,catalog_productSelector,catalog_productCalculator,currency_currencyCore) {
	'use strict';

	var PageEventsManager = /*#__PURE__*/function () {
	  function PageEventsManager(settings) {
	    babelHelpers.classCallCheck(this, PageEventsManager);
	    babelHelpers.defineProperty(this, "_settings", {});
	    this._settings = settings ? settings : {};
	    this.eventHandlers = {};
	  }

	  babelHelpers.createClass(PageEventsManager, [{
	    key: "registerEventHandler",
	    value: function registerEventHandler(eventName, eventHandler) {
	      if (!this.eventHandlers[eventName]) this.eventHandlers[eventName] = [];
	      this.eventHandlers[eventName].push(eventHandler);
	      BX.addCustomEvent(this, eventName, eventHandler);
	    }
	  }, {
	    key: "fireEvent",
	    value: function fireEvent(eventName, eventParams) {
	      BX.onCustomEvent(this, eventName, eventParams);
	    }
	  }, {
	    key: "unregisterEventHandlers",
	    value: function unregisterEventHandlers(eventName) {
	      if (this.eventHandlers[eventName]) {
	        for (var i = 0; i < this.eventHandlers[eventName].length; i++) {
	          BX.removeCustomEvent(this, eventName, this.eventHandlers[eventName][i]);
	        }

	        delete this.eventHandlers[eventName];
	      }
	    }
	  }]);
	  return PageEventsManager;
	}();

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<label class=\"ui-ctl-block ui-entity-editor-popup-create-field-item ui-ctl-w100\">\n\t\t\t\t<div class=\"ui-ctl-w10\" style=\"text-align: center\">", "</div>\n\t\t\t\t<div class=\"ui-ctl-w75\">\n\t\t\t\t\t<span class=\"ui-entity-editor-popup-create-field-item-title\">", "</span>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</label>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-entity-editor-popup-create-field-item-desc\">", "</span>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"checkbox\">\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class='ui-entity-editor-popup-create-field-list'></div>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _target = new WeakMap();

	var _settings = new WeakMap();

	var _editor = new WeakMap();

	var _cache = new WeakMap();

	var _getSetting = new WeakSet();

	var _prepareSettingsContent = new WeakSet();

	var _getSettingItem = new WeakSet();

	var _setSetting = new WeakSet();

	var _requestGridSettings = new WeakSet();

	var _showNotification = new WeakSet();

	var SettingsPopup = /*#__PURE__*/function () {
	  function SettingsPopup(target) {
	    var settings = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
	    var editor = arguments.length > 2 ? arguments[2] : undefined;
	    babelHelpers.classCallCheck(this, SettingsPopup);

	    _showNotification.add(this);

	    _requestGridSettings.add(this);

	    _setSetting.add(this);

	    _getSettingItem.add(this);

	    _prepareSettingsContent.add(this);

	    _getSetting.add(this);

	    _target.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _settings.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _editor.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _cache.set(this, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });

	    babelHelpers.classPrivateFieldSet(this, _target, target);
	    babelHelpers.classPrivateFieldSet(this, _settings, settings);
	    babelHelpers.classPrivateFieldSet(this, _editor, editor);
	  }

	  babelHelpers.createClass(SettingsPopup, [{
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this = this;

	      return babelHelpers.classPrivateFieldGet(this, _cache).remember('settings-popup', function () {
	        return new main_popup.Popup(babelHelpers.classPrivateFieldGet(_this, _editor).getId() + '_' + Math.random() * 100, babelHelpers.classPrivateFieldGet(_this, _target), {
	          autoHide: true,
	          draggable: false,
	          offsetLeft: 0,
	          offsetTop: 0,
	          angle: {
	            position: 'top',
	            offset: 43
	          },
	          noAllPaddings: true,
	          bindOptions: {
	            forceBindPosition: true
	          },
	          closeByEsc: true,
	          content: _classPrivateMethodGet(_this, _prepareSettingsContent, _prepareSettingsContent2).call(_this)
	        });
	      });
	    }
	  }, {
	    key: "updateCheckboxState",
	    value: function updateCheckboxState() {
	      var _this2 = this;

	      var popupContainer = this.getPopup().getContentContainer();
	      babelHelpers.classPrivateFieldGet(this, _settings).filter(function (item) {
	        return item.action === 'grid' && main_core.Type.isArray(item.columns);
	      }).forEach(function (item) {
	        var allColumnsExist = true;
	        item.columns.forEach(function (columnName) {
	          if (!babelHelpers.classPrivateFieldGet(_this2, _editor).getGrid().getColumnHeaderCellByName(columnName)) {
	            allColumnsExist = false;
	          }
	        });
	        var checkbox = popupContainer.querySelector('input[data-setting-id="' + item.id + '"]');

	        if (main_core.Type.isDomNode(checkbox)) {
	          checkbox.checked = allColumnsExist;
	        }
	      });
	    }
	  }]);
	  return SettingsPopup;
	}();

	var _getSetting2 = function _getSetting2(id) {
	  return babelHelpers.classPrivateFieldGet(this, _settings).filter(function (item) {
	    return item.id === id;
	  })[0];
	};

	var _prepareSettingsContent2 = function _prepareSettingsContent2() {
	  var _this3 = this;

	  var content = main_core.Tag.render(_templateObject());
	  babelHelpers.classPrivateFieldGet(this, _settings).forEach(function (item) {
	    content.append(_classPrivateMethodGet(_this3, _getSettingItem, _getSettingItem2).call(_this3, item));
	  });
	  return content;
	};

	var _getSettingItem2 = function _getSettingItem2(item) {
	  var input = main_core.Tag.render(_templateObject2());
	  input.checked = item.checked;
	  input.dataset.settingId = item.id;
	  var descriptionNode = main_core.Type.isStringFilled(item.desc) ? main_core.Tag.render(_templateObject3(), item.desc) : '';
	  var setting = main_core.Tag.render(_templateObject4(), input, item.title, descriptionNode);
	  main_core.Event.bind(setting, 'change', _classPrivateMethodGet(this, _setSetting, _setSetting2).bind(this));
	  return setting;
	};

	var _setSetting2 = function _setSetting2(event) {
	  var settingItem = _classPrivateMethodGet(this, _getSetting, _getSetting2).call(this, event.target.dataset.settingId);

	  if (!settingItem) {
	    return;
	  }

	  var settingEnabled = event.target.checked;

	  _classPrivateMethodGet(this, _requestGridSettings, _requestGridSettings2).call(this, settingItem, settingEnabled);
	};

	var _requestGridSettings2 = function _requestGridSettings2(setting, enabled) {
	  var _this4 = this;

	  var headers = [];
	  var cells = babelHelpers.classPrivateFieldGet(this, _editor).getGrid().getRows().getHeadFirstChild().getCells();
	  Array.from(cells).forEach(function (header) {
	    if ('name' in header.dataset) {
	      headers.push(header.dataset.name);
	    }
	  });
	  main_core.ajax.runComponentAction(babelHelpers.classPrivateFieldGet(this, _editor).getComponentName(), 'setGridSetting', {
	    mode: 'class',
	    data: {
	      signedParameters: babelHelpers.classPrivateFieldGet(this, _editor).getSignedParameters(),
	      settingId: setting.id,
	      selected: enabled,
	      currentHeaders: headers
	    }
	  }).then(function () {
	    setting.checked = enabled;

	    if (setting.id === 'ADD_NEW_ROW_TOP') {
	      var panel = enabled ? 'top' : 'bottom';
	      babelHelpers.classPrivateFieldGet(_this4, _editor).setSettingValue('newRowPosition', panel);
	      var activePanel = babelHelpers.classPrivateFieldGet(_this4, _editor).changeActivePanelButtons(panel);
	      var settingButton = activePanel.querySelector('[data-role="product-list-settings-button"]');

	      _this4.getPopup().setBindElement(settingButton);
	    } else {
	      babelHelpers.classPrivateFieldGet(_this4, _editor).reloadGrid();
	    }

	    _this4.getPopup().close();

	    var message = enabled ? main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_ENABLED') : main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_DISABLED');

	    _classPrivateMethodGet(_this4, _showNotification, _showNotification2).call(_this4, message.replace('#NAME#', setting.title), {
	      category: 'popup-settings'
	    });
	  });
	};

	var _showNotification2 = function _showNotification2(content, options) {
	  options = options || {};
	  BX.UI.Notification.Center.notify({
	    content: content,
	    stack: options.stack || null,
	    position: 'top-right',
	    width: 'auto',
	    category: options.category || null,
	    autoHideDelay: options.autoHideDelay || 3000
	  });
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var GRID_TEMPLATE_ROW = 'template_0';
	var DEFAULT_PRECISION = 2;
	var Editor = /*#__PURE__*/function () {
	  function Editor(id) {
	    babelHelpers.classCallCheck(this, Editor);
	    babelHelpers.defineProperty(this, "products", []);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "actions", {
	      productChange: 'productChange',
	      productListChanged: 'productListChanged',
	      updateListField: 'listField',
	      stateChanged: 'stateChange',
	      updateTotal: 'total'
	    });
	    babelHelpers.defineProperty(this, "stateChange", {
	      changed: false,
	      sended: false
	    });
	    babelHelpers.defineProperty(this, "updateFieldForList", null);
	    babelHelpers.defineProperty(this, "totalData", {
	      inProgress: false
	    });
	    babelHelpers.defineProperty(this, "productSelectionPopupHandler", this.handleProductSelectionPopup.bind(this));
	    babelHelpers.defineProperty(this, "productRowAddHandler", this.handleProductRowAdd.bind(this));
	    babelHelpers.defineProperty(this, "showSettingsPopupHandler", this.handleShowSettingsPopup.bind(this));
	    babelHelpers.defineProperty(this, "onDialogSelectProductHandler", this.handleOnDialogSelectProduct.bind(this));
	    babelHelpers.defineProperty(this, "onSaveHandler", this.handleOnSave.bind(this));
	    babelHelpers.defineProperty(this, "onInnerCancelHandler", this.handleOnInnerCancel.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeGridRequestHandler", this.handleOnBeforeGridRequest.bind(this));
	    babelHelpers.defineProperty(this, "onGridUpdatedHandler", this.handleOnGridUpdated.bind(this));
	    babelHelpers.defineProperty(this, "onGridRowMovedHandler", this.handleOnGridRowMoved.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeProductChangeHandler", this.handleOnBeforeProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onProductChangeHandler", this.handleOnProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onProductClearHandler", this.handleOnProductClear.bind(this));
	    babelHelpers.defineProperty(this, "dropdownChangeHandler", this.handleDropdownChange.bind(this));
	    babelHelpers.defineProperty(this, "changeProductFieldHandler", this.handleFieldChange.bind(this));
	    babelHelpers.defineProperty(this, "updateTotalDataDelayedHandler", main_core.Runtime.debounce(this.updateTotalDataDelayed, 1000, this));
	    this.setId(id);
	  }

	  babelHelpers.createClass(Editor, [{
	    key: "init",
	    value: function init() {
	      var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      this.setSettings(config);

	      if (this.canEdit()) {
	        this.addFirstRowIfEmpty();
	        this.enableEdit();
	      }

	      this.initForm();
	      this.initProducts();
	      this.initGridData();
	      main_core_events.EventEmitter.emit(window, 'EntityProductListController', [this]);
	      this.subscribeDomEvents();
	      this.subscribeCustomEvents();
	    }
	  }, {
	    key: "subscribeDomEvents",
	    value: function subscribeDomEvents() {
	      var _this = this;

	      var container = this.getContainer();

	      if (main_core.Type.isElementNode(container)) {
	        container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	          main_core.Event.bind(selectButton, 'click', _this.productSelectionPopupHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	          main_core.Event.bind(addButton, 'click', _this.productRowAddHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.bind(configButton, 'click', _this.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "unsubscribeDomEvents",
	    value: function unsubscribeDomEvents() {
	      var _this2 = this;

	      var container = this.getContainer();

	      if (main_core.Type.isElementNode(container)) {
	        container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	          main_core.Event.unbind(selectButton, 'click', _this2.productSelectionPopupHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	          main_core.Event.unbind(addButton, 'click', _this2.productRowAddHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.unbind(configButton, 'click', _this2.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "subscribeCustomEvents",
	    value: function subscribeCustomEvents() {
	      main_core_events.EventEmitter.subscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.subscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.subscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.subscribe('Dropdown::change', this.dropdownChangeHandler);
	    }
	  }, {
	    key: "unsubscribeCustomEvents",
	    value: function unsubscribeCustomEvents() {
	      main_core_events.EventEmitter.unsubscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.unsubscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.unsubscribe('Dropdown::change', this.dropdownChangeHandler);
	    }
	  }, {
	    key: "handleOnDialogSelectProduct",
	    value: function handleOnDialogSelectProduct(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          productId = _event$getCompatData2[0];

	      var id = this.addProductRow();
	      this.selectProductInRow(id, productId);
	    }
	  }, {
	    key: "selectProductInRow",
	    value: function selectProductInRow(id, productId) {
	      var _this3 = this;

	      if (!main_core.Type.isStringFilled(id) || main_core.Text.toNumber(productId) <= 0) {
	        return;
	      }

	      requestAnimationFrame(function () {
	        var _this3$getProductSele;

	        (_this3$getProductSele = _this3.getProductSelector(id)) === null || _this3$getProductSele === void 0 ? void 0 : _this3$getProductSele.onProductSelect(productId);
	      });
	    }
	  }, {
	    key: "handleOnSave",
	    value: function handleOnSave(event) {
	      var items = [];
	      this.products.forEach(function (product) {
	        var item = {
	          fields: babelHelpers.objectSpread({}, product.fields),
	          rowId: product.fields.ROW_ID
	        };
	        items.push(item);
	      });
	      this.setSettingValue('items', items);
	    }
	  }, {
	    key: "handleOnInnerCancel",
	    value: function handleOnInnerCancel(event) {
	      if (this.controller) {
	        this.controller.rollback();
	      }

	      this.reloadGrid(false);
	    }
	  }, {
	    key: "changeActivePanelButtons",
	    value: function changeActivePanelButtons(panelCode) {
	      var container = this.getContainer();
	      var activePanel = container.querySelector('.crm-entity-product-list-add-block-' + panelCode);

	      if (main_core.Type.isDomNode(activePanel)) {
	        main_core.Dom.removeClass(activePanel, 'crm-entity-product-list-add-block-hidden');
	        main_core.Dom.addClass(activePanel, 'crm-entity-product-list-add-block-active');
	      }

	      var hiddenPanelCode = panelCode === 'top' ? 'bottom' : 'top';
	      var removePanel = container.querySelector('.crm-entity-product-list-add-block-' + hiddenPanelCode);

	      if (main_core.Type.isDomNode(removePanel)) {
	        main_core.Dom.addClass(removePanel, 'crm-entity-product-list-add-block-hidden');
	        main_core.Dom.removeClass(removePanel, 'crm-entity-product-list-add-block-active');
	      }

	      return activePanel;
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      var _this4 = this;

	      var useProductsFromRequest = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      var isInternalChanging = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (isInternalChanging === null) {
	        isInternalChanging = !useProductsFromRequest;
	      }

	      this.getGrid().reloadTable('POST', {
	        useProductsFromRequest: useProductsFromRequest
	      }, function () {
	        return _this4.actionUpdateTotalData({
	          isInternalChanging: isInternalChanging
	        });
	      });
	    }
	    /*
	    	keep in mind 4 actions for this handler:
	    	- native reload by grid actions (columns settings, etc)		- products from request
	    	- reload by tax/discount settings button					- products from request		this.reloadGrid(true)
	    	- rollback													- products from db			this.reloadGrid(false)
	    	- reload after SalesCenter order save						- products from db			this.reloadGrid(false)
	     */

	  }, {
	    key: "handleOnBeforeGridRequest",
	    value: function handleOnBeforeGridRequest(event) {
	      var _this5 = this;

	      var _event$getCompatData3 = event.getCompatData(),
	          _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 2),
	          grid = _event$getCompatData4[0],
	          eventArgs = _event$getCompatData4[1];

	      if (!grid || !grid.parent || grid.parent.getId() !== this.getGridId()) {
	        return;
	      } // reload by native grid actions (columns settings, etc), otherwise by this.reloadGrid()


	      var isNativeAction = !('useProductsFromRequest' in eventArgs.data);
	      var useProductsFromRequest = isNativeAction ? true : eventArgs.data.useProductsFromRequest;
	      eventArgs.url = this.getReloadUrl();
	      eventArgs.method = 'POST';
	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.data = babelHelpers.objectSpread({}, eventArgs.data, {
	        signedParameters: this.getSignedParameters(),
	        products: useProductsFromRequest ? this.getProductsFields() : null
	      });
	      this.clearEditor();

	      if (isNativeAction) {
	        main_core_events.EventEmitter.subscribeOnce('Grid::updated', function () {
	          return _this5.actionUpdateTotalData({
	            isInternalChanging: false
	          });
	        });
	      }
	    }
	  }, {
	    key: "handleOnGridUpdated",
	    value: function handleOnGridUpdated(event) {
	      var _event$getCompatData5 = event.getCompatData(),
	          _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 1),
	          grid = _event$getCompatData6[0];

	      if (!grid || grid.getId() !== this.getGridId()) {
	        return;
	      }

	      this.getSettingsPopup().updateCheckboxState();
	    }
	  }, {
	    key: "handleOnGridRowMoved",
	    value: function handleOnGridRowMoved(event) {
	      var _event$getCompatData7 = event.getCompatData(),
	          _event$getCompatData8 = babelHelpers.slicedToArray(_event$getCompatData7, 3),
	          ids = _event$getCompatData8[0],
	          grid = _event$getCompatData8[2];

	      if (!grid || grid.getId() !== this.getGridId()) {
	        return;
	      }

	      var changed = this.resortProductsByIds(ids);

	      if (changed) {
	        this.refreshSortFields();
	        this.executeActions([{
	          type: this.actions.productListChanged
	        }]);
	      }
	    }
	  }, {
	    key: "initPageEventsManager",
	    value: function initPageEventsManager() {
	      var componentId = this.getSettingValue('componentId');
	      this.pageEventsManager = new PageEventsManager({
	        id: componentId
	      });
	    }
	  }, {
	    key: "getPageEventsManager",
	    value: function getPageEventsManager() {
	      if (!this.pageEventsManager) {
	        this.initPageEventsManager();
	      }

	      return this.pageEventsManager;
	    }
	  }, {
	    key: "canEdit",
	    value: function canEdit() {
	      return this.getSettingValue('allowEdit', false) === true;
	    }
	  }, {
	    key: "enableEdit",
	    value: function enableEdit() {
	      this.getGrid().getRows().selectAll();
	      this.getGrid().editSelected();
	    }
	  }, {
	    key: "addFirstRowIfEmpty",
	    value: function addFirstRowIfEmpty() {
	      var _this6 = this;

	      if (this.getGrid().getRows().getCountDisplayed() === 0) {
	        requestAnimationFrame(function () {
	          return _this6.addProductRow();
	        });
	      }
	    }
	  }, {
	    key: "clearEditor",
	    value: function clearEditor() {
	      this.products = [];
	      this.destroySettingsPopup();
	      this.unsubscribeDomEvents();
	      this.unsubscribeCustomEvents();
	      main_core.Event.unbindAll(this.container);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      this.setForm(null);
	      this.clearController();
	      this.clearEditor();
	    }
	  }, {
	    key: "setController",
	    value: function setController(controller) {
	      if (this.controller === controller) {
	        return;
	      }

	      if (this.controller) {
	        this.controller.clearProductList();
	      }

	      this.controller = controller;
	    }
	  }, {
	    key: "clearController",
	    value: function clearController() {
	      this.controller = null;
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = id;
	    }
	    /* settings tools */

	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      return this.settings;
	    }
	  }, {
	    key: "setSettings",
	    value: function setSettings(settings) {
	      this.settings = settings ? settings : {};
	    }
	  }, {
	    key: "getSettingValue",
	    value: function getSettingValue(name, defaultValue) {
	      return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	    }
	  }, {
	    key: "setSettingValue",
	    value: function setSettingValue(name, value) {
	      this.settings[name] = value;
	    }
	  }, {
	    key: "getComponentName",
	    value: function getComponentName() {
	      return this.getSettingValue('componentName', '');
	    }
	  }, {
	    key: "getReloadUrl",
	    value: function getReloadUrl() {
	      return this.getSettingValue('reloadUrl', '');
	    }
	  }, {
	    key: "getSignedParameters",
	    value: function getSignedParameters() {
	      return this.getSettingValue('signedParameters', '');
	    }
	  }, {
	    key: "getContainerId",
	    value: function getContainerId() {
	      return this.getSettingValue('containerId', '');
	    }
	  }, {
	    key: "getGridId",
	    value: function getGridId() {
	      return this.getSettingValue('gridId', '');
	    }
	  }, {
	    key: "getLanguageId",
	    value: function getLanguageId() {
	      return this.getSettingValue('languageId', '');
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return this.getSettingValue('siteId', '');
	    }
	  }, {
	    key: "getCatalogId",
	    value: function getCatalogId() {
	      return this.getSettingValue('catalogId', 0);
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return this.getSettingValue('readOnly', true);
	    }
	  }, {
	    key: "setReadOnly",
	    value: function setReadOnly(readOnly) {
	      this.setSettingValue('readOnly', readOnly);
	    }
	  }, {
	    key: "getCurrencyId",
	    value: function getCurrencyId() {
	      return this.getSettingValue('currencyId', '');
	    }
	  }, {
	    key: "setCurrencyId",
	    value: function setCurrencyId(currencyId) {
	      this.setSettingValue('currencyId', currencyId);
	    }
	  }, {
	    key: "changeCurrencyId",
	    value: function changeCurrencyId(currencyId) {
	      var _this7 = this;

	      this.setCurrencyId(currencyId);
	      var products = [];
	      this.products.forEach(function (product) {
	        products.push({
	          fields: product.getFields(),
	          id: product.getId()
	        });
	      });

	      if (products.length > 0) {
	        this.ajaxRequest('calculateProductPrices', {
	          products: products,
	          currencyId: currencyId
	        });
	      }

	      var editData = this.getGridEditData();
	      var templateRow = editData[GRID_TEMPLATE_ROW];
	      templateRow['CURRENCY'] = this.getCurrencyId();
	      var templateFieldNames = ['DISCOUNT_ROW', 'SUM', 'PRICE'];
	      templateFieldNames.forEach(function (field) {
	        templateRow[field]['CURRENCY']['VALUE'] = _this7.getCurrencyId();
	      });
	      this.setGridEditData(editData);
	    }
	  }, {
	    key: "onCalculatePricesResponse",
	    value: function onCalculatePricesResponse(products) {
	      this.products.forEach(function (product) {
	        if (main_core.Type.isObject(products[product.getId()])) {
	          var newPrice = main_core.Text.toNumber(products[product.getId()]['PRICE']);
	          product.updateUiCurrencyFields();
	          product.updateField('PRICE', newPrice);
	          product.setField('CURRENCY', products[product.getId()]['CURRENCY_ID']);
	        }
	      });
	      this.updateTotalUiCurrency();
	    }
	  }, {
	    key: "updateTotalUiCurrency",
	    value: function updateTotalUiCurrency() {
	      var _this8 = this;

	      var totalBlock = BX(this.getSettingValue('totalBlockContainerId', null));

	      if (main_core.Type.isElementNode(totalBlock)) {
	        totalBlock.querySelectorAll('[data-role="currency-wrapper"]').forEach(function (row) {
	          row.innerHTML = _this8.getCurrencyText();
	        });
	      }
	    }
	  }, {
	    key: "getCurrencyText",
	    value: function getCurrencyText() {
	      var currencyId = this.getCurrencyId();

	      if (!main_core.Type.isStringFilled(currencyId)) {
	        return '';
	      }

	      var format = currency_currencyCore.CurrencyCore.getCurrencyFormat(currencyId);
	      return format && format.FORMAT_STRING.replace(/(^|[^&])#/, '$1').trim() || '';
	    }
	  }, {
	    key: "getDataFieldName",
	    value: function getDataFieldName() {
	      return this.getSettingValue('dataFieldName', '');
	    }
	  }, {
	    key: "getDataSettingsFieldName",
	    value: function getDataSettingsFieldName() {
	      var field = this.getDataFieldName();
	      return main_core.Type.isStringFilled(field) ? field + '_SETTINGS' : '';
	    }
	  }, {
	    key: "getDiscountEnabled",
	    value: function getDiscountEnabled() {
	      return this.getSettingValue('enableDiscount', 'N');
	    }
	  }, {
	    key: "getPricePrecision",
	    value: function getPricePrecision() {
	      return this.getSettingValue('pricePrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getQuantityPrecision",
	    value: function getQuantityPrecision() {
	      return this.getSettingValue('quantityPrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getCommonPrecision",
	    value: function getCommonPrecision() {
	      return this.getSettingValue('commonPrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getTaxAllowed",
	    value: function getTaxAllowed() {
	      return this.getSettingValue('allowTax', 'N');
	    }
	  }, {
	    key: "isTaxAllowed",
	    value: function isTaxAllowed() {
	      return this.getTaxAllowed() === 'Y';
	    }
	  }, {
	    key: "getTaxEnabled",
	    value: function getTaxEnabled() {
	      return this.getSettingValue('enableTax', 'N');
	    }
	  }, {
	    key: "isTaxEnabled",
	    value: function isTaxEnabled() {
	      return this.getTaxEnabled() === 'Y';
	    }
	  }, {
	    key: "isTaxUniform",
	    value: function isTaxUniform() {
	      return this.getSettingValue('taxUniform', true);
	    }
	  }, {
	    key: "getMeasures",
	    value: function getMeasures() {
	      return this.getSettingValue('measures', []);
	    }
	  }, {
	    key: "getDefaultMeasure",
	    value: function getDefaultMeasure() {
	      return this.getSettingValue('defaultMeasure', {});
	    }
	  }, {
	    key: "getRowIdPrefix",
	    value: function getRowIdPrefix() {
	      return this.getSettingValue('rowIdPrefix', 'crm_entity_product_list_');
	    }
	    /* settings tools finish */

	    /* calculate tools */

	  }, {
	    key: "parseInt",
	    value: function (_parseInt) {
	      function parseInt(_x) {
	        return _parseInt.apply(this, arguments);
	      }

	      parseInt.toString = function () {
	        return _parseInt.toString();
	      };

	      return parseInt;
	    }(function (value) {
	      var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var result;
	      var isNumberValue = main_core.Type.isNumber(value);
	      var isStringValue = main_core.Type.isStringFilled(value);

	      if (!isNumberValue && !isStringValue) {
	        return defaultValue;
	      }

	      if (isStringValue) {
	        value = value.replace(/^\s+|\s+$/g, '');
	        var isNegative = value.indexOf('-') === 0;
	        result = parseInt(value.replace(/[^\d]/g, ''), 10);

	        if (isNaN(result)) {
	          result = defaultValue;
	        } else {
	          if (isNegative) {
	            result = -result;
	          }
	        }
	      } else {
	        result = parseInt(value, 10);

	        if (isNaN(result)) {
	          result = defaultValue;
	        }
	      }

	      return result;
	    })
	  }, {
	    key: "parseFloat",
	    value: function (_parseFloat) {
	      function parseFloat(_x2) {
	        return _parseFloat.apply(this, arguments);
	      }

	      parseFloat.toString = function () {
	        return _parseFloat.toString();
	      };

	      return parseFloat;
	    }(function (value) {
	      var precision = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_PRECISION;
	      var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0.0;
	      var result;
	      var isNumberValue = main_core.Type.isNumber(value);
	      var isStringValue = main_core.Type.isStringFilled(value);

	      if (!isNumberValue && !isStringValue) {
	        return defaultValue;
	      }

	      if (isStringValue) {
	        value = value.replace(/^\s+|\s+$/g, '');
	        var dot = value.indexOf('.');
	        var comma = value.indexOf(',');
	        var isNegative = value.indexOf('-') === 0;

	        if (dot < 0 && comma >= 0) {
	          var s1 = value.substr(0, comma);
	          var decimalLength = value.length - comma - 1;

	          if (decimalLength > 0) {
	            s1 += '.' + value.substr(comma + 1, decimalLength);
	          }

	          value = s1;
	        }

	        value = value.replace(/[^\d.]+/g, '');
	        result = parseFloat(value);

	        if (isNaN(result)) {
	          result = defaultValue;
	        }

	        if (isNegative) {
	          result = -result;
	        }
	      } else {
	        result = parseFloat(value);
	      }

	      if (precision >= 0) {
	        result = this.round(result, precision);
	      }

	      return result;
	    })
	  }, {
	    key: "round",
	    value: function round(value) {
	      var precision = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_PRECISION;
	      var factor = Math.pow(10, precision);
	      return Math.round(value * factor) / factor;
	    }
	  }, {
	    key: "calculatePriceWithoutDiscount",
	    value: function calculatePriceWithoutDiscount(price, discount, discountType) {
	      var result = 0.0;

	      switch (discountType) {
	        case catalog_productCalculator.DiscountType.PERCENTAGE:
	          result = price - price * discount / 100;
	          break;

	        case catalog_productCalculator.DiscountType.MONETARY:
	          result = price - discount;
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "calculateDiscountRate",
	    value: function calculateDiscountRate(originalPrice, price) {
	      if (originalPrice === 0.0) {
	        return 0.0;
	      }

	      if (price === 0.0) {
	        return originalPrice > 0 ? 100.0 : -100.0;
	      }

	      return 100 * (originalPrice - price) / originalPrice;
	    }
	  }, {
	    key: "calculateDiscount",
	    value: function calculateDiscount(originalPrice, discountRate) {
	      return originalPrice * discountRate / 100;
	    }
	  }, {
	    key: "calculatePriceWithoutTax",
	    value: function calculatePriceWithoutTax(price, taxRate) {
	      // Tax is not included in price
	      return price / (1 + taxRate / 100);
	    }
	  }, {
	    key: "calculatePriceWithTax",
	    value: function calculatePriceWithTax(price, taxRate) {
	      // Tax is included in price
	      return price * (1 + taxRate / 100);
	    }
	    /* calculate tools finish */

	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this9 = this;

	      return this.cache.remember('container', function () {
	        return document.getElementById(_this9.getContainerId());
	      });
	    }
	  }, {
	    key: "initForm",
	    value: function initForm() {
	      var formId = this.getSettingValue('formId', '');
	      var form = main_core.Type.isStringFilled(formId) ? BX('form_' + formId) : null;

	      if (main_core.Type.isElementNode(form)) {
	        this.setForm(form);
	      }
	    }
	  }, {
	    key: "isExistForm",
	    value: function isExistForm() {
	      return main_core.Type.isElementNode(this.getForm());
	    }
	  }, {
	    key: "getForm",
	    value: function getForm() {
	      return this.form;
	    }
	  }, {
	    key: "setForm",
	    value: function setForm(form) {
	      this.form = form;
	    }
	  }, {
	    key: "initFormFields",
	    value: function initFormFields() {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container)) {
	        var field = this.getDataField();

	        if (!main_core.Type.isElementNode(field)) {
	          this.initDataField();
	        }

	        var settingsField = this.getDataSettingsField();

	        if (!main_core.Type.isElementNode(settingsField)) {
	          this.initDataSettingsField();
	        }
	      }
	    }
	  }, {
	    key: "initFormField",
	    value: function initFormField(fieldName) {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container) && main_core.Type.isStringFilled(fieldName)) {
	        container.appendChild(main_core.Dom.create('input', {
	          attrs: {
	            type: "hidden",
	            name: fieldName
	          }
	        }));
	      }
	    }
	  }, {
	    key: "removeFormFields",
	    value: function removeFormFields() {
	      var field = this.getDataField();

	      if (main_core.Type.isElementNode(field)) {
	        main_core.Dom.remove(field);
	      }

	      var settingsField = this.getDataSettingsField();

	      if (main_core.Type.isElementNode(settingsField)) {
	        main_core.Dom.remove(settingsField);
	      }
	    }
	  }, {
	    key: "initDataField",
	    value: function initDataField() {
	      this.initFormField(this.getDataFieldName());
	    }
	  }, {
	    key: "initDataSettingsField",
	    value: function initDataSettingsField() {
	      this.initFormField(this.getDataSettingsFieldName());
	    }
	  }, {
	    key: "getFormField",
	    value: function getFormField(fieldName) {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container) && main_core.Type.isStringFilled(fieldName)) {
	        return container.querySelector('input[name="' + fieldName + '"]');
	      }

	      return null;
	    }
	  }, {
	    key: "getDataField",
	    value: function getDataField() {
	      return this.getFormField(this.getDataFieldName());
	    }
	  }, {
	    key: "getDataSettingsField",
	    value: function getDataSettingsField() {
	      return this.getFormField(this.getDataSettingsFieldName());
	    }
	  }, {
	    key: "getProductCount",
	    value: function getProductCount() {
	      return this.products.length;
	    }
	  }, {
	    key: "initProducts",
	    value: function initProducts() {
	      var list = this.getSettingValue('items', []);

	      var _iterator = _createForOfIteratorHelper(list),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;
	          var fields = babelHelpers.objectSpread({}, item.fields);
	          this.products.push(new Row(item.rowId, fields, {}, this));
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      var _this10 = this;

	      return this.cache.remember('grid', function () {
	        var gridId = _this10.getGridId();

	        if (!main_core.Reflection.getClass('BX.Main.gridManager.getInstanceById')) {
	          throw Error("Cannot find grid with '".concat(gridId, "' id."));
	        }

	        return BX.Main.gridManager.getInstanceById(gridId);
	      });
	    }
	  }, {
	    key: "initGridData",
	    value: function initGridData() {
	      var gridEditData = this.getSettingValue('templateGridEditData', null);

	      if (gridEditData) {
	        this.setGridEditData(gridEditData);
	      }
	    }
	  }, {
	    key: "getGridEditData",
	    value: function getGridEditData() {
	      return this.getGrid().arParams.EDITABLE_DATA;
	    }
	  }, {
	    key: "setGridEditData",
	    value: function setGridEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA = data;
	    }
	  }, {
	    key: "setOriginalTemplateEditData",
	    value: function setOriginalTemplateEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA[GRID_TEMPLATE_ROW] = data;
	    }
	  }, {
	    key: "handleFieldChange",
	    value: function handleFieldChange(event) {
	      var row = event.target.closest('tr');

	      if (row && row.hasAttribute('data-id')) {
	        var product = this.getProductById(row.getAttribute('data-id'));

	        if (product) {
	          var cell = event.target.closest('td');
	          var fieldCode = this.getFieldCodeByGridCell(row, cell);

	          if (fieldCode) {
	            product.updateFieldByEvent(fieldCode, event);
	          }
	        }
	      }
	    }
	  }, {
	    key: "handleDropdownChange",
	    value: function handleDropdownChange(event) {
	      var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 5),
	          dropdownId = _event$getData2[0],
	          value = _event$getData2[4];

	      var regExp = new RegExp(this.getRowIdPrefix() + '([A-Za-z0-9]+)_(\\w+)_control', 'i');
	      var matches = dropdownId.match(regExp);

	      if (matches) {
	        var _matches = babelHelpers.slicedToArray(matches, 3),
	            rowId = _matches[1],
	            fieldCode = _matches[2];

	        var product = this.getProductById(rowId);

	        if (product) {
	          product.updateField(fieldCode, value);
	        }
	      }
	    }
	  }, {
	    key: "getProductById",
	    value: function getProductById(id) {
	      var rowId = this.getRowIdPrefix() + id;
	      return this.getProductByRowId(rowId);
	    }
	  }, {
	    key: "getProductByRowId",
	    value: function getProductByRowId(rowId) {
	      return this.products.find(function (row) {
	        return row.getId() === rowId;
	      });
	    }
	  }, {
	    key: "getFieldCodeByGridCell",
	    value: function getFieldCodeByGridCell(row, cell) {
	      if (!main_core.Type.isDomNode(row) || !main_core.Type.isDomNode(cell)) {
	        return null;
	      }

	      var grid = this.getGrid();

	      if (grid) {
	        var headRow = grid.getRows().getHeadFirstChild();
	        var index = babelHelpers.toConsumableArray(row.cells).indexOf(cell);
	        return headRow.getCellNameByCellIndex(index);
	      }

	      return null;
	    }
	  }, {
	    key: "handleProductSelectionPopup",
	    value: function handleProductSelectionPopup(event) {
	      var caller = 'crm_entity_product_list';
	      var jsEventsManagerId = this.getSettingValue('jsEventsManagerId', '');
	      var popup = new BX.CDialog({
	        content_url: '/bitrix/components/bitrix/crm.product_row.list/product_choice_dialog.php?' + 'caller=' + caller + '&JS_EVENTS_MANAGER_ID=' + BX.util.urlencode(jsEventsManagerId) + '&sessid=' + BX.bitrix_sessid(),
	        height: Math.max(500, window.innerHeight - 400),
	        width: Math.max(800, window.innerWidth - 400),
	        draggable: true,
	        resizable: true,
	        min_height: 500,
	        min_width: 800,
	        zIndex: 800
	      });
	      main_core_events.EventEmitter.subscribe(popup, 'onWindowRegister', BX.defer(function () {
	        popup.Get().style.position = 'fixed';
	        popup.Get().style.top = parseInt(popup.Get().style.top) - BX.GetWindowScrollPos().scrollTop + 'px';
	        popup.OVERLAY.style.zIndex = 798;
	      }));
	      main_core_events.EventEmitter.subscribe(window, 'EntityProductListController:onInnerCancel', BX.defer(function () {
	        popup.Close();
	      }));

	      if (typeof BX.Crm.EntityEvent !== "undefined") {
	        main_core_events.EventEmitter.subscribe(window, BX.Crm.EntityEvent.names.update, BX.defer(function () {
	          requestAnimationFrame(function () {
	            popup.Close();
	          }, 0);
	        }));
	      }

	      popup.Show();
	    }
	  }, {
	    key: "addProductRow",
	    value: function addProductRow() {
	      var row = this.createGridProductRow();
	      var newId = row.getId();
	      this.initializeNewProductRow(newId);
	      return newId;
	    }
	  }, {
	    key: "handleProductRowAdd",
	    value: function handleProductRowAdd() {
	      var id = this.addProductRow();
	      this.focusProductSelector(id);
	    }
	  }, {
	    key: "handleShowSettingsPopup",
	    value: function handleShowSettingsPopup() {
	      this.getSettingsPopup().show();
	    }
	  }, {
	    key: "destroySettingsPopup",
	    value: function destroySettingsPopup() {
	      if (this.cache.has('settings-popup')) {
	        this.cache.get('settings-popup').getPopup().destroy();
	        this.cache.delete('settings-popup');
	      }
	    }
	  }, {
	    key: "getSettingsPopup",
	    value: function getSettingsPopup() {
	      var _this11 = this;

	      return this.cache.remember('settings-popup', function () {
	        return new SettingsPopup(_this11.getContainer().querySelector('.crm-entity-product-list-add-block-active [data-role="product-list-settings-button"]'), _this11.getSettingValue('popupSettings', []), _this11);
	      });
	    }
	  }, {
	    key: "createGridProductRow",
	    value: function createGridProductRow() {
	      var newId = main_core.Text.getRandom();
	      var originalTemplate = this.redefineTemplateEditData(newId);
	      var grid = this.getGrid();
	      var newRow;

	      if (this.getSettingValue('newRowPosition') === 'bottom') {
	        newRow = grid.appendRowEditor();
	      } else {
	        newRow = grid.prependRowEditor();
	      }

	      var newNode = newRow.getNode();

	      if (main_core.Type.isDomNode(newNode)) {
	        newNode.setAttribute('data-id', newId);
	        newRow.makeCountable();
	      }

	      if (originalTemplate) {
	        this.setOriginalTemplateEditData(originalTemplate);
	      }

	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);
	      grid.adjustRows();
	      grid.updateCounterDisplayed();
	      grid.updateCounterSelected();
	      return newRow;
	    }
	  }, {
	    key: "redefineTemplateEditData",
	    value: function redefineTemplateEditData(newId) {
	      var data = this.getGridEditData();
	      var originalTemplateData = data[GRID_TEMPLATE_ROW];
	      var customEditData = this.prepareCustomEditData(originalTemplateData, newId);
	      this.setOriginalTemplateEditData(babelHelpers.objectSpread({}, originalTemplateData, customEditData));
	      return originalTemplateData;
	    }
	  }, {
	    key: "prepareCustomEditData",
	    value: function prepareCustomEditData(originalEditData, newId) {
	      var customEditData = {};
	      var templateIdMask = this.getSettingValue('templateIdMask', '');

	      for (var i in originalEditData) {
	        if (originalEditData.hasOwnProperty(i)) {
	          if (main_core.Type.isStringFilled(originalEditData[i]) && originalEditData[i].indexOf(templateIdMask) >= 0) {
	            customEditData[i] = originalEditData[i].replace(new RegExp(templateIdMask, 'g'), newId);
	          } else if (main_core.Type.isPlainObject(originalEditData[i])) {
	            customEditData[i] = this.prepareCustomEditData(originalEditData[i], newId);
	          } else {
	            customEditData[i] = originalEditData[i];
	          }
	        }
	      }

	      return customEditData;
	    }
	  }, {
	    key: "initializeNewProductRow",
	    value: function initializeNewProductRow(newId) {
	      var rowId = this.getRowIdPrefix() + newId;
	      var fields = babelHelpers.objectSpread({}, this.getSettingValue('templateItemFields', {}), {
	        ID: newId,
	        // hack: specially reversed field to change it after (isChangedValue need to be true)
	        TAX_INCLUDED: this.isTaxIncludedActive() ? 'N' : 'Y',
	        CURRENCY: this.getCurrencyId()
	      });
	      var product = new Row(rowId, fields, {}, this);
	      product.updateFieldValue('TAX_INCLUDED', this.isTaxIncludedActive());

	      if (this.getSettingValue('newRowPosition') === 'bottom') {
	        this.products.push(product);
	      } else {
	        this.products.unshift(product);
	      }

	      this.refreshSortFields();
	      product.updateUiCurrencyFields();
	      this.updateTotalUiCurrency();
	      return product;
	    }
	  }, {
	    key: "isTaxIncludedActive",
	    value: function isTaxIncludedActive() {
	      return this.products.filter(function (product) {
	        return product.isTaxIncluded();
	      }).length > 0;
	    }
	  }, {
	    key: "getProductSelector",
	    value: function getProductSelector(newId) {
	      return catalog_productSelector.ProductSelector.getById('crm_grid_' + this.getRowIdPrefix() + newId);
	    }
	  }, {
	    key: "focusProductSelector",
	    value: function focusProductSelector(newId) {
	      var _this12 = this;

	      requestAnimationFrame(function () {
	        var _this12$getProductSel;

	        (_this12$getProductSel = _this12.getProductSelector(newId)) === null || _this12$getProductSel === void 0 ? void 0 : _this12$getProductSel.searchInDialog().focusName();
	      });
	    }
	  }, {
	    key: "handleOnBeforeProductChange",
	    value: function handleOnBeforeProductChange(event) {
	      var data = event.getData();
	      var product = this.getProductByRowId(data.rowId);

	      if (product) {
	        this.getGrid().tableFade();
	        product.resetExternalActions();
	      }
	    }
	  }, {
	    key: "handleOnProductChange",
	    value: function handleOnProductChange(event) {
	      var _this13 = this;

	      var data = event.getData();
	      var productRow = this.getProductByRowId(data.rowId);

	      if (productRow && data.fields) {
	        var promise = new Promise(function (resolve, reject) {
	          var fields = data.fields;

	          if (_this13.getCurrencyId() !== fields['CURRENCY_ID']) {
	            fields['CURRENCY'] = fields['CURRENCY_ID'];
	            var products = [{
	              fields: data.fields,
	              id: productRow.getId()
	            }];
	            main_core.ajax.runComponentAction(_this13.getComponentName(), 'calculateProductPrices', {
	              mode: 'class',
	              signedParameters: _this13.getSignedParameters(),
	              data: {
	                products: products,
	                currencyId: _this13.getCurrencyId(),
	                options: {
	                  ACTION: 'calculateProductPrices'
	                }
	              }
	            }).then(function (response) {
	              var changedFields = response.data.result[productRow.getId()];

	              if (changedFields) {
	                changedFields['CUSTOMIZED'] = 'Y';
	                resolve(Object.assign(fields, changedFields));
	              } else {
	                resolve(fields);
	              }
	            });
	          } else {
	            resolve(fields);
	          }
	        });
	        promise.then(function (fields) {
	          Object.keys(fields).forEach(function (key) {
	            productRow.updateFieldValue(key, fields[key]);
	          });

	          if (!main_core.Type.isStringFilled(fields['CUSTOMIZED'])) {
	            productRow.setField('CUSTOMIZED', 'N');
	          }

	          productRow.setField('IS_NEW', data.isNew ? 'Y' : 'N');
	          productRow.initHandlersForProductSelector();
	          productRow.executeExternalActions();

	          _this13.getGrid().tableUnfade();
	        });
	      } else {
	        this.getGrid().tableUnfade();
	      }
	    }
	  }, {
	    key: "handleOnProductClear",
	    value: function handleOnProductClear(event) {
	      var _event$getData3 = event.getData(),
	          rowId = _event$getData3.rowId;

	      var product = this.getProductByRowId(rowId);

	      if (product) {
	        product.initHandlersForProductSelector();
	        product.changePrice(0);
	        product.executeExternalActions();
	      }
	    }
	  }, {
	    key: "compileProductData",
	    value: function compileProductData() {
	      if (!this.isExistForm()) {
	        return;
	      }

	      this.initFormFields();
	      var field = this.getDataField();
	      var settingsField = this.getDataSettingsField();
	      this.cleanProductRows();

	      if (main_core.Type.isElementNode(field) && main_core.Type.isElementNode(settingsField)) {
	        field.value = this.prepareProductDataValue();
	        settingsField.value = JSON.stringify({
	          ENABLE_DISCOUNT: this.getDiscountEnabled(),
	          ENABLE_TAX: this.getTaxEnabled()
	        });
	      }

	      this.addFirstRowIfEmpty();
	    }
	  }, {
	    key: "prepareProductDataValue",
	    value: function prepareProductDataValue() {
	      var productDataValue = '';

	      if (this.getProductCount()) {
	        var productData = [];
	        this.products.forEach(function (item) {
	          var itemFields = item.getFields();

	          if (!/^[0-9]+$/.test(itemFields['ID'])) {
	            itemFields['ID'] = 0;
	          }

	          itemFields['CUSTOMIZED'] = 'Y';
	          productData.push(itemFields);
	        });
	        productDataValue = JSON.stringify(productData);
	      }

	      return productDataValue;
	    }
	    /* actions */

	  }, {
	    key: "executeActions",
	    value: function executeActions(actions) {
	      var _this14 = this;

	      if (!main_core.Type.isArrayFilled(actions)) {
	        return;
	      }

	      var disableSaveButton = actions.filter(function (action) {
	        return action.type === _this14.actions.updateTotal;
	      }).length > 0;

	      var _iterator2 = _createForOfIteratorHelper(actions),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var item = _step2.value;

	          if (!main_core.Type.isPlainObject(item) || !main_core.Type.isStringFilled(item.type)) {
	            continue;
	          }

	          switch (item.type) {
	            case this.actions.productChange:
	              this.actionSendProductChange(item, disableSaveButton);
	              break;

	            case this.actions.productListChanged:
	              this.actionSendProductListChanged(disableSaveButton);
	              break;

	            case this.actions.updateListField:
	              this.actionUpdateListField(item);
	              break;

	            case this.actions.updateTotal:
	              this.actionUpdateTotalData();
	              break;

	            case this.actions.stateChanged:
	              this.actionSendStatusChange(item);
	              break;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	    }
	  }, {
	    key: "actionSendProductChange",
	    value: function actionSendProductChange(item, disableSaveButton) {
	      if (!main_core.Type.isStringFilled(item.id)) {
	        return;
	      }

	      var product = this.getProductByRowId(item.id);

	      if (!product) {
	        return;
	      }

	      main_core_events.EventEmitter.emit(this, 'ProductList::onChangeFields', {
	        rowId: item.id,
	        productId: product.getField('PRODUCT_ID'),
	        fields: this.getProductByRowId(item.id).getCatalogFields()
	      });

	      if (this.controller) {
	        this.controller.productChange(disableSaveButton);
	      }
	    }
	  }, {
	    key: "actionSendProductListChanged",
	    value: function actionSendProductListChanged() {
	      var disableSaveButton = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (this.controller) {
	        this.controller.productChange(disableSaveButton);
	      }
	    }
	  }, {
	    key: "actionUpdateListField",
	    value: function actionUpdateListField(item) {
	      if (!main_core.Type.isStringFilled(item.field) || !('value' in item)) {
	        return;
	      }

	      if (!this.allowUpdateListField(item.field)) {
	        return;
	      }

	      this.updateFieldForList = item.field;

	      var _iterator3 = _createForOfIteratorHelper(this.products),
	          _step3;

	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var row = _step3.value;
	          row.updateFieldByName(item.field, item.value);
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }

	      this.updateFieldForList = null;
	    }
	  }, {
	    key: "actionUpdateTotalData",
	    value: function actionUpdateTotalData() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (this.totalData.inProgress) {
	        return;
	      }

	      this.updateTotalDataDelayedHandler(options);
	    }
	  }, {
	    key: "actionSendStatusChange",
	    value: function actionSendStatusChange(item) {
	      if (!('value' in item)) {
	        return;
	      }

	      if (this.stateChange.changed === item.value) {
	        return;
	      }

	      this.stateChange.changed = item.value;

	      if (this.stateChange.sended) {
	        return;
	      }

	      this.stateChange.sended = true;
	    }
	    /* actions finish */

	    /* action tools */

	  }, {
	    key: "allowUpdateListField",
	    value: function allowUpdateListField(field) {
	      if (this.updateFieldForList !== null) {
	        return false;
	      }

	      var result = true;

	      switch (field) {
	        case 'TAX_INCLUDED':
	          result = this.isTaxUniform() && this.isTaxAllowed();
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "updateTotalDataDelayed",
	    value: function updateTotalDataDelayed() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (this.totalData.inProgress) {
	        return;
	      }

	      this.totalData.inProgress = true;
	      var products = this.getProductsFields(this.getProductFieldListForTotalData());
	      products.forEach(function (item) {
	        return item['CUSTOMIZED'] = 'Y';
	      });
	      this.ajaxRequest('calculateTotalData', {
	        options: options,
	        products: products,
	        currencyId: this.getCurrencyId()
	      });
	    }
	  }, {
	    key: "getProductsFields",
	    value: function getProductsFields() {
	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	      var productFields = [];

	      var _iterator4 = _createForOfIteratorHelper(this.products),
	          _step4;

	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var item = _step4.value;
	          productFields.push(item.getFields(fields));
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }

	      return productFields;
	    }
	  }, {
	    key: "getProductFieldListForTotalData",
	    value: function getProductFieldListForTotalData() {
	      return ['PRODUCT_ID', 'PRODUCT_NAME', 'QUANTITY', 'DISCOUNT_TYPE_ID', 'DISCOUNT_RATE', 'DISCOUNT_SUM', 'TAX_RATE', 'TAX_INCLUDED', 'PRICE_EXCLUSIVE', 'PRICE', 'CUSTOMIZED'];
	    }
	  }, {
	    key: "setTotalData",
	    value: function setTotalData(data) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var item = BX(this.getSettingValue('totalBlockContainerId', null));

	      if (main_core.Type.isElementNode(item)) {
	        var currencyId = this.getCurrencyId();
	        var list = ['totalCost', 'totalTax', 'totalWithoutTax', 'totalDiscount', 'totalWithoutDiscount'];

	        for (var _i = 0, _list = list; _i < _list.length; _i++) {
	          var id = _list[_i];
	          var row = item.querySelector('[data-total="' + id + '"]');

	          if (main_core.Type.isElementNode(row) && id in data) {
	            row.innerHTML = currency_currencyCore.CurrencyCore.currencyFormat(data[id], currencyId, false);
	          }
	        }
	      }

	      this.sendTotalData(data, options);
	      this.totalData.inProgress = false;
	    }
	  }, {
	    key: "sendTotalData",
	    value: function sendTotalData(data, options) {
	      if (this.controller) {
	        var needMarkAsChanged = true;

	        if (main_core.Type.isObject(options) && (options.isInternalChanging === true || options.isInternalChanging === 'true')) {
	          needMarkAsChanged = false;
	        }

	        this.controller.changeSumTotal(data, needMarkAsChanged);
	      }
	    }
	    /* action tools finish */

	    /* ajax tools */

	  }, {
	    key: "ajaxRequest",
	    value: function ajaxRequest(action, data) {
	      var _this15 = this;

	      if (!main_core.Type.isPlainObject(data.options)) {
	        data.options = {};
	      }

	      data.options.ACTION = action;
	      main_core.ajax.runComponentAction(this.getComponentName(), action, {
	        mode: 'class',
	        signedParameters: this.getSignedParameters(),
	        data: data
	      }).then(function (response) {
	        return _this15.ajaxResultSuccess(response, data.options);
	      }, function (response) {
	        return _this15.ajaxResultFailure(response);
	      });
	    }
	  }, {
	    key: "ajaxResultSuccess",
	    value: function ajaxResultSuccess(response, requestOptions) {
	      if (!this.ajaxResultCommonCheck(response)) {
	        return;
	      }

	      switch (response.data.action) {
	        case 'calculateTotalData':
	          if (main_core.Type.isPlainObject(response.data.result)) {
	            this.setTotalData(response.data.result, requestOptions);
	          }

	          break;

	        case 'calculateProductPrices':
	          if (main_core.Type.isPlainObject(response.data.result)) {
	            this.onCalculatePricesResponse(response.data.result);
	          }

	          break;
	      }
	    }
	  }, {
	    key: "ajaxResultFailure",
	    value: function ajaxResultFailure(response) {}
	  }, {
	    key: "ajaxResultCommonCheck",
	    value: function ajaxResultCommonCheck(responce) {
	      if (!main_core.Type.isPlainObject(responce)) {
	        return false;
	      }

	      if (!main_core.Type.isStringFilled(responce.status)) {
	        return false;
	      }

	      if (responce.status !== 'success') {
	        return false;
	      }

	      if (!main_core.Type.isPlainObject(responce.data)) {
	        return false;
	      }

	      if (!main_core.Type.isStringFilled(responce.data.action)) {
	        return false;
	      } // noinspection RedundantIfStatementJS


	      if (!('result' in responce.data)) {
	        return false;
	      }

	      return true;
	    }
	  }, {
	    key: "deleteRow",
	    value: function deleteRow(rowId) {
	      var skipActions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

	      if (!main_core.Type.isStringFilled(rowId)) {
	        return;
	      }

	      var gridRow = this.getGrid().getRows().getById(rowId);

	      if (gridRow) {
	        main_core.Dom.remove(gridRow.getNode());
	        this.getGrid().getRows().reset();
	      }

	      var productRow = this.getProductById(rowId);

	      if (productRow) {
	        var index = this.products.indexOf(productRow);

	        if (index > -1) {
	          this.products.splice(index, 1);
	          this.refreshSortFields();
	        }
	      }

	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);

	      if (!skipActions) {
	        this.addFirstRowIfEmpty();
	        this.executeActions([{
	          type: this.actions.productListChanged
	        }, {
	          type: this.actions.updateTotal
	        }]);
	      }
	    }
	  }, {
	    key: "cleanProductRows",
	    value: function cleanProductRows() {
	      var _this16 = this;

	      this.products.filter(function (item) {
	        return !main_core.Type.isStringFilled(item.getField('PRODUCT_NAME', '').trim()) && item.getField('PRODUCT_ID', 0) <= 0 && item.getPrice() <= 0;
	      }).forEach(function (row) {
	        return _this16.deleteRow(row.getField('ID'), true);
	      });
	    }
	  }, {
	    key: "resortProductsByIds",
	    value: function resortProductsByIds(ids) {
	      var changed = false;

	      if (main_core.Type.isArrayFilled(ids)) {
	        this.products.sort(function (a, b) {
	          if (ids.indexOf(a.getField('ID')) > ids.indexOf(b.getField('ID'))) {
	            return 1;
	          }

	          changed = true;
	          return -1;
	        });
	      }

	      return changed;
	    }
	  }, {
	    key: "refreshSortFields",
	    value: function refreshSortFields() {
	      this.products.forEach(function (item, index) {
	        return item.setField('SORT', (index + 1) * 10);
	      });
	    }
	  }, {
	    key: "handleOnTabShow",
	    value: function handleOnTabShow() {
	      main_core_events.EventEmitter.emit('onDemandRecalculateWrapper');
	    }
	  }]);
	  return Editor;
	}();

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var MODE_EDIT = 'EDIT';
	var MODE_SET = 'SET';
	var Row = /*#__PURE__*/function () {
	  function Row(id, fields, settings, editor) {
	    babelHelpers.classCallCheck(this, Row);
	    babelHelpers.defineProperty(this, "fields", {});
	    babelHelpers.defineProperty(this, "externalActions", []);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    this.setId(id);
	    this.setFields(fields);
	    this.setSettings(settings);
	    this.setEditor(editor);
	    requestAnimationFrame(this.initHandlers.bind(this));
	  }

	  babelHelpers.createClass(Row, [{
	    key: "getNode",
	    value: function getNode() {
	      var _this = this;

	      return this.cache.remember('node', function () {
	        var rowId = _this.getField('ID', 0);

	        return _this.getEditorContainer().querySelector('[data-id="' + rowId + '"]');
	      });
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = id;
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      return this.settings;
	    }
	  }, {
	    key: "setSettings",
	    value: function setSettings(settings) {
	      this.settings = main_core.Type.isPlainObject(settings) ? settings : {};
	    }
	  }, {
	    key: "getSettingValue",
	    value: function getSettingValue(name, defaultValue) {
	      return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	    }
	  }, {
	    key: "setSettingValue",
	    value: function setSettingValue(name, value) {
	      this.settings[name] = value;
	    }
	  }, {
	    key: "setEditor",
	    value: function setEditor(editor) {
	      this.editor = editor;
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return this.editor;
	    }
	  }, {
	    key: "getEditorContainer",
	    value: function getEditorContainer() {
	      return this.getEditor().getContainer();
	    }
	  }, {
	    key: "initHandlers",
	    value: function initHandlers() {
	      var editor = this.getEditor();
	      this.getNode().querySelectorAll('input').forEach(function (node) {
	        main_core.Event.bind(node, 'input', editor.changeProductFieldHandler);
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for text fields

	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	      this.getNode().querySelectorAll('select').forEach(function (node) {
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for select fields

	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	    }
	  }, {
	    key: "initHandlersForProductSelector",
	    value: function initHandlersForProductSelector() {
	      var editor = this.getEditor();
	      this.getNode().querySelectorAll('[data-name="MAIN_INFO"] input[type="text"]').forEach(function (node) {
	        main_core.Event.bind(node, 'input', editor.changeProductFieldHandler);
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for select fields

	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	    }
	  }, {
	    key: "getFields",
	    value: function getFields() {
	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	      var result;

	      if (!main_core.Type.isArrayFilled(fields)) {
	        result = main_core.Runtime.clone(this.fields);
	      } else {
	        result = {};

	        var _iterator = _createForOfIteratorHelper$1(fields),
	            _step;

	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var fieldName = _step.value;
	            result[fieldName] = this.getField(fieldName);
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      }

	      if ('PRODUCT_NAME' in result) {
	        var fixedProductName = this.getField('FIXED_PRODUCT_NAME', '');

	        if (main_core.Type.isStringFilled(fixedProductName)) {
	          result['PRODUCT_NAME'] = fixedProductName;
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "getCatalogFields",
	    value: function getCatalogFields() {
	      var fields = this.getFields(['CURRENCY', 'QUANTITY', 'MEASURE_CODE']);
	      fields['PRICE'] = this.getBasePrice();
	      fields['VAT_INCLUDED'] = this.getTaxIncluded();
	      fields['VAT_ID'] = this.getTaxId();
	      return fields;
	    }
	  }, {
	    key: "getCalculateFields",
	    value: function getCalculateFields() {
	      return {
	        'PRICE': this.getPrice(),
	        'PRICE_EXCLUSIVE': this.getPriceExclusive(),
	        'PRICE_NETTO': this.getPriceNetto(),
	        'PRICE_BRUTTO': this.getPriceBrutto(),
	        'QUANTITY': this.getQuantity(),
	        'DISCOUNT_TYPE_ID': this.getDiscountType(),
	        'DISCOUNT_RATE': this.getDiscountRate(),
	        'DISCOUNT_SUM': this.getDiscountSum(),
	        'DISCOUNT_ROW': this.getDiscountRow(),
	        'TAX_INCLUDED': this.getTaxIncluded(),
	        'TAX_RATE': this.getTaxRate()
	      };
	    }
	  }, {
	    key: "setFields",
	    value: function setFields(fields) {
	      for (var name in fields) {
	        if (fields.hasOwnProperty(name)) {
	          this.setField(name, fields[name]);
	        }
	      }
	    }
	  }, {
	    key: "getField",
	    value: function getField(name, defaultValue) {
	      return this.fields.hasOwnProperty(name) ? this.fields[name] : defaultValue;
	    }
	  }, {
	    key: "setField",
	    value: function setField(name, value) {
	      this.fields[name] = value;
	    }
	  }, {
	    key: "getUiFieldId",
	    value: function getUiFieldId(field) {
	      return this.getId() + '_' + field;
	    }
	  }, {
	    key: "getBasePrice",
	    value: function getBasePrice() {
	      return this.isPriceNetto() ? this.getPriceNetto() : this.getPriceBrutto();
	    }
	  }, {
	    key: "isPriceNetto",
	    value: function isPriceNetto() {
	      return this.getEditor().isTaxAllowed() && !this.isTaxIncluded();
	    }
	  }, {
	    key: "getPrice",
	    value: function getPrice() {
	      return this.getField('PRICE', 0);
	    }
	  }, {
	    key: "getPriceExclusive",
	    value: function getPriceExclusive() {
	      return this.getField('PRICE_EXCLUSIVE', 0);
	    }
	  }, {
	    key: "getPriceNetto",
	    value: function getPriceNetto() {
	      return this.getField('PRICE_NETTO', 0);
	    }
	  }, {
	    key: "getPriceBrutto",
	    value: function getPriceBrutto() {
	      return this.getField('PRICE_BRUTTO', 0);
	    }
	  }, {
	    key: "getQuantity",
	    value: function getQuantity() {
	      return this.getField('QUANTITY', 1);
	    }
	  }, {
	    key: "getDiscountType",
	    value: function getDiscountType() {
	      return this.getField('DISCOUNT_TYPE_ID', catalog_productCalculator.DiscountType.UNDEFINED);
	    }
	  }, {
	    key: "isDiscountUndefined",
	    value: function isDiscountUndefined() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.UNDEFINED;
	    }
	  }, {
	    key: "isDiscountPercentage",
	    value: function isDiscountPercentage() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.PERCENTAGE;
	    }
	  }, {
	    key: "isDiscountMonetary",
	    value: function isDiscountMonetary() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.MONETARY;
	    }
	  }, {
	    key: "isDiscountHandmade",
	    value: function isDiscountHandmade() {
	      return this.isDiscountPercentage() || this.isDiscountMonetary();
	    }
	  }, {
	    key: "getDiscountRate",
	    value: function getDiscountRate() {
	      return this.getField('DISCOUNT_RATE', 0);
	    }
	  }, {
	    key: "getDiscountSum",
	    value: function getDiscountSum() {
	      return this.getField('DISCOUNT_SUM', 0);
	    }
	  }, {
	    key: "getDiscountRow",
	    value: function getDiscountRow() {
	      return this.getField('DISCOUNT_ROW', 0);
	    }
	  }, {
	    key: "isEmptyDiscount",
	    value: function isEmptyDiscount() {
	      if (this.isDiscountPercentage()) {
	        return this.getDiscountRate() === 0;
	      } else if (this.isDiscountMonetary()) {
	        return this.getDiscountSum() === 0;
	      } else if (this.isDiscountUndefined()) {
	        return true;
	      }

	      return false;
	    }
	  }, {
	    key: "getTaxIncluded",
	    value: function getTaxIncluded() {
	      return this.getField('TAX_INCLUDED', 'N');
	    }
	  }, {
	    key: "isTaxIncluded",
	    value: function isTaxIncluded() {
	      return this.getTaxIncluded() === 'Y';
	    }
	  }, {
	    key: "getTaxRate",
	    value: function getTaxRate() {
	      return this.getField('TAX_RATE', 0);
	    }
	  }, {
	    key: "getTaxSum",
	    value: function getTaxSum() {
	      return this.isTaxIncluded() ? this.getPrice() * this.getQuantity() * (1 - 1 / (1 + this.getTaxRate() / 100)) : this.getPriceExclusive() * this.getQuantity() * this.getTaxRate() / 100;
	    }
	  }, {
	    key: "getTaxNode",
	    value: function getTaxNode() {
	      return this.getNode().querySelector('select[data-field-code="TAX_RATE"]');
	    }
	  }, {
	    key: "getTaxId",
	    value: function getTaxId() {
	      var taxNode = this.getTaxNode();

	      if (main_core.Type.isDomNode(taxNode) && taxNode.options[taxNode.selectedIndex]) {
	        return main_core.Text.toNumber(taxNode.options[taxNode.selectedIndex].getAttribute('data-tax-id'));
	      }

	      return 0;
	    }
	  }, {
	    key: "updateFieldByEvent",
	    value: function updateFieldByEvent(fieldCode, event) {
	      var target = event.target;
	      var value = target.type === 'checkbox' ? target.checked : target.value;
	      var mode = event.type === 'input' ? MODE_EDIT : MODE_SET;
	      this.updateField(fieldCode, value, mode);
	    }
	  }, {
	    key: "updateField",
	    value: function updateField(fieldCode, value) {
	      var mode = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : MODE_SET;
	      this.resetExternalActions();
	      this.updateFieldValue(fieldCode, value, mode);
	      this.executeExternalActions();
	    }
	  }, {
	    key: "updateFieldValue",
	    value: function updateFieldValue(code, value) {
	      var mode = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : MODE_SET;

	      switch (code) {
	        case 'ID':
	          this.changeProductId(value);
	          break;

	        case 'PRICE':
	          this.changePrice(value, mode);
	          break;

	        case 'QUANTITY':
	          this.changeQuantity(value, mode);
	          break;

	        case 'MEASURE_CODE':
	          this.changeMeasureCode(value);
	          break;

	        case 'DISCOUNT':
	        case 'DISCOUNT_PRICE':
	          this.changeDiscount(value, mode);
	          break;

	        case 'DISCOUNT_TYPE_ID':
	          this.changeDiscountType(value);
	          break;

	        case 'DISCOUNT_ROW':
	          this.changeRowDiscount(value, mode);
	          break;

	        case 'VAT_ID':
	        case 'TAX_ID':
	          this.changeTaxId(value);
	          break;

	        case 'TAX_RATE':
	          this.changeTaxRate(value);
	          break;

	        case 'VAT_INCLUDED':
	        case 'TAX_INCLUDED':
	          this.changeTaxIncluded(value);
	          break;

	        case 'SUM':
	          this.changeRowSum(value, mode);
	          break;

	        case 'NAME':
	        case 'PRODUCT_NAME':
	        case 'MAIN_INFO':
	          this.changeProductName(value);
	          break;

	        case 'SORT':
	          this.changeSort(value, mode);
	          break;
	      }
	    }
	  }, {
	    key: "updateFieldByName",
	    value: function updateFieldByName(field, value) {
	      switch (field) {
	        case 'TAX_INCLUDED':
	          this.setTaxIncluded(value);
	          break;
	      }
	    }
	  }, {
	    key: "changeProductId",
	    value: function changeProductId(value) {
	      var preparedValue = this.parseInt(value);
	      this.setProductId(preparedValue);
	    }
	  }, {
	    key: "changePrice",
	    value: function changePrice(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setPrice(preparedValue, mode);
	    }
	  }, {
	    key: "changeQuantity",
	    value: function changeQuantity(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getQuantityPrecision());
	      this.setQuantity(preparedValue, mode);
	    }
	  }, {
	    key: "changeMeasureCode",
	    value: function changeMeasureCode(value) {
	      var _this2 = this;

	      this.getEditor().getMeasures().filter(function (item) {
	        return item.CODE === value;
	      }).forEach(function (item) {
	        return _this2.setMeasure(item);
	      });
	    }
	  }, {
	    key: "changeDiscount",
	    value: function changeDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue;

	      if (this.isDiscountPercentage()) {
	        preparedValue = this.parseFloat(value, this.getCommonPrecision());
	      } else {
	        preparedValue = this.parseFloat(value, this.getPricePrecision()).toFixed(this.getPricePrecision());
	      }

	      this.setDiscount(preparedValue, mode);
	    }
	  }, {
	    key: "changeDiscountType",
	    value: function changeDiscountType(value) {
	      var preparedValue = this.parseInt(value, catalog_productCalculator.DiscountType.UNDEFINED);
	      this.setDiscountType(preparedValue);
	    }
	  }, {
	    key: "changeRowDiscount",
	    value: function changeRowDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setRowDiscount(preparedValue, mode);
	    }
	  }, {
	    key: "changeTaxId",
	    value: function changeTaxId(value) {
	      var taxNode = this.getTaxNode();
	      var taxOptionNode = this.getNode().querySelector("option[data-tax-id=\"".concat(value, "\"]"));

	      if (main_core.Type.isDomNode(taxNode) && main_core.Type.isDomNode(taxOptionNode)) {
	        taxNode.value = taxOptionNode.value;
	        this.changeTaxRate(this.parseFloat(taxOptionNode.value));
	      }
	    }
	  }, {
	    key: "changeTaxRate",
	    value: function changeTaxRate(value) {
	      var preparedValue = this.parseFloat(value, this.getCommonPrecision());
	      this.setTaxRate(preparedValue);
	    }
	  }, {
	    key: "changeTaxIncluded",
	    value: function changeTaxIncluded(value) {
	      if (main_core.Type.isBoolean(value)) {
	        value = value ? 'Y' : 'N';
	      }

	      this.setTaxIncluded(value);
	    }
	  }, {
	    key: "changeRowSum",
	    value: function changeRowSum(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setRowSum(preparedValue, mode);
	    }
	  }, {
	    key: "changeProductName",
	    value: function changeProductName(value) {
	      var preparedValue = value.toString();
	      var isChangedValue = this.getField('PRODUCT_NAME') !== preparedValue;

	      if (isChangedValue) {
	        this.setField('PRODUCT_NAME', preparedValue);
	        this.addActionProductChange();
	      }
	    }
	  }, {
	    key: "changeSort",
	    value: function changeSort(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseInt(value);

	      if (mode === MODE_SET) {
	        this.setField('SORT', preparedValue);
	      }

	      var isChangedValue = this.getField('SORT') !== preparedValue;

	      if (isChangedValue) {
	        this.addActionProductChange();
	      }
	    }
	  }, {
	    key: "refreshFieldsLayout",
	    value: function refreshFieldsLayout() {
	      var exceptFields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];

	      for (var field in this.fields) {
	        if (this.fields.hasOwnProperty(field) && !exceptFields.includes(field)) {
	          this.updateUiField(field, this.fields[field]);
	        }
	      }
	    }
	  }, {
	    key: "getCalculator",
	    value: function getCalculator() {
	      /** @var {ProductCalculator} */
	      var calculator = this.cache.remember('calculator', function () {
	        return new catalog_productCalculator.ProductCalculator();
	      });
	      return calculator.setFields(this.getCalculateFields()).setSettings(this.getEditor().getSettings());
	    }
	  }, {
	    key: "setProductId",
	    value: function setProductId(value) {
	      var isChangedValue = this.getField('PRODUCT_ID') !== value;

	      if (isChangedValue) {
	        this.setField('PRODUCT_ID', value);
	        this.setField('OFFER_ID', value);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setPrice",
	    value: function setPrice(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_SET) {
	        this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
	      }

	      var isChangedValue = this.getBasePrice() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculatePrice(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['PRICE_NETTO', 'PRICE_BRUTTO']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setQuantity",
	    value: function setQuantity(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_SET) {
	        this.updateUiInputField('QUANTITY', value);
	      }

	      var isChangedValue = this.getField('QUANTITY') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateQuantity(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['QUANTITY']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setMeasure",
	    value: function setMeasure(measure) {
	      this.setField('MEASURE_CODE', measure.CODE);
	      this.setField('MEASURE_NAME', measure.SYMBOL);
	      this.updateUiMoneyField('MEASURE_CODE', measure.CODE, measure.SYMBOL);
	      this.addActionProductChange();
	    }
	  }, {
	    key: "setDiscount",
	    value: function setDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (!this.isDiscountHandmade()) {
	        return;
	      }

	      if (mode === MODE_SET) {
	        this.updateUiInputField('DISCOUNT_PRICE', value);
	      }

	      var fieldName = this.isDiscountPercentage() ? 'DISCOUNT_RATE' : 'DISCOUNT_SUM';
	      var isChangedValue = this.getField(fieldName) !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateDiscount(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['DISCOUNT_RATE', 'DISCOUNT_SUM', 'DISCOUNT']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setDiscountType",
	    value: function setDiscountType(value) {
	      var isChangedValue = value !== catalog_productCalculator.DiscountType.UNDEFINED && this.getField('DISCOUNT_TYPE_ID') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateDiscountType(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setRowDiscount",
	    value: function setRowDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_SET) {
	        this.updateUiInputField('DISCOUNT_ROW', value.toFixed(this.getPricePrecision()));
	      }

	      var isChangedValue = this.getField('DISCOUNT_ROW') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateRowDiscount(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['DISCOUNT_ROW']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setTaxRate",
	    value: function setTaxRate(value) {
	      if (!this.getEditor().isTaxAllowed()) {
	        return;
	      }

	      var isChangedValue = this.getTaxRate() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateTax(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setTaxIncluded",
	    value: function setTaxIncluded(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (!this.getEditor().isTaxAllowed()) {
	        return;
	      }

	      if (mode === MODE_SET) {
	        this.updateUiCheckboxField('TAX_INCLUDED', value);
	      }

	      var isChangedValue = this.getTaxIncluded() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateTaxIncluded(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionUpdateFieldList('TAX_INCLUDED', value);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setRowSum",
	    value: function setRowSum(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_SET) {
	        this.updateUiInputField('SUM', value.toFixed(this.getPricePrecision()));
	      }

	      var isChangedValue = this.getField('SUM') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateRowSum(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['SUM']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    } // controls

	  }, {
	    key: "getInputByFieldName",
	    value: function getInputByFieldName(fieldName) {
	      var fieldId = this.getUiFieldId(fieldName);
	      var item = document.getElementById(fieldId);

	      if (!main_core.Type.isElementNode(item)) {
	        item = this.getNode().querySelector('[name="' + fieldId + '"]');
	      }

	      return item;
	    }
	  }, {
	    key: "updateUiInputField",
	    value: function updateUiInputField(name, value) {
	      var item = this.getInputByFieldName(name);

	      if (main_core.Type.isElementNode(item)) {
	        item.value = value;
	      }
	    }
	  }, {
	    key: "updateUiCheckboxField",
	    value: function updateUiCheckboxField(name, value) {
	      var item = this.getInputByFieldName(name);

	      if (main_core.Type.isElementNode(item)) {
	        item.checked = value === 'Y';
	      }
	    }
	  }, {
	    key: "updateUiDiscountTypeField",
	    value: function updateUiDiscountTypeField(name, value) {
	      var text = value === catalog_productCalculator.DiscountType.MONETARY ? this.getEditor().getCurrencyText() : '%';
	      this.updateUiMoneyField(name, value, text);
	    }
	  }, {
	    key: "getMoneyFieldDropdownApi",
	    value: function getMoneyFieldDropdownApi(name) {
	      if (!main_core.Reflection.getClass('BX.Main.dropdownManager')) {
	        return null;
	      }

	      return BX.Main.dropdownManager.getById(this.getId() + '_' + name + '_control');
	    }
	  }, {
	    key: "updateMoneyFieldUiWithDropdownApi",
	    value: function updateMoneyFieldUiWithDropdownApi(dropdown, value) {
	      if (dropdown.getValue() == value) {
	        return;
	      }

	      var item = dropdown.menu.itemsContainer.querySelector('[data-value="' + value + '"]');
	      var menuItem = item && dropdown.getMenuItem(item);

	      if (menuItem) {
	        dropdown.refresh(menuItem);
	        dropdown.selectItem(menuItem);
	      }
	    }
	  }, {
	    key: "updateMoneyFieldUiManually",
	    value: function updateMoneyFieldUiManually(name, value, text) {
	      var item = this.getInputByFieldName(name);

	      if (!main_core.Type.isElementNode(item)) {
	        return;
	      }

	      item.dataset.value = value;
	      var span = item.querySelector('span.main-dropdown-inner');

	      if (!main_core.Type.isElementNode(span)) {
	        return;
	      }

	      span.innerHTML = text;
	    }
	  }, {
	    key: "updateUiMoneyField",
	    value: function updateUiMoneyField(name, value, text) {
	      var dropdownApi = this.getMoneyFieldDropdownApi(name);

	      if (dropdownApi) {
	        this.updateMoneyFieldUiWithDropdownApi(dropdownApi, value);
	      } else {
	        this.updateMoneyFieldUiManually(name, value, text);
	      }
	    }
	  }, {
	    key: "updateUiHtmlField",
	    value: function updateUiHtmlField(name, html) {
	      var item = this.getInputByFieldName(name);

	      if (main_core.Type.isElementNode(item)) {
	        item.innerHTML = html;
	      }
	    }
	  }, {
	    key: "updateUiCurrencyFields",
	    value: function updateUiCurrencyFields() {
	      var _this3 = this;

	      var currencyText = this.getEditor().getCurrencyText();
	      var currencyId = '' + this.getEditor().getCurrencyId();
	      var currencyFieldNames = ['PRICE_CURRENCY', 'SUM_CURRENCY', 'DISCOUNT_TYPE_ID', 'DISCOUNT_ROW_CURRENCY'];
	      currencyFieldNames.forEach(function (name) {
	        var dropdownValues = [];

	        if (name === 'DISCOUNT_TYPE_ID') {
	          dropdownValues.push({
	            NAME: '%',
	            VALUE: '' + catalog_productCalculator.DiscountType.PERCENTAGE
	          });
	          dropdownValues.push({
	            NAME: currencyText,
	            VALUE: '' + catalog_productCalculator.DiscountType.MONETARY
	          });

	          if (_this3.getDiscountType() === catalog_productCalculator.DiscountType.MONETARY) {
	            _this3.updateMoneyFieldUiManually(name, catalog_productCalculator.DiscountType.MONETARY, currencyText);
	          }
	        } else {
	          dropdownValues.push({
	            NAME: currencyText,
	            VALUE: currencyId
	          });

	          _this3.updateUiMoneyField(name, currencyId, currencyText);
	        }

	        main_core.Dom.attr(_this3.getInputByFieldName(name), 'data-items', dropdownValues);
	      });
	      this.updateUiField('TAX_SUM', this.getField('TAX_SUM'));
	    }
	  }, {
	    key: "updateUiField",
	    value: function updateUiField(field, value) {
	      var uiName = this.getUiFieldName(field);

	      if (!uiName) {
	        return;
	      }

	      var uiType = this.getUiFieldType(field);

	      if (!uiType) {
	        return;
	      }

	      if (!this.allowUpdateUiField(field)) {
	        return;
	      }

	      switch (uiType) {
	        case 'input':
	          if (field === 'QUANTITY') {
	            value = this.parseFloat(value, this.getQuantityPrecision());
	          } else if (field === 'DISCOUNT_RATE') {
	            value = this.parseFloat(value, this.getCommonPrecision());
	          } else if (main_core.Type.isNumber(value)) {
	            value = this.parseFloat(value, this.getPricePrecision()).toFixed(this.getPricePrecision());
	          }

	          this.updateUiInputField(uiName, value);
	          break;

	        case 'checkbox':
	          this.updateUiCheckboxField(uiName, value);
	          break;

	        case 'discount_type_field':
	          this.updateUiDiscountTypeField(uiName, value);
	          break;

	        case 'html':
	          this.updateUiHtmlField(uiName, value);
	          break;

	        case 'money_html':
	          value = currency_currencyCore.CurrencyCore.currencyFormat(value, this.getEditor().getCurrencyId(), true);
	          this.updateUiHtmlField(uiName, value);
	          break;
	      }
	    }
	  }, {
	    key: "getUiFieldName",
	    value: function getUiFieldName(field) {
	      var result = null;

	      switch (field) {
	        case 'QUANTITY':
	        case 'MEASURE_CODE':
	        case 'DISCOUNT_ROW':
	        case 'DISCOUNT_TYPE_ID':
	        case 'TAX_RATE':
	        case 'TAX_INCLUDED':
	        case 'TAX_SUM':
	        case 'SUM':
	        case 'PRODUCT_NAME':
	        case 'SORT':
	          result = field;
	          break;

	        case 'PRICE_NETTO':
	        case 'PRICE_BRUTTO':
	          result = 'PRICE';
	          break;

	        case 'DISCOUNT_RATE':
	        case 'DISCOUNT_SUM':
	          result = 'DISCOUNT_PRICE';
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "getUiFieldType",
	    value: function getUiFieldType(field) {
	      var result = null;

	      switch (field) {
	        case 'PRICE_NETTO':
	        case 'PRICE_BRUTTO':
	        case 'QUANTITY':
	        case 'DISCOUNT_RATE':
	        case 'DISCOUNT_SUM':
	        case 'DISCOUNT_ROW':
	        case 'SUM':
	        case 'PRODUCT_NAME':
	        case 'SORT':
	          result = 'input';
	          break;

	        case 'DISCOUNT_TYPE_ID':
	          result = 'discount_type_field';
	          break;

	        case 'TAX_RATE':
	          result = 'list';
	          break;

	        case 'TAX_INCLUDED':
	          result = 'checkbox';
	          break;

	        case 'TAX_SUM':
	          result = 'money_html';
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "allowUpdateUiField",
	    value: function allowUpdateUiField(field) {
	      var result = true;

	      switch (field) {
	        case 'PRICE_NETTO':
	          result = this.isPriceNetto();
	          break;

	        case 'PRICE_BRUTTO':
	          result = !this.isPriceNetto();
	          break;

	        case 'DISCOUNT_RATE':
	          result = this.isDiscountPercentage();
	          break;

	        case 'DISCOUNT_SUM':
	          result = this.isDiscountMonetary();
	          break;
	      }

	      return result;
	    } // proxy

	  }, {
	    key: "parseInt",
	    value: function parseInt(value) {
	      var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      return this.getEditor().parseInt(value, defaultValue);
	    }
	  }, {
	    key: "parseFloat",
	    value: function parseFloat(value, precision) {
	      var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      return this.getEditor().parseFloat(value, precision, defaultValue);
	    }
	  }, {
	    key: "getPricePrecision",
	    value: function getPricePrecision() {
	      return this.getEditor().getPricePrecision();
	    }
	  }, {
	    key: "getQuantityPrecision",
	    value: function getQuantityPrecision() {
	      return this.getEditor().getQuantityPrecision();
	    }
	  }, {
	    key: "getCommonPrecision",
	    value: function getCommonPrecision() {
	      return this.getEditor().getCommonPrecision();
	    }
	  }, {
	    key: "resetExternalActions",
	    value: function resetExternalActions() {
	      this.externalActions.length = 0;
	    }
	  }, {
	    key: "addExternalAction",
	    value: function addExternalAction(action) {
	      this.externalActions.push(action);
	    }
	  }, {
	    key: "addActionProductChange",
	    value: function addActionProductChange() {
	      this.addExternalAction({
	        type: this.getEditor().actions.productChange,
	        id: this.getId()
	      });
	    }
	  }, {
	    key: "addActionUpdateFieldList",
	    value: function addActionUpdateFieldList(field, value) {
	      this.addExternalAction({
	        type: this.getEditor().actions.updateListField,
	        field: field,
	        value: value
	      });
	    }
	  }, {
	    key: "addActionStateChanged",
	    value: function addActionStateChanged() {
	      this.addExternalAction({
	        type: this.getEditor().actions.stateChanged,
	        value: true
	      });
	    }
	  }, {
	    key: "addActionStateReset",
	    value: function addActionStateReset() {
	      this.addExternalAction({
	        type: this.getEditor().actions.stateChanged,
	        value: false
	      });
	    }
	  }, {
	    key: "addActionUpdateTotal",
	    value: function addActionUpdateTotal() {
	      this.addExternalAction({
	        type: this.getEditor().actions.updateTotal
	      });
	    }
	  }, {
	    key: "executeExternalActions",
	    value: function executeExternalActions() {
	      if (this.externalActions.length === 0) {
	        return;
	      }

	      this.getEditor().executeActions(this.externalActions);
	      this.resetExternalActions();
	    }
	  }]);
	  return Row;
	}();

	exports.Row = Row;
	exports.Editor = Editor;
	exports.PageEventsManager = PageEventsManager;

}((this.BX.Crm.Entity.ProductList = this.BX.Crm.Entity.ProductList || {}),BX.Main,BX,BX.Event,BX.Catalog,BX.Catalog,BX.Currency));
//# sourceMappingURL=script.js.map
