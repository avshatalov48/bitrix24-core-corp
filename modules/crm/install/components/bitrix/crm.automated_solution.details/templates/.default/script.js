/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.AutomatedSolution = this.BX.Crm.AutomatedSolution || {};
(function (exports,ui_vue3,ui_vue3_vuex,main_core_events,ui_analytics,crm_integration_analytics,crm_router,ui_dialogs_messagebox,ui_entitySelector,crm_toolbarComponent,ui_infoHelper,main_core) {
	'use strict';

	function createSaveAnalyticsBuilder(store) {
	  var builder = store.getters.isNew ? new crm_integration_analytics.Builder.Automation.AutomatedSolution.CreateEvent() : new crm_integration_analytics.Builder.Automation.AutomatedSolution.EditEvent();
	  return builder.setId(store.state.automatedSolution.id).setTypeIds(store.state.automatedSolution.typeIds);
	}
	function wrapPromiseInAnalytics(promise, builder) {
	  ui_analytics.sendData(builder.setStatus(crm_integration_analytics.Dictionary.STATUS_ATTEMPT).buildData());
	  return promise.then(function (thenResult) {
	    ui_analytics.sendData(builder.setStatus(crm_integration_analytics.Dictionary.STATUS_SUCCESS).buildData());
	    return thenResult;
	  })["catch"](function (error) {
	    ui_analytics.sendData(builder.setStatus(crm_integration_analytics.Dictionary.STATUS_ERROR).buildData());
	    throw error;
	  });
	}

	var Card = {
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      isShown: true
	    };
	  },
	  template: "\n\t\t<div v-if=\"isShown\" class=\"crm-type-ui-card crm-type-ui-card-message\">\n\t\t\t<div class=\"crm-type-ui-card-header\">\n\t\t\t\t<div class=\"crm-type-ui-card-message-icon crm-type-ui-card-message-icon--custom-fields\"></div>\n\t\t\t\t<div class=\"crm-type-ui-card-message-title\">{{ title }}</div>\n\t\t\t</div>\n\t\t\t<div class=\"crm-type-ui-card-body\">\n\t\t\t\t<div class=\"crm-type-ui-card-message-description\">{{ description }}</div>\n\t\t\t</div>\n\t\t\t<div \n\t\t\t\tclass=\"crm-type-ui-card-message-close-button\" \n\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_CLOSE_TITLE')\"\n\t\t\t\t@click=\"isShown = false\"\n\t\t\t></div>\n\t\t</div>\n\t"
	};

	var CommonTab = {
	  components: {
	    Card: Card
	  },
	  computed: {
	    title: {
	      get: function get() {
	        return this.$store.state.automatedSolution.title;
	      },
	      set: function set(title) {
	        this.$store.dispatch('setTitle', title);
	      }
	    }
	  },
	  template: "\n\t\t<div data-tab=\"common\">\n\t\t\t<div class=\"ui-title-3\">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_TITLE_COMMON') }}</div>\n\t\t\t<Card\n\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_COMMON_TITLE')\"\n\t\t\t\t:description=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_COMMON_DESCRIPTION')\"\n\t\t\t/>\n\t\t\t<div class=\"ui-form-row crm-automated-solution-details-form-label-xs\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_TITLE') }}</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\tv-model=\"title\"\n\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t:placeholder=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_PLACEHOLDER_TITLE')\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TypesTab = {
	  components: {
	    Card: Card
	  },
	  // tag selector should not be reactive (recreated on store mutations)
	  boundTypesTagSelector: null,
	  externalTypesTagSelector: null,
	  crmTypesTagSelector: null,
	  computed: {
	    isShowPermissionsResetAlert: function isShowPermissionsResetAlert() {
	      if (!this.$store.state.isPermissionsLayoutV2Enabled) {
	        return false;
	      }
	      var currentTypeIds = babelHelpers.toConsumableArray(this.$store.state.automatedSolution.typeIds);
	      var originallyTypeIds = babelHelpers.toConsumableArray(this.$store.state.automatedSolutionOrigTypeIds);
	      return currentTypeIds.some(function (id) {
	        return !originallyTypeIds.includes(id);
	      }) || originallyTypeIds.some(function (id) {
	        return !currentTypeIds.includes(id);
	      });
	    }
	  },
	  mounted: function mounted() {
	    var _this = this;
	    this.boundTypesTagSelector = new ui_entitySelector.TagSelector({
	      multiple: true,
	      showAddButton: false,
	      showCreateButton: true,
	      // all preselected items go to this selector
	      items: this.$store.state.automatedSolution.typeIds.map(function (typeId) {
	        return {
	          id: typeId,
	          entityId: 'dynamic_type',
	          title: _this.$store.state.dynamicTypesTitles[typeId]
	        };
	      }),
	      events: {
	        onCreateButtonClick: this.handleCreateTypeClick,
	        onTagRemove: this.removeTypeIdByTagRemoveEvent
	      }
	    });
	    this.boundTypesTagSelector.renderTo(this.$refs.boundTypesTagSelectorContainer);

	    // these selectors contain types only if there were added here in the app lifetime by user interaction
	    // selector states are not synced reactively in the app lifetime
	    this.crmTypesTagSelector = this.initFilteredTagSelector(true, false, !this.$store.state.permissions.canMoveSmartProcessFromCrm);
	    this.crmTypesTagSelector.renderTo(this.$refs.crmTypesTagSelectorContainer);
	    this.externalTypesTagSelector = this.initFilteredTagSelector(false, true, !this.$store.state.permissions.canMoveSmartProcessFromAnotherAutomatedSolution);
	    this.externalTypesTagSelector.renderTo(this.$refs.externalTypesTagSelectorContainer);
	  },
	  methods: {
	    initFilteredTagSelector: function initFilteredTagSelector(isOnlyCrmTypes, isOnlyExternalTypes, locked) {
	      var tagSlector = new ui_entitySelector.TagSelector({
	        multiple: true,
	        addButtonCaption: this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAG_SELECTOR_ADD_BUTTON_CAPTION'),
	        addButtonCaptionMore: this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAG_SELECTOR_ADD_BUTTON_CAPTION'),
	        dialogOptions: {
	          height: 200,
	          enableSearch: true,
	          context: 'crm.automated_solutions.details',
	          dropdownMode: true,
	          showAvatars: false,
	          entities: [{
	            id: 'dynamic_type',
	            dynamicLoad: true,
	            dynamicSearch: true,
	            options: {
	              showAutomatedSolutionBadge: true,
	              isOnlyExternalTypes: isOnlyExternalTypes,
	              isOnlyCrmTypes: isOnlyCrmTypes
	            }
	          }]
	        },
	        events: {
	          onTagAdd: this.addTypeIdByTagAddEvent,
	          onTagRemove: this.removeTypeIdIfNotContainsInBoundTypes
	        }
	      });
	      if (locked) {
	        tagSlector.setLocked(true);
	      }
	      return tagSlector;
	    },
	    addTypeIdByTagAddEvent: function addTypeIdByTagAddEvent(event) {
	      var _event$getData = event.getData(),
	        tag = _event$getData.tag;
	      this.$store.dispatch('addTypeId', tag.getId());
	    },
	    removeTypeIdByTagRemoveEvent: function removeTypeIdByTagRemoveEvent(event) {
	      var _event$getData2 = event.getData(),
	        tag = _event$getData2.tag;
	      this.$store.dispatch('removeTypeId', tag.getId());
	    },
	    removeTypeIdIfNotContainsInBoundTypes: function removeTypeIdIfNotContainsInBoundTypes(event) {
	      var _event$getData3 = event.getData(),
	        tag = _event$getData3.tag;
	      var boundSelectedTags = this.boundTypesTagSelector.getTags();
	      var isTagContainsInBoundTags = boundSelectedTags.some(function (boundTag) {
	        return boundTag.getId() === tag.getId();
	      });
	      if (!isTagContainsInBoundTags) {
	        this.removeTypeIdByTagRemoveEvent(event);
	      }
	    },
	    handleCreateTypeClick: function handleCreateTypeClick() {
	      var _this2 = this;
	      if (this.$store.getters.isSaved) {
	        this.openTypeCreationSlider();
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.confirm(this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_NEED_SAVE_POPUP_MESSAGE'), function (messageBox) {
	        return _this2.save().then(function () {
	          return _this2.openTypeCreationSlider();
	        })["finally"](function () {
	          return messageBox.close();
	        });
	      }, this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_NEED_SAVE_POPUP_YES_CAPTION'));
	    },
	    openTypeCreationSlider: function openTypeCreationSlider() {
	      var _this3 = this;
	      void crm_router.Router.Instance.openTypeDetail(0, null, {
	        automatedSolutionId: this.$store.state.automatedSolution.id,
	        activeTabId: 'common',
	        isExternal: 'Y'
	      }).then(function () {
	        _this3.$Bitrix.Application.get().reloadWithNewUri(_this3.$store.state.automatedSolution.id, {
	          activeTabId: 'types'
	        });
	      });
	    },
	    save: function save() {
	      var builder = createSaveAnalyticsBuilder(this.$store).setElement(crm_integration_analytics.Dictionary.ELEMENT_SAVE_IS_REQUIRED_TO_PROCEED_POPUP);
	      return wrapPromiseInAnalytics(this.$store.dispatch('save'), builder);
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<div class=\"ui-title-3\">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_TITLE_TYPES') }}</div>\n\t\t\t<Card\n\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_TYPES_TITLE')\"\n\t\t\t\t:description=\"$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_TYPES_DESCRIPTION')\"\n\t\t\t/>\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_CREATE_TYPE') }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div ref=\"boundTypesTagSelectorContainer\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_CRM_TYPES') }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div ref=\"crmTypesTagSelectorContainer\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_EXTERNAL_TYPES') }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div ref=\"externalTypesTagSelectorContainer\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isShowPermissionsResetAlert\" class=\"ui-alert ui-alert-warning\">\n\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_PERMISSIONS_WILL_BE_RESET_ALERT') }}\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var Main = {
	  components: {
	    CommonTab: CommonTab,
	    TypesTab: TypesTab
	  },
	  props: {
	    initialActiveTabId: String
	  },
	  data: function data() {
	    return {
	      tabs: {
	        // tab show flags
	        common: this.initialActiveTabId === 'common',
	        types: this.initialActiveTabId === 'types'
	      },
	      isCancelEventAlreadyRegistered: false
	    };
	  },
	  computed: {
	    allTabIds: function allTabIds() {
	      return Object.keys(this.tabs);
	    },
	    saveButton: function saveButton() {
	      return document.getElementById('ui-button-panel-save');
	    },
	    cancelButton: function cancelButton() {
	      return document.getElementById('ui-button-panel-cancel');
	    },
	    deleteButton: function deleteButton() {
	      return document.getElementById('ui-button-panel-remove');
	    },
	    allButtons: function allButtons() {
	      var buttons = [this.saveButton, this.cancelButton];
	      if (this.deleteButton) {
	        buttons.push(this.deleteButton);
	      }
	      return buttons;
	    },
	    slider: function slider() {
	      return BX.SidePanel.Instance.getSliderByWindow(window);
	    },
	    errors: function errors() {
	      return this.$store.state.errors;
	    },
	    hasErrors: function hasErrors() {
	      return main_core.Type.isArrayFilled(this.errors);
	    },
	    // eslint-disable-next-line max-len
	    analyticsBuilder: function analyticsBuilder() {
	      return createSaveAnalyticsBuilder(this.$store);
	    }
	  },
	  mounted: function mounted() {
	    main_core_events.EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:showTab', this.showTabFromEvent);
	    main_core_events.EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:save', this.save);
	    main_core_events.EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:delete', this["delete"]);
	    main_core_events.EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:close', this.onCloseByCancelButton);
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onCloseByEsc', this.onCloseByEsc);
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', this.onClose);
	  },
	  beforeUnmount: function beforeUnmount() {
	    main_core_events.EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:showTab', this.showTabFromEvent);
	    main_core_events.EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:save', this.save);
	    main_core_events.EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:delete', this["delete"]);
	    main_core_events.EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:close', this.onCloseByCancelButton);
	    main_core_events.EventEmitter.unsubscribe('SidePanel.Slider:onCloseByEsc', this.onCloseByEsc);
	    main_core_events.EventEmitter.unsubscribe('SidePanel.Slider:onClose', this.onClose);
	  },
	  methods: {
	    showTabFromEvent: function showTabFromEvent(event) {
	      var _event$getData = event.getData(),
	        tabId = _event$getData.tabId;
	      this.showTab(tabId);
	    },
	    showTab: function showTab(tabId) {
	      if (!this.allTabIds.includes(tabId)) {
	        throw new Error('invalid tab id');
	      }
	      var _iterator = _createForOfIteratorHelper(this.allTabIds),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var id = _step.value;
	          this.tabs[id] = false;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      this.tabs[tabId] = true;
	    },
	    save: function save() {
	      var _this = this;
	      var builder = this.analyticsBuilder.setElement(crm_integration_analytics.Dictionary.ELEMENT_CREATE_BUTTON);
	      wrapPromiseInAnalytics(this.$store.dispatch('save'), builder).then(function () {
	        // don't register cancel event when this slider closes
	        _this.isCancelEventAlreadyRegistered = true;
	        _this.$Bitrix.Application.get().closeSliderOrRedirect();
	      })["catch"](function () {}) // errors will be displayed reactively
	      ["finally"](function () {
	        return _this.unlockButtons();
	      });
	    },
	    "delete": function _delete() {
	      var _this2 = this;
	      var builder = new crm_integration_analytics.Builder.Automation.AutomatedSolution.DeleteEvent().setId(this.$store.state.automatedSolution.id).setElement(crm_integration_analytics.Dictionary.ELEMENT_DELETE_BUTTON);

	      // don't register cancel event when this slider closes.
	      // we set this flag here because for some reason slider starts to close before the promise is resolved
	      this.isCancelEventAlreadyRegistered = true;
	      wrapPromiseInAnalytics(this.$store.dispatch('delete'), builder).then(function () {
	        _this2.$Bitrix.Application.get().closeSliderOrRedirect();
	      })["catch"](function () {
	        // errors will be displayed reactively

	        // okay, may be the slider won't be closed after all since we've failed
	        _this2.isCancelEventAlreadyRegistered = false;
	      })["finally"](function () {
	        return _this2.unlockButtons();
	      });
	    },
	    onCloseByCancelButton: function onCloseByCancelButton() {
	      if (this.isCancelEventAlreadyRegistered) {
	        return;
	      }
	      this.isCancelEventAlreadyRegistered = true;
	      ui_analytics.sendData(this.analyticsBuilder.setElement(crm_integration_analytics.Dictionary.ELEMENT_CANCEL_BUTTON).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	    },
	    onCloseByEsc: function onCloseByEsc(event) {
	      if (this.isCancelEventAlreadyRegistered) {
	        return;
	      }
	      var _event$getData2 = event.getData(),
	        _event$getData3 = babelHelpers.slicedToArray(_event$getData2, 1),
	        sliderEvent = _event$getData3[0];
	      if (sliderEvent.getSlider() !== this.slider) {
	        return;
	      }
	      this.isCancelEventAlreadyRegistered = true;
	      ui_analytics.sendData(this.analyticsBuilder.setElement(crm_integration_analytics.Dictionary.ELEMENT_ESC_BUTTON).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	    },
	    onClose: function onClose(event) {
	      if (this.isCancelEventAlreadyRegistered) {
	        return;
	      }
	      var _event$getData4 = event.getData(),
	        _event$getData5 = babelHelpers.slicedToArray(_event$getData4, 1),
	        sliderEvent = _event$getData5[0];
	      if (sliderEvent.getSlider() !== this.slider) {
	        return;
	      }
	      this.isCancelEventAlreadyRegistered = true;
	      ui_analytics.sendData(this.analyticsBuilder.setElement(null).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	    },
	    unlockButtons: function unlockButtons() {
	      this.allButtons.forEach(function (button) {
	        main_core.Dom.removeClass(button, 'ui-btn-wait');
	      });
	    },
	    hideError: function hideError(error) {
	      this.$store.dispatch('removeError', error);
	    }
	  },
	  template: "\n\t\t<div class=\"crm-automated-solution-details\">\n\t\t\t<form class=\"ui-form\">\n\t\t\t\t<div v-if=\"hasErrors\" class=\"ui-alert ui-alert-danger\">\n\t\t\t\t\t<template\n\t\t\t\t\t\tv-for=\"error in errors\"\n\t\t\t\t\t\t:key=\"error.message\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<span class=\"ui-alert-message\">{{error.message}}</span>\n\t\t\t\t\t\t<span class=\"ui-alert-close-btn\" @click=\"hideError(error)\"></span>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t\t<CommonTab v-show=\"tabs.common\"/>\n\t\t\t\t<TypesTab v-show=\"tabs.types\"/>\n\t\t\t</form>\n\t\t</div>\n\t"
	};

	function normalizeId(id) {
	  if (main_core.Type.isNil(id)) {
	    return null;
	  }
	  return main_core.Text.toInteger(id);
	}
	function normalizeDynamicTypesTitles(titles) {
	  if (!main_core.Type.isPlainObject(titles)) {
	    return {};
	  }
	  var result = {};
	  for (var _i = 0, _Object$entries = Object.entries(titles); _i < _Object$entries.length; _i++) {
	    var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	      key = _Object$entries$_i[0],
	      value = _Object$entries$_i[1];
	    if (main_core.Text.toInteger(key) > 0 && main_core.Type.isStringFilled(value)) {
	      result[key] = value;
	    }
	  }
	  return result;
	}
	function normalizeTitle(title) {
	  if (main_core.Type.isNil(title)) {
	    return null;
	  }
	  return String(title);
	}
	function normalizeTypesIds(typeIds) {
	  if (!main_core.Type.isArrayFilled(typeIds)) {
	    return [];
	  }
	  return typeIds.map(function (x) {
	    return normalizeTypeId(x);
	  }).filter(function (x) {
	    return x > 0;
	  });
	}
	function normalizeTypeId(typeId) {
	  return main_core.Text.toInteger(typeId);
	}
	function normalizeErrors(errors) {
	  if (!main_core.Type.isArrayFilled(errors)) {
	    return [];
	  }
	  return errors.filter(function (x) {
	    return isValidError(x);
	  });
	}
	function isValidError(error) {
	  return main_core.Type.isStringFilled(error.message) && (main_core.Type.isNil(error.code) || main_core.Type.isStringFilled(error.code) || main_core.Type.isInteger(error.code)) && (main_core.Type.isNil(error.customData) || main_core.Type.isPlainObject(error.customData));
	}

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var actions = {
	  setState: function setState(store$$1, stateToSet) {
	    store$$1.commit('setState', stateToSet);
	  },
	  setErrors: function setErrors(store$$1, errors) {
	    store$$1.commit('setErrors', errors);
	  },
	  removeError: function removeError(store$$1, error) {
	    store$$1.commit('removeError', error);
	  },
	  setTitle: function setTitle(store$$1, title) {
	    store$$1.commit('setTitle', title);
	  },
	  addTypeId: function addTypeId(store$$1, typeId) {
	    store$$1.commit('addTypeId', typeId);
	  },
	  removeTypeId: function removeTypeId(store$$1, typeId) {
	    store$$1.commit('removeTypeId', typeId);
	  },
	  save: function save(store$$1) {
	    var savePromise = null;
	    var fields = main_core.Runtime.clone(store$$1.state.automatedSolution);
	    if (fields.typeIds.length <= 0) {
	      // we cant send an empty array in form data
	      fields.typeIds = false;
	    }
	    if (store$$1.getters.isNew) {
	      savePromise = runAjaxAction('crm.automatedsolution.add', {
	        data: {
	          fields: fields
	        }
	      });
	    } else {
	      savePromise = runAjaxAction('crm.automatedsolution.update', {
	        data: {
	          id: store$$1.state.automatedSolution.id,
	          fields: fields
	        }
	      });
	    }
	    return savePromise.then(function (_ref) {
	      var data = _ref.data;
	      store$$1.dispatch('setState', {
	        automatedSolution: data.automatedSolution
	      });
	      emitUpdateEventToOutsideWorld(data.automatedSolution);
	    })["catch"](function (response) {
	      // eslint-disable-next-line no-console
	      console.warn('could not save automated solution', {
	        response: response,
	        state: main_core.Runtime.clone(store$$1.state)
	      });
	      var errors = response.errors;
	      var wasErrorHandled = false;
	      var _iterator = _createForOfIteratorHelper$1(errors),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _error$customData;
	          var error = _step.value;
	          if (main_core.Type.isStringFilled((_error$customData = error.customData) === null || _error$customData === void 0 ? void 0 : _error$customData.sliderCode)) {
	            ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	              code: error.customData.sliderCode
	            }).show();
	            wasErrorHandled = true;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      if (!wasErrorHandled) {
	        // to show errors in ui
	        store$$1.dispatch('setErrors', errors);
	      }
	      throw errors;
	    });
	  },
	  "delete": function _delete(store$$1) {
	    return runAjaxAction('crm.automatedsolution.delete', {
	      data: {
	        id: store$$1.state.automatedSolution.id
	      }
	    }).then(function () {
	      store$$1.dispatch('setState', {});
	      emitUpdateEventToOutsideWorld({
	        id: store$$1.state.automatedSolution.id
	      });
	    })["catch"](function (response) {
	      // eslint-disable-next-line no-console
	      console.warn('could not delete automated solution', {
	        response: response,
	        state: main_core.Runtime.clone(store$$1.state)
	      });
	      store$$1.dispatch('setErrors', response.errors);
	      throw response.errors;
	    });
	  }
	};
	function runAjaxAction() {
	  for (var _len = arguments.length, ajaxRunActionArgs = new Array(_len), _key = 0; _key < _len; _key++) {
	    ajaxRunActionArgs[_key] = arguments[_key];
	  }
	  // vuex don't understand BX.Promise. 'this.$store.dispatch.then' and 'subscribeAction({after})' won't work
	  // wrap it in native Promise to fix it

	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runAction.apply(main_core.ajax, ajaxRunActionArgs).then(resolve)["catch"](reject);
	  });
	}
	function emitUpdateEventToOutsideWorld(_ref2) {
	  var id = _ref2.id,
	    title = _ref2.title,
	    intranetCustomSectionId = _ref2.intranetCustomSectionId;
	  var data = {
	    id: normalizeId(id),
	    title: normalizeTitle(title),
	    intranetCustomSectionId: normalizeId(intranetCustomSectionId)
	  };
	  crm_toolbarComponent.ToolbarComponent.Instance.emitAutomatedSolutionUpdatedEvent(data);
	}

	/* eslint-disable no-param-reassign */
	var mutations = {
	  /**
	   * Sets new initial state for the store. Modification flag is reset
	   */
	  setState: function setState(state, stateToSet) {
	    var _stateToSet$automated, _stateToSet$automated2, _stateToSet$automated3, _stateToSet$permissio, _stateToSet$permissio2, _stateToSet$permissio3, _stateToSet$permissio4, _stateToSet$isPermiss;
	    state.automatedSolution.id = normalizeId((_stateToSet$automated = stateToSet.automatedSolution) === null || _stateToSet$automated === void 0 ? void 0 : _stateToSet$automated.id);
	    state.automatedSolution.title = normalizeTitle((_stateToSet$automated2 = stateToSet.automatedSolution) === null || _stateToSet$automated2 === void 0 ? void 0 : _stateToSet$automated2.title);
	    var typeIds = normalizeTypesIds((_stateToSet$automated3 = stateToSet.automatedSolution) === null || _stateToSet$automated3 === void 0 ? void 0 : _stateToSet$automated3.typeIds);
	    state.automatedSolution.typeIds = babelHelpers.toConsumableArray(typeIds);
	    state.automatedSolutionOrigTypeIds = babelHelpers.toConsumableArray(typeIds);
	    state.permissions.canMoveSmartProcessFromCrm = main_core.Text.toBoolean((_stateToSet$permissio = (_stateToSet$permissio2 = stateToSet.permissions) === null || _stateToSet$permissio2 === void 0 ? void 0 : _stateToSet$permissio2.canMoveSmartProcessFromCrm) !== null && _stateToSet$permissio !== void 0 ? _stateToSet$permissio : false);
	    state.permissions.canMoveSmartProcessFromAnotherAutomatedSolution = main_core.Text.toBoolean((_stateToSet$permissio3 = (_stateToSet$permissio4 = stateToSet.permissions) === null || _stateToSet$permissio4 === void 0 ? void 0 : _stateToSet$permissio4.canMoveSmartProcessFromAnotherAutomatedSolution) !== null && _stateToSet$permissio3 !== void 0 ? _stateToSet$permissio3 : false);
	    state.dynamicTypesTitles = normalizeDynamicTypesTitles(stateToSet.dynamicTypesTitles);
	    state.errors = normalizeErrors(stateToSet.errors);
	    state.isModified = false;
	    state.isPermissionsLayoutV2Enabled = main_core.Text.toBoolean((_stateToSet$isPermiss = stateToSet.isPermissionsLayoutV2Enabled) !== null && _stateToSet$isPermiss !== void 0 ? _stateToSet$isPermiss : false);
	  },
	  setErrors: function setErrors(state, errors) {
	    state.errors = normalizeErrors(errors);
	  },
	  removeError: function removeError(state, error) {
	    if (!isValidError(error)) {
	      return;
	    }
	    state.errors = state.errors.filter(function (x) {
	      return x !== error;
	    });
	  },
	  setTitle: function setTitle(state, title) {
	    var newTitle = normalizeTitle(title);
	    if (newTitle !== state.automatedSolution.title) {
	      state.isModified = true;
	    }
	    state.automatedSolution.title = newTitle;
	  },
	  addTypeId: function addTypeId(state, typeId) {
	    var normalizedTypeId = normalizeTypeId(typeId);
	    if (normalizedTypeId <= 0) {
	      return;
	    }
	    if (state.automatedSolution.typeIds.includes(normalizedTypeId)) {
	      return;
	    }
	    state.automatedSolution.typeIds.push(normalizedTypeId);
	    state.isModified = true;
	  },
	  removeTypeId: function removeTypeId(state, typeId) {
	    var normalizedTypeId = normalizeTypeId(typeId);
	    if (normalizedTypeId <= 0) {
	      return;
	    }
	    var newTypeIds = state.automatedSolution.typeIds.filter(function (id) {
	      return id !== normalizedTypeId;
	    });
	    if (newTypeIds.length !== state.automatedSolution.typeIds.length) {
	      state.isModified = true;
	    }
	    state.automatedSolution.typeIds = newTypeIds;
	  }
	};

	var store = {
	  strict: true,
	  state: function state() {
	    return {
	      automatedSolutionOrigTypeIds: [],
	      automatedSolution: {
	        id: null,
	        title: null,
	        typeIds: []
	      },
	      permissions: {
	        canMoveSmartProcessFromCrm: false,
	        canMoveSmartProcessFromAnotherAutomatedSolution: false
	      },
	      dynamicTypesTitles: {},
	      errors: [],
	      isModified: false,
	      isPermissionsLayoutV2Enabled: false
	    };
	  },
	  getters: {
	    isNew: function isNew(state) {
	      return state.automatedSolution.id <= 0;
	    },
	    isSaved: function isSaved(state, getters) {
	      return !state.isModified && !getters.isNew;
	    }
	  },
	  actions: actions,
	  mutations: mutations
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _container = /*#__PURE__*/new WeakMap();
	var _initialActiveTabId = /*#__PURE__*/new WeakMap();
	var _initialState = /*#__PURE__*/new WeakMap();
	var _app = /*#__PURE__*/new WeakMap();
	var App = /*#__PURE__*/function () {
	  function App(_ref) {
	    var containerId = _ref.containerId,
	      activeTabId = _ref.activeTabId,
	      state = _ref.state;
	    babelHelpers.classCallCheck(this, App);
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _initialActiveTabId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _initialState, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _app, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _container, document.getElementById(containerId));
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _container))) {
	      throw new Error('container not found');
	    }
	    babelHelpers.classPrivateFieldSet(this, _initialActiveTabId, String(activeTabId));
	    if (main_core.Type.isPlainObject(state)) {
	      babelHelpers.classPrivateFieldSet(this, _initialState, state);
	    }
	  }
	  babelHelpers.createClass(App, [{
	    key: "start",
	    value: function start() {
	      // eslint-disable-next-line unicorn/no-this-assignment
	      var appWrapperRef = this;
	      babelHelpers.classPrivateFieldSet(this, _app, ui_vue3.BitrixVue.createApp(_objectSpread(_objectSpread({}, Main), {}, {
	        beforeCreate: function beforeCreate() {
	          this.$bitrix.Application.set(appWrapperRef);
	        }
	      }), {
	        initialActiveTabId: babelHelpers.classPrivateFieldGet(this, _initialActiveTabId)
	      }));
	      var vuexStore = ui_vue3_vuex.createStore(store);
	      if (babelHelpers.classPrivateFieldGet(this, _initialState)) {
	        vuexStore.dispatch('setState', babelHelpers.classPrivateFieldGet(this, _initialState));
	      }
	      babelHelpers.classPrivateFieldGet(this, _app).use(vuexStore);
	      babelHelpers.classPrivateFieldGet(this, _app).mount(babelHelpers.classPrivateFieldGet(this, _container));
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      babelHelpers.classPrivateFieldGet(this, _app).unmount();
	      babelHelpers.classPrivateFieldSet(this, _app, null);
	    }
	  }, {
	    key: "reloadWithNewUri",
	    value: function reloadWithNewUri(automatedSolutionId) {
	      var queryParams = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      setTimeout(function () {
	        var uri = crm_router.Router.Instance.getAutomatedSolutionDetailUrl(automatedSolutionId);
	        uri.setQueryParams(_objectSpread(_objectSpread({}, queryParams), {}, {
	          IFRAME: 'Y',
	          IFRAME_TYPE: 'SIDE_SLIDER'
	        }));
	        window.location.href = uri.toString();
	      });
	    }
	  }, {
	    key: "closeSliderOrRedirect",
	    value: function closeSliderOrRedirect() {
	      setTimeout(function () {
	        crm_router.Router.Instance.closeSliderOrRedirect(crm_router.Router.Instance.getAutomatedSolutionListUrl(), window);
	      });
	    }
	  }]);
	  return App;
	}();

	exports.App = App;

}((this.BX.Crm.AutomatedSolution.Details = this.BX.Crm.AutomatedSolution.Details || {}),BX.Vue3,BX.Vue3.Vuex,BX.Event,BX.UI.Analytics,BX.Crm.Integration.Analytics,BX.Crm,BX.UI.Dialogs,BX.UI.EntitySelector,BX.Crm,BX.UI,BX));
//# sourceMappingURL=script.js.map
