/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_entitySelector,main_core_events,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var DEFAULT_COUNTRY_CODE = 'XX';

	/**
	 * @memberOf BX.Crm
	 */
	var _countrySelector = /*#__PURE__*/new WeakMap();
	var _getSelectContainer = /*#__PURE__*/new WeakSet();
	var _getCountrySelector = /*#__PURE__*/new WeakSet();
	var _getDefaultCountry = /*#__PURE__*/new WeakSet();
	var _getSchemeElementOptions = /*#__PURE__*/new WeakSet();
	var PhoneNumberInputFieldConfigurator = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(PhoneNumberInputFieldConfigurator, _BX$UI$EntityEditorFi);
	  function PhoneNumberInputFieldConfigurator() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, PhoneNumberInputFieldConfigurator);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(PhoneNumberInputFieldConfigurator)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getSchemeElementOptions);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getDefaultCountry);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getCountrySelector);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getSelectContainer);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _countrySelector, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(PhoneNumberInputFieldConfigurator, [{
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _countrySelector)) {
	        babelHelpers.classPrivateFieldGet(this, _countrySelector).destroy();
	      }
	    } // region overridden methods from BX.UI.EntityEditorFieldConfigurator ----------------------------------------------
	    /**
	     * @override
	     */
	  }, {
	    key: "layoutInternal",
	    value: function layoutInternal() {
	      main_core.Dom.append(this.getInputContainer(), this._wrapper);
	      if (this._typeId === 'list') {
	        this.layoutInnerConfigurator(this._field.getInnerConfig(), this._field.getItems());
	      }
	      main_core.Dom.append(this.getOptionContainer(), this._wrapper);
	      if (this._typeId === 'multifield' || this._typeId === 'client_light') {
	        main_core.Dom.append(this.getCountrySelectContent(), this._wrapper); // NEW: country selector added
	      }

	      main_core.Dom.append(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<hr class=\"ui-entity-editor-line\">"]))), this._wrapper);
	      main_core.Dom.append(this.getButtonContainer(), this._wrapper);
	    }
	  }, {
	    key: "prepareSaveParams",
	    value: function prepareSaveParams() {
	      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
	        args[_key2] = arguments[_key2];
	      }
	      var params = babelHelpers.get(babelHelpers.getPrototypeOf(PhoneNumberInputFieldConfigurator.prototype), "prepareSaveParams", this).call(this, this, args);

	      // add selected value
	      if (babelHelpers.classPrivateFieldGet(this, _countrySelector)) {
	        var items = babelHelpers.classPrivateFieldGet(this, _countrySelector).getDialog().getSelectedItems();
	        if (items.length <= 1) {
	          params.defaultCountry = main_core.Type.isArrayFilled(items) ? items[0].id : DEFAULT_COUNTRY_CODE;
	          _classPrivateMethodGet(this, _getSchemeElementOptions, _getSchemeElementOptions2).call(this).defaultCountry = params.defaultCountry;
	        }
	      }
	      return params;
	    } // endregion -------------------------------------------------------------------------------------------------------
	  }, {
	    key: "getCountrySelectContent",
	    value: function getCountrySelectContent() {
	      var wrapper = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t<div class=\"ui-entity-editor-block-title\">\n\t\t\t\t\t<span class=\"ui-entity-editor-block-title-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_PHONE_NUMBER_INPUT_FIELD_CONFIGURATOR_TITLE'));
	      main_core.Dom.append(_classPrivateMethodGet(this, _getSelectContainer, _getSelectContainer2).call(this), wrapper);
	      return wrapper;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return PhoneNumberInputFieldConfigurator;
	}(BX.UI.EntityEditorFieldConfigurator);
	function _getSelectContainer2() {
	  var selectContainer = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block crm-entity-country-tag-selector\"></div>\n\t\t"])));
	  _classPrivateMethodGet(this, _getCountrySelector, _getCountrySelector2).call(this).renderTo(selectContainer);
	  return selectContainer;
	}
	function _getCountrySelector2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _countrySelector)) {
	    babelHelpers.classPrivateFieldSet(this, _countrySelector, new crm_entitySelector.TagSelector({
	      textBoxWidth: '100%',
	      tagMaxWidth: 270,
	      placeholder: main_core.Loc.getMessage('CRM_PHONE_NUMBER_INPUT_FIELD_CONFIGURATOR_PLACEHOLDER'),
	      multiple: false,
	      dialogOptions: {
	        width: 425,
	        multiple: false,
	        showAvatars: true,
	        dropdownMode: true,
	        preselectedItems: [['country', _classPrivateMethodGet(this, _getDefaultCountry, _getDefaultCountry2).call(this)]],
	        entities: [{
	          id: 'country'
	        }],
	        events: {
	          onFirstShow: function onFirstShow(event) {
	            var popupContainer = event.getTarget().getPopup().getContentContainer();
	            if (main_core.Type.isDomNode(popupContainer)) {
	              main_core.Dom.addClass(popupContainer, 'crm-entity-country-tag-selector-popup');
	            }
	          }
	        }
	      }
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _countrySelector);
	}
	function _getDefaultCountry2() {
	  var _classPrivateMethodGe = _classPrivateMethodGet(this, _getSchemeElementOptions, _getSchemeElementOptions2).call(this),
	    defaultCountry = _classPrivateMethodGe.defaultCountry;
	  if (main_core.Type.isStringFilled(defaultCountry)) {
	    return defaultCountry;
	  }
	  return DEFAULT_COUNTRY_CODE;
	}
	function _getSchemeElementOptions2() {
	  var _this$_field, _this$_field$getSchem;
	  return this === null || this === void 0 ? void 0 : (_this$_field = this._field) === null || _this$_field === void 0 ? void 0 : (_this$_field$getSchem = _this$_field.getSchemeElement()) === null || _this$_field$getSchem === void 0 ? void 0 : _this$_field$getSchem._options;
	}

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var DEFAULT_ADDRESS_TYPE_ID = '11'; // \Bitrix\Crm\EntityAddressType::Delivery
	var _addressTypeSelect = /*#__PURE__*/new WeakMap();
	var _allAddressTypes = /*#__PURE__*/new WeakMap();
	var _suitableAddressTypes = /*#__PURE__*/new WeakMap();
	var _setDefaultAddressTypeToSchemeOptions = /*#__PURE__*/new WeakSet();
	var _getDefaultAddressTypeSetterContainer = /*#__PURE__*/new WeakSet();
	var _getAddressTypeSelect = /*#__PURE__*/new WeakSet();
	var _getDefaultAddressType = /*#__PURE__*/new WeakSet();
	var _getPreparedAddressTypesForOptions = /*#__PURE__*/new WeakSet();
	var _getAddressTypeSelectValue = /*#__PURE__*/new WeakSet();
	var _isValidAddressType = /*#__PURE__*/new WeakSet();
	var _getAllAddressTypes = /*#__PURE__*/new WeakSet();
	var _getSuitableAddressTypes = /*#__PURE__*/new WeakSet();
	var _getSchemeData = /*#__PURE__*/new WeakSet();
	var _getSchemeOptions = /*#__PURE__*/new WeakSet();
	var _getSchemeElement = /*#__PURE__*/new WeakSet();
	var _getAddressZoneConfig = /*#__PURE__*/new WeakSet();
	var RequisiteAddressFieldConfigurator = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(RequisiteAddressFieldConfigurator, _BX$UI$EntityEditorFi);
	  function RequisiteAddressFieldConfigurator() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, RequisiteAddressFieldConfigurator);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(RequisiteAddressFieldConfigurator)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getAddressZoneConfig);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSchemeElement);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSchemeOptions);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSchemeData);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSuitableAddressTypes);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getAllAddressTypes);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _isValidAddressType);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getAddressTypeSelectValue);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getPreparedAddressTypesForOptions);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getDefaultAddressType);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getAddressTypeSelect);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getDefaultAddressTypeSetterContainer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _setDefaultAddressTypeToSchemeOptions);
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _addressTypeSelect, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _allAddressTypes, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _suitableAddressTypes, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(RequisiteAddressFieldConfigurator, [{
	    key: "layoutInternal",
	    value: function layoutInternal() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(RequisiteAddressFieldConfigurator.prototype), "layoutInternal", this).call(this);

	      // eslint-disable-next-line no-underscore-dangle
	      var wrapper = this._wrapper;
	      var hr = wrapper.querySelector('hr');
	      main_core.Dom.insertBefore(_classPrivateMethodGet$1(this, _getDefaultAddressTypeSetterContainer, _getDefaultAddressTypeSetterContainer2).call(this), hr);
	    }
	  }, {
	    key: "prepareSaveParams",
	    value: function prepareSaveParams() {
	      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
	        args[_key2] = arguments[_key2];
	      }
	      var params = babelHelpers.get(babelHelpers.getPrototypeOf(RequisiteAddressFieldConfigurator.prototype), "prepareSaveParams", this).call(this, this, args);
	      var newDefaultAddressTypeId = _classPrivateMethodGet$1(this, _getAddressTypeSelectValue, _getAddressTypeSelectValue2).call(this);
	      if (!_classPrivateMethodGet$1(this, _isValidAddressType, _isValidAddressType2).call(this, newDefaultAddressTypeId)) {
	        return params;
	      }
	      _classPrivateMethodGet$1(this, _setDefaultAddressTypeToSchemeOptions, _setDefaultAddressTypeToSchemeOptions2).call(this, newDefaultAddressTypeId);
	      params.defaultAddressType = newDefaultAddressTypeId;
	      return params;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return RequisiteAddressFieldConfigurator;
	}(BX.UI.EntityEditorFieldConfigurator);
	function _setDefaultAddressTypeToSchemeOptions2(defaultAddressTypeId) {
	  var schemeOptions = _classPrivateMethodGet$1(this, _getSchemeOptions, _getSchemeOptions2).call(this);
	  if (schemeOptions) {
	    schemeOptions.defaultAddressType = defaultAddressTypeId;
	  }
	}
	function _getDefaultAddressTypeSetterContainer2() {
	  var title = main_core.Loc.getMessage('CRM_REQUISITE_DEFAULT_ADDRESS_TYPE_TITLE');
	  var wrapper = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t<div class=\"ui-entity-editor-block-title\">\n\t\t\t\t\t<span class=\"ui-entity-editor-block-title-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(title));
	  var selectContainer = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-entity-editor-content-block crm-default-requisite-address-type\"></div>"])));
	  main_core.Dom.append(_classPrivateMethodGet$1(this, _getAddressTypeSelect, _getAddressTypeSelect2).call(this), selectContainer);
	  main_core.Dom.append(selectContainer, wrapper);
	  return wrapper;
	}
	function _getAddressTypeSelect2() {
	  var _this2 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _addressTypeSelect)) {
	    babelHelpers.classPrivateFieldSet(this, _addressTypeSelect, main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<select class=\"main-ui-control main-enum-dialog-input\" name=\"display\"></select>"]))));
	    _classPrivateMethodGet$1(this, _getPreparedAddressTypesForOptions, _getPreparedAddressTypesForOptions2).call(this).forEach(function (addressType) {
	      var option = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<option value=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</option>\n\t\t\t\t"])), main_core.Text.encode(addressType.value), main_core.Text.encode(addressType.label));
	      main_core.Dom.append(option, babelHelpers.classPrivateFieldGet(_this2, _addressTypeSelect));
	    });
	    babelHelpers.classPrivateFieldGet(this, _addressTypeSelect).value = _classPrivateMethodGet$1(this, _getDefaultAddressType, _getDefaultAddressType2).call(this);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _addressTypeSelect);
	}
	function _getDefaultAddressType2() {
	  var _classPrivateMethodGe, _classPrivateMethodGe2;
	  var _ref = (_classPrivateMethodGe = _classPrivateMethodGet$1(this, _getSchemeOptions, _getSchemeOptions2).call(this)) !== null && _classPrivateMethodGe !== void 0 ? _classPrivateMethodGe : {},
	    optionAddressTypeId = _ref.defaultAddressType;
	  if (_classPrivateMethodGet$1(this, _isValidAddressType, _isValidAddressType2).call(this, optionAddressTypeId)) {
	    return optionAddressTypeId;
	  }
	  var _ref2 = (_classPrivateMethodGe2 = _classPrivateMethodGet$1(this, _getAddressZoneConfig, _getAddressZoneConfig2).call(this)) !== null && _classPrivateMethodGe2 !== void 0 ? _classPrivateMethodGe2 : {},
	    schemeDefaultAddressTypeId = _ref2.defaultAddressType;
	  if (_classPrivateMethodGet$1(this, _isValidAddressType, _isValidAddressType2).call(this, schemeDefaultAddressTypeId)) {
	    return schemeDefaultAddressTypeId;
	  }
	  return DEFAULT_ADDRESS_TYPE_ID;
	}
	function _getPreparedAddressTypesForOptions2() {
	  var options = [];
	  var suitableAddressTypes = _classPrivateMethodGet$1(this, _getSuitableAddressTypes, _getSuitableAddressTypes2).call(this);
	  suitableAddressTypes.forEach(function (addressType) {
	    options.push({
	      value: addressType.ID,
	      label: addressType.DESCRIPTION
	    });
	  });
	  return options;
	}
	function _getAddressTypeSelectValue2() {
	  return _classPrivateMethodGet$1(this, _getAddressTypeSelect, _getAddressTypeSelect2).call(this).value;
	}
	function _isValidAddressType2(addressTypeId) {
	  return main_core.Type.isStringFilled(addressTypeId) && _classPrivateMethodGet$1(this, _getSuitableAddressTypes, _getSuitableAddressTypes2).call(this).has(addressTypeId);
	}
	function _getAllAddressTypes2() {
	  var _this3 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _allAddressTypes)) {
	    var _classPrivateMethodGe3;
	    babelHelpers.classPrivateFieldSet(this, _allAddressTypes, new Map());
	    var _ref3 = (_classPrivateMethodGe3 = _classPrivateMethodGet$1(this, _getSchemeData, _getSchemeData2).call(this)) !== null && _classPrivateMethodGe3 !== void 0 ? _classPrivateMethodGe3 : {},
	      allAddressTypes = _ref3.types;
	    if (allAddressTypes) {
	      Object.values(allAddressTypes).forEach(function (addressType) {
	        babelHelpers.classPrivateFieldGet(_this3, _allAddressTypes).set(addressType.ID, addressType);
	      });
	    }
	  }
	  return babelHelpers.classPrivateFieldGet(this, _allAddressTypes);
	}
	function _getSuitableAddressTypes2() {
	  var _this4 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _suitableAddressTypes)) {
	    var _classPrivateMethodGe4;
	    babelHelpers.classPrivateFieldSet(this, _suitableAddressTypes, new Map());
	    var _ref4 = (_classPrivateMethodGe4 = _classPrivateMethodGet$1(this, _getAddressZoneConfig, _getAddressZoneConfig2).call(this)) !== null && _classPrivateMethodGe4 !== void 0 ? _classPrivateMethodGe4 : {},
	      currentZoneAddressTypes = _ref4.currentZoneAddressTypes;
	    if (currentZoneAddressTypes) {
	      currentZoneAddressTypes.forEach(function (addressTypeId) {
	        var addressType = _classPrivateMethodGet$1(_this4, _getAllAddressTypes, _getAllAddressTypes2).call(_this4).get(addressTypeId);
	        if (addressType) {
	          babelHelpers.classPrivateFieldGet(_this4, _suitableAddressTypes).set(addressType.ID, addressType);
	        }
	      });
	    }
	  }
	  return babelHelpers.classPrivateFieldGet(this, _suitableAddressTypes);
	}
	function _getSchemeData2() {
	  var _classPrivateMethodGe5;
	  return (_classPrivateMethodGe5 = _classPrivateMethodGet$1(this, _getSchemeElement, _getSchemeElement2).call(this)) === null || _classPrivateMethodGe5 === void 0 ? void 0 : _classPrivateMethodGe5.getData();
	}
	function _getSchemeOptions2() {
	  var _classPrivateMethodGe6;
	  return (_classPrivateMethodGe6 = _classPrivateMethodGet$1(this, _getSchemeElement, _getSchemeElement2).call(this)) === null || _classPrivateMethodGe6 === void 0 ? void 0 : _classPrivateMethodGe6.getOptions();
	}
	function _getSchemeElement2() {
	  var _this$getField;
	  return (_this$getField = this.getField()) === null || _this$getField === void 0 ? void 0 : _this$getField.getSchemeElement();
	}
	function _getAddressZoneConfig2() {
	  var _classPrivateMethodGe7;
	  return (_classPrivateMethodGe7 = _classPrivateMethodGet$1(this, _getSchemeData, _getSchemeData2).call(this)) === null || _classPrivateMethodGe7 === void 0 ? void 0 : _classPrivateMethodGe7.addressZoneConfig;
	}

	/* eslint-disable no-underscore-dangle, @bitrix24/bitrix24-rules/no-pseudo-private */

	/**
	 * @memberOf BX.Crm
	 */
	var EntityConfigurationManager = /*#__PURE__*/function (_BX$UI$EntityConfigur) {
	  babelHelpers.inherits(EntityConfigurationManager, _BX$UI$EntityConfigur);
	  function EntityConfigurationManager() {
	    babelHelpers.classCallCheck(this, EntityConfigurationManager);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityConfigurationManager).apply(this, arguments));
	  }
	  babelHelpers.createClass(EntityConfigurationManager, [{
	    key: "getSimpleFieldConfigurator",
	    /**
	     * @param {Object} params
	     * @param {Object} parent
	     *
	     * @returns {BX.UI.EntityEditorFieldConfigurator}
	     *
	     * @override
	     */
	    value: function getSimpleFieldConfigurator(params, parent) {
	      if (!main_core.Type.isPlainObject(params)) {
	        throw new TypeError('EntityConfigurationManager: The "params" argument must be object.');
	      }
	      var typeId = '';
	      var child = params.field,
	        mandatoryConfigurator = params.mandatoryConfigurator;
	      if (child) {
	        typeId = child.getType();
	        child.setVisible(false);
	      } else {
	        typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
	      }
	      var fieldConfiguratorOptions = {
	        editor: this._editor,
	        schemeElement: null,
	        model: parent._model,
	        mode: BX.UI.EntityEditorMode.edit,
	        parent: parent,
	        typeId: typeId,
	        field: child,
	        mandatoryConfigurator: mandatoryConfigurator
	      };

	      // override for 'PHONE', 'CLIENT', 'COMPANY', 'CONTACT' fields:
	      // add additional option to set up default country phone code
	      if (EntityConfigurationManager.PHONE_NUMBER_FIELDS.includes(child.getId())) {
	        this._fieldConfigurator = PhoneNumberInputFieldConfigurator.create('', fieldConfiguratorOptions);
	      } else if (EntityConfigurationManager.REQUISITE_ADDRESS_FIELDS.includes(child.getId()) && typeId === 'requisite_address') {
	        this._fieldConfigurator = RequisiteAddressFieldConfigurator.create('', fieldConfiguratorOptions);
	      } else {
	        this._fieldConfigurator = BX.UI.EntityEditorFieldConfigurator.create('', fieldConfiguratorOptions);
	      }
	      return this._fieldConfigurator;
	    }
	    /**
	     * @param {Object} params
	     * @param {Object} parent
	     *
	     * @returns { BX.UI.EntityEditorUserFieldConfigurator}
	     *
	     * @override
	     */
	  }, {
	    key: "getUserFieldConfigurator",
	    value: function getUserFieldConfigurator(params, parent) {
	      if (!main_core.Type.isPlainObject(params)) {
	        throw 'EntityConfigurationManager: The "params" argument must be object.';
	      }
	      var typeId = '';
	      var field = BX.prop.get(params, 'field', null);
	      if (field) {
	        if (!(field instanceof BX.UI.EntityEditorUserField)) {
	          throw 'EntityConfigurationManager: The "field" param must be EntityEditorUserField.';
	        }
	        typeId = field.getFieldType();
	        field.setVisible(false);
	      } else {
	        typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
	      }
	      if (typeId === 'resourcebooking') {
	        var options = {
	          editor: this._editor,
	          schemeElement: null,
	          model: parent.getModel(),
	          mode: BX.UI.EntityEditorMode.edit,
	          parent: parent,
	          typeId: typeId,
	          field: field,
	          showAlways: true,
	          enableMandatoryControl: BX.prop.getBoolean(params, 'enableMandatoryControl', true),
	          mandatoryConfigurator: params.mandatoryConfigurator
	        };
	        if (BX.Calendar && BX.type.isFunction(BX.Calendar.ResourcebookingUserfield)) {
	          return BX.Calendar.ResourcebookingUserfield.getCrmFieldConfigurator('', options);
	        } else if (BX.Calendar && BX.Calendar.UserField && BX.Calendar.UserField.EntityEditorUserFieldConfigurator) {
	          return BX.Calendar.UserField.EntityEditorUserFieldConfigurator.create('', options);
	        }
	      } else {
	        return BX.Crm.EntityEditorUserFieldConfigurator.create('', {
	          editor: this._editor,
	          schemeElement: null,
	          model: parent.getModel(),
	          mode: BX.UI.EntityEditorMode.edit,
	          parent: parent,
	          typeId: typeId,
	          field: field,
	          mandatoryConfigurator: params.mandatoryConfigurator,
	          visibilityConfigurator: params.visibilityConfigurator,
	          showAlways: true
	        });
	      }
	    }
	  }, {
	    key: "getTypeInfos",
	    value: function getTypeInfos() {
	      var typeInfos = babelHelpers.get(babelHelpers.getPrototypeOf(EntityConfigurationManager.prototype), "getTypeInfos", this).call(this);
	      var ufAddRestriction = this._editor.getRestriction('userFieldAdd');
	      var ufResourceBookingRestriction = this._editor.getRestriction('userFieldResourceBooking');
	      if (ufAddRestriction && !ufAddRestriction['isPermitted'] && ufAddRestriction['restrictionCallback']) {
	        for (var i = 0, length = typeInfos.length; i < length; i++) {
	          typeInfos[i].callback = function () {
	            eval(ufAddRestriction['restrictionCallback']);
	          };
	        }
	      } else if (ufResourceBookingRestriction && !ufResourceBookingRestriction['isPermitted'] && ufResourceBookingRestriction['restrictionCallback']) {
	        for (var j = 0; j < typeInfos.length; j++) {
	          if (typeInfos[j].name === 'resourcebooking') {
	            typeInfos[j].callback = function () {
	              eval(ufResourceBookingRestriction['restrictionCallback']);
	            };
	          }
	        }
	      }
	      return typeInfos;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityConfigurationManager;
	}(BX.UI.EntityConfigurationManager);
	babelHelpers.defineProperty(EntityConfigurationManager, "PHONE_NUMBER_FIELDS", ['PHONE', 'CLIENT', 'COMPANY', 'CONTACT', 'MYCOMPANY_ID']);
	babelHelpers.defineProperty(EntityConfigurationManager, "REQUISITE_ADDRESS_FIELDS", ['ADDRESS']);

	exports.EntityConfigurationManager = EntityConfigurationManager;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm.EntitySelectorEx,BX.Event,BX));
//# sourceMappingURL=field-configurator.bundle.js.map
