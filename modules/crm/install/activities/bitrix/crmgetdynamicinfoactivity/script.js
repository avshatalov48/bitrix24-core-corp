/* eslint-disable */
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
	      if (!main_core.Type.isNil(form)) {
	        this.entityTypeIdSelect = form.dynamic_type_id;
	        this.currentEntityTypeId = Number(this.entityTypeIdSelect.value);
	        this.entityTypeDependentElements = document.querySelectorAll('[data-role="bca-cuda-entity-type-id-dependent"]');
	      }
	      this.document = new bizproc_automation.Document({
	        rawDocumentType: this.documentType,
	        documentFields: options.documentFields,
	        title: options.documentName
	      });
	      this.initAutomationContext();
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

	      // issue 0158608
	      if (!main_core.Type.isNil(options.documentType) && !this.isRobot) {
	        BX.Bizproc.Automation.API.documentType = options.documentType;
	      }
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
	      var _this2 = this;
	      try {
	        bizproc_automation.getGlobalContext();
	        if (this.isRobot) {
	          this.onOpenFilterFieldsMenu = function (event) {
	            var dialog = bizproc_automation.Designer.getInstance().getRobotSettingsDialog();
	            var template = dialog.template;
	            var robot = dialog.robot;
	            if (template && robot) {
	              template.onOpenMenu(event, robot);
	            }
	          };
	        }
	      } catch (error) {
	        bizproc_automation.setGlobalContext(new bizproc_automation.Context({
	          document: this.document
	        }));
	        this.onOpenFilterFieldsMenu = function (event) {
	          return _this2.addBPFields(event.getData().selector);
	        };
	      }
	    }
	  }, {
	    key: "addBPFields",
	    value: function addBPFields(selector) {
	      var getSelectorProperties = function getSelectorProperties(_ref5) {
	        var properties = _ref5.properties,
	          objectName = _ref5.objectName,
	          expressionPrefix = _ref5.expressionPrefix;
	        if (main_core.Type.isObject(properties)) {
	          return Object.entries(properties).map(function (_ref6) {
	            var _ref7 = babelHelpers.slicedToArray(_ref6, 2),
	              id = _ref7[0],
	              property = _ref7[1];
	            return {
	              id: id,
	              title: property.Name,
	              customData: {
	                field: {
	                  Id: id,
	                  Type: property.Type,
	                  Name: property.Name,
	                  ObjectName: objectName,
	                  SystemExpression: "{=".concat(objectName, ":").concat(id, "}"),
	                  Expression: expressionPrefix ? "{{".concat(expressionPrefix, ":").concat(id, "}}") : "{=".concat(objectName, ":").concat(id, "}")
	                }
	              }
	            };
	          });
	        }
	        return [];
	      };
	      var getGlobalSelectorProperties = function getGlobalSelectorProperties(_ref8) {
	        var properties = _ref8.properties,
	          visibilityNames = _ref8.visibilityNames,
	          objectName = _ref8.objectName;
	        if (main_core.Type.isObject(properties)) {
	          return Object.entries(properties).map(function (_ref9) {
	            var _ref10 = babelHelpers.slicedToArray(_ref9, 2),
	              id = _ref10[0],
	              property = _ref10[1];
	            var field = {
	              id: id,
	              Type: property.Type,
	              title: property.Name,
	              ObjectName: objectName,
	              SystemExpression: "{=".concat(objectName, ":").concat(id, "}"),
	              Expression: "{=".concat(objectName, ":").concat(id, "}")
	            };
	            if (property.Visibility && visibilityNames[property.Visibility]) {
	              field.Expression = "{{".concat(visibilityNames[property.Visibility], ": ").concat(property.Name, "}}");
	            }
	            return {
	              id: id,
	              title: property.Name,
	              supertitle: visibilityNames[property.Visibility],
	              customData: {
	                field: field
	              }
	            };
	          });
	        }
	        return [];
	      };
	      selector.addGroup('workflowParameters', {
	        id: 'workflowParameters',
	        title: main_core.Loc.getMessage('BIZPROC_WFEDIT_MENU_PARAMS'),
	        children: [{
	          id: 'parameters',
	          title: main_core.Loc.getMessage('BIZPROC_AUTOMATION_CMP_PARAMETERS_LIST'),
	          children: getSelectorProperties({
	            properties: window.arWorkflowParameters || {},
	            objectName: 'Template',
	            expressionPrefix: '~*'
	          })
	        }, {
	          id: 'variables',
	          title: main_core.Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_VARIABLES_LIST_1'),
	          children: getSelectorProperties({
	            properties: window.arWorkflowVariables || {},
	            objectName: 'Variable'
	          })
	        }, {
	          id: 'constants',
	          title: main_core.Loc.getMessage('BIZPROC_AUTOMATION_CMP_CONSTANTS_LIST'),
	          children: getSelectorProperties({
	            properties: window.arWorkflowConstants || {},
	            objectName: 'Constant',
	            expressionPrefix: '~&'
	          })
	        }]
	      });
	      if (window.arWorkflowGlobalVariables && window.wfGVarVisibilityNames) {
	        selector.addGroup('globalVariables', {
	          id: 'globalVariables',
	          title: main_core.Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_VARIABLES_LIST'),
	          children: getGlobalSelectorProperties({
	            properties: window.arWorkflowGlobalVariables || {},
	            visibilityNames: window.wfGVarVisibilityNames || {},
	            objectName: 'GlobalVar'
	          })
	        });
	      }
	      selector.addGroup('globalConstants', {
	        id: 'globalConstants',
	        title: main_core.Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_CONSTANTS_LIST'),
	        children: getGlobalSelectorProperties({
	          properties: window.arWorkflowGlobalConstants || {},
	          visibilityNames: window.wfGConstVisibilityNames || {},
	          objectName: 'GlobalConst'
	        })
	      });
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
	          fieldPrefix: this.filteringFieldsPrefix,
	          onOpenMenu: this.onOpenFilterFieldsMenu,
	          caption: {
	            head: main_core.Loc.getMessage('CRM_GDIA_FILTERING_FIELDS_PROPERTY'),
	            collapsed: main_core.Loc.getMessage('CRM_GDIA_FILTERING_FIELDS_COLLAPSED_TEXT')
	          }
	        });

	        // todo: remove 2024 with this.filterFieldsContainer.parentNode.firstElementChild
	        if (selector.modern && this.filterFieldsContainer && this.filterFieldsContainer.parentNode) {
	          var element = this.filterFieldsContainer.parentNode.firstElementChild === this.filterFieldsContainer ? this.filterFieldsContainer.parentNode.parentNode.firstElementChild : this.filterFieldsContainer.parentNode.firstElementChild;
	          main_core.Dom.clean(element);
	        }
	        main_core.Dom.clean(this.filterFieldsContainer);
	        main_core.Dom.append(selector.createNode(), this.filterFieldsContainer);
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

}((this.window = this.window || {}),BX,BX.Bizproc.Automation));
//# sourceMappingURL=script.js.map
