(function (exports,main_core) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var CrmChangeDynamicCategoryActivity = /*#__PURE__*/function () {
	  function CrmChangeDynamicCategoryActivity(options) {
	    babelHelpers.classCallCheck(this, CrmChangeDynamicCategoryActivity);

	    if (main_core.Type.isPlainObject(options)) {
	      var form = document.forms[options.formName];

	      if (!main_core.Type.isNil(form)) {
	        this.categorySelect = form['category_id'];
	        this.stageSelect = form['stage_id'];
	      }
	    }
	  }

	  babelHelpers.createClass(CrmChangeDynamicCategoryActivity, [{
	    key: "init",
	    value: function init() {
	      if (!this.categorySelect || !this.stageSelect) {
	        return false;
	      }

	      main_core.Event.bind(this.categorySelect, 'change', this.filter.bind(this));
	      this.filter();
	    }
	  }, {
	    key: "filter",
	    value: function filter() {
	      var categoryId = this.categorySelect.value;
	      var prefix = "C".concat(categoryId, ":");

	      var _iterator = _createForOfIteratorHelper(this.stageSelect.options),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var opt = _step.value;

	          if (opt.value === '') {
	            continue;
	          }

	          opt.disabled = opt.value.indexOf(prefix) < 0;

	          if (opt.disabled === main_core.Dom.isShown(opt)) {
	            main_core.Dom.toggle(opt);
	          }

	          if (opt.disabled && opt.value === this.stageSelect.value) {
	            opt.selected = false;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }]);
	  return CrmChangeDynamicCategoryActivity;
	}();

	namespace.CrmChangeDynamicCategoryActivity = CrmChangeDynamicCategoryActivity;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
