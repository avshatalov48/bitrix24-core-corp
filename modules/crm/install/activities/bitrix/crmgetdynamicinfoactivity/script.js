(function (exports,main_core,bizproc_automation) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var CrmGetDynamicInfoActivity = /*#__PURE__*/function () {
	  function CrmGetDynamicInfoActivity(options) {
	    babelHelpers.classCallCheck(this, CrmGetDynamicInfoActivity);

	    if (main_core.Type.isPlainObject(options)) {
	      this.documentType = options.documentType;
	      this.isRobot = options.isRobot;
	      var form = document.forms[options.formName];
	      this.document = new bizproc_automation.Document({
	        rawDocumentType: this.documentType,
	        documentFields: options.returnFieldsMap,
	        title: options.documentName
	      });

	      if (!main_core.Type.isNil(form)) {
	        this.entityTypeIdSelect = form.dynamic_type_id;
	        this.currentEntityTypeId = Number(this.entityTypeIdSelect.value);
	        this.entityTypeDependentElements = document.querySelectorAll('[data-role="bca-cuda-entity-type-id-dependent"]');
	      }

	      this.initFilterFields(options);
	      this.initReturnFields(options);
	      this.render();
	    }
	  }

	  babelHelpers.createClass(CrmGetDynamicInfoActivity, [{
	    key: "initFilterFields",
	    value: function initFilterFields(options) {
	      this.conditinIdPrefix = 'id_bca_cuda_field_';
	      this.filterFieldsContainer = document.querySelector('[data-role="bca-cuda-filter-fields-container"]');
	      this.filteringFieldsPrefix = options.filteringFieldsPrefix;
	      this.filterFieldsMap = new Map(Object.entries(options.filterFieldsMap).map(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            entityTypeId = _ref2[0],
	            fieldsMap = _ref2[1];

	        return [Number(entityTypeId), fieldsMap];
	      }));
	      this.conditionGroup = new bizproc_automation.ConditionGroup(options.conditions);
	    }
	  }, {
	    key: "initReturnFields",
	    value: function initReturnFields(options) {
	      var _this = this;

	      this.returnFieldsProperty = options.returnFieldsProperty;
	      this.returnFieldsIds = main_core.Type.isArray(options.returnFieldsIds) ? options.returnFieldsIds : [];
	      this.returnFieldsMapContainer = document.querySelector('[data-role="bca-cuda-return-fields-container"]');
	      this.returnFieldsMap = new Map();
	      Object.entries(options.returnFieldsMap).forEach(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	            entityTypeId = _ref4[0],
	            fieldsMap = _ref4[1];

	        _this.returnFieldsMap.set(Number(entityTypeId), new Map(Object.entries(fieldsMap)));
	      });
	    }
	  }, {
	    key: "initAutomationContext",
	    value: function initAutomationContext() {
	      try {
	        bizproc_automation.getGlobalContext();
	      } catch (error) {
	        bizproc_automation.setGlobalContext(new bizproc_automation.AutomationContext({
	          document: this.document
	        }));
	      }
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      if (this.entityTypeIdSelect) {
	        main_core.Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
	      }
	    }
	  }, {
	    key: "onEntityTypeIdChange",
	    value: function onEntityTypeIdChange() {
	      this.currentEntityTypeId = Number(this.entityTypeIdSelect.value);
	      this.conditionGroup = new bizproc_automation.ConditionGroup();
	      this.returnFieldsIds = [];
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (main_core.Type.isNil(this.currentEntityTypeId) || this.currentEntityTypeId === 0) {
	        this.entityTypeDependentElements.forEach(function (element) {
	          return main_core.Dom.hide(element);
	        });
	      } else {
	        this.entityTypeDependentElements.forEach(function (element) {
	          return main_core.Dom.show(element);
	        });
	        this.renderFilterFields();
	        this.renderReturnFields();
	      }
	    }
	  }, {
	    key: "renderFilterFields",
	    value: function renderFilterFields() {
	      if (!main_core.Type.isNil(this.conditionGroup) && this.currentEntityTypeId !== 0) {
	        var selector = new bizproc_automation.ConditionGroupSelector(this.conditionGroup, {
	          fields: Object.values(this.filterFieldsMap.get(this.currentEntityTypeId)),
	          fieldPrefix: this.filteringFieldsPrefix
	        });
	        main_core.Dom.clean(this.filterFieldsContainer);
	        this.filterFieldsContainer.appendChild(selector.createNode());
	      }
	    }
	  }, {
	    key: "renderReturnFields",
	    value: function renderReturnFields() {
	      var entityTypeId = this.currentEntityTypeId;
	      var fieldsMap = this.returnFieldsMap.get(entityTypeId);

	      if (!main_core.Type.isNil(fieldsMap)) {
	        var fieldOptions = {};
	        fieldsMap.forEach(function (field, fieldId) {
	          fieldOptions[fieldId] = field.Name;
	        });
	        this.returnFieldsProperty.Options = fieldOptions;
	        main_core.Dom.clean(this.returnFieldsMapContainer);
	        this.returnFieldsMapContainer.appendChild(BX.Bizproc.FieldType.renderControl(this.documentType, this.returnFieldsProperty, this.returnFieldsProperty.FieldName, this.returnFieldsIds, this.isRobot ? 'public' : 'designer'));
	      }
	    }
	  }]);
	  return CrmGetDynamicInfoActivity;
	}();

	namespace.CrmGetDynamicInfoActivity = CrmGetDynamicInfoActivity;

}((this.window = this.window || {}),BX,BX.Bizproc));
//# sourceMappingURL=script.js.map
