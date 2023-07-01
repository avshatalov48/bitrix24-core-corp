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
	var _isRobot = /*#__PURE__*/new WeakMap();
	var CompleteTaskActivity = /*#__PURE__*/function () {
	  function CompleteTaskActivity(options) {
	    var _this = this;
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
	    _classPrivateFieldInitSpec(this, _isRobot, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _form, document.forms.namedItem(options.formName));
	    if (!main_core.Type.isArray(options.chosenStages)) {
	      options.chosenStages = [];
	    }
	    babelHelpers.classPrivateFieldSet(this, _categoryContainer, babelHelpers.classPrivateFieldGet(this, _form)['target_category']);
	    babelHelpers.classPrivateFieldSet(this, _stagesContainer, babelHelpers.classPrivateFieldGet(this, _form)['target_status[]']);
	    babelHelpers.classPrivateFieldSet(this, _chosenStages, new Set(options.chosenStages.map(function (stageId) {
	      return String(stageId);
	    })));
	    babelHelpers.classPrivateFieldSet(this, _isRobot, options.isRobot);
	    babelHelpers.classPrivateFieldSet(this, _stages, {});
	    if (main_core.Type.isPlainObject(options.stages)) {
	      for (var _i = 0, _Object$entries = Object.entries(options.stages); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	          categoryId = _Object$entries$_i[0],
	          stages = _Object$entries$_i[1];
	        // Due to http://jabber.bx/view.php?id=169508
	        // we have to cast types explicitly
	        babelHelpers.classPrivateFieldGet(this, _stages)[categoryId] = stages.map(function (stageInfo) {
	          return {
	            id: String(stageInfo.id),
	            name: String(stageInfo.name)
	          };
	        });
	      }
	    } else if (main_core.Type.isArray(options.stages)) {
	      // Due to http://jabber.bx/view.php?id=169508
	      // we have to cast types explicitly
	      options.stages.forEach(function (categoryStages, categoryId) {
	        babelHelpers.classPrivateFieldGet(_this, _stages)[categoryId] = categoryStages.map(function (stageInfo) {
	          return {
	            id: String(stageInfo.id),
	            name: String(stageInfo.name)
	          };
	        });
	      });
	    }
	  }
	  babelHelpers.createClass(CompleteTaskActivity, [{
	    key: "init",
	    value: function init() {
	      if (babelHelpers.classPrivateFieldGet(this, _categoryContainer).options.length <= 1) {
	        if (babelHelpers.classPrivateFieldGet(this, _isRobot)) {
	          main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _categoryContainer).parentElement);
	        } else {
	          main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _categoryContainer).parentElement.parentElement);
	        }
	      } else {
	        this.updateStages();
	      }
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
	      var _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _stages).hasOwnProperty(babelHelpers.classPrivateFieldGet(this, _categoryContainer).value)) {
	        var stages = babelHelpers.classPrivateFieldGet(this, _stages)[babelHelpers.classPrivateFieldGet(this, _categoryContainer).value];
	        stages.forEach(function (_ref) {
	          var id = _ref.id,
	            name = _ref.name;
	          var option = new Option(name, id, false, babelHelpers.classPrivateFieldGet(_this2, _chosenStages).has(id));
	          babelHelpers.classPrivateFieldGet(_this2, _stagesContainer).append(option);
	        });
	      } else {
	        var _iterator = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _categoryContainer).options),
	          _step;
	        try {
	          var _loop = function _loop() {
	            var categoryOption = _step.value;
	            var categoryId = categoryOption.value;
	            var categoryName = categoryOption.text;
	            if (babelHelpers.classPrivateFieldGet(_this2, _stages).hasOwnProperty(categoryId)) {
	              babelHelpers.classPrivateFieldGet(_this2, _stages)[categoryId].forEach(function (_ref2) {
	                var id = _ref2.id,
	                  name = _ref2.name;
	                var stageName = main_core.Text.encode("".concat(categoryName, " / ").concat(name));
	                var option = new Option(stageName, id, false, babelHelpers.classPrivateFieldGet(_this2, _chosenStages).has(id));
	                babelHelpers.classPrivateFieldGet(_this2, _stagesContainer).append(option);
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
