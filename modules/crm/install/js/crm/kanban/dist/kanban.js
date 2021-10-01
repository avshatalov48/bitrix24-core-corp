this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,ui_notification,main_popup,main_core) {
	'use strict';

	var _queue = new WeakMap();

	var _grid = new WeakMap();

	var _isProgress = new WeakMap();

	var _isFreeze = /*#__PURE__*/new WeakMap();

	var PullQueue = /*#__PURE__*/function () {
	  function PullQueue(grid) {
	    babelHelpers.classCallCheck(this, PullQueue);

	    _queue.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _grid.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isProgress.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isFreeze.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _grid, grid);
	    babelHelpers.classPrivateFieldSet(this, _queue, new Set());
	    babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	    babelHelpers.classPrivateFieldSet(this, _isFreeze, false);
	  }

	  babelHelpers.createClass(PullQueue, [{
	    key: "loadItem",
	    value: function loadItem(isForce) {
	      var _this = this;

	      setTimeout(function () {
	        isForce = isForce || false;

	        if (babelHelpers.classPrivateFieldGet(_this, _isProgress) && !isForce) {
	          return;
	        }

	        if (document.hidden || _this.isOverflow() || _this.isFreezed()) {
	          return;
	        }

	        var id = _this.pop();

	        if (id) {
	          var loadNextOnSuccess = function loadNextOnSuccess(response) {
	            if (_this.peek()) {
	              _this.loadItem(true);
	            }

	            babelHelpers.classPrivateFieldSet(_this, _isProgress, false);
	          };

	          var doNothingOnError = function doNothingOnError(err) {};

	          babelHelpers.classPrivateFieldSet(_this, _isProgress, true);
	          babelHelpers.classPrivateFieldGet(_this, _grid).loadNew(id, false, true, true).then(loadNextOnSuccess, doNothingOnError);
	        }
	      }, 1000);
	    }
	  }, {
	    key: "push",
	    value: function push(id) {
	      id = parseInt(id, 10);

	      if (babelHelpers.classPrivateFieldGet(this, _queue).has(id)) {
	        babelHelpers.classPrivateFieldGet(this, _queue).delete(id);
	      }

	      babelHelpers.classPrivateFieldGet(this, _queue).add(id);
	      return this;
	    }
	  }, {
	    key: "pop",
	    value: function pop() {
	      var values = babelHelpers.classPrivateFieldGet(this, _queue).values();
	      var first = values.next();

	      if (first.value !== undefined) {
	        babelHelpers.classPrivateFieldGet(this, _queue).delete(first.value);
	      }

	      return first.value;
	    }
	  }, {
	    key: "peek",
	    value: function peek() {
	      var values = babelHelpers.classPrivateFieldGet(this, _queue).values();
	      var first = values.next();
	      return first.value !== undefined ? first.value : null;
	    }
	  }, {
	    key: "delete",
	    value: function _delete(id) {
	      babelHelpers.classPrivateFieldGet(this, _queue).delete(id);
	    }
	  }, {
	    key: "has",
	    value: function has(id) {
	      return babelHelpers.classPrivateFieldGet(this, _queue).has(id);
	    }
	  }, {
	    key: "clear",
	    value: function clear() {
	      babelHelpers.classPrivateFieldGet(this, _queue).clear();
	    }
	  }, {
	    key: "isOverflow",
	    value: function isOverflow() {
	      var MAX_PENDING_ITEMS = 10;
	      return babelHelpers.classPrivateFieldGet(this, _queue).size > MAX_PENDING_ITEMS;
	    }
	  }, {
	    key: "freeze",
	    value: function freeze() {
	      babelHelpers.classPrivateFieldSet(this, _isFreeze, true);
	    }
	  }, {
	    key: "unfreeze",
	    value: function unfreeze() {
	      babelHelpers.classPrivateFieldSet(this, _isFreeze, false);
	    }
	  }, {
	    key: "isFreezed",
	    value: function isFreezed() {
	      return babelHelpers.classPrivateFieldGet(this, _isFreeze);
	    }
	  }]);
	  return PullQueue;
	}();

	var PullManager = /*#__PURE__*/function () {
	  function PullManager(grid) {
	    babelHelpers.classCallCheck(this, PullManager);
	    this.grid = grid;
	    this.queue = new PullQueue(this.grid);

	    if (main_core.Type.isString(grid.getData().moduleId) && grid.getData().userId > 0) {
	      this.init();
	    }

	    this.bindEvents();
	  }

	  babelHelpers.createClass(PullManager, [{
	    key: "init",
	    value: function init() {
	      var _this = this;

	      main_core.Event.ready(function () {
	        var Pull = BX.PULL;

	        if (!Pull) {
	          console.error('pull is not initialized');
	          return;
	        }

	        Pull.subscribe({
	          moduleId: _this.grid.getData().moduleId,
	          command: _this.grid.getData().pullTag,
	          callback: function callback(params) {
	            if (main_core.Type.isString(params.eventName)) {
	              if (_this.queue.isOverflow()) {
	                return;
	              }

	              if (params.eventName === 'ITEMUPDATED') {
	                _this.onPullItemUpdated(params);
	              } else if (params.eventName === 'ITEMADDED') {
	                _this.onPullItemAdded(params);
	              } else if (params.eventName === 'ITEMDELETED') {
	                _this.onPullItemDeleted(params);
	              } else if (params.eventName === 'STAGEADDED') {
	                _this.onPullStageAdded(params);
	              } else if (params.eventName === 'STAGEDELETED') {
	                _this.onPullStageDeleted(params);
	              } else if (params.eventName === 'STAGEUPDATED') {
	                _this.onPullStageUpdated(params);
	              }
	            }
	          }
	        });
	        Pull.extendWatch(_this.grid.getData().pullTag);
	        main_core.Event.bind(document, 'visibilitychange', function () {
	          if (!document.hidden) {
	            _this.onTabActivated();
	          }
	        });
	      });
	    }
	  }, {
	    key: "onPullItemUpdated",
	    value: function onPullItemUpdated(params) {
	      if (this.updateItem(params)) {
	        this.queue.loadItem();
	      }
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(params) {
	      var item = this.grid.getItem(params.item.id);
	      var paramsItem = params.item;

	      if (item) {
	        var oldPrice = parseFloat(item.data.price);
	        var oldColumnId = item.data.columnId;

	        for (var key in paramsItem.data) {
	          if (key in item.data) {
	            item.data[key] = paramsItem.data[key];
	          }
	        }

	        item.rawData = paramsItem.rawData;
	        item.setActivityExistInnerHtml();
	        item.useAnimation = true;
	        item.setChangedInPullRequest();
	        this.grid.resetMultiSelectMode();
	        this.grid.insertItem(item);
	        var newColumn = this.grid.getColumn(paramsItem.data.columnId);
	        var newPrice = parseFloat(paramsItem.data.price);

	        if (oldColumnId !== paramsItem.data.columnId) {
	          var oldColumn = this.grid.getColumn(oldColumnId);
	          oldColumn.decPrice(oldPrice);
	          oldColumn.renderSubTitle();
	          newColumn.incPrice(newPrice);
	          newColumn.renderSubTitle();
	        } else {
	          if (oldPrice < newPrice) {
	            newColumn.incPrice(newPrice - oldPrice);
	            newColumn.renderSubTitle();
	          } else if (oldPrice > newPrice) {
	            newColumn.decPrice(oldPrice - newPrice);
	            newColumn.renderSubTitle();
	          }
	        }

	        item.columnId = paramsItem.data.columnId;
	        this.queue.push(item.id);
	        return true;
	      }

	      this.onPullItemAdded(params);
	      return false;
	    }
	  }, {
	    key: "onPullItemAdded",
	    value: function onPullItemAdded(params) {
	      this.addItem(params);
	      this.queue.loadItem();
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(params) {
	      var oldItem = this.grid.getItem(params.item.id);

	      if (oldItem) {
	        return;
	      }

	      this.grid.addItemTop(params.item);
	      this.queue.push(params.item.id);
	    }
	  }, {
	    key: "onPullItemDeleted",
	    value: function onPullItemDeleted(params) {
	      var _this2 = this;

	      if (!main_core.Type.isPlainObject(params.item)) {
	        return;
	      }
	      /**
	       * Delay so that the element has time to be rendered before deletion,
	       * if an event for changing the element came before. Ticket #141983
	       */


	      var delay = this.queue.has(params.item.id) ? 5000 : 0;
	      setTimeout(function () {
	        _this2.queue.delete(params.item.id);

	        _this2.grid.removeItem(params.item.id);

	        var column = _this2.grid.getColumn(params.item.data.columnId);

	        column.decPrice(params.item.data.price);
	        column.renderSubTitle();
	      }, delay);
	    }
	  }, {
	    key: "onPullStageAdded",
	    value: function onPullStageAdded(params) {
	      this.grid.onApplyFilter();
	    }
	  }, {
	    key: "onPullStageDeleted",
	    value: function onPullStageDeleted(params) {
	      this.grid.removeColumn(params.stage.id);
	    }
	  }, {
	    key: "onPullStageUpdated",
	    value: function onPullStageUpdated(params) {
	      this.grid.onApplyFilter();
	    }
	  }, {
	    key: "onTabActivated",
	    value: function onTabActivated() {
	      if (this.queue.isOverflow()) {
	        this.showOutdatedDataDialog();
	      } else if (this.queue.peek()) {
	        this.queue.loadItem();
	      }
	    }
	  }, {
	    key: "showOutdatedDataDialog",
	    value: function showOutdatedDataDialog() {
	      var _this3 = this;

	      if (!this.notifier) {
	        this.notifier = BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_KANBAN_NOTIFY_OUTDATED_DATA'),
	          closeButton: false,
	          autoHide: false,
	          actions: [{
	            title: main_core.Loc.getMessage('CRM_KANBAN_GRID_RELOAD'),
	            events: {
	              click: function click(event, balloon, action) {
	                balloon.close();

	                _this3.grid.reload();

	                _this3.queue.clear();
	              }
	            }
	          }]
	        });
	      } else {
	        this.notifier.show();
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this3 = this;

	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpen', function (event) {
	        if (_this3.isEntitySlider(event.data[0].slider)) {
	          _this3.queue.freeze();
	        }
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function (event) {
	        if (_this3.isEntitySlider(event.data[0].slider)) {
	          _this3.queue.unfreeze();

	          _this3.onTabActivated();
	        }
	      });
	    }
	  }, {
	    key: "isEntitySlider",
	    value: function isEntitySlider(slider) {
	      var sliderUrl = slider.getUrl();
	      var entityPath = this.grid.getData().entityPath;
	      var maskUrl = entityPath.replace(/\#([^\#]+)\#/, '([\\d]+)');
	      return new RegExp(maskUrl).test(sliderUrl);
	    }
	  }]);
	  return PullManager;
	}();

	exports.PullManager = PullManager;

}((this.BX.Crm.Kanban = this.BX.Crm.Kanban || {}),BX.Event,BX,BX.Main,BX));
//# sourceMappingURL=kanban.js.map
