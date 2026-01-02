/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_counterpanel,main_core,main_core_events,tasks_viewed,ui_analytics) {
	'use strict';

	var Filter = /*#__PURE__*/function () {
	  function Filter(options) {
	    babelHelpers.classCallCheck(this, Filter);
	    this.filterId = options.filterId;
	    this.filterManager = BX.Main.filterManager.getById(this.filterId);
	    this.bindEvents();
	    this.updateFields();
	  }
	  babelHelpers.createClass(Filter, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply() {
	      this.updateFields();
	    }
	  }, {
	    key: "updateFields",
	    value: function updateFields() {
	      this.fields = this.filterManager.getFilterFieldsValues();
	    }
	  }, {
	    key: "isFilteredByField",
	    value: function isFilteredByField(field) {
	      if (!Object.keys(this.fields).includes(field)) {
	        return false;
	      }
	      if (main_core.Type.isArray(this.fields[field])) {
	        return this.fields[field].length > 0;
	      }
	      return this.fields[field] !== '';
	    }
	  }, {
	    key: "isFilteredByFieldValue",
	    value: function isFilteredByFieldValue(field, value) {
	      return this.isFilteredByField(field) && this.fields[field] === value;
	    }
	  }, {
	    key: "toggleByField",
	    value: function toggleByField(field) {
	      var _this = this;
	      var name = field.filterField;
	      var value = field.filterValue;
	      if (!this.isFilteredByFieldValue(name, value)) {
	        this.filterManager.getApi().extendFilter(babelHelpers.defineProperty({}, name, value), false, {
	          COUNTER_TYPE: 'TASKS_COUNTER_TYPE_' + value
	        });
	        return;
	      }
	      this.filterManager.getFilterFields().forEach(function (field) {
	        if (field.getAttribute('data-name') === name) {
	          _this.filterManager.getFields().deleteField(field);
	        }
	      });
	      this.filterManager.getSearch().apply();
	    }
	  }, {
	    key: "getFilter",
	    value: function getFilter() {
	      return this.filterManager;
	    }
	  }]);
	  return Filter;
	}();

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _filterId = /*#__PURE__*/new WeakMap();
	var _readAll = /*#__PURE__*/new WeakSet();
	var _isBigScreen = /*#__PURE__*/new WeakSet();
	var _onActivateItem = /*#__PURE__*/new WeakSet();
	var _activateLinkedMenuItem = /*#__PURE__*/new WeakSet();
	var _onDeactivateItem = /*#__PURE__*/new WeakSet();
	var _deactivateLinkedMenuItem = /*#__PURE__*/new WeakSet();
	var _getColorByValue = /*#__PURE__*/new WeakSet();
	var _markCounters = /*#__PURE__*/new WeakSet();
	var _updateReadAllItem = /*#__PURE__*/new WeakSet();
	var _sendAnalyticsEvent = /*#__PURE__*/new WeakSet();
	var _getAnalyticsEvent = /*#__PURE__*/new WeakSet();
	var _getAnalyticsSection = /*#__PURE__*/new WeakSet();
	var _getAnalyticsElement = /*#__PURE__*/new WeakSet();
	var Counters = /*#__PURE__*/function (_CounterPanel) {
	  babelHelpers.inherits(Counters, _CounterPanel);
	  babelHelpers.createClass(Counters, null, [{
	    key: "counterTypes",
	    get: function get() {
	      return {
	        my: ['expired', 'my_expired', 'originator_expired', 'accomplices_expired', 'auditor_expired', 'new_comments', 'my_new_comments', 'originator_new_comments', 'accomplices_new_comments', 'auditor_new_comments', 'projects_total_expired', 'projects_total_comments', 'sonet_total_expired', 'sonet_total_comments', 'groups_total_expired', 'groups_total_comments', 'scrum_total_comments', 'flow_total_expired', 'flow_total_comments'],
	        other: ['project_expired', 'project_comments', 'projects_foreign_expired', 'projects_foreign_comments', 'groups_foreign_expired', 'groups_foreign_comments', 'sonet_foreign_expired', 'sonet_foreign_comments', 'scrum_foreign_comments'],
	        additional: ['muted_new_comments'],
	        expired: ['expired', 'my_expired', 'originator_expired', 'accomplices_expired', 'auditor_expired', 'project_expired', 'projects_total_expired', 'projects_foreign_expired', 'groups_total_expired', 'groups_foreign_expired', 'sonet_total_expired', 'sonet_foreign_expired', 'flow_total_expired'],
	        comment: ['new_comments', 'my_new_comments', 'originator_new_comments', 'accomplices_new_comments', 'auditor_new_comments', 'muted_new_comments', 'project_comments', 'projects_total_comments', 'projects_foreign_comments', 'groups_total_comments', 'groups_foreign_comments', 'sonet_total_comments', 'sonet_foreign_comments', 'scrum_total_comments', 'scrum_foreign_comments', 'flow_total_comments'],
	        project: ['project_expired', 'projects_total_expired', 'projects_foreign_expired', 'groups_total_expired', 'groups_foreign_expired', 'sonet_total_expired', 'sonet_foreign_expired', 'project_comments', 'projects_total_comments', 'projects_foreign_comments', 'groups_total_comments', 'groups_foreign_comments', 'sonet_total_comments', 'sonet_foreign_comments'],
	        scrum: ['scrum_total_comments', 'scrum_foreign_comments']
	      };
	    }
	  }]);
	  function Counters(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Counters);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Counters).call(this, {
	      target: options.renderTo,
	      items: [],
	      multiselect: true,
	      title: main_core.Loc.getMessage('TASKS_COUNTER_MY')
	    }));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getAnalyticsElement);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getAnalyticsSection);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getAnalyticsEvent);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _sendAnalyticsEvent);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _updateReadAllItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _markCounters);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getColorByValue);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _deactivateLinkedMenuItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onDeactivateItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _activateLinkedMenuItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onActivateItem);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _isBigScreen);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _readAll);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _filterId, {
	      writable: true,
	      value: void 0
	    });
	    _this.userId = options.userId;
	    _this.targetUserId = options.targetUserId;
	    _this.groupId = options.groupId;
	    _this.counters = options.counters;
	    _this.initialCounterTypes = options.counterTypes;
	    _this.role = options.role;
	    _this.signedParameters = options.signedParameters;
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _filterId, options.filterId);
	    _this.setData(_this.counters);
	    _this.initPull();
	    _this.elements = _this.getCounterItems(_this.counters);
	    _this.setItems(_this.elements);
	    return _this;
	  }
	  babelHelpers.createClass(Counters, [{
	    key: "isMyTaskList",
	    value: function isMyTaskList() {
	      return this.userId === this.targetUserId;
	    }
	  }, {
	    key: "isUserTaskList",
	    value: function isUserTaskList() {
	      return Object.keys(this.otherCounters).length === 0;
	    }
	  }, {
	    key: "isProjectsTaskList",
	    value: function isProjectsTaskList() {
	      return this.groupId > 0;
	    }
	  }, {
	    key: "isProjectList",
	    value: function isProjectList() {
	      return !this.isUserTaskList() && !this.isProjectsTaskList();
	    }
	  }, {
	    key: "initPull",
	    value: function initPull() {
	      var _this2 = this;
	      BX.PULL.subscribe({
	        moduleId: 'tasks',
	        callback: function callback(data) {
	          return _this2.processPullEvent(data);
	        }
	      });
	      this.extendWatch();
	    }
	  }, {
	    key: "extendWatch",
	    value: function extendWatch() {
	      var _this3 = this;
	      if (this.isProjectsTaskList() || this.isProjectList()) {
	        var tagId = 'TASKS_PROJECTS';
	        if (this.isProjectsTaskList()) {
	          tagId = "TASKS_PROJECTS_".concat(this.groupId);
	        }
	        BX.PULL.extendWatch(tagId, true);
	        setTimeout(function () {
	          return _this3.extendWatch();
	        }, 29 * 60 * 1000);
	      }
	    }
	  }, {
	    key: "processPullEvent",
	    value: function processPullEvent(data) {
	      var eventHandlers = {
	        user_counter: this.onUserCounter.bind(this),
	        project_counter: this.onProjectCounter.bind(this),
	        comment_read_all: this.onCommentReadAll.bind(this)
	      };
	      var has = Object.prototype.hasOwnProperty;
	      var command = data.command,
	        params = data.params;
	      if (has.call(eventHandlers, command)) {
	        var method = eventHandlers[command];
	        if (method) {
	          method.apply(this, [params]);
	        }
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this4 = this;
	      main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', _classPrivateMethodGet(this, _onActivateItem, _onActivateItem2).bind(this));
	      main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', _classPrivateMethodGet(this, _onDeactivateItem, _onDeactivateItem2).bind(this));
	      main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:click', _classPrivateMethodGet(this, _readAll, _readAll2).bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	      main_core.Event.bind(window, 'resize', main_core.Runtime.throttle(function () {
	        if (_classPrivateMethodGet(_this4, _isBigScreen, _isBigScreen2).call(_this4)) {
	          var _this4$getItemById;
	          (_this4$getItemById = _this4.getItemById(Counters.READ_ALL_ID)) === null || _this4$getItemById === void 0 ? void 0 : _this4$getItemById.expand();
	        } else {
	          var _this4$getItemById2;
	          (_this4$getItemById2 = _this4.getItemById(Counters.READ_ALL_ID)) === null || _this4$getItemById2 === void 0 ? void 0 : _this4$getItemById2.collapse();
	        }
	      }, 10, this));
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply() {
	      var _this5 = this;
	      if (this.isRoleChanged()) {
	        this.getItems().forEach(function (item) {
	          return item.deactivate(false);
	        });
	        this.updateRole();
	        this.updateCountersData();
	      } else {
	        Object.values(this.elements).forEach(function (element) {
	          var item = _this5.getItemById(element.id);
	          var isFiltered = _this5.filter.isFilteredByFieldValue(element.filterField, element.filterValue);
	          if (!isFiltered) {
	            item.deactivate(false);
	          }
	        });
	        _classPrivateMethodGet(this, _markCounters, _markCounters2).call(this);
	      }
	    }
	  }, {
	    key: "updateCountersData",
	    value: function updateCountersData() {
	      var _this6 = this;
	      if (Counters.updateTimeout) {
	        Counters.needUpdate = true;
	        return;
	      }
	      Counters.updateTimeout = true;
	      Counters.needUpdate = false;
	      main_core.ajax.runComponentAction('bitrix:tasks.interface.counters', 'getCounters', {
	        mode: 'class',
	        data: {
	          groupId: this.groupId,
	          role: this.role,
	          counters: this.initialCounterTypes
	        },
	        signedParameters: this.signedParameters
	      }).then(function (response) {
	        return _this6.rerender(response.data);
	      }, function (response) {
	        return console.log(response);
	      });
	      setTimeout(function () {
	        Counters.updateTimeout = false;
	        if (Counters.needUpdate) {
	          _this6.updateCountersData();
	        }
	      }, Counters.timeoutTTL);
	    }
	  }, {
	    key: "isRoleChanged",
	    value: function isRoleChanged() {
	      return this.role !== (this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all');
	    }
	  }, {
	    key: "updateRole",
	    value: function updateRole() {
	      this.role = this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all';
	    }
	  }, {
	    key: "onCommentReadAll",
	    value: function onCommentReadAll(data) {
	      this.updateCountersData();
	    }
	  }, {
	    key: "onUserCounter",
	    value: function onUserCounter(data) {
	      var has = Object.prototype.hasOwnProperty;
	      if (!this.isUserTaskList() || !this.isMyTaskList() || !has.call(data, this.groupId) || this.userId !== Number(data.userId)) {
	        // most likely project counters were updated, but due to 'isSonetEnable' flag only user counters are comming
	        this.updateCountersData();
	        return;
	      }
	      if (data.isSonetEnabled !== undefined && data.isSonetEnabled === false) {
	        this.updateCountersData();
	      }
	    }
	  }, {
	    key: "onProjectCounter",
	    value: function onProjectCounter(data) {
	      if (this.isUserTaskList()) {
	        return;
	      }
	      this.updateCountersData();
	    }
	  }, {
	    key: "getCounterItems",
	    value: function getCounterItems(counters) {
	      var _this7 = this;
	      var items = [];
	      var parentTotal = 0;
	      var parentItemId = 'tasks_more';
	      var hasOther = false;
	      var availableTypes = new Set([].concat(babelHelpers.toConsumableArray(Counters.counterTypes.my), babelHelpers.toConsumableArray(Counters.counterTypes.other)));
	      items = [];
	      Object.entries(counters).forEach(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	          type = _ref2[0],
	          data = _ref2[1];
	        if (!availableTypes.has(type)) {
	          return;
	        }
	        if (Counters.counterTypes.other.includes(type)) {
	          hasOther = true;
	          parentTotal += Number(data.VALUE);
	          items.push({
	            id: type,
	            title: _this7.getCounterNameByType(type),
	            value: {
	              value: Number(data.VALUE),
	              order: -1
	            },
	            parentId: parentItemId,
	            color: _classPrivateMethodGet(_this7, _getColorByValue, _getColorByValue2).call(_this7, Number(data.VALUE), data.STYLE),
	            filterField: data.FILTER_FIELD,
	            filterValue: data.FILTER_VALUE
	          });
	        } else {
	          items.push({
	            id: type,
	            title: _this7.getCounterNameByType(type),
	            value: Number(data.VALUE),
	            isRestricted: false,
	            color: _classPrivateMethodGet(_this7, _getColorByValue, _getColorByValue2).call(_this7, Number(data.VALUE), data.STYLE),
	            filterField: data.FILTER_FIELD,
	            filterValue: data.FILTER_VALUE
	          });
	        }
	      });
	      if (!this.isUserTaskList() || this.isMyTaskList()) {
	        items.push({
	          id: Counters.READ_ALL_ID,
	          title: main_core.Loc.getMessage('TASKS_COUNTER_READ_ALL'),
	          collapsedIcon: 'chat-check',
	          collapsed: !_classPrivateMethodGet(this, _isBigScreen, _isBigScreen2).call(this),
	          locked: false,
	          dataAttributes: {
	            role: 'tasks-counters--item-head-read-all'
	          }
	        });
	      }
	      if (hasOther) {
	        items.push({
	          id: parentItemId,
	          title: main_core.Loc.getMessage('TASKS_COUNTER_MORE'),
	          value: {
	            value: parentTotal,
	            order: -1
	          },
	          isRestricted: false,
	          color: Counters.COLOR_THEME
	        });
	      }
	      return items;
	    }
	  }, {
	    key: "getCounterNameByType",
	    value: function getCounterNameByType(type) {
	      if (Counters.counterTypes.expired.includes(type)) {
	        return main_core.Loc.getMessage('TASKS_COUNTER_EXPIRED');
	      }
	      if (Counters.counterTypes.comment.includes(type)) {
	        return main_core.Loc.getMessage('TASKS_COUNTER_NEW_COMMENTS');
	      }
	      return '';
	    }
	  }, {
	    key: "setData",
	    value: function setData(counters) {
	      var _this8 = this;
	      this.myCounters = {};
	      this.otherCounters = {};
	      var availableTypes = new Set([].concat(babelHelpers.toConsumableArray(Counters.counterTypes.my), babelHelpers.toConsumableArray(Counters.counterTypes.other)));
	      Object.entries(counters).forEach(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	          type = _ref4[0],
	          data = _ref4[1];
	        if (!availableTypes.has(type)) {
	          return;
	        }
	        var counterItem = {
	          id: type,
	          title: _this8.getCounterNameByType(type),
	          value: Number(data.VALUE),
	          color: _classPrivateMethodGet(_this8, _getColorByValue, _getColorByValue2).call(_this8, Number(data.VALUE), data.STYLE),
	          filterField: data.FILTER_FIELD,
	          filterValue: data.FILTER_VALUE
	        };
	        if (Counters.counterTypes.my.includes(type)) {
	          _this8.myCounters[type] = counterItem;
	        } else if (Counters.counterTypes.other.includes(type)) {
	          _this8.otherCounters[type] = counterItem;
	        }
	      });
	    }
	  }, {
	    key: "readAllByRole",
	    value: function readAllByRole() {
	      new tasks_viewed.Controller().userComments({
	        groupId: this.groupId,
	        userId: this.userId,
	        role: this.role
	      });
	    }
	  }, {
	    key: "readAllForProjects",
	    value: function readAllForProjects() {
	      var allCounters = _objectSpread(_objectSpread({}, this.myCounters), this.otherCounters);
	      Object.entries(allCounters).forEach(function (_ref5) {
	        var _ref6 = babelHelpers.slicedToArray(_ref5, 2),
	          type = _ref6[0],
	          counter = _ref6[1];
	        if (Counters.counterTypes.comment.includes(type)) {
	          counter.updateCount(0);
	        }
	      });
	      new tasks_viewed.Controller().projectComments({
	        groupId: this.groupId
	      });
	    }
	  }, {
	    key: "readAllForScrum",
	    value: function readAllForScrum() {
	      var allCounters = _objectSpread(_objectSpread({}, this.myCounters), this.otherCounters);
	      Object.entries(allCounters).forEach(function (_ref7) {
	        var _ref8 = babelHelpers.slicedToArray(_ref7, 2),
	          type = _ref8[0],
	          counter = _ref8[1];
	        if (Counters.counterTypes.comment.includes(type)) {
	          counter.updateCount(0);
	        }
	      });
	      new tasks_viewed.Controller().scrumComments({
	        groupId: this.groupId
	      });
	    }
	  }, {
	    key: "rerender",
	    value: function rerender(counters) {
	      var _this9 = this;
	      this.setData(counters);
	      var parentTotal = 0;
	      var parentId = null;
	      var canResetReadAll = false;
	      var availableTypes = new Set([].concat(babelHelpers.toConsumableArray(Counters.counterTypes.my), babelHelpers.toConsumableArray(Counters.counterTypes.other)));
	      Object.entries(counters).forEach(function (_ref9) {
	        var _ref10 = babelHelpers.slicedToArray(_ref9, 2),
	          code = _ref10[0],
	          counter = _ref10[1];
	        if (!availableTypes.has(code)) {
	          return;
	        }
	        var item = _this9.getItemById(code);
	        if (item.hasParentId()) {
	          parentId = item.parentId;
	          parentTotal += Number(counter.VALUE);
	        }
	        item.updateValue(Number(counter.VALUE));
	        item.updateColor(_classPrivateMethodGet(_this9, _getColorByValue, _getColorByValue2).call(_this9, Number(counter.VALUE), counter.STYLE));
	        if (_classPrivateMethodGet(_this9, _getColorByValue, _getColorByValue2).call(_this9, Number(counter.VALUE), counter.STYLE) === Counters.COLOR_SUCCESS) {
	          canResetReadAll = true;
	        }
	      });
	      if (canResetReadAll && (!this.isUserTaskList() || this.isMyTaskList())) {
	        _classPrivateMethodGet(this, _updateReadAllItem, _updateReadAllItem2).call(this, canResetReadAll);
	      }
	      if (parentId) {
	        var item = this.getItemById(parentId);
	        item.updateValue(parentTotal);
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Counters.prototype), "init", this).call(this);
	    }
	  }, {
	    key: "connectWithFilter",
	    value: function connectWithFilter() {
	      this.filter = new Filter({
	        filterId: babelHelpers.classPrivateFieldGet(this, _filterId)
	      });
	      this.bindEvents();
	      _classPrivateMethodGet(this, _markCounters, _markCounters2).call(this);
	    }
	  }]);
	  return Counters;
	}(ui_counterpanel.CounterPanel);
	function _readAll2(event) {
	  var item = event.getData().item;
	  if (item.getId() === Counters.READ_ALL_ID) {
	    if (this.isUserTaskList() || this.isProjectsTaskList() && this.role !== 'view_all') {
	      this.readAllByRole();
	    } else if (this.myCounters.scrum_total_comments || this.otherCounters.scrum_foreign_comments) {
	      this.readAllForScrum();
	    } else {
	      this.readAllForProjects();
	    }
	    item.lock();
	  }
	}
	function _isBigScreen2() {
	  return window.innerWidth > 1520;
	}
	function _onActivateItem2(event) {
	  var item = event.getData();
	  var itemId = item.id;
	  var count = null;
	  Object.values(this.elements).forEach(function (element) {
	    if (element.id === itemId) {
	      count = element;
	    }
	  });
	  _classPrivateMethodGet(this, _activateLinkedMenuItem, _activateLinkedMenuItem2).call(this, item);
	  this.filter.toggleByField(count);
	  _classPrivateMethodGet(this, _sendAnalyticsEvent, _sendAnalyticsEvent2).call(this, item);
	  main_core_events.EventEmitter.emit('Tasks.Toolbar:onItem', {
	    counter: count
	  });
	  main_core_events.EventEmitter.emit('BX.Tasks.Counters:active', count);
	}
	function _activateLinkedMenuItem2(item) {
	  var items = this.getItems();
	  Object.values(items).forEach(function (element) {
	    if (element.id !== item.id) {
	      element.deactivate(false);
	    }
	  });
	  if (item.hasParentId()) {
	    this.getItemById(item.parentId).activate(false);
	  }
	}
	function _onDeactivateItem2(event) {
	  var _this10 = this;
	  var item = event.getData();
	  var itemId = item.id;
	  var count = null;
	  if (item.parent) {
	    item.getItems().forEach(function (childItemId) {
	      var childItem = _this10.getItemById(childItemId);
	      if (childItem.isActive) {
	        itemId = childItem.id;
	      }
	    });
	  }
	  Object.values(this.elements).forEach(function (element) {
	    if (element.id === itemId) {
	      count = element;
	    }
	  });
	  _classPrivateMethodGet(this, _deactivateLinkedMenuItem, _deactivateLinkedMenuItem2).call(this, item);
	  this.filter.toggleByField(count);
	  main_core_events.EventEmitter.emit('Tasks.Toolbar:onItem', {
	    counter: count
	  });
	  main_core_events.EventEmitter.emit('BX.Tasks.Counters:unActive', count);
	}
	function _deactivateLinkedMenuItem2(item) {
	  var _this11 = this;
	  if (item.hasParentId()) {
	    var parentItem = this.getItemById(item.parentId);
	    parentItem.deactivate(false);
	    return;
	  }
	  if (item.parent) {
	    item.getItems().forEach(function (childItemId) {
	      var childItem = _this11.getItemById(childItemId);
	      if (childItem.isActive) {
	        childItem.deactivate(false);
	      }
	    });
	  }
	}
	function _getColorByValue2(value, color) {
	  return Number(value) === 0 ? Counters.COLOR_GRAY : color.toUpperCase();
	}
	function _markCounters2() {
	  var _this12 = this;
	  var parentId = null;
	  var canResetReadAll = false;
	  Object.values(this.elements).forEach(function (element) {
	    var item = _this12.getItemById(element.id);
	    var isFiltered = _this12.filter.isFilteredByFieldValue(element.filterField, element.filterValue);
	    if (item.color === Counters.COLOR_SUCCESS) {
	      canResetReadAll = true;
	    }
	    if (isFiltered) {
	      if (item.hasParentId()) {
	        parentId = item.parentId;
	      }
	      item.activate(false);
	    }
	  });
	  if (canResetReadAll && (!this.isUserTaskList() || this.isMyTaskList())) {
	    _classPrivateMethodGet(this, _updateReadAllItem, _updateReadAllItem2).call(this, canResetReadAll);
	  }
	  if (parentId) {
	    this.getItemById(parentId).activate(false);
	  }
	}
	function _updateReadAllItem2(canResetReadAll) {
	  var readAllItem = this.getItemById(Counters.READ_ALL_ID);
	  if (canResetReadAll && readAllItem.isLocked()) {
	    readAllItem.unLock();
	  }
	  if (!canResetReadAll && !readAllItem.isLocked()) {
	    readAllItem.lock();
	  }
	}
	function _sendAnalyticsEvent2(item) {
	  ui_analytics.sendData({
	    tool: 'tasks',
	    category: 'task_operations',
	    type: 'task',
	    event: _classPrivateMethodGet(this, _getAnalyticsEvent, _getAnalyticsEvent2).call(this, item),
	    c_section: _classPrivateMethodGet(this, _getAnalyticsSection, _getAnalyticsSection2).call(this, item),
	    c_element: _classPrivateMethodGet(this, _getAnalyticsElement, _getAnalyticsElement2).call(this, item)
	  });
	}
	function _getAnalyticsEvent2(item) {
	  if (Counters.counterTypes.expired.includes(item.id)) {
	    return 'overdue_counters_on';
	  }
	  return 'comments_counters_on';
	}
	function _getAnalyticsSection2(item) {
	  if (Counters.counterTypes.scrum.includes(item.id)) {
	    return 'scrum';
	  }
	  if (Counters.counterTypes.project.includes(item.id)) {
	    return 'project';
	  }
	  return 'tasks';
	}
	function _getAnalyticsElement2(item) {
	  if (Counters.counterTypes.expired.includes(item.id)) {
	    return 'overdue_counters_filter';
	  }
	  return 'comments_counters_filter';
	}
	babelHelpers.defineProperty(Counters, "READ_ALL_ID", 'read_all');
	babelHelpers.defineProperty(Counters, "COLOR_GRAY", 'GRAY');
	babelHelpers.defineProperty(Counters, "COLOR_THEME", 'THEME');
	babelHelpers.defineProperty(Counters, "COLOR_SUCCESS", 'SUCCESS');
	babelHelpers.defineProperty(Counters, "updateTimeout", false);
	babelHelpers.defineProperty(Counters, "needUpdate", false);
	babelHelpers.defineProperty(Counters, "timeoutTTL", 5000);

	exports.Counters = Counters;

}((this.BX.Tasks.Counters = this.BX.Tasks.Counters || {}),BX.UI,BX,BX.Event,BX.Tasks.Viewed,BX.UI.Analytics));
//# sourceMappingURL=script.js.map
