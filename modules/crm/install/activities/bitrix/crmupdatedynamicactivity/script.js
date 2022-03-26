(function (exports,main_core,main_popup) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var CrmUpdateDynamicActivity = /*#__PURE__*/function () {
	  function CrmUpdateDynamicActivity(options) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, CrmUpdateDynamicActivity);

	    if (main_core.Type.isPlainObject(options)) {
	      this.documentType = options.documentType;
	      this.isRobot = options.isRobot;
	      var form = document.forms[options.formName];

	      if (!main_core.Type.isNil(form)) {
	        this.entityTypeIdSelect = form.dynamic_type_id;
	        this.currentEntityTypeId = this.entityTypeIdSelect.value;
	        this.entityTypeDependentElements = document.querySelectorAll('[data-role="bca-cuda-entity-type-id-dependent"]');
	      }

	      if (this.isRobot) {
	        this.fieldsListSelect = document.querySelector('[data-role="bca-cuda-fields-list"]');
	      } else {
	        this.addConditionButton = document.querySelector('[data-role="bca_cuda_add_condition"]');
	      }

	      this.entitiesFieldsContainers = document.querySelector('[data-role="bca-cuda-fields-container"]');
	      this.conditinIdPrefix = 'id_bca_cuda_field_';
	      this.fieldsMap = new Map(Object.entries(options.fieldsMap));
	      this.filterFieldsContainer = document.querySelector('[data-role="bca-cuda-filter-fields-container"]');
	      this.filteringFieldsPrefix = options.filteringFieldsPrefix;
	      this.filterFieldsMap = new Map(Object.entries(options.filterFieldsMap));

	      if (!main_core.Type.isNil(options.documentName)) {
	        BX.Bizproc.Automation.API.documentName = options.documentName;
	      }

	      if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup) {
	        this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup(options.conditions);
	      }

	      this.currentValues = new Map();
	      Array.from(this.fieldsMap.keys()).forEach(function (entityTypeId) {
	        return _this.currentValues.set(entityTypeId, {});
	      });

	      if (!main_core.Type.isNil(this.currentEntityTypeId) && main_core.Type.isObject(options.currentValues)) {
	        this.currentValues.set(this.currentEntityTypeId, options.currentValues);
	      }

	      this.render();
	    }
	  }

	  babelHelpers.createClass(CrmUpdateDynamicActivity, [{
	    key: "init",
	    value: function init() {
	      if (this.entityTypeIdSelect && this.fieldsMap && this.entitiesFieldsContainers) {
	        main_core.Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
	      }

	      if (this.isRobot && this.fieldsListSelect) {
	        main_core.Event.bind(this.fieldsListSelect, 'click', this.onFieldsListSelectClick.bind(this));
	      } else if (!this.isRobot && this.addConditionButton) {
	        main_core.Event.bind(this.addConditionButton, 'click', this.onAddConditionButtonClick.bind(this));
	      }
	    }
	  }, {
	    key: "onEntityTypeIdChange",
	    value: function onEntityTypeIdChange() {
	      this.currentEntityTypeId = this.entityTypeIdSelect.value;
	      main_core.Dom.clean(this.filterFieldsContainer);

	      if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup) {
	        this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup();
	      }

	      Array.from(this.entitiesFieldsContainers.children).forEach(function (elem) {
	        return main_core.Dom.remove(elem);
	      });
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (main_core.Type.isNil(this.currentEntityTypeId) || this.currentEntityTypeId === '') {
	        this.entityTypeDependentElements.forEach(function (element) {
	          return main_core.Dom.hide(element);
	        });
	      } else {
	        this.entityTypeDependentElements.forEach(function (element) {
	          return main_core.Dom.show(element);
	        });
	        this.renderFilterFields();
	        this.renderEntityFields();
	      }
	    }
	  }, {
	    key: "renderFilterFields",
	    value: function renderFilterFields() {
	      if (!main_core.Type.isNil(this.conditionGroup) && !main_core.Type.isNil(this.currentEntityTypeId)) {
	        var selector = new BX.Bizproc.Automation.ConditionGroupSelector(this.conditionGroup, {
	          fields: Object.values(this.filterFieldsMap.get(this.currentEntityTypeId)),
	          fieldPrefix: this.filteringFieldsPrefix
	        });
	        this.filterFieldsContainer.appendChild(selector.createNode());
	      }
	    }
	  }, {
	    key: "renderEntityFields",
	    value: function renderEntityFields() {
	      var _this2 = this;

	      Object.keys(this.currentValues.get(this.currentEntityTypeId)).forEach(function (fieldId) {
	        return _this2.addCondition(fieldId);
	      });
	    }
	  }, {
	    key: "onFieldsListSelectClick",
	    value: function onFieldsListSelectClick(event) {
	      var fields = this.fieldsMap.get(this.currentEntityTypeId);

	      if (main_core.Type.isNil(fields)) {
	        return event.preventDefault();
	      }

	      var activity = this;
	      var menuItems = Object.entries(fields).map(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            fieldId = _ref2[0],
	            field = _ref2[1];

	        return {
	          fieldId: fieldId,
	          text: field.Name,
	          onclick: function onclick(_, item) {
	            this.popupWindow.close();
	            activity.addCondition(item.fieldId);
	          }
	        };
	      });
	      var menuManagerOptions = {
	        id: Math.random().toString(),
	        bindElement: this.fieldsListSelect,
	        items: Array.from(menuItems),
	        autoHide: true,
	        offsetLeft: main_core.Dom.getPosition(this.fieldsListSelect).width / 2,
	        angle: {
	          position: 'top',
	          offset: 0
	        },
	        zIndex: 200,
	        className: 'bizproc-automation-inline-selector-menu',
	        events: {
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          }
	        }
	      };
	      main_popup.MenuManager.show(menuManagerOptions);
	      return event.preventDefault();
	    }
	  }, {
	    key: "onAddConditionButtonClick",
	    value: function onAddConditionButtonClick(event) {
	      var defaultFieldId = Object.keys(this.fieldsMap.get(this.currentEntityTypeId))[0];
	      this.addCondition(defaultFieldId);
	      return event.preventDefault();
	    }
	  }, {
	    key: "addCondition",
	    value: function addCondition(fieldId) {
	      if (this.isRobot) {
	        this.addRobotCondition(fieldId);
	      } else {
	        this.addBizprocCondition(fieldId);
	      }
	    }
	  }, {
	    key: "addRobotCondition",
	    value: function addRobotCondition(fieldId) {
	      var conditionId = this.conditinIdPrefix + fieldId;
	      var titleNode = main_core.Dom.create('span', {
	        attrs: {
	          className: 'bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete'
	        },
	        text: this.fieldsMap.get(this.currentEntityTypeId)[fieldId].Name
	      });
	      var deleteButton = main_core.Dom.create('a', {
	        attrs: {
	          className: 'bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light'
	        },
	        props: {
	          href: '#'
	        },
	        text: main_core.Loc.getMessage('CRM_UDA_DELETE_CONDITION'),
	        events: {
	          // eslint-disable-next-line func-names
	          click: function (event) {
	            this.deleteCondition(fieldId);
	            return event.preventDefault();
	          }.bind(this)
	        }
	      });
	      var fieldNode = main_core.Dom.create('div', {
	        attrs: {
	          className: 'bizproc-automation-popup-settings'
	        },
	        props: {
	          id: conditionId
	        },
	        children: [titleNode, this.renderField(fieldId), deleteButton]
	      });
	      this.entitiesFieldsContainers.appendChild(fieldNode);
	    }
	  }, {
	    key: "deleteCondition",
	    value: function deleteCondition(fieldId) {
	      var conditionId = this.conditinIdPrefix + fieldId;
	      main_core.Dom.remove(document.getElementById(conditionId));
	    }
	  }, {
	    key: "addBizprocCondition",
	    value: function addBizprocCondition(fieldId) {
	      var newConditionRow = this.entitiesFieldsContainers.insertRow(-1);
	      newConditionRow.id = this.conditinIdPrefix + Math.random().toString().substr(1, 5);
	      var activity = this;
	      var entityFieldSelect = main_core.Dom.create('select', {
	        children: this.getCurrentFieldsOptions(fieldId),
	        events: {
	          change: function change(event) {
	            var fieldValueNode = newConditionRow.children[2];
	            var newFieldId = event.srcElement.value;
	            newConditionRow.replaceChild(activity.renderField(newFieldId), fieldValueNode);
	          }
	        }
	      });
	      var equalSignNode = main_core.Dom.create('span', {
	        text: '='
	      });
	      var entityFieldValueNode = this.renderField(fieldId);
	      var deleteConditionButton = main_core.Dom.create('a', {
	        props: {
	          href: '#'
	        },
	        text: main_core.Loc.getMessage('CRM_UDA_DELETE_CONDITION'),
	        events: {
	          click: function click(event) {
	            main_core.Dom.remove(document.getElementById(newConditionRow.id));
	            event.preventDefault();
	          }
	        }
	      });
	      [entityFieldSelect, equalSignNode, entityFieldValueNode, deleteConditionButton].forEach(function (node) {
	        newConditionRow.insertCell(-1).appendChild(node);
	      });
	    }
	  }, {
	    key: "getCurrentFieldsOptions",
	    value: function getCurrentFieldsOptions(selectedFieldId) {
	      return Object.entries(this.fieldsMap.get(this.currentEntityTypeId)).map(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	            fieldId = _ref4[0],
	            field = _ref4[1];

	        return main_core.Dom.create('option', {
	          props: {
	            value: field.FieldName
	          },
	          attrs: selectedFieldId === fieldId ? {
	            selected: 'selected'
	          } : undefined,
	          text: field.Name
	        });
	      });
	    }
	  }, {
	    key: "renderField",
	    value: function renderField(fieldId) {
	      var value = this.currentValues.get(this.currentEntityTypeId)[fieldId];

	      if (main_core.Type.isNil(value)) {
	        value = '';
	      }

	      return BX.Bizproc.FieldType.renderControl(this.documentType, this.fieldsMap.get(this.currentEntityTypeId)[fieldId], fieldId, value, this.isRobot ? 'public' : 'designer');
	    }
	  }]);
	  return CrmUpdateDynamicActivity;
	}();

	namespace.CrmUpdateDynamicActivity = CrmUpdateDynamicActivity;

}((this.window = this.window || {}),BX,BX.Main));
//# sourceMappingURL=script.js.map
