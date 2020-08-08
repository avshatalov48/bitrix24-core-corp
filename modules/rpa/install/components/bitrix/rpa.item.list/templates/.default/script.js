(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var ItemsListComponent =
	/*#__PURE__*/
	function () {
	  function ItemsListComponent(params) {
	    babelHelpers.classCallCheck(this, ItemsListComponent);
	    babelHelpers.defineProperty(this, "typeId", 0);
	    babelHelpers.defineProperty(this, "gridId", null);
	    babelHelpers.defineProperty(this, "kanbanPullTag", null);
	    babelHelpers.defineProperty(this, "grid", null);

	    if (main_core.Type.isPlainObject(params)) {
	      this.typeId = main_core.Text.toInteger(params.typeId);
	      this.gridId = params.gridId;
	      this.kanbanPullTag = params.kanbanPullTag;
	    }

	    this.eventIds = new Set();
	    this.bindEvents();
	  }

	  babelHelpers.createClass(ItemsListComponent, [{
	    key: "getGrid",
	    value: function getGrid() {
	      if (this.grid) {
	        return this.grid;
	      }

	      if (this.gridId && BX.Main.grid && BX.Main.gridManager) {
	        this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	      }

	      return this.grid;
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      main_core.Event.ready(function () {
	        var Pull = BX.PULL;

	        if (!Pull) {
	          console.error('pull is not initialized');
	          return;
	        }

	        if (main_core.Type.isString(_this.kanbanPullTag) && _this.typeId > 0) {
	          Pull.subscribe({
	            moduleId: 'rpa',
	            command: _this.kanbanPullTag,
	            callback: function callback(params) {
	              if (main_core.Type.isString(params.eventName)) {
	                if (main_core.Type.isString(params.eventId)) {
	                  if (_this.eventIds.has(params.eventId)) {
	                    return;
	                  }
	                }

	                if (params.eventName.indexOf('ITEMUPDATED' + _this.typeId) === 0 && main_core.Type.isPlainObject(params.item)) {
	                  _this.onPullItemUpdated(params.item);
	                }
	              }
	            }
	          });
	        }
	      });
	    }
	  }, {
	    key: "onPullItemUpdated",
	    value: function onPullItemUpdated(item) {
	      var grid = this.getGrid();

	      if (!grid) {
	        return;
	      }

	      var row = grid.getRows().getById(item.id);

	      if (!row) {
	        return;
	      }

	      row.update();
	    }
	  }]);
	  return ItemsListComponent;
	}();

	namespace.ItemsListComponent = ItemsListComponent;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
