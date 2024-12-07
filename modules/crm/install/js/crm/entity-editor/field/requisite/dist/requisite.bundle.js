/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_entityEditor_field_requisite_autocomplete,main_popup,ui_dialogs_messagebox,crm_entityEditor_field_address,main_core,main_loader,crm_entityEditor_field_address_base,main_core_events) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var RequisiteList = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(RequisiteList, _EventEmitter);
	  function RequisiteList() {
	    var _this;
	    babelHelpers.classCallCheck(this, RequisiteList);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(RequisiteList).call(this));
	    _this.setEventNamespace('BX.Crm.EntityEditorRequisiteField.RequisiteList');
	    _this._items = [];
	    _this.CHANGE_EVENT = 'onChange';
	    return _this;
	  }
	  babelHelpers.createClass(RequisiteList, [{
	    key: "initialize",
	    value: function initialize(value, settings) {
	      if (!main_core.Type.isArray(value)) {
	        value = [];
	      }
	      var _iterator = _createForOfIteratorHelper(value),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;
	          var listItem = RequisiteListItem.create(item);
	          this._items.push(listItem);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }, {
	    key: "getList",
	    value: function getList() {
	      return this._items.filter(function (item) {
	        return !item.isDeleted();
	      });
	    }
	  }, {
	    key: "getListWithDeleted",
	    value: function getListWithDeleted() {
	      return this._items;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return !this.getList().length;
	    }
	  }, {
	    key: "getSelected",
	    value: function getSelected() {
	      var selectedId = this.getSelectedId();
	      return this.getById(selectedId);
	    }
	  }, {
	    key: "getSelectedId",
	    value: function getSelectedId() {
	      var list = this.getList();
	      if (!list.length) {
	        return null;
	      }
	      for (var index = 0; index < list.length; index++) {
	        var requisite = list[index];
	        if (requisite.isSelected()) {
	          return index;
	        }
	      }
	      return 0; // first element by default
	    }
	  }, {
	    key: "getById",
	    value: function getById(id) {
	      var list = this.getList();
	      if (null === id) {
	        return null;
	      }
	      if (id >= 0 && id < list.length) {
	        return list[id];
	      }
	      return null;
	    }
	  }, {
	    key: "getByRequisiteId",
	    value: function getByRequisiteId(requisiteId) {
	      var list = this.getList();
	      return list.filter(function (item) {
	        return item.getRequisiteId() == requisiteId;
	      }).reduce(function (prev, current) {
	        return current;
	      }, null);
	    }
	  }, {
	    key: "setSelected",
	    value: function setSelected(requisiteId, bankDetailsId) {
	      var requisite = this.getById(requisiteId);
	      if (requisite) {
	        var _iterator2 = _createForOfIteratorHelper(this.getList()),
	          _step2;
	        try {
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            var item = _step2.value;
	            var selected = item === requisite;
	            item.setSelected(selected);
	            if (selected) {
	              item.setSelectedBankDetails(main_core.Type.isNull(bankDetailsId) ? requisite.getSelectedBankDetailId() : bankDetailsId);
	            }
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }
	        this.notifyListChanged();
	      }
	    }
	  }, {
	    key: "getNewRequisiteId",
	    value: function getNewRequisiteId() {
	      var maxExistedId = this.getList().reduce(function (prevId, item) {
	        var requisiteId = item.getRequisiteIdAsString();
	        var match = requisiteId ? requisiteId.match(RequisiteListItem.newRequisitePattern) : false;
	        var currentId = match && match[1] ? match[1] : -1;
	        return Math.max(prevId, currentId);
	      }, -1);
	      return 'n' + (parseInt(maxExistedId) + 1);
	    }
	  }, {
	    key: "indexOf",
	    value: function indexOf(item) {
	      return this._items.indexOf(item);
	    }
	  }, {
	    key: "add",
	    value: function add(item) {
	      this._items.push(item);
	      if (!item.isAddressOnly()) {
	        this.setSelected(this._items.indexOf(item));
	      } else {
	        this.notifyListChanged();
	      }
	    }
	  }, {
	    key: "remove",
	    value: function remove(item) {
	      var index = this._items.indexOf(item);
	      if (index >= 0) {
	        this._items.splice(index, 1);
	      }
	      this.notifyListChanged();
	    }
	  }, {
	    key: "removePostponed",
	    value: function removePostponed(item) {
	      var index = this._items.indexOf(item);
	      if (index >= 0) {
	        item.setDeleted(true);
	      }
	      this.notifyListChanged();
	    }
	  }, {
	    key: "hide",
	    value: function hide(item) {
	      var index = this._items.indexOf(item);
	      if (index >= 0) {
	        item.setAddressOnly(true);
	        item.setChanged(true);
	      }
	      this.notifyListChanged();
	    }
	  }, {
	    key: "unhide",
	    value: function unhide(item) {
	      var index = this._items.indexOf(item);
	      if (index >= 0) {
	        item.setAddressOnly(false);
	        item.setChanged(true);
	      }
	      this.notifyListChanged();
	    }
	  }, {
	    key: "notifyListChanged",
	    value: function notifyListChanged() {
	      this.emit(this.CHANGE_EVENT);
	    }
	  }, {
	    key: "exportToModel",
	    value: function exportToModel() {
	      var result = [];
	      var _iterator3 = _createForOfIteratorHelper(this._items),
	        _step3;
	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var item = _step3.value;
	          var exportedItem = item.exportToModel();
	          result.push(_objectSpread({}, exportedItem));
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }
	      return result;
	    }
	  }], [{
	    key: "create",
	    value: function create(value, settings) {
	      var self = new RequisiteList();
	      self.initialize(value, settings);
	      return self;
	    }
	  }]);
	  return RequisiteList;
	}(main_core_events.EventEmitter);
	var RequisiteListItem = /*#__PURE__*/function () {
	  function RequisiteListItem() {
	    babelHelpers.classCallCheck(this, RequisiteListItem);
	    this._data = null;
	  }
	  babelHelpers.createClass(RequisiteListItem, [{
	    key: "initialize",
	    value: function initialize(value, settings) {
	      if (main_core.Type.isPlainObject(value)) {
	        this._data = _objectSpread({}, value);
	        this._data.isNew = false;
	        this._data.isChanged = false;
	        this._data.isDeleted = false;
	        this._data.isAddressOnly = false;
	        this._data.formData = {};
	        this._data.addressData = {};
	        if (!main_core.Type.isPlainObject(this._data.autocompleteState)) {
	          this._data.autocompleteState = {};
	        }
	      } else {
	        // new empty requisite
	        this._data = {
	          isNew: true,
	          isChanged: false,
	          isDeleted: false,
	          isAddressOnly: false,
	          selected: false,
	          presetId: null,
	          requisiteId: BX.prop.getString(settings, 'newRequisiteId', 'n0'),
	          requisiteData: '',
	          requisiteDataSign: '',
	          bankDetails: [],
	          bankDetailIdSelected: 0,
	          addressList: {},
	          value: {},
	          title: '',
	          subtitle: '',
	          autocompleteState: {},
	          formData: {},
	          addressData: {}
	        };
	        var extraData = BX.prop.getObject(settings, 'newRequisiteExtraFields', {});
	        this._data = _objectSpread(_objectSpread({}, this._data), extraData);
	      }
	      this._data.initialAddressDdta = null;
	      this.prepareViewData(this._data);
	    }
	  }, {
	    key: "prepareViewData",
	    value: function prepareViewData() {
	      try {
	        this._data.value = this._data.requisiteData ? JSON.parse(this._data.requisiteData) : {};
	      } catch (e) {
	        this._data.value = {};
	      }
	      if (main_core.Type.isPlainObject(this._data.value) && main_core.Type.isPlainObject(this._data.value.viewData)) {
	        this._data.title = this._data.value.viewData.title;
	        this._data.subtitle = this._data.value.viewData.subtitle;
	      }
	      if (this.getRequisiteIdAsString().match(RequisiteListItem.newRequisitePattern))
	        // was new requisite
	        {
	          var newRequisiteId = BX.prop.getNumber(this.getFields(), 'ID', 0);
	          if (newRequisiteId > 0)
	            // if new requisite was saved
	            {
	              this.setRequisiteId(newRequisiteId);
	            }
	        }
	      this.setAddressOnly(BX.prop.getString(this.getFields(), 'ADDRESS_ONLY', 'N') === 'Y');
	      this._data.bankDetails = [];
	      if (main_core.Type.isPlainObject(this._data.value) && main_core.Type.isArray(this._data.value.bankDetailViewDataList)) {
	        this._data.bankDetails = this.prepareBankDetailsList(this._data.value.bankDetailViewDataList);
	      }
	      this._data.addressList = _objectSpread({}, BX.prop.getObject(this.getFields(), 'RQ_ADDR', {}));
	    }
	  }, {
	    key: "prepareBankDetailsList",
	    value: function prepareBankDetailsList(bankDetails) {
	      var result = [];
	      var _iterator4 = _createForOfIteratorHelper(bankDetails),
	        _step4;
	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var bankDetailsItem = _step4.value;
	          if (bankDetailsItem.deleted) {
	            continue; // Deleted items should not be shown
	          }

	          if (main_core.Type.isPlainObject(bankDetailsItem.viewData)) {
	            var item = {
	              'title': bankDetailsItem.viewData.title,
	              'id': bankDetailsItem.pseudoId,
	              'value': '',
	              'selected': !!bankDetailsItem.selected
	            };
	            if (main_core.Type.isArray(bankDetailsItem.viewData.fields) && bankDetailsItem.viewData.fields.length) {
	              item.value = bankDetailsItem.viewData.fields.filter(function (item) {
	                return main_core.Type.isStringFilled(item.textValue);
	              }).map(function (item) {
	                return item.title + ': ' + item.textValue;
	              }).join(', ');
	            }
	            if (!item.value.length) {
	              item.value = item.title;
	            }
	            result.push(item);
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
	    key: "isSelected",
	    value: function isSelected() {
	      if (!this._data.hasOwnProperty('justSelected')) {
	        return BX.prop.getBoolean(this._data, 'selected', false);
	      }
	      return BX.prop.getBoolean(this._data, 'justSelected', false);
	    }
	  }, {
	    key: "isChanged",
	    value: function isChanged() {
	      return BX.prop.getBoolean(this._data, 'isChanged', false);
	    }
	  }, {
	    key: "setChanged",
	    value: function setChanged(changed) {
	      this._data.isChanged = !!changed;
	    }
	  }, {
	    key: "isNew",
	    value: function isNew() {
	      return BX.prop.getBoolean(this._data, 'isNew', false);
	    }
	  }, {
	    key: "setNew",
	    value: function setNew(isNew) {
	      this._data.isNew = !!isNew;
	    }
	  }, {
	    key: "getSelectedBankDetailId",
	    value: function getSelectedBankDetailId() {
	      var selectedBankDetailId = BX.prop.getInteger(this._data, 'selectedBankDetailId', -1);
	      if (selectedBankDetailId !== -1) {
	        return selectedBankDetailId;
	      }
	      selectedBankDetailId = this.getBankDetails().reduce(function (selected, item, index) {
	        return item.selected ? index : selected;
	      }, -1);
	      this._data.selectedBankDetailId = selectedBankDetailId;
	      return selectedBankDetailId > -1 ? selectedBankDetailId : 0;
	    }
	  }, {
	    key: "getBankDetailById",
	    value: function getBankDetailById(bankDetailId) {
	      var list = this.getBankDetails();
	      if (null === bankDetailId) {
	        return null;
	      }
	      if (bankDetailId >= 0 && bankDetailId < list.length) {
	        return list[bankDetailId];
	      }
	      return null;
	    }
	  }, {
	    key: "getBankDetailByBankDetailId",
	    value: function getBankDetailByBankDetailId(bankDetailId) {
	      var list = this.getBankDetails();
	      if (null === bankDetailId) {
	        return null;
	      }
	      return list.filter(function (item) {
	        return item.id == bankDetailId;
	      }).reduce(function (prev, current) {
	        return current;
	      }, null);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return BX.prop.getString(this._data, 'title', "");
	    }
	  }, {
	    key: "getSubtitle",
	    value: function getSubtitle() {
	      return BX.prop.getString(this._data, 'subtitle', "");
	    }
	  }, {
	    key: "getPresetId",
	    value: function getPresetId() {
	      return BX.prop.getString(this._data, 'presetId', "0");
	    }
	  }, {
	    key: "getPresetCountryId",
	    value: function getPresetCountryId() {
	      return BX.prop.getString(this._data, 'presetCountryId', "0");
	    }
	  }, {
	    key: "getBankDetails",
	    value: function getBankDetails() {
	      return BX.prop.getArray(this._data, 'bankDetails', []);
	    }
	  }, {
	    key: "getRequisiteId",
	    value: function getRequisiteId() {
	      return this._data.requisiteId;
	    }
	  }, {
	    key: "getRequisiteIdAsString",
	    value: function getRequisiteIdAsString() {
	      var requisiteId = this.getRequisiteId();
	      requisiteId = main_core.Type.isNumber(requisiteId) ? String(requisiteId) : requisiteId;
	      return main_core.Type.isStringFilled(requisiteId) ? requisiteId : '';
	    }
	  }, {
	    key: "getRequisiteData",
	    value: function getRequisiteData() {
	      return this._data.requisiteData;
	    }
	  }, {
	    key: "getRequisiteDataSign",
	    value: function getRequisiteDataSign() {
	      return this._data.requisiteDataSign;
	    }
	  }, {
	    key: "getFields",
	    value: function getFields() {
	      if (main_core.Type.isPlainObject(this._data.value) && main_core.Type.isPlainObject(this._data.value.fields)) {
	        return _objectSpread({}, this._data.value.fields);
	      }
	      return {};
	    }
	  }, {
	    key: "getAutocompleteData",
	    value: function getAutocompleteData() {
	      var result = null;
	      var autocompleteState = this.getAutocompleteState();
	      var selectedAutocompleteItem = BX.prop.getObject(autocompleteState, 'currentItem', null);
	      if (main_core.Type.isPlainObject(selectedAutocompleteItem)) {
	        result = {
	          title: BX.prop.getString(selectedAutocompleteItem, 'title', ''),
	          subTitle: BX.prop.getString(selectedAutocompleteItem, 'subTitle', '')
	        };
	      } else if (!main_core.Type.isUndefined(this._data.value.viewData) && main_core.Type.isArray(this._data.value.viewData.fields)) {
	        var fields = this._data.value.viewData.fields;
	        result = {
	          title: main_core.Type.isStringFilled(this._data.title) ? this._data.title : '',
	          subTitle: fields.filter(function (item) {
	            return item.name === 'RQ_INN' && item.textValue.length;
	          }).map(function (item) {
	            return item.title + ' ' + item.textValue;
	          }).join('')
	        };
	      }
	      return result;
	    }
	  }, {
	    key: "setAutocompleteState",
	    value: function setAutocompleteState(state) {
	      this._data.autocompleteState = main_core.Type.isPlainObject(state) ? state : {};
	    }
	  }, {
	    key: "getAutocompleteState",
	    value: function getAutocompleteState() {
	      return this._data.autocompleteState;
	    }
	  }, {
	    key: "getAddressList",
	    value: function getAddressList() {
	      return this._data.addressList;
	    }
	  }, {
	    key: "setAddressList",
	    value: function setAddressList(addressList) {
	      this._data.addressList = addressList;
	    }
	  }, {
	    key: "setInitialAddressData",
	    value: function setInitialAddressData(addressData) {
	      this._data.initialAddressDdta = addressData;
	    }
	  }, {
	    key: "getAddressesForSave",
	    value: function getAddressesForSave() {
	      var oldAddressTypes = main_core.Type.isPlainObject(this._data.initialAddressDdta) ? Object.keys(this._data.initialAddressDdta) : [];
	      var addresses = {};
	      var _iterator5 = _createForOfIteratorHelper(oldAddressTypes),
	        _step5;
	      try {
	        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	          var _type2 = _step5.value;
	          addresses[_type2] = "";
	        }
	      } catch (err) {
	        _iterator5.e(err);
	      } finally {
	        _iterator5.f();
	      }
	      var addressData = this.getAddressData();
	      for (var type in addressData) {
	        if (addressData.hasOwnProperty(type) && addressData[type].length) {
	          addresses[type] = addressData[type];
	        }
	      }
	      for (var _type in addresses) {
	        if (main_core.Type.isString(addresses[_type]) && addresses[_type] === "") {
	          addresses[_type] = {
	            DELETED: 'Y'
	          };
	        }
	      }
	      return addresses;
	    }
	  }, {
	    key: "setRequisiteId",
	    value: function setRequisiteId(requisiteId) {
	      this._data.requisiteId = requisiteId;
	    }
	  }, {
	    key: "setPresetId",
	    value: function setPresetId(presetId) {
	      this._data.presetId = presetId;
	    }
	  }, {
	    key: "setPresetCountryId",
	    value: function setPresetCountryId(presetCountryId) {
	      this._data.presetCountryId = presetCountryId;
	    }
	  }, {
	    key: "setSelected",
	    value: function setSelected(selected) {
	      this._data.selected = !!selected;
	      this._data.justSelected = !!selected;
	    }
	  }, {
	    key: "setRequisiteData",
	    value: function setRequisiteData(requisiteData, requisiteDataSign) {
	      this._data.requisiteData = requisiteData;
	      if (main_core.Type.isStringFilled(requisiteDataSign)) {
	        this._data.requisiteDataSign = requisiteDataSign;
	      }
	      this.prepareViewData();
	    }
	  }, {
	    key: "setDeleted",
	    value: function setDeleted(isDeleted) {
	      this._data.isDeleted = !!isDeleted;
	    }
	  }, {
	    key: "isDeleted",
	    value: function isDeleted() {
	      return this._data.isDeleted;
	    }
	  }, {
	    key: "setAddressOnly",
	    value: function setAddressOnly(isAddressOnly) {
	      this._data.isAddressOnly = !!isAddressOnly;
	      this.setFormData(_objectSpread(_objectSpread({}, this.getFormData()), {}, {
	        'ADDRESS_ONLY': isAddressOnly ? 'Y' : 'N'
	      }));
	    }
	  }, {
	    key: "isAddressOnly",
	    value: function isAddressOnly() {
	      return this._data.isAddressOnly;
	    }
	  }, {
	    key: "isEmptyFormData",
	    value: function isEmptyFormData() {
	      return Object.keys(this._data.formData).length <= 0;
	    }
	  }, {
	    key: "getFormData",
	    value: function getFormData() {
	      return this._data.formData;
	    }
	  }, {
	    key: "setFormData",
	    value: function setFormData(formData) {
	      this._data.formData = formData;
	    }
	  }, {
	    key: "clearFormData",
	    value: function clearFormData() {
	      this.setFormData({});
	    }
	  }, {
	    key: "isEmptyAddressData",
	    value: function isEmptyAddressData() {
	      return Object.keys(this._data.addressData).length <= 0;
	    }
	  }, {
	    key: "getAddressData",
	    value: function getAddressData() {
	      return this._data.addressData;
	    }
	  }, {
	    key: "setAddressData",
	    value: function setAddressData(addressData) {
	      this._data.addressData = addressData;
	    }
	  }, {
	    key: "clearAddressData",
	    value: function clearAddressData() {
	      this.setAddressData({});
	    }
	  }, {
	    key: "setSelectedBankDetails",
	    value: function setSelectedBankDetails(bankDetailsId) {
	      if (!main_core.Type.isArray(this._data.bankDetails)) {
	        return;
	      }
	      if (main_core.Type.isNull(bankDetailsId)) {
	        bankDetailsId = 0; // first item by default
	      }

	      for (var index = 0; index < this._data.bankDetails.length; index++) {
	        this._data.bankDetails[index].selected = index === bankDetailsId;
	      }
	      this._data.selectedBankDetailId = bankDetailsId;
	    }
	  }, {
	    key: "clearSelectedBankDetails",
	    value: function clearSelectedBankDetails() {
	      if (!main_core.Type.isArray(this._data.bankDetails)) {
	        return;
	      }
	      for (var index = 0; index < this._data.bankDetails.length; index++) {
	        this._data.bankDetails[index].selected = false;
	      }
	    }
	  }, {
	    key: "exportToModel",
	    value: function exportToModel() {
	      var exportedItem = _objectSpread({}, this._data);
	      delete exportedItem.value;
	      delete exportedItem.addressList;
	      delete exportedItem.bankDetails;
	      delete exportedItem.initialAddressDdta;
	      delete exportedItem.isAddressOnly;
	      return exportedItem;
	    }
	  }], [{
	    key: "create",
	    value: function create(value, settings) {
	      var self = new RequisiteListItem();
	      self.initialize(value, settings);
	      return self;
	    }
	  }]);
	  return RequisiteListItem;
	}();
	RequisiteListItem.newRequisitePattern = /n([0-9]+)/;

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var EntityEditorRequisiteEditor = /*#__PURE__*/function () {
	  function EntityEditorRequisiteEditor() {
	    babelHelpers.classCallCheck(this, EntityEditorRequisiteEditor);
	    this._requisiteList = null;
	    this._entityTypeId = null;
	    this._entityId = null;
	    this._entityCategoryId = null;
	    this._permissionToken = null;
	    this._contextId = null;
	    this._mode = BX.UI.EntityEditorMode.view;
	    this.currentSliderRequisiste = null;
	  }
	  babelHelpers.createClass(EntityEditorRequisiteEditor, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._entityTypeId = BX.prop.getInteger(settings, 'entityTypeId', 0);
	      this._entityId = BX.prop.getInteger(settings, 'entityId', 0);
	      this._entityCategoryId = BX.prop.getInteger(settings, 'entityCategoryId', null);
	      this._contextId = BX.prop.getString(settings, 'contextId', "");
	      this._requisiteEditUrl = BX.prop.getString(settings, 'requisiteEditUrl', "");
	      this._permissionToken = BX.prop.getString(settings, 'permissionToken', null);
	      this._onExternalEventListener = this.onExternalEvent.bind(this);
	      main_core_events.EventEmitter.subscribe('onLocalStorageSet', this._onExternalEventListener);
	    }
	  }, {
	    key: "setRequisiteList",
	    value: function setRequisiteList(requisiteList) {
	      this._requisiteList = requisiteList;
	    }
	  }, {
	    key: "setMode",
	    value: function setMode(mode) {
	      this._mode = mode;
	    }
	  }, {
	    key: "open",
	    value: function open(requisite) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      if (!(requisite instanceof RequisiteListItem)) {
	        return;
	      }
	      this.currentSliderRequisiste = requisite;
	      var sliderOptions = {
	        width: 950,
	        cacheable: false,
	        allowChangeHistory: false,
	        requestMethod: 'post',
	        requestParams: this.prepareSliderRequestParams(requisite, options)
	      };
	      BX.Crm.Page.openSlider(this.getSliderUrl(requisite), sliderOptions);
	    }
	  }, {
	    key: "deleteRequisite",
	    value: function deleteRequisite(id) {
	      var _this = this;
	      var requisite = this._requisiteList.getById(id);
	      if (requisite) {
	        var postData = _objectSpread$1({}, this.prepareSliderRequestParams(requisite));
	        postData.sessid = BX.bitrix_sessid();
	        postData.mode = 'delete';
	        postData.ACTION = 'SAVE';
	        BX.ajax.post(this.getSliderUrl(requisite), postData, function (data) {
	          try {
	            var json = JSON.parse(data);
	            if (main_core.Type.isStringFilled(json.ERROR)) {
	              _this.showError(json.ERROR);
	            } else {
	              var selectedRequisite = _this._requisiteList.getSelected();
	              var selectedRemoved = selectedRequisite === requisite;
	              _this._requisiteList.remove(requisite);
	              main_core_events.EventEmitter.emit(_this, 'onAfterDeleteRequisite', {
	                selectedRemoved: selectedRemoved
	              });
	            }
	          } catch (e) {}
	        });
	      }
	    }
	  }, {
	    key: "showError",
	    value: function showError(errorMessage) {
	      ui_dialogs_messagebox.MessageBox.alert(errorMessage, main_core.Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
	    }
	  }, {
	    key: "getSliderUrl",
	    value: function getSliderUrl(requisite) {
	      var requisiteId = requisite.getRequisiteId();
	      var urlParams = {
	        etype: this._entityTypeId,
	        eid: this._entityId,
	        external_context_id: this._contextId
	      };
	      var presetId = requisite.getPresetId();
	      if (presetId > 0) {
	        urlParams["pid"] = presetId;
	      }
	      if (!main_core.Type.isNull(this._entityCategoryId)) {
	        urlParams.cid = this._entityCategoryId;
	      }
	      return BX.util.add_url_param(this.getRequisiteEditUrl(requisiteId), urlParams);
	    }
	  }, {
	    key: "prepareSliderRequestParams",
	    value: function prepareSliderRequestParams(requisite) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var requestParams = {};
	      var requisiteData = requisite.getRequisiteData();
	      if (requisite.isChanged() && main_core.Type.isString(requisiteData) && requisiteData.length) {
	        requestParams['externalData'] = {
	          'data': requisiteData,
	          'sign': requisite.getRequisiteDataSign()
	        };
	      }
	      if (requisite.isSelected()) {
	        var autocompleteState = requisite.getAutocompleteState();
	        if (Object.keys(autocompleteState).length) {
	          requestParams['AUTOCOMPLETE'] = JSON.stringify(autocompleteState);
	          requestParams['useFormData'] = 'Y';
	        }
	      }
	      if (!requisite.isEmptyFormData()) {
	        requestParams = _objectSpread$1(_objectSpread$1({}, requestParams), requisite.getFormData());
	      }
	      if (!requisite.isEmptyAddressData()) {
	        requestParams = _objectSpread$1(_objectSpread$1({}, requestParams), {
	          RQ_ADDR: requisite.getAddressesForSave()
	        });
	      }
	      requestParams['mode'] = requisite.isNew() ? 'create' : 'edit';
	      if (this.isViewMode()) {
	        requestParams['doSave'] = 'Y';
	      }
	      if (BX.prop.getBoolean(options, 'addBankDetailsItem', false)) {
	        requestParams['addBankDetailsItem'] = 'Y';
	      }
	      var overriddenPresetId = BX.prop.getInteger(options, 'overriddenPresetId', 0);
	      if (overriddenPresetId > 0) {
	        requestParams['PRESET_ID'] = overriddenPresetId;
	        requestParams['useFormData'] = 'Y';
	      }
	      if (!main_core.Type.isNull(this._permissionToken)) {
	        requestParams['permissionToken'] = this._permissionToken;
	      }
	      return requestParams;
	    }
	  }, {
	    key: "getRequisiteEditUrl",
	    value: function getRequisiteEditUrl(requisiteId) {
	      return this._requisiteEditUrl.replace(/#requisite_id#/gi, requisiteId);
	    }
	  }, {
	    key: "getSignRequisitePromise",
	    value: function getSignRequisitePromise(requisite) {
	      var postData = this.prepareSliderRequestParams(requisite);
	      postData.sessid = BX.bitrix_sessid();
	      postData.PRESET_ID = requisite.getPresetId();
	      postData.useFormData = 'Y';
	      postData.ACTION = 'SAVE';
	      return BX.ajax.promise({
	        method: 'post',
	        dataType: 'json',
	        url: this.getSliderUrl(requisite),
	        data: postData
	      });
	    }
	  }, {
	    key: "isViewMode",
	    value: function isViewMode() {
	      return this._mode === BX.UI.EntityEditorMode.view;
	    }
	  }, {
	    key: "release",
	    value: function release() {
	      main_core_events.EventEmitter.unsubscribe('onLocalStorageSet', this._onExternalEventListener);
	    }
	  }, {
	    key: "onExternalEvent",
	    value: function onExternalEvent(event) {
	      var dataArray = event.getData();
	      if (!main_core.Type.isArray(dataArray)) {
	        return;
	      }
	      var data = dataArray[0];
	      var eventName = BX.prop.getString(data, "key", "");
	      if (eventName !== "BX.Crm.RequisiteSliderDetails:onCancelEdit" && eventName !== "BX.Crm.RequisiteSliderDetails:onSave") {
	        return;
	      }
	      var value = BX.prop.getObject(data, "value", {});
	      var contextId = BX.prop.getString(value, "contextId", "");
	      if (contextId !== this._contextId) {
	        return;
	      }
	      if (eventName === "BX.Crm.RequisiteSliderDetails:onCancelEdit") {
	        this.currentSliderRequisiste = null;
	      }
	      if (eventName === "BX.Crm.RequisiteSliderDetails:onSave") {
	        var requisite = this.currentSliderRequisiste;
	        if (main_core.Type.isObject(requisite)) {
	          if (main_core.Type.isString(value.requisiteData)) {
	            requisite.setRequisiteData(value.requisiteData, value.requisiteDataSign);
	            requisite.setAutocompleteState({});
	            requisite.clearFormData();
	            requisite.clearAddressData();
	          }
	          if (main_core.Type.isString(value.presetId)) {
	            requisite.setPresetId(value.presetId);
	          }
	          if (main_core.Type.isString(value.presetCountryId)) {
	            requisite.setPresetCountryId(value.presetCountryId);
	          }
	          if (this.isViewMode()) {
	            requisite.setNew(false);
	          } else {
	            requisite.setChanged(true);
	          }
	          requisite.setDeleted(false);
	          if (this._requisiteList.indexOf(requisite) < 0) {
	            this._requisiteList.add(requisite);
	          } else {
	            this._requisiteList.notifyListChanged();
	          }
	          this.currentSliderRequisiste = null;
	          main_core_events.EventEmitter.emit(this, 'onAfterEditRequisite');
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new EntityEditorRequisiteEditor();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorRequisiteEditor;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var EntityEditorRequisiteController = /*#__PURE__*/function (_BX$Crm$EntityEditorC) {
	  babelHelpers.inherits(EntityEditorRequisiteController, _BX$Crm$EntityEditorC);
	  function EntityEditorRequisiteController() {
	    var _this;
	    babelHelpers.classCallCheck(this, EntityEditorRequisiteController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorRequisiteController).call(this));
	    _this._requisiteList = null;
	    _this._requisiteEditor = null;
	    _this._requisiteFieldId = null;
	    _this._requisiteField = null;
	    _this._requisiteInitData = null;
	    _this._addressFieldId = null;
	    _this._addressField = null;
	    _this._isLoading = false;
	    _this._formInputsWrapper = null;
	    _this._enableRequisiteSelection = false;
	    return _this;
	  }
	  babelHelpers.createClass(EntityEditorRequisiteController, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteController.prototype), "doInitialize", this).call(this);
	      this._requisiteFieldId = this.getConfigStringParam("requisiteFieldId", "");
	      this._addressFieldId = this.getConfigStringParam("addressFieldId", "");
	      this.saveRequisiteInitData();
	      main_core_events.EventEmitter.subscribe(this._editor, 'onFieldInit', this.onFieldInit.bind(this));
	      this.initRequisiteEditor();
	      this.initRequisiteList();
	      var selectedItem = BX.prop.getObject(this.getConfig(), "requisiteBinding", {});
	      if (!main_core.Type.isUndefined(selectedItem.REQUISITE_ID) && !main_core.Type.isUndefined(selectedItem.BANK_DETAIL_ID)) {
	        var requisite = this._requisiteList.getByRequisiteId(selectedItem.REQUISITE_ID);
	        if (requisite) {
	          var bankDetail = selectedItem.BANK_DETAIL_ID > 0 ? requisite.getBankDetailByBankDetailId(selectedItem.BANK_DETAIL_ID) : null;
	          this._requisiteList.setSelected(this._requisiteList.indexOf(requisite), bankDetail ? requisite.getBankDetails().indexOf(bankDetail) : null);
	        }
	      }
	      this._enableRequisiteSelection = BX.prop.getString(this._config, 'enableRequisiteSelection', false);
	    }
	  }, {
	    key: "initRequisiteList",
	    value: function initRequisiteList() {
	      this._requisiteList = RequisiteList.create(this._requisiteInitData);
	      this._requisiteList.subscribe(this._requisiteList.CHANGE_EVENT, this.onChangeRequisites.bind(this));
	      this._requisiteEditor.setRequisiteList(this._requisiteList);
	    }
	  }, {
	    key: "initRequisiteEditor",
	    value: function initRequisiteEditor() {
	      this._requisiteEditor = EntityEditorRequisiteEditor.create(this._id + '_rq_editor', {
	        entityTypeId: this._editor.getEntityTypeId(),
	        entityId: this._editor.getEntityId(),
	        contextId: this._editor.getContextId(),
	        requisiteEditUrl: this._editor.getRequisiteEditUrl('#requisite_id#'),
	        permissionToken: this.getConfigStringParam('permissionToken', null),
	        entityCategoryId: BX.prop.getString(this.getConfig(), 'entityCategoryId', 0)
	      });
	      main_core_events.EventEmitter.subscribe(this._requisiteEditor, 'onAfterEditRequisite', this.onRequisiteEditorAfterEdit.bind(this));
	      main_core_events.EventEmitter.subscribe(this._requisiteEditor, 'onAfterDeleteRequisite', this.onRequisiteEditorAfterDelete.bind(this));
	    }
	  }, {
	    key: "initRequisiteField",
	    value: function initRequisiteField() {
	      if (this._requisiteField) {
	        this._requisiteField.setRequisites(this._requisiteList);
	        this._requisiteField.setSelectModeEnabled(this._enableRequisiteSelection);
	      }
	    }
	  }, {
	    key: "initAddressField",
	    value: function initAddressField() {
	      if (this._addressField) {
	        var countryId = 0;
	        var addressList = {};
	        var selectedRequisite = this._requisiteList ? this._requisiteList.getSelected() : null;
	        if (selectedRequisite) {
	          countryId = selectedRequisite.getPresetCountryId();
	          var requisiteAddressList = selectedRequisite.getAddressList();
	          for (var type in requisiteAddressList) {
	            if (requisiteAddressList.hasOwnProperty(type) && BX.prop.getString(requisiteAddressList[type], 'DELETED', 'N') !== 'Y') {
	              addressList[type] = requisiteAddressList[type];
	            }
	          }
	        }
	        this._addressField.setCountryId(countryId);
	        this._addressField.setAddressList(addressList);
	      }
	    }
	  }, {
	    key: "setSelectModeEnabled",
	    value: function setSelectModeEnabled(enableRequisiteSelection) {
	      if (this._enableRequisiteSelection !== enableRequisiteSelection) {
	        this._enableRequisiteSelection = enableRequisiteSelection;
	        if (this._requisiteField) {
	          this._requisiteField.setSelectModeEnabled(this._enableRequisiteSelection);
	        }
	      }
	    }
	  }, {
	    key: "saveRequisiteInitData",
	    value: function saveRequisiteInitData() {
	      this._requisiteInitData = this.getRequisiteFieldValue();
	    }
	  }, {
	    key: "validate",
	    value: function validate(result) {
	      var _this2 = this;
	      var promises = [];
	      var _iterator = _createForOfIteratorHelper$1(this._requisiteList.getList()),
	        _step;
	      try {
	        var _loop = function _loop() {
	          var requisite = _step.value;
	          if (!requisite.isChanged()) {
	            return "continue";
	          }
	          if (requisite.isEmptyFormData() && requisite.isEmptyAddressData()) {
	            return "continue";
	          }
	          var signPromise = _this2.signRequisiteFields(requisite);
	          signPromise.then(function (data) {
	            var error = BX.prop.getString(data, 'ERROR', '');
	            var entityDataObj = BX.prop.getObject(data, 'ENTITY_DATA', {});
	            var entityData = BX.prop.getString(entityDataObj, 'REQUISITE_DATA', "");
	            var entityDataSign = BX.prop.getString(entityDataObj, 'REQUISITE_DATA_SIGN', "");
	            if (main_core.Type.isStringFilled(error)) {
	              result.addError(BX.Crm.EntityValidationError.create({
	                field: _this2.getFirstEditModeField()
	              }));
	              _this2.showError(error);
	            } else if (main_core.Type.isStringFilled(entityData) && main_core.Type.isStringFilled(entityDataSign)) {
	              requisite.setRequisiteData(entityData, entityDataSign);
	              requisite.clearFormData();
	              requisite.clearAddressData();
	            } else {
	              result.addError(BX.Crm.EntityValidationError.create({
	                field: _this2.getFirstEditModeField()
	              }));
	              _this2.showError(main_core.Loc.getMessage('CRM_EDITOR_SAVE_ERROR_CONTENT'));
	            }
	            return true;
	          }, function () {
	            result.addError(BX.Crm.EntityValidationError.create({
	              field: _this2.getFirstEditModeField()
	            }));
	            _this2.showError(main_core.Loc.getMessage('CRM_EDITOR_SAVE_ERROR_CONTENT'));
	            return new Promise(function (resolve, reject) {
	              resolve();
	            });
	          });
	          promises.push(signPromise);
	        };
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _ret = _loop();
	          if (_ret === "continue") continue;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return promises.length > 0 ? Promise.all(promises) : null;
	    }
	  }, {
	    key: "setSelectedRequisite",
	    value: function setSelectedRequisite(requisiteId, bankDetailId) {
	      var _this3 = this;
	      var entityId = this._editor.getEntityId();
	      if (!entityId) {
	        // impossible situation, but...
	        return;
	      }
	      var newSelectedRequisite = this._requisiteList.getById(requisiteId);
	      if (!newSelectedRequisite || newSelectedRequisite.isNew()) {
	        // impossible situation too
	        return;
	      }
	      this._requisiteList.setSelected(requisiteId, bankDetailId);
	      var selectedBankDetail = newSelectedRequisite.getBankDetailById(newSelectedRequisite.getSelectedBankDetailId());
	      var selectedBankDetailId = main_core.Type.isNull(selectedBankDetail) ? null : selectedBankDetail.id;
	      this.startLoading();
	      BX.ajax.runAction('crm.requisite.settings.setSelectedEntityRequisite', {
	        data: {
	          entityTypeId: this._editor.getEntityTypeId(),
	          entityId: entityId,
	          requisiteId: newSelectedRequisite.getRequisiteId(),
	          bankDetailId: selectedBankDetailId
	        }
	      }).then(function () {
	        _this3.stopLoading();
	      }, function () {
	        _this3.stopLoading();
	      });
	    }
	  }, {
	    key: "openEditor",
	    value: function openEditor(requisite) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      this.setRequisiteInitAddrData(requisite);
	      this._requisiteEditor.setMode(this._editor.getMode());
	      this._requisiteEditor.open(requisite, options);
	    }
	  }, {
	    key: "isViewMode",
	    value: function isViewMode() {
	      return this._editor.getMode() === BX.Crm.EntityEditorMode.view;
	    }
	  }, {
	    key: "setRequisiteInitAddrData",
	    value: function setRequisiteInitAddrData(requisite) {
	      var requisiteId = requisite.getRequisiteId();
	      var rawRequisite = this._requisiteInitData.filter(function (item) {
	        return item.requisiteId === requisiteId;
	      }).reduce(function (prev, current) {
	        return current;
	      }, null);
	      if (main_core.Type.isPlainObject(rawRequisite)) {
	        try {
	          var requisiteData = JSON.parse(rawRequisite.requisiteData);
	          var requisiteFields = BX.prop.getObject(requisiteData, 'fields', {});
	          var addressData = BX.prop.getObject(requisiteFields, 'RQ_ADDR', null);
	          requisite.setInitialAddressData(addressData);
	        } catch (e) {
	          requisite.setInitAddrData(null);
	        }
	      }
	    }
	  }, {
	    key: "getFirstEditModeField",
	    value: function getFirstEditModeField() {
	      if (this._requisiteField && this._requisiteField._mode === BX.Crm.EntityEditorMode.edit) {
	        return this._requisiteField;
	      }
	      if (this._addressField && this._addressField._mode === BX.Crm.EntityEditorMode.edit) {
	        return this._addressField;
	      }
	      return null;
	    }
	  }, {
	    key: "updateRequisiteFieldModel",
	    value: function updateRequisiteFieldModel() {
	      var modelValue = this._requisiteList.exportToModel();
	      if (this._requisiteField) {
	        this._model.setField(this._requisiteFieldId, modelValue);
	      }
	      this.saveRequisiteInitData(babelHelpers.toConsumableArray(modelValue));
	    }
	  }, {
	    key: "getRequisiteFieldValue",
	    value: function getRequisiteFieldValue() {
	      if (!this._requisiteFieldId) {
	        return [];
	      }
	      return this._model.getField(this._requisiteFieldId, []);
	    }
	  }, {
	    key: "addEditorFormInputs",
	    value: function addEditorFormInputs() {
	      this._formInputsWrapper = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	      main_core.Dom.append(this._formInputsWrapper, this._editor.getFormElement());
	      var _iterator2 = _createForOfIteratorHelper$1(this._requisiteList.getListWithDeleted()),
	        _step2;
	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var requisite = _step2.value;
	          if (requisite.isDeleted()) {
	            main_core.Dom.append(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"REQUISITES[", "][DELETED]\" value=\"Y\" >"])), requisite.getRequisiteId()), this._formInputsWrapper);
	            continue;
	          }
	          if (!requisite.isChanged()) {
	            continue;
	          }
	          var dataInput = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"REQUISITES[", "][DATA]\" value=\"", "\" >"])), requisite.getRequisiteId(), main_core.Tag.safe(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["", ""])), requisite.getRequisiteData()));
	          var signInput = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"REQUISITES[", "][SIGN]\" value=\"", "\" >"])), requisite.getRequisiteId(), requisite.getRequisiteDataSign());
	          main_core.Dom.append(dataInput, this._formInputsWrapper);
	          main_core.Dom.append(signInput, this._formInputsWrapper);
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	    }
	  }, {
	    key: "removeEditorFormInputs",
	    value: function removeEditorFormInputs() {
	      if (main_core.Type.isDomNode(this._formInputsWrapper)) {
	        main_core.Dom.remove(this._formInputsWrapper);
	        this._formInputsWrapper = null;
	      }
	    }
	  }, {
	    key: "markFieldsAsChanged",
	    value: function markFieldsAsChanged() {
	      if (this._requisiteField) {
	        this._requisiteField.markAsChanged();
	      }
	      if (this._addressField) {
	        this._addressField.markAsChanged();
	      }
	    }
	  }, {
	    key: "rollback",
	    value: function rollback() {
	      this.initRequisiteList();
	      this.initRequisiteField();
	      this.initAddressField();
	      this.updateRequisiteFieldModel();
	    }
	  }, {
	    key: "isLoading",
	    value: function isLoading() {
	      return !!this._isLoading;
	    }
	  }, {
	    key: "startLoading",
	    value: function startLoading() {
	      this._isLoading = true;
	    }
	  }, {
	    key: "stopLoading",
	    value: function stopLoading() {
	      this._isLoading = false;
	    }
	  }, {
	    key: "showError",
	    value: function showError(errorMessage) {
	      ui_dialogs_messagebox.MessageBox.alert(errorMessage, main_core.Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
	    }
	  }, {
	    key: "prepareRequisiteByEventData",
	    value: function prepareRequisiteByEventData(eventData) {
	      var requisite = this._requisiteList.getById(eventData.id);
	      if (!requisite) {
	        var extraFields = {
	          selected: this._requisiteList.isEmpty(),
	          presetId: eventData.defaultPresetId
	        };
	        if (main_core.Type.isPlainObject(eventData.data)) {
	          if (eventData.data.title) {
	            extraFields.title = eventData.data.title;
	          }
	          if (eventData.data.subtitle) {
	            extraFields.subtitle = eventData.data.subtitle;
	          }
	        }
	        if (eventData.hasOwnProperty('autocompleteState')) {
	          extraFields.autocompleteState = eventData.autocompleteState;
	        }
	        requisite = RequisiteListItem.create(null, {
	          'newRequisiteId': this._requisiteList.getNewRequisiteId(),
	          'newRequisiteExtraFields': extraFields
	        });
	      } else {
	        if (eventData.hasOwnProperty('autocompleteState')) {
	          requisite.setAutocompleteState(eventData.autocompleteState);
	        }
	        if (main_core.Type.isPlainObject(eventData.data)) {
	          if (eventData.data.title) {
	            requisite._data.title = eventData.data.title;
	          }
	          if (eventData.data.subtitle) {
	            requisite._data.subtitle = eventData.data.subtitle;
	          }
	        }
	      }
	      return requisite;
	    }
	  }, {
	    key: "getDefaultPresetId",
	    value: function getDefaultPresetId() {
	      var _this4 = this;
	      if (this._requisiteField) {
	        return this._requisiteField.getDefaultPresetId();
	      } else
	        // if requisiteField is hidden
	        {
	          var schemeElement = this._editor.getScheme().getAvailableElements().filter(function (item) {
	            return item.getName() === _this4._requisiteFieldId;
	          }).reduce(function (prev, current) {
	            return current;
	          }, null);
	          if (!schemeElement) {
	            return null;
	          }
	          var _iterator3 = _createForOfIteratorHelper$1(BX.prop.getArray(schemeElement.getData(), "presets", [])),
	            _step3;
	          try {
	            for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	              var preset = _step3.value;
	              if (preset.IS_DEFAULT) {
	                return preset.VALUE;
	              }
	            }
	          } catch (err) {
	            _iterator3.e(err);
	          } finally {
	            _iterator3.f();
	          }
	        }
	      return null;
	    }
	  }, {
	    key: "signRequisiteFields",
	    value: function signRequisiteFields(requisite) {
	      this.setRequisiteInitAddrData(requisite);
	      this._requisiteEditor.setMode(this._editor.getMode());
	      return this._requisiteEditor.getSignRequisitePromise(requisite);
	    }
	  }, {
	    key: "release",
	    value: function release() {
	      if (this._requisiteEditor) {
	        this._requisiteEditor.release();
	      }
	    }
	  }, {
	    key: "onFieldInit",
	    value: function onFieldInit(event) {
	      var eventData = event.getData();
	      var field = eventData.field;
	      if (field) {
	        var fieldId = field.getId();
	        if (main_core.Type.isStringFilled(this._requisiteFieldId) && fieldId === this._requisiteFieldId) {
	          this._requisiteField = field;
	          this.initRequisiteField();
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onEditNew', this.onEditNewRequisite.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onEditExisted', this.onEditExistedRequisite.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onFinishAutocomplete', this.onFinishRequisiteAutocomplete.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onClearAutocomplete', this.onClearRequisiteAutocomplete.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onSetDefault', this.onSetDefaultRequisite.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onDelete', this.onDeleteRequisite.bind(this));
	          main_core_events.EventEmitter.subscribe(this._requisiteField, 'onHide', this.onHideRequisite.bind(this));
	        }
	        if (main_core.Type.isStringFilled(this._addressFieldId) && fieldId === this._addressFieldId) {
	          this._addressField = field;
	          this.initAddressField();
	          main_core_events.EventEmitter.subscribe(this._addressField, 'onAddressListUpdate', this.onAddressListUpdate.bind(this));
	        }
	      }
	    }
	  }, {
	    key: "onEditNewRequisite",
	    value: function onEditNewRequisite(event) {
	      var params = event.getData();
	      params.selected = this._requisiteList.isEmpty();
	      var requisite = RequisiteListItem.create(null, {
	        'newRequisiteId': this._requisiteList.getNewRequisiteId(),
	        'newRequisiteExtraFields': params
	      });
	      requisite.setRequisiteId(this._requisiteList.getNewRequisiteId());
	      this.openEditor(requisite);
	    }
	  }, {
	    key: "onEditExistedRequisite",
	    value: function onEditExistedRequisite(event) {
	      var params = event.getData();
	      var requisite = this._requisiteList.getById(params.id);
	      if (requisite) {
	        var options = BX.prop.getObject(params, 'options', {});
	        if (main_core.Type.isPlainObject(options.autocompleteState)) {
	          requisite.setAutocompleteState(options.autocompleteState);
	        }
	        var editorOptions = {};
	        if (main_core.Type.isPlainObject(options.editorOptions)) {
	          editorOptions = options.editorOptions;
	        }
	        this.openEditor(requisite, editorOptions);
	      }
	    }
	  }, {
	    key: "onClearRequisiteAutocomplete",
	    value: function onClearRequisiteAutocomplete(event) {
	      var params = event.getData();
	      var requisite = this._requisiteList.getById(params.id);
	      if (requisite) {
	        requisite.clearFormData();
	      }
	    }
	  }, {
	    key: "onFinishRequisiteAutocomplete",
	    value: function onFinishRequisiteAutocomplete(event) {
	      var eventData = event.getData();
	      var requisite = this.prepareRequisiteByEventData(eventData);
	      var formData = BX.prop.getObject(BX.prop.getObject(eventData, 'data', {}), 'fields', {});
	      if (formData.hasOwnProperty('RQ_ADDR')) {
	        if (main_core.Type.isPlainObject(formData.RQ_ADDR)) {
	          var oldAddr = requisite.getAddressList();
	          oldAddr = main_core.Type.isPlainObject(oldAddr) ? oldAddr : {};
	          var addr = _objectSpread$2(_objectSpread$2({}, oldAddr), formData.RQ_ADDR);
	          requisite.setAddressData(addr);
	          requisite.setAddressList(addr);
	        }
	        delete formData.RQ_ADDR;
	      }
	      requisite.setFormData(formData);
	      requisite.setChanged(true);
	      requisite.setDeleted(false);
	      var presetId = BX.prop.getInteger(formData, 'PRESET_ID', 0);
	      if (presetId > 0) {
	        requisite.setPresetId(presetId);
	      }
	      var presetCountryId = BX.prop.getInteger(formData, 'PRESET_COUNTRY_ID', 0);
	      if (presetCountryId > 0) {
	        requisite.setPresetCountryId(presetCountryId);
	      }
	      if (this._requisiteList.indexOf(requisite) < 0) {
	        this._requisiteList.add(requisite);
	      } else {
	        this._requisiteList.notifyListChanged();
	      }
	      if (requisite.isAddressOnly()) {
	        this._requisiteList.unhide(requisite);
	      }
	    }
	  }, {
	    key: "onSetDefaultRequisite",
	    value: function onSetDefaultRequisite(event) {
	      var eventData = event.getData();
	      var id = eventData.id;
	      var bankDetailId = eventData.bankDetailId;
	      if (this._addressField && this._addressField.isChanged()) {
	        this._editor.cancel();
	        return false; // have to save address first
	      }

	      this.setSelectedRequisite(id, bankDetailId);
	      this.updateRequisiteFieldModel();
	      return true;
	    }
	  }, {
	    key: "onDeleteRequisite",
	    value: function onDeleteRequisite(event) {
	      var params = event.getData();
	      var requisite = this._requisiteList.getById(params.id);
	      if (requisite) {
	        this.setRequisiteInitAddrData(requisite);
	      }
	      if (params.postponed) {
	        this._requisiteList.removePostponed(requisite);
	      } else {
	        this._requisiteEditor.setMode(this._editor.getMode());
	        this._requisiteEditor.deleteRequisite(params.id);
	      }
	    }
	  }, {
	    key: "onHideRequisite",
	    value: function onHideRequisite(event) {
	      var params = event.getData();
	      var requisite = this._requisiteList.getById(params.id);
	      if (requisite) {
	        this._requisiteList.hide(requisite);
	      }
	    }
	  }, {
	    key: "onChangeRequisites",
	    value: function onChangeRequisites() {
	      this.initAddressField();
	    }
	  }, {
	    key: "onAddressListUpdate",
	    value: function onAddressListUpdate(event) {
	      var eventData = event.getData();
	      eventData.id = this._requisiteList.getSelectedId();
	      eventData.defaultPresetId = this.getDefaultPresetId();
	      var requisite = this.prepareRequisiteByEventData(eventData);
	      var addresses = {};
	      var isEmptyAddress = true;
	      var _iterator4 = _createForOfIteratorHelper$1(eventData.value),
	        _step4;
	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var address = _step4.value;
	          addresses[address.type] = address.value;
	          if (main_core.Type.isStringFilled(address.value)) {
	            isEmptyAddress = false;
	          }
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }
	      requisite.setAddressData(addresses);
	      requisite.setAddressList(addresses);
	      requisite.setChanged(true);
	      if (!isEmptyAddress) {
	        requisite.setDeleted(false);
	      }
	      if (this._requisiteList.indexOf(requisite) < 0) {
	        if (!isEmptyAddress) {
	          requisite.setAddressOnly(true);
	          this._requisiteList.add(requisite);
	        }
	      } else {
	        //  remove requisite if address is empty and requisite contain only address
	        if (isEmptyAddress && requisite.isAddressOnly()) {
	          this._requisiteList.removePostponed(requisite);
	        }
	        this._requisiteList.notifyListChanged();
	      }
	    }
	  }, {
	    key: "onBeforeSubmit",
	    value: function onBeforeSubmit() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteController.prototype), "onBeforeSubmit", this).call(this);
	      this.addEditorFormInputs();
	    }
	  }, {
	    key: "onBeforesSaveControl",
	    value: function onBeforesSaveControl(data) {
	      if (!data.hasOwnProperty('REQUISITES')) {
	        data['REQUISITES'] = {};
	      }
	      var _iterator5 = _createForOfIteratorHelper$1(this._requisiteList.getListWithDeleted()),
	        _step5;
	      try {
	        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	          var requisite = _step5.value;
	          if (!requisite.isChanged() && !requisite.isDeleted()) {
	            continue;
	          }
	          var requisiteData = {};
	          if (requisite.isDeleted()) {
	            requisiteData['DELETED'] = 'Y';
	          } else {
	            requisiteData['DATA'] = requisite.getRequisiteData();
	            requisiteData['SIGN'] = requisite.getRequisiteDataSign();
	          }
	          data['REQUISITES'][requisite.getRequisiteId()] = requisiteData;
	        }
	      } catch (err) {
	        _iterator5.e(err);
	      } finally {
	        _iterator5.f();
	      }
	      if (this._enableRequisiteSelection) {
	        var selectedRequisite = this._requisiteList.getSelected();
	        var selectedRequisiteId = null;
	        var selectedBankDetailId = null;
	        if (selectedRequisite) {
	          selectedRequisiteId = selectedRequisite.getRequisiteId();
	          var selectedBankDetail = selectedRequisite.getBankDetailById(selectedRequisite.getSelectedBankDetailId());
	          selectedBankDetailId = main_core.Type.isNull(selectedBankDetail) ? null : selectedBankDetail.id;
	        }
	        data['REQUISITES']['BINDING'] = {
	          requisiteId: selectedRequisiteId,
	          bankDetailId: selectedBankDetailId
	        };
	      }
	      return data;
	    }
	  }, {
	    key: "onAfterSave",
	    value: function onAfterSave() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteController.prototype), "onAfterSave", this).call(this);
	      this.saveRequisiteInitData(this.getRequisiteFieldValue());
	      this.initRequisiteList();
	      this.initRequisiteField();
	      this.initAddressField();
	      this.removeEditorFormInputs();
	    }
	  }, {
	    key: "onRequisiteEditorAfterEdit",
	    value: function onRequisiteEditorAfterEdit() {
	      if (this.isViewMode()) {
	        this.updateRequisiteFieldModel();
	      }
	      this.markFieldsAsChanged();
	    }
	  }, {
	    key: "onRequisiteEditorAfterDelete",
	    value: function onRequisiteEditorAfterDelete(event) {
	      var data = event.getData();
	      var isEmptyRequisitesList = this._requisiteList.isEmpty();
	      if (!isEmptyRequisitesList && data.selectedRemoved) {
	        // set new default requisite
	        this.setSelectedRequisite(0, 0);
	      }
	      this.updateRequisiteFieldModel();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new EntityEditorRequisiteController();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorRequisiteController;
	}(BX.Crm.EntityEditorController);

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }
	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var PresetMenu = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PresetMenu, _EventEmitter);
	  function PresetMenu(id, presetList) {
	    var _this;
	    babelHelpers.classCallCheck(this, PresetMenu);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PresetMenu).call(this));
	    _this.setEventNamespace('BX.Crm.RequisitePresetMenu');
	    _this._isShown = false;
	    _this.menuId = id;
	    _this.presetList = presetList;
	    return _this;
	  }
	  babelHelpers.createClass(PresetMenu, [{
	    key: "toggle",
	    value: function toggle(bindElement) {
	      if (this._isShown) {
	        this.close();
	      } else if (bindElement) {
	        this.show(bindElement);
	      }
	    }
	  }, {
	    key: "show",
	    value: function show(bindElement) {
	      var _this2 = this;
	      if (this._isShown) {
	        return;
	      }
	      if (!this.presetList || !this.presetList.length) {
	        ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('REQUISITE_LIST_EMPTY_PRESET_LIST'), main_core.Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
	        return;
	      }
	      var menu = [];
	      var _iterator = _createForOfIteratorHelper$2(this.presetList),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;
	          menu.push({
	            text: main_core.Text.encode(item.name),
	            value: item.value,
	            onclick: this.onSelect.bind(this, item)
	          });
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      main_popup.MenuManager.show(this.menuId, bindElement, menu, {
	        angle: false,
	        cacheable: false,
	        events: {
	          onPopupShow: function onPopupShow() {
	            _this2._isShown = true;
	          },
	          onPopupClose: function onPopupClose() {
	            _this2._isShown = false;
	          }
	        }
	      });
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (!this._isShown) {
	        return;
	      }
	      var menu = main_popup.MenuManager.getMenuById(this.menuId);
	      if (menu) {
	        menu.popupWindow.close();
	      }
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this._isShown;
	    }
	  }, {
	    key: "onSelect",
	    value: function onSelect(item) {
	      this.close();
	      this.emit('onSelect', item);
	    }
	  }]);
	  return PresetMenu;
	}(main_core_events.EventEmitter);

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	var EntityEditorRequisiteTooltip = /*#__PURE__*/function () {
	  function EntityEditorRequisiteTooltip() {
	    babelHelpers.classCallCheck(this, EntityEditorRequisiteTooltip);
	  }
	  babelHelpers.createClass(EntityEditorRequisiteTooltip, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._padding = BX.prop.getInteger(this._settings, 'padding', 20);
	      this._showTimer = false;
	      this._closeTimer = false;
	      this._closeTimeout = BX.prop.getInteger(this._settings, 'closeTimeout', 700);
	      this._showTimeout = BX.prop.getInteger(this._settings, 'showTimeout', 500);
	      this._popupId = this._id + '_popup';
	      this._isReadonly = BX.prop.getBoolean(this._settings, 'readonly', true);
	      this._canChangeDefaultRequisite = BX.prop.getBoolean(this._settings, 'canChangeDefaultRequisite', true);
	      this._bindElement = null;
	      this._wrapper = null;
	      this._requisiteList = null;
	      this._isLoading = false;
	      this._changeRequisistesHandler = this.onChangeRequisites.bind(this);
	      this.presetMenu = new PresetMenu(this._id + '_preset_menu', BX.prop.getArray(this._settings, 'presets', []));
	      this.presetMenu.subscribe('onSelect', this.onAddRequisite.bind(this));
	    }
	  }, {
	    key: "setRequisites",
	    value: function setRequisites(requisiteList) {
	      this._requisiteList = requisiteList;
	      requisiteList.unsubscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
	      requisiteList.subscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
	      this.refreshLayout();
	    }
	  }, {
	    key: "setBindElement",
	    value: function setBindElement(bindElement, wrapper) {
	      this._bindElement = bindElement;
	      if (!main_core.Type.isDomNode(wrapper)) {
	        wrapper = this._bindElement.parentNode ? this._bindElement.parentNode : null;
	      }
	      this._wrapper = wrapper;
	    }
	  }, {
	    key: "setLoading",
	    value: function setLoading(isLoading) {
	      this._isLoading = !!isLoading;
	      this.refreshLayout();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.cancelCloseDebounced();
	      var success = false;
	      if (this._requisiteList && !this._requisiteList.isEmpty()) {
	        var popup = main_popup.PopupManager.create({
	          id: this._popupId,
	          cacheable: false,
	          autoHide: true,
	          padding: this._padding,
	          contentPadding: 0,
	          content: this.getLayout(),
	          closeByEsc: true
	        });
	        popup.show();
	        this.adjustPosition();
	        success = true;
	      }
	      main_core_events.EventEmitter.emit(this, 'onShow', {
	        success: success
	      });
	    }
	  }, {
	    key: "showDebounced",
	    value: function showDebounced() {
	      var delayMultiplier = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      delayMultiplier = parseInt(delayMultiplier) > 0 ? parseInt(delayMultiplier) : 1;
	      this.cancelShowDebounced();
	      this.cancelCloseDebounced();
	      this._showTimer = setTimeout(this.show.bind(this), this._showTimeout * delayMultiplier);
	    }
	  }, {
	    key: "cancelShowDebounced",
	    value: function cancelShowDebounced() {
	      clearTimeout(this._showTimer);
	    }
	  }, {
	    key: "closeDebounced",
	    value: function closeDebounced() {
	      var delayMultiplier = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.cancelShowDebounced();
	      delayMultiplier = parseInt(delayMultiplier) > 0 ? parseInt(delayMultiplier) : 1;
	      this.cancelCloseDebounced();
	      this._closeTimer = setTimeout(this.close.bind(this), this._closeTimeout * delayMultiplier);
	    }
	  }, {
	    key: "cancelCloseDebounced",
	    value: function cancelCloseDebounced() {
	      clearTimeout(this._closeTimer);
	    }
	  }, {
	    key: "adjustPosition",
	    value: function adjustPosition() {
	      var popup = main_popup.PopupManager.getPopupById(this._popupId);
	      if (!popup || !popup.isShown() || !main_core.Type.isDomNode(this._bindElement) || !main_core.Type.isDomNode(this._wrapper)) {
	        return;
	      }
	      var wrapperRect = this._wrapper.getBoundingClientRect();
	      var itemRect = this._bindElement.getBoundingClientRect();
	      var offsetLeft = wrapperRect.width - (itemRect.left - wrapperRect.left);
	      var offsetTop = itemRect.height / 2 + this._padding;
	      var angleOffset = itemRect.height / 2;
	      var popupWidth = popup.getPopupContainer().offsetWidth;
	      var popupHeight = popup.getPopupContainer().offsetHeight;
	      var popupBottom = itemRect.top + popupHeight;
	      var clientWidth = document.documentElement.clientWidth;
	      var clientHeight = document.documentElement.clientHeight;

	      // let's try to fit a popup to the browser viewport
	      var exceeded = popupBottom - clientHeight;
	      if (exceeded > 0) {
	        var roundOffset = Math.ceil(exceeded / itemRect.height) * itemRect.height;
	        if (roundOffset > itemRect.top) {
	          // it cannot be higher than the browser viewport.
	          roundOffset -= Math.ceil((roundOffset - itemRect.top) / itemRect.height) * itemRect.height;
	        }
	        if (itemRect.bottom > popupBottom - roundOffset) {
	          // let's sync bottom boundaries.
	          roundOffset -= itemRect.bottom - (popupBottom - roundOffset) + this._padding;
	        }
	        offsetTop += roundOffset;
	        angleOffset += roundOffset + this._padding;
	      }
	      popup.setBindElement(this._bindElement);
	      if (wrapperRect.left + offsetLeft + popupWidth <= clientWidth) {
	        popup.setAngle({
	          position: 'left',
	          offset: angleOffset
	        });
	        offsetLeft += popup.angle ? popup.angle.element.offsetWidth : 0;
	        offsetTop += popup.angle ? Math.ceil(popup.angle.element.offsetHeight / 2) : 0;
	        popup.setOffset({
	          offsetLeft: offsetLeft,
	          offsetTop: -offsetTop
	        });
	      } else {
	        popup.setAngle(true);
	      }
	      popup.adjustPosition();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      var popup = main_popup.PopupManager.getPopupById(this._popupId);
	      popup ? popup.close() : null;
	      this.presetMenu.close();
	    }
	  }, {
	    key: "removeDebouncedEvents",
	    value: function removeDebouncedEvents() {
	      this.cancelCloseDebounced();
	      this.cancelShowDebounced();
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      var popup = main_popup.PopupManager.getPopupById(this._popupId);
	      if (popup) {
	        popup.setContent(this.getLayout());
	      }
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this = this;
	      if (this._isLoading) {
	        var loader = new main_loader.Loader();
	        var container = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-rq-wrapper crm-rq-wrapper-loading\"></div>"])));
	        loader.show(container);
	        return container;
	      }
	      var requisites = main_core.Type.isNull(this._requisiteList) ? [] : this._requisiteList.getList();
	      var renderRequisiteEditButtonNode = function renderRequisiteEditButtonNode(id) {
	        if (_this._isReadonly) {
	          return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-rq-org-requisite-btn-container\">\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary\" onclick=\"", "\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t"])), _this.onEditRequisite.bind(_this, id), main_core.Loc.getMessage('REQUISITE_TOOLTIP_SHOW_DETAILS'));
	        } else {
	          return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-rq-org-requisite-btn-container\">\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary\" onclick=\"", "\">", "</span>\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary\" data-requisite-id=\"", "\" onclick=\"", "\">", "</span>\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary\" onclick=\"", "\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t"])), _this.onEditRequisite.bind(_this, id), main_core.Loc.getMessage('REQUISITE_TOOLTIP_EDIT'), id, _this.onDeleteRequisite.bind(_this), main_core.Loc.getMessage('REQUISITE_TOOLTIP_DELETE'), _this.onAddBankDetails.bind(_this, id), main_core.Loc.getMessage('REQUISITE_TOOLTIP_ADD_BANK_DETAILS'));
	        }
	      };
	      var renderRequisiteBankDetails = function renderRequisiteBankDetails(requisite, id) {
	        var bandDetails = requisite.getBankDetails();
	        var selectedBankDetailId = requisite.getSelectedBankDetailId();
	        return bandDetails.length ? main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-rq-org-requisite-container\">\n\t\t\t\t\t\t<div class=\"crm-rq-org-requisite-title\">", "</div>\n\t\t\t\t\t\t<div class=\"crm-rq-org-requisite-list\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), main_core.Loc.getMessage('REQUISITE_TOOLTIP_BANK_DETAILS_TITLE'), bandDetails.map(function (bankDetail, index) {
	          return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<label class=\"crm-rq-org-requisite-item\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t\t<input type=\"radio\" data-requisite-id=\"", "\" data-bankdetails-id=\"", "\" class=\"crm-rq-org-requisite-btn\" ", " ", ">\n\t\t\t\t\t\t\t\t\t<span class=\"crm-rq-org-requisite-info\">", "</span>\n\t\t\t\t\t\t\t\t</label>"])), _this.onSetSelectedBankDetails.bind(_this), id, index, requisite.isSelected() && index === selectedBankDetailId ? ' checked' : '', _this._canChangeDefaultRequisite ? '' : ' disabled', main_core.Text.encode(bankDetail.value));
	        }), renderRequisiteEditButtonNode(id)) : renderRequisiteEditButtonNode(id);
	      };
	      return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"crm-rq-wrapper\"\n\t\t\tonmouseenter=\"", "\"\n\t\t\tonmouseleave=\"", "\">\n\t\t\t<div class=\"crm-rq-org-list\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t", "\n\t\t</div>"])), this.onMouseEnter.bind(this), this.onMouseLeave.bind(this), requisites.map(function (requisite, index) {
	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-rq-org-item", "\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"crm-rq-org-info-container\">\n\t\t\t\t\t\t\t<div class=\"crm-rq-org-name\">", "</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), requisite.isSelected() ? ' crm-rq-org-item-selected' : '', _this.onSetSelectedRequisite.bind(_this, index), main_core.Text.encode(requisite.getTitle()), requisite.getSubtitle().length ? main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-rq-org-description\">", "</div>"])), main_core.Text.encode(requisite.getSubtitle())) : '', renderRequisiteBankDetails(requisite, index));
	      }), this._isReadonly ? '' : main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-rq-btn-container\">\n\t\t\t\t<span class=\"crm-rq-add-rq\" onclick=\"", "\">\n\t\t\t\t\t<span class=\"ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round\"></span> ", "\n\t\t\t\t</span>\n\t\t\t</div>"])), this.onStartAddRequisite.bind(this), main_core.Loc.getMessage('REQUISITE_TOOLTIP_ADD')));
	    }
	  }, {
	    key: "setSelectedRequisite",
	    value: function setSelectedRequisite(id, bankDetailId) {
	      var newSelectedRequisite = this._requisiteList.getById(id);
	      if (!newSelectedRequisite) {
	        return false;
	      }
	      var selectedRequisite = this._requisiteList.getSelected();
	      if (id === this._requisiteList.getSelectedId())
	        // selected the same requisite
	        {
	          var selectedBankDetails = selectedRequisite.getBankDetails();
	          if (selectedBankDetails.length) {
	            for (var index = 0; index < selectedBankDetails.length; index++) {
	              var bankDetails = selectedBankDetails[index];
	              if (main_core.Type.isObject(bankDetails) && bankDetails.selected) {
	                if (index === bankDetailId || main_core.Type.isNull(bankDetailId)) {
	                  return false; // selected same requisite and bank details
	                }
	              }
	            }
	          } else {
	            return false; // requisite already selected and hasn't bank details
	          }
	        }

	      main_core_events.EventEmitter.emit(this, 'onSetSelectedRequisite', {
	        id: id,
	        bankDetailId: bankDetailId
	      });
	      return true;
	    }
	  }, {
	    key: "onMouseEnter",
	    value: function onMouseEnter() {
	      this.cancelCloseDebounced();
	    }
	  }, {
	    key: "onMouseLeave",
	    value: function onMouseLeave() {
	      if (!this.presetMenu.isShown()) {
	        this.closeDebounced(1.5);
	      }
	    }
	  }, {
	    key: "onStartAddRequisite",
	    value: function onStartAddRequisite(event) {
	      event.stopPropagation();
	      this.presetMenu.toggle(event.target);
	    }
	  }, {
	    key: "onAddRequisite",
	    value: function onAddRequisite(event) {
	      var data = event.getData();
	      this.close();
	      main_core_events.EventEmitter.emit(this, 'onAddRequisite', {
	        presetId: data.value
	      });
	    }
	  }, {
	    key: "onEditRequisite",
	    value: function onEditRequisite(id, event) {
	      event.stopPropagation();
	      this.close();
	      main_core_events.EventEmitter.emit(this, 'onEditRequisite', {
	        id: id
	      });
	    }
	  }, {
	    key: "onDeleteRequisite",
	    value: function onDeleteRequisite(event) {
	      event.stopPropagation();
	      var element = event.target;
	      if (main_core.Type.isDomNode(element)) {
	        var id = element.getAttribute('data-requisite-id');
	        main_core_events.EventEmitter.emit(this, 'onDeleteRequisite', {
	          id: id
	        });
	      }
	    }
	  }, {
	    key: "onAddBankDetails",
	    value: function onAddBankDetails(requisiteId, event) {
	      event.stopPropagation();
	      this.close();
	      main_core_events.EventEmitter.emit(this, 'onAddBankDetails', {
	        requisiteId: requisiteId
	      });
	    }
	  }, {
	    key: "onSetSelectedRequisite",
	    value: function onSetSelectedRequisite(id) {
	      if (this._canChangeDefaultRequisite) {
	        this.setSelectedRequisite(id, null);
	      }
	      return false;
	    }
	  }, {
	    key: "onSetSelectedBankDetails",
	    value: function onSetSelectedBankDetails(event) {
	      event.stopPropagation();
	      var element = event.target;
	      if (this._canChangeDefaultRequisite && main_core.Type.isDomNode(element)) {
	        if (element.nodeName.toString().toLowerCase() !== 'input') {
	          element = element.querySelector('input');
	          if (!main_core.Type.isDomNode(element)) {
	            return;
	          }
	        }
	        element.checked = false;
	        var id = parseInt(element.getAttribute('data-requisite-id'));
	        var bankDetailsId = parseInt(element.getAttribute('data-bankdetails-id'));
	        this.setSelectedRequisite(id, bankDetailsId);
	      }
	      return false;
	    }
	  }, {
	    key: "onChangeRequisites",
	    value: function onChangeRequisites(event) {
	      this.refreshLayout();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new EntityEditorRequisiteTooltip();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorRequisiteTooltip;
	}();

	var _templateObject$2, _templateObject2$2, _templateObject3$2, _templateObject4$2, _templateObject5$2, _templateObject6$1, _templateObject7$1, _templateObject8$1, _templateObject9$1, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14;
	function _createForOfIteratorHelper$3(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$3(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$3(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$3(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$3(o, minLen); }
	function _arrayLikeToArray$3(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var EntityEditorRequisiteField = /*#__PURE__*/function (_BX$Crm$EntityEditorF) {
	  babelHelpers.inherits(EntityEditorRequisiteField, _BX$Crm$EntityEditorF);
	  function EntityEditorRequisiteField() {
	    var _this;
	    babelHelpers.classCallCheck(this, EntityEditorRequisiteField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorRequisiteField).call(this));
	    _this._domNodes = {};
	    _this._requisiteList = null;
	    _this.presetMenu = null;
	    _this._autocomplete = null;
	    _this._tooltip = null;
	    _this.isSearchMode = true;
	    _this.requisitesDropdown = null;
	    _this.bankDetailsDropdown = null;
	    _this.selectModeEnabled = false;
	    _this._changeRequisistesHandler = _this.onChangeRequisites.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(EntityEditorRequisiteField, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      var _this2 = this;
	      this._autocomplete = crm_entityEditor_field_requisite_autocomplete.RequisiteAutocompleteField.create(this.getName(), {
	        searchAction: 'crm.requisite.entity.search',
	        canAddRequisite: true,
	        feedbackFormParams: BX.prop.getObject(this._schemeElement.getData(), "feedback_form", {}),
	        enabled: true,
	        showFeedbackLink: false
	      });
	      this._autocomplete.subscribe('onSelectValue', this.onSelectAutocompleteValue.bind(this));
	      this._autocomplete.subscribe('onCreateNewItem', this.onAddRequisiteFromAutocomplete.bind(this));
	      this._autocomplete.subscribe('onClear', this.onClearAutocompleteValue.bind(this));
	      this._autocomplete.subscribe('onInstallDefaultApp', this.onInstallDefaultApp.bind(this));
	      main_core_events.EventEmitter.subscribe("BX.Crm.RequisiteAutocomplete:onAfterInstallDefaultApp", this.onInstallDefaultAppGlobal.bind(this));
	      this.presetMenu = new PresetMenu(this.getName() + '_requisite_preset_menu', this.getPresetList());
	      this.presetMenu.subscribe('onSelect', this.onAddRequisiteFromMenu.bind(this));
	      var isReadonly = this.getEditor().isReadOnly();
	      this._tooltip = EntityEditorRequisiteTooltip.create(this.getName() + '_requisite_details', {
	        readonly: isReadonly,
	        canChangeDefaultRequisite: !isReadonly,
	        presets: this.getPresetList()
	      });
	      main_core_events.EventEmitter.subscribe(this._tooltip, 'onAddRequisite', this.onAddRequisiteFromTooltip.bind(this));
	      main_core_events.EventEmitter.subscribe(this._tooltip, 'onEditRequisite', this.onEditRequisite.bind(this));
	      main_core_events.EventEmitter.subscribe(this._tooltip, 'onDeleteRequisite', this.onDeleteRequisite.bind(this));
	      main_core_events.EventEmitter.subscribe(this._tooltip, 'onAddBankDetails', this.onAddBankDetails.bind(this));
	      main_core_events.EventEmitter.subscribe(this._tooltip, 'onSetSelectedRequisite', this.onSetSelectedRequisite.bind(this));
	      this.updateAutocompletePlaceholder();
	      this.updateAutocompeteClientResolverPlacementParams();
	      main_core_events.EventEmitter.emit(this.getEditor(), 'onFieldInit', {
	        field: this
	      });
	      var schemeElementData = this.getSchemeElement().getData();
	      if (schemeElementData.hasOwnProperty('isEditMode') && schemeElementData['isEditMode'] === true) {
	        schemeElementData['isEditMode'] = false;
	        if (this.getEditor().getMode() === BX.UI.EntityEditorMode.edit) {
	          setTimeout(function () {
	            _this2.editDefaultRequisite();
	          });
	        }
	      }
	    }
	  }, {
	    key: "setSelectModeEnabled",
	    value: function setSelectModeEnabled(selectModeEnabled) {
	      if (this.selectModeEnabled !== selectModeEnabled) {
	        this.selectModeEnabled = selectModeEnabled;
	        if (this.isSelectModeEnabled()) {
	          this.isSearchMode = false;
	        }
	        this.refreshLayoutParts();
	      }
	    }
	  }, {
	    key: "isSelectModeEnabled",
	    value: function isSelectModeEnabled() {
	      return this.selectModeEnabled && this.hasRequisites() && this.getRequisites().getList().length > 0;
	    }
	  }, {
	    key: "setRequisites",
	    value: function setRequisites(requisiteList) {
	      var hasRequisites = this.hasRequisites();
	      var vasEmpty = hasRequisites && this.getRequisites().isEmpty();
	      this._requisiteList = requisiteList;
	      requisiteList.unsubscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
	      requisiteList.subscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
	      this._tooltip.setRequisites(requisiteList);
	      if (hasRequisites && !vasEmpty && !this.getRequisites().isEmpty()) {
	        this.refreshLayoutParts();
	      } else {
	        this.refreshLayout();
	      }
	    }
	  }, {
	    key: "getRequisites",
	    value: function getRequisites() {
	      return this._requisiteList;
	    }
	  }, {
	    key: "hasRequisites",
	    value: function hasRequisites() {
	      return main_core.Type.isObject(this._requisiteList);
	    }
	  }, {
	    key: "isSingleMode",
	    value: function isSingleMode() {
	      if (!this.hasRequisites()) {
	        return true;
	      }
	      return this.getRequisites().getList().length <= 1;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var title = babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteField.prototype), "getTitle", this).call(this);
	      if (this.hasRequisites() && !this.isSingleMode()) {
	        var selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
	        var selectedPresetId = selectedRequisite ? selectedRequisite.getPresetId() : null;
	        if (selectedRequisite && selectedPresetId) {
	          var selectedPresetName = this.getPresetList().reduce(function (name, item) {
	            return item.value === selectedPresetId ? item.name : name;
	          }, '');
	          if (selectedPresetName.length) {
	            title += ' (' + selectedPresetName + ')';
	          }
	        }
	      }
	      return title;
	    }
	  }, {
	    key: "createTitleActionControls",
	    value: function createTitleActionControls() {
	      var actions = [];
	      if (this._mode !== BX.UI.EntityEditorMode.edit) {
	        return actions;
	      }
	      if (this.isAutocompleteEnabled() && this.selectModeEnabled) {
	        if (this.isSearchMode) {
	          actions.push(main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\"\n\t\t\t\t\t\tonclick=\"", "\">", "</span>"])), this.toggleSearchMode.bind(this), main_core.Loc.getMessage('REQUISITE_LABEL_DETAILS_SELECT')));
	        } else {
	          var title = this.getClientResolverTitle();
	          actions.push(main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\"\n\t\t\t\t\t\tonclick=\"", "\">", "</span>"])), this.toggleSearchMode.bind(this), title));
	        }
	      }
	      if (this.hasRequisites()) {
	        actions.push(main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\"\n\t\t\t\t \tonclick=\"", "\">", "</span>"])), this.editDefaultRequisite.bind(this), main_core.Loc.getMessage('REQUISITE_LABEL_DETAILS_TEXT')));
	      }
	      return actions;
	    }
	  }, {
	    key: "toggleSearchMode",
	    value: function toggleSearchMode() {
	      this.isSearchMode = !this.isSearchMode;
	      this.refreshLayoutParts();
	    }
	  }, {
	    key: "isNeedToDisplay",
	    value: function isNeedToDisplay(options) {
	      return this.hasRequisites() && babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteField.prototype), "isNeedToDisplay", this).call(this, options);
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (this._hasLayout) {
	        return;
	      }
	      this._domNodes = {};
	      this.ensureWrapperCreated({
	        classNames: ["crm-entity-widget-content-block-field-requisites"]
	      });
	      this.adjustWrapper();
	      this.bindWrapperEvents();
	      if (!this.isNeedToDisplay()) {
	        this.registerLayout(options);
	        this._hasLayout = true;
	        return;
	      }
	      if (this.isDragEnabled()) {
	        main_core.Dom.append(this.createDragButton(), this._wrapper);
	      }
	      main_core.Dom.append(this.createTitleNode(this.getTitle()), this._wrapper);
	      if (this._mode === BX.UI.EntityEditorMode.edit) {
	        this._domNodes.addButton = this.renderAddButton();
	        this._domNodes.autocompleteForm = this.renderAutocompleteForm();
	        this._domNodes.requisiteSelectForm = this.renderRequisiteSelectForm();
	        this._domNodes.bankDetailSelectForm = this.renderBankDetailSelectForm();
	        main_core.Dom.append(this._domNodes.autocompleteForm, this._wrapper);
	        main_core.Dom.append(this._domNodes.requisiteSelectForm, this._wrapper);
	        main_core.Dom.append(this._domNodes.bankDetailSelectForm, this._wrapper);
	        main_core.Dom.append(this._domNodes.addButton, this._wrapper);
	        this.adjustNodesVisibility();
	        this.updateRequisitesDropdown();
	        this.updateBankDetailsDropdown();
	        this.updateRequisiteSelectorValue();
	        this.updateBankDetailsSelectorValue();
	      } else
	        // if(this._mode === BX.UI.EntityEditorMode.view)
	        {
	          main_core.Dom.append(this.renderSelectedRequisite(), this._wrapper);
	        }
	      if (this.isContextMenuEnabled()) {
	        this._wrapper.appendChild(this.createContextMenuButton());
	      }
	      if (this.isDragEnabled()) {
	        this.initializeDragDropAbilities();
	      }
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "bindWrapperEvents",
	    value: function bindWrapperEvents() {
	      if (!this.wrapperMouseEnterHandler) {
	        this.wrapperMouseEnterHandler = this.onFieldMouseEnter.bind(this);
	      }
	      if (!this.wrapperMouseLeaveHandler) {
	        this.wrapperMouseLeaveHandler = this.onFieldMouseLeave.bind(this);
	      }
	      main_core.Event.unbind(this._wrapper, 'mouseenter', this.wrapperMouseEnterHandler);
	      main_core.Event.unbind(this._wrapper, 'mouseleave', this.wrapperMouseLeaveHandler);
	      main_core.Event.bind(this._wrapper, 'mouseenter', this.wrapperMouseEnterHandler);
	      main_core.Event.bind(this._wrapper, 'mouseleave', this.wrapperMouseLeaveHandler);
	    }
	  }, {
	    key: "refreshLayoutParts",
	    value: function refreshLayoutParts() {
	      this.updateSelectedRequisiteText();
	      this.refreshTitleLayout();
	      this.updateAutocompleteState();
	      this.adjustNodesVisibility();
	      this.updateRequisitesDropdown();
	      this.updateBankDetailsDropdown();
	      this.updateRequisiteSelectorValue();
	      this.updateBankDetailsSelectorValue();
	    }
	  }, {
	    key: "hasContentToDisplay",
	    value: function hasContentToDisplay() {
	      return this.hasValue();
	    }
	  }, {
	    key: "hasValue",
	    value: function hasValue() {
	      if (!this.hasRequisites()) {
	        return false;
	      }
	      var list = this.getRequisites().getList();
	      if (list.length > 1) {
	        return true;
	      }
	      // if list contains only one item, it shouldn't be hidden:
	      return list.length === 1 && !list[0].isAddressOnly();
	    }
	  }, {
	    key: "isAutocompleteEnabled",
	    value: function isAutocompleteEnabled() {
	      if (!this.hasRequisites() || this.getRequisites().isEmpty() || this.getRequisites().getSelected().isAddressOnly()) {
	        return !!this.getClientResolverPropForPreset(this.getSelectedPresetId());
	      }
	      return true;
	    }
	  }, {
	    key: "renderSelectedRequisite",
	    value: function renderSelectedRequisite() {
	      this._domNodes.selectedRequisiteView = main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	      this.updateSelectedRequisiteText();
	      this.updateAutocompleteState();
	      var container = main_core.Tag.render(_templateObject5$2 || (_templateObject5$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\" \n\t\t\t\tonclick=\"", "\"\n\t\t\t\tonmouseenter=\"", "\">\n\t\t\t\t\t", "\n\t\t\t</div>"])), this.onViewStringClick.bind(this), this.onViewStringMouseEnter.bind(this), this._domNodes.selectedRequisiteView);
	      this._tooltip.setBindElement(container, this.getEditor().getFormElement());
	      return container;
	    }
	  }, {
	    key: "updateSelectedRequisiteText",
	    value: function updateSelectedRequisiteText() {
	      if (!this._domNodes.selectedRequisiteView) {
	        return;
	      }
	      var selectedRequisite = this.hasRequisites() && this.getRequisites().getSelected();
	      if (this.hasValue() && selectedRequisite && selectedRequisite.getTitle().length) {
	        this._domNodes.selectedRequisiteView.classList.add('ui-link', 'ui-link-dark', 'ui-link-dotted');
	        this._domNodes.selectedRequisiteView.textContent = selectedRequisite.getTitle();
	      } else {
	        this._domNodes.selectedRequisiteView.classList.remove('ui-link', 'ui-link-dark', 'ui-link-dotted');
	        this._domNodes.selectedRequisiteView.textContent = BX.UI.EntityEditorField.messages.isEmpty;
	      }
	    }
	  }, {
	    key: "renderAddButton",
	    value: function renderAddButton() {
	      return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block crm-entity-widget-content-block-requisites\">\n\t\t\t\t<span class=\"crm-entity-widget-client-requisites-add-btn\" onclick=\"", "\">", "</span>\n\t\t\t</div>"])), this.toggleNewRequisitePresetMenu.bind(this), main_core.Loc.getMessage('CRM_EDITOR_ADD'));
	    }
	  }, {
	    key: "renderAutocompleteForm",
	    value: function renderAutocompleteForm() {
	      var autocompleteContainer = main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites\"></div>"])));
	      var hasResolvers = !!this.getClientResolverPropForPreset(this.getSelectedPresetId());
	      this._autocomplete.setEnabled(hasResolvers);
	      this._autocomplete.layout(autocompleteContainer);
	      this.updateAutocompleteState();
	      return main_core.Tag.render(_templateObject8$1 || (_templateObject8$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-entity-widget-content-block-add-field\">\n\t\t\t\t\t<span class=\"crm-entity-widget-content-add-field\" onclick=\"", "\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>"])), autocompleteContainer, this.toggleNewRequisitePresetMenu.bind(this), main_core.Loc.getMessage('CRM_EDITOR_ADD'));
	    }
	  }, {
	    key: "renderRequisiteSelectForm",
	    value: function renderRequisiteSelectForm() {
	      var _this3 = this;
	      var isOpen = false;
	      var toggleDropdown = function toggleDropdown() {
	        if (_this3.requisitesDropdown) {
	          if (!isOpen) {
	            _this3.requisitesDropdown.showPopupWindow();
	          } else {
	            _this3.requisitesDropdown.destroyPopupWindow();
	          }
	          isOpen = !isOpen;
	        }
	      };
	      var selectInput = main_core.Tag.render(_templateObject9$1 || (_templateObject9$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl-element\" onclick=\"", "\"></div>"])), toggleDropdown);
	      var selectContainer = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites\">\n\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-angle\" onclick=\"", "\"></button>\n\t\t\t\t\t", "\n\t\t\t\t</div>\t\t\t\n\t\t\t</div>"])), toggleDropdown, selectInput);
	      this.requisitesDropdown = new BX.UI.Dropdown({
	        targetElement: selectInput,
	        items: [],
	        isDisabled: true,
	        events: {
	          onSelect: this.onRequisiteSelect.bind(this)
	        }
	      });
	      return main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-entity-widget-content-block-add-field\">\n\t\t\t\t\t<span class=\"crm-entity-widget-content-add-field\" onclick=\"", "\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>"])), selectContainer, this.toggleNewRequisitePresetMenu.bind(this), main_core.Loc.getMessage('CRM_EDITOR_ADD_REQUISITE'));
	    }
	  }, {
	    key: "renderBankDetailSelectForm",
	    value: function renderBankDetailSelectForm() {
	      var _this4 = this;
	      var isOpen = false;
	      var toggleDropdown = function toggleDropdown() {
	        if (_this4.bankDetailsDropdown) {
	          if (!isOpen) {
	            _this4.bankDetailsDropdown.showPopupWindow();
	          } else {
	            _this4.bankDetailsDropdown.destroyPopupWindow();
	          }
	          isOpen = !isOpen;
	        }
	      };
	      var selectInput = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl-element\" onclick=\"", "\"></div>"])), toggleDropdown);
	      this._domNodes.bankDetailsSelectContainer = main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites\">\n\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-angle\" onclick=\"", "\"></button>\n\t\t\t\t\t", "\n\t\t\t\t</div>\t\t\t\n\t\t\t</div>"])), toggleDropdown, selectInput);
	      this.bankDetailsDropdown = new BX.UI.Dropdown({
	        targetElement: selectInput,
	        items: [],
	        isDisabled: true,
	        events: {
	          onSelect: this.onBankDetailSelect.bind(this)
	        }
	      });
	      return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-entity-widget-content-block-add-field\">\n\t\t\t\t\t<span class=\"crm-entity-widget-content-add-field\" onclick=\"", "\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>"])), this._domNodes.bankDetailsSelectContainer, this.onAddBankDetailsClick.bind(this), main_core.Loc.getMessage('CRM_EDITOR_ADD_BANK_DETAILS'));
	    }
	  }, {
	    key: "updateAutocompleteState",
	    value: function updateAutocompleteState() {
	      var autocompleteValue = null;
	      var selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
	      if (selectedRequisite && !selectedRequisite.isAddressOnly()) {
	        autocompleteValue = selectedRequisite.getAutocompleteData();
	      }
	      this._autocomplete.setCurrentItem(autocompleteValue);
	      this._autocomplete.setContext(this.getAutocompleteContext());
	    }
	  }, {
	    key: "updateAutocompletePlaceholder",
	    value: function updateAutocompletePlaceholder() {
	      var clientResolverPropTitle = this.getClientResolverTitle();
	      this._autocomplete.setEnabled(!!clientResolverPropTitle);
	      this._autocomplete.setPlaceholderText(clientResolverPropTitle);
	    }
	  }, {
	    key: "updateAutocompeteClientResolverPlacementParams",
	    value: function updateAutocompeteClientResolverPlacementParams() {
	      this._autocomplete.setClientResolverPlacementParams(this.getClientResolverPlacementParams());
	    }
	  }, {
	    key: "getClientResolverPlacementParams",
	    value: function getClientResolverPlacementParams() {
	      var clientResolverProp = this.getClientResolverPropForPreset(this.getSelectedPresetId());
	      return clientResolverProp ? {
	        "isPlacement": BX.prop.getString(clientResolverProp, "IS_PLACEMENT", "N") === "Y",
	        "numberOfPlacements": BX.prop.getArray(clientResolverProp, "PLACEMENTS", []).length,
	        "countryId": BX.prop.getInteger(clientResolverProp, "COUNTRY_ID", 0),
	        "defaultAppInfo": BX.prop.getObject(clientResolverProp, "DEFAULT_APP_INFO", {})
	      } : {};
	    }
	  }, {
	    key: "getClientResolverTitle",
	    value: function getClientResolverTitle() {
	      var title = "";
	      var clientResolverProp = this.getClientResolverPropForPreset(this.getSelectedPresetId());
	      title = BX.prop.getString(clientResolverProp, "TITLE", "");
	      var isPlacement = BX.prop.getString(clientResolverProp, 'IS_PLACEMENT', 'N') === 'Y';
	      if (!isPlacement && main_core.Type.isStringFilled(title)) {
	        var modifiedTitle = main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN_01', {
	          '#FIELD_NAME#': title
	        });
	        if (main_core.Type.isStringFilled(modifiedTitle)) {
	          title = modifiedTitle;
	        }
	      }
	      return title;
	    }
	  }, {
	    key: "adjustNodesVisibility",
	    value: function adjustNodesVisibility() {
	      if (!this._domNodes.autocompleteForm || !this._domNodes.requisiteSelectForm || !this._domNodes.bankDetailSelectForm || !this._domNodes.addButton) {
	        return;
	      }
	      if (this.isSearchMode && this.isAutocompleteEnabled()) {
	        this._domNodes.autocompleteForm.style.display = '';
	        this._domNodes.requisiteSelectForm.style.display = 'none';
	        this._domNodes.addButton.style.display = 'none';
	        this._domNodes.bankDetailSelectForm.style.display = 'none';
	      } else if (this.isSelectModeEnabled() && this.hasRequisites() && (this.getRequisites().getList().length > 1 || this.getRequisites().getList().length > 0 && !this.getRequisites().getSelected().isAddressOnly())) {
	        this._domNodes.autocompleteForm.style.display = 'none';
	        this._domNodes.requisiteSelectForm.style.display = '';
	        this._domNodes.bankDetailSelectForm.style.display = '';
	        this._domNodes.addButton.style.display = 'none';
	        if (this._domNodes.bankDetailsSelectContainer) {
	          var bankDetails = this.getRequisites().getSelected().getBankDetails();
	          if (!bankDetails.length) {
	            this._domNodes.bankDetailsSelectContainer.style.display = 'none';
	          } else {
	            this._domNodes.bankDetailsSelectContainer.style.display = '';
	          }
	        }
	      } else {
	        this._domNodes.autocompleteForm.style.display = 'none';
	        this._domNodes.requisiteSelectForm.style.display = 'none';
	        this._domNodes.addButton.style.display = '';
	        this._domNodes.bankDetailSelectForm.style.display = 'none';
	      }
	    }
	  }, {
	    key: "getSelectedRequisiteTitle",
	    value: function getSelectedRequisiteTitle() {
	      var title = '';
	      if (!this.hasRequisites()) {
	        return title;
	      }
	      var selectedRequisite = this.getRequisites().getSelected();
	      if (selectedRequisite) {
	        title = selectedRequisite.getTitle();
	      }
	      return title;
	    }
	  }, {
	    key: "updateRequisiteSelectorValue",
	    value: function updateRequisiteSelectorValue() {
	      if (!this.requisitesDropdown || !this.requisitesDropdown.targetElement) {
	        return;
	      }
	      this.requisitesDropdown.targetElement.innerText = this.getSelectedRequisiteTitle();
	    }
	  }, {
	    key: "updateBankDetailsSelectorValue",
	    value: function updateBankDetailsSelectorValue() {
	      if (!this.bankDetailsDropdown || !this.bankDetailsDropdown.targetElement) {
	        return;
	      }
	      this.bankDetailsDropdown.targetElement.innerText = this.getSelectedBankDetailTitle();
	    }
	  }, {
	    key: "getSelectedBankDetailTitle",
	    value: function getSelectedBankDetailTitle() {
	      var title = '';
	      if (!this.hasRequisites()) {
	        return title;
	      }
	      var selectedRequisite = this.getRequisites().getSelected();
	      if (selectedRequisite) {
	        var bankDetail = selectedRequisite.getBankDetailById(selectedRequisite.getSelectedBankDetailId());
	        if (bankDetail && bankDetail.title) {
	          title = bankDetail.title;
	        }
	      }
	      return title;
	    }
	  }, {
	    key: "getDefaultPresetId",
	    value: function getDefaultPresetId() {
	      var _iterator = _createForOfIteratorHelper$3(BX.prop.getArray(this._schemeElement.getData(), "presets", [])),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var preset = _step.value;
	          if (preset.IS_DEFAULT) {
	            return preset.VALUE;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return null;
	    }
	  }, {
	    key: "getSelectedPresetId",
	    value: function getSelectedPresetId() {
	      var selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
	      if (selectedRequisite) {
	        return selectedRequisite.getPresetId();
	      }
	      return this.getDefaultPresetId();
	    }
	  }, {
	    key: "getClientResolverPropForPreset",
	    value: function getClientResolverPropForPreset(presetId) {
	      var _iterator2 = _createForOfIteratorHelper$3(BX.prop.getArray(this._schemeElement.getData(), "presets", [])),
	        _step2;
	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var preset = _step2.value;
	          if (preset.VALUE === presetId) {
	            return BX.prop.get(preset, 'CLIENT_RESOLVER_PROP', null);
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	      return null;
	    }
	  }, {
	    key: "getAutocompleteContext",
	    value: function getAutocompleteContext() {
	      return {
	        presetId: this.getSelectedPresetId()
	      };
	    }
	  }, {
	    key: "toggleNewRequisitePresetMenu",
	    value: function toggleNewRequisitePresetMenu(e) {
	      this.presetMenu.toggle(e.target);
	    }
	  }, {
	    key: "getPresetList",
	    value: function getPresetList() {
	      var presets = [];
	      var _iterator3 = _createForOfIteratorHelper$3(BX.prop.getArray(this._schemeElement.getData(), "presets")),
	        _step3;
	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var item = _step3.value;
	          var value = BX.prop.getString(item, "VALUE", 0);
	          var name = BX.prop.getString(item, "NAME", value);
	          presets.push({
	            name: name,
	            value: value
	          });
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }
	      return presets;
	    }
	  }, {
	    key: "addRequisite",
	    value: function addRequisite(params) {
	      main_core_events.EventEmitter.emit(this, 'onEditNew', params);
	    }
	  }, {
	    key: "editRequisite",
	    value: function editRequisite(id, options) {
	      main_core_events.EventEmitter.emit(this, 'onEditExisted', {
	        id: id,
	        options: options
	      });
	    }
	  }, {
	    key: "deleteRequisite",
	    value: function deleteRequisite(id) {
	      this._tooltip.removeDebouncedEvents();
	      this._tooltip.close();
	      main_core_events.EventEmitter.emit(this, 'onDelete', {
	        id: id,
	        postponed: this._mode === BX.UI.EntityEditorMode.edit
	      });
	    }
	  }, {
	    key: "hideRequisite",
	    value: function hideRequisite(id) {
	      this.markAsChanged();
	      this._autocomplete.setCurrentItem(null);
	      main_core_events.EventEmitter.emit(this, 'onHide', {
	        id: id
	      });
	    }
	  }, {
	    key: "showDeleteConfirmation",
	    value: function showDeleteConfirmation(requisiteId) {
	      var _this5 = this;
	      BX.Crm.EditorAuxiliaryDialog.create("delete_requisite_confirmation", {
	        title: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_TITLE'),
	        content: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_CONTENT'),
	        buttons: [{
	          id: "yes",
	          type: BX.Crm.DialogButtonType.accept,
	          text: main_core.Loc.getMessage("CRM_EDITOR_YES"),
	          callback: function callback(button) {
	            button.getDialog().close();
	            _this5.markAsChanged();
	            _this5.deleteRequisite(requisiteId);
	          }
	        }, {
	          id: "no",
	          type: BX.Crm.DialogButtonType.cancel,
	          text: main_core.Loc.getMessage("CRM_EDITOR_NO"),
	          callback: function callback(button) {
	            button.getDialog().close();
	          }
	        }]
	      }).open();
	    }
	  }, {
	    key: "showClearConfirmation",
	    value: function showClearConfirmation(requisiteId) {
	      var _this6 = this;
	      BX.Crm.EditorAuxiliaryDialog.create("hide_requisite_confirmation", {
	        title: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_HIDE_CONFIRMATION_TITLE'),
	        content: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_HIDE_CONFIRMATION_CONTENT'),
	        buttons: [{
	          id: "yes",
	          type: BX.Crm.DialogButtonType.accept,
	          text: main_core.Loc.getMessage("CRM_EDITOR_YES"),
	          callback: function callback(button) {
	            button.getDialog().close();
	            _this6.hideRequisite(requisiteId);
	          }
	        }, {
	          id: "no",
	          type: BX.Crm.DialogButtonType.cancel,
	          text: main_core.Loc.getMessage("CRM_EDITOR_NO"),
	          callback: function callback(button) {
	            button.getDialog().close();
	          }
	        }]
	      }).open();
	    }
	  }, {
	    key: "editDefaultRequisite",
	    value: function editDefaultRequisite() {
	      var selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
	      if (null !== selectedRequisiteId) {
	        this.editRequisite(selectedRequisiteId, {
	          autocompleteState: this._autocomplete.getState()
	        });
	      } else {
	        this.addRequisite({
	          presetId: this.getDefaultPresetId(),
	          autocompleteState: this._autocomplete.getState()
	        });
	      }
	    }
	  }, {
	    key: "onChangeRequisites",
	    value: function onChangeRequisites() {
	      if (this._domNodes && main_core.Type.isDomNode(this._domNodes.addButton) && this._domNodes.addButton.style.display !== 'none' || this.hasRequisites() && this.getRequisites().isEmpty() || this.hasRequisites() && this.getRequisites().getSelected().isAddressOnly()) {
	        // this.refreshLayout();
	        this.refreshLayoutParts();
	      } else {
	        this.refreshLayoutParts();
	      }
	      this.updateAutocompletePlaceholder();
	      this.updateAutocompeteClientResolverPlacementParams();
	    }
	  }, {
	    key: "updateRequisitesDropdown",
	    value: function updateRequisitesDropdown() {
	      if (!this.requisitesDropdown) {
	        return;
	      }
	      var items = [];
	      if (this.hasRequisites()) {
	        this.getRequisites().getList().forEach(function (requisiteItem, index) {
	          items.push({
	            id: requisiteItem.getRequisiteId(),
	            title: requisiteItem.getTitle(),
	            subTitle: requisiteItem.getSubtitle(),
	            index: index
	          });
	        });
	      }
	      this.requisitesDropdown.setItems(items);
	    }
	  }, {
	    key: "updateBankDetailsDropdown",
	    value: function updateBankDetailsDropdown() {
	      if (!this.bankDetailsDropdown) {
	        return;
	      }
	      var items = [];
	      if (this.hasRequisites() && this.getRequisites().getSelected()) {
	        var bankDetails = this.getRequisites().getSelected().getBankDetails();
	        if (bankDetails.length) {
	          bankDetails.forEach(function (bankDetail, index) {
	            items.push({
	              id: bankDetail.id,
	              title: bankDetail.title,
	              subTitle: bankDetail.value,
	              index: index
	            });
	          });
	        }
	      }
	      this.bankDetailsDropdown.setItems(items);
	    }
	  }, {
	    key: "onRequisiteSelect",
	    value: function onRequisiteSelect(sender, _ref) {
	      var index = _ref.index;
	      if (!this.hasRequisites()) {
	        return;
	      }
	      if (index !== undefined) {
	        var selectedRequisiteId = Number(this.getRequisites().getSelectedId());
	        if (selectedRequisiteId !== index) {
	          this.getRequisites().setSelected(index);
	          this.markAsChanged();
	        }
	      }
	      if (this.requisitesDropdown) {
	        this.requisitesDropdown.getPopupWindow().close();
	      }
	    }
	  }, {
	    key: "onBankDetailSelect",
	    value: function onBankDetailSelect(sender, _ref2) {
	      var index = _ref2.index;
	      if (!this.hasRequisites() || !this.bankDetailsDropdown) {
	        return;
	      }
	      if (index !== undefined) {
	        var selectedRequisiteId = Number(this.getRequisites().getSelectedId());
	        var selectedBankDetailId = Number(this.getRequisites().getSelected().getSelectedBankDetailId());
	        if (selectedBankDetailId !== index) {
	          this.getRequisites().setSelected(selectedRequisiteId, index);
	          this.markAsChanged();
	        }
	      }
	      this.bankDetailsDropdown.getPopupWindow().close();
	    }
	  }, {
	    key: "onAddRequisiteFromMenu",
	    value: function onAddRequisiteFromMenu(event) {
	      var data = event.getData();
	      var selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
	      // if hidden requisite is selected, it will be used instead of new:
	      if (null !== selectedRequisite && selectedRequisite.isAddressOnly()) {
	        this.editRequisite(this.getRequisites().getSelectedId(), {
	          editorOptions: {
	            overriddenPresetId: data.value
	          }
	        });
	      } else {
	        this.addRequisite({
	          presetId: data.value
	        });
	      }
	    }
	  }, {
	    key: "onAddRequisiteFromTooltip",
	    value: function onAddRequisiteFromTooltip(event) {
	      this.addRequisite(event.getData());
	    }
	  }, {
	    key: "onAddRequisiteFromAutocomplete",
	    value: function onAddRequisiteFromAutocomplete() {
	      this.editDefaultRequisite();
	    }
	  }, {
	    key: "onEditRequisite",
	    value: function onEditRequisite(event) {
	      var eventData = event.getData();
	      this.editRequisite(eventData.id, {
	        autocompleteState: this._autocomplete.getState()
	      });
	    }
	  }, {
	    key: "onDeleteRequisite",
	    value: function onDeleteRequisite(event) {
	      var eventData = event.getData();
	      this.showDeleteConfirmation(eventData.id);
	    }
	  }, {
	    key: "onAddBankDetails",
	    value: function onAddBankDetails(event) {
	      var eventData = event.getData();
	      this.editRequisite(eventData.requisiteId, {
	        editorOptions: {
	          addBankDetailsItem: true
	        },
	        autocompleteState: this._autocomplete.getState()
	      });
	    }
	  }, {
	    key: "onSelectAutocompleteValue",
	    value: function onSelectAutocompleteValue(event) {
	      var data = event.getData();
	      this.markAsChanged();
	      this._autocomplete.setLoading(true);
	      if (this.selectModeEnabled) {
	        this.isSearchMode = false;
	      }
	      var selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
	      main_core_events.EventEmitter.emit(this, 'onFinishAutocomplete', {
	        id: selectedRequisiteId,
	        defaultPresetId: this.getDefaultPresetId(),
	        autocompleteState: this._autocomplete.getState(),
	        data: data
	      });
	    }
	  }, {
	    key: "onClearAutocompleteValue",
	    value: function onClearAutocompleteValue() {
	      var selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
	      if (null !== selectedRequisiteId) {
	        var selectedRequisite = this.getRequisites().getSelected();
	        var hasAddresses = false;
	        var addresses = selectedRequisite.getAddressList();
	        for (var addressType in addresses) {
	          if (addresses.hasOwnProperty(addressType) && main_core.Type.isStringFilled(addresses[addressType])) {
	            hasAddresses = true;
	            break;
	          }
	        }
	        if (hasAddresses) {
	          this.showClearConfirmation(selectedRequisiteId);
	        } else {
	          this.showDeleteConfirmation(selectedRequisiteId);
	        }
	      }
	    }
	  }, {
	    key: "onInstallDefaultApp",
	    value: function onInstallDefaultApp() {
	      BX.onGlobalCustomEvent("BX.Crm.RequisiteAutocomplete:onAfterInstallDefaultApp");
	    }
	  }, {
	    key: "onInstallDefaultAppGlobal",
	    value: function onInstallDefaultAppGlobal() {
	      var _this7 = this;
	      BX.ajax.runAction('crm.requisite.schemedata.getRequisitesSchemeData', {
	        data: {
	          entityTypeId: this.getEditor().getEntityTypeId()
	        }
	      }).then(function (data) {
	        if (main_core.Type.isPlainObject(data) && data.hasOwnProperty("data") && main_core.Type.isPlainObject(data["data"])) {
	          _this7._schemeElement.setData(data["data"]);
	          _this7.updateAutocompletePlaceholder();
	          _this7.updateAutocompeteClientResolverPlacementParams();
	        }
	      });
	    }
	  }, {
	    key: "onViewStringClick",
	    value: function onViewStringClick() {
	      if (!this.getEditor().isReadOnly()) {
	        this._tooltip.removeDebouncedEvents();
	        this._tooltip.close();
	        this.switchToSingleEditMode();
	      }
	    }
	  }, {
	    key: "onViewStringMouseEnter",
	    value: function onViewStringMouseEnter() {
	      if (this._mode === BX.UI.EntityEditorMode.view && this.hasValue()) {
	        this._tooltip.showDebounced();
	      }
	    }
	  }, {
	    key: "onSetSelectedRequisite",
	    value: function onSetSelectedRequisite(event) {
	      var eventData = event.getData();
	      if (!this.getEditor().isReadOnly()) {
	        main_core_events.EventEmitter.emit(this, 'onSetDefault', eventData);
	      }
	      return false;
	    }
	  }, {
	    key: "onFieldMouseEnter",
	    value: function onFieldMouseEnter() {
	      if (this._mode === BX.UI.EntityEditorMode.view && this.hasValue()) {
	        this._tooltip.showDebounced(5);
	      }
	    }
	  }, {
	    key: "onFieldMouseLeave",
	    value: function onFieldMouseLeave() {
	      this._tooltip.closeDebounced();
	      this._tooltip.cancelShowDebounced();
	    }
	  }, {
	    key: "onAddBankDetailsClick",
	    value: function onAddBankDetailsClick(event) {
	      event.preventDefault();
	      var selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
	      if (null !== selectedRequisiteId) {
	        this.editRequisite(selectedRequisiteId, {
	          autocompleteState: this._autocomplete.getState(),
	          editorOptions: {
	            addBankDetailsItem: true
	          }
	        });
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorRequisiteField;
	}(BX.Crm.EntityEditorField);

	var EntityEditorRequisiteAddressField = /*#__PURE__*/function (_EntityEditorAddressF) {
	  babelHelpers.inherits(EntityEditorRequisiteAddressField, _EntityEditorAddressF);
	  function EntityEditorRequisiteAddressField() {
	    babelHelpers.classCallCheck(this, EntityEditorRequisiteAddressField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorRequisiteAddressField).apply(this, arguments));
	  }
	  babelHelpers.createClass(EntityEditorRequisiteAddressField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteAddressField.prototype), "initialize", this).call(this, id, settings);
	      main_core_events.EventEmitter.emit(this.getEditor(), 'onFieldInit', {
	        field: this
	      });
	    }
	  }, {
	    key: "rollback",
	    value: function rollback() {
	      // rollback will be executed in requisite controller
	    }
	  }, {
	    key: "reset",
	    value: function reset() {
	      // reset will be executed in requisite controller
	    }
	  }, {
	    key: "onAddressListUpdate",
	    value: function onAddressListUpdate(event) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorRequisiteAddressField.prototype), "onAddressListUpdate", this).call(this, event);
	      main_core_events.EventEmitter.emit(this, 'onAddressListUpdate', event);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorRequisiteAddressField;
	}(crm_entityEditor_field_address.EntityEditorAddressField);

	var _templateObject$3, _templateObject2$3;
	function _createForOfIteratorHelper$4(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$4(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$4(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$4(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$4(o, minLen); }
	function _arrayLikeToArray$4(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var EntityEditorClientRequisites = /*#__PURE__*/function () {
	  function EntityEditorClientRequisites() {
	    babelHelpers.classCallCheck(this, EntityEditorClientRequisites);
	    this._id = "";
	    this._entityInfo = null;
	    this._fieldsParams = null;
	    this._loaderConfig = null;
	    this._addressConfig = null;
	    this._requisiteList = null;
	    this._requisiteEditor = null;
	    this._permissionToken = null;
	    this._readonly = true;
	    this._requisiteEditUrl = null;
	    this._addressContainer = null;
	    this._formElement = null;
	    this._showTooltipOnEntityLoad = false;
	  }
	  babelHelpers.createClass(EntityEditorClientRequisites, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this.setEntity(BX.prop.get(settings, "entityInfo", null), false);
	      if (!this._entityInfo) {
	        throw "EntityEditorClientRequisites: EntityInfo must be instance of BX.CrmEntityInfo";
	      }
	      this._fieldsParams = BX.prop.get(settings, "fieldsParams", null);
	      if (!this._fieldsParams) {
	        throw "EntityEditorClientRequisites: Fields params are undefined";
	      }
	      this._addressConfig = BX.prop.getObject(this._fieldsParams, 'ADDRESS', null);
	      this._requisitesConfig = BX.prop.getObject(this._fieldsParams, 'REQUISITES', null);
	      if (this._requisiteList) {
	        var selectedItem = BX.prop.getObject(settings, "requisiteBinding", null);
	        if (selectedItem && !main_core.Type.isUndefined(selectedItem.REQUISITE_ID) && !main_core.Type.isUndefined(selectedItem.BANK_DETAIL_ID)) {
	          var requisite = this._requisiteList.getByRequisiteId(selectedItem.REQUISITE_ID);
	          if (requisite) {
	            var bankDetail = selectedItem.BANK_DETAIL_ID > 0 ? requisite.getBankDetailByBankDetailId(selectedItem.BANK_DETAIL_ID) : null;
	            this._requisiteList.setSelected(this._requisiteList.indexOf(requisite), bankDetail ? requisite.getBankDetails().indexOf(bankDetail) : null);
	          }
	        }
	      }
	      this._loaderConfig = BX.prop.getObject(settings, 'loaderConfig', null);
	      this._readonly = BX.prop.getBoolean(settings, "readonly", true);
	      this._canChangeDefaultRequisite = BX.prop.getBoolean(settings, "canChangeDefaultRequisite", true);
	      this._requisiteEditUrl = BX.prop.getString(settings, "requisiteEditUrl", null);
	      this._formElement = BX.prop.get(settings, "formElement", null);
	      this._permissionToken = BX.prop.getString(settings, "permissionToken", null);
	      if (BX.prop.getBoolean(settings, "enableTooltip", true) && !main_core.Type.isNull(this._requisitesConfig)) {
	        this._tooltip = EntityEditorRequisiteTooltip.create(this._id + '_client_requisite_details', {
	          readonly: this._readonly,
	          canChangeDefaultRequisite: this._canChangeDefaultRequisite,
	          presets: this.getRequisitesPresetList()
	        });
	      }
	      this._requisiteEditor = EntityEditorRequisiteEditor.create(this._id + '_rq_editor', {
	        entityTypeId: this._entityInfo.getTypeId(),
	        entityId: this._entityInfo.getId(),
	        contextId: BX.prop.getString(settings, "contextId", ""),
	        requisiteEditUrl: this._requisiteEditUrl,
	        permissionToken: this._permissionToken
	      });
	      this._requisiteEditor.setRequisiteList(this._requisiteList);
	      main_core_events.EventEmitter.subscribe(this._requisiteEditor, 'onAfterEditRequisite', this.onRequisiteEditorAfterEdit.bind(this));
	      main_core_events.EventEmitter.subscribe(this._requisiteEditor, 'onAfterDeleteRequisite', this.onRequisiteEditorAfterDelete.bind(this));
	      if (this._tooltip) {
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onAddRequisite', this.onEditNewRequisite.bind(this));
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onEditRequisite', this.onEditExistedRequisite.bind(this));
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onDeleteRequisite', this.onDeleteRequisite.bind(this));
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onAddBankDetails', this.onAddBankDetails.bind(this));
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onSetSelectedRequisite', this.onSetSelectedRequisite.bind(this));
	        main_core_events.EventEmitter.subscribe(this._tooltip, 'onShow', this.onShowTooltip.bind(this));
	      }
	    }
	  }, {
	    key: "setEntity",
	    value: function setEntity(entityInfo, emitNotification) {
	      this._entityInfo = entityInfo;
	      if (this._entityInfo.hasEditRequisiteData()) {
	        this._requisiteList = RequisiteList.create(this._entityInfo.getRequisites());
	      }
	      if (emitNotification) {
	        this.onChangeRequisiteList();
	      }
	    }
	  }, {
	    key: "addressLayout",
	    value: function addressLayout(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        return;
	      }
	      this._addressContainer = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-entity-widget-client-address\"></div>"])));
	      main_core.Dom.append(this._addressContainer, container);
	      this.doAddressLayout();
	    }
	  }, {
	    key: "doAddressLayout",
	    value: function doAddressLayout() {
	      if (!main_core.Type.isDomNode(this._addressContainer)) {
	        return;
	      }
	      main_core.Dom.clean(this._addressContainer);
	      if (!main_core.Type.isNull(this._addressConfig)) {
	        if (this._entityInfo.hasEditRequisiteData()) {
	          var defaultRequisite = this._requisiteList.getSelected();
	          var addressValue = defaultRequisite ? defaultRequisite.getAddressList() : null;
	          if (!main_core.Type.isNull(addressValue) && Object.keys(addressValue).length) {
	            var countryId = 0;
	            if (defaultRequisite) {
	              countryId = parseInt(defaultRequisite.getPresetCountryId());
	            }
	            this._addressField = crm_entityEditor_field_address_base.EntityEditorBaseAddressField.create(this._entityInfo.getId(), {
	              showFirstItemOnly: true,
	              showAddressTypeInViewMode: true,
	              addressZoneConfig: BX.prop.getObject(this._addressConfig, "addressZoneConfig", {}),
	              countryId: countryId,
	              defaultAddressTypeByCategory: BX.prop.getInteger(this._addressConfig, "defaultAddressTypeByCategory", 0)
	            });
	            this._addressField.setMultiple(true);
	            this._addressField.setTypesList(BX.prop.getObject(this._addressConfig, "types", {}));
	            this._addressField.setValue(addressValue);
	            main_core.Dom.append(this._addressField.layout(false), this._addressContainer);
	          }
	        } else {
	          var showAddressLink = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"ui-link ui-link-secondary ui-link-dotted\" onmouseup=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>"])), this.onLoadAddressMouseUp.bind(this), main_core.Loc.getMessage('CLIENT_REQUISITES_ADDRESS_SHOW_ADDRESS'));
	          main_core.Dom.append(showAddressLink, this._addressContainer);
	        }
	      }
	    }
	  }, {
	    key: "showTooltip",
	    value: function showTooltip(bindElement) {
	      if (!this._tooltip) {
	        return;
	      }
	      if (this._entityInfo.hasEditRequisiteData()) {
	        this._tooltip.setRequisites(this._requisiteList);
	        if (this.isRequisiteAddressOnly()) {
	          return;
	        }
	      }
	      this._tooltip.setBindElement(bindElement, this._formElement);
	      this._tooltip.showDebounced();
	    }
	  }, {
	    key: "closeTooltip",
	    value: function closeTooltip() {
	      if (!this._tooltip) {
	        return;
	      }
	      this._tooltip.closeDebounced();
	      this._tooltip.cancelShowDebounced();
	      this._showTooltipOnEntityLoad = false;
	    }
	  }, {
	    key: "release",
	    value: function release() {
	      if (this._addressField) {
	        this._addressField.release();
	      }
	      if (this._tooltip) {
	        this._tooltip.close();
	        this._tooltip.removeDebouncedEvents();
	      }
	      if (this._requisiteEditor) {
	        this._requisiteEditor.release();
	      }
	    }
	  }, {
	    key: "loadEntity",
	    value: function loadEntity() {
	      if (!this._loaderConfig) {
	        return;
	      }
	      if (main_core.Type.isDomNode(this._addressContainer)) {
	        var loader = new main_loader.Loader({
	          size: 19,
	          mode: 'inline'
	        });
	        main_core.Dom.clean(this._addressContainer);
	        loader.show(this._addressContainer);
	      }
	      BX.CrmDataLoader.create(this._id, {
	        serviceUrl: this._loaderConfig["url"],
	        action: this._loaderConfig["action"],
	        params: {
	          "ENTITY_TYPE_NAME": this._entityInfo.getTypeName(),
	          "ENTITY_ID": this._entityInfo.getId(),
	          "NORMALIZE_MULTIFIELDS": "Y"
	        }
	      }).load(this.onEntityInfoLoad.bind(this));
	    }
	  }, {
	    key: "getRequisitesPresetList",
	    value: function getRequisitesPresetList() {
	      if (main_core.Type.isNull(this._requisitesConfig)) {
	        return [];
	      }
	      var presets = [];
	      var _iterator = _createForOfIteratorHelper$4(BX.prop.getArray(this._requisitesConfig, "presets")),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;
	          var value = BX.prop.getString(item, "VALUE", 0);
	          var name = BX.prop.getString(item, "NAME", value);
	          presets.push({
	            name: name,
	            value: value
	          });
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return presets;
	    }
	  }, {
	    key: "deleteRequisite",
	    value: function deleteRequisite(id) {
	      if (this._tooltip) {
	        this._tooltip.removeDebouncedEvents();
	        this._tooltip.close();
	      }
	      this._requisiteEditor.deleteRequisite(id);
	    }
	  }, {
	    key: "isRequisiteAddressOnly",
	    value: function isRequisiteAddressOnly() {
	      var list = this._requisiteList.getList();
	      return list.length === 1 && list[0].isAddressOnly();
	    }
	  }, {
	    key: "onLoadAddressMouseUp",
	    value: function onLoadAddressMouseUp(event) {
	      event.stopPropagation(); // cancel switching client to edit mode
	      this.loadEntity();
	    }
	  }, {
	    key: "onEntityInfoLoad",
	    value: function onEntityInfoLoad(sender, result) {
	      var entityData = BX.prop.getObject(result, "DATA", null);
	      if (entityData) {
	        this.setEntity(BX.CrmEntityInfo.create(entityData), true);
	        if (this._tooltip && this._showTooltipOnEntityLoad) {
	          if (!this.isRequisiteAddressOnly()) {
	            this._tooltip.show();
	          }
	          this._showTooltipOnEntityLoad = false;
	        }
	      }
	    }
	  }, {
	    key: "onEditNewRequisite",
	    value: function onEditNewRequisite(event) {
	      var params = event.getData();
	      params.selected = this._requisiteList.isEmpty();
	      var requisite = RequisiteListItem.create(null, {
	        'newRequisiteId': this._requisiteList.getNewRequisiteId(),
	        'newRequisiteExtraFields': params
	      });
	      requisite.setRequisiteId(this._requisiteList.getNewRequisiteId());
	      this._requisiteEditor.open(requisite);
	    }
	  }, {
	    key: "onEditExistedRequisite",
	    value: function onEditExistedRequisite(event) {
	      var params = event.getData();
	      var requisite = this._requisiteList.getById(params.id);
	      if (requisite) {
	        this._requisiteEditor.open(requisite, {});
	      }
	    }
	  }, {
	    key: "onDeleteRequisite",
	    value: function onDeleteRequisite(event) {
	      var _this = this;
	      var eventData = event.getData();
	      BX.Crm.EditorAuxiliaryDialog.create("delete_requisite_confirmation", {
	        title: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_TITLE'),
	        content: main_core.Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_CONTENT'),
	        buttons: [{
	          id: "yes",
	          type: BX.Crm.DialogButtonType.accept,
	          text: main_core.Loc.getMessage("CRM_EDITOR_YES"),
	          callback: function callback(button) {
	            button.getDialog().close();
	            _this.deleteRequisite(eventData.id);
	          }
	        }, {
	          id: "no",
	          type: BX.Crm.DialogButtonType.cancel,
	          text: main_core.Loc.getMessage("CRM_EDITOR_NO"),
	          callback: function callback(button) {
	            button.getDialog().close();
	          }
	        }]
	      }).open();
	    }
	  }, {
	    key: "onAddBankDetails",
	    value: function onAddBankDetails(event) {
	      var eventData = event.getData();
	      var requisite = this._requisiteList.getById(eventData.requisiteId);
	      if (requisite) {
	        this._requisiteEditor.open(requisite, {
	          addBankDetailsItem: true
	        });
	      }
	    }
	  }, {
	    key: "onSetSelectedRequisite",
	    value: function onSetSelectedRequisite(event) {
	      var eventData = event.getData();
	      this._requisiteList.setSelected(eventData.id, eventData.bankDetailId);
	      this.doAddressLayout();
	      var newSelectedRequisite = this._requisiteList.getSelected();
	      if (newSelectedRequisite) {
	        var selectedBankDetail = newSelectedRequisite.getBankDetailById(newSelectedRequisite.getSelectedBankDetailId());
	        var selectedBankDetailId = main_core.Type.isNull(selectedBankDetail) ? 0 : selectedBankDetail.id;
	        main_core_events.EventEmitter.emit(this, 'onSetSelectedRequisite', {
	          requisiteId: newSelectedRequisite.getRequisiteId(),
	          bankDetailId: selectedBankDetailId
	        });
	      }
	    }
	  }, {
	    key: "onRequisiteEditorAfterEdit",
	    value: function onRequisiteEditorAfterEdit(event) {
	      this.onChangeRequisiteList();
	    }
	  }, {
	    key: "onRequisiteEditorAfterDelete",
	    value: function onRequisiteEditorAfterDelete(event) {
	      this.onChangeRequisiteList();
	    }
	  }, {
	    key: "onChangeRequisiteList",
	    value: function onChangeRequisiteList() {
	      this.doAddressLayout();
	      if (this._tooltip) {
	        this._tooltip.setRequisites(this._requisiteList);
	        this._tooltip.setLoading(false);
	      }
	      this._requisiteEditor.setRequisiteList(this._requisiteList);
	      var requisiteList = this._requisiteList ? this._requisiteList.exportToModel() : null;
	      this._entityInfo.setRequisites(requisiteList);
	      main_core_events.EventEmitter.emit(this, 'onChangeRequisiteList', {
	        entityTypeName: this._entityInfo.getTypeName(),
	        entityId: this._entityInfo.getId(),
	        requisites: this._requisiteList.exportToModel()
	      });
	    }
	  }, {
	    key: "onShowTooltip",
	    value: function onShowTooltip() {
	      if (!this._entityInfo.hasEditRequisiteData()) {
	        if (this._tooltip) {
	          this._tooltip.close();
	          this._showTooltipOnEntityLoad = true;
	        }
	        this.loadEntity();
	      } else if (this.isRequisiteAddressOnly()) {
	        this._tooltip.close();
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new EntityEditorClientRequisites();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorClientRequisites;
	}();

	exports.EntityEditorRequisiteField = EntityEditorRequisiteField;
	exports.EntityEditorRequisiteAddressField = EntityEditorRequisiteAddressField;
	exports.EntityEditorRequisiteController = EntityEditorRequisiteController;
	exports.EntityEditorClientRequisites = EntityEditorClientRequisites;
	exports.EntityEditorRequisiteTooltip = EntityEditorRequisiteTooltip;
	exports.RequisiteList = RequisiteList;
	exports.EntityEditorRequisiteEditor = EntityEditorRequisiteEditor;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm,BX.Main,BX.UI.Dialogs,BX.Crm,BX,BX,BX.Crm,BX.Event));
//# sourceMappingURL=requisite.bundle.js.map
