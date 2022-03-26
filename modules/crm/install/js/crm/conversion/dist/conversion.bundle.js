this.BX = this.BX || {};
(function (exports,main_core_events,crm_categoryList,crm_categoryModel,ui_buttons,ui_dialogs_messagebox,main_popup,ui_forms,main_core) {
	'use strict';

	var _id = new WeakMap();

	var _name = new WeakMap();

	var _entityTypeIds = new WeakMap();

	var _phrase = new WeakMap();

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var SchemeItem = /*#__PURE__*/function () {
	  function SchemeItem(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, SchemeItem);

	    _id.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _name.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _entityTypeIds.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _phrase.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _id, String(params.id));
	    babelHelpers.classPrivateFieldSet(this, _name, String(params.name));
	    babelHelpers.classPrivateFieldSet(this, _phrase, String(params.phrase));
	    babelHelpers.classPrivateFieldSet(this, _entityTypeIds, []);

	    if (main_core.Type.isArray(params.entityTypeIds)) {
	      params.entityTypeIds.forEach(function (entityTypeId) {
	        babelHelpers.classPrivateFieldGet(_this, _entityTypeIds).push(Number(entityTypeId));
	      });
	    }
	  }

	  babelHelpers.createClass(SchemeItem, [{
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _id);
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return babelHelpers.classPrivateFieldGet(this, _name);
	    }
	  }, {
	    key: "getEntityTypeIds",
	    value: function getEntityTypeIds() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeIds);
	    }
	  }, {
	    key: "getPhrase",
	    value: function getPhrase() {
	      return babelHelpers.classPrivateFieldGet(this, _phrase);
	    }
	  }]);
	  return SchemeItem;
	}();

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var _currentItemId = new WeakMap();

	var _items = new WeakMap();

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var Scheme = /*#__PURE__*/function () {
	  function Scheme(currentItemId, items) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Scheme);

	    _currentItemId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _items.set(this, {
	      writable: true,
	      value: []
	    });

	    babelHelpers.classPrivateFieldSet(this, _currentItemId, main_core.Type.isNull(currentItemId) ? currentItemId : String(currentItemId));

	    if (main_core.Type.isArray(items)) {
	      items.forEach(function (item) {
	        if (item instanceof SchemeItem) {
	          babelHelpers.classPrivateFieldGet(_this, _items).push(item);
	        } else {
	          console.error('SchemeItem is invalid in Scheme constructor. Expected instance of SchemeItem, got ' + babelHelpers.typeof(item));
	        }
	      });
	    }
	  }

	  babelHelpers.createClass(Scheme, [{
	    key: "getCurrentItem",
	    value: function getCurrentItem() {
	      if (!babelHelpers.classPrivateFieldGet(this, _items) || !babelHelpers.classPrivateFieldGet(this, _items).length) {
	        return null;
	      }

	      var item = this.getItemById(babelHelpers.classPrivateFieldGet(this, _currentItemId));
	      return item || babelHelpers.classPrivateFieldGet(this, _items)[0];
	    }
	  }, {
	    key: "setCurrentItemId",
	    value: function setCurrentItemId(currentItemId) {
	      babelHelpers.classPrivateFieldSet(this, _currentItemId, currentItemId);
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return babelHelpers.classPrivateFieldGet(this, _items);
	    }
	  }, {
	    key: "getItemById",
	    value: function getItemById(itemId) {
	      var _iterator = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _items)),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;

	          if (item.getId() === itemId) {
	            return item;
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
	    key: "getItemForSingleEntityTypeId",
	    value: function getItemForSingleEntityTypeId(entityTypeId) {
	      var _iterator2 = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _items)),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var item = _step2.value;
	          var entityTypeIds = item.getEntityTypeIds();

	          if (entityTypeIds.length === 1 && Array.from(entityTypeIds)[0] === entityTypeId) {
	            return item;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      return null;
	    }
	  }], [{
	    key: "create",
	    value: function create(params) {
	      var schemeItems = [];
	      params.items.forEach(function (item) {
	        schemeItems.push(new SchemeItem(item));
	      });
	      return new Scheme(params.currentItemId, schemeItems);
	    }
	  }]);
	  return Scheme;
	}();

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _active = new WeakMap();

	var _enableSync = new WeakMap();

	var _initData = new WeakMap();

	var _entityTypeId = new WeakMap();

	var _title = new WeakMap();

	var _internalizeBooleanValue = new WeakSet();

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var ConfigItem = /*#__PURE__*/function () {
	  function ConfigItem(params) {
	    babelHelpers.classCallCheck(this, ConfigItem);

	    _internalizeBooleanValue.add(this);

	    _active.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _enableSync.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _initData.set(this, {
	      writable: true,
	      value: {}
	    });

	    _entityTypeId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _title.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, Number(params.entityTypeId));
	    babelHelpers.classPrivateFieldSet(this, _active, _classPrivateMethodGet(this, _internalizeBooleanValue, _internalizeBooleanValue2).call(this, params.active));
	    babelHelpers.classPrivateFieldSet(this, _enableSync, _classPrivateMethodGet(this, _internalizeBooleanValue, _internalizeBooleanValue2).call(this, params.enableSync));

	    if (main_core.Type.isPlainObject(params.initData)) {
	      babelHelpers.classPrivateFieldSet(this, _initData, params.initData);
	    }

	    babelHelpers.classPrivateFieldSet(this, _title, String(params.title));
	  }

	  babelHelpers.createClass(ConfigItem, [{
	    key: "externalize",
	    value: function externalize() {
	      return {
	        entityTypeId: this.getEntityTypeId(),
	        title: this.getTitle(),
	        initData: this.getInitData(),
	        active: this.isActive() ? "Y" : "N",
	        enableSync: this.isEnableSync() ? "Y" : "N"
	      };
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return babelHelpers.classPrivateFieldGet(this, _active);
	    }
	  }, {
	    key: "setActive",
	    value: function setActive(active) {
	      babelHelpers.classPrivateFieldSet(this, _active, active);
	      return this;
	    }
	  }, {
	    key: "isEnableSync",
	    value: function isEnableSync() {
	      return babelHelpers.classPrivateFieldGet(this, _enableSync);
	    }
	  }, {
	    key: "setEnableSync",
	    value: function setEnableSync(enableSync) {
	      babelHelpers.classPrivateFieldSet(this, _enableSync, enableSync);
	      return this;
	    }
	  }, {
	    key: "getInitData",
	    value: function getInitData() {
	      return babelHelpers.classPrivateFieldGet(this, _initData) || {};
	    }
	  }, {
	    key: "setInitData",
	    value: function setInitData(data) {
	      babelHelpers.classPrivateFieldSet(this, _initData, data);
	      return this;
	    }
	  }, {
	    key: "getEntityTypeId",
	    value: function getEntityTypeId() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeId);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return babelHelpers.classPrivateFieldGet(this, _title);
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      babelHelpers.classPrivateFieldSet(this, _title, title);
	      return this;
	    }
	  }]);
	  return ConfigItem;
	}();

	var _internalizeBooleanValue2 = function _internalizeBooleanValue2(value) {
	  if (main_core.Type.isBoolean(value)) {
	    return value;
	  }

	  if (main_core.Type.isString(value)) {
	    return value === 'Y';
	  }

	  return Boolean(value);
	};

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	/**
	 * @memberOf BX.Crm.Conversion
	 */

	var _entityTypeId$1 = new WeakMap();

	var _items$1 = new WeakMap();

	var _scheme = new WeakMap();

	var Config = /*#__PURE__*/function () {
	  function Config(entityTypeId, items, scheme) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Config);

	    _entityTypeId$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _items$1.set(this, {
	      writable: true,
	      value: []
	    });

	    _scheme.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _entityTypeId$1, Number(entityTypeId));

	    if (main_core.Type.isArray(items)) {
	      items.forEach(function (item) {
	        if (item instanceof ConfigItem) {
	          babelHelpers.classPrivateFieldGet(_this, _items$1).push(item);
	        } else {
	          console.error('ConfigItem is invalid in Config constructor. Expected instance of ConfigItem, got ' + babelHelpers.typeof(item));
	        }
	      });
	    }

	    if (scheme instanceof Scheme) {
	      babelHelpers.classPrivateFieldSet(this, _scheme, scheme);
	    } else {
	      console.error('Scheme is invalid in Config constructor. Expected instance of Scheme, got ' + babelHelpers.typeof(scheme));
	    }
	  }

	  babelHelpers.createClass(Config, [{
	    key: "getEntityTypeId",
	    value: function getEntityTypeId() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeId$1);
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return babelHelpers.classPrivateFieldGet(this, _items$1);
	    }
	  }, {
	    key: "getScheme",
	    value: function getScheme() {
	      return babelHelpers.classPrivateFieldGet(this, _scheme);
	    }
	  }, {
	    key: "updateFromSchemeItem",
	    value: function updateFromSchemeItem() {
	      var schemeItem = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

	      if (!schemeItem) {
	        schemeItem = this.getScheme().getCurrentItem();
	      } else {
	        this.getScheme().setCurrentItemId(schemeItem.getId());
	      }

	      var activeEntityTypeIds = schemeItem.getEntityTypeIds();
	      babelHelpers.classPrivateFieldGet(this, _items$1).forEach(function (item) {
	        var isActive = activeEntityTypeIds.indexOf(item.getEntityTypeId()) > -1;
	        item.setEnableSync(isActive);
	        item.setActive(isActive);
	      });
	      return this;
	    }
	  }, {
	    key: "getItemByEntityTypeId",
	    value: function getItemByEntityTypeId(entityTypeId) {
	      var _iterator = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _items$1)),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;

	          if (item.getEntityTypeId() === entityTypeId) {
	            return item;
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
	    key: "externalize",
	    value: function externalize() {
	      var data = {};
	      this.getItems().forEach(function (item) {
	        data[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.externalize();
	      });
	      return data;
	    }
	  }, {
	    key: "updateItems",
	    value: function updateItems(items) {
	      var _this2 = this;

	      babelHelpers.classPrivateFieldSet(this, _items$1, []);
	      items.forEach(function (item) {
	        babelHelpers.classPrivateFieldGet(_this2, _items$1).push(new ConfigItem(item));
	      });
	      return this;
	    }
	  }], [{
	    key: "create",
	    value: function create(entityTypeId, items, scheme) {
	      var configItems = [];
	      items.forEach(function (item) {
	        configItems.push(new ConfigItem(item));
	      });
	      return new Config(entityTypeId, configItems, scheme);
	    }
	  }]);
	  return Config;
	}();

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }

	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<option value=\"", "\">", "</option>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-converter-category-selector ui-form ui-form-line\">\n\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t<div class=\"crm-converter-category-selector-label ui-form-label\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t<div class=\"crm-converter-category-selector-select ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t<select class=\"ui-ctl-element\"></select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _entityTypeId$2 = new WeakMap();

	var _entityId = new WeakMap();

	var _config = new WeakMap();

	var _params = new WeakMap();

	var _isProgress = new WeakMap();

	var _isSynchronisationAllowed = new WeakMap();

	var _fieldsSynchronizer = new WeakMap();

	var _request = new WeakSet();

	var _onRequestSuccess = new WeakSet();

	var _onRequestError = new WeakSet();

	var _collectAdditionalData = new WeakSet();

	var _getCategoryForEntityTypeId = new WeakSet();

	var _isNeedToLoadCategories = new WeakSet();

	var _showCategorySelector = new WeakSet();

	var _processRequiredAction = new WeakSet();

	var _getFieldsSynchronizer = new WeakSet();

	var _getMessage = new WeakSet();

	var _emitConvertedEvent = new WeakSet();

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var Converter = /*#__PURE__*/function () {
	  function Converter(_entityTypeId2, _config2, params) {
	    babelHelpers.classCallCheck(this, Converter);

	    _emitConvertedEvent.add(this);

	    _getMessage.add(this);

	    _getFieldsSynchronizer.add(this);

	    _processRequiredAction.add(this);

	    _showCategorySelector.add(this);

	    _isNeedToLoadCategories.add(this);

	    _getCategoryForEntityTypeId.add(this);

	    _collectAdditionalData.add(this);

	    _onRequestError.add(this);

	    _onRequestSuccess.add(this);

	    _request.add(this);

	    _entityTypeId$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _entityId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _config.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _params.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isProgress.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isSynchronisationAllowed.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _fieldsSynchronizer.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _entityTypeId$2, Number(_entityTypeId2));

	    if (_config2 instanceof Config) {
	      babelHelpers.classPrivateFieldSet(this, _config, _config2);
	    } else {
	      console.error('Config is invalid in Converter constructor. Expected instance of Config, got ' + babelHelpers.typeof(_config2));
	    }

	    babelHelpers.classPrivateFieldSet(this, _params, params !== null && params !== void 0 ? params : {});
	    babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	    babelHelpers.classPrivateFieldSet(this, _isSynchronisationAllowed, false);
	    babelHelpers.classPrivateFieldSet(this, _entityId, 0);
	  }

	  babelHelpers.createClass(Converter, [{
	    key: "getEntityTypeId",
	    value: function getEntityTypeId() {
	      return babelHelpers.classPrivateFieldGet(this, _entityTypeId$2);
	    }
	  }, {
	    key: "getConfig",
	    value: function getConfig() {
	      return babelHelpers.classPrivateFieldGet(this, _config);
	    }
	  }, {
	    key: "getServiceUrl",
	    value: function getServiceUrl() {
	      var serviceUrl = babelHelpers.classPrivateFieldGet(this, _params).serviceUrl;

	      if (!serviceUrl) {
	        return null;
	      }

	      var additionalParams = {
	        action: "convert"
	      };
	      this.getConfig().getItems().forEach(function (item) {
	        additionalParams[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.isActive() ? "Y" : "N";
	      });
	      return BX.util.add_url_param(serviceUrl, additionalParams);
	    }
	  }, {
	    key: "getOriginUrl",
	    value: function getOriginUrl() {
	      if (babelHelpers.classPrivateFieldGet(this, _params) && babelHelpers.classPrivateFieldGet(this, _params).hasOwnProperty("originUrl")) {
	        return String(babelHelpers.classPrivateFieldGet(this, _params).originUrl);
	      }

	      return null;
	    }
	  }, {
	    key: "isRedirectToDetailPageEnabled",
	    value: function isRedirectToDetailPageEnabled() {
	      if (babelHelpers.classPrivateFieldGet(this, _params) && babelHelpers.classPrivateFieldGet(this, _params).hasOwnProperty("isRedirectToDetailPageEnabled")) {
	        return babelHelpers.classPrivateFieldGet(this, _params).isRedirectToDetailPageEnabled;
	      }

	      return true;
	    }
	  }, {
	    key: "convert",
	    value: function convert(entityId, data) {
	      var _this = this;

	      babelHelpers.classPrivateFieldSet(this, _entityId, entityId);
	      this.data = data;
	      var schemeItem = babelHelpers.classPrivateFieldGet(this, _config).getScheme().getCurrentItem();

	      if (!schemeItem) {
	        console.error('Scheme is not found');
	        return;
	      }

	      _classPrivateMethodGet$1(this, _collectAdditionalData, _collectAdditionalData2).call(this, schemeItem).then(function (result) {
	        if (result.isCanceled) {
	          return;
	        }

	        _classPrivateMethodGet$1(_this, _request, _request2).call(_this);
	      }).catch(function (error) {
	        if (error) {
	          console.error(error);
	        }
	      });
	    }
	  }]);
	  return Converter;
	}();

	var _request2 = function _request2() {
	  var serviceUrl = this.getServiceUrl();

	  if (!serviceUrl) {
	    console.error('Convert endpoint is not specifier');
	    return;
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _isProgress)) {
	    console.error('Another request is in progress');
	    return;
	  }

	  babelHelpers.classPrivateFieldSet(this, _isProgress, true);
	  main_core.ajax({
	    url: serviceUrl,
	    method: "POST",
	    dataType: "json",
	    data: {
	      "MODE": "CONVERT",
	      "ENTITY_ID": babelHelpers.classPrivateFieldGet(this, _entityId),
	      "ENABLE_SYNCHRONIZATION": babelHelpers.classPrivateFieldGet(this, _isSynchronisationAllowed) ? "Y" : "N",
	      "ENABLE_REDIRECT_TO_SHOW": this.isRedirectToDetailPageEnabled ? "Y" : "N",
	      "CONFIG": this.getConfig().externalize(),
	      "CONTEXT": this.data,
	      "ORIGIN_URL": this.getOriginUrl()
	    },
	    onsuccess: _classPrivateMethodGet$1(this, _onRequestSuccess, _onRequestSuccess2).bind(this),
	    onfailure: _classPrivateMethodGet$1(this, _onRequestError, _onRequestError2).bind(this)
	  });
	};

	var _onRequestSuccess2 = function _onRequestSuccess2(response) {
	  // todo return promise
	  babelHelpers.classPrivateFieldSet(this, _isProgress, false);

	  if (response.ERROR) {
	    ui_dialogs_messagebox.MessageBox.alert(response.ERROR.MESSAGE || "Error during conversion");
	    return;
	  }

	  if (main_core.Type.isPlainObject(response.REQUIRED_ACTION)) {
	    return _classPrivateMethodGet$1(this, _processRequiredAction, _processRequiredAction2).call(this, response.REQUIRED_ACTION);
	  }

	  var data = main_core.Type.isPlainObject(response.DATA) ? response.DATA : {};

	  if (!data) {
	    return;
	  }

	  var redirectUrl = main_core.Type.isString(data.URL) ? data.URL : "";
	  var isRedirected = false;

	  if (data.IS_FINISHED && data.IS_FINISHED === "Y") {
	    this.data = {};
	    isRedirected = _classPrivateMethodGet$1(this, _emitConvertedEvent, _emitConvertedEvent2).call(this, redirectUrl);
	  }

	  if (redirectUrl !== "" && !isRedirected) {
	    BX.Crm.Page.open(redirectUrl);
	  } else if (!(isRedirected && window.top === window)) ;
	};

	var _onRequestError2 = function _onRequestError2(error) {
	  babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	  ui_dialogs_messagebox.MessageBox.alert(error);
	};

	var _collectAdditionalData2 = function _collectAdditionalData2(schemeItem) {
	  var _this2 = this;

	  var config = this.getConfig();
	  var promises = [];
	  schemeItem.getEntityTypeIds().forEach(function (entityTypeId) {
	    promises.push(function () {
	      return _classPrivateMethodGet$1(_this2, _getCategoryForEntityTypeId, _getCategoryForEntityTypeId2).call(_this2, entityTypeId);
	    });
	  });
	  var result = {
	    isCanceled: false
	  };

	  var promiseIterator = function promiseIterator(promises) {
	    var index = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	    return new Promise(function (resolve, reject) {
	      if (result.isCanceled || !promises[index]) {
	        resolve(result);
	        return;
	      }

	      promises[index]().then(function (categoryResult) {
	        if (categoryResult.isCanceled) {
	          result.isCanceled = true;
	        } else if (categoryResult.category) {
	          var entityTypeId = categoryResult.category.getEntityTypeId();
	          var configItem = config.getItemByEntityTypeId(entityTypeId);

	          if (!configItem) {
	            reject('Scheme is not correct: configItem is not found for ' + entityTypeId);
	            return;
	          }

	          var initData = configItem.getInitData();
	          initData.categoryId = categoryResult.category.getId();
	          configItem.setInitData(initData);
	        }

	        resolve(promiseIterator(promises, ++index));
	      });
	    });
	  };

	  return promiseIterator(promises);
	};

	var _getCategoryForEntityTypeId2 = function _getCategoryForEntityTypeId2(entityTypeId) {
	  var _this3 = this;

	  return new Promise(function (resolve, reject) {
	    var configItem = _this3.getConfig().getItemByEntityTypeId(entityTypeId);

	    if (!configItem) {
	      reject('Scheme is not correct: configItem is not found for ' + entityTypeId);
	      return;
	    }

	    if (_classPrivateMethodGet$1(_this3, _isNeedToLoadCategories, _isNeedToLoadCategories2).call(_this3, entityTypeId)) {
	      crm_categoryList.CategoryList.Instance.getItems(entityTypeId).then(function (categories) {
	        if (categories.length > 1) {
	          _classPrivateMethodGet$1(_this3, _showCategorySelector, _showCategorySelector2).call(_this3, categories, configItem.getTitle()).then(resolve).catch(reject);
	        } else {
	          resolve({
	            isCanceled: false,
	            category: categories[0]
	          });
	        }
	      }).catch(reject);
	    } else {
	      resolve({
	        isCanceled: false,
	        category: null
	      });
	    }
	  });
	};

	var _isNeedToLoadCategories2 = function _isNeedToLoadCategories2(entityTypeId) {
	  // todo pass isCategoriesEnabled from backend
	  return entityTypeId === BX.CrmEntityType.enumeration.deal || BX.CrmEntityType.isDynamicTypeByTypeId(entityTypeId);
	};

	var _showCategorySelector2 = function _showCategorySelector2(categories, title) {
	  return new Promise(function (resolve) {
	    var categorySelectorContent = main_core.Tag.render(_templateObject(), main_core.Loc.getMessage("CRM_COMMON_CATEGORY"));
	    var select = categorySelectorContent.querySelector('select');
	    categories.forEach(function (category) {
	      select.appendChild(main_core.Tag.render(_templateObject2(), category.getId(), main_core.Text.encode(category.getName())));
	    });
	    var popup = new main_popup.Popup({
	      titleBar: main_core.Loc.getMessage("CRM_CONVERSION_CATEGORY_SELECTOR_TITLE", {
	        '#ENTITY#': main_core.Text.encode(title)
	      }),
	      content: categorySelectorContent,
	      closeByEsc: true,
	      closeIcon: true,
	      buttons: [new ui_buttons.Button({
	        text: main_core.Loc.getMessage("CRM_COMMON_ACTION_SAVE"),
	        color: ui_buttons.ButtonColor.SUCCESS,
	        onclick: function onclick() {
	          var value = Array.from(select.selectedOptions)[0].value;
	          popup.destroy();

	          var _iterator = _createForOfIteratorHelper$2(categories),
	              _step;

	          try {
	            for (_iterator.s(); !(_step = _iterator.n()).done;) {
	              var category = _step.value;

	              if (category.getId() === Number(value)) {
	                resolve({
	                  category: category
	                });
	                return true;
	              }
	            }
	          } catch (err) {
	            _iterator.e(err);
	          } finally {
	            _iterator.f();
	          }

	          console.error('Selected category not found');
	          resolve({
	            isCanceled: true
	          });
	          return true;
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage("CRM_COMMON_ACTION_CANCEL"),
	        color: ui_buttons.ButtonColor.LIGHT,
	        onclick: function onclick() {
	          popup.destroy();
	          resolve({
	            isCanceled: true
	          });
	          return true;
	        }
	      })],
	      events: {
	        onClose: function onClose() {
	          resolve({
	            isCanceled: true
	          });
	        }
	      }
	    });
	    popup.show();
	  });
	};

	var _processRequiredAction2 = function _processRequiredAction2(action) {
	  var name = String(action.NAME);
	  var data = main_core.Type.isPlainObject(action.DATA) ? action.DATA : {};

	  if (name === "SYNCHRONIZE") {
	    var newConfig = null;

	    if (main_core.Type.isArray(data.CONFIG)) {
	      newConfig = data.CONFIG;
	    } else if (main_core.Type.isPlainObject(data.CONFIG)) {
	      newConfig = Object.values(data.CONFIG);
	    }

	    if (newConfig) {
	      babelHelpers.classPrivateFieldGet(this, _config).updateItems(newConfig);
	    }

	    _classPrivateMethodGet$1(this, _getFieldsSynchronizer, _getFieldsSynchronizer2).call(this, main_core.Type.isArray(data.FIELD_NAMES) ? data.FIELD_NAMES : []).show();

	    return;
	  }

	  if (name === "CORRECT") {
	    if (main_core.Type.isPlainObject(data.CHECK_ERRORS)) {
	      // todo this is actual for leads only.
	      // this.openEntityEditorDialog(
	      // 	{
	      // 		title: manager ? manager.getMessage("checkErrorTitle") : null,
	      // 		helpData: { text: manager.getMessage("checkErrorHelp"), code: manager.getMessage("checkErrorHelpArticleCode") },
	      // 		fieldNames: Object.keys(checkErrors),
	      // 		initData: BX.prop.getObject(data, "EDITOR_INIT_DATA", null),
	      // 		context: BX.prop.getObject(data, "CONTEXT", null)
	      // 	}
	      // );
	      return;
	    }
	  }
	};

	var _getFieldsSynchronizer2 = function _getFieldsSynchronizer2(fieldNames) {
	  var _this4 = this;

	  if (babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer)) {
	    babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer).setConfig(babelHelpers.classPrivateFieldGet(this, _config).externalize());
	    babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer).setFieldNames(fieldNames);
	    return babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer);
	  }

	  babelHelpers.classPrivateFieldSet(this, _fieldsSynchronizer, BX.CrmEntityFieldSynchronizationEditor.create("crm_converter_fields_synchronizer_" + this.getEntityTypeId(), {
	    config: babelHelpers.classPrivateFieldGet(this, _config).externalize(),
	    title: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "dialogTitle"),
	    fieldNames: fieldNames,
	    legend: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "syncEditorLegend"),
	    fieldListTitle: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "syncEditorFieldListTitle"),
	    entityListTitle: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "syncEditorEntityListTitle"),
	    continueButton: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "continueButton"),
	    cancelButton: _classPrivateMethodGet$1(this, _getMessage, _getMessage2).call(this, "cancelButton")
	  }));
	  babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer).addClosingListener(function (sender, args) {
	    if (!(main_core.Type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false)) {
	      return;
	    }

	    babelHelpers.classPrivateFieldSet(_this4, _isSynchronisationAllowed, true);
	    babelHelpers.classPrivateFieldGet(_this4, _config).updateItems(Object.values(babelHelpers.classPrivateFieldGet(_this4, _fieldsSynchronizer).getConfig()));

	    _classPrivateMethodGet$1(_this4, _request, _request2).call(_this4);
	  });
	  return babelHelpers.classPrivateFieldGet(this, _fieldsSynchronizer);
	};

	var _getMessage2 = function _getMessage2(phraseId) {
	  if (!babelHelpers.classPrivateFieldGet(this, _params).messages) {
	    babelHelpers.classPrivateFieldGet(this, _params).messages = {};
	  }

	  return babelHelpers.classPrivateFieldGet(this, _params).messages[phraseId] || phraseId;
	};

	var _emitConvertedEvent2 = function _emitConvertedEvent2(redirectUrl) {
	  var entityTypeId = this.getEntityTypeId();
	  var eventArgs = {
	    entityTypeId: entityTypeId,
	    entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
	    entityId: babelHelpers.classPrivateFieldGet(this, _entityId),
	    redirectUrl: redirectUrl,
	    isRedirected: false
	  };
	  var current = BX.Crm.Page.getTopSlider();

	  if (current) {
	    eventArgs["sliderUrl"] = current.getUrl();
	  }

	  BX.onCustomEvent(window, "Crm.EntityConverter.Converted", [this, eventArgs]);
	  BX.localStorage.set("onCrmEntityConvert", eventArgs, 10);
	  return eventArgs["isRedirected"];
	};

	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	/**
	 * @memberOf BX.Crm.Conversion
	 * @mixes EventEmitter
	 */

	var _entityId$1 = new WeakMap();

	var _container = new WeakMap();

	var _menuButton = new WeakMap();

	var _label = new WeakMap();

	var _converter = new WeakMap();

	var _menuId = new WeakMap();

	var _isAutoConversionEnabled = new WeakMap();

	var _initUI = new WeakSet();

	var _bindEvents = new WeakSet();

	var _handleContainerClick = new WeakSet();

	var _handleMenuButtonClick = new WeakSet();

	var _showMenu = new WeakSet();

	var _closeMenu = new WeakSet();

	var _getMenuItems = new WeakSet();

	var _handleItemClick = new WeakSet();

	var SchemeSelector = /*#__PURE__*/function () {
	  function SchemeSelector(converter, params) {
	    babelHelpers.classCallCheck(this, SchemeSelector);

	    _handleItemClick.add(this);

	    _getMenuItems.add(this);

	    _closeMenu.add(this);

	    _showMenu.add(this);

	    _handleMenuButtonClick.add(this);

	    _handleContainerClick.add(this);

	    _bindEvents.add(this);

	    _initUI.add(this);

	    _entityId$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _container.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _menuButton.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _label.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _converter.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _menuId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isAutoConversionEnabled.set(this, {
	      writable: true,
	      value: void 0
	    });

	    if (converter instanceof Converter) {
	      babelHelpers.classPrivateFieldSet(this, _converter, converter);
	    } else {
	      console.error('Converter is invalid in SchemeSelector constructor. Expected instance of Converter, got ' + babelHelpers.typeof(converter));
	    }

	    babelHelpers.classPrivateFieldSet(this, _entityId$1, Number(params.entityId));
	    babelHelpers.classPrivateFieldSet(this, _container, document.getElementById(params.containerId));
	    babelHelpers.classPrivateFieldSet(this, _menuButton, document.getElementById(params.buttonId));
	    babelHelpers.classPrivateFieldSet(this, _label, document.getElementById(params.labelId));
	    babelHelpers.classPrivateFieldSet(this, _menuId, 'crm_conversion_scheme_selector_' + babelHelpers.classPrivateFieldGet(this, _entityId$1) + '_' + main_core.Text.getRandom());
	    babelHelpers.classPrivateFieldSet(this, _isAutoConversionEnabled, false);

	    if (!babelHelpers.classPrivateFieldGet(this, _entityId$1) || !babelHelpers.classPrivateFieldGet(this, _container) || !babelHelpers.classPrivateFieldGet(this, _menuButton) || !babelHelpers.classPrivateFieldGet(this, _label) || !babelHelpers.classPrivateFieldGet(this, _converter)) {
	      console.error('Error SchemeSelector initializing', this);
	    } else {
	      _classPrivateMethodGet$2(this, _initUI, _initUI2).call(this);

	      _classPrivateMethodGet$2(this, _bindEvents, _bindEvents2).call(this);
	    }

	    main_core_events.EventEmitter.makeObservable(this, 'BX.Crm.Conversion');
	  }

	  babelHelpers.createClass(SchemeSelector, [{
	    key: "enableAutoConversion",
	    value: function enableAutoConversion() {
	      babelHelpers.classPrivateFieldSet(this, _isAutoConversionEnabled, true);
	    }
	  }, {
	    key: "disableAutoConversion",
	    value: function disableAutoConversion() {
	      babelHelpers.classPrivateFieldSet(this, _isAutoConversionEnabled, false);
	    }
	  }]);
	  return SchemeSelector;
	}();

	var _initUI2 = function _initUI2() {
	  var currentSchemeItem = babelHelpers.classPrivateFieldGet(this, _converter).getConfig().getScheme().getCurrentItem();

	  if (currentSchemeItem) {
	    babelHelpers.classPrivateFieldGet(this, _label).innerText = currentSchemeItem.getPhrase();
	  }
	};

	var _bindEvents2 = function _bindEvents2() {
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _container), "click", _classPrivateMethodGet$2(this, _handleContainerClick, _handleContainerClick2).bind(this));
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _menuButton), "click", _classPrivateMethodGet$2(this, _handleMenuButtonClick, _handleMenuButtonClick2).bind(this));
	};

	var _handleContainerClick2 = function _handleContainerClick2() {
	  var event = new main_core_events.BaseEvent({
	    data: {
	      isCanceled: false
	    }
	  });
	  this.emit('SchemeSelector:onContainerClick', event);
	  babelHelpers.classPrivateFieldGet(this, _converter).getConfig().updateFromSchemeItem();

	  if (babelHelpers.classPrivateFieldGet(this, _isAutoConversionEnabled) && !event.getData().isCanceled) {
	    babelHelpers.classPrivateFieldGet(this, _converter).convert(babelHelpers.classPrivateFieldGet(this, _entityId$1));
	  }
	};

	var _handleMenuButtonClick2 = function _handleMenuButtonClick2() {
	  _classPrivateMethodGet$2(this, _showMenu, _showMenu2).call(this);
	};

	var _showMenu2 = function _showMenu2() {
	  var anchorPos = BX.pos(babelHelpers.classPrivateFieldGet(this, _container));
	  main_popup.MenuManager.show({
	    id: babelHelpers.classPrivateFieldGet(this, _menuId),
	    bindElement: babelHelpers.classPrivateFieldGet(this, _menuButton),
	    items: _classPrivateMethodGet$2(this, _getMenuItems, _getMenuItems2).call(this),
	    closeByEsc: true,
	    cacheable: false,
	    offsetLeft: -anchorPos['width']
	  });
	};

	var _closeMenu2 = function _closeMenu2() {
	  main_popup.MenuManager.destroy(babelHelpers.classPrivateFieldGet(this, _menuId));
	};

	var _getMenuItems2 = function _getMenuItems2() {
	  var _this = this;

	  var items = [];
	  babelHelpers.classPrivateFieldGet(this, _converter).getConfig().getScheme().getItems().forEach(function (item) {
	    items.push({
	      text: main_core.Text.encode(item.getPhrase()),
	      onclick: function onclick() {
	        _classPrivateMethodGet$2(_this, _handleItemClick, _handleItemClick2).call(_this, item);
	      }
	    });
	  });
	  return items;
	};

	var _handleItemClick2 = function _handleItemClick2(item) {
	  _classPrivateMethodGet$2(this, _closeMenu, _closeMenu2).call(this);

	  babelHelpers.classPrivateFieldGet(this, _label).innerText = item.getPhrase();
	  babelHelpers.classPrivateFieldGet(this, _converter).getConfig().updateFromSchemeItem(item);
	  var event = new main_core_events.BaseEvent({
	    data: {
	      isCanceled: false
	    }
	  });
	  this.emit('SchemeSelector:onSchemeSelected', event);

	  if (babelHelpers.classPrivateFieldGet(this, _isAutoConversionEnabled) && !event.getData().isCanceled) {
	    babelHelpers.classPrivateFieldGet(this, _converter).convert(babelHelpers.classPrivateFieldGet(this, _entityId$1));
	  }
	};

	var instance = null;
	/**
	 * @memberOf BX.Crm.Conversion
	 */

	var _converters = new WeakMap();

	var Manager = /*#__PURE__*/function () {
	  function Manager() {
	    babelHelpers.classCallCheck(this, Manager);

	    _converters.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _converters, {});
	  }

	  babelHelpers.createClass(Manager, [{
	    key: "initializeConverter",
	    value: function initializeConverter(entityTypeId, params) {
	      var config = Config.create(entityTypeId, params.configItems, Scheme.create(params.scheme));
	      babelHelpers.classPrivateFieldGet(this, _converters)[entityTypeId] = new Converter(entityTypeId, config, params.params);
	      return babelHelpers.classPrivateFieldGet(this, _converters)[entityTypeId];
	    }
	  }, {
	    key: "getConverter",
	    value: function getConverter(entityTypeId) {
	      return babelHelpers.classPrivateFieldGet(this, _converters)[entityTypeId] || null;
	    }
	  }], [{
	    key: "Instance",
	    get: function get() {
	      if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.Conversion.Manager')) {
	        return window.top.BX.Crm.Conversion.Manager.Instance;
	      }

	      if (instance === null) {
	        instance = new Manager();
	      }

	      return instance;
	    }
	  }]);
	  return Manager;
	}();

	/**
	 * @memberOf BX.Crm
	 */

	var Conversion = {
	  Scheme: Scheme,
	  Config: Config,
	  Converter: Converter,
	  Manager: Manager,
	  SchemeSelector: SchemeSelector
	};

	exports.Conversion = Conversion;

}((this.BX.Crm = this.BX.Crm || {}),BX.Event,BX.Crm,BX.Crm.Models,BX.UI,BX.UI.Dialogs,BX.Main,BX,BX));
//# sourceMappingURL=conversion.bundle.js.map
