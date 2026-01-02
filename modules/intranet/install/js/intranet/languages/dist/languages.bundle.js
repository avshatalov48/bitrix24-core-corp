this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var Languages = /*#__PURE__*/function () {
	  function Languages() {
	    babelHelpers.classCallCheck(this, Languages);
	  }

	  babelHelpers.createClass(Languages, [{
	    key: "getLanguages",
	    value: function getLanguages() {
	      return main_core.Extension.getSettings('intranet.languages').languages;
	    }
	  }]);
	  return Languages;
	}();

	exports.Languages = Languages;

}((this.BX.Intranet = this.BX.Intranet || {}),BX));
//# sourceMappingURL=languages.bundle.js.map
