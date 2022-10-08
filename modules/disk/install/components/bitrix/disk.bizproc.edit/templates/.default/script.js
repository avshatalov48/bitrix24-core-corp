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
	      var _this = this;

	      bizproc_globals.Globals.Manager.Instance.showGlobals(bizproc_globals.Globals.Manager.Instance.mode.variable, String(this.signedDocumentType)).then(function (slider) {
	        _this.onAfterSliderClose(slider, window.arWorkflowGlobalVariables);
	      });
	    }
	  }, {
	    key: "showGlobalConstants",
	    value: function showGlobalConstants() {
	      var _this2 = this;

	      bizproc_globals.Globals.Manager.Instance.showGlobals(bizproc_globals.Globals.Manager.Instance.mode.constant, String(this.signedDocumentType)).then(function (slider) {
	        _this2.onAfterSliderClose(slider, window.arWorkflowGlobalConstants);
	      });
	    }
	  }, {
	    key: "onAfterSliderClose",
	    value: function onAfterSliderClose(slider, target) {
	      var sliderInfo = slider.getData();

	      if (sliderInfo.get('upsert')) {
	        var newGFields = sliderInfo.get('upsert');

	        for (var fieldId in newGFields) {
	          target[fieldId] = newGFields[fieldId];
	        }
	      }

	      if (sliderInfo.get('delete')) {
	        var deletedGFields = sliderInfo.get('delete');

	        for (var i in deletedGFields) {
	          delete target[deletedGFields[i]];
	        }
	      }
	    }
	  }]);
	  return BizprocEditComponent;
	}();

	namespace.BizprocEditComponent = BizprocEditComponent;

}((this.window = this.window || {}),BX,BX.Bizproc));
//# sourceMappingURL=script.js.map
