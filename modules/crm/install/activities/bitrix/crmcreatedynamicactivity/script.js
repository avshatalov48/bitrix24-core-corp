(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var CrmCreateDynamicActivity = /*#__PURE__*/function () {
	  function CrmCreateDynamicActivity(options) {
	    babelHelpers.classCallCheck(this, CrmCreateDynamicActivity);
	    babelHelpers.defineProperty(this, "entitiesFieldsContainers", new Map());

	    if (main_core.Type.isPlainObject(options)) {
	      var form = document.forms[options.formName];

	      if (!main_core.Type.isNil(form)) {
	        this.entityTypeIdSelect = form['dynamic_type_id'];
	        this.currentEntityTypeId = this.entityTypeIdSelect.value;
	      }

	      if (main_core.Type.isString(options.fieldsContainerIdPrefix)) {
	        this.fieldsContainerIdPrefix = options.fieldsContainerIdPrefix;
	      }
	    }
	  }

	  babelHelpers.createClass(CrmCreateDynamicActivity, [{
	    key: "init",
	    value: function init() {
	      if (this.entityTypeIdSelect && this.fieldsContainerIdPrefix) {
	        main_core.Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
	      }
	    }
	  }, {
	    key: "onEntityTypeIdChange",
	    value: function onEntityTypeIdChange() {
	      main_core.Dom.hide(this.getEntityFieldsContainer(this.currentEntityTypeId));
	      this.currentEntityTypeId = this.entityTypeIdSelect.value;
	      main_core.Dom.show(this.getEntityFieldsContainer(this.currentEntityTypeId));
	    }
	  }, {
	    key: "getEntityFieldsContainer",
	    value: function getEntityFieldsContainer(entityTypeId) {
	      if (!this.entitiesFieldsContainers.has(entityTypeId)) {
	        this.entitiesFieldsContainers.set(entityTypeId, document.getElementById(this.fieldsContainerIdPrefix + entityTypeId));
	      }

	      return this.entitiesFieldsContainers.get(entityTypeId);
	    }
	  }]);
	  return CrmCreateDynamicActivity;
	}();

	namespace.CrmCreateDynamicActivity = CrmCreateDynamicActivity;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
