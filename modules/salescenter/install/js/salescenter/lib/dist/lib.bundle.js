this.BX = this.BX || {};
(function (exports,salescenter_manager) {
	'use strict';

	var SenderConfig = /*#__PURE__*/function () {
	  function SenderConfig() {
	    babelHelpers.classCallCheck(this, SenderConfig);
	  }

	  babelHelpers.createClass(SenderConfig, null, [{
	    key: "needConfigure",
	    value: function needConfigure(sender) {
	      if (!sender.isAvailable || sender.isConnected) {
	        return false;
	      }

	      return true;
	    }
	  }, {
	    key: "openSliderFreeMessages",
	    value: function openSliderFreeMessages(url) {
	      return function () {
	        if (typeof url === 'string') {
	          return salescenter_manager.Manager.openSlider(url);
	        }

	        if (babelHelpers["typeof"](url) === 'object' && url !== null) {
	          if (url.type === 'ui_helper') {
	            return BX.loadExt('ui.info-helper').then(function () {
	              BX.UI.InfoHelper.show(url.value);
	            });
	          }
	        }

	        return Promise.resolve();
	      };
	    }
	  }]);
	  return SenderConfig;
	}();
	babelHelpers.defineProperty(SenderConfig, "BITRIX24", 'bitrix24');

	exports.SenderConfig = SenderConfig;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX.Salescenter));
//# sourceMappingURL=lib.bundle.js.map
