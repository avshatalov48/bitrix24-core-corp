this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
(function (exports,main_popup) {
	'use strict';

	var Popup = /*#__PURE__*/function () {
	  function Popup() {
	    babelHelpers.classCallCheck(this, Popup);
	  }
	  babelHelpers.createClass(Popup, [{
	    key: "show",
	    value: function show(target, message, timer) {
	      var _this = this;
	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }
	      if (!target && !message) {
	        return;
	      }
	      this.popup = new main_popup.Popup(null, target, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this.popup.destroy();
	            _this.popup = null;
	          }
	        },
	        darkMode: true,
	        content: message,
	        offsetLeft: target.offsetWidth
	      });
	      if (timer) {
	        setTimeout(function () {
	          _this.popup.destroy();
	          _this.popup = null;
	        }, timer);
	      }
	      this.popup.show();
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      if (this.popup) {
	        this.popup.destroy();
	      }
	    }
	  }]);
	  return Popup;
	}();

	exports.Popup = Popup;

}((this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {}),BX.Main));
//# sourceMappingURL=hint.bundle.js.map
