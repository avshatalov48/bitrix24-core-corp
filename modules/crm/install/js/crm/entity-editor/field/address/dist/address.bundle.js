this.BX = this.BX || {};
(function (exports,crm_entityEditor_field_address_base,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var EntityEditorAddressField = /*#__PURE__*/function (_BX$Crm$EntityEditorF) {
	  babelHelpers.inherits(EntityEditorAddressField, _BX$Crm$EntityEditorF);

	  function EntityEditorAddressField() {
	    var _this;

	    babelHelpers.classCallCheck(this, EntityEditorAddressField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorAddressField).call(this));
	    _this._field = null;
	    _this._isMultiple = null;
	    _this._autocompleteEnabled = false;
	    _this._restrictionsCallback = null;
	    return _this;
	  }

	  babelHelpers.createClass(EntityEditorAddressField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorAddressField.prototype), "initialize", this).call(this, id, settings);

	      var params = this._schemeElement.getData();

	      this._isMultiple = BX.prop.getBoolean(params, "multiple", false);
	      this._autocompleteEnabled = BX.prop.getBoolean(params, "autocompleteEnabled", false);

	      if (!this._autocompleteEnabled) {
	        this._restrictionsCallback = BX.prop.getString(params, "featureRestrictionCallback", '');
	      }

	      settings = main_core.Type.isPlainObject(settings) ? settings : {};
	      settings.crmCompatibilityMode = true;
	      settings.enableAutocomplete = this._autocompleteEnabled;
	      settings.hideDefaultAddressType = this._isMultiple; // hide for multiple addresses only

	      settings.showAddressTypeInViewMode = this._isMultiple; //for multiple addresses only

	      settings.addressZoneConfig = BX.prop.getObject(params, "addressZoneConfig", {});
	      settings.countryId = 0;
	      settings.defaultAddressTypeByCategory = BX.prop.getInteger(params, "defaultAddressTypeByCategory", 0);
	      this._field = crm_entityEditor_field_address_base.EntityEditorBaseAddressField.create(id, settings);

	      this._field.setMultiple(this._isMultiple);

	      if (this._isMultiple) {
	        this._field.setTypesList(BX.prop.getObject(params, "types", {}));
	      }

	      main_core_events.EventEmitter.subscribe(this._field, 'onUpdate', this.onAddressListUpdate.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onStartLoadAddress', this.onStartLoadAddress.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onAddressLoaded', this.onAddressLoaded.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onAddressDataInputting', this.onAddressDataInputting.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onError', this.onError.bind(this));
	      this.initializeFromModel();
	    }
	  }, {
	    key: "setupFromModel",
	    value: function setupFromModel(model, options) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorAddressField.prototype), "setupFromModel", this).call(this, model, options);
	      this.initializeFromModel();
	    }
	  }, {
	    key: "initializeFromModel",
	    value: function initializeFromModel() {
	      this.setAddressList(this.getValue(this._isMultiple ? {} : ""));
	    }
	  }, {
	    key: "setAddressList",
	    value: function setAddressList(addressList) {
	      if (this._field.setValue(addressList)) {
	        this.refreshLayout();
	      }
	    }
	  }, {
	    key: "getCountryId",
	    value: function getCountryId() {
	      return this._field.getCountryId();
	    }
	  }, {
	    key: "setCountryId",
	    value: function setCountryId(countryId) {
	      this._field.setCountryId(countryId);
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (this._hasLayout) {
	        return;
	      }

	      this.ensureWrapperCreated({
	        classNames: ["crm-entity-widget-content-block-field-address"]
	      });
	      this.adjustWrapper();

	      if (!this.isNeedToDisplay()) {
	        this.registerLayout(options);
	        this._hasLayout = true;
	        return;
	      }

	      if (this.isDragEnabled()) {
	        main_core.Dom.append(this.createDragButton(), this._wrapper);
	      }

	      main_core.Dom.append(this.createTitleNode(this.getTitle()), this._wrapper);

	      if (!this.hasValue() && this._mode === BX.UI.EntityEditorMode.view) {
	        main_core.Dom.append(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-entity-editor-content-block\" onclick=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), this.onViewModeClick.bind(this), BX.UI.EntityEditorField.messages.isEmpty), this._wrapper);
	      } else {
	        var fieldContainer = this._field.layout(this._mode === BX.UI.EntityEditorMode.edit);

	        fieldContainer.classList.add('ui-entity-editor-content-block');

	        if (this._mode === BX.UI.EntityEditorMode.view) {
	          main_core.Event.bind(fieldContainer, 'click', this.onViewModeClick.bind(this));
	        }

	        main_core.Dom.append(fieldContainer, this._wrapper);
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
	    key: "reset",
	    value: function reset() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorAddressField.prototype), "reset", this).call(this);
	      this.initializeFromModel();
	    }
	  }, {
	    key: "doClearLayout",
	    value: function doClearLayout(options) {
	      if (BX.prop.getBoolean(options, "release", false)) {
	        this._field.release();
	      } else {
	        this._field.resetView();
	      }
	    }
	  }, {
	    key: "hasContentToDisplay",
	    value: function hasContentToDisplay() {
	      return this.hasValue();
	    }
	  }, {
	    key: "hasValue",
	    value: function hasValue() {
	      if (!main_core.Type.isObject(this._field)) {
	        return false;
	      }

	      return this._isMultiple ? !!this._field.getValue().filter(function (item) {
	        return main_core.Type.isStringFilled(item.value);
	      }).length : !!this._field.getValue();
	    }
	  }, {
	    key: "createTitleMarker",
	    value: function createTitleMarker() {
	      if (this._mode === BX.Crm.EntityEditorMode.view) {
	        return null;
	      }

	      if (this._restrictionsCallback && this._restrictionsCallback.length) {
	        var lockIcon = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral([" <span class=\"tariff-lock\"></span>"])));
	        lockIcon.setAttribute('onclick', this._restrictionsCallback);
	        return lockIcon;
	      }

	      return babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorAddressField.prototype), "createTitleMarker", this).call(this);
	    }
	  }, {
	    key: "rollback",
	    value: function rollback() {
	      this.initializeFromModel();
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (this.isVirtual()) {
	        return;
	      }

	      if (!main_core.Type.isDomNode(this._wrapper)) {
	        return;
	      }

	      if (this._isMultiple) {
	        var fieldNamePrefix = this.getName();

	        var _iterator = _createForOfIteratorHelper(this._field.getValue()),
	            _step;

	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var address = _step.value;
	            var type = address.type;
	            var value = address.value;
	            var name = "".concat(fieldNamePrefix, "[").concat(type, "]");
	            var node = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\">"])));
	            node.name = name;
	            node.value = value;
	            main_core.Dom.append(node, this._wrapper);
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      } else {
	        var _address = this._field.getValue();

	        var _node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\">"])));

	        _node.name = this.getName();
	        _node.value = _address ? _address : "";
	        main_core.Dom.append(_node, this._wrapper);
	      }
	    }
	  }, {
	    key: "onViewModeClick",
	    value: function onViewModeClick() {
	      if (!this.getEditor().isReadOnly()) {
	        this.switchToSingleEditMode();
	      }
	    }
	  }, {
	    key: "onAddressListUpdate",
	    value: function onAddressListUpdate(event) {
	      this.markAsChanged();
	    }
	  }, {
	    key: "onStartLoadAddress",
	    value: function onStartLoadAddress() {
	      var toolPanel = this.getEditor()._toolPanel;

	      if (toolPanel) {
	        toolPanel.setLocked(true);
	      }
	    }
	  }, {
	    key: "onAddressLoaded",
	    value: function onAddressLoaded() {
	      var toolPanel = this.getEditor()._toolPanel;

	      if (toolPanel) {
	        toolPanel.setLocked(false);
	      }
	    }
	  }, {
	    key: "onAddressDataInputting",
	    value: function onAddressDataInputting() {
	      this.markAsChanged();
	    }
	  }, {
	    key: "onError",
	    value: function onError(event) {
	      var data = event.getData();
	      this.showError(data.error);

	      var toolPanel = this.getEditor()._toolPanel;

	      if (toolPanel) {
	        toolPanel.setLocked(false);
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
	  return EntityEditorAddressField;
	}(BX.Crm.EntityEditorField);

	exports.EntityEditorAddressField = EntityEditorAddressField;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm,BX,BX.Event));
//# sourceMappingURL=address.bundle.js.map
