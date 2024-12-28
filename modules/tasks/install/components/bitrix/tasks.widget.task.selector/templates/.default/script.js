/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_entitySelector,main_core_events) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _multiple = /*#__PURE__*/new WeakMap();
	var _currentTasks = /*#__PURE__*/new WeakMap();
	var _userId = /*#__PURE__*/new WeakMap();
	var _tagSelector = /*#__PURE__*/new WeakMap();
	var _textBoxWidth = /*#__PURE__*/new WeakMap();
	var _getLinkByTaskId = /*#__PURE__*/new WeakSet();
	var _getItems = /*#__PURE__*/new WeakSet();
	var _onTagAdd = /*#__PURE__*/new WeakSet();
	var _onTagRemove = /*#__PURE__*/new WeakSet();
	var TaskSelector = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(TaskSelector, _EventEmitter);
	  function TaskSelector(_data) {
	    var _this;
	    babelHelpers.classCallCheck(this, TaskSelector);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TaskSelector).call(this, _data));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onTagRemove);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onTagAdd);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getItems);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getLinkByTaskId);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _multiple, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _currentTasks, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _userId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _tagSelector, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _textBoxWidth, {
	      writable: true,
	      value: 205
	    });
	    _this.setEventNamespace('BX.Tasks.TaskSelector');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _multiple, _data.multiple);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _currentTasks, JSON.parse(_data.currentTasks));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _userId, _data.userId);
	    return _this;
	  }
	  babelHelpers.createClass(TaskSelector, [{
	    key: "getSelector",
	    value: function getSelector() {
	      var _this2 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _tagSelector)) {
	        if (!Array.isArray(babelHelpers.classPrivateFieldGet(this, _currentTasks))) {
	          babelHelpers.classPrivateFieldSet(this, _currentTasks, []);
	        }
	        babelHelpers.classPrivateFieldSet(this, _tagSelector, new ui_entitySelector.TagSelector({
	          textBoxWidth: babelHelpers.classPrivateFieldGet(this, _textBoxWidth),
	          multiple: babelHelpers.classPrivateFieldGet(this, _multiple),
	          items: _classPrivateMethodGet(this, _getItems, _getItems2).call(this),
	          dialogOptions: {
	            showAvatars: false,
	            enableSearch: true,
	            context: 'TASKS',
	            entities: [{
	              id: 'task-with-id',
	              itemOptions: {
	                "default": {
	                  link: _classPrivateMethodGet(this, _getLinkByTaskId, _getLinkByTaskId2).call(this, '#id#')
	                }
	              }
	            }]
	          },
	          events: {
	            onTagAdd: function onTagAdd(event) {
	              return _classPrivateMethodGet(_this2, _onTagAdd, _onTagAdd2).call(_this2, event);
	            },
	            onTagRemove: function onTagRemove(event) {
	              return _classPrivateMethodGet(_this2, _onTagRemove, _onTagRemove2).call(_this2, event);
	            }
	          }
	        }));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _tagSelector);
	    }
	  }]);
	  return TaskSelector;
	}(main_core_events.EventEmitter);
	function _getLinkByTaskId2(taskId) {
	  return '/company/personal/user/' + babelHelpers.classPrivateFieldGet(this, _userId) + '/tasks/task/view/' + taskId + '/';
	}
	function _getItems2() {
	  var _this3 = this;
	  var items = [];
	  babelHelpers.classPrivateFieldGet(this, _currentTasks).forEach(function (task) {
	    items.push({
	      id: task.ID,
	      entityId: 'task-with-id',
	      title: task.TITLE + '[' + task.ID + ']',
	      link: _classPrivateMethodGet(_this3, _getLinkByTaskId, _getLinkByTaskId2).call(_this3, task.ID)
	    });
	  });
	  return items;
	}
	function _onTagAdd2(event) {
	  var data = {
	    'selector': event.getTarget(),
	    'tag': event.getData()
	  };
	  this.emit('tagAdded', data);
	}
	function _onTagRemove2(event) {
	  var data = {
	    'selector': event.getTarget(),
	    'tag': event.getData()
	  };
	  this.emit('tagRemoved', data);
	}

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _inputNodeId = /*#__PURE__*/new WeakMap();
	var _scopeContainerId = /*#__PURE__*/new WeakMap();
	var _inputPrefix = /*#__PURE__*/new WeakMap();
	var _blockName = /*#__PURE__*/new WeakMap();
	var _addInput = /*#__PURE__*/new WeakSet();
	var _updateInput = /*#__PURE__*/new WeakSet();
	var TagDomManager = /*#__PURE__*/function () {
	  function TagDomManager(data) {
	    babelHelpers.classCallCheck(this, TagDomManager);
	    _classPrivateMethodInitSpec$1(this, _updateInput);
	    _classPrivateMethodInitSpec$1(this, _addInput);
	    _classPrivateFieldInitSpec$1(this, _inputNodeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _scopeContainerId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _inputPrefix, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _blockName, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _inputNodeId, data.inputNodeId);
	    babelHelpers.classPrivateFieldSet(this, _scopeContainerId, data.scopeContainerId);
	    babelHelpers.classPrivateFieldSet(this, _inputPrefix, data.inputPrefix);
	    babelHelpers.classPrivateFieldSet(this, _blockName, data.blockName);
	  }
	  babelHelpers.createClass(TagDomManager, [{
	    key: "onTagAdd",
	    value: function onTagAdd(event) {
	      var selector = event.getData().selector;
	      var tag = event.getData().tag.tag;
	      if (selector.isMultiple()) {
	        _classPrivateMethodGet$1(this, _addInput, _addInput2).call(this, tag);
	      } else {
	        _classPrivateMethodGet$1(this, _updateInput, _updateInput2).call(this, tag);
	      }
	    }
	  }, {
	    key: "onTagRemove",
	    value: function onTagRemove(event) {
	      var selector = event.getData().selector;
	      var tag = event.getData().tag.tag;
	      if (selector.isMultiple()) {
	        var input = document.getElementById(babelHelpers.classPrivateFieldGet(this, _inputNodeId) + '-' + tag.getId());
	        input.setAttribute('value', '');
	      } else {
	        var _input = document.getElementById(babelHelpers.classPrivateFieldGet(this, _inputNodeId));
	        _input.setAttribute('value', '');
	      }
	    }
	  }]);
	  return TagDomManager;
	}();
	function _addInput2(tag) {
	  var spanContainer = document.getElementById(babelHelpers.classPrivateFieldGet(this, _scopeContainerId));
	  var input = document.createElement('input');
	  input.type = 'hidden';
	  input.name = babelHelpers.classPrivateFieldGet(this, _inputPrefix) + '[' + babelHelpers.classPrivateFieldGet(this, _blockName) + ']' + '[' + tag.getId() + '][ID]';
	  input.id = babelHelpers.classPrivateFieldGet(this, _inputNodeId) + '-' + tag.getId();
	  input.value = tag.getId();
	  input.setAttribute('data-bx-id', 'task-edit-parent-input');
	  spanContainer.appendChild(input);
	}
	function _updateInput2(tag) {
	  var input = document.getElementById(babelHelpers.classPrivateFieldGet(this, _inputNodeId));
	  input.setAttribute('value', tag.getId());
	}

	exports.TaskSelector = TaskSelector;
	exports.TagDomManager = TagDomManager;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.UI.EntitySelector,BX.Event));
//# sourceMappingURL=script.js.map
