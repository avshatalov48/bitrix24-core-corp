/* eslint-disable */
(function (exports,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');
	var CrmCreateDynamicActivity = /*#__PURE__*/function () {
	  function CrmCreateDynamicActivity(options) {
	    babelHelpers.classCallCheck(this, CrmCreateDynamicActivity);
	    babelHelpers.defineProperty(this, "fieldsMapContainer", undefined);
	    babelHelpers.defineProperty(this, "entityTypeIdSelect", undefined);
	    babelHelpers.defineProperty(this, "currentValues", {});
	    babelHelpers.defineProperty(this, "renderedProperties", {});
	    babelHelpers.defineProperty(this, "entitiesFieldsContainers", new Map());
	    this.fieldsMapContainer = document.getElementById('fields-map-container');
	    if (main_core.Type.isPlainObject(options)) {
	      this.isRobot = options.isRobot;
	      var form = document.forms[options.formName];
	      if (!main_core.Type.isNil(form)) {
	        this.entityTypeIdSelect = form['dynamic_type_id'];
	      }
	      this.entitiesFieldsMap = options.entitiesFieldsMap;
	      if (main_core.Type.isPlainObject(options.currentValues)) {
	        this.currentValues = options.currentValues;
	      }
	    }
	  }
	  babelHelpers.createClass(CrmCreateDynamicActivity, [{
	    key: "getBindFieldId",
	    value: function getBindFieldId() {
	      return "".concat(this.currentEntityTypeId, "_BindToCurrentElement");
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      if (this.entityTypeIdSelect) {
	        this.render();
	        main_core.Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
	      }
	    }
	  }, {
	    key: "onEntityTypeIdChange",
	    value: function onEntityTypeIdChange() {
	      main_core.Dom.clean(this.fieldsMapContainer);
	      this.currentValues = {};
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (Object.hasOwn(this.entitiesFieldsMap, this.currentEntityTypeId)) {
	        var fieldsMap = this.entitiesFieldsMap[this.currentEntityTypeId].fieldsMap;
	        this.loadRenderedFields();
	        for (var _i = 0, _Object$keys = Object.keys(fieldsMap); _i < _Object$keys.length; _i++) {
	          var fieldId = _Object$keys[_i];
	          main_core.Dom.append(this.renderProperty(fieldId), this.fieldsMapContainer);
	        }
	      }
	    }
	  }, {
	    key: "loadRenderedFields",
	    value: function loadRenderedFields() {
	      var _this = this;
	      var _this$entitiesFieldsM = this.entitiesFieldsMap[this.currentEntityTypeId],
	        documentType = _this$entitiesFieldsM.documentType,
	        fieldsMap = _this$entitiesFieldsM.fieldsMap;
	      if (main_core.Type.isFunction(BX.Bizproc.FieldType.renderControlCollection)) {
	        this.renderedProperties = BX.Bizproc.FieldType.renderControlCollection(documentType, Object.entries(fieldsMap).map(function (_ref) {
	          var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            fieldId = _ref2[0],
	            field = _ref2[1];
	          return {
	            property: field,
	            fieldName: field.FieldName,
	            value: _this.currentValues[fieldId],
	            controlId: fieldId
	          };
	        }), this.isRobot ? 'public' : 'designer');
	      }
	    }
	  }, {
	    key: "renderProperty",
	    value: function renderProperty(fieldId) {
	      if (this.getBindFieldId() === fieldId) {
	        return this.isRobot ? this.renderRobotBindField() : '';
	      }
	      return this.isRobot ? this.renderRobotProperty(fieldId) : this.renderDesignerProperty(fieldId);
	    }
	  }, {
	    key: "renderRobotBindField",
	    value: function renderRobotBindField() {
	      var fieldsMap = this.entitiesFieldsMap[this.currentEntityTypeId].fieldsMap;
	      var bindField = fieldsMap[this.getBindFieldId()];
	      var bindFieldValue = Object.hasOwn(this.currentValues, this.getBindFieldId()) && (this.currentValues[this.getBindFieldId()] === 'Y' || this.currentValues[this.getBindFieldId()] === true);
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"bizproc-automation-popup-settings\">\n\t\t\t\t<div class=\"bizproc-automation-popup-checkbox-item\">\n\t\t\t\t\t<input type=\"hidden\" name=\"", "\" value=\"N\">\n\t\t\t\t\t<label class=\"bizproc-automation-popup-chk-label\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\ttype=\"checkbox\"\n\t\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\t\tvalue=\"Y\"\n\t\t\t\t\t\t\tclass=\"bizproc-automation-popup-chk\"\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(bindField.FieldName), main_core.Text.encode(bindField.FieldName), bindFieldValue ? 'checked' : '', main_core.Text.encode(bindField.Name));
	    }
	  }, {
	    key: "renderRobotProperty",
	    value: function renderRobotProperty(fieldId) {
	      var _this2 = this;
	      var _this$entitiesFieldsM2 = this.entitiesFieldsMap[this.currentEntityTypeId],
	        documentType = _this$entitiesFieldsM2.documentType,
	        fieldsMap = _this$entitiesFieldsM2.fieldsMap;
	      var property = fieldsMap[fieldId];
	      var fallback = function fallback() {
	        return BX.Bizproc.FieldType.renderControlPublic(documentType, property, property.FieldName, _this2.currentValues[fieldId]);
	      };
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"bizproc-automation-popup-settings\">\n\t\t\t\t<span class=\"bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete\">\n\t\t\t\t\t", ":\n\t\t\t\t</span>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(property.Name), main_core.Type.isDomNode(this.renderedProperties[fieldId]) ? this.renderedProperties[fieldId] : fallback());
	    }
	  }, {
	    key: "renderDesignerProperty",
	    value: function renderDesignerProperty(fieldId) {
	      var _this3 = this;
	      var _this$entitiesFieldsM3 = this.entitiesFieldsMap[this.currentEntityTypeId],
	        documentType = _this$entitiesFieldsM3.documentType,
	        fieldsMap = _this$entitiesFieldsM3.fieldsMap;
	      var property = fieldsMap[fieldId];
	      var fallback = function fallback() {
	        return BX.Bizproc.FieldType.renderControlDesigner(documentType, property, property.FieldName, _this3.currentValues[fieldId]);
	      };
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<tr>\n\t\t\t\t<td align=\"right\" width=\"40%\">", ":</td>\n\t\t\t\t<td width=\"60%\">\n\t\t\t\t\t", "\n\t\t\t\t</td>\n\t\t\t</tr>\n\t\t"])), main_core.Text.encode(property.Name), main_core.Type.isDomNode(this.renderedProperties[fieldId]) ? this.renderedProperties[fieldId] : fallback());
	    }
	  }, {
	    key: "currentEntityTypeId",
	    get: function get() {
	      if (!this.entityTypeIdSelect) {
	        return 0;
	      }
	      return parseInt(this.entityTypeIdSelect.value, 10);
	    }
	  }]);
	  return CrmCreateDynamicActivity;
	}();
	namespace.CrmCreateDynamicActivity = CrmCreateDynamicActivity;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
