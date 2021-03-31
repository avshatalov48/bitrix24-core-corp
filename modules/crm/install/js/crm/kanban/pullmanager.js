this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Kanban');

	var _queue = new WeakMap();

	var _grid = new WeakMap();

	var _isProgress = new WeakMap();

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

	    babelHelpers.classPrivateFieldSet(this, _grid, grid);
	    babelHelpers.classPrivateFieldSet(this, _queue, []);
	    babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	  }

	  babelHelpers.createClass(PullQueue, [{
	    key: "loadItem",
	    value: function loadItem(isForce) {
	      var id = this.pop();

	      if (id && (!babelHelpers.classPrivateFieldGet(this, _isProgress) || isForce === true)) {
	        babelHelpers.classPrivateFieldSet(this, _isProgress, true);
	        babelHelpers.classPrivateFieldGet(this, _grid).loadNew(id, false).then(function (response) {
	          if (this.peek()) {
	            this.loadItem(true);
	          } else {
	            babelHelpers.classPrivateFieldSet(this, _isProgress, false);
	          }
	        }.bind(this));
	      }
	    }
	  }, {
	    key: "push",
	    value: function push(id) {
	      if (this.getAll().indexOf(id) === -1) {
	        babelHelpers.classPrivateFieldGet(this, _queue).push(id);
	      }

	      return this;
	    }
	  }, {
	    key: "pop",
	    value: function pop() {
	      return babelHelpers.classPrivateFieldGet(this, _queue).shift();
	    }
	  }, {
	    key: "peek",
	    value: function peek() {
	      return babelHelpers.classPrivateFieldGet(this, _queue).length ? babelHelpers.classPrivateFieldGet(this, _queue)[0] : null;
	    }
	  }, {
	    key: "getAll",
	    value: function getAll() {
	      return babelHelpers.classPrivateFieldGet(this, _queue);
	    }
	  }]);
	  return PullQueue;
	}();

	var namespace$1 = main_core.Reflection.namespace('BX.Crm.Kanban');

	var PullManager = /*#__PURE__*/function () {
	  function PullManager(grid) {
	    babelHelpers.classCallCheck(this, PullManager);
	    this.grid = grid;
	    this.queue = new PullQueue(this.grid);

	    if (main_core.Type.isString(grid.getData().moduleId) && grid.getData().userId > 0) {
	      this.init();
	    }
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

	        item.setActivityExistInnerHtml();
	        item.useAnimation = true;
	        item.setChangedInPullRequest();
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
	      if (!main_core.Type.isPlainObject(params.item)) {
	        return;
	      }

	      this.grid.removeItem(params.item.id);
	      var column = this.grid.getColumn(params.item.data.columnId);
	      column.decPrice(params.item.data.price);
	      column.renderSubTitle();
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
	  }]);
	  return PullManager;
	}();
	namespace$1.PullManager = PullManager;

	exports.default = PullManager;

}((this.BX.Crm.Kanban = this.BX.Crm.Kanban || {}),BX));
//# sourceMappingURL=pullmanager.js.map
