this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Config = this.BX.Crm.Config || {};
(function (exports,main_popup,ui_buttons,catalog_storeUse,ui_vue,ui_notification,ui_designTokens,main_core,main_core_events) {
	'use strict';

	var LocMixin = {
	  computed: {
	    loc: function loc() {
	      return ui_vue.Vue.getFilteredPhrases('CRM_CFG_C_SETTINGS_');
	    }
	  }
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var Reservation = {
	  props: {
	    settings: {
	      type: Object,
	      required: true
	    }
	  },
	  data: function data() {
	    var result = {};

	    var _iterator = _createForOfIteratorHelper(this.settings.scheme),
	        _step;

	    try {
	      for (_iterator.s(); !(_step = _iterator.n()).done;) {
	        var element = _step.value;
	        result[element.code] = this.settings.values[element.code];
	      }
	    } catch (err) {
	      _iterator.e(err);
	    } finally {
	      _iterator.f();
	    }

	    return result;
	  },
	  methods: {
	    onChanged: function onChanged() {
	      this.$emit('change', this.$data);
	    },
	    getWrapperClass: function getWrapperClass(type) {
	      return type === 'option' ? {
	        'catalog-settings-editor-checkbox-content-block': true
	      } : {
	        'catalog-settings-editor-content-block': true
	      };
	    }
	  },
	  mounted: function mounted() {
	    BX.UI.Hint.init(this.$el);
	  },
	  template: "\n\t\t<div>\n\t\t\t<div\n\t\t\t\tv-for=\"setting in settings.scheme\"\n\t\t\t\t:class=\"getWrapperClass(setting.type)\"\n\t\t\t>\n\t\t\t\t<template v-if=\"setting.type === 'list'\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t<label>{{setting.name}}</label>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100\">\n\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t<select\n\t\t\t\t\t\t\tv-model=\"$data[setting.code]\"\n\t\t\t\t\t\t\t@change=\"onChanged\"\n\t\t\t\t\t\t\t:disabled=\"setting.disabled\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<option v-for=\"value in setting.values\" :value=\"value.code\">\n\t\t\t\t\t\t\t\t{{value.name}}\n\t\t\t\t\t\t\t</option>\t\t\t\t\t\t\n\t\t\t\t\t\t</select>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-if=\"setting.type === 'text'\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t<label>{{setting.name}}</label>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\tv-model=\"$data[setting.code]\"\n\t\t\t\t\t\t\t@change=\"onChanged\"\n\t\t\t\t\t\t\t:disabled=\"setting.disabled\"\n\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-if=\"setting.type === 'int'\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t<label>{{setting.name}}</label>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\tv-model=\"$data[setting.code]\"\n\t\t\t\t\t\t\t@input=\"onChanged\"\n\t\t\t\t\t\t\t:disabled=\"setting.disabled\"\n\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-if=\"setting.type === 'option'\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\tv-model=\"$data[setting.code]\"\n\t\t\t\t\t\t\t@change=\"onChanged\"\n\t\t\t\t\t\t\t:id=\"setting.code + '_' + $vnode.key\"\n\t\t\t\t\t\t\t:disabled=\"setting.disabled\"\n\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t<label\n\t\t\t\t\t\t\t:for=\"setting.code + '_' + $vnode.key\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-label-text\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{setting.name}}\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"setting.description\"\n\t\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\t\t:data-hint=\"setting.description\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	var ProductSettingsUpdater = /*#__PURE__*/function () {
	  function ProductSettingsUpdater(params) {
	    babelHelpers.classCallCheck(this, ProductSettingsUpdater);
	    this.url = '/bitrix/tools/catalog/product_settings.php';
	    this.stepOptions = {
	      ajaxSessionID: '',
	      maxExecutionTime: 30,
	      maxOperationCounter: 10
	    };
	    this.finish = false;
	    this.currentState = {
	      counter: 0,
	      operationCounter: 0,
	      errorCounter: 0,
	      lastID: 0
	    };
	    this.ajaxParams = {
	      operation: 'Y'
	    };
	    this.iblocks = [];
	    this.iblockIndex = -1;
	    this.stepOptions.ajaxSessionID = 'productSettings';
	    this.currentState.counter = 0;
	    this.events = params.events;
	    this.settings = params.settings;
	  }

	  babelHelpers.createClass(ProductSettingsUpdater, [{
	    key: "nextStep",
	    value: function nextStep() {
	      for (var key in this.stepOptions) {
	        if (this.stepOptions.hasOwnProperty(key)) {
	          this.ajaxParams[key] = this.stepOptions[key];
	        }
	      }

	      for (var _key in this.currentState) {
	        if (this.currentState.hasOwnProperty(_key)) {
	          this.ajaxParams[_key] = this.currentState[_key];
	        }
	      }

	      this.ajaxParams.sessid = BX.bitrix_sessid();
	      this.ajaxParams.lang = BX.message('LANGUAGE_ID');
	      BX.ajax.loadJSON(this.url, this.ajaxParams, BX.proxy(this.nextStepResult, this));
	    }
	  }, {
	    key: "nextStepResult",
	    value: function nextStepResult(result) {
	      if (BX.type.isPlainObject(result)) {
	        this.currentState.lastID = result.lastID;
	        this.stepOptions.maxOperationCounter = result.maxOperationCounter;
	        this.currentState.operationCounter = parseInt(result.operationCounter, 10);

	        if (isNaN(this.currentState.operationCounter)) {
	          this.currentState.operationCounter = 0;
	        }

	        this.currentState.errorCounter = parseInt(result.errorCounter, 10);

	        if (isNaN(this.currentState.errorCounter)) {
	          this.currentState.errorCounter = 0;
	        }

	        if (this.events.onProgress) {
	          this.events.onProgress({
	            allCnt: result.allCounter,
	            doneCnt: result.allOperationCounter,
	            currentIblockName: this.iblocks[this.iblockIndex].NAME
	          });
	        }

	        if (this.finish) {
	          this.finishOperation();
	        } else {
	          this.checkOperation(result.finishOperation);
	        }
	      }
	    }
	  }, {
	    key: "finishOperation",
	    value: function finishOperation() {
	      this.currentState.operationCounter = 0;
	      this.currentState.errorCounter = 0;
	      this.currentState.lastID = 0;
	      this.finish = false;

	      if (this.events.onComplete) {
	        this.events.onComplete();
	      }
	    }
	  }, {
	    key: "startOperation",
	    value: function startOperation() {
	      BX.ajax.loadJSON(this.url, _objectSpread({
	        sessid: BX.bitrix_sessid(),
	        changeSettings: 'Y'
	      }, this.settings), BX.proxy(this.changeSettingsResult, this));
	    }
	  }, {
	    key: "changeSettingsResult",
	    value: function changeSettingsResult(result) {
	      if (!BX.type.isPlainObject(result)) {
	        return;
	      }

	      if (result.success === 'Y') {
	        this.loadIblockList();
	      } else {
	        this.stopOperation();
	      }
	    }
	  }, {
	    key: "stopOperation",
	    value: function stopOperation() {
	      this.finish = true;
	    }
	  }, {
	    key: "checkIblockIndex",
	    value: function checkIblockIndex() {
	      return !(this.iblocks.length === 0 || this.iblockIndex < 0 || this.iblockIndex >= this.iblocks.length);
	    }
	  }, {
	    key: "loadIblockList",
	    value: function loadIblockList() {
	      var _this = this;

	      BX.ajax.loadJSON(this.url, {
	        sessid: BX.bitrix_sessid(),
	        getIblock: 'Y'
	      }, function (result) {
	        if (BX.type.isArray(result)) {
	          _this.iblocks = result;

	          if (_this.iblocks.length > 0) {
	            _this.iblockIndex = 0;

	            _this.iblockReindex();
	          } else {
	            _this.stopOperation();
	          }
	        }
	      });
	    }
	  }, {
	    key: "iblockReindex",
	    value: function iblockReindex() {
	      if (this.finish || !this.checkIblockIndex()) {
	        return;
	      }

	      this.initStep();
	      this.nextStep();
	    }
	  }, {
	    key: "initStep",
	    value: function initStep() {
	      this.currentState.iblockId = this.iblocks[this.iblockIndex].ID;
	      this.currentState.counter = this.iblocks[this.iblockIndex].COUNT;
	      this.currentState.operationCounter = 0;
	      this.currentState.errorCounter = 0;
	      this.currentState.lastID = 0;
	    }
	  }, {
	    key: "checkOperation",
	    value: function checkOperation(result) {
	      if (!!result) {
	        this.iblockIndex++;

	        if (this.iblockIndex >= this.iblocks.length || this.currentState.errorCounter > 0) {
	          this.finishOperation();

	          if (this.currentState.errorCounter == 0) {
	            this.finalRequest();
	          }
	        } else {
	          this.initStep();
	          this.nextStep();
	        }
	      } else {
	        this.nextStep();
	      }
	    }
	  }, {
	    key: "finalRequest",
	    value: function finalRequest() {
	      var iblockList = [];

	      if (this.iblocks.length > 0) {
	        for (var i = 0; i < this.iblocks.length; i++) {
	          iblockList[iblockList.length] = this.iblocks[i].ID;
	        }

	        BX.ajax.get(this.url, {
	          sessid: BX.bitrix_sessid(),
	          finalRequest: 'Y',
	          iblockList: iblockList
	        });
	      }
	    }
	  }]);
	  return ProductSettingsUpdater;
	}();

	var ProductUpdater = ui_vue.Vue.extend({
	  mixins: [LocMixin],
	  props: {
	    settings: {
	      type: Object,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      currentIblockName: null,
	      allCnt: 0,
	      doneCnt: 0
	    };
	  },
	  computed: {
	    progressStyles: function progressStyles() {
	      var width = 0;

	      if (this.allCnt > 0) {
	        width = Math.round(this.doneCnt / this.allCnt * 100);
	      }

	      return {
	        width: width + '%'
	      };
	    }
	  },
	  created: function created() {
	    var _this = this;

	    new ProductSettingsUpdater({
	      settings: this.settings,
	      events: {
	        onProgress: function onProgress(data) {
	          _this.currentIblockName = data.currentIblockName;
	          _this.allCnt = data.allCnt;
	          _this.doneCnt = data.doneCnt;
	        },
	        onComplete: function onComplete() {
	          _this.$emit('complete');
	        }
	      }
	    }).startOperation();
	  },
	  template: "\n\t\t<div >\n\t\t\t<div class=\"ui-progressbar ui-progressbar-column\">\n\t\t\t\t<div style=\"font-weight: bold;\" class=\"ui-progressbar-text-before\">\n\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_UPDATE_TITLE}}\t\t\t\t\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-progressbar-track\">\n\t\t\t\t\t<div :style=\"progressStyles\" class=\"ui-progressbar-bar\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-progressbar-text-after\">\n\t\t\t\t\t{{doneCnt}} {{loc.CRM_CFG_C_SETTINGS_OUT_OF}} {{allCnt}}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div style=\"color: rgb(83, 92, 105); font-size: 12px;\">\n\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_UPDATE_WAIT}}\n\t\t\t\t<div\n\t\t\t\t\tv-show=\"currentIblockName\"\n\t\t\t\t\tstyle=\"padding-top: 10px;\"\n\t\t\t\t>\n\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_CURRENT_CATALOG}}: {{currentIblockName}}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var Const = Object.freeze({
	  url: '/crm/configs/catalog/'
	});

	var _templateObject, _templateObject2;

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var HELP_ARTICLE_ID = 15706692;
	var app = ui_vue.Vue.extend({
	  mixins: [LocMixin],
	  components: {
	    'reservation': Reservation
	  },
	  props: {
	    initData: {
	      type: Object,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      /**
	       * State
	       */
	      isSaving: false,
	      isChanged: false,
	      currentReservationEntityCode: null,

	      /**
	       *
	       */
	      isStoreControlUsed: null,
	      productsCnt: null,

	      /**
	       * Reservation settings
	       */
	      reservationEntities: [],

	      /**
	       * Default products settings
	       */
	      initDefaultQuantityTrace: null,
	      initDefaultCanBuyZero: null,
	      initDefaultSubscribe: null,
	      initCheckRightsOnDecreaseStoreAmount: null,
	      defaultQuantityTrace: null,
	      defaultCanBuyZero: null,
	      defaultSubscribe: null,
	      checkRightsOnDecreaseStoreAmount: null,

	      /**
	       * Product card
	       */
	      productCardSliderEnabled: null,
	      isCanEnableProductCardSlider: false,
	      isBitrix24: false,
	      busProductCardHelpLink: '',
	      defaultProductVatIncluded: null
	    };
	  },
	  created: function created() {
	    this.initialize(this.initData);
	    this.productUpdaterPopup = null;
	    this.settingsMenu = null;
	    var sliderUrl = Const.url;

	    if (this.configCatalogSource) {
	      sliderUrl += '?configCatalogSource=' + this.configCatalogSource;
	    }

	    this.slider = BX.SidePanel.Instance.getSlider(sliderUrl);
	  },
	  computed: {
	    hasAccessToReservationSettings: function hasAccessToReservationSettings() {
	      if (this.initData.hasAccessToReservationSettings !== undefined) {
	        return this.initData.hasAccessToReservationSettings === true;
	      }

	      return true;
	    },
	    hasAccessToCatalogSettings: function hasAccessToCatalogSettings() {
	      if (this.initData.hasAccessToCatalogSettings !== undefined) {
	        return this.initData.hasAccessToCatalogSettings === true;
	      }

	      return true;
	    },
	    isCanChangeOptionCanByZero: function isCanChangeOptionCanByZero() {
	      var _Extension$getSetting;

	      return ((_Extension$getSetting = main_core.Extension.getSettings('crm.config.catalog')) === null || _Extension$getSetting === void 0 ? void 0 : _Extension$getSetting.isCanChangeOptionCanByZero) === true;
	    },
	    isReservationUsed: function isReservationUsed() {
	      return this.isStoreControlUsed || this.isReservationUsageViaQuantityTrace;
	    },
	    isCanBuyZeroInDocsVisible: function isCanBuyZeroInDocsVisible() {
	      return this.isStoreControlUsed;
	    },
	    isDefaultQuantityTraceVisible: function isDefaultQuantityTraceVisible() {
	      return this.isReservationUsageViaQuantityTrace;
	    },
	    isReservationUsageViaQuantityTrace: function isReservationUsageViaQuantityTrace() {
	      return !this.isStoreControlUsed && this.initDefaultQuantityTrace;
	    },
	    hasProductSettingsChanged: function hasProductSettingsChanged() {
	      return !(this.initDefaultQuantityTrace === this.defaultQuantityTrace && this.initDefaultCanBuyZero === this.defaultCanBuyZero && this.initDefaultSubscribe === this.defaultSubscribe && this.initCheckRightsOnDecreaseStoreAmount === this.checkRightsOnDecreaseStoreAmount);
	    },
	    needProgressBarOnProductsUpdating: function needProgressBarOnProductsUpdating() {
	      return this.productsCnt > 500;
	    },
	    saveButtonClasses: function saveButtonClasses() {
	      return {
	        'ui-btn': true,
	        'ui-btn-success': true,
	        'ui-btn-wait': this.isSaving
	      };
	    },
	    buttonsPanelClass: function buttonsPanelClass() {
	      return {
	        'ui-button-panel-wrapper': true,
	        'ui-pinner': true,
	        'ui-pinner-bottom': true,
	        'ui-pinner-full-width': true,
	        'ui-button-panel-wrapper-hide': !this.isChanged
	      };
	    },
	    description: function description() {
	      return this.isStoreControlUsed ? main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_STORE_CONTROL_ACTIVE') : main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_STORE_CONTROL_NOT_ACTIVE');
	    }
	  },
	  watch: {
	    defaultQuantityTrace: function defaultQuantityTrace(newVal, oldVal) {
	      var showWarn = this.isDefaultQuantityTraceVisible && newVal === false && oldVal === true;

	      if (!showWarn) {
	        return;
	      }

	      var warnPopup = new main_popup.Popup(null, null, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            return warnPopup.destroy();
	          }
	        },
	        content: main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"catalog-settings-popup-content\">\n\t\t\t\t\t\t<h3>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</h3>\n\t\t\t\t\t\t<div class=\"catalog-settings-popup-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_TURN_OFF_QUANTITY_TRACE_TITLE'), main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_TURN_OFF_QUANTITY_TRACE_TEXT')),
	        maxWidth: 500,
	        overlay: true,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_CLOSE'),
	          color: ui_buttons.Button.Color.PRIMARY,
	          onclick: function onclick() {
	            return warnPopup.close();
	          }
	        })]
	      });
	      warnPopup.show();
	    }
	  },
	  methods: {
	    markAsChanged: function markAsChanged() {
	      this.isChanged = true;
	    },
	    onEnableProductCardCheckboxClick: function onEnableProductCardCheckboxClick() {
	      if (!this.productCardSliderEnabled) {
	        this.askToEnableProductCardSlider();
	      }

	      this.markAsChanged();
	    },
	    askToEnableProductCardSlider: function askToEnableProductCardSlider() {
	      var askPopup = this.isBitrix24 ? this.createWarningProductCardPopupForBitrix24() : this.createWarningProductCardPopupForBUS();
	      askPopup.show();
	    },
	    createWarningProductCardPopupForBitrix24: function createWarningProductCardPopupForBitrix24() {
	      var _this = this;

	      var askPopup = this.createWarningProductCardPopup(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_TEXT'), [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_DISAGREE'),
	        color: ui_buttons.Button.Color.PRIMARY,
	        onclick: function onclick() {
	          _this.productCardSliderEnabled = false;
	          askPopup.close();
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_AGREE'),
	        onclick: function onclick() {
	          return askPopup.close();
	        }
	      })], {
	        onPopupShow: function onPopupShow() {
	          var helpdeskLink = document.getElementById('catalog-settings-new-productcard-popup-helpdesk');

	          if (helpdeskLink) {
	            main_core.Event.bind(helpdeskLink, 'click', function () {
	              return top.BX.Helper.show('redirect=detail&code=11657084');
	            });
	          }
	        }
	      });
	      return askPopup;
	    },
	    createWarningProductCardPopupForBUS: function createWarningProductCardPopupForBUS() {
	      var _this2 = this;

	      var askPopup = this.createWarningProductCardPopup(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_BUS_TEXT').replace('#HELP_LINK#', this.busProductCardHelpLink), [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_AGREE'),
	        color: ui_buttons.Button.Color.SUCCESS,
	        onclick: function onclick() {
	          return askPopup.close();
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_BUS_DISAGREE'),
	        color: ui_buttons.Button.Color.LINK,
	        onclick: function onclick() {
	          _this2.productCardSliderEnabled = false;
	          askPopup.close();
	        }
	      })]);
	      return askPopup;
	    },
	    createWarningProductCardPopup: function createWarningProductCardPopup(contentText, buttons) {
	      var events = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      var popupParams = {
	        events: _objectSpread$1({
	          onPopupClose: function onPopupClose() {
	            return askPopup.destroy();
	          }
	        }, events),
	        content: main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"catalog-settings-new-productcard-popup-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), contentText),
	        className: 'catalog-settings-new-productcard-popup',
	        titleBar: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_TITLE'),
	        maxWidth: 800,
	        overlay: true,
	        buttons: buttons
	      };
	      var askPopup = new main_popup.Popup(null, null, popupParams);
	      return askPopup;
	    },
	    openStoreControlMaster: function openStoreControlMaster() {
	      var _this3 = this;

	      var mode = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var sliderUrl = '/bitrix/components/bitrix/catalog.warehouse.master.clear/slider.php?mode=' + mode;

	      if (this.configCatalogSource) {
	        sliderUrl += '&inventoryManagementSource=' + this.configCatalogSource;
	      }

	      new catalog_storeUse.Slider().open(sliderUrl, {}).then(function (slider) {
	        main_core.ajax.runAction('catalog.config.isUsedInventoryManagement', {}).then(function (response) {
	          if (_this3.isStoreControlUsed !== response.data) {
	            if (response.data === true) {
	              _this3.close();
	            } else {
	              _this3.refresh();
	            }
	          }

	          if (slider !== null && slider !== void 0 && slider.getData().get('isPresetApplied')) {
	            _this3.showMessage(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_SAVED_SUCCESSFULLY'));
	          }
	        });
	      });
	    },
	    refresh: function refresh() {
	      var _this4 = this;

	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:crm.config.catalog.settings', 'initialize', {
	          mode: 'class',
	          json: {}
	        }).then(function (response) {
	          _this4.initialize(response.data);

	          resolve();
	        })["catch"](function (response) {
	          _this4.showResponseErrors(response);

	          reject();
	        });
	      });
	    },
	    wait: function wait(ms) {
	      return new Promise(function (resolve, reject) {
	        setTimeout(function () {
	          resolve();
	        }, ms);
	      });
	    },
	    showResponseErrors: function showResponseErrors(response) {
	      this.showMessage(response.errors.map(function (error) {
	        return error.message;
	      }).join(', '));
	    },
	    showMessage: function showMessage(message) {
	      top.BX.loadExt("ui.notification").then(function () {
	        top.BX.UI.Notification.Center.notify({
	          content: message
	        });
	      });
	    },
	    initialize: function initialize(data) {
	      var _this$configCatalogSo;

	      this.isStoreControlUsed = data.isStoreControlUsed;
	      this.productsCnt = data.productsCnt;
	      /**
	       * Reservation settings
	       */

	      this.reservationEntities = data.reservationEntities;

	      if (this.reservationEntities.length > 0) {
	        this.currentReservationEntityCode = this.reservationEntities[0].code;
	      }
	      /**
	       * Product settings
	       */


	      this.initDefaultQuantityTrace = this.defaultQuantityTrace = data.defaultQuantityTrace;
	      this.initDefaultCanBuyZero = this.defaultCanBuyZero = data.defaultCanBuyZero;
	      this.initDefaultSubscribe = this.defaultSubscribe = data.defaultSubscribe;
	      this.initCheckRightsOnDecreaseStoreAmount = this.checkRightsOnDecreaseStoreAmount = data.checkRightsOnDecreaseStoreAmount;
	      /**
	       * Other settings
	       */

	      this.defaultProductVatIncluded = data.defaultProductVatIncluded;
	      this.productCardSliderEnabled = data.productCardSliderEnabled;
	      this.isCanEnableProductCardSlider = data.isCanEnableProductCardSlider;
	      this.isBitrix24 = data.isBitrix24;
	      this.busProductCardHelpLink = data.busProductCardHelpLink;
	      this.configCatalogSource = (_this$configCatalogSo = this.configCatalogSource) !== null && _this$configCatalogSo !== void 0 ? _this$configCatalogSo : data.configCatalogSource;
	      this.isChanged = false;
	    },
	    onReservationSettingsValuesChanged: function onReservationSettingsValuesChanged(values, index) {
	      this.reservationEntities[index].settings.values = values;
	      this.markAsChanged();
	    },
	    save: function save() {
	      var _this5 = this;

	      if (this.isSaving) {
	        return;
	      }

	      this.isSaving = true;
	      this.saveProductSettings().then(function () {
	        main_core.ajax.runComponentAction('bitrix:crm.config.catalog.settings', 'save', {
	          mode: 'class',
	          json: {
	            values: {
	              reservationSettings: _this5.makeReservationSettings(),
	              productCardSliderEnabled: _this5.productCardSliderEnabled,
	              defaultProductVatIncluded: _this5.defaultProductVatIncluded,
	              checkRightsOnDecreaseStoreAmount: _this5.checkRightsOnDecreaseStoreAmount
	            }
	          }
	        }).then(function (response) {
	          _this5.isChanged = false;
	          _this5.isSaving = false;

	          _this5.showMessage(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_SAVED_SUCCESSFULLY'));

	          _this5.refresh().then(function () {
	            return _this5.wait(700);
	          }).then(function () {
	            return _this5.close();
	          });

	          BX.SidePanel.Instance.postMessage(window, "BX.Crm.Config.Catalog:onAfterSaveSettings");
	        })["catch"](function (response) {
	          _this5.isChanged = false;
	          _this5.isSaving = false;

	          _this5.showResponseErrors(response);
	        });
	      });
	    },
	    saveProductSettings: function saveProductSettings() {
	      var _this6 = this;

	      if (!this.hasProductSettingsChanged) {
	        return Promise.resolve();
	      }

	      var productUpdaterOptions = {
	        propsData: {
	          settings: {
	            default_quantity_trace: this.defaultQuantityTrace ? 'Y' : 'N',
	            default_can_buy_zero: this.defaultCanBuyZero ? 'Y' : 'N',
	            default_subscribe: this.defaultSubscribe ? 'Y' : 'N'
	          }
	        }
	      };
	      return new Promise(function (resolve) {
	        var productUpdater = new ProductUpdater(productUpdaterOptions).$on('complete', function () {
	          resolve();

	          if (_this6.needProgressBarOnProductsUpdating) {
	            _this6.productUpdaterPopup.destroy();
	          }
	        }).$mount();

	        if (_this6.needProgressBarOnProductsUpdating) {
	          _this6.productUpdaterPopup = new main_popup.Popup({
	            content: productUpdater.$el,
	            width: 310,
	            overlay: true,
	            padding: 17,
	            animation: 'fading-slide',
	            angle: false
	          });

	          _this6.productUpdaterPopup.show();
	        }
	      });
	    },
	    makeReservationSettings: function makeReservationSettings() {
	      var result = {};

	      var _iterator = _createForOfIteratorHelper$1(this.reservationEntities),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var reservationEntity = _step.value;
	          result[reservationEntity.code] = reservationEntity.settings.values;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      return result;
	    },
	    cancel: function cancel() {
	      this.close();
	    },
	    close: function close() {
	      this.slider.close();
	    },
	    getReservationSettingsHint: function getReservationSettingsHint() {
	      return this.getHintContentWrapped(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_RESERVATION_SETTINGS_HINT'), HELP_ARTICLE_ID, 'reservation');
	    },
	    getProductsSettingsHint: function getProductsSettingsHint() {
	      return this.getHintContent(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_HINT'), HELP_ARTICLE_ID, 'products');
	    },
	    getCanBuyZeroHint: function getCanBuyZeroHint() {
	      return this.getHintContent(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_CAN_BUY_ZERO_HINT'), HELP_ARTICLE_ID, 'products');
	    },
	    getCanBuyZeroInDocsHint: function getCanBuyZeroInDocsHint() {
	      return this.getHintContent("\n\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_CAN_BUY_ZERO_IN_DOCS_HINT'), "\n\t\t\t\t\t<br/><br/>\n\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_CAN_BUY_ZERO_IN_DOCS_HINT_DOC_TYPE_RESTRICTIONS'), "\n\t\t\t\t\t<br/>\n\t\t\t\t"), HELP_ARTICLE_ID, 'products');
	    },
	    getHintContent: function getHintContent(content, article, anchor) {
	      return "\n\t\t\t\t".concat(content, "\n\t\t\t\t<br/>\n\t\t\t\t").concat(this.getDocumentationLink(main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_DETAILS'), article, anchor), "\n\t\t\t");
	    },
	    getHintContentWrapped: function getHintContentWrapped(text, article, anchor) {
	      return this.getDocumentationLink(text, article, anchor);
	    },
	    getDocumentationLink: function getDocumentationLink(text, article, anchor) {
	      return "\n\t\t\t\t<a href=\"javascript:void(0);\" onclick=\"if (top.BX.Helper){top.BX.Helper.show('redirect=detail&code=".concat(article, "#").concat(anchor, "');}\" class=\"catalog-settings-helper-link\">\n\t\t\t\t\t").concat(text, "\n\t\t\t\t</a>\n\t\t\t");
	    },
	    showSettingsMenu: function showSettingsMenu(e) {
	      var _this7 = this;

	      this.settingsMenu = new main_popup.Menu({
	        bindElement: e.target,
	        angle: true,
	        offsetLeft: 20,
	        items: [{
	          text: main_core.Loc.getMessage('CRM_CFG_C_SETTINGS_TURN_INVENTORY_CONTROL_OFF'),
	          onclick: function onclick() {
	            _this7.settingsMenu.destroy();

	            _this7.openStoreControlMaster('disable');
	          }
	        }]
	      });
	      this.settingsMenu.show();
	    }
	  },
	  mounted: function mounted() {
	    BX.UI.Hint.init(this.$el);
	  },
	  template: "\n\t\t<div class=\"catalog-settings-wrapper\">\n\t\t\t<form>\n\t\t\t\t<div class=\"ui-slider-section\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tstyle=\"display: flex; align-items: center\"\n\t\t\t\t\t\t\tclass=\"ui-slider-heading-4\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_TITLE}}\n\t\t\t\t\t\t\t<div v-if=\"isStoreControlUsed && hasAccessToCatalogSettings\" class=\"catalog-settings-main-header-feedback-container\">\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\t@click.prevent=\"showSettingsMenu\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-toolbar-right-buttons\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-light-border ui-btn-icon-setting ui-btn-themes\"></button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t\t<p class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t\t\t{{description}}\n\t\t\t\t\t\t\t</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"hasAccessToCatalogSettings\" class=\"catalog-settings-button-container\">\n\t\t\t\t\t\t\t<template v-if=\"isStoreControlUsed\">\n\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t@click=\"openStoreControlMaster('edit')\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light-border ui-btn-width\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_OPEN_SETTINGS}}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t@click=\"openStoreControlMaster()\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_TURN_INVENTORY_CONTROL_ON}}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"catalog-settings-main-settings\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"isReservationUsed && hasAccessToReservationSettings\"\n\t\t\t\t\t\tclass=\"ui-slider-section\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">\n\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_RESERVATION_SETTINGS}}\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\t\t\tdata-hint-html=\"\"\n\t\t\t\t\t\t\t\tdata-hint-interactivity=\"\"\n\t\t\t\t\t\t\t\t:data-hint=\"getReservationSettingsHint()\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"catalog-settings-editor-content-block\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t<label>{{loc.CRM_CFG_C_SETTINGS_RESERVATION_ENTITY}}</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-disabled ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<!--<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>-->\n\t\t\t\t\t\t\t\t<select\n\t\t\t\t\t\t\t\t\tv-model=\"currentReservationEntityCode\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<option\n\t\t\t\t\t\t\t\t\t\tv-for=\"reservationEntity in reservationEntities\"\n\t\t\t\t\t\t\t\t\t\t:value=\"reservationEntity.code\"\n\t\t\t\t\t\t\t\t\t\t:disabled=\"reservationEntity.code !== 'deal'\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{reservationEntity.name}}\n\t\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<reservation\n\t\t\t\t\t\t\tv-for=\"(reservationEntity, index) in reservationEntities\"\n\t\t\t\t\t\t\tv-show=\"reservationEntity.code === currentReservationEntityCode\"\n\t\t\t\t\t\t\t:key=\"reservationEntity.code\"\n\t\t\t\t\t\t\t:settings=\"reservationEntity.settings\"\n\t\t\t\t\t\t\t@change=\"onReservationSettingsValuesChanged($event, index)\"\n\t\t\t\t\t\t></reservation>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"hasAccessToCatalogSettings\" class=\"ui-slider-section\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">\n\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS}}\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\t\t\tdata-hint-html=\"\"\n\t\t\t\t\t\t\t\tdata-hint-interactivity=\"\"\n\t\t\t\t\t\t\t\t:data-hint=\"getProductsSettingsHint()\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"isCanEnableProductCardSlider\"\n\t\t\t\t\t\t\tclass=\"catalog-settings-editor-checkbox-content-block\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t@click=\"onEnableProductCardCheckboxClick\"\n\t\t\t\t\t\t\t\t\tv-model=\"productCardSliderEnabled\"\n\t\t\t\t\t\t\t\t\tid=\"product_card_slider_enabled\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label for=\"product_card_slider_enabled\" class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"catalog-settings-editor-checkbox-content-block\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\tv-model=\"defaultSubscribe\"\n\t\t\t\t\t\t\t\t\t@click=\"markAsChanged\"\n\t\t\t\t\t\t\t\t\tid=\"default_subscribe\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label for=\"default_subscribe\" class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_SUBSCRIBE}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"catalog-settings-editor-checkbox-content-block\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\tv-model=\"defaultProductVatIncluded\"\n\t\t\t\t\t\t\t\t\t@click=\"markAsChanged\"\n\t\t\t\t\t\t\t\t\tid=\"default_product_vat_included\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label for=\"default_product_vat_included\" class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCT_CARD_SET_VAT_IN_PRICE_FOR_NEW_PRODUCTS}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"isDefaultQuantityTraceVisible\"\n\t\t\t\t\t\t\tclass=\"catalog-settings-editor-checkbox-content-block\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\tv-model=\"defaultQuantityTrace\"\n\t\t\t\t\t\t\t\t\t@click=\"markAsChanged\"\n\t\t\t\t\t\t\t\t\tid=\"default_quantity_trace\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label for=\"default_quantity_trace\" class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_DEFAULT_QUANTITY_TRACE}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"isCanBuyZeroInDocsVisible\" class=\"catalog-settings-editor-checkbox-content-block\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\tv-model=\"checkRightsOnDecreaseStoreAmount\"\n\t\t\t\t\t\t\t\t\t@click=\"markAsChanged\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_CAN_BUY_ZERO_IN_DOCS}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\t\t\t\tdata-hint-html=\"\"\n\t\t\t\t\t\t\t\t\tdata-hint-interactivity=\"\"\n\t\t\t\t\t\t\t\t\t:data-hint=\"getCanBuyZeroInDocsHint()\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"isReservationUsed && isCanChangeOptionCanByZero\"\n\t\t\t\t\t\t\tclass=\"catalog-settings-editor-checkbox-content-block\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-checkbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\tv-model=\"defaultCanBuyZero\"\n\t\t\t\t\t\t\t\t\t@click=\"markAsChanged\"\n\t\t\t\t\t\t\t\t\tid=\"default_can_buy_zero\"\n\t\t\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!isCanChangeOptionCanByZero\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<label for=\"default_can_buy_zero\" class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_CAN_BUY_ZERO_V2}}\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\t\t\t\tdata-hint-html=\"\"\n\t\t\t\t\t\t\t\t\tdata-hint-interactivity=\"\"\n\t\t\t\t\t\t\t\t\t:data-hint=\"getCanBuyZeroHint()\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</form>\n\t\t\t<div\n\t\t\t\t:class=\"buttonsPanelClass\"\n\t\t\t>\n\t\t\t\t<div class=\"ui-button-panel ui-button-panel-align-center \">\n\t\t\t\t\t<button\n\t\t\t\t\t\t@click=\"save\"\n\t\t\t\t\t\t:class=\"saveButtonClasses\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_SAVE_BUTTON}}\n\t\t\t\t\t</button>\n\t\t\t\t\t<a\n\t\t\t\t\t\t@click=\"cancel\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-link\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{loc.CRM_CFG_C_SETTINGS_CANCEL_BUTTON}}\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div style=\"height: 65px;\"></div>\n\t\t</div>\n\t"
	});

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	var Slider = /*#__PURE__*/function () {
	  function Slider() {
	    babelHelpers.classCallCheck(this, Slider);
	  }

	  babelHelpers.createClass(Slider, null, [{
	    key: "open",
	    value: function open() {
	      var source = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var url = Const.url;

	      if (main_core.Type.isStringFilled(source)) {
	        url += '?configCatalogSource=' + source;
	      }

	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	        var _event$getData = event.getData(),
	            _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	            data = _event$getData2[0];

	        if (data.eventId === 'BX.Crm.Config.Catalog:onAfterSaveSettings') {
	          main_core_events.EventEmitter.emit(window, 'onCatalogSettingsSave');
	        }
	      });
	      return new Promise(function (resolve) {
	        return BX.SidePanel.Instance.open(url, _objectSpread$2({
	          width: 1000,
	          allowChangeHistory: false,
	          cacheable: false
	        }, options));
	      });
	    }
	  }]);
	  return Slider;
	}();

	exports.App = app;
	exports.Slider = Slider;

}((this.BX.Crm.Config.Catalog = this.BX.Crm.Config.Catalog || {}),BX.Main,BX.UI,BX.Catalog.StoreUse,BX,BX,BX,BX,BX.Event));
//# sourceMappingURL=catalog.bundle.js.map
