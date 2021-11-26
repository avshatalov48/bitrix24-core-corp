(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var CrmChangeRelationsActivity = /*#__PURE__*/function () {
	  function CrmChangeRelationsActivity(options) {
	    babelHelpers.classCallCheck(this, CrmChangeRelationsActivity);

	    if (main_core.Type.isPlainObject(options)) {
	      var form = document.forms[options.formName];

	      if (!main_core.Type.isNil(form)) {
	        this.actionTypeSelect = form.action;
	        this.parentIdPropertyDiv = form.parent_id.parentElement.parentElement;
	      }

	      this.onActionTypeChange();
	    }
	  }

	  babelHelpers.createClass(CrmChangeRelationsActivity, [{
	    key: "init",
	    value: function init() {
	      main_core.Event.bind(this.actionTypeSelect, 'change', this.onActionTypeChange.bind(this));
	    }
	  }, {
	    key: "onActionTypeChange",
	    value: function onActionTypeChange() {
	      if (this.actionTypeSelect.value === 'remove') {
	        main_core.Dom.style(this.parentIdPropertyDiv, 'visibility', 'hidden');
	      } else {
	        main_core.Dom.style(this.parentIdPropertyDiv, 'visibility', 'visible');
	      }
	    }
	  }]);
	  return CrmChangeRelationsActivity;
	}();

	namespace.CrmChangeRelationsActivity = CrmChangeRelationsActivity;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
