/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _target = /*#__PURE__*/new WeakMap();
	var Badge = /*#__PURE__*/function () {
	  function Badge(target) {
	    babelHelpers.classCallCheck(this, Badge);
	    _classPrivateFieldInitSpec(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _target, target);
	  }
	  babelHelpers.createClass(Badge, [{
	    key: "init",
	    value: function init() {
	      var _params$hint,
	        _this = this;
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var hint = (_params$hint = params === null || params === void 0 ? void 0 : params.hint) !== null && _params$hint !== void 0 ? _params$hint : main_core.Dom.attr(babelHelpers.classPrivateFieldGet(this, _target), 'data-badgehint');
	      if (!main_core.Type.isStringFilled(hint)) {
	        return;
	      }
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _target), '--hint');
	      main_core.Event.unbindAll(babelHelpers.classPrivateFieldGet(this, _target), 'mouseover');
	      main_core.Event.unbindAll(babelHelpers.classPrivateFieldGet(this, _target), 'mouseleave');
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _target), 'mouseover', function (event) {
	        main_core.Runtime.debounce(function () {
	          BX.UI.Hint.show(babelHelpers.classPrivateFieldGet(_this, _target), hint);

	          // hide the title in the grid so that it does not overlap the hint
	          var parentRow = event.target.closest('tr');
	          if (!parentRow) {
	            return;
	          }
	          var title = parentRow.getAttribute('title');
	          if (main_core.Type.isStringFilled(title)) {
	            parentRow.setAttribute('data-title', title);
	            parentRow.removeAttribute('title');
	          }
	        }, 50, _this)();
	      });
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _target), 'mouseleave', function (event) {
	        BX.UI.Hint.hide(babelHelpers.classPrivateFieldGet(_this, _target));
	        var parentRow = event.target.closest('tr');
	        if (!parentRow) {
	          return;
	        }
	        var title = parentRow.getAttribute('data-title');
	        if (main_core.Type.isStringFilled(title)) {
	          parentRow.setAttribute('title', title);
	        }
	        parentRow.removeAttribute('data-title');
	      });
	    }
	  }]);
	  return Badge;
	}();

	exports.Badge = Badge;

}((this.BX.Crm = this.BX.Crm || {}),BX));
//# sourceMappingURL=badge.bundle.js.map
