this.BX = this.BX || {};
(function (exports,main_core,crm_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var DEFAULT_COUNTRY_CODE = 'XX';

	/**
	 * @memberOf BX.Crm
	 */
	var _countrySelector = /*#__PURE__*/new WeakMap();
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
	      this._wrapper.appendChild(this.getInputContainer());
	      if (this._typeId === "list") {
	        this.layoutInnerConfigurator(this._field.getInnerConfig(), this._field.getItems());
	      }
	      this._wrapper.appendChild(this.getOptionContainer());
	      if (this._typeId === 'multifield' || this._typeId === 'client_light') {
	        this._wrapper.appendChild(this.getCountrySelectContainer()); // NEW: country selector added
	      }

	      main_core.Dom.append(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<hr class=\"ui-entity-editor-line\">"]))), this._wrapper);
	      this._wrapper.appendChild(this.getButtonContainer());
	    }
	  }, {
	    key: "prepareSaveParams",
	    /**
	     * @param event
	     *
	     * @returns {Object}
	     *
	     * @override
	     */
	    value: function prepareSaveParams(event) {
	      var params = babelHelpers.get(babelHelpers.getPrototypeOf(PhoneNumberInputFieldConfigurator.prototype), "prepareSaveParams", this).call(this, this, arguments);

	      // add selected value
	      if (babelHelpers.classPrivateFieldGet(this, _countrySelector)) {
	        var items = babelHelpers.classPrivateFieldGet(this, _countrySelector).getDialog().getSelectedItems();
	        if (items.length <= 1) {
	          params['defaultCountry'] = items.length === 0 ? DEFAULT_COUNTRY_CODE : items[0].id;
	          this._field.getSchemeElement()._options['defaultCountry'] = params['defaultCountry'];
	        }
	      }
	      return params;
	    } // endregion -------------------------------------------------------------------------------------------------------
	  }, {
	    key: "getCountrySelectContainer",
	    value: function getCountrySelectContainer() {
	      var wrapper = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t<div class=\"ui-entity-editor-block-title\">\n\t\t\t\t\t<span class=\"ui-entity-editor-block-title-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_PHONE_NUMBER_INPUT_FIELD_CONFIGURATOR_TITLE'));
	      var selectContainer = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block crm-entity-country-tag-selector\"></div>\n\t\t"])));
	      main_core.Dom.append(selectContainer, wrapper);
	      var defaultCountry = DEFAULT_COUNTRY_CODE;
	      if (this._field && this._field.getSchemeElement() && main_core.Type.isPlainObject(this._field.getSchemeElement()._options) && main_core.Type.isStringFilled(this._field.getSchemeElement()._options.defaultCountry)) {
	        defaultCountry = this._field.getSchemeElement()._options.defaultCountry;
	      }
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
	          preselectedItems: [['country', defaultCountry]],
	          entities: [{
	            id: 'country'
	          }],
	          events: {
	            'onFirstShow': function onFirstShow(event) {
	              var popupContainer = event.getTarget().getPopup().getContentContainer();
	              if (main_core.Type.isDomNode(popupContainer)) {
	                main_core.Dom.addClass(popupContainer, 'crm-entity-country-tag-selector-popup');
	              }
	            }
	          }
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _countrySelector).renderTo(selectContainer);
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
	        throw 'EntityConfigurationManager: The "params" argument must be object.';
	      }
	      var typeId = '';
	      var child = BX.prop.get(params, 'field', null);
	      if (child) {
	        typeId = child.getType();
	        child.setVisible(false);
	      } else {
	        typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
	      }

	      // override for 'PHONE', 'CLIENT', 'COMPANY', 'CONTACT' fields: add additional option to setup default country phone code
	      if (['PHONE', 'CLIENT', 'COMPANY', 'CONTACT', 'MYCOMPANY_ID'].indexOf(child.getId()) >= 0) {
	        return this._fieldConfigurator = PhoneNumberInputFieldConfigurator.create('', {
	          editor: this._editor,
	          schemeElement: null,
	          model: parent._model,
	          mode: BX.UI.EntityEditorMode.edit,
	          parent: parent,
	          typeId: typeId,
	          field: child,
	          mandatoryConfigurator: params.mandatoryConfigurator
	        });
	      } else {
	        return this._fieldConfigurator = BX.UI.EntityEditorFieldConfigurator.create('', {
	          editor: this._editor,
	          schemeElement: null,
	          model: parent._model,
	          mode: BX.UI.EntityEditorMode.edit,
	          parent: parent,
	          typeId: typeId,
	          field: child,
	          mandatoryConfigurator: params.mandatoryConfigurator
	        });
	      }
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

	exports.EntityConfigurationManager = EntityConfigurationManager;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Crm.EntitySelectorEx));
//# sourceMappingURL=field-configurator.bundle.js.map
