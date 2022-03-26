this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var Component = /*#__PURE__*/function () {
	  function Component(options) {
	    babelHelpers.classCallCheck(this, Component);
	    this.options = options;
	    this.bindEvents();
	  }

	  babelHelpers.createClass(Component, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      var buttonClass = this.options.buttonClass;
	      document.querySelectorAll(".".concat(buttonClass)).forEach(function (button) {
	        main_core.Event.bind(button, 'click', _this.handleClickOnTemplate.bind(_this));
	      });
	    }
	  }, {
	    key: "handleClickOnTemplate",
	    value: function handleClickOnTemplate(event) {
	      var templateId = event.currentTarget.dataset.templateId;
	      main_core.Dom.clean(this.options.container);
	      top.window.postMessage({
	        type: 'selectedTemplate'
	      }, '*');
	      var loader = new BX.Loader({
	        target: this.options.container
	      });
	      loader.show();
	      main_core.ajax.runAction('disk.api.integration.messengerCall.createResumeByTemplate', {
	        data: {
	          callId: this.options.call.id,
	          templateId: templateId
	        }
	      }).then(function (response) {
	        if (response.data.document.urlToEdit) {
	          document.location = response.data.document.urlToEdit;
	        }
	      });
	    }
	  }]);
	  return Component;
	}();

	exports.Component = Component;

}((this.BX.Disk.EditorTemplates = this.BX.Disk.EditorTemplates || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map
