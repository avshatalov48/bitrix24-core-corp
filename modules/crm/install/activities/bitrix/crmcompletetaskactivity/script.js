(function (exports,main_core) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var namespace = main_core.Reflection.namespace('BX.Crm.Automation.Activity');
	var _form = /*#__PURE__*/new WeakMap();
	var _chosenStages = /*#__PURE__*/new WeakMap();
	var _stages = /*#__PURE__*/new WeakMap();
	var _categoryContainer = /*#__PURE__*/new WeakMap();
	var _stagesContainer = /*#__PURE__*/new WeakMap();
	var CompleteTaskActivity = /*#__PURE__*/function () {
	  function CompleteTaskActivity(options) {
	    babelHelpers.classCallCheck(this, CompleteTaskActivity);
	    _classPrivateFieldInitSpec(this, _form, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _chosenStages, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _stages, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _categoryContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _stagesContainer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _form, document.forms.namedItem(options.formName));
	    babelHelpers.classPrivateFieldSet(this, _categoryContainer, babelHelpers.classPrivateFieldGet(this, _form)['target_category']);
	    babelHelpers.classPrivateFieldSet(this, _stagesContainer, babelHelpers.classPrivateFieldGet(this, _form)['target_status[]']);
	    babelHelpers.classPrivateFieldSet(this, _stages, options.stages);
	    babelHelpers.classPrivateFieldSet(this, _chosenStages, new Set(options.chosenStages));
	  }
	  babelHelpers.createClass(CompleteTaskActivity, [{
	    key: "init",
	    value: function init() {
	      this.updateStages();
	    }
	  }, {
	    key: "updateStages",
	    value: function updateStages() {
	      main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _stagesContainer));
	      this.renderStages();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (babelHelpers.classPrivateFieldGet(this, _categoryContainer)) {
	        babelHelpers.classPrivateFieldGet(this, _categoryContainer).onchange = this.updateStages.bind(this);
	      }
	    }
	  }, {
	    key: "renderStages",
	    value: function renderStages() {
	      var _this = this;
	      if (babelHelpers.classPrivateFieldGet(this, _stages).hasOwnProperty(babelHelpers.classPrivateFieldGet(this, _categoryContainer).value)) {
	        var stages = babelHelpers.classPrivateFieldGet(this, _stages)[babelHelpers.classPrivateFieldGet(this, _categoryContainer).value];
	        stages.forEach(function (_ref) {
	          var id = _ref.id,
	            name = _ref.name;
	          var option = new Option(name, id, false, babelHelpers.classPrivateFieldGet(_this, _chosenStages).has(id));
	          babelHelpers.classPrivateFieldGet(_this, _stagesContainer).append(option);
	        });
	      } else {
	        var _iterator = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _categoryContainer).options),
	          _step;
	        try {
	          var _loop = function _loop() {
	            var categoryOption = _step.value;
	            var categoryId = categoryOption.value;
	            var categoryName = categoryOption.text;
	            if (babelHelpers.classPrivateFieldGet(_this, _stages).hasOwnProperty(categoryId)) {
	              babelHelpers.classPrivateFieldGet(_this, _stages)[categoryId].forEach(function (_ref2) {
	                var id = _ref2.id,
	                  name = _ref2.name;
	                var stageName = main_core.Text.encode("".concat(categoryName, " / ").concat(name));
	                var option = new Option(stageName, id, false, babelHelpers.classPrivateFieldGet(_this, _chosenStages).has(id));
	                babelHelpers.classPrivateFieldGet(_this, _stagesContainer).append(option);
	              });
	            }
	          };
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            _loop();
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      }
	    }
	  }]);
	  return CompleteTaskActivity;
	}();
	namespace.CompleteTaskActivity = CompleteTaskActivity;

	exports.CompleteTaskActivity = CompleteTaskActivity;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
