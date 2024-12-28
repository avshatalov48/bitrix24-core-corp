/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_counterpanel,main_core,main_core_events) {
	'use strict';

	var EntityCounterType = function EntityCounterType() {
	  babelHelpers.classCallCheck(this, EntityCounterType);
	};
	babelHelpers.defineProperty(EntityCounterType, "UNDEFINED", 0);
	babelHelpers.defineProperty(EntityCounterType, "IDLE", 1);
	babelHelpers.defineProperty(EntityCounterType, "PENDING", 2);
	babelHelpers.defineProperty(EntityCounterType, "OVERDUE", 4);
	babelHelpers.defineProperty(EntityCounterType, "INCOMING_CHANNEL", 8);
	babelHelpers.defineProperty(EntityCounterType, "READY_TODO", 16);
	babelHelpers.defineProperty(EntityCounterType, "CURRENT", 20);
	babelHelpers.defineProperty(EntityCounterType, "ALL_DEADLINE_BASED", 7);
	babelHelpers.defineProperty(EntityCounterType, "ALL", 31);
	babelHelpers.defineProperty(EntityCounterType, "IDLE_NAME", 'IDLE');
	babelHelpers.defineProperty(EntityCounterType, "PENDING_NAME", 'PENDING');
	babelHelpers.defineProperty(EntityCounterType, "OVERDUE_NAME", 'OVERDUE');
	babelHelpers.defineProperty(EntityCounterType, "READY_TODO_NAME", 'READYTODO');
	babelHelpers.defineProperty(EntityCounterType, "CURRENT_NAME", 'CURRENT');
	babelHelpers.defineProperty(EntityCounterType, "INCOMING_CHANNEL_NAME", 'INCOMINGCHANNEL');
	babelHelpers.defineProperty(EntityCounterType, "ALL_DEADLINE_BASED_NAME", 'ALLDEADLINEBASED');
	babelHelpers.defineProperty(EntityCounterType, "ALL_NAME", 'ALL');

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _filterManager = /*#__PURE__*/new WeakMap();
	var _fields = /*#__PURE__*/new WeakMap();
	var _isActive = /*#__PURE__*/new WeakMap();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var _onFilterApply = /*#__PURE__*/new WeakSet();
	var _isFilteredByField = /*#__PURE__*/new WeakSet();
	var EntityCounterFilterManager = /*#__PURE__*/function () {
	  function EntityCounterFilterManager() {
	    babelHelpers.classCallCheck(this, EntityCounterFilterManager);
	    _classPrivateMethodInitSpec(this, _isFilteredByField);
	    _classPrivateMethodInitSpec(this, _onFilterApply);
	    _classPrivateMethodInitSpec(this, _bindEvents);
	    _classPrivateFieldInitSpec(this, _filterManager, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _fields, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isActive, {
	      writable: true,
	      value: true
	    });
	    var filters = main_core.Type.isObject(BX.Main.filterManager) && BX.Main.filterManager.hasOwnProperty('getList') ? BX.Main.filterManager.getList() : Object.values(BX.Main.filterManager.data);
	    if (filters.length === 0) {
	      console.warn('BX.Crm.EntityCounterFilter: Unable to define filter.');
	      babelHelpers.classPrivateFieldSet(this, _isActive, false);
	    } else {
	      babelHelpers.classPrivateFieldSet(this, _filterManager, filters[0]); // use first filter to work
	      _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	      this.updateFields();
	    }
	  }
	  babelHelpers.createClass(EntityCounterFilterManager, [{
	    key: "getManager",
	    value: function getManager() {
	      return babelHelpers.classPrivateFieldGet(this, _filterManager);
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return babelHelpers.classPrivateFieldGet(this, _isActive);
	    }
	  }, {
	    key: "getFields",
	    value: function getFields() {
	      var _this = this;
	      var isFilterEmpty = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      if (isFilterEmpty) {
	        var filtered = Object.entries(babelHelpers.classPrivateFieldGet(this, _fields)).filter(function (_ref) {
	          var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            field = _ref2[0],
	            value = _ref2[1];
	          return _classPrivateMethodGet(_this, _isFilteredByField, _isFilteredByField2).call(_this, field);
	        });
	        return Object.fromEntries(filtered);
	      }
	      return babelHelpers.classPrivateFieldGet(this, _fields);
	    }
	  }, {
	    key: "getApi",
	    value: function getApi() {
	      return babelHelpers.classPrivateFieldGet(this, _filterManager).getApi();
	    }
	  }, {
	    key: "updateFields",
	    value: function updateFields() {
	      babelHelpers.classPrivateFieldSet(this, _fields, babelHelpers.classPrivateFieldGet(this, _filterManager).getFilterFieldsValues());
	    }
	  }, {
	    key: "isFilteredByFieldEx",
	    value: function isFilteredByFieldEx(field) {
	      if (!Object.keys(babelHelpers.classPrivateFieldGet(this, _fields)).includes(field) || field.endsWith('_datesel') || field.endsWith('_numsel') || field.endsWith('_label')) {
	        return false;
	      }
	      return _classPrivateMethodGet(this, _isFilteredByField, _isFilteredByField2).call(this, field);
	    }
	  }, {
	    key: "isFiltered",
	    value: function isFiltered(userId, typeId, entityTypeId, isOtherUsersFilter, counterUserFieldName) {
	      var _this2 = this;
	      if (userId === 0 || typeId === EntityCounterType.UNDEFINED) {
	        return false;
	      }
	      var isFilteredByUser = this.isFilteredByFieldEx(counterUserFieldName) && main_core.Type.isArray(babelHelpers.classPrivateFieldGet(this, _fields)[counterUserFieldName]) && babelHelpers.classPrivateFieldGet(this, _fields)[counterUserFieldName].length === 1 && (isOtherUsersFilter ? babelHelpers.classPrivateFieldGet(this, _fields)[counterUserFieldName][0] === EntityCounterFilterManager.FILTER_OTHER_USERS : parseInt(babelHelpers.classPrivateFieldGet(this, _fields)[counterUserFieldName][0], 10) === userId);
	      var hasFilteredByTypeValue = this.isFilteredByFieldEx(EntityCounterFilterManager.COUNTER_TYPE_FIELD) && main_core.Type.isObject(babelHelpers.classPrivateFieldGet(this, _fields)[EntityCounterFilterManager.COUNTER_TYPE_FIELD]);
	      var filteredTypeValues = hasFilteredByTypeValue ? Object.values(babelHelpers.classPrivateFieldGet(this, _fields)[EntityCounterFilterManager.COUNTER_TYPE_FIELD]).map(function (item) {
	        return parseInt(item, 10);
	      }).sort() : [];
	      var isFilteredByType = filteredTypeValues.length === 1 && filteredTypeValues[0] === typeId || filteredTypeValues.length === 2 && typeId === EntityCounterType.CURRENT && filteredTypeValues[0] === EntityCounterType.READY_TODO && filteredTypeValues[1] === EntityCounterType.OVERDUE;
	      var counterFields = [counterUserFieldName, EntityCounterFilterManager.COUNTER_TYPE_FIELD].concat(babelHelpers.toConsumableArray(EntityCounterFilterManager.EXCLUDED_FIELDS));
	      var keysFields = Object.keys(babelHelpers.classPrivateFieldGet(this, _fields));
	      var otherFields = counterFields.filter(function (item) {
	        return !keysFields.includes(item);
	      }).concat(keysFields.filter(function (x) {
	        return !counterFields.includes(x);
	      })); // exclude checked fields
	      var isOtherFilterUsed = otherFields.some(function (item) {
	        return _this2.isFilteredByFieldEx(item);
	      });
	      return isFilteredByUser && isFilteredByType && !isOtherFilterUsed;
	    }
	  }]);
	  return EntityCounterFilterManager;
	}();
	function _bindEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', _classPrivateMethodGet(this, _onFilterApply, _onFilterApply2).bind(this));
	}
	function _onFilterApply2() {
	  this.updateFields();
	}
	function _isFilteredByField2(field) {
	  if (main_core.Type.isArray(babelHelpers.classPrivateFieldGet(this, _fields)[field])) {
	    return babelHelpers.classPrivateFieldGet(this, _fields)[field].length > 0;
	  }
	  if (main_core.Type.isObject(babelHelpers.classPrivateFieldGet(this, _fields)[field])) {
	    return Object.values(babelHelpers.classPrivateFieldGet(this, _fields)[field]).length > 0;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _fields)[field] !== '';
	}
	babelHelpers.defineProperty(EntityCounterFilterManager, "COUNTER_TYPE_FIELD", 'ACTIVITY_COUNTER');
	babelHelpers.defineProperty(EntityCounterFilterManager, "EXCLUDED_FIELDS", ['FIND']);
	babelHelpers.defineProperty(EntityCounterFilterManager, "FILTER_OTHER_USERS", 'other-users');

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _id = /*#__PURE__*/new WeakMap();
	var _entityTypeId = /*#__PURE__*/new WeakMap();
	var _codes = /*#__PURE__*/new WeakMap();
	var _extras = /*#__PURE__*/new WeakMap();
	var _withExcludeUsers = /*#__PURE__*/new WeakMap();
	var _counterData = /*#__PURE__*/new WeakMap();
	var _isRequestRunning = /*#__PURE__*/new WeakMap();
	var _lastPullEventData = /*#__PURE__*/new WeakMap();
	var _isTabActive = /*#__PURE__*/new WeakMap();
	var _openedSlidersCount = /*#__PURE__*/new WeakMap();
	var _bindEvents$1 = /*#__PURE__*/new WeakSet();
	var _onPullEvent = /*#__PURE__*/new WeakSet();
	var _tryRecalculate = /*#__PURE__*/new WeakSet();
	var _startRecalculationRequest = /*#__PURE__*/new WeakSet();
	var _onRecalculationSuccess = /*#__PURE__*/new WeakSet();
	var _fetchCounterData = /*#__PURE__*/new WeakSet();
	var _isRecalculationRequired = /*#__PURE__*/new WeakSet();
	var EntityCounterManager = /*#__PURE__*/function () {
	  function EntityCounterManager(options) {
	    babelHelpers.classCallCheck(this, EntityCounterManager);
	    _classPrivateMethodInitSpec$1(this, _isRecalculationRequired);
	    _classPrivateMethodInitSpec$1(this, _fetchCounterData);
	    _classPrivateMethodInitSpec$1(this, _onRecalculationSuccess);
	    _classPrivateMethodInitSpec$1(this, _startRecalculationRequest);
	    _classPrivateMethodInitSpec$1(this, _tryRecalculate);
	    _classPrivateMethodInitSpec$1(this, _onPullEvent);
	    _classPrivateMethodInitSpec$1(this, _bindEvents$1);
	    _classPrivateFieldInitSpec$1(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _codes, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _extras, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _withExcludeUsers, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$1(this, _counterData, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _isRequestRunning, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _lastPullEventData, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _isTabActive, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _openedSlidersCount, {
	      writable: true,
	      value: void 0
	    });
	    if (!main_core.Type.isPlainObject(options)) {
	      throw new TypeError('BX.Crm.EntityCounterManager: The "options" argument must be object.');
	    }
	    babelHelpers.classPrivateFieldSet(this, _id, main_core.Type.isString(options.id) ? options.id : '');
	    if (babelHelpers.classPrivateFieldGet(this, _id) === '') {
	      throw new RangeError('BX.Crm.EntityCounterManager: The "id" argument must be specified.');
	    }
	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, options.entityTypeId ? main_core.Text.toInteger(options.entityTypeId) : 0);
	    babelHelpers.classPrivateFieldSet(this, _codes, main_core.Type.isArray(options.codes) ? options.codes : []);
	    babelHelpers.classPrivateFieldSet(this, _extras, main_core.Type.isObject(options.extras) ? options.extras : {});
	    babelHelpers.classPrivateFieldSet(this, _withExcludeUsers, main_core.Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false);
	    babelHelpers.classPrivateFieldSet(this, _counterData, {});
	    babelHelpers.classPrivateFieldSet(this, _isTabActive, true);
	    babelHelpers.classPrivateFieldSet(this, _openedSlidersCount, 0);
	    _classPrivateMethodGet$1(this, _bindEvents$1, _bindEvents2$1).call(this);
	    this.constructor.lastInstance = this;
	  }
	  babelHelpers.createClass(EntityCounterManager, [{
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _id);
	    }
	  }, {
	    key: "getCounterData",
	    value: function getCounterData() {
	      return babelHelpers.classPrivateFieldGet(this, _counterData);
	    }
	  }, {
	    key: "setCounterData",
	    value: function setCounterData(data) {
	      babelHelpers.classPrivateFieldSet(this, _counterData, data);
	    }
	  }], [{
	    key: "getLastInstance",
	    value: function getLastInstance() {
	      return this.lastInstance;
	    }
	  }]);
	  return EntityCounterManager;
	}();
	function _bindEvents2$1() {
	  var _this = this;
	  main_core_events.EventEmitter.subscribe('onPullEvent-main', main_core.Runtime.debounce(_classPrivateMethodGet$1(this, _onPullEvent, _onPullEvent2), 3000, this));
	  main_core.Event.ready(function () {
	    main_core.Event.bind(document, 'visibilitychange', function () {
	      babelHelpers.classPrivateFieldSet(_this, _isTabActive, document.visibilityState === 'visible');
	      if (babelHelpers.classPrivateFieldGet(_this, _isTabActive) && _classPrivateMethodGet$1(_this, _isRecalculationRequired, _isRecalculationRequired2).call(_this)) {
	        _classPrivateMethodGet$1(_this, _tryRecalculate, _tryRecalculate2).call(_this, babelHelpers.classPrivateFieldGet(_this, _lastPullEventData));
	      }
	    });
	  });
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpen', function () {
	    var _this$openedSlidersCo, _this$openedSlidersCo2;
	    babelHelpers.classPrivateFieldSet(_this, _openedSlidersCount, (_this$openedSlidersCo = babelHelpers.classPrivateFieldGet(_this, _openedSlidersCount), _this$openedSlidersCo2 = _this$openedSlidersCo++, _this$openedSlidersCo)), _this$openedSlidersCo2;
	    babelHelpers.classPrivateFieldSet(_this, _isTabActive, false);
	  });
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function () {
	    var _this$openedSlidersCo3, _this$openedSlidersCo4;
	    babelHelpers.classPrivateFieldSet(_this, _openedSlidersCount, (_this$openedSlidersCo3 = babelHelpers.classPrivateFieldGet(_this, _openedSlidersCount), _this$openedSlidersCo4 = _this$openedSlidersCo3--, _this$openedSlidersCo3)), _this$openedSlidersCo4;
	    if (babelHelpers.classPrivateFieldGet(_this, _openedSlidersCount) <= 0) {
	      babelHelpers.classPrivateFieldSet(_this, _openedSlidersCount, 0);
	      babelHelpers.classPrivateFieldSet(_this, _isTabActive, true);
	      if (_classPrivateMethodGet$1(_this, _isRecalculationRequired, _isRecalculationRequired2).call(_this)) {
	        _classPrivateMethodGet$1(_this, _tryRecalculate, _tryRecalculate2).call(_this, babelHelpers.classPrivateFieldGet(_this, _lastPullEventData));
	      }
	    }
	  });
	}
	function _onPullEvent2(event) {
	  var _event$getData = event.getData(),
	    _event$getData2 = babelHelpers.slicedToArray(_event$getData, 2),
	    command = _event$getData2[0],
	    params = _event$getData2[1];
	  if (command !== 'user_counter') {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _lastPullEventData, params);
	  if (!babelHelpers.classPrivateFieldGet(this, _isTabActive)) {
	    return;
	  }
	  _classPrivateMethodGet$1(this, _tryRecalculate, _tryRecalculate2).call(this, params);
	}
	function _tryRecalculate2(params) {
	  var enableRecalculation = false;
	  var enableRecalculationWithRequest = false;
	  var counterData = _classPrivateMethodGet$1(this, _fetchCounterData, _fetchCounterData2).call(this, params);

	  // eslint-disable-next-line no-restricted-syntax
	  for (var counterId in counterData) {
	    if (!Object.hasOwn(counterData, counterId) || !babelHelpers.classPrivateFieldGet(this, _codes).includes(counterId)) {
	      continue;
	    }
	    var counterValue = BX.prop.getInteger(counterData, counterId, 0);
	    if (counterValue < 0) {
	      enableRecalculationWithRequest = true;
	      break;
	    }
	    var currentCounterValue = BX.prop.getInteger(babelHelpers.classPrivateFieldGet(this, _counterData), counterId, 0);
	    if (currentCounterValue !== counterValue) {
	      enableRecalculation = true;

	      // update counter data
	      babelHelpers.classPrivateFieldGet(this, _counterData)[counterId] = counterValue;
	    }
	  }
	  if (enableRecalculationWithRequest) {
	    _classPrivateMethodGet$1(this, _startRecalculationRequest, _startRecalculationRequest2).call(this);
	  }
	  if (enableRecalculation) {
	    main_core_events.EventEmitter.emit(this, 'BX.Crm.EntityCounterManager:onRecalculate');
	  }
	}
	function _startRecalculationRequest2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isRequestRunning)) {
	    return;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _isTabActive)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _isRequestRunning, true);
	  var data = {
	    entityTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	    extras: babelHelpers.classPrivateFieldGet(this, _extras),
	    withExcludeUsers: babelHelpers.classPrivateFieldGet(this, _withExcludeUsers) ? 1 : 0
	  };
	  void main_core.ajax.runAction('crm.counter.list', {
	    data: data
	  }).then(_classPrivateMethodGet$1(this, _onRecalculationSuccess, _onRecalculationSuccess2).bind(this));
	}
	function _onRecalculationSuccess2(result) {
	  babelHelpers.classPrivateFieldSet(this, _isRequestRunning, false);
	  var data = main_core.Type.isPlainObject(result.data) ? result.data : null;
	  if (data === null) {
	    return;
	  }
	  this.setCounterData(data);
	  main_core_events.EventEmitter.emit('BX.Crm.EntityCounterManager:onRecalculate', this);
	}
	function _fetchCounterData2(params) {
	  var currentSiteId = main_core.Loc.getMessage('SITE_ID');
	  return main_core.Type.isPlainObject(params[currentSiteId]) ? params[currentSiteId] : {};
	}
	function _isRecalculationRequired2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _lastPullEventData)) {
	    return false;
	  }
	  var counterData = _classPrivateMethodGet$1(this, _fetchCounterData, _fetchCounterData2).call(this, babelHelpers.classPrivateFieldGet(this, _lastPullEventData));
	  return Object.values(counterData).includes(-1);
	}
	babelHelpers.defineProperty(EntityCounterManager, "lastInstance", null);

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var _id$1 = /*#__PURE__*/new WeakMap();
	var _entityTypeId$1 = /*#__PURE__*/new WeakMap();
	var _entityTypeName = /*#__PURE__*/new WeakMap();
	var _userId = /*#__PURE__*/new WeakMap();
	var _userName = /*#__PURE__*/new WeakMap();
	var _codes$1 = /*#__PURE__*/new WeakMap();
	var _data = /*#__PURE__*/new WeakMap();
	var _counterManager = /*#__PURE__*/new WeakMap();
	var _filterManager$1 = /*#__PURE__*/new WeakMap();
	var _filterLastPresetId = /*#__PURE__*/new WeakMap();
	var _filterLastPreset = /*#__PURE__*/new WeakMap();
	var _filterResponsibleFiledName = /*#__PURE__*/new WeakMap();
	var _bindEvents$2 = /*#__PURE__*/new WeakSet();
	var _onActivateItem = /*#__PURE__*/new WeakSet();
	var _onDeactivateItem = /*#__PURE__*/new WeakSet();
	var _onFilterApply$1 = /*#__PURE__*/new WeakSet();
	var _onRecalculate = /*#__PURE__*/new WeakSet();
	var _processItemSelection = /*#__PURE__*/new WeakSet();
	var _makeFilterAnalyticsLabel = /*#__PURE__*/new WeakSet();
	var _prepareFilterTypeId = /*#__PURE__*/new WeakSet();
	var _markCounters = /*#__PURE__*/new WeakSet();
	var _isAllDeactivated = /*#__PURE__*/new WeakSet();
	var _deactivateLinkedMenuItem = /*#__PURE__*/new WeakSet();
	var _getParentItemTotalValue = /*#__PURE__*/new WeakSet();
	var EntityCounterPanel = /*#__PURE__*/function (_CounterPanel) {
	  babelHelpers.inherits(EntityCounterPanel, _CounterPanel);
	  function EntityCounterPanel(_options) {
	    var _this;
	    babelHelpers.classCallCheck(this, EntityCounterPanel);
	    if (!main_core.Type.isPlainObject(_options)) {
	      throw 'BX.Crm.EntityCounterPanel: The "options" argument must be object.';
	    }
	    var _data2 = main_core.Type.isPlainObject(_options.data) ? _options.data : {};
	    var withExcludeUsers = main_core.Type.isBoolean(_options.withExcludeUsers) ? _options.withExcludeUsers : false;
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityCounterPanel).call(this, {
	      target: BX(_options.id),
	      items: EntityCounterPanel.getCounterItems(_data2, _options),
	      multiselect: false,
	      // disable multiselect for CRM counters
	      title: main_core.Loc.getMessage('NEW_CRM_COUNTER_TITLE_MY')
	    }));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getParentItemTotalValue);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _deactivateLinkedMenuItem);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _isAllDeactivated);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _markCounters);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _prepareFilterTypeId);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _makeFilterAnalyticsLabel);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _processItemSelection);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _onRecalculate);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _onFilterApply$1);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _onDeactivateItem);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _onActivateItem);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _bindEvents$2);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _id$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _entityTypeId$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _entityTypeName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _userId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _userName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _codes$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _data, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _counterManager, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _filterManager$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _filterLastPresetId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _filterLastPreset, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _filterResponsibleFiledName, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id$1, _options.id);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _entityTypeId$1, _options.entityTypeId ? main_core.Text.toInteger(_options.entityTypeId) : 0);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _entityTypeName, _options.entityTypeName);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _userId, _options.userId ? main_core.Text.toInteger(_options.userId) : 0);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _userName, main_core.Type.isStringFilled(_options.userName) ? _options.userName : babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _userId));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _codes$1, main_core.Type.isArray(_options.codes) ? _options.codes : []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _data, _data2);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _filterResponsibleFiledName, _options.filterResponsibleFiledName);
	    if (BX.CrmEntityType.isDefined(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _entityTypeId$1))) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _counterManager, new EntityCounterManager({
	        id: babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _id$1),
	        entityTypeId: babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _entityTypeId$1),
	        codes: babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _codes$1),
	        extras: main_core.Type.isObject(_options.extras) ? _options.extras : {},
	        withExcludeUsers: withExcludeUsers
	      }));
	    }
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _filterManager$1, new EntityCounterFilterManager());
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _filterLastPresetId, _options.filterLastPresetId);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _filterLastPreset, main_core.Type.isArray(_options.filterLastPresetData) ? JSON.parse(_options.filterLastPresetData[0]) : {
	      presetId: null
	    });
	    _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _bindEvents$2, _bindEvents2$2).call(babelHelpers.assertThisInitialized(_this), _options);
	    return _this;
	  }
	  babelHelpers.createClass(EntityCounterPanel, [{
	    key: "init",
	    value: function init() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityCounterPanel.prototype), "init", this).call(this);
	      _classPrivateMethodGet$2(this, _markCounters, _markCounters2).call(this);
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _id$1);
	    }
	  }], [{
	    key: "getCounterItems",
	    value: function getCounterItems(input, options) {
	      var withExcludeUsers = main_core.Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false;
	      var isRestricted = main_core.Type.isStringFilled(options.lockedCallback);
	      var parentItemId = EntityCounterPanel.getMenuParentItemId(main_core.Type.isArray(options.codes) ? options.codes : []);
	      var otherUsersItems = [];
	      if (withExcludeUsers && !main_core.Type.isUndefined(parentItemId)) {
	        var parentTotal = 0;
	        otherUsersItems = Object.entries(input).map(function (_ref) {
	          var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            code = _ref2[0],
	            item = _ref2[1];
	          if (code.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX)) {
	            var value = parseInt(item.VALUE, 10);
	            parentTotal += value;
	            var color = EntityCounterPanel.detectCounterItemColor(item.TYPE_NAME, value);
	            return {
	              id: code,
	              title: main_core.Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER_' + item.TYPE_NAME),
	              value: {
	                value: value,
	                order: -1
	              },
	              color: color === 'THEME' ? 'GRAY' : color,
	              // override color to correct display on different themes
	              parentId: parentItemId
	            };
	          }
	        }, this);

	        // add parent item
	        otherUsersItems = [{
	          id: parentItemId,
	          title: main_core.Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER_TITLE'),
	          value: {
	            value: parentTotal,
	            order: -1
	          },
	          isRestricted: isRestricted,
	          color: 'THEME'
	        }].concat(otherUsersItems);
	      }
	      var currentUserItems = Object.entries(input).map(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	          code = _ref4[0],
	          item = _ref4[1];
	        if (!code.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX)) {
	          var value = parseInt(item.VALUE, 10);
	          return {
	            id: code,
	            title: main_core.Loc.getMessage('NEW_CRM_COUNTER_TYPE_' + item.TYPE_NAME),
	            value: value,
	            isRestricted: isRestricted,
	            color: EntityCounterPanel.detectCounterItemColor(item.TYPE_NAME, value)
	          };
	        }
	      }, this);
	      return currentUserItems.concat(otherUsersItems).filter(function (item) {
	        return main_core.Type.isObject(item);
	      });
	    }
	  }, {
	    key: "getMenuParentItemId",
	    value: function getMenuParentItemId(codes) {
	      return codes.find(function (element) {
	        return element.endsWith(EntityCounterPanel.EXCLUDE_ALL_USERS_CODE_SUFFIX);
	      });
	    }
	  }, {
	    key: "detectCounterItemColor",
	    value: function detectCounterItemColor(type, value) {
	      var isRedCounter = [EntityCounterType.IDLE_NAME, EntityCounterType.OVERDUE_NAME, EntityCounterType.CURRENT_NAME].includes(type);
	      var isGreenCounter = [EntityCounterType.INCOMING_CHANNEL_NAME].includes(type);
	      return value > 0 ? isRedCounter ? 'DANGER' : isGreenCounter ? 'SUCCESS' : 'THEME' : 'THEME';
	    }
	  }]);
	  return EntityCounterPanel;
	}(ui_counterpanel.CounterPanel);
	function _bindEvents2$2(options) {
	  if (main_core.Type.isStringFilled(options.lockedCallback)) {
	    BX.bind(BX(options.id), 'click', function () {
	      eval(options.lockedCallback);
	    });
	  } else {
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', _classPrivateMethodGet$2(this, _onActivateItem, _onActivateItem2).bind(this));
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', _classPrivateMethodGet$2(this, _onDeactivateItem, _onDeactivateItem2).bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', _classPrivateMethodGet$2(this, _onFilterApply$1, _onFilterApply2$1).bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Crm.EntityCounterManager:onRecalculate', _classPrivateMethodGet$2(this, _onRecalculate, _onRecalculate2).bind(this));
	  }
	}
	function _onActivateItem2(event) {
	  var item = event.getData();
	  if (!_classPrivateMethodGet$2(this, _processItemSelection, _processItemSelection2).call(this, item)) {
	    return event.preventDefault();
	  }
	}
	function _onDeactivateItem2(event) {
	  _classPrivateMethodGet$2(this, _deactivateLinkedMenuItem, _deactivateLinkedMenuItem2).call(this, event.getData());
	  if (_classPrivateMethodGet$2(this, _isAllDeactivated, _isAllDeactivated2).call(this) && babelHelpers.classPrivateFieldGet(this, _filterManager$1).isActive()) {
	    var api = babelHelpers.classPrivateFieldGet(this, _filterManager$1).getApi();
	    if (babelHelpers.classPrivateFieldGet(this, _filterLastPreset).presetId === 'tmp_filter') {
	      api.setFields(babelHelpers.classPrivateFieldGet(this, _filterLastPreset).fields);
	      api.apply();
	    } else {
	      api.setFilter({
	        preset_id: babelHelpers.classPrivateFieldGet(this, _filterLastPreset).presetId
	      });
	    }
	  }
	}
	function _onFilterApply2$1() {
	  if (babelHelpers.classPrivateFieldGet(this, _filterManager$1).isActive()) {
	    babelHelpers.classPrivateFieldGet(this, _filterManager$1).updateFields();
	  }
	  _classPrivateMethodGet$2(this, _markCounters, _markCounters2).call(this);
	}
	function _onRecalculate2() {
	  var data = babelHelpers.classPrivateFieldGet(this, _counterManager).getCounterData();
	  var parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(babelHelpers.classPrivateFieldGet(this, _codes$1)));
	  for (var code in data) {
	    if (!data.hasOwnProperty(code) || !(code.indexOf('crm') === 0 && data[code] >= 0) // HACK: Skip of CRM counter reset
	    || !babelHelpers.classPrivateFieldGet(this, _data).hasOwnProperty(code) || main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _data)[code].VALUE) === main_core.Text.toNumber(data[code])) {
	      continue;
	    }
	    babelHelpers.classPrivateFieldGet(this, _data)[code].VALUE = data[code];
	    var item = this.getItemById(code);
	    item.updateValue(main_core.Text.toNumber(data[code]));
	    item.updateColor(EntityCounterPanel.detectCounterItemColor(babelHelpers.classPrivateFieldGet(this, _data)[code].TYPE_NAME, main_core.Text.toNumber(data[code])));
	  }
	  if (parentItem) {
	    parentItem.updateValue(_classPrivateMethodGet$2(this, _getParentItemTotalValue, _getParentItemTotalValue2).call(this));
	  }
	}
	function _processItemSelection2(item) {
	  var isOtherUsersFilter = item.id.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX);
	  var typeId = parseInt(babelHelpers.classPrivateFieldGet(this, _data)[item.id].TYPE_ID, 10);
	  if (typeId > 0) {
	    if (babelHelpers.classPrivateFieldGet(this, _filterManager$1).isActive()) {
	      var _objectSpread2;
	      var filteredFields = babelHelpers.classPrivateFieldGet(this, _filterManager$1).getFields(true);
	      if (typeof filteredFields[EntityCounterFilterManager.COUNTER_TYPE_FIELD] === 'undefined') {
	        babelHelpers.classPrivateFieldGet(this, _filterLastPreset).presetId = babelHelpers.classPrivateFieldGet(this, _filterManager$1).getApi().parent.getPreset().getCurrentPresetId();
	        if (babelHelpers.classPrivateFieldGet(this, _filterLastPreset).presetId === 'tmp_filter') {
	          babelHelpers.classPrivateFieldGet(this, _filterLastPreset).fields = filteredFields;
	        }
	        BX.userOptions.save('crm', babelHelpers.classPrivateFieldGet(this, _filterLastPresetId), '', JSON.stringify(babelHelpers.classPrivateFieldGet(this, _filterLastPreset)));
	      }
	      var userId = isOtherUsersFilter ? EntityCounterFilterManager.FILTER_OTHER_USERS : babelHelpers.classPrivateFieldGet(this, _userId).toString();
	      var userName = isOtherUsersFilter ? main_core.Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER') : babelHelpers.classPrivateFieldGet(this, _userName);
	      var counterTypeId = _classPrivateMethodGet$2(this, _prepareFilterTypeId, _prepareFilterTypeId2).call(this, typeId);
	      var api = babelHelpers.classPrivateFieldGet(this, _filterManager$1).getApi();
	      var fields = {
	        "ACTIVITY_COUNTER": BX.Type.isPlainObject(counterTypeId) ? counterTypeId : {
	          0: counterTypeId
	        }
	      };
	      var responsibleField = babelHelpers.classPrivateFieldGet(this, _filterResponsibleFiledName);
	      fields = _objectSpread(_objectSpread({}, fields), {}, (_objectSpread2 = {}, babelHelpers.defineProperty(_objectSpread2, responsibleField, {
	        0: userId
	      }), babelHelpers.defineProperty(_objectSpread2, responsibleField + '_label', [userName]), _objectSpread2));
	      api.setFields(fields);
	      api.apply({
	        'COUNTER': _classPrivateMethodGet$2(this, _makeFilterAnalyticsLabel, _makeFilterAnalyticsLabel2).call(this, counterTypeId)
	      });
	    } else {
	      return false;
	    }
	  }
	  return true;
	}
	function _makeFilterAnalyticsLabel2(counterTypeId) {
	  if (babelHelpers.classPrivateFieldGet(this, _entityTypeName) && counterTypeId) {
	    return 'CRM_' + babelHelpers.classPrivateFieldGet(this, _entityTypeName) + '_COUNTER_TYPE_' + counterTypeId;
	  }
	  return '';
	}
	function _prepareFilterTypeId2(typeId) {
	  if (typeId === EntityCounterType.CURRENT) {
	    return {
	      0: EntityCounterType.OVERDUE.toString(),
	      1: EntityCounterType.READY_TODO.toString()
	    };
	  }
	  return typeId.toString();
	}
	function _markCounters2() {
	  var _this2 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _filterManager$1).isActive()) {
	    return;
	  }
	  var parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(babelHelpers.classPrivateFieldGet(this, _codes$1)));
	  var isOtherUsersFilterUse = false;
	  Object.entries(babelHelpers.classPrivateFieldGet(this, _data)).forEach(function (_ref5) {
	    var _ref6 = babelHelpers.slicedToArray(_ref5, 2),
	      code = _ref6[0],
	      record = _ref6[1];
	    var item = _this2.getItemById(code);
	    var isOtherUsersFilter = item.id.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX);
	    var isFiltered = babelHelpers.classPrivateFieldGet(_this2, _filterManager$1).isFiltered(babelHelpers.classPrivateFieldGet(_this2, _userId), parseInt(record.TYPE_ID, 10), babelHelpers.classPrivateFieldGet(_this2, _entityTypeId$1), isOtherUsersFilter, babelHelpers.classPrivateFieldGet(_this2, _filterResponsibleFiledName));
	    if (isFiltered) {
	      item.activate(false);
	      if (isOtherUsersFilter) {
	        isOtherUsersFilterUse = true;
	      }
	    } else {
	      item.deactivate(false);
	    }

	    // TODO: need fix it in parent CounterItem class
	    if (item.value !== item.counter.getValue()) {
	      item.updateValue(item.value);
	    }
	  });
	  if (parentItem) {
	    isOtherUsersFilterUse ? parentItem.activate(false) : parentItem.deactivate(false);
	  }
	}
	function _isAllDeactivated2() {
	  return this.getItems().every(function (record) {
	    return !record.isActive;
	  });
	}
	function _deactivateLinkedMenuItem2(item) {
	  var _this3 = this;
	  if (item.hasParentId()) {
	    var parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(babelHelpers.classPrivateFieldGet(this, _codes$1)));
	    parentItem.deactivate(false);
	    return;
	  }
	  if (item.parent) {
	    item.getItems().forEach(function (childItemId) {
	      var childItem = _this3.getItemById(childItemId);
	      if (childItem.isActive) {
	        childItem.deactivate(false);
	      }
	    });
	  }
	}
	function _getParentItemTotalValue2() {
	  var result = 0;
	  this.getItems().forEach(function (record) {
	    if (record.hasParentId()) {
	      result += record.value;
	    }
	  });
	  return result;
	}
	babelHelpers.defineProperty(EntityCounterPanel, "EXCLUDE_USERS_CODE_SUFFIX", 'excl');
	babelHelpers.defineProperty(EntityCounterPanel, "EXCLUDE_ALL_USERS_CODE_SUFFIX", 'all_excl');
	namespace.EntityCounterPanel = EntityCounterPanel;

}((this.BX.Crm = this.BX.Crm || {}),BX.UI,BX,BX.Event));
//# sourceMappingURL=script.js.map
