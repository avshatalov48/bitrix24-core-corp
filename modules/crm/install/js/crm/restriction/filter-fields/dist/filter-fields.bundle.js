this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var FilterFieldsRestriction = /*#__PURE__*/function () {
	  function FilterFieldsRestriction(options) {
	    babelHelpers.classCallCheck(this, FilterFieldsRestriction);
	    this.options = options;
	    this.bindAddFilterItemEvent();
	    this.bindGridSortEvent();
	    this.bindCheckboxListOptionClick();
	  }
	  babelHelpers.createClass(FilterFieldsRestriction, [{
	    key: "bindAddFilterItemEvent",
	    value: function bindAddFilterItemEvent() {
	      var _this$options$filterI,
	        _this = this;
	      var filterId = (_this$options$filterI = this.options.filterId) !== null && _this$options$filterI !== void 0 ? _this$options$filterI : null;
	      if (filterId && BX.Main.filterManager) {
	        var filter = BX.Main.filterManager.getById(filterId);
	        if (filter) {
	          filter.getEmitter().subscribe('onBeforeChangeFilterItems', function (event) {
	            var eventData = event.getData();
	            var fields = eventData.fields,
	              oldFields = eventData.oldFields;
	            var newFields = fields.filter(function (field) {
	              return !oldFields.includes(field);
	            });
	            var hasRestrictions = newFields.some(function (field) {
	              return _this.isRestrictedFilterField(field);
	            });
	            if (hasRestrictions) {
	              event.preventDefault();
	              _this.callRestrictionCallback();
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "bindGridSortEvent",
	    value: function bindGridSortEvent() {
	      var _this$options$gridId,
	        _this2 = this;
	      var gridId = (_this$options$gridId = this.options.gridId) !== null && _this$options$gridId !== void 0 ? _this$options$gridId : null;
	      if (gridId && BX.Main.gridManager) {
	        main_core.Event.EventEmitter.subscribe('BX.Main.grid:onBeforeSort', function (event) {
	          var _event$getData = event.getData(),
	            grid = _event$getData.grid,
	            columnName = _event$getData.columnName;
	          if (grid.getId() === gridId && _this2.isRestrictedGridField(columnName)) {
	            event.preventDefault();
	            _this2.callRestrictionCallback();
	          }
	        });
	      }
	    }
	  }, {
	    key: "bindCheckboxListOptionClick",
	    value: function bindCheckboxListOptionClick() {
	      var _this3 = this;
	      main_core.Event.EventEmitter.subscribe('ui:checkbox-list:check-option', function (event) {
	        var _event$getData2 = event.getData(),
	          id = _event$getData2.id,
	          context = _event$getData2.context;
	        if (!main_core.Type.isPlainObject(context) || !main_core.Type.isStringFilled(context.parentType)) {
	          return;
	        }
	        if (context.parentType === 'filter' && _this3.isRestrictedFilterField(id)) {
	          event.preventDefault();
	          _this3.callRestrictionCallback();
	        }
	        if (context.parentType === 'grid' && _this3.isRestrictedGridField(id)) {
	          event.preventDefault();
	          _this3.callRestrictionCallback();
	        }
	      });
	    }
	  }, {
	    key: "isRestrictedFilterField",
	    value: function isRestrictedFilterField(fieldName) {
	      var _this$options$filterF;
	      var fields = (_this$options$filterF = this.options.filterFields) !== null && _this$options$filterF !== void 0 ? _this$options$filterF : [];
	      return main_core.Type.isArray(fields) && fields.includes(fieldName);
	    }
	  }, {
	    key: "isRestrictedGridField",
	    value: function isRestrictedGridField(fieldName) {
	      var _this$options$gridFie;
	      var fields = (_this$options$gridFie = this.options.gridFields) !== null && _this$options$gridFie !== void 0 ? _this$options$gridFie : [];
	      return main_core.Type.isArray(fields) && fields.includes(fieldName);
	    }
	  }, {
	    key: "callRestrictionCallback",
	    value: function callRestrictionCallback() {
	      if (main_core.Type.isStringFilled(this.options.callback)) {
	        eval(this.options.callback);
	      }
	    }
	  }]);
	  return FilterFieldsRestriction;
	}();

	exports.FilterFieldsRestriction = FilterFieldsRestriction;

}((this.BX.Crm.Restriction = this.BX.Crm.Restriction || {}),BX));
//# sourceMappingURL=filter-fields.bundle.js.map
