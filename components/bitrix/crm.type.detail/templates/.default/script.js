(function (exports,main_core,main_core_events,main_loader,ui_dialogs_messagebox,crm_typeModel,crm_router,ui_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var instance = null;
	/**
	 * @memberOf BX.Crm.Component
	 */

	var TypeDetail = /*#__PURE__*/function () {
	  function TypeDetail(params) {
	    babelHelpers.classCallCheck(this, TypeDetail);
	    babelHelpers.defineProperty(this, "isProgress", false);
	    babelHelpers.defineProperty(this, "tabs", new Map());
	    babelHelpers.defineProperty(this, "isRestricted", false);

	    if (main_core.Type.isPlainObject(params)) {
	      this.type = params.type;
	      this.isNew = !this.type.isSaved();
	      this.form = params.form;
	      this.container = params.container;
	      this.errorsContainer = params.errorsContainer;
	      this.presets = params.presets;
	      this.relations = params.relations;
	      this.isRestricted = Boolean(params.isRestricted);
	    }

	    this.buttonsPanel = document.getElementById('ui-button-panel');
	    this.saveButton = document.getElementById('ui-button-panel-save');
	    this.cancelButton = document.getElementById('ui-button-panel-cancel');
	    this.deleteButton = document.getElementById('ui-button-panel-remove');
	    instance = this;
	  }

	  babelHelpers.createClass(TypeDetail, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	      this.fillTabs();

	      if (!this.type.getId()) {
	        this.enablePresetsView();
	      } else {
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
	      main_core.Dom.removeClass(document.querySelector('body'), 'crm-type-settings-presets');
	      var commonTab = document.querySelector('[data-role="tab-common"]');

	      if (commonTab) {
	        commonTab.click();
	      }

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
	        crm_router.Router.Instance.showFeatureSlider();
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
	      }); // this.type.setConversionMap({
	      // 	sourceTypes: this.collectEntityTypeIds('conversion-source'),
	      // 	destinationTypes: this.collectEntityTypeIds('conversion-destination'),
	      // });

	      var linkedUserFields = {};
	      this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach(function (linkedUserFieldNode) {
	        var name = linkedUserFieldNode.dataset.name.substr('linkedUserFields['.length).replace(']', '');
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
	        this.type.setCustomSections(customSectionData.customSecions);
	      }

	      this.type.save().then(function (response) {
	        _this5.stopProgress();

	        _this5.afterSave(response);

	        _this5.isNew = false;
	      })["catch"](function (errors) {
	        _this5.showErrors(errors);

	        _this5.stopProgress();
	      });
	    }
	  }, {
	    key: "collectEntityTypeIds",
	    value: function collectEntityTypeIds(role) {
	      var entityTypeIds = [];
	      var checkboxes = this.container.querySelectorAll("[data-role=\"".concat(role, "\"]"));
	      Array.from(checkboxes).forEach(function (checkbox) {
	        if (checkbox.checked) {
	          entityTypeIds.push(checkbox.dataset.entityTypeId);
	        }
	      });
	      return entityTypeIds;
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(response) {
	      this.addDataToSlider('response', response);

	      if (response.data.hasOwnProperty('urlTemplates')) {
	        crm_router.Router.Instance.setUrlTemplates(response.data.urlTemplates);
	      }

	      var slider = this.getSlider();

	      if (slider) {
	        slider.close();
	      } else if (this.isNew) {
	        location.href = crm_router.Router.Instance.getTypeDetailUrl(this.type.getEntityTypeId());
	      }

	      this.emitTypeUpdatedEvent({
	        isUrlChanged: response.data.isUrlChanged === true
	      });
	    }
	  }, {
	    key: "getSlider",
	    value: function getSlider() {
	      if (main_core.Reflection.getClass('BX.SidePanel')) {
	        return BX.SidePanel.Instance.getSliderByWindow(window);
	      }

	      return null;
	    }
	  }, {
	    key: "getToolbarComponent",
	    value: function getToolbarComponent() {
	      if (main_core.Reflection.getClass('BX.Crm.ToolbarComponent')) {
	        return BX.Crm.ToolbarComponent.Instance;
	      }

	      return null;
	    }
	  }, {
	    key: "emitTypeUpdatedEvent",
	    value: function emitTypeUpdatedEvent(data) {
	      var toolbar = this.getToolbarComponent();

	      if (toolbar) {
	        toolbar.emitTypeUpdatedEvent(data);
	      }
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
	        this.errorsContainer.parentNode.style.display = 'block';
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isDomNode(this.errorsContainer)) {
	        this.errorsContainer.parentNode.style.display = 'none';
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

	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('CRM_TYPE_DETAIL_DELETE_CONFIRM'), function () {
	        return new Promise(function (resolve) {
	          _this6.startProgress();

	          _this6.type["delete"]().then(function (response) {
	            _this6.stopProgress();

	            var isUrlChanged = main_core.Type.isObject(response.data) && response.data.isUrlChanged === true;

	            _this6.emitTypeUpdatedEvent({
	              isUrlChanged: isUrlChanged
	            });

	            var slider = _this6.getSlider();

	            if (slider) {
	              slider.close();
	            } else {
	              var listUrl = crm_router.Router.Instance.getTypeListUrl();

	              if (listUrl) {
	                location.href = listUrl.toString();
	              }
	            }
	          })["catch"](function (errors) {
	            _this6.showErrors(errors);

	            _this6.stopProgress();

	            resolve();
	          });
	        });
	      }, null, function (box) {
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

	      Array.from(this.tabs.keys()).forEach(function (tabName) {
	        if (tabName === tabNameToShow) {
	          _this8.tabs.get(tabName).classList.add('crm-type-tab-current');
	        } else {
	          _this8.tabs.get(tabName).classList.remove('crm-type-tab-current');
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
	      return this.container.querySelector('[data-name="' + fieldName + '"]');
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
	        customSections: this.type.getCustomSections() || []
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
	      target.parentNode.style.display = 'none';
	    }
	  }, {
	    key: "handleBooleanFieldClick",
	    value: function handleBooleanFieldClick(fieldName) {
	      if (instance) {
	        instance.toggleBooleanField(fieldName);
	      }
	    }
	  }, {
	    key: "handlePresetSelectorClick",
	    value: function handlePresetSelectorClick() {
	      if (instance) {
	        instance.enablePresetsView();
	      }
	    }
	  }]);
	  return TypeDetail;
	}();

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

	      if (!this.switcher.isChecked()) {
	        main_core.Dom.addClass(this.container, 'crm-type-hidden');
	      } else {
	        main_core.Dom.removeClass(this.container, 'crm-type-hidden');
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
	      return selectedItems.filter(function (selectedItem) {
	        return item.id === selectedItem.id;
	      }).length > 0;
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
	    babelHelpers.classCallCheck(this, CustomSectionsController);
	    this.switcher = options.switcher;
	    this.container = options.container;
	    this.selectorContainer = options.selectorContainer;

	    if (main_core.Type.isArray(options.customSections)) {
	      this.customSections = options.customSections;
	    } else {
	      this.customSections = [];
	    }

	    this.initSelector();
	    this.settingsContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-hidden crm-type-custom-sections-settings-container\">\n\t\t\t<div class=\"crm-type-relation-subtitle\">", "</div>\n\t\t</div>"])), main_core.Loc.getMessage('CRM_TYPE_DETAIL_CUSTOM_SECTION_LIST'));
	    this.container.append(this.settingsContainer);
	    this.adjustInitialState();
	    this.bindEvents();
	    this.adjust();
	  }

	  babelHelpers.createClass(CustomSectionsController, [{
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
	      this.selector = new ui_entitySelector.TagSelector({
	        showCreateButton: true,
	        createButtonCaption: main_core.Loc.getMessage('CRM_COMMON_ACTION_CONFIG'),
	        multiple: false,
	        dialogOptions: {
	          enableSearch: false,
	          multiple: false,
	          items: items,
	          selectedItems: selectedItems,
	          dropdownMode: true,
	          height: 200,
	          showAvatars: false,
	          recentTabOptions: {
	            stub: false
	          }
	        }
	      });
	      this.selector.subscribe('onCreateButtonClick', this.onCreateButtonClick.bind(this));
	      this.selector.renderTo(this.selectorContainer);
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
	      main_core_events.EventEmitter.subscribe(this.switcher, 'toggled', this.adjust.bind(this));
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
	        this.sectionsListContainer = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-custom-sections-list-container\"></div>"])));
	        this.settingsContainer.append(this.sectionsListContainer);
	      }

	      this.renderSectionsList(this.sectionsListContainer);

	      if (!this.addSectionItemButton) {
	        this.addSectionItemButton = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-custom-section-add-item-container\">\n\t\t\t\t<span class=\"crm-type-custom-section-add-item-button\" onclick=\"", "\">", "</span>\n\t\t\t</div>"])), function () {
	          _this13.sectionsListContainer.append(_this13.renderSectionItem());
	        }, main_core.Loc.getMessage('CRM_COMMON_ACTION_CREATE'));
	        this.settingsContainer.append(this.addSectionItemButton);
	      }

	      if (!this.buttonsContainer) {
	        this.settingsContainer.append(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<hr class=\"crm-type-custom-sections-line\">"]))));
	        this.buttonsContainer = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-type-custom-sections-buttons-container\"></div>"])));
	        this.settingsContainer.append(this.buttonsContainer);
	      }

	      if (!this.saveButton) {
	        this.saveButton = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-btn ui-btn-primary\" onclick=\"", "\">", "</span>"])), this.onSaveConfigHandler.bind(this), main_core.Loc.getMessage('CRM_COMMON_ACTION_SAVE'));
	        this.buttonsContainer.append(this.saveButton);
	      }

	      if (!this.cancelButton) {
	        this.cancelButton = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-btn ui-btn-light-border\" onclick=\"", "\">", "</span>"])), this.onCancelConfigHandler.bind(this), main_core.Loc.getMessage('CRM_COMMON_ACTION_CANCEL'));
	        this.buttonsContainer.append(this.cancelButton);
	      }
	    }
	  }, {
	    key: "onSaveConfigHandler",
	    value: function onSaveConfigHandler(event) {
	      event.preventDefault();
	      var selectedSection = this.getSelectedSection();
	      var newCustomSections = [];
	      Array.from(this.sectionsListContainer.children).forEach(function (node) {
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
	      main_core.Dom.clean(this.selectorContainer);
	      this.initSelector();
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
	      var node = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div style=\"margin-bottom: 10px;\" class=\"ui-ctl ui-ctl-textbox ui-ctl-w100 ui-ctl-row\">\n\t\t\t<input type=\"hidden\" name=\"id\" value=\"", "\" />\n\t\t\t<input class=\"ui-ctl-element\" name=\"value\" type=\"text\" value=\"", "\">\n\t\t\t<div class=\"crm-type-custom-section-remove-item\" onclick=\"", "\"></div>\n\t\t</div>"])), item.getId(), main_core.Text.encode(item.getValue()), function (event) {
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
	      if (!this.switcher.isChecked()) {
	        main_core.Dom.addClass(this.container, 'crm-type-hidden');
	      } else {
	        main_core.Dom.removeClass(this.container, 'crm-type-hidden');
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
	      var data = {};
	      data.customSectionId = 0;

	      if (this.switcher.isChecked()) {
	        var selectedSection = this.getSelectedSection();

	        if (selectedSection) {
	          data.customSectionId = selectedSection.id;
	        }
	      }

	      data.customSecions = this.customSections;
	      return data;
	    }
	  }]);
	  return CustomSectionsController;
	}();

	var CustomSectionItem = /*#__PURE__*/function () {
	  function CustomSectionItem() {
	    var customSection = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	    babelHelpers.classCallCheck(this, CustomSectionItem);
	    this.id = customSection ? customSection.id : 'new_' + main_core.Text.getRandom();
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

}((this.window = this.window || {}),BX,BX.Event,BX,BX.UI.Dialogs,BX.Crm.Models,BX.Crm,BX.UI.EntitySelector));
//# sourceMappingURL=script.js.map
