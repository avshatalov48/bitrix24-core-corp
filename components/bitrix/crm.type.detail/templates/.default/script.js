/* eslint-disable */
(function (exports,crm_integration_analytics,crm_router,crm_toolbarComponent,crm_typeModel,main_core,main_core_events,main_loader,ui_analytics,ui_dialogs_messagebox,ui_entitySelector,ui_alerts) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var instance = null;

	/**
	 * @memberOf BX.Crm.Component
	 */
	var _isCancelEventRegistered = /*#__PURE__*/new WeakMap();
	var _registerCancelEvent = /*#__PURE__*/new WeakSet();
	var _findActiveTabButton = /*#__PURE__*/new WeakSet();
	var _findFirstTabButton = /*#__PURE__*/new WeakSet();
	var _getAnalyticsBuilder = /*#__PURE__*/new WeakSet();
	var TypeDetail = /*#__PURE__*/function () {
	  function TypeDetail(params) {
	    babelHelpers.classCallCheck(this, TypeDetail);
	    _classPrivateMethodInitSpec(this, _getAnalyticsBuilder);
	    _classPrivateMethodInitSpec(this, _findFirstTabButton);
	    _classPrivateMethodInitSpec(this, _findActiveTabButton);
	    _classPrivateMethodInitSpec(this, _registerCancelEvent);
	    babelHelpers.defineProperty(this, "isProgress", false);
	    babelHelpers.defineProperty(this, "tabs", new Map());
	    babelHelpers.defineProperty(this, "isRestricted", false);
	    babelHelpers.defineProperty(this, "isExternal", false);
	    babelHelpers.defineProperty(this, "isSaveFromTypeDetail", true);
	    babelHelpers.defineProperty(this, "isCreateSectionsViaAutomatedSolutionDetails", false);
	    babelHelpers.defineProperty(this, "canEditAutomatedSolution", false);
	    babelHelpers.defineProperty(this, "permissionsUrl", null);
	    _classPrivateFieldInitSpec(this, _isCancelEventRegistered, {
	      writable: true,
	      value: false
	    });
	    if (main_core.Type.isPlainObject(params)) {
	      this.type = params.type;
	      this.isNew = !this.type.isSaved();
	      this.form = params.form;
	      this.container = params.container;
	      this.errorsContainer = params.errorsContainer;
	      this.presets = params.presets;
	      this.relations = params.relations;
	      this.isRestricted = Boolean(params.isRestricted);
	      this.restrictionErrorMessage = main_core.Type.isStringFilled(params.restrictionErrorMessage) ? params.restrictionErrorMessage : '';
	      this.restrictionSliderCode = main_core.Type.isStringFilled(params.restrictionSliderCode) && this.isRestricted ? params.restrictionSliderCode : null;
	      this.isExternal = Boolean(params.isExternal);
	      this.isCreateSectionsViaAutomatedSolutionDetails = Boolean(params.isCreateSectionsViaAutomatedSolutionDetails);
	      this.canEditAutomatedSolution = Boolean(params.canEditAutomatedSolution);
	      if (main_core.Type.isStringFilled(params.permissionsUrl)) {
	        this.permissionsUrl = params.permissionsUrl;
	      }
	    }
	    this.buttonsPanel = document.getElementById('ui-button-panel');
	    this.saveButton = document.getElementById('ui-button-panel-save');
	    this.cancelButton = document.getElementById('ui-button-panel-cancel');
	    this.deleteButton = document.getElementById('ui-button-panel-remove');

	    // eslint-disable-next-line unicorn/no-this-assignment
	    instance = this;
	  }
	  babelHelpers.createClass(TypeDetail, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	      this.fillTabs();
	      if (this.type.getId()) {
	        // const customPreset = this.getPresetById('bitrix:empty');
	        // const presetSelector = document.querySelector('[data-role="crm-type-preset-selector"]');
	        // if (customPreset && presetSelector)
	        // {
	        // 	presetSelector.textContent = customPreset.fields.title;
	        // }
	        this.disablePresetsView();
	        var presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
	        if (presetSelectorContainer) {
	          main_core.Dom.addClass(presetSelectorContainer, 'crm-type-hidden');
	        }
	      } else {
	        this.enablePresetsView();
	      }
	      main_core.Dom.removeClass(document.querySelector('body'), 'crm-type-hidden');
	      this.initRelations();
	      this.initCustomSections();
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core.Event.bind(this.saveButton, 'click', function (event) {
	        _this.save(event);
	      }, {
	        passive: false
	      });
	      if (this.deleteButton) {
	        main_core.Event.bind(this.deleteButton, 'click', function (event) {
	          _this["delete"](event);
	        });
	      }
	      var userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
	      if (userFieldOption) {
	        main_core.Event.bind(userFieldOption, 'click', this.disableLinkedUserFieldsIfNotAvailable.bind(this));
	      }
	      this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach(function (linkedUserFieldNode) {
	        main_core.Event.bind(linkedUserFieldNode, 'click', _this.enableUserFieldIfAnyLinkedChecked.bind(_this));
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onCloseByEsc', function (event) {
	        var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	          sliderEvent = _event$getData2[0];
	        var slider = sliderEvent.getSlider();
	        if (slider === _this.getSlider()) {
	          _classPrivateMethodGet(_this, _registerCancelEvent, _registerCancelEvent2).call(_this, crm_integration_analytics.Dictionary.ELEMENT_ESC_BUTTON);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function (event) {
	        var _event$getData3 = event.getData(),
	          _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 1),
	          sliderEvent = _event$getData4[0];
	        var slider = sliderEvent.getSlider();
	        if (slider === _this.getSlider()) {
	          _classPrivateMethodGet(_this, _registerCancelEvent, _registerCancelEvent2).call(_this, null);
	        }
	      });
	      this.handleSliderDestroy = this.handleSliderDestroy.bind(this);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
	    }
	  }, {
	    key: "handleSliderDestroy",
	    value: function handleSliderDestroy(event) {
	      var _event$getData5 = event.getData(),
	        _event$getData6 = babelHelpers.slicedToArray(_event$getData5, 1),
	        sliderEvent = _event$getData6[0];
	      var slider = sliderEvent.getSlider();
	      if (slider.getFrameWindow() === window) {
	        // if we add event handler from iframe to the main page, they will live forever, even after slider destroys
	        // sometimes it causes errors, like in this case
	        this.destroy();
	        top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      var _this$customSectionCo;
	      (_this$customSectionCo = this.customSectionController) === null || _this$customSectionCo === void 0 ? void 0 : _this$customSectionCo.destroy();
	    }
	  }, {
	    key: "enablePresetsView",
	    value: function enablePresetsView() {
	      main_core.Dom.addClass(document.querySelector('body'), 'crm-type-settings-presets');
	      var activeTab = this.container.querySelector('.crm-type-tab-current');
	      if (activeTab) {
	        main_core.Dom.removeClass(activeTab, 'crm-type-tab-current');
	      }
	      var presetsTab = this.container.querySelector('[data-tab="presets"]');
	      if (presetsTab) {
	        main_core.Dom.addClass(presetsTab, 'crm-type-tab-current');
	      }
	      var presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
	      if (presetSelectorContainer) {
	        main_core.Dom.addClass(presetSelectorContainer, 'crm-type-hidden');
	      }
	      main_core.Dom.removeClass(document.getElementById('pagetitle'), 'crm-type-hidden');
	      main_core.Dom.addClass(this.buttonsPanel, 'crm-type-hidden');
	      this.hideErrors();
	    }
	  }, {
	    key: "disablePresetsView",
	    value: function disablePresetsView() {
	      var _classPrivateMethodGe;
	      main_core.Dom.removeClass(document.querySelector('body'), 'crm-type-settings-presets');
	      ((_classPrivateMethodGe = _classPrivateMethodGet(this, _findActiveTabButton, _findActiveTabButton2).call(this)) !== null && _classPrivateMethodGe !== void 0 ? _classPrivateMethodGe : _classPrivateMethodGet(this, _findFirstTabButton, _findFirstTabButton2).call(this)).click();
	      var presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
	      if (presetSelectorContainer) {
	        main_core.Dom.removeClass(presetSelectorContainer, 'crm-type-hidden');
	      }
	      main_core.Dom.addClass(document.getElementById('pagetitle'), 'crm-type-hidden');
	      main_core.Dom.removeClass(this.buttonsPanel, 'crm-type-hidden');
	    }
	  }, {
	    key: "disableLinkedUserFieldsIfNotAvailable",
	    value: function disableLinkedUserFieldsIfNotAvailable() {
	      var _this2 = this;
	      var userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
	      if (!this.isBooleanFieldChecked(userFieldOption)) {
	        this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach(function (linkedUserFieldNode) {
	          _this2.setBooleanFieldCheckedState(linkedUserFieldNode, false);
	        });
	      }
	    }
	  }, {
	    key: "enableUserFieldIfAnyLinkedChecked",
	    value: function enableUserFieldIfAnyLinkedChecked() {
	      var _this3 = this;
	      var userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
	      if (!this.isBooleanFieldChecked(userFieldOption)) {
	        this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach(function (linkedUserFieldNode) {
	          if (_this3.isBooleanFieldChecked(linkedUserFieldNode)) {
	            _this3.setBooleanFieldCheckedState(userFieldOption, true);
	          }
	        });
	      }
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 150
	        });
	      }
	      return this.loader;
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      this.isProgress = true;
	      if (!this.getLoader().isShown()) {
	        this.getLoader().show(this.form);
	      }
	      this.hideErrors();
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      var _this4 = this;
	      this.isProgress = false;
	      this.getLoader().hide();
	      setTimeout(function () {
	        main_core.Dom.removeClass(_this4.saveButton, 'ui-btn-wait');
	        main_core.Dom.removeClass(_this4.cancelButton, 'ui-btn-wait');
	        if (_this4.deleteButton) {
	          main_core.Dom.removeClass(_this4.deleteButton, 'ui-btn-wait');
	        }
	      }, 200);
	    }
	  }, {
	    key: "save",
	    value: function save(event) {
	      var _this5 = this;
	      if (this.isRestricted) {
	        if (main_core.Type.isStringFilled(this.restrictionSliderCode) && main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	          BX.UI.InfoHelper.show(this.restrictionSliderCode);
	        } else {
	          this.showErrors([this.restrictionErrorMessage]);
	        }
	        this.stopProgress();
	        return;
	      }
	      event.preventDefault();
	      if (!this.form) {
	        return;
	      }
	      if (this.isProgress) {
	        return;
	      }
	      if (!this.type) {
	        return;
	      }
	      this.startProgress();
	      this.type.setTitle(this.form.querySelector('[name="title"]').value);
	      crm_typeModel.TypeModel.getBooleanFieldNames().forEach(function (fieldName) {
	        var fieldNode = _this5.getBooleanFieldNodeByName(fieldName);
	        if (fieldNode) {
	          _this5.type.data[fieldName] = _this5.isBooleanFieldChecked(fieldNode);
	        }
	      });
	      // this.type.setConversionMap({
	      // 	sourceTypes: this.collectEntityTypeIds('conversion-source'),
	      // 	destinationTypes: this.collectEntityTypeIds('conversion-destination'),
	      // });
	      var linkedUserFields = {};
	      this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach(function (linkedUserFieldNode) {
	        var name = linkedUserFieldNode.dataset.name.slice('linkedUserFields['.length).replace(']', '');
	        linkedUserFields[name] = _this5.isBooleanFieldChecked(linkedUserFieldNode);
	      });
	      this.type.setLinkedUserFields(linkedUserFields);
	      this.type.setRelations({
	        parent: this.parentRelationsController.getData(),
	        child: this.childRelationsController.getData()
	      });
	      if (this.customSectionController) {
	        var customSectionData = this.customSectionController.getData();
	        this.type.setCustomSectionId(customSectionData.customSectionId);
	        this.type.setCustomSections(customSectionData.customSections);
	        this.type.setIsExternalDynamicalType(this.isExternal);
	        this.type.setIsSaveFromTypeDetail(this.isSaveFromTypeDetail);
	      }
	      var analyticsBuilder = _classPrivateMethodGet(this, _getAnalyticsBuilder, _getAnalyticsBuilder2).call(this).setElement(crm_integration_analytics.Dictionary.ELEMENT_CREATE_BUTTON);
	      ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ATTEMPT).buildData());
	      this.type.save().then(function (response) {
	        ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_SUCCESS).setId(_this5.type.getId()).buildData());
	        babelHelpers.classPrivateFieldSet(_this5, _isCancelEventRegistered, true);
	        _this5.stopProgress();
	        _this5.afterSave(response);
	        _this5.isNew = false;
	      })["catch"](function (errors) {
	        ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ERROR).setId(_this5.type.getId()).buildData());
	        _this5.showErrors(errors);
	        _this5.stopProgress();
	      });
	    }
	  }, {
	    key: "collectEntityTypeIds",
	    value: function collectEntityTypeIds(role) {
	      var entityTypeIds = [];
	      var checkboxes = this.container.querySelectorAll("[data-role=\"".concat(role, "\"]"));
	      babelHelpers.toConsumableArray(checkboxes).forEach(function (checkbox) {
	        if (checkbox.checked) {
	          entityTypeIds.push(checkbox.dataset.entityTypeId);
	        }
	      });
	      return entityTypeIds;
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(response) {
	      var _this$getSlider;
	      this.addDataToSlider('response', response);
	      if (Object.hasOwn(response.data, 'urlTemplates')) {
	        crm_router.Router.Instance.setUrlTemplates(response.data.urlTemplates);
	      }
	      (_this$getSlider = this.getSlider()) === null || _this$getSlider === void 0 ? void 0 : _this$getSlider.close();
	      this.emitTypeUpdatedEvent({
	        isUrlChanged: response.data.isUrlChanged === true
	      });
	    }
	  }, {
	    key: "getSlider",
	    value: function getSlider() {
	      var _BX$SidePanel, _BX$SidePanel$Instanc;
	      return (_BX$SidePanel = BX.SidePanel) === null || _BX$SidePanel === void 0 ? void 0 : (_BX$SidePanel$Instanc = _BX$SidePanel.Instance) === null || _BX$SidePanel$Instanc === void 0 ? void 0 : _BX$SidePanel$Instanc.getSliderByWindow(window);
	    }
	  }, {
	    key: "emitTypeUpdatedEvent",
	    value: function emitTypeUpdatedEvent(data) {
	      crm_toolbarComponent.ToolbarComponent.Instance.emitTypeUpdatedEvent(data);
	    }
	  }, {
	    key: "addDataToSlider",
	    value: function addDataToSlider(key, data) {
	      if (main_core.Type.isString(key) && main_core.Type.isPlainObject(data)) {
	        var slider = this.getSlider();
	        if (slider) {
	          slider.data.set(key, data);
	        }
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var text = '';
	      errors.forEach(function (message) {
	        text += message;
	      });
	      if (main_core.Type.isDomNode(this.errorsContainer)) {
	        this.errorsContainer.innerText = text;
	        main_core.Dom.style(this.errorsContainer.parentNode, 'display', 'block');
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isDomNode(this.errorsContainer)) {
	        main_core.Dom.style(this.errorsContainer.parentNode, 'display', 'none');
	        this.errorsContainer.innerText = '';
	      }
	    }
	  }, {
	    key: "delete",
	    value: function _delete(event) {
	      var _this6 = this;
	      event.preventDefault();
	      if (!this.form) {
	        return;
	      }
	      if (this.isProgress) {
	        return;
	      }
	      if (!this.type) {
	        return;
	      }
	      var currentUrl = new main_core.Uri(decodeURI(window.location.href));
	      var analyticsBuilder = new crm_integration_analytics.Builder.Automation.Type.DeleteEvent().setElement(crm_integration_analytics.Dictionary.ELEMENT_DELETE_BUTTON).setIsExternal(this.isExternal).setSubSection(currentUrl.getQueryParam('c_sub_section')).setId(this.type.getId());
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('CRM_TYPE_DETAIL_DELETE_CONFIRM'), function () {
	        return new Promise(function (resolve) {
	          ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ATTEMPT).buildData());
	          _this6.startProgress();
	          _this6.type["delete"]().then(function (response) {
	            ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_SUCCESS).buildData());
	            babelHelpers.classPrivateFieldSet(_this6, _isCancelEventRegistered, true);
	            _this6.stopProgress();
	            var isUrlChanged = main_core.Type.isObject(response.data) && response.data.isUrlChanged === true;
	            _this6.emitTypeUpdatedEvent({
	              isUrlChanged: isUrlChanged
	            });
	            crm_router.Router.Instance.closeSliderOrRedirect(crm_router.Router.Instance.getTypeListUrl());
	          })["catch"](function (errors) {
	            ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ERROR).buildData());
	            _this6.showErrors(errors);
	            _this6.stopProgress();
	            resolve();
	          });
	        });
	      }, null, function (box) {
	        ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	        _this6.stopProgress();
	        box.close();
	      });
	    }
	  }, {
	    key: "fillTabs",
	    value: function fillTabs() {
	      var _this7 = this;
	      if (this.container) {
	        this.container.querySelectorAll('.crm-type-tab').forEach(function (tabNode) {
	          if (tabNode.dataset.tab) {
	            _this7.tabs.set(tabNode.dataset.tab, tabNode);
	          }
	        });
	      }
	    }
	  }, {
	    key: "showTab",
	    value: function showTab(tabNameToShow) {
	      var _this8 = this;
	      babelHelpers.toConsumableArray(this.tabs.keys()).forEach(function (tabName) {
	        if (tabName === tabNameToShow) {
	          main_core.Dom.addClass(_this8.tabs.get(tabName), 'crm-type-tab-current');
	        } else {
	          main_core.Dom.removeClass(_this8.tabs.get(tabName), 'crm-type-tab-current');
	        }
	      });
	    }
	  }, {
	    key: "applyPreset",
	    value: function applyPreset(presetId) {
	      var _this9 = this;
	      this.disablePresetsView();
	      var presetSelector = document.querySelector('[data-role="crm-type-preset-selector"]');
	      var currentPresetNode = this.container.querySelector('[data-role="preset"].crm-type-preset-active');
	      if (currentPresetNode) {
	        var currentPreset = this.getPresetById(currentPresetNode.dataset.presetId);
	        if (currentPreset && currentPreset.data.title && this.form.querySelector('[name="title"]').value === currentPreset.data.title) {
	          this.form.querySelector('[name="title"]').value = '';
	        }
	      }
	      var presets = this.container.querySelectorAll('[data-role="preset"]');
	      presets.forEach(function (presetNode) {
	        main_core.Dom.removeClass(presetNode, 'crm-type-preset-active');
	        if (presetNode.dataset.presetId === presetId) {
	          main_core.Dom.addClass(presetNode, 'crm-type-preset-active');
	          var preset = _this9.getPresetById(presetId);
	          if (preset) {
	            _this9.updateInputs(preset.data);
	            if (presetSelector) {
	              presetSelector.textContent = main_core.Text.encode(preset.fields.title);
	            }
	          }
	        }
	      });
	      this.selectedPresetId = presetId;
	    }
	  }, {
	    key: "getPresetById",
	    value: function getPresetById(presetId) {
	      var _iterator = _createForOfIteratorHelper(this.presets),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var preset = _step.value;
	          if (preset.fields.id === presetId) {
	            return preset;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return null;
	    }
	  }, {
	    key: "updateInputs",
	    value: function updateInputs(data) {
	      var _this10 = this;
	      if (this.form.querySelector('[name="title"]').value.length <= 0) {
	        this.form.querySelector('[name="title"]').value = data.title || '';
	      }
	      crm_typeModel.TypeModel.getBooleanFieldNames().forEach(function (fieldName) {
	        var node = _this10.getBooleanFieldNodeByName(fieldName);
	        if (node) {
	          _this10.setBooleanFieldCheckedState(node, data[fieldName]);
	        }
	      });
	      this.disableLinkedUserFieldsIfNotAvailable();
	    }
	  }, {
	    key: "toggleBooleanField",
	    value: function toggleBooleanField(fieldName) {
	      var node = this.getBooleanFieldNodeByName(fieldName);
	      if (!node) {
	        return;
	      }
	      if (node.nodeName === 'INPUT') {
	        node.checked = !node.checked;
	      } else {
	        main_core.Dom.toggleClass(node, 'crm-type-field-button-item-active');
	      }
	    }
	  }, {
	    key: "getBooleanFieldNodeByName",
	    value: function getBooleanFieldNodeByName(fieldName) {
	      return this.container.querySelector("[data-name=\"".concat(fieldName, "\"]"));
	    }
	  }, {
	    key: "isBooleanFieldChecked",
	    value: function isBooleanFieldChecked(node) {
	      if (node.nodeName === 'INPUT') {
	        return node.checked;
	      }
	      return main_core.Dom.hasClass(node, 'crm-type-field-button-item-active');
	    }
	  }, {
	    key: "setBooleanFieldCheckedState",
	    value: function setBooleanFieldCheckedState(node, isChecked) {
	      if (node.nodeName === 'INPUT') {
	        // eslint-disable-next-line no-param-reassign
	        node.checked = isChecked;
	        return;
	      }
	      if (isChecked) {
	        main_core.Dom.addClass(node, 'crm-type-field-button-item-active');
	      } else {
	        main_core.Dom.removeClass(node, 'crm-type-field-button-item-active');
	      }
	    }
	  }, {
	    key: "initRelations",
	    value: function initRelations() {
	      this.parentRelationsController = new RelationsController({
	        switcher: BX.UI.Switcher.getById('crm-type-relation-parent-switcher'),
	        container: this.container.querySelector('[data-role="crm-type-relation-parent-items"]'),
	        typeSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-selector"]'),
	        tabsContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-tabs"]'),
	        tabsCheckbox: this.container.querySelector('[data-name="isRelationParentShowChildrenEnabled"]'),
	        tabsSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-tabs-selector"]'),
	        relations: this.relations.parent
	      });
	      this.childRelationsController = new RelationsController({
	        switcher: BX.UI.Switcher.getById('crm-type-relation-child-switcher'),
	        container: this.container.querySelector('[data-role="crm-type-relation-child-items"]'),
	        typeSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-selector"]'),
	        tabsContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-tabs"]'),
	        tabsCheckbox: this.container.querySelector('[data-name="isRelationChildShowChildrenEnabled"]'),
	        tabsSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-tabs-selector"]'),
	        relations: this.relations.child
	      });
	    }
	  }, {
	    key: "initCustomSections",
	    value: function initCustomSections() {
	      this.customSectionController = new CustomSectionsController({
	        switcher: BX.UI.Switcher.getById('crm-type-custom-section-switcher'),
	        container: this.container.querySelector('[data-role="crm-type-custom-section-container"]'),
	        selectorContainer: this.container.querySelector('[data-role="crm-type-custom-section-selector"]'),
	        customSections: this.type.getCustomSections() || [],
	        isCreateSectionsViaAutomatedSolutionDetails: this.isCreateSectionsViaAutomatedSolutionDetails,
	        canEditAutomatedSolution: this.canEditAutomatedSolution,
	        isNew: this.isNew,
	        permissionsUrl: this.permissionsUrl
	      });
	    }
	  }], [{
	    key: "handleLeftMenuClick",
	    value: function handleLeftMenuClick(tabName) {
	      if (instance) {
	        instance.showTab(tabName);
	      }
	    }
	  }, {
	    key: "handlePresetClick",
	    value: function handlePresetClick(presetId) {
	      if (instance) {
	        instance.applyPreset(presetId);
	      }
	    }
	  }, {
	    key: "handleHideDescriptionClick",
	    value: function handleHideDescriptionClick(target) {
	      main_core.Dom.style(target.parentNode, 'display', 'none');
	    }
	  }, {
	    key: "handleBooleanFieldClick",
	    value: function handleBooleanFieldClick(fieldName) {
	      var _instance2;
	      (_instance2 = instance) === null || _instance2 === void 0 ? void 0 : _instance2.toggleBooleanField(fieldName);
	    }
	  }, {
	    key: "handlePresetSelectorClick",
	    value: function handlePresetSelectorClick() {
	      var _instance3;
	      (_instance3 = instance) === null || _instance3 === void 0 ? void 0 : _instance3.enablePresetsView();
	    }
	  }, {
	    key: "handleCancelButtonClick",
	    value: function handleCancelButtonClick() {
	      var _instance;
	      // if we just add click event handler to cancel button node, that handler will be called after slider close
	      // to capture click before that, we need to add handler directly to markup
	      (_instance = instance) === null || _instance === void 0 ? void 0 : _classPrivateMethodGet(_instance, _registerCancelEvent, _registerCancelEvent2).call(_instance, crm_integration_analytics.Dictionary.ELEMENT_CANCEL_BUTTON);
	    }
	  }]);
	  return TypeDetail;
	}();
	function _registerCancelEvent2(element) {
	  if (babelHelpers.classPrivateFieldGet(this, _isCancelEventRegistered)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _isCancelEventRegistered, true);
	  ui_analytics.sendData(_classPrivateMethodGet(this, _getAnalyticsBuilder, _getAnalyticsBuilder2).call(this).setElement(element).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	}
	function _findActiveTabButton2() {
	  return document.querySelector('.ui-sidepanel-menu-item.ui-sidepanel-menu-active > [data-role^=tab-]');
	}
	function _findFirstTabButton2() {
	  return document.querySelector('.ui-sidepanel-menu-item > [data-role^=tab-]');
	}
	function _getAnalyticsBuilder2() {
	  var builder = this.isNew ? new crm_integration_analytics.Builder.Automation.Type.CreateEvent() : new crm_integration_analytics.Builder.Automation.Type.EditEvent();
	  builder.setIsExternal(this.isExternal);
	  if (main_core.Type.isStringFilled(this.selectedPresetId)) {
	    builder.setPreset(this.selectedPresetId);
	  }
	  if (this.type.getId() > 0) {
	    builder.setId(this.type.getId());
	  }
	  var currentUrl = new main_core.Uri(decodeURI(window.location.href));
	  if (currentUrl.getQueryParam('c_sub_section') && builder instanceof crm_integration_analytics.Builder.Automation.Type.EditEvent) {
	    builder.setSubSection(currentUrl.getQueryParam('c_sub_section'));
	  }
	  return builder;
	}
	namespace.TypeDetail = TypeDetail;
	var RelationsController = /*#__PURE__*/function () {
	  function RelationsController(options) {
	    babelHelpers.classCallCheck(this, RelationsController);
	    this.switcher = options.switcher;
	    this.container = options.container;
	    this.typeSelectorContainer = options.typeSelectorContainer;
	    this.tabsContainer = options.tabsContainer;
	    this.tabsCheckbox = options.tabsCheckbox;
	    this.tabsSelectorContainer = options.tabsSelectorContainer;
	    this.relations = options.relations;
	    this.initSelectors();
	    this.adjustInitialState();
	    this.bindEvents();
	    this.adjust();
	  }
	  babelHelpers.createClass(RelationsController, [{
	    key: "initSelectors",
	    value: function initSelectors() {
	      var unselectedTypes = [];
	      var selectedTypes = [];
	      var unselectedTabs = [];
	      var selectedTabs = [];
	      this.relations.forEach(function (relation) {
	        var item = {
	          id: relation.entityTypeId,
	          entityId: 'crmType',
	          title: relation.title,
	          tabs: 'recents'
	        };
	        if (relation.isChecked) {
	          selectedTypes.push(item);
	          if (relation.isChildrenListEnabled) {
	            selectedTabs.push(item);
	          } else {
	            unselectedTabs.push(item);
	          }
	        } else {
	          unselectedTypes.push(item);
	        }
	      });
	      this.typeSelector = new ui_entitySelector.TagSelector({
	        dialogOptions: {
	          enableSearch: false,
	          multiple: false,
	          items: unselectedTypes,
	          selectedItems: selectedTypes,
	          dropdownMode: true,
	          height: 200,
	          showAvatars: false
	        },
	        events: {
	          onAfterTagAdd: this.adjust.bind(this),
	          onAfterTagRemove: this.adjust.bind(this)
	        }
	      });
	      this.typeSelector.renderTo(this.typeSelectorContainer);
	      this.tabsSelector = new ui_entitySelector.TagSelector({
	        dialogOptions: {
	          enableSearch: false,
	          multiple: false,
	          items: unselectedTabs,
	          selectedItems: selectedTabs,
	          dropdownMode: true,
	          height: 200,
	          showAvatars: false
	        }
	      });
	      this.tabsSelector.renderTo(this.tabsSelectorContainer);
	    }
	  }, {
	    key: "adjustInitialState",
	    value: function adjustInitialState() {
	      var selectedTypes = this.typeSelector.getDialog().getSelectedItems();
	      if (selectedTypes.length > 0) {
	        this.switcher.check(true);
	      }
	      this.tabsCheckbox.checked = this.tabsSelector.getDialog().getSelectedItems().length > 0;
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe(this.switcher, 'toggled', this.adjust.bind(this));
	      main_core.Event.bind(this.tabsCheckbox, 'click', this.adjust.bind(this));
	    }
	  }, {
	    key: "adjust",
	    value: function adjust() {
	      var _this11 = this;
	      if (this.switcher.isChecked()) {
	        main_core.Dom.removeClass(this.container, 'crm-type-hidden');
	      } else {
	        main_core.Dom.addClass(this.container, 'crm-type-hidden');
	      }
	      var selectedTypes = this.typeSelector.getDialog().getSelectedItems();
	      if (selectedTypes.length > 0) {
	        main_core.Dom.removeClass(this.tabsContainer, 'crm-type-hidden');
	      } else {
	        main_core.Dom.addClass(this.tabsContainer, 'crm-type-hidden');
	      }
	      if (this.tabsCheckbox.checked) {
	        main_core.Dom.removeClass(this.tabsSelectorContainer, 'crm-type-hidden');
	      } else {
	        main_core.Dom.addClass(this.tabsSelectorContainer, 'crm-type-hidden');
	      }
	      this.tabsSelector.getDialog().getItems().forEach(function (item) {
	        if (!_this11.isItemSelected(item, selectedTypes)) {
	          item.deselect();
	          _this11.tabsSelector.getDialog().removeItem(item);
	          _this11.tabsSelector.removeTag({
	            id: item.getId(),
	            entityId: item.getEntityId()
	          });
	        }
	      });
	      selectedTypes.forEach(function (item) {
	        var itemData = {
	          id: item.getId(),
	          entityId: item.getEntityId(),
	          title: item.getTitle(),
	          tabs: 'recents'
	        };
	        var tabItem = _this11.tabsSelector.getDialog().getItem(itemData);
	        if (!tabItem) {
	          var newItem = _this11.tabsSelector.getDialog().addItem(itemData);
	          newItem.select();
	        }
	      });
	    }
	  }, {
	    key: "isItemSelected",
	    value: function isItemSelected(item, selectedItems) {
	      return selectedItems.some(function (selectedItem) {
	        return item.id === selectedItem.id;
	      });
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      var _this12 = this;
	      var data = [];
	      if (!this.switcher.isChecked()) {
	        return [];
	      }
	      var isTabsCheckboxChecked = this.tabsCheckbox.checked;
	      var selectedTypes = this.typeSelector.getDialog().getSelectedItems();
	      selectedTypes.forEach(function (selectedType) {
	        var type = {
	          entityTypeId: selectedType.getId(),
	          isChildrenListEnabled: false
	        };
	        if (isTabsCheckboxChecked && _this12.isItemSelected(selectedType, _this12.tabsSelector.getDialog().getSelectedItems())) {
	          type.isChildrenListEnabled = true;
	        }
	        data.push(type);
	      });
	      return data;
	    }
	  }]);
	  return RelationsController;
	}();
	var CustomSectionsController = /*#__PURE__*/function () {
	  function CustomSectionsController(options) {
	    var _this$customSections$;
	    babelHelpers.classCallCheck(this, CustomSectionsController);
	    babelHelpers.defineProperty(this, "permissionsResetAlert", null);
	    babelHelpers.defineProperty(this, "isPermissionsResetAlertShown", false);
	    babelHelpers.defineProperty(this, "permissionsUrl", null);
	    this.switcher = options.switcher;
	    this.container = options.container;
	    this.selectorContainer = options.selectorContainer;
	    if (main_core.Type.isArray(options.customSections)) {
	      this.customSections = options.customSections;
	    } else {
	      this.customSections = [];
	    }
	    this.originallySelectedCustomSection = (_this$customSections$ = this.customSections.find(function (section) {
	      return section.isSelected;
	    })) !== null && _this$customSections$ !== void 0 ? _this$customSections$ : null;
	    this.isNew = main_core.Type.isBoolean(options.isNew) ? options.isNew : false;
	    if (main_core.Type.isBoolean(options.isCreateSectionsViaAutomatedSolutionDetails)) {
	      this.isCreateSectionsViaAutomatedSolutionDetails = options.isCreateSectionsViaAutomatedSolutionDetails;
	    }
	    if (main_core.Type.isBoolean(options.canEditAutomatedSolution)) {
	      this.canEditAutomatedSolution = options.canEditAutomatedSolution;
	    }
	    if (main_core.Type.isStringFilled(options.permissionsUrl)) {
	      this.permissionsUrl = options.permissionsUrl;
	    }
	    this.initSelector();
	    this.settingsContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-type-hidden crm-type-custom-sections-settings-container\">\n\t\t\t\t<div class=\"crm-type-relation-subtitle\">", "</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_TYPE_DETAIL_CUSTOM_SECTION_LIST_MSGVER_1'));
	    this.container.append(this.settingsContainer);
	    this.adjustInitialState();
	    this.bindEvents();
	    this.adjust();
	  }
	  babelHelpers.createClass(CustomSectionsController, [{
	    key: "destroy",
	    value: function destroy() {
	      var _this$selector;
	      this.unbindEvents();
	      (_this$selector = this.selector) === null || _this$selector === void 0 ? void 0 : _this$selector.unsubscribeAll();
	    }
	  }, {
	    key: "initSelector",
	    value: function initSelector() {
	      var items = [];
	      var selectedItems = [];
	      this.customSections.forEach(function (section) {
	        var item = {
	          id: section.id,
	          entityId: 'custom-section',
	          title: section.title,
	          tabs: 'recents'
	        };
	        items.push(item);
	        if (section.isSelected) {
	          selectedItems.push(item);
	        }
	      });
	      var adjustResetPermissionsAlertDebounce = main_core.Runtime.debounce(this.adjustResetPermissionsAlert.bind(this), 200);
	      var tagSelectorOptions = {
	        multiple: false,
	        dialogOptions: {
	          items: items,
	          selectedItems: selectedItems,
	          dropdownMode: true,
	          height: 200,
	          showAvatars: false
	        },
	        events: {
	          onAfterTagRemove: adjustResetPermissionsAlertDebounce,
	          onAfterTagAdd: adjustResetPermissionsAlertDebounce
	        }
	      };
	      if (this.isCreateSectionsViaAutomatedSolutionDetails) {
	        tagSelectorOptions.showCreateButton = false;
	        tagSelectorOptions.dialogOptions.footer = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\tclass=\"ui-selector-footer-link ui-selector-footer-link-add\"\n\t\t\t\t>", "</a>\n\t\t\t"])), this.onOpenAutomatedSolutionCreationSliderClick.bind(this), main_core.Loc.getMessage('CRM_COMMON_ACTION_CREATE'));
	      } else {
	        tagSelectorOptions.showCreateButton = true;
	        tagSelectorOptions.createButtonCaption = main_core.Loc.getMessage('CRM_COMMON_ACTION_CONFIG');
	        tagSelectorOptions.events = {
	          onCreateButtonClick: this.onCreateButtonClick.bind(this)
	        };
	      }
	      if (!this.canEditAutomatedSolution) {
	        tagSelectorOptions.locked = true;
	      }
	      this.selector = new ui_entitySelector.TagSelector(tagSelectorOptions);
	      this.selector.renderTo(this.selectorContainer);
	    }
	  }, {
	    key: "adjustResetPermissionsAlert",
	    value: function adjustResetPermissionsAlert() {
	      var _selectedItems$, _this$originallySelec;
	      if (!FeatureManager.getInstance().isPermissionsLayoutV2Enabled() || this.permissionsUrl === null || this.isNew) {
	        return;
	      }
	      var selectedItems = this.selector.getDialog().getSelectedItems();
	      var item = (_selectedItems$ = selectedItems[0]) !== null && _selectedItems$ !== void 0 ? _selectedItems$ : null;
	      var alert = this.getPermissionsResetAlert();
	      var isItemChanged = (item === null || item === void 0 ? void 0 : item.getId()) !== ((_this$originallySelec = this.originallySelectedCustomSection) === null || _this$originallySelec === void 0 ? void 0 : _this$originallySelec.id) && this.switcher.isChecked();
	      var isDetachCustomSection = this.originallySelectedCustomSection && !this.switcher.isChecked();
	      var isShow = isItemChanged || isDetachCustomSection;
	      if (isShow) {
	        if (!this.isPermissionsResetAlertShown) {
	          this.isPermissionsResetAlertShown = true;
	          alert.renderTo(this.container.parentNode);
	          alert.show();
	        }
	        return;
	      }
	      if (this.isPermissionsResetAlertShown) {
	        this.isPermissionsResetAlertShown = false;
	        alert.hide();
	      }
	    }
	  }, {
	    key: "getPermissionsResetAlert",
	    value: function getPermissionsResetAlert() {
	      if (this.permissionsResetAlert === null) {
	        var text = main_core.Loc.getMessage('CRM_TYPE_DETAIL_PERMISSIONS_WILL_BE_RESET_ALERT').replace('#LINK#', this.permissionsUrl);
	        this.permissionsResetAlert = new ui_alerts.Alert({
	          size: ui_alerts.AlertSize.MD,
	          color: ui_alerts.AlertColor.WARNING,
	          customClass: 'crm-type-permissions-reset-alert',
	          text: text
	        });
	      }
	      return this.permissionsResetAlert;
	    }
	  }, {
	    key: "reInitSelector",
	    value: function reInitSelector() {
	      this.selector.getDialog().destroy();
	      this.selector.unsubscribeAll();
	      this.selector = null;
	      main_core.Dom.clean(this.selectorContainer);
	      this.initSelector();
	    }
	  }, {
	    key: "showSelector",
	    value: function showSelector() {
	      main_core.Dom.removeClass(this.selectorContainer, 'crm-type-hidden');
	    }
	  }, {
	    key: "hideSelector",
	    value: function hideSelector() {
	      main_core.Dom.addClass(this.selectorContainer, 'crm-type-hidden');
	    }
	  }, {
	    key: "adjustInitialState",
	    value: function adjustInitialState() {
	      var selectedSection = this.selector.getDialog().getSelectedItems();
	      if (selectedSection.length > 0) {
	        this.switcher.check(true);
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      this.adjust = this.adjust.bind(this);
	      this.onAutomatedSolutionUpdate = this.onAutomatedSolutionUpdate.bind(this);
	      main_core_events.EventEmitter.subscribe(this.switcher, 'toggled', this.adjust);
	      main_core_events.EventEmitter.subscribe(this.switcher, 'toggled', this.adjustResetPermissionsAlert.bind(this));
	      if (this.isCreateSectionsViaAutomatedSolutionDetails) {
	        crm_toolbarComponent.ToolbarComponent.Instance.subscribeAutomatedSolutionUpdatedEvent(this.onAutomatedSolutionUpdate);
	      }
	    }
	  }, {
	    key: "unbindEvents",
	    value: function unbindEvents() {
	      main_core_events.EventEmitter.unsubscribe(this.switcher, 'toggled', this.adjust);
	      crm_toolbarComponent.ToolbarComponent.Instance.unsubscribeAutomatedSolutionUpdatedEvent(this.onAutomatedSolutionUpdate);
	    }
	  }, {
	    key: "onAutomatedSolutionUpdate",
	    value: function onAutomatedSolutionUpdate(event) {
	      var id = main_core.Text.toInteger(event.getData().intranetCustomSectionId);
	      var title = String(event.getData().title);
	      if (id <= 0 || !main_core.Type.isStringFilled(title)) {
	        return;
	      }
	      var currentCustomSection = this.customSections.find(function (section) {
	        return main_core.Text.toInteger(section.id) === id;
	      });
	      if (currentCustomSection) {
	        currentCustomSection.title = title;
	      } else {
	        this.customSections.push({
	          id: id,
	          title: title
	        });
	      }
	      this.reInitSelector();
	      this.selectCustomSectionById(id);
	    }
	  }, {
	    key: "selectCustomSectionById",
	    value: function selectCustomSectionById(id) {
	      var _dialog$getItem;
	      var dialog = this.selector.getDialog();
	      dialog.deselectAll();
	      (_dialog$getItem = dialog.getItem({
	        entityId: 'custom-section',
	        id: id
	      })) === null || _dialog$getItem === void 0 ? void 0 : _dialog$getItem.select();
	    }
	  }, {
	    key: "onOpenAutomatedSolutionCreationSliderClick",
	    value: function onOpenAutomatedSolutionCreationSliderClick() {
	      void crm_router.Router.Instance.openAutomatedSolutionDetail();
	    }
	  }, {
	    key: "onCreateButtonClick",
	    value: function onCreateButtonClick() {
	      this.hideSelector();
	      this.showSectionsList();
	    }
	  }, {
	    key: "renderSectionsConfig",
	    value: function renderSectionsConfig() {
	      var _this13 = this;
	      if (!this.sectionsListContainer) {
	        this.sectionsListContainer = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-custom-sections-list-container\"></div>"])));
	        this.settingsContainer.append(this.sectionsListContainer);
	      }
	      this.renderSectionsList(this.sectionsListContainer);
	      if (!this.addSectionItemButton) {
	        this.addSectionItemButton = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-type-custom-section-add-item-container\">\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"crm-type-custom-section-add-item-button\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t"])), function () {
	          return _this13.sectionsListContainer.append(_this13.renderSectionItem());
	        }, main_core.Loc.getMessage('CRM_COMMON_ACTION_CREATE'));
	        this.settingsContainer.append(this.addSectionItemButton);
	      }
	      if (!this.buttonsContainer) {
	        this.settingsContainer.append(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<hr class=\"crm-type-custom-sections-line\">"]))));
	        this.buttonsContainer = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-custom-sections-buttons-container\"></div>"])));
	        this.settingsContainer.append(this.buttonsContainer);
	      }
	      if (!this.saveButton) {
	        this.saveButton = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-btn ui-btn-primary\" onclick=\"", "\">", "</span>"])), this.onSaveConfigHandler.bind(this), main_core.Loc.getMessage('CRM_COMMON_ACTION_SAVE'));
	        this.buttonsContainer.append(this.saveButton);
	      }
	      if (!this.cancelButton) {
	        this.cancelButton = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-btn ui-btn-light-border\" onclick=\"", "\">", "</span>"])), this.onCancelConfigHandler.bind(this), main_core.Loc.getMessage('CRM_COMMON_ACTION_CANCEL'));
	        this.buttonsContainer.append(this.cancelButton);
	      }
	    }
	  }, {
	    key: "onSaveConfigHandler",
	    value: function onSaveConfigHandler(event) {
	      event.preventDefault();
	      var selectedSection = this.getSelectedSection();
	      var newCustomSections = [];
	      babelHelpers.toConsumableArray(this.sectionsListContainer.children).forEach(function (node) {
	        var idInput = node.querySelector('[name="id"]');
	        var valueInput = node.querySelector('[name="value"]');
	        if (!idInput || !valueInput) {
	          return;
	        }
	        var id = idInput.value;
	        var title = valueInput.value;
	        var isSelected = false;
	        if (selectedSection && selectedSection.id === id) {
	          isSelected = true;
	        }
	        if (title) {
	          newCustomSections.push({
	            id: id,
	            title: title,
	            isSelected: isSelected
	          });
	        }
	      });
	      this.customSections = newCustomSections;
	      this.reInitSelector();
	      this.showSelector();
	      this.hideSectionsList();
	    }
	  }, {
	    key: "onCancelConfigHandler",
	    value: function onCancelConfigHandler(event) {
	      event.preventDefault();
	      this.showSelector();
	      this.hideSectionsList();
	    }
	  }, {
	    key: "renderSectionsList",
	    value: function renderSectionsList(listContainer) {
	      var _this14 = this;
	      main_core.Dom.clean(listContainer);
	      this.customSections.forEach(function (section) {
	        listContainer.append(_this14.renderSectionItem(section));
	      });
	      listContainer.append(this.renderSectionItem());
	    }
	  }, {
	    key: "renderSectionItem",
	    value: function renderSectionItem(section) {
	      var _this15 = this;
	      var item = new CustomSectionItem(section);
	      var node = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div style=\"margin-bottom: 10px;\" class=\"ui-ctl ui-ctl-textbox ui-ctl-w100 ui-ctl-row\">\n\t\t\t\t<input type=\"hidden\" name=\"id\" value=\"", "\" />\n\t\t\t\t<input class=\"ui-ctl-element\" name=\"value\" type=\"text\" value=\"", "\">\n\t\t\t\t<div\n\t\t\t\t\tclass=\"crm-type-custom-section-remove-item\"\n\t\t\t\t\tonclick=\"", "\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), item.getId(), main_core.Text.encode(item.getValue()), function (event) {
	        event.preventDefault();
	        _this15.sectionsListContainer.removeChild(item.getNode());
	      });
	      item.setNode(node);
	      return node;
	    }
	  }, {
	    key: "showSectionsList",
	    value: function showSectionsList() {
	      this.renderSectionsConfig();
	      main_core.Dom.removeClass(this.settingsContainer, 'crm-type-hidden');
	    }
	  }, {
	    key: "hideSectionsList",
	    value: function hideSectionsList() {
	      main_core.Dom.clean(this.sectionsListContainer);
	      main_core.Dom.addClass(this.settingsContainer, 'crm-type-hidden');
	    }
	  }, {
	    key: "adjust",
	    value: function adjust() {
	      if (this.switcher.isChecked()) {
	        main_core.Dom.removeClass(this.container, 'crm-type-hidden');
	      } else {
	        main_core.Dom.addClass(this.container, 'crm-type-hidden');
	      }
	    }
	  }, {
	    key: "getSelectedSection",
	    value: function getSelectedSection() {
	      var selectedItems = this.selector.getDialog().getSelectedItems();
	      if (selectedItems.length > 0) {
	        return {
	          id: selectedItems[0].getId(),
	          title: selectedItems[0].getTitle()
	        };
	      }
	      return null;
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = {
	        customSectionId: 0,
	        customSections: this.customSections
	      };
	      if (this.switcher.isChecked()) {
	        var selectedSection = this.getSelectedSection();
	        if (selectedSection) {
	          data.customSectionId = selectedSection.id;
	        }
	      }
	      return data;
	    }
	  }]);
	  return CustomSectionsController;
	}();
	var CustomSectionItem = /*#__PURE__*/function () {
	  function CustomSectionItem() {
	    var customSection = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	    babelHelpers.classCallCheck(this, CustomSectionItem);
	    this.id = customSection ? customSection.id : "new_".concat(main_core.Text.getRandom());
	    this.value = customSection ? customSection.title : '';
	  }
	  babelHelpers.createClass(CustomSectionItem, [{
	    key: "setNode",
	    value: function setNode(node) {
	      this.node = node;
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getInput",
	    value: function getInput() {
	      var node = this.getNode();
	      if (!node) {
	        return null;
	      }
	      if (node instanceof HTMLInputElement) {
	        return node;
	      }
	      return node.querySelector('input');
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      var input = this.getInput();
	      if (input && input.value) {
	        return input.value;
	      }
	      return this.value || '';
	    }
	  }]);
	  return CustomSectionItem;
	}();
	var _isPermissionsLayoutV2Enabled = /*#__PURE__*/new WeakMap();
	var FeatureManager = /*#__PURE__*/function () {
	  function FeatureManager() {
	    babelHelpers.classCallCheck(this, FeatureManager);
	    _classPrivateFieldInitSpec(this, _isPermissionsLayoutV2Enabled, {
	      writable: true,
	      value: false
	    });
	  }
	  babelHelpers.createClass(FeatureManager, [{
	    key: "setPermissionsLayoutV2Enabled",
	    value: function setPermissionsLayoutV2Enabled(isEnabled) {
	      babelHelpers.classPrivateFieldSet(this, _isPermissionsLayoutV2Enabled, isEnabled);
	      return this;
	    }
	  }, {
	    key: "isPermissionsLayoutV2Enabled",
	    value: function isPermissionsLayoutV2Enabled() {
	      return babelHelpers.classPrivateFieldGet(this, _isPermissionsLayoutV2Enabled);
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (_classStaticPrivateFieldSpecGet(this, FeatureManager, _instance4) === null) {
	        _classStaticPrivateFieldSpecSet(this, FeatureManager, _instance4, new FeatureManager());
	      }
	      return _classStaticPrivateFieldSpecGet(this, FeatureManager, _instance4);
	    }
	  }]);
	  return FeatureManager;
	}();
	var _instance4 = {
	  writable: true,
	  value: null
	};
	namespace.FeatureManager = FeatureManager;

}((this.window = this.window || {}),BX.Crm.Integration.Analytics,BX.Crm,BX.Crm,BX.Crm.Models,BX,BX.Event,BX,BX.UI.Analytics,BX.UI.Dialogs,BX.UI.EntitySelector,BX.UI));
//# sourceMappingURL=script.js.map
