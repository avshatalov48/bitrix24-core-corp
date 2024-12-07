/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup) {
	'use strict';

	var OrderType = function OrderType() {
	  babelHelpers.classCallCheck(this, OrderType);
	};
	babelHelpers.defineProperty(OrderType, "ASC", 'asc');
	babelHelpers.defineProperty(OrderType, "DESC", 'desc');

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _mainMenu = /*#__PURE__*/new WeakMap();
	var _mainItem = /*#__PURE__*/new WeakMap();
	var KanbanMenu = /*#__PURE__*/function () {
	  function KanbanMenu(item) {
	    babelHelpers.classCallCheck(this, KanbanMenu);
	    _classPrivateFieldInitSpec(this, _mainMenu, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _mainItem, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _mainItem, item);
	    babelHelpers.classPrivateFieldSet(this, _mainMenu, babelHelpers.classPrivateFieldGet(this, _mainItem).getMenuWindow());
	  }
	  babelHelpers.createClass(KanbanMenu, [{
	    key: "select",
	    value: function select(item) {
	      main_core.Dom.removeClass(item.layout.item, KanbanMenu.DESELECTED);
	      main_core.Dom.addClass(item.layout.item, KanbanMenu.SELECTED);
	    }
	  }, {
	    key: "deselect",
	    value: function deselect(item) {
	      main_core.Dom.removeClass(item.layout.item, KanbanMenu.SELECTED);
	      main_core.Dom.addClass(item.layout.item, KanbanMenu.DESELECTED);
	    }
	  }, {
	    key: "deselectAll",
	    value: function deselectAll() {
	      var _this = this;
	      this.getItems().forEach(function (element) {
	        _this.deselect(element);
	      });
	    }
	  }, {
	    key: "deselectSubItems",
	    value: function deselectSubItems() {
	      var _this2 = this;
	      this.getSubItems().forEach(function (element) {
	        _this2.deselect(element);
	      });
	    }
	  }, {
	    key: "isCustomSortEnabled",
	    value: function isCustomSortEnabled() {
	      var items = this.getItems();
	      var ascSort = items.find(function (item) {
	        var _item$params, _item$params2;
	        return ((_item$params = item.params) === null || _item$params === void 0 ? void 0 : _item$params.type) === 'sub' && ((_item$params2 = item.params) === null || _item$params2 === void 0 ? void 0 : _item$params2.order) === OrderType.ASC;
	      });
	      var descSort = items.find(function (item) {
	        var _item$params3, _item$params4;
	        return ((_item$params3 = item.params) === null || _item$params3 === void 0 ? void 0 : _item$params3.type) === 'sub' && ((_item$params4 = item.params) === null || _item$params4 === void 0 ? void 0 : _item$params4.order) === OrderType.DESC;
	      });
	      return Boolean(ascSort) && Boolean(descSort);
	    }
	  }, {
	    key: "addItemsFromItemParams",
	    value: function addItemsFromItemParams(onSelectCallback) {
	      var _this3 = this;
	      babelHelpers.classPrivateFieldGet(this, _mainItem).params.forEach(function (subItem) {
	        var _subItem$params;
	        subItem.params = BX.parseJSON(subItem.params);
	        if ((_subItem$params = subItem.params) !== null && _subItem$params !== void 0 && _subItem$params.order) {
	          subItem.onclick = onSelectCallback;
	        }
	        babelHelpers.classPrivateFieldGet(_this3, _mainMenu).addMenuItem(subItem);
	      });
	    }
	  }, {
	    key: "removeSubItems",
	    value: function removeSubItems() {
	      var _this4 = this;
	      this.getSubItems().forEach(function (subItem) {
	        babelHelpers.classPrivateFieldGet(_this4, _mainMenu).removeMenuItem(subItem.getId());
	      });
	    }
	  }, {
	    key: "getSubItems",
	    value: function getSubItems() {
	      return this.getItems().filter(function (element) {
	        var _element$params;
	        return ((_element$params = element.params) === null || _element$params === void 0 ? void 0 : _element$params.type) === 'sub';
	      });
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return babelHelpers.classPrivateFieldGet(this, _mainMenu).getMenuItems();
	    }
	  }, {
	    key: "find",
	    value: function find() {
	      var order = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      return this.getItems().find(function (element) {
	        var _element$params2;
	        return ((_element$params2 = element.params) === null || _element$params2 === void 0 ? void 0 : _element$params2.order) === order;
	      });
	    }
	  }]);
	  return KanbanMenu;
	}();
	babelHelpers.defineProperty(KanbanMenu, "SELECTED", 'menu-popup-item-accept');
	babelHelpers.defineProperty(KanbanMenu, "DESELECTED", 'menu-popup-item-none');

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _ajaxComponentPath = /*#__PURE__*/new WeakMap();
	var _ajaxComponentParams = /*#__PURE__*/new WeakMap();
	var KanbanAjaxComponent = /*#__PURE__*/function () {
	  function KanbanAjaxComponent() {
	    var parameters = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, KanbanAjaxComponent);
	    _classPrivateFieldInitSpec$1(this, _ajaxComponentPath, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _ajaxComponentParams, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _ajaxComponentPath, parameters === null || parameters === void 0 ? void 0 : parameters.ajaxComponentPath);
	    babelHelpers.classPrivateFieldSet(this, _ajaxComponentParams, parameters === null || parameters === void 0 ? void 0 : parameters.ajaxComponentParams);
	  }
	  babelHelpers.createClass(KanbanAjaxComponent, [{
	    key: "getPath",
	    value: function getPath() {
	      return babelHelpers.classPrivateFieldGet(this, _ajaxComponentPath);
	    }
	  }, {
	    key: "getParams",
	    value: function getParams() {
	      return babelHelpers.classPrivateFieldGet(this, _ajaxComponentParams);
	    }
	  }]);
	  return KanbanAjaxComponent;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _ajaxComponent = /*#__PURE__*/new WeakMap();
	var _init = /*#__PURE__*/new WeakSet();
	var KanbanRequestSender = /*#__PURE__*/function () {
	  function KanbanRequestSender() {
	    babelHelpers.classCallCheck(this, KanbanRequestSender);
	    _classPrivateMethodInitSpec(this, _init);
	    _classPrivateFieldInitSpec$2(this, _ajaxComponent, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateMethodGet(this, _init, _init2).call(this);
	  }
	  babelHelpers.createClass(KanbanRequestSender, [{
	    key: "setOrder",
	    value: function setOrder() {
	      var _this = this;
	      var order = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      BX.ajax.runComponentAction('bitrix:tasks.kanban', 'setNewTaskOrder', {
	        mode: 'class',
	        data: {
	          order: order,
	          params: babelHelpers.classPrivateFieldGet(this, _ajaxComponent).getParams()
	        }
	      }).then(function (response) {
	        var data = response.data;
	        BX.onCustomEvent(_this, 'onTaskSortChanged', [data]);
	      });
	    }
	  }]);
	  return KanbanRequestSender;
	}();
	function _init2() {
	  babelHelpers.classPrivateFieldSet(this, _ajaxComponent, BX.Tasks.KanbanAjaxComponent.Parameters);
	}

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _kanbanMenu = /*#__PURE__*/new WeakMap();
	var _requestSender = /*#__PURE__*/new WeakMap();
	var _bindMethods = /*#__PURE__*/new WeakSet();
	var _setOrder = /*#__PURE__*/new WeakSet();
	var KanbanSort = /*#__PURE__*/function () {
	  function KanbanSort() {
	    babelHelpers.classCallCheck(this, KanbanSort);
	    _classPrivateMethodInitSpec$1(this, _setOrder);
	    _classPrivateMethodInitSpec$1(this, _bindMethods);
	    _classPrivateFieldInitSpec$3(this, _kanbanMenu, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _requestSender, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateMethodGet$1(this, _bindMethods, _bindMethods2).call(this);
	  }
	  babelHelpers.createClass(KanbanSort, [{
	    key: "enableCustomSort",
	    value: function enableCustomSort(event, item) {
	      babelHelpers.classPrivateFieldSet(this, _requestSender, new KanbanRequestSender());
	      babelHelpers.classPrivateFieldSet(this, _kanbanMenu, new KanbanMenu(item));
	      if (babelHelpers.classPrivateFieldGet(this, _kanbanMenu).isCustomSortEnabled()) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).addItemsFromItemParams(this.selectCustomOrder.bind(this));
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).deselectAll();
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).select(item);
	      var selectedItem = babelHelpers.classPrivateFieldGet(this, _kanbanMenu).find(OrderType.DESC);
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).select(selectedItem);
	      _classPrivateMethodGet$1(this, _setOrder, _setOrder2).call(this, selectedItem);
	    }
	  }, {
	    key: "disableCustomSort",
	    value: function disableCustomSort(event, item) {
	      babelHelpers.classPrivateFieldSet(this, _requestSender, new KanbanRequestSender());
	      babelHelpers.classPrivateFieldSet(this, _kanbanMenu, new KanbanMenu(item));
	      if (!babelHelpers.classPrivateFieldGet(this, _kanbanMenu).isCustomSortEnabled()) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).removeSubItems();
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).deselectAll();
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).select(item);
	      _classPrivateMethodGet$1(this, _setOrder, _setOrder2).call(this, item);
	    }
	  }, {
	    key: "selectCustomOrder",
	    value: function selectCustomOrder(event, item) {
	      babelHelpers.classPrivateFieldSet(this, _requestSender, new KanbanRequestSender());
	      babelHelpers.classPrivateFieldSet(this, _kanbanMenu, new KanbanMenu(item));
	      if (!babelHelpers.classPrivateFieldGet(this, _kanbanMenu).isCustomSortEnabled()) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).deselectSubItems();
	      babelHelpers.classPrivateFieldGet(this, _kanbanMenu).select(item);
	      _classPrivateMethodGet$1(this, _setOrder, _setOrder2).call(this, item);
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      return new this();
	    }
	  }]);
	  return KanbanSort;
	}();
	function _bindMethods2() {
	  this.enableCustomSort = this.enableCustomSort.bind(this);
	  this.disableCustomSort = this.disableCustomSort.bind(this);
	  this.selectCustomOrder = this.selectCustomOrder.bind(this);
	}
	function _setOrder2(item) {
	  var _item$params;
	  var order = (item === null || item === void 0 ? void 0 : (_item$params = item.params) === null || _item$params === void 0 ? void 0 : _item$params.order) || '';
	  babelHelpers.classPrivateFieldGet(this, _requestSender).setOrder(order);
	}

	exports.KanbanSort = KanbanSort;
	exports.KanbanAjaxComponent = KanbanAjaxComponent;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Main));
//# sourceMappingURL=kanban-sort.bundle.js.map
