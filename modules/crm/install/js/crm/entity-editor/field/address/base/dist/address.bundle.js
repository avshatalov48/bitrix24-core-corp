this.BX = this.BX || {};
(function (exports,main_core,main_core_events,main_popup) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18, _templateObject19, _templateObject20, _templateObject21, _templateObject22, _templateObject23, _templateObject24;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var EntityEditorBaseAddressField = /*#__PURE__*/function () {
	  function EntityEditorBaseAddressField() {
	    babelHelpers.classCallCheck(this, EntityEditorBaseAddressField);
	  }

	  babelHelpers.createClass(EntityEditorBaseAddressField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._isMultiple = false;
	      this._settings = settings ? settings : {};
	      this._typesList = [];
	      this._availableTypesIds = [];
	      this._allowedTypeIds = [];
	      this._addressList = [];
	      this._wrapper = null;
	      this._isEditMode = true;
	      this._showFirstItemOnly = BX.prop.getBoolean(settings, 'showFirstItemOnly', false);
	      this._enableAutocomplete = BX.prop.getBoolean(settings, 'enableAutocomplete', true);
	      this._hideDefaultAddressType = BX.prop.getBoolean(this._settings, 'hideDefaultAddressType', false);
	      this._showAddressTypeInViewMode = BX.prop.getBoolean(this._settings, 'showAddressTypeInViewMode', false);
	      this._addrZoneConfig = BX.prop.getObject(this._settings, 'addressZoneConfig', {});
	      this._countryId = BX.prop.getInteger(this._settings, 'countryId', BX.prop.getInteger(this._addrZoneConfig, "countryId", 0));
	      this._defaultAddressType = BX.prop.getInteger(this._settings, 'defaultAddressTypeByCategory', 0);

	      if (this._defaultAddressType <= 0) {
	        this._defaultAddressType = BX.prop.getInteger(this._addrZoneConfig, 'defaultAddressType', 0);
	      }

	      this.updateAllowedTypes();
	    }
	  }, {
	    key: "setMultiple",
	    value: function setMultiple(isMultiple) {
	      this._isMultiple = !!isMultiple;
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(value) {
	      if (this._isMultiple) {
	        var items = main_core.Type.isPlainObject(value) ? value : {};
	        var types = Object.keys(items);
	        var isSame = this._addressList.length > 0 && types.length == this._addressList.length;

	        if (isSame) {
	          var _iterator = _createForOfIteratorHelper(this._addressList),
	              _step;

	          try {
	            for (_iterator.s(); !(_step = _iterator.n()).done;) {
	              var addressItem = _step.value;
	              var type = addressItem.getType();

	              if (!items.hasOwnProperty(type) || items[type] !== addressItem.getValue()) {
	                isSame = false;
	                break;
	              }
	            }
	          } catch (err) {
	            _iterator.e(err);
	          } finally {
	            _iterator.f();
	          }
	        }

	        if ( // if new value is empty and old value has only one empty element
	        !isSame && !types.length && this._addressList.length === 1 && !this._addressList[0].getValue().length) {
	          isSame = true;
	        }

	        if (isSame) {
	          return false; // update is not required
	        }

	        this.removeAllAddresses();

	        for (var _i = 0, _types = types; _i < _types.length; _i++) {
	          var _type = _types[_i];
	          this.addAddress(_type, items[_type]);
	        }

	        if (!types.length) {
	          this.addAddress(this.getDefaultType(), null);
	        }
	      } else {
	        this.removeAllAddresses();
	        var address = main_core.Type.isStringFilled(value) ? value : null;
	        this.addAddress(null, address);
	      }

	      return true;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      if (this._isMultiple) {
	        var result = [];

	        var _iterator2 = _createForOfIteratorHelper(this._addressList),
	            _step2;

	        try {
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            var addressItem = _step2.value;
	            var value = addressItem.getValue();

	            if (main_core.Type.isString(value)) {
	              result.push({
	                type: addressItem.getType(),
	                value: value
	              });
	            }
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }

	        return result;
	      } else {
	        if (this._addressList && this._addressList[0] && main_core.Type.isString(this._addressList[0].getValue())) {
	          return this._addressList[0].getValue();
	        }

	        return null;
	      }
	    }
	  }, {
	    key: "setTypesList",
	    value: function setTypesList(list) {
	      this._typesList = [];

	      if (main_core.Type.isPlainObject(list)) {
	        for (var _i2 = 0, _Object$keys = Object.keys(list); _i2 < _Object$keys.length; _i2++) {
	          var id = _Object$keys[_i2];

	          this._typesList.push(list[id]);
	        }
	      }

	      this.initAvailableTypes();
	    }
	  }, {
	    key: "getTypesList",
	    value: function getTypesList() {
	      var types = [];

	      var _iterator3 = _createForOfIteratorHelper(this._typesList),
	          _step3;

	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var item = _step3.value;
	          var value = BX.prop.getString(item, "ID", "");
	          var name = BX.prop.getString(item, "DESCRIPTION", "");
	          types.push({
	            name: name,
	            value: value
	          });
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }

	      return types;
	    }
	  }, {
	    key: "setAllowedTypes",
	    value: function setAllowedTypes(typeIds) {
	      this._allowedTypeIds = [];

	      if (main_core.Type.isArray(typeIds)) {
	        this._allowedTypeIds = typeIds;
	      }
	    }
	  }, {
	    key: "getAllowedTypes",
	    value: function getAllowedTypes() {
	      return this._allowedTypeIds;
	    }
	  }, {
	    key: "setCountryId",
	    value: function setCountryId(countryId) {
	      var needUpdateAllowedTypes = false;
	      countryId = parseInt(countryId);

	      if (this._countryId !== countryId) {
	        needUpdateAllowedTypes = true;
	      }

	      this._countryId = countryId;

	      if (needUpdateAllowedTypes) {
	        this.updateAllowedTypes();
	      }
	    }
	  }, {
	    key: "getCountryId",
	    value: function getCountryId() {
	      return this._countryId;
	    }
	  }, {
	    key: "getAddressZoneConfig",
	    value: function getAddressZoneConfig() {
	      return this._addrZoneConfig;
	    }
	  }, {
	    key: "getValueTypes",
	    value: function getValueTypes() {
	      var result = [];

	      var _iterator4 = _createForOfIteratorHelper(this._addressList),
	          _step4;

	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var addressItem = _step4.value;
	          var addressType = parseInt(addressItem.getType());

	          if (result.indexOf(addressType) < 0) {
	            result.push(addressType);
	          }
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }

	      return result;
	    }
	  }, {
	    key: "updateAllowedTypes",
	    value: function updateAllowedTypes() {
	      var allowedTypeList = [];
	      var typeValues = this.getValueTypes();
	      var countryId = this.getCountryId();
	      var config = this.getAddressZoneConfig();

	      if (main_core.Type.isPlainObject(config)) {
	        if (config.hasOwnProperty("currentZoneAddressTypes") && main_core.Type.isArray(config["currentZoneAddressTypes"])) {
	          var i;
	          var typeId;
	          var curZoneAddrTypes = config["currentZoneAddressTypes"];

	          for (i = 0; i < curZoneAddrTypes.length; i++) {
	            typeId = parseInt(curZoneAddrTypes[i]);

	            if (allowedTypeList.indexOf(typeId) < 0) {
	              allowedTypeList.push(typeId);
	            }
	          }

	          if (countryId > 0 && config.hasOwnProperty("countryAddressTypeMap") && main_core.Type.isPlainObject(config["countryAddressTypeMap"]) && config["countryAddressTypeMap"].hasOwnProperty(countryId) && main_core.Type.isArray(config["countryAddressTypeMap"][countryId])) {
	            var addrTypeMap = config["countryAddressTypeMap"][countryId];

	            for (i = 0; i < addrTypeMap.length; i++) {
	              typeId = parseInt(addrTypeMap[i]);

	              if (allowedTypeList.indexOf(typeId) < 0) {
	                allowedTypeList.push(typeId);
	              }
	            }
	          }

	          for (i = 0; i < typeValues.length; i++) {
	            typeId = parseInt(typeValues[i]);

	            if (allowedTypeList.indexOf(typeId) < 0) {
	              allowedTypeList.push(typeId);
	            }
	          }
	        }
	      }

	      this._allowedTypeIds = allowedTypeList;

	      var _iterator5 = _createForOfIteratorHelper(this._addressList),
	          _step5;

	      try {
	        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	          var addressItem = _step5.value;
	          addressItem.setAllowedTypesIds(babelHelpers.toConsumableArray(this._allowedTypeIds));
	        }
	      } catch (err) {
	        _iterator5.e(err);
	      } finally {
	        _iterator5.f();
	      }
	    }
	  }, {
	    key: "getDefaultType",
	    value: function getDefaultType() {
	      var defAddrType = this._defaultAddressType.toString();

	      if (defAddrType > 0 && this._availableTypesIds.indexOf(defAddrType) >= 0 && this._allowedTypeIds.indexOf(parseInt(defAddrType)) >= 0) {
	        return defAddrType;
	      }

	      var _iterator6 = _createForOfIteratorHelper(this._typesList),
	          _step6;

	      try {
	        for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
	          var item = _step6.value;
	          var value = BX.prop.getString(item, "ID", "");
	          var isDefault = BX.prop.getString(item, "IS_DEFAULT", false);

	          if (isDefault && this._availableTypesIds.indexOf(value) >= 0 && this._allowedTypeIds.indexOf(parseInt(value)) >= 0) {
	            return value;
	          }
	        }
	      } catch (err) {
	        _iterator6.e(err);
	      } finally {
	        _iterator6.f();
	      }

	      var _iterator7 = _createForOfIteratorHelper(this._typesList),
	          _step7;

	      try {
	        for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
	          var _item = _step7.value;

	          var _value = BX.prop.getString(_item, "ID", "");

	          if (this._availableTypesIds.indexOf(_value) >= 0 && this._allowedTypeIds.indexOf(parseInt(_value)) >= 0) {
	            return _value;
	          }
	        }
	      } catch (err) {
	        _iterator7.e(err);
	      } finally {
	        _iterator7.f();
	      }

	      return null;
	    }
	  }, {
	    key: "layout",
	    value: function layout(isEditMode) {
	      this._isEditMode = isEditMode;
	      this._wrapper = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-address-control-wrap ", "\"></div>"])), this._isEditMode ? 'crm-address-control-wrap-edit' : '');
	      this.refreshLayout();
	      return this._wrapper;
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      main_core.Dom.clean(this._wrapper);
	      var addrCounter = true;

	      var _iterator8 = _createForOfIteratorHelper(this._addressList),
	          _step8;

	      try {
	        for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
	          var addressItem = _step8.value;
	          addressItem.setEditMode(this._isEditMode);

	          if (!this._isEditMode && this._showFirstItemOnly && addrCounter > 1) {
	            var showMore = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary ui-link-dotted\" onmouseup=\"", "\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>"])), this.onShowMoreMouseUp.bind(this), main_core.Loc.getMessage('CRM_ADDRESS_SHOW_ALL'));
	            main_core.Dom.append(showMore, this._wrapper);
	            break;
	          } else {
	            main_core.Dom.append(addressItem.layout(), this._wrapper);
	          }

	          addrCounter++;
	        }
	      } catch (err) {
	        _iterator8.e(err);
	      } finally {
	        _iterator8.f();
	      }

	      if (this._isEditMode && this._isMultiple && !main_core.Type.isNull(this.getDefaultType())) {
	        var crmCompatibilityMode = BX.prop.getBoolean(this._settings, 'crmCompatibilityMode', false);
	        var addButtonWrapClass = crmCompatibilityMode ? 'crm-entity-widget-content-block-add-field' : 'ui-entity-widget-content-block-add-field';
	        var addButtonClass = crmCompatibilityMode ? 'crm-entity-widget-content-add-field' : 'ui-entity-editor-content-add-lnk';
	        main_core.Dom.append(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\"><span class=\"", "\" onclick=\"", "\">", "</span></div>\n\t\t\t"])), addButtonWrapClass, addButtonClass, this.onAddNewAddress.bind(this), main_core.Loc.getMessage('CRM_ADDRESS_ADD')), this._wrapper);
	      }
	    }
	  }, {
	    key: "release",
	    value: function release() {
	      main_core.Dom.clean(this._wrapper);
	      this.removeAllAddresses();
	    }
	  }, {
	    key: "removeAllAddresses",
	    value: function removeAllAddresses() {
	      var ids = this._addressList.map(function (item) {
	        return item.getId();
	      });

	      var _iterator9 = _createForOfIteratorHelper(ids),
	          _step9;

	      try {
	        for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
	          var id = _step9.value;
	          this.removeAddress(id);
	        }
	      } catch (err) {
	        _iterator9.e(err);
	      } finally {
	        _iterator9.f();
	      }
	    }
	  }, {
	    key: "addAddress",
	    value: function addAddress(type) {
	      var value = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var addressItem = new AddressItem(main_core.Text.getRandom(8), {
	        typesList: this.getTypesList(),
	        availableTypesIds: babelHelpers.toConsumableArray(this._availableTypesIds),
	        allowedTypesIds: babelHelpers.toConsumableArray(this._allowedTypeIds),
	        canChangeType: this._isMultiple,
	        enableAutocomplete: this._enableAutocomplete,
	        showAddressTypeInViewMode: this._isMultiple && this._showAddressTypeInViewMode,
	        type: type,
	        value: value
	      });
	      addressItem.subscribe('onUpdateAddress', this.onUpdateAddress.bind(this));
	      addressItem.subscribe('onUpdateAddressType', this.onUpdateAddressType.bind(this));
	      addressItem.subscribe('onDelete', this.onDeleteAddress.bind(this));
	      addressItem.subscribe('onStartLoadAddress', this.onStartLoadAddress.bind(this));
	      addressItem.subscribe('onAddressLoaded', this.onAddressLoaded.bind(this));
	      addressItem.subscribe('onAddressDataInputting', this.onAddressDataInputting.bind(this));
	      addressItem.subscribe('onError', this.onError.bind(this));
	      addressItem.subscribe('onCopyAddress', this.onCopyAddress.bind(this));
	      this.updateAvailableTypes(type, null);

	      this._addressList.push(addressItem);

	      this.updateAllowedTypes();
	      this.updateTypeSelectorVisibility(this._addressList.length > 1);
	      return addressItem;
	    }
	  }, {
	    key: "removeAddress",
	    value: function removeAddress(id) {
	      var addressItem = this.getAddressById(id);

	      if (addressItem) {
	        var type = addressItem.getType();

	        this._addressList.splice(this._addressList.indexOf(addressItem), 1);

	        this.updateAvailableTypes(null, type);
	        this.updateAllowedTypes();
	        this.updateTypeSelectorVisibility(this._addressList.length > 1);
	        addressItem.destroy();
	      }
	    }
	  }, {
	    key: "getAddressById",
	    value: function getAddressById(id) {
	      return this._addressList.filter(function (item) {
	        return item.getId() === id;
	      }).reduce(function (prev, item) {
	        return prev ? prev : item;
	      }, null);
	    }
	  }, {
	    key: "initAvailableTypes",
	    value: function initAvailableTypes() {
	      this._availableTypesIds = [];

	      var _iterator10 = _createForOfIteratorHelper(this._typesList),
	          _step10;

	      try {
	        for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
	          var type = _step10.value;

	          this._availableTypesIds.push(BX.prop.getString(type, "ID", ""));
	        }
	      } catch (err) {
	        _iterator10.e(err);
	      } finally {
	        _iterator10.f();
	      }
	    }
	  }, {
	    key: "updateAvailableTypes",
	    value: function updateAvailableTypes(removedType, addedType) {
	      if (!main_core.Type.isNull(addedType) && this._availableTypesIds.indexOf(addedType) < 0) {
	        this._availableTypesIds.push(addedType);
	      }

	      if (!main_core.Type.isNull(removedType) && this._availableTypesIds.indexOf(removedType) >= 0) {
	        this._availableTypesIds.splice(this._availableTypesIds.indexOf(removedType), 1);
	      }

	      var _iterator11 = _createForOfIteratorHelper(this._addressList),
	          _step11;

	      try {
	        for (_iterator11.s(); !(_step11 = _iterator11.n()).done;) {
	          var addressItem = _step11.value;
	          addressItem.setAvailableTypesIds(babelHelpers.toConsumableArray(this._availableTypesIds));
	        }
	      } catch (err) {
	        _iterator11.e(err);
	      } finally {
	        _iterator11.f();
	      }
	    }
	  }, {
	    key: "updateTypeSelectorVisibility",
	    value: function updateTypeSelectorVisibility(showTypeSelector) {
	      if (!this._hideDefaultAddressType) {
	        return;
	      }

	      var _iterator12 = _createForOfIteratorHelper(this._addressList),
	          _step12;

	      try {
	        for (_iterator12.s(); !(_step12 = _iterator12.n()).done;) {
	          var addressItem = _step12.value;
	          addressItem.setTypeSelectorVisibility(showTypeSelector);
	        }
	      } catch (err) {
	        _iterator12.e(err);
	      } finally {
	        _iterator12.f();
	      }
	    }
	  }, {
	    key: "emitUpdateEvent",
	    value: function emitUpdateEvent() {
	      main_core_events.EventEmitter.emit(this, 'onUpdate', {
	        value: this.getValue()
	      });
	    }
	  }, {
	    key: "onAddNewAddress",
	    value: function onAddNewAddress() {
	      this.addAddress(this.getDefaultType());
	      this.refreshLayout();
	    }
	  }, {
	    key: "onUpdateAddress",
	    value: function onUpdateAddress(event) {
	      this.emitUpdateEvent();
	    }
	  }, {
	    key: "onDeleteAddress",
	    value: function onDeleteAddress(event) {
	      var data = event.getData();
	      var id = data.id;

	      if (this._addressList.length <= 1) {
	        // should be at least one address, so just clear it
	        var addressItem = this.getAddressById(id);

	        if (addressItem) {
	          addressItem.clearValue();
	        }

	        return;
	      }

	      this.removeAddress(id);
	      this.refreshLayout();
	    }
	  }, {
	    key: "onUpdateAddressType",
	    value: function onUpdateAddressType(event) {
	      var data = event.getData();
	      var prevType = data.prevType;
	      var type = data.type;
	      this.updateAvailableTypes(type, prevType);
	      this.updateAllowedTypes();
	      this.emitUpdateEvent();
	    }
	  }, {
	    key: "onShowMoreMouseUp",
	    value: function onShowMoreMouseUp(event) {
	      event.stopPropagation(); // cancel switching client to edit mode

	      this._showFirstItemOnly = false;
	      this.refreshLayout();
	      return false;
	    }
	  }, {
	    key: "onStartLoadAddress",
	    value: function onStartLoadAddress(event) {
	      main_core_events.EventEmitter.emit(this, 'onStartLoadAddress');
	    }
	  }, {
	    key: "onAddressLoaded",
	    value: function onAddressLoaded(event) {
	      main_core_events.EventEmitter.emit(this, 'onAddressLoaded');
	    }
	  }, {
	    key: "onAddressDataInputting",
	    value: function onAddressDataInputting(event) {
	      main_core_events.EventEmitter.emit(this, 'onAddressDataInputting');
	    }
	  }, {
	    key: "onError",
	    value: function onError(event) {
	      main_core_events.EventEmitter.emit(this, 'onError', event);
	    }
	  }, {
	    key: "onCopyAddress",
	    value: function onCopyAddress(event) {
	      var _this = this;

	      var data = event.getData();
	      var sourceAddress = this.getAddressById(data.sourceId);

	      if (!sourceAddress) {
	        return;
	      }

	      var sourceAddressData = sourceAddress.getValue();

	      if (!sourceAddressData.length) {
	        return;
	      }

	      var _loop = function _loop(i) {
	        var type = data.destinationTypes[i];

	        var destinationAddress = _this._addressList.filter(function (item) {
	          return item.getType() == type;
	        }).reduce(function (prev, cur) {
	          return prev ? prev : cur;
	        }, null);

	        if (destinationAddress) {
	          destinationAddress.setValue(sourceAddressData);
	        } else {
	          destinationAddress = _this.addAddress(type, sourceAddressData);
	        }

	        destinationAddress.markAsNew();

	        _this.refreshLayout();
	      };

	      for (var i = 0; i < data.destinationTypes.length; i++) {
	        _loop(i);
	      }

	      this.emitUpdateEvent();
	    }
	  }, {
	    key: "resetView",
	    value: function resetView() {
	      var _iterator13 = _createForOfIteratorHelper(this._addressList),
	          _step13;

	      try {
	        for (_iterator13.s(); !(_step13 = _iterator13.n()).done;) {
	          var addressItem = _step13.value;
	          addressItem.resetView();
	        }
	      } catch (err) {
	        _iterator13.e(err);
	      } finally {
	        _iterator13.f();
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new EntityEditorBaseAddressField();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorBaseAddressField;
	}();

	var AddressItem = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(AddressItem, _EventEmitter);

	  function AddressItem(id, settings) {
	    var _this2;

	    babelHelpers.classCallCheck(this, AddressItem);
	    _this2 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddressItem).call(this));

	    _this2.setEventNamespace('BX.Crm.AddressItem');

	    _this2._id = id;
	    _this2._value = BX.prop.getString(settings, 'value', "");
	    _this2._isTypesMenuOpened = false;
	    _this2._typesList = BX.prop.getArray(settings, 'typesList', []);
	    _this2._availableTypesIds = BX.prop.getArray(settings, 'availableTypesIds', []);
	    _this2._allowedTypesIds = BX.prop.getArray(settings, 'allowedTypesIds', []);
	    _this2._canChangeType = BX.prop.getBoolean(settings, 'canChangeType', false);
	    _this2.typesMenuId = 'address_type_menu_' + _this2._id;
	    _this2._type = BX.prop.getString(settings, 'type', "");
	    _this2._isEditMode = true;
	    _this2._isTypeSelectorVisible = BX.prop.getBoolean(settings, 'isTypeSelectorVisible', true);
	    _this2._isAutocompleteEnabled = BX.prop.getBoolean(settings, 'enableAutocomplete', true);
	    _this2._showAddressTypeInViewMode = BX.prop.getBoolean(settings, 'showAddressTypeInViewMode', true);
	    _this2._showDetails = !_this2._isAutocompleteEnabled || BX.prop.getBoolean(settings, 'showDetails', false);
	    _this2._isLoading = false;
	    _this2._icon = null;
	    _this2._isDropdownLoading = false;
	    _this2._addressWidget = null;
	    _this2._wrapper = null;
	    _this2._domNodes = {};
	    _this2._selectedCopyDestinations = [];
	    _this2._isLocationModuleInstalled = !main_core.Type.isUndefined(BX.Location) && !main_core.Type.isUndefined(BX.Location.Core) && !main_core.Type.isUndefined(BX.Location.Widget);

	    _this2.initializeAddressWidget();

	    return _this2;
	  }

	  babelHelpers.createClass(AddressItem, [{
	    key: "initializeAddressWidget",
	    value: function initializeAddressWidget() {
	      if (!this._isLocationModuleInstalled) {
	        return;
	      }

	      var value = this.getValue();
	      var address = null;

	      if (main_core.Type.isStringFilled(value)) {
	        try {
	          address = new BX.Location.Core.Address(JSON.parse(value));
	        } catch (e) {}
	      }

	      var widgetFactory = new BX.Location.Widget.Factory();
	      this._addressWidget = widgetFactory.createAddressWidget({
	        address: address,
	        mode: this._isEditMode ? BX.Location.Core.ControlMode.edit : BX.Location.Core.ControlMode.view,
	        popupBindOptions: {
	          position: 'right'
	        }
	      });

	      this._addressWidget.subscribeOnStateChangedEvent(this.onAddressWidgetChangedState.bind(this));

	      this._addressWidget.subscribeOnAddressChangedEvent(this.onAddressChanged.bind(this));

	      this._addressWidget.subscribeOnFeatureEvent(this.onFeatureEvent.bind(this));

	      this._addressWidget.subscribeOnErrorEvent(this.onError.bind(this));
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return this._type;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this._value;
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(value) {
	      this.destroy();
	      this._value = value;
	      this.initializeAddressWidget();
	    }
	  }, {
	    key: "markAsNew",
	    value: function markAsNew() {
	      var address = this.getAddress();

	      if (address) {
	        address.id = 0;
	        address.clearLinks();
	      }

	      this._value = address ? address.toJson() : '';
	    }
	  }, {
	    key: "setEditMode",
	    value: function setEditMode(isEditMode) {
	      this._isEditMode = !!isEditMode;

	      if (!main_core.Type.isNull(this._addressWidget)) {
	        this._addressWidget.mode = isEditMode ? BX.Location.Core.ControlMode.edit : BX.Location.Core.ControlMode.view;
	      }
	    }
	  }, {
	    key: "setAvailableTypesIds",
	    value: function setAvailableTypesIds(ids) {
	      this._availableTypesIds = ids;
	    }
	  }, {
	    key: "setAllowedTypesIds",
	    value: function setAllowedTypesIds(ids) {
	      this._allowedTypesIds = ids;
	    }
	  }, {
	    key: "getTypeListByIds",
	    value: function getTypeListByIds(ids) {
	      var result = [];

	      if (main_core.Type.isArray(ids) && ids.length > 0) {
	        var typeMap = {};

	        var _iterator14 = _createForOfIteratorHelper(this._typesList),
	            _step14;

	        try {
	          for (_iterator14.s(); !(_step14 = _iterator14.n()).done;) {
	            var item = _step14.value;
	            typeMap["a" + item.value] = item;
	          }
	        } catch (err) {
	          _iterator14.e(err);
	        } finally {
	          _iterator14.f();
	        }

	        var _iterator15 = _createForOfIteratorHelper(ids),
	            _step15;

	        try {
	          for (_iterator15.s(); !(_step15 = _iterator15.n()).done;) {
	            var typeId = _step15.value;
	            var index = "a" + typeId;

	            if (typeMap.hasOwnProperty(index)) {
	              result.push(typeMap[index]);
	            }
	          }
	        } catch (err) {
	          _iterator15.e(err);
	        } finally {
	          _iterator15.f();
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      if (main_core.Type.isNull(this._addressWidget)) {
	        this._wrapper = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div>Location module is not installed</div>"])));
	        return this._wrapper;
	      }

	      var addressWidgetParams = {};
	      var addressString = this.convertAddressToString(this.getAddress());

	      if (this._isEditMode) {
	        this._wrapper = this.getEditHtml(addressString);
	        addressWidgetParams.mode = BX.Location.Core.ControlMode.edit;
	        addressWidgetParams.inputNode = this._domNodes.searchInput;
	        addressWidgetParams.mapBindElement = this._domNodes.searchInput;
	        addressWidgetParams.fieldsContainer = this._domNodes.detailsContainer;
	        addressWidgetParams.controlWrapper = this._domNodes.addressContainer;
	      } else {
	        this._wrapper = this.getViewHtml(addressString);
	        addressWidgetParams.mode = BX.Location.Core.ControlMode.view;
	        addressWidgetParams.mapBindElement = this._wrapper;
	      }

	      addressWidgetParams.controlWrapper = this._domNodes.addressContainer;

	      this._addressWidget.render(addressWidgetParams);

	      return this._wrapper;
	    }
	  }, {
	    key: "openTypesMenu",
	    value: function openTypesMenu(bindElement) {
	      var _this3 = this;

	      if (this._isTypesMenuOpened) {
	        return;
	      }

	      var menu = [];
	      var allowedTypes = babelHelpers.toConsumableArray(this._allowedTypesIds);
	      var selectedTypeId = parseInt(this._type);

	      if (allowedTypes.indexOf(selectedTypeId) < 0) {
	        allowedTypes.push(selectedTypeId);
	      }

	      var _iterator16 = _createForOfIteratorHelper(this.getTypeListByIds(allowedTypes)),
	          _step16;

	      try {
	        for (_iterator16.s(); !(_step16 = _iterator16.n()).done;) {
	          var item = _step16.value;
	          var selected = selectedTypeId === parseInt(item.value);

	          if (this._availableTypesIds.indexOf(item.value) < 0 && !selected) {
	            continue;
	          }

	          menu.push({
	            text: item.name,
	            value: item.value,
	            //className: selected ? "menu-popup-item-accept" : "menu-popup-item-none",
	            onclick: this.onChangeType.bind(this)
	          });
	        }
	      } catch (err) {
	        _iterator16.e(err);
	      } finally {
	        _iterator16.f();
	      }

	      main_popup.MenuManager.show(this.typesMenuId, bindElement, menu, {
	        angle: false,
	        cacheable: false,
	        events: {
	          onPopupShow: function onPopupShow() {
	            _this3._isTypesMenuOpened = true;
	          },
	          onPopupClose: function onPopupClose() {
	            _this3._isTypesMenuOpened = false;
	          }
	        }
	      });
	      var createdMenu = main_popup.MenuManager.getMenuById(this.typesMenuId);

	      if (createdMenu && main_core.Type.isDomNode(this._domNodes.addressTypeSelector) && this._domNodes.addressTypeSelector.offsetWidth > 200) {
	        createdMenu.getPopupWindow().setWidth(this._domNodes.addressTypeSelector.offsetWidth);
	      }
	    }
	  }, {
	    key: "closeTypesMenu",
	    value: function closeTypesMenu() {
	      var menu = main_popup.MenuManager.getMenuById(this.typesMenuId);

	      if (menu) {
	        menu.close();
	      }
	    }
	  }, {
	    key: "setTypeSelectorVisibility",
	    value: function setTypeSelectorVisibility(visible) {
	      this._isTypeSelectorVisible = !!visible;
	    }
	  }, {
	    key: "getEditHtml",
	    value: function getEditHtml(addressString) {
	      this._domNodes.typeName = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl-element\"></div>"])));
	      this._domNodes.searchInput = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" value=\"", "\" ", ">"])), addressString, this._isAutocompleteEnabled ? '' : 'readonly');
	      this._domNodes.icon = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	      this._domNodes.addressContainer = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"crm-address-search-control-block\">\n\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>"])), this._domNodes.icon, this._domNodes.searchInput);
	      this._domNodes.detailsContainer = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"location-fields-control-block\"></div>"])));

	      if (this._canChangeType) {
	        if (this._isTypeSelectorVisible) {
	          this._domNodes.addressTypeSelector = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-inline ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w25\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), this.onToggleTypesMenu.bind(this), this._domNodes.typeName);
	          this._domNodes.addressTypeContainer = null;
	          main_core.Dom.addClass(this._domNodes.addressContainer, ['ui-ctl-inline', 'ui-ctl-w75']);
	          this._domNodes.addressContainer = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl-inline ui-ctl-w100\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), this._domNodes.addressTypeSelector, this._domNodes.addressContainer);
	        } else {
	          this._domNodes.addressTypeSelector = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), this.onToggleTypesMenu.bind(this), this._domNodes.typeName);
	          this._domNodes.addressTypeContainer = main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"location-fields-control-block crm-address-type-block\">\n\t\t\t\t\t\t<div class=\"ui-entity-editor-content-block ui-entity-editor-field-text\">\n\t\t\t\t\t\t\t<div class=\"ui-entity-editor-block-title\">\n\t\t\t\t\t\t\t\t<label class=\"ui-entity-editor-block-title-text\">", "</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>"])), main_core.Loc.getMessage('CRM_ADDRESS_TYPE'), this._domNodes.addressTypeSelector);
	        }

	        this.refreshTypeName();
	      }

	      this.refreshIcon();
	      this._domNodes.detailsToggler = main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\" onclick=\"", "\"></span>"])), this.onToggleDetailsVisibility.bind(this));

	      if (this._canChangeType) {
	        this._domNodes.copyButton = main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\" onclick=\"", "\">", "</span>"])), this.onCopyButtonClick.bind(this), main_core.Loc.getMessage('CRM_ADDRESS_COPY1'));
	      }

	      this.refreshCopyButtonVisibility();
	      this.setDetailsVisibility(this._showDetails);
	      var result = main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-address-control-item\">\n\t\t\t\t<div class=\"crm-address-control-mode-switch\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"])), this._domNodes.copyButton ? this._domNodes.copyButton : '', this._domNodes.detailsToggler, this._domNodes.addressContainer, this._domNodes.detailsContainer);

	      if (this._canChangeType && main_core.Type.isDomNode(this._domNodes.addressTypeContainer)) {
	        main_core.Dom.append(this._domNodes.addressTypeContainer, result);
	      }

	      return result;
	    }
	  }, {
	    key: "getViewHtml",
	    value: function getViewHtml(addressString) {
	      var _this4 = this;

	      this._domNodes.addressContainer = main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block-text\">\n\t\t\t\t<span class=\"ui-link ui-link-dark ui-link-dotted\">", "</span>\n\t\t\t</div>"])), addressString);
	      var addressType = '';

	      if (this._showAddressTypeInViewMode) {
	        var typeName = this._typesList.filter(function (item) {
	          return item.value == _this4._type;
	        }).map(function (item) {
	          return item.name;
	        }).join('');

	        addressType = main_core.Tag.render(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-link ui-link-secondary\">", ":</span>"])), main_core.Text.encode(typeName));
	      }

	      return main_core.Tag.render(_templateObject19 || (_templateObject19 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-address-control-item\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"])), addressType, this._domNodes.addressContainer);
	    }
	  }, {
	    key: "refreshTypeName",
	    value: function refreshTypeName() {
	      var _this5 = this;

	      if (main_core.Type.isDomNode(this._domNodes.typeName)) {
	        var typeName = this._typesList.filter(function (item) {
	          return item.value === _this5._type;
	        }).map(function (item) {
	          return item.name;
	        }).join('');

	        this._domNodes.typeName.textContent = typeName;
	        this._domNodes.typeName.title = typeName;
	      }
	    }
	  }, {
	    key: "refreshIcon",
	    value: function refreshIcon() {
	      var newIcon = this.getNewIcon();

	      if (this._icon !== newIcon) {
	        var node = this._domNodes.icon;

	        if (main_core.Type.isDomNode(node)) {
	          var newNode;

	          if (newIcon === 'loading') {
	            newNode = main_core.Tag.render(_templateObject20 || (_templateObject20 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-ctl-after ui-ctl-icon-loader\"></span>"])));
	          } else {
	            if (newIcon === 'clear') {
	              newNode = main_core.Tag.render(_templateObject21 || (_templateObject21 = babelHelpers.taggedTemplateLiteral(["<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" onclick=\"", "\"></button>"])), this.onDelete.bind(this));
	            } else if (newIcon === 'search') {
	              newNode = main_core.Tag.render(_templateObject22 || (_templateObject22 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-ctl-after ", "\"></span>"])), this._isAutocompleteEnabled ? 'ui-ctl-icon-search' : '');
	            }
	          }

	          main_core.Dom.replace(node, newNode);
	          this._domNodes.icon = newNode;
	        }

	        this._icon = newIcon;
	      }
	    }
	  }, {
	    key: "getNewIcon",
	    value: function getNewIcon() {
	      if (this._isLoading) {
	        return 'loading';
	      } else {
	        return this.getAddress() ? 'clear' : 'search';
	      }
	    }
	  }, {
	    key: "refreshCopyButtonVisibility",
	    value: function refreshCopyButtonVisibility() {
	      var node = this._domNodes.copyButton;

	      if (main_core.Type.isDomNode(node)) {
	        var isVisible = !!this.getAddress();
	        main_core.Dom.style(node, 'display', isVisible ? '' : 'none');
	      }
	    }
	  }, {
	    key: "convertAddressToString",
	    value: function convertAddressToString(address) {
	      if (!address) {
	        return '';
	      }

	      return address.toString(this.getAddressFormat());
	    }
	  }, {
	    key: "getAddress",
	    value: function getAddress() {
	      return main_core.Type.isNull(this._addressWidget) ? null : this._addressWidget.address;
	    }
	  }, {
	    key: "getAddressFormat",
	    value: function getAddressFormat() {
	      return main_core.Type.isNull(this._addressWidget) ? null : this._addressWidget.addressFormat;
	    }
	  }, {
	    key: "clearValue",
	    value: function clearValue() {
	      if (!main_core.Type.isNull(this._addressWidget)) {
	        this._addressWidget.resetView();

	        this._addressWidget.address = null;
	      }

	      if (main_core.Type.isDomNode(this._domNodes.searchInput)) {
	        this._domNodes.searchInput.value = '';
	      }

	      this._value = "";
	      this._isLoading = false;
	      this.refreshIcon();
	      this.refreshCopyButtonVisibility();
	    }
	  }, {
	    key: "setDetailsVisibility",
	    value: function setDetailsVisibility(visible) {
	      this._showDetails = !!visible;

	      if (this._showDetails) {
	        main_core.Dom.addClass(this._domNodes.detailsContainer, 'visible');

	        if (main_core.Type.isDomNode(this._domNodes.detailsToggler)) {
	          this._domNodes.detailsToggler.textContent = main_core.Loc.getMessage('CRM_ADDRESS_MODE_SHORT');
	        }

	        if (this._canChangeType) {
	          main_core.Dom.addClass(this._domNodes.addressTypeContainer, 'visible');
	        }
	      } else {
	        main_core.Dom.removeClass(this._domNodes.detailsContainer, 'visible');

	        if (main_core.Type.isDomNode(this._domNodes.detailsToggler)) {
	          this._domNodes.detailsToggler.textContent = main_core.Loc.getMessage('CRM_ADDRESS_MODE_DETAILED');
	        }

	        if (this._canChangeType) {
	          main_core.Dom.removeClass(this._domNodes.addressTypeContainer, 'visible');
	        }
	      }
	    }
	  }, {
	    key: "showCopyDestinationPopup",
	    value: function showCopyDestinationPopup() {
	      var _this6 = this;

	      var popup = main_popup.PopupManager.create({
	        id: this._id + '_copy_dst_popup',
	        cacheable: false,
	        autoHide: true,
	        titleBar: main_core.Loc.getMessage('CRM_ADDRESS_COPY_TITLE'),
	        content: this.getCopyDestinationLayout(),
	        closeIcon: true,
	        closeByEsc: true,
	        buttons: [new BX.UI.Button({
	          id: 'copy',
	          text: main_core.Loc.getMessage('CRM_ADDRESS_COPY2'),
	          color: BX.UI.Button.Color.PRIMARY,
	          state: BX.UI.ButtonState.DISABLED,
	          onclick: function onclick(button) {
	            button.getContext().close();

	            _this6.emit('onCopyAddress', {
	              sourceId: _this6.getId(),
	              destinationTypes: _this6._selectedCopyDestinations
	            });
	          }
	        })]
	      });
	      popup.show();
	    }
	  }, {
	    key: "getCopyDestinationLayout",
	    value: function getCopyDestinationLayout() {
	      var _this7 = this;

	      var types = this.getTypeListByIds(this._allowedTypesIds).filter(function (item) {
	        return item.value !== _this7._type;
	      });
	      return main_core.Tag.render(_templateObject23 || (_templateObject23 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"ui-title-7\">", "</div>\n\t\t\t\t<div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_ADDRESS_COPY_TO'), types.map(function (item) {
	        return main_core.Tag.render(_templateObject24 || (_templateObject24 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-checkbox ui-ctl-xs\">\n\t\t\t\t\t<label>\n\t\t\t\t\t<input onclick=\"", "\" type=\"checkbox\" value=\"", "\">\n\t\t\t\t\t\t<span class=\"ui-ctl-label-text\">", "</span>\n\t\t\t\t\t</label>\n\t\t\t\t\t</div>\n\t\t\t\t\t"])), _this7.onChangeCopyDestination.bind(_this7), item.value, main_core.Text.encode(item.name));
	      }));
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (!main_core.Type.isNull(this._addressWidget)) {
	        this._addressWidget.destroy();
	      }
	    }
	  }, {
	    key: "onToggleDetailsVisibility",
	    value: function onToggleDetailsVisibility() {
	      this.setDetailsVisibility(!this._showDetails);
	    }
	  }, {
	    key: "onDelete",
	    value: function onDelete() {
	      this.clearValue();
	      this.emit('onUpdateAddress', {
	        id: this.getId(),
	        value: this.getValue()
	      });
	    }
	  }, {
	    key: "onCopyButtonClick",
	    value: function onCopyButtonClick() {
	      this.showCopyDestinationPopup();
	    }
	  }, {
	    key: "onChangeCopyDestination",
	    value: function onChangeCopyDestination(e) {
	      var input = e.target;
	      var value = input ? input.value : null;
	      var isChecked = input ? input.checked : false;

	      if (isChecked && this._selectedCopyDestinations.indexOf(value) < 0) {
	        this._selectedCopyDestinations.push(value);
	      }

	      if (!isChecked && this._selectedCopyDestinations.indexOf(value) >= 0) {
	        this._selectedCopyDestinations.splice(this._selectedCopyDestinations.indexOf(value), 1);
	      }

	      var popup = main_popup.PopupManager.getPopupById(this._id + '_copy_dst_popup');

	      if (popup) {
	        var button = popup.getButton('copy');

	        if (button) {
	          button.setDisabled(!this._selectedCopyDestinations.length);
	        }
	      }
	    }
	  }, {
	    key: "onToggleTypesMenu",
	    value: function onToggleTypesMenu(event) {
	      if (this._isTypesMenuOpened) {
	        this.closeTypesMenu();
	      } else {
	        this.openTypesMenu(event.target);
	      }
	    }
	  }, {
	    key: "onChangeType",
	    value: function onChangeType(e, item) {
	      this.closeTypesMenu();

	      if (this._type !== item.value) {
	        var prevType = this._type;
	        this._type = item.value;
	        this.refreshTypeName();
	        this.emit('onUpdateAddressType', {
	          id: this.getId(),
	          type: this.getType(),
	          prevType: prevType
	        });
	      }
	    }
	  }, {
	    key: "onAddressWidgetChangedState",
	    value: function onAddressWidgetChangedState(event) {
	      var data = event.getData(),
	          state = data.state;
	      var wasLoading = this._isLoading;
	      this.computeIsLoading();

	      if (wasLoading !== this._isLoading) {
	        this.refreshIcon();
	        this.refreshCopyButtonVisibility();
	      }

	      if (state === BX.Location.Widget.State.DATA_LOADING) {
	        this.emit('onStartLoadAddress', {
	          id: this.getId()
	        });
	      } else if (state === BX.Location.Widget.State.DATA_LOADED) {
	        this.emit('onAddressLoaded', {
	          id: this.getId()
	        });
	      } else if (state === BX.Location.Widget.State.DATA_INPUTTING) {
	        this.emit('onAddressDataInputting', {
	          id: this.getId()
	        });
	      }
	    }
	  }, {
	    key: "onAddressChanged",
	    value: function onAddressChanged(event) {
	      this._isLoading = false;
	      var data = event.getData();
	      this._value = main_core.Type.isObject(data.address) ? data.address.toJson() : '';
	      this.refreshIcon();
	      this.refreshCopyButtonVisibility();
	      this.emit('onUpdateAddress', {
	        id: this.getId(),
	        value: this.getValue()
	      });
	    }
	  }, {
	    key: "onFeatureEvent",
	    value: function onFeatureEvent(event) {
	      var data = event.getData();

	      if (data.feature instanceof BX.Location.Widget.AutocompleteFeature) {
	        this._isDropdownLoading = data.eventCode === BX.Location.Widget.AutocompleteFeature.searchStartedEvent;
	        var wasLoading = this._isLoading;
	        this.computeIsLoading();

	        if (wasLoading !== this._isLoading) {
	          this.refreshIcon();
	          this.refreshCopyButtonVisibility();
	        }
	      }
	    }
	  }, {
	    key: "onError",
	    value: function onError(event) {
	      var data = event.getData();
	      var errors = data.errors;
	      var errorMessage = errors.map(function (error) {
	        return error.message + (error.code.length ? "".concat(error.code) : '');
	      }).join(', ');
	      this._isLoading = false;
	      this.refreshIcon();
	      this.refreshCopyButtonVisibility();
	      this.emit('onError', {
	        id: this.getId(),
	        error: errorMessage
	      });
	    }
	  }, {
	    key: "computeIsLoading",
	    value: function computeIsLoading() {
	      this._isLoading = this._addressWidget.state === BX.Location.Widget.State.DATA_LOADING || this._isDropdownLoading;
	    }
	  }, {
	    key: "resetView",
	    value: function resetView() {
	      this._addressWidget.resetView();
	    }
	  }]);
	  return AddressItem;
	}(main_core_events.EventEmitter);

	exports.EntityEditorBaseAddressField = EntityEditorBaseAddressField;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event,BX.Main));
//# sourceMappingURL=address.bundle.js.map
