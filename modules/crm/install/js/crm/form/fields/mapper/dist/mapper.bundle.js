this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Form = this.BX.Crm.Form || {};
(function (exports,ui_sidepanelContent,main_core,main_core_events,landing_ui_collection_buttoncollection,landing_ui_collection_formcollection,landing_ui_panel_fieldspanel) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _fields = /*#__PURE__*/new WeakMap();
	var _map = /*#__PURE__*/new WeakMap();
	var _from = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var _getEntityNameByField = /*#__PURE__*/new WeakSet();
	var _getFieldByName = /*#__PURE__*/new WeakSet();
	var _onClickChange = /*#__PURE__*/new WeakSet();
	var _appendOutputData = /*#__PURE__*/new WeakSet();
	var Mapper = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Mapper, _EventEmitter);
	  function Mapper(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Mapper);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Mapper).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _appendOutputData);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onClickChange);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getFieldByName);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getEntityNameByField);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _fields, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _map, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _from, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _fields, options.fields);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _from, options.from);
	    _this.setMap(options.map);
	    return _this;
	  }
	  babelHelpers.createClass(Mapper, [{
	    key: "setMap",
	    value: function setMap(map) {
	      var _this2 = this;
	      babelHelpers.classPrivateFieldSet(this, _map, map);
	      babelHelpers.classPrivateFieldGet(this, _map).forEach(function (item) {
	        return _classPrivateMethodGet(_this2, _appendOutputData, _appendOutputData2).call(_this2, item, item.outputCode);
	      });
	      this.render();
	      return this;
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return babelHelpers.classPrivateFieldGet(this, _map);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this3 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      }
	      babelHelpers.classPrivateFieldGet(this, _container).innerHTML = '';
	      this.getMap().forEach(function (field) {
	        var changeHandler = function changeHandler() {
	          return _classPrivateMethodGet(_this3, _onClickChange, _onClickChange2).call(_this3, field);
	        };
	        var element = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-form-row\" style=\"background: #F5F7F8; border-radius: 12px;\">\n\t\t\t\t\t\t<div class=\"ui-form\" style=\"width: 100%; padding: 20px;\">\n\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", " - ", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t<div class=\"crm-form-fields-mapper-row\">\n\t\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\t\tclass=\"crm-form-fields-mapper-row-label ", "\"\n\t\t\t\t\t\t\t\t\t\tdata-role=\"caption\"\n\t\t\t\t\t\t\t\t\t>", "</div>\n\t\t\t\t\t\t\t\t\t<div>\n\t\t\t\t\t\t\t\t\t\t<a class=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round\"\n\t\t\t\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t\t\t>", "</a>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Tag.safe(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["", ""])), field.inputName), main_core.Tag.safe(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["", ""])), babelHelpers.classPrivateFieldGet(_this3, _from).caption), field.outputName ? '' : 'crm-form-fields-mapper-row-label-error', main_core.Tag.safe(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["", ""])), field.outputName) || main_core.Loc.getMessage('CRM_FORM_FIELDS_MAPPER_NOT_SELECTED'), changeHandler, main_core.Loc.getMessage('CRM_FORM_FIELDS_MAPPER_CHOOSE_FIELD'));
	        field.element = element;
	        babelHelpers.classPrivateFieldGet(_this3, _container).appendChild(element);
	      });
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }]);
	  return Mapper;
	}(main_core_events.EventEmitter);
	function _getEntityNameByField2(fieldName) {
	  var entityNameParts = fieldName.split('_');
	  var entityName = entityNameParts[0];
	  if (entityName === 'DYNAMIC') {
	    entityName = entityNameParts[0] + '_' + entityNameParts[1];
	  }
	  return entityName;
	}
	function _getFieldByName2(name) {
	  var entityName = _classPrivateMethodGet(this, _getEntityNameByField, _getEntityNameByField2).call(this, name);
	  var entity = babelHelpers.classPrivateFieldGet(this, _fields)[entityName];
	  return entity.FIELDS.filter(function (field) {
	    return field.name === name;
	  })[0] || null;
	}
	function _onClickChange2(item) {
	  var _this4 = this;
	  var selectorOptions = {
	    multiple: false,
	    allowedTypes: [],
	    allowedCategories: []
	  };
	  if (['email', 'phone'].includes(item.inputType)) {
	    selectorOptions.allowedTypes = [{
	      type: 'typed_string',
	      entityFieldName: 'PHONE'
	    }, {
	      type: 'typed_string',
	      entityFieldName: 'EMAIL'
	    }];
	    selectorOptions.allowedCategories = ['LEAD', 'CONTACT', 'COMPANY'];
	  } else {
	    selectorOptions.allowedTypes = ['string', 'text'];
	  }
	  selectorOptions.disabledFields = this.getMap().map(function (item) {
	    return item.outputCode;
	  });
	  landing_ui_panel_fieldspanel.FieldsPanel.getInstance().show(selectorOptions).then(function (selectedNames) {
	    _classPrivateMethodGet(_this4, _appendOutputData, _appendOutputData2).call(_this4, item, selectedNames[0]);
	    _this4.render();
	    _this4.emit('change');
	  });
	}
	function _appendOutputData2(item, name) {
	  if (!name) {
	    return;
	  }
	  var entityName = _classPrivateMethodGet(this, _getEntityNameByField, _getEntityNameByField2).call(this, name);
	  var entity = babelHelpers.classPrivateFieldGet(this, _fields)[entityName];
	  var field = _classPrivateMethodGet(this, _getFieldByName, _getFieldByName2).call(this, name);
	  if (!field) {
	    return;
	  }
	  item.outputCode = name;
	  item.outputName = "".concat(field.caption, " - ").concat(entity.CAPTION);
	}

	exports.Mapper = Mapper;

}((this.BX.Crm.Form.Fields = this.BX.Crm.Form.Fields || {}),BX,BX,BX.Event,BX.Landing.UI.Collection,BX.Landing.UI.Collection,BX.Landing.UI.Panel));
//# sourceMappingURL=mapper.bundle.js.map
