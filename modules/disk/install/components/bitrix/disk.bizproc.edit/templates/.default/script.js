(function (exports,main_core,bizproc_globals) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Disk.Component');

	var BizprocEditComponent = /*#__PURE__*/function () {
	  function BizprocEditComponent(options) {
	    babelHelpers.classCallCheck(this, BizprocEditComponent);

	    if (main_core.Type.isPlainObject(options)) {
	      this.signedDocumentType = String(options.signedDocumentType);
	    }
	  }

	  babelHelpers.createClass(BizprocEditComponent, [{
	    key: "showGlobalVariables",
	    value: function showGlobalVariables() {
	      var me = this;
	      bizproc_globals.Globals.Manager.Instance.showGlobals(bizproc_globals.Globals.Manager.Instance.mode.variable, String(this.signedDocumentType)).then(function (slider) {
	        me.onAfterSliderClose(slider);
	      });
	    }
	  }, {
	    key: "showGlobalConstants",
	    value: function showGlobalConstants() {
	      var me = this;
	      bizproc_globals.Globals.Manager.Instance.showGlobals(bizproc_globals.Globals.Manager.Instance.mode.constant, String(this.signedDocumentType)).then(function (slider) {
	        me.onAfterSliderClose(slider);
	      });
	    }
	  }, {
	    key: "onAfterSliderClose",
	    value: function onAfterSliderClose(slider) {//do smt
	    }
	  }]);
	  return BizprocEditComponent;
	}();

	namespace.BizprocEditComponent = BizprocEditComponent;

}((this.window = this.window || {}),BX,BX.Bizproc));
//# sourceMappingURL=script.js.map
