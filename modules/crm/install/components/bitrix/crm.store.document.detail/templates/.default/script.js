/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Store = this.BX.Crm.Store || {};
(function (exports,ui_designTokens,catalog_entityCard,main_popup,ui_buttons,main_core,main_core_events,spotlight,catalog_storeEnableWizard,catalog_toolAvailabilityManager) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onboardingData = /*#__PURE__*/new WeakMap();
	var _documentGuid = /*#__PURE__*/new WeakMap();
	var _productListController = /*#__PURE__*/new WeakMap();
	var _hintProductListField = /*#__PURE__*/new WeakSet();
	var _getFirstProductRow = /*#__PURE__*/new WeakSet();
	var _getProductList = /*#__PURE__*/new WeakSet();
	var DocumentOnboardingManager = /*#__PURE__*/function () {
	  function DocumentOnboardingManager(params) {
	    babelHelpers.classCallCheck(this, DocumentOnboardingManager);
	    _classPrivateMethodInitSpec(this, _getProductList);
	    _classPrivateMethodInitSpec(this, _getFirstProductRow);
	    _classPrivateMethodInitSpec(this, _hintProductListField);
	    _classPrivateFieldInitSpec(this, _onboardingData, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _documentGuid, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _productListController, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _onboardingData, params.onboardingData);
	    babelHelpers.classPrivateFieldSet(this, _documentGuid, params.documentGuid);
	    if (params.productListController) {
	      babelHelpers.classPrivateFieldSet(this, _productListController, params.productListController);
	    }
	  }
	  babelHelpers.createClass(DocumentOnboardingManager, [{
	    key: "processOnboarding",
	    value: function processOnboarding() {
	      var chain = babelHelpers.classPrivateFieldGet(this, _onboardingData).chain;
	      var step = babelHelpers.classPrivateFieldGet(this, _onboardingData).chainStep;
	      if (chain === 1 && step === 1) {
	        var rowId = _classPrivateMethodGet(this, _getFirstProductRow, _getFirstProductRow2).call(this);
	        if (rowId) {
	          _classPrivateMethodGet(this, _hintProductListField, _hintProductListField2).call(this, rowId);
	        }
	      }
	    }
	  }]);
	  return DocumentOnboardingManager;
	}();
	function _hintProductListField2() {
	  var rowId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	  var buttonsContainer = document.querySelector("#".concat(babelHelpers.classPrivateFieldGet(this, _documentGuid), "_TABS_MENU"));
	  var spotlight$$1 = new BX.SpotLight({
	    id: 'arrow_spotlight',
	    targetElement: document.querySelector('[data-tab-id=tab_products]'),
	    autoSave: true,
	    targetVertex: "middle-center",
	    zIndex: 200
	  });
	  spotlight$$1.show();
	  spotlight$$1.container.style.pointerEvents = "none";
	  var productListTabListener = function productListTabListener(event) {
	    spotlight$$1.close();
	    var _event$data = babelHelpers.slicedToArray(event.data, 1),
	      productListEditor = _event$data[0];
	    var buttonsPanelListener = function buttonsPanelListener() {
	      var activeHint = productListEditor.getActiveHint();
	      if (activeHint !== null) {
	        activeHint.close();
	        main_core.Event.unbind(buttonsContainer, 'click', buttonsPanelListener);
	      }
	    };
	    main_core.Event.bind(buttonsContainer, 'click', buttonsPanelListener);
	    var tabChangeListener = function tabChangeListener(event) {
	      var _event$data2, _productListEditor$ge;
	      if ((event === null || event === void 0 ? void 0 : (_event$data2 = event.data) === null || _event$data2 === void 0 ? void 0 : _event$data2.tabId) === 'tab_products') {
	        return;
	      }
	      (_productListEditor$ge = productListEditor.getActiveHint()) === null || _productListEditor$ge === void 0 ? void 0 : _productListEditor$ge.close();
	    };
	    productListEditor.showFieldTourHint('AMOUNT', {
	      title: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_AMOUNT_GUIDE_TITLE_2'),
	      text: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_AMOUNT_GUIDE_TEXT')
	    }, function () {
	      main_core.userOptions.save('crm', 'warehouse-onboarding', 'secondChainStage', 2);
	      main_core.userOptions.save('crm', 'warehouse-onboarding', 'chainStage', 2);
	      main_core.Event.unbind(buttonsContainer, 'click', buttonsPanelListener);
	      main_core_events.EventEmitter.unsubscribe('onDemandRecalculateWrapper', productListTabListener);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.EntityCard.TabManager:onOpenTab', tabChangeListener);
	    }, [], rowId);
	    main_core_events.EventEmitter.subscribe('BX.Catalog.EntityCard.TabManager:onOpenTab', tabChangeListener);
	  };
	  main_core_events.EventEmitter.subscribe('onDemandRecalculateWrapper', productListTabListener);
	}
	function _getFirstProductRow2() {
	  var productList = _classPrivateMethodGet(this, _getProductList, _getProductList2).call(this);
	  var _iterator = _createForOfIteratorHelper(productList),
	    _step;
	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var product = _step.value;
	      if (!product.getModel().isService()) {
	        return product.getId();
	      }
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }
	  return '';
	}
	function _getProductList2() {
	  if (babelHelpers.classPrivateFieldGet(this, _productListController) && babelHelpers.classPrivateFieldGet(this, _productListController).productList) {
	    if (babelHelpers.classPrivateFieldGet(this, _productListController).productList.products instanceof Array) {
	      return babelHelpers.classPrivateFieldGet(this, _productListController).productList.products;
	    }
	  }
	  return [];
	}

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _documentOnboardingManager = /*#__PURE__*/new WeakMap();
	var _subscribeToProductRowSummaryEvents = /*#__PURE__*/new WeakSet();
	var Document = /*#__PURE__*/function (_BaseCard) {
	  babelHelpers.inherits(Document, _BaseCard);
	  function Document(id, settings) {
	    var _this;
	    babelHelpers.classCallCheck(this, Document);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this, id, settings));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _subscribeToProductRowSummaryEvents);
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _documentOnboardingManager, {
	      writable: true,
	      value: null
	    });
	    _this.isDocumentDeducted = settings.documentStatus === 'Y';
	    _this.isDeductLocked = settings.isDeductLocked;
	    _this.masterSliderUrl = settings.masterSliderUrl;
	    _this.inventoryManagementSource = settings.inventoryManagementSource;
	    _this.permissions = settings.permissions;
	    _this.isInventoryManagementDisabled = settings.isInventoryManagementDisabled;
	    _this.inventoryManagementFeatureCode = settings.inventoryManagementFeatureCode;
	    _this.lockedCancellation = settings.isProductBatchMethodSelected;
	    _this.isOnecMode = settings.isOnecMode;
	    _this.addCopyLinkPopup();
	    main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onFailedValidation', function (event) {
	      main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	        tabId: 'main'
	      });
	    });
	    main_core_events.EventEmitter.subscribe('onProductsCheckFailed', function (event) {
	      main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	        tabId: 'tab_products'
	      });
	    });
	    main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', function (event) {
	      var eventEditor = event.data[0];
	      if (eventEditor && eventEditor._ajaxForm) {
	        var _eventEditor$_ajaxFor, _eventEditor$_ajaxFor2;
	        if (_this.isInventoryManagementDisabled) {
	          var _eventEditor$_toolPan;
	          event.data[1].cancel = true;
	          (_eventEditor$_toolPan = eventEditor._toolPanel) === null || _eventEditor$_toolPan === void 0 ? void 0 : _eventEditor$_toolPan.setLocked(false);
	          return;
	        }
	        var action = ((_eventEditor$_ajaxFor = eventEditor._ajaxForm) === null || _eventEditor$_ajaxFor === void 0 ? void 0 : _eventEditor$_ajaxFor._actionName) === 'SAVE' ? 'save' : (_eventEditor$_ajaxFor2 = eventEditor._ajaxForm) === null || _eventEditor$_ajaxFor2 === void 0 ? void 0 : _eventEditor$_ajaxFor2._config.data.ACTION;
	        if (action === Document.saveAndDeductAction) {
	          var controllersErrorCollection = _this.getControllersIssues(eventEditor.getControllers());
	          if (controllersErrorCollection.length > 0) {
	            var _eventEditor$_toolPan2, _eventEditor$_toolPan3;
	            event.data[1].cancel = true;
	            (_eventEditor$_toolPan2 = eventEditor._toolPanel) === null || _eventEditor$_toolPan2 === void 0 ? void 0 : _eventEditor$_toolPan2.setLocked(false);
	            (_eventEditor$_toolPan3 = eventEditor._toolPanel) === null || _eventEditor$_toolPan3 === void 0 ? void 0 : _eventEditor$_toolPan3.addError(controllersErrorCollection[0]);
	            return;
	          }
	        }
	        if (action === 'SAVE') {
	          // for consistency in analytics tags
	          action = 'save';
	        }
	        var urlParams = {
	          isNewDocument: _this.entityId <= 0 ? 'Y' : 'N',
	          inventoryManagementSource: _this.inventoryManagementSource
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
	          documentType: 'W',
	          inventoryManagementSource: _this.inventoryManagementSource
	        });
	        _this.isTabAnalyticsSent = true;
	      }
	    });
	    _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _subscribeToProductRowSummaryEvents, _subscribeToProductRowSummaryEvents2).call(babelHelpers.assertThisInitialized(_this));
	    _classStaticPrivateFieldSpecSet(Document, Document, _instance, babelHelpers.assertThisInitialized(_this));
	    BX.UI.SidePanel.Wrapper.setParam('closeAfterSave', true);
	    _this.showNotificationOnClose = false;
	    return _this;
	  }
	  babelHelpers.createClass(Document, [{
	    key: "focusOnTab",
	    value: function focusOnTab(tabId) {
	      main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	        tabId: tabId
	      });
	    }
	  }, {
	    key: "getControllersIssues",
	    value: function getControllersIssues(controllers) {
	      var validateErrorCollection = [];
	      if (Array.isArray(controllers)) {
	        controllers.forEach(function (controller) {
	          if (controller instanceof BX.Crm.EntityStoreDocumentProductListController) {
	            validateErrorCollection.push.apply(validateErrorCollection, babelHelpers.toConsumableArray(controller.getErrorCollection()));
	          }
	        });
	      }
	      return validateErrorCollection;
	    }
	  }, {
	    key: "openMasterSlider",
	    value: function openMasterSlider() {
	      var card = this;
	      new catalog_storeEnableWizard.EnableWizardOpener().open(this.masterSliderUrl, {
	        urlParams: {
	          analyticsContextSection: catalog_storeEnableWizard.AnalyticsContextList.DOCUMENT_CARD
	        },
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
	              BX.SidePanel.Instance.getOpenSliders().forEach(function (slider) {
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
	        if (_this2.isInventoryManagementDisabled) {
	          _this2.showPlanRestrictedSlider();
	          return;
	        }
	        _this2.showNotificationOnClose = false;
	        editor._ajaxForm._config.data.ACTION = _this2.defaultSaveActionName;
	        editor._ajaxForm._config.onsuccess = _this2.defaultOnSuccessCallback;
	        savePanel.onSaveButtonClick(event);
	      };
	      if (this.permissions.conduct && !this.isDocumentDeducted) {
	        var deductAndSaveButton = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<button class=\"ui-btn ui-btn-light-border\">", "</button>"])), main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_SAVE_AND_DEDUCT_BUTTON'));
	        deductAndSaveButton.onclick = function (event) {
	          if (_this2.isInventoryManagementDisabled) {
	            _this2.showPlanRestrictedSlider();
	            return;
	          }
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
	          text: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_DEDUCT_BUTTON'),
	          color: ui_buttons.ButtonColor.LIGHT_BORDER,
	          onclick: function onclick(button, event) {
	            if (savePanel.isLocked()) {
	              return;
	            }
	            if (_this2.isInventoryManagementDisabled) {
	              _this2.showPlanRestrictedSlider();
	              return;
	            }
	            if (_this2.isDeductLocked) {
	              _this2.openMasterSlider();
	              return;
	            }
	            button.setState(ui_buttons.ButtonState.CLOCKING);
	            savePanel.setLocked(true);
	            var actionName = Document.deductAction;
	            var controllers = editor.getControllers();
	            var errorCollection = [];
	            controllers.forEach(function (controller) {
	              if (controller instanceof BX.Crm.EntityStoreDocumentProductListController && !controller.validateProductList()) {
	                errorCollection.push.apply(errorCollection, babelHelpers.toConsumableArray(controller.getErrorCollection()));
	              }
	            });
	            if (errorCollection.length > 0) {
	              savePanel.clearErrors();
	              savePanel.addError(errorCollection[0]);
	              savePanel.setLocked(false);
	              button.setActive(true);
	              return;
	            }
	            var formData = {};
	            if (window.EntityEditorDocumentOrderShipmentController) {
	              formData = window.EntityEditorDocumentOrderShipmentController.demandFormData();
	            }
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
	              action: actionName,
	              documentType: 'W'
	            });
	            deductDocumentAjaxForm.submit();
	          }
	        }).render();
	        saveButton.after(deductButton);
	        this.deductButton = deductButton;
	      } else if (this.permissions.cancel && !this.isDisabledCancellation()) {
	        var _deductButton = new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_CANCEL_DEDUCT_BUTTON'),
	          color: ui_buttons.ButtonColor.LIGHT_BORDER,
	          onclick: function onclick(button, event) {
	            if (savePanel.isLocked()) {
	              return;
	            }
	            if (_this2.isInventoryManagementDisabled) {
	              _this2.showPlanRestrictedSlider();
	              return;
	            }
	            if (_this2.isDeductLocked) {
	              _this2.openMasterSlider();
	              return;
	            }
	            if (_this2.isLockedCancellation()) {
	              _this2.showCancellationInfo();
	              return;
	            }
	            button.setState(ui_buttons.ButtonState.CLOCKING);
	            savePanel.setLocked(true);
	            var actionName = Document.cancelDeductAction;
	            var formData = {};
	            if (window.EntityEditorDocumentOrderShipmentController) {
	              formData = window.EntityEditorDocumentOrderShipmentController.demandFormData();
	            }
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
	              action: actionName,
	              documentType: 'W',
	              inventoryManagementSource: _this2.inventoryManagementSource
	            });
	            deductDocumentAjaxForm.submit();
	          }
	        }).render();
	        saveButton.after(_deductButton);
	        this.deductButton = _deductButton;
	      }
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
	    key: "showPlanRestrictedSlider",
	    value: function showPlanRestrictedSlider() {
	      if (this.isOnecMode) {
	        catalog_toolAvailabilityManager.OneCPlanRestrictionSlider.show();
	      } else if (this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	      }
	    }
	  }, {
	    key: "isLockedCancellation",
	    value: function isLockedCancellation() {
	      return this.lockedCancellation;
	    }
	  }, {
	    key: "isDisabledCancellation",
	    value: function isDisabledCancellation() {
	      return this.isOnecMode;
	    }
	  }, {
	    key: "showCancellationInfo",
	    value: function showCancellationInfo() {
	      var _this3 = this;
	      var popup = new main_popup.Popup(null, null, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            popup.destroy();
	          }
	        },
	        content: this.getCancellationPopupContent(),
	        overlay: true,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_YES'),
	          color: ui_buttons.Button.Color.PRIMARY,
	          onclick: function onclick() {
	            _this3.lockedCancellation = false;
	            if (_this3.deductButton) {
	              _this3.deductButton.click();
	            }
	            popup.close();
	          }
	        }), new BX.UI.Button({
	          text: main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_NO'),
	          color: BX.UI.Button.Color.LINK,
	          onclick: function onclick() {
	            popup.close();
	          }
	        })]
	      });
	      popup.show();
	    }
	  }, {
	    key: "getCancellationPopupContent",
	    value: function getCancellationPopupContent() {
	      var moreLink = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<a href=\"#\" class=\"ui-form-link\">", "</a>"])), main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_LINK'));
	      main_core.Event.bind(moreLink, 'click', function () {
	        if (top.BX.Helper) {
	          top.BX.Helper.show("redirect=detail&code=".concat(Document.HELP_COST_CALCULATION_MODE_ARTICLE_ID));
	        }
	      });
	      var descriptionHtml = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>", "</div>\n\t\t"])), main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_HINT').replace('#HELP_LINK#', '<help-link></help-link>'));
	      main_core.Dom.replace(descriptionHtml.querySelector('help-link'), moreLink);
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<h3>", "</h3>\n\t\t\t\t<div>", "\n\t\t\t\t<br>", "<div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_TITLE'), main_core.Text.encode(main_core.Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_CANCELLATION_POPUP_QUESTION')), descriptionHtml);
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
	      var _this4 = this;
	      var copyLinkButton = document.getElementById(this.settings.copyLinkButtonId);
	      if (!copyLinkButton) {
	        return;
	      }
	      copyLinkButton.onclick = function () {
	        _this4.copyDocumentLinkToClipboard();
	      };
	    }
	  }, {
	    key: "copyDocumentLinkToClipboard",
	    value: function copyDocumentLinkToClipboard() {
	      var url = BX.util.remove_url_param(window.location.href, ['IFRAME', 'IFRAME_TYPE']);
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
	          position: 'top'
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
	  }, {
	    key: "enableOnboardingChain",
	    value: function enableOnboardingChain(onboardingData) {
	      if (babelHelpers.classPrivateFieldGet(this, _documentOnboardingManager) === null) {
	        babelHelpers.classPrivateFieldSet(this, _documentOnboardingManager, new DocumentOnboardingManager({
	          onboardingData: onboardingData,
	          documentGuid: this.id,
	          productListController: this.getProductListController()
	        }));
	        babelHelpers.classPrivateFieldGet(this, _documentOnboardingManager).processOnboarding();
	      }
	    }
	  }, {
	    key: "getProductListController",
	    value: function getProductListController() {
	      var editor = this.getEditorInstance();
	      var controllers = editor.getControllers();
	      var _iterator = _createForOfIteratorHelper$1(controllers),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var controller = _step.value;
	          if (controller instanceof BX.Crm.EntityStoreDocumentProductListController) {
	            return controller;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return null;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      return _classStaticPrivateFieldSpecGet(Document, Document, _instance);
	    }
	  }]);
	  return Document;
	}(catalog_entityCard.BaseCard);
	function _subscribeToProductRowSummaryEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorProductRowSummary:onDetailProductListLinkClick', function () {
	    main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	      tabId: 'tab_products'
	    });
	  });
	  main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList', function () {
	    main_core_events.EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {
	      tabId: 'tab_products'
	    });
	    setTimeout(function () {
	      main_core_events.EventEmitter.emit('onFocusToProductList');
	    }, 500);
	  });
	}
	var _instance = {
	  writable: true,
	  value: void 0
	};
	babelHelpers.defineProperty(Document, "saveAndDeductAction", 'saveAndDeduct');
	babelHelpers.defineProperty(Document, "deductAction", 'deduct');
	babelHelpers.defineProperty(Document, "cancelDeductAction", 'cancelDeduct');
	babelHelpers.defineProperty(Document, "HELP_COST_CALCULATION_MODE_ARTICLE_ID", 17858278);

	exports.Document = Document;

}((this.BX.Crm.Store.DocumentCard = this.BX.Crm.Store.DocumentCard || {}),BX,BX.Catalog.EntityCard,BX.Main,BX.UI,BX,BX.Event,BX,BX.Catalog.Store,BX.Catalog));
//# sourceMappingURL=script.js.map
