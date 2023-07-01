this.BX = this.BX || {};
(function (exports,crm_entityEditor_field_address_base,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var EntityEditorUiAddressField = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(EntityEditorUiAddressField, _BX$UI$EntityEditorFi);

	  function EntityEditorUiAddressField() {
	    var _this;

	    babelHelpers.classCallCheck(this, EntityEditorUiAddressField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorUiAddressField).call(this));
	    _this._field = null;
	    _this._autocompleteEnabled = false;
	    _this._restrictionsCallback = null;
	    return _this;
	  }

	  babelHelpers.createClass(EntityEditorUiAddressField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorUiAddressField.prototype), "initialize", this).call(this, id, settings);

	      var params = this._schemeElement.getData();

	      this._autocompleteEnabled = BX.prop.getBoolean(params, "autocompleteEnabled", false);

	      if (!this._autocompleteEnabled) {
	        this._restrictionsCallback = BX.prop.getString(params, "featureRestrictionCallback", '');
	      }

	      settings.enableAutocomplete = this._autocompleteEnabled;
	      settings.hideDefaultAddressType = true;
	      settings.addressZoneConfig = BX.prop.getObject(params, "addressZoneConfig", {});
	      settings.defaultAddressTypeByCategory = BX.prop.getInteger(params, "defaultAddressTypeByCategory", 0);
	      this._field = crm_entityEditor_field_address_base.EntityEditorBaseAddressField.create(id, settings);

	      this._field.setMultiple(true);

	      this._field.setTypesList(BX.prop.getObject(params, "types", {}));

	      main_core_events.EventEmitter.subscribe(this._field, 'onUpdate', this.onAddressListUpdate.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onStartLoadAddress', this.onStartLoadAddress.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onAddressLoaded', this.onAddressLoaded.bind(this));
	      main_core_events.EventEmitter.subscribe(this._field, 'onError', this.onError.bind(this));
	      this._valueNode = null;
	      this._modelTypes = [];
	      this.initializeFromModel();
	    }
	  }, {
	    key: "initializeFromModel",
	    value: function initializeFromModel() {
	      var value = this.prepareValue(this.getValue());
	      this._modelTypes = Object.keys(value);

	      this._field.setValue(value);
	    }
	  }, {
	    key: "prepareValue",
	    value: function prepareValue(value) {
	      return main_core.Type.isPlainObject(value) ? value : {};
	    }
	  }, {
	    key: "onBeforeSubmit",
	    value: function onBeforeSubmit() {
	      if (!main_core.Type.isDomNode(this._valueNode)) {
	        return;
	      }

	      main_core.Dom.clean(this._valueNode);
	      var values = {};

	      var _iterator = _createForOfIteratorHelper(this._modelTypes),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _type = _step.value;
	          values[_type] = "";
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      var _iterator2 = _createForOfIteratorHelper(this._field.getValue()),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var address = _step2.value;

	          if (address.value.length) {
	            values[address.type] = address.value;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      var fieldNamePrefix = this.getName();

	      for (var type in values) {
	        if (!values.hasOwnProperty(type)) {
	          continue;
	        }

	        var value = values[type];
	        var name = '';

	        if (main_core.Type.isStringFilled(value)) {
	          name = "".concat(fieldNamePrefix, "[").concat(type, "]");
	        } else {
	          name = "".concat(fieldNamePrefix, "[").concat(type, "][DELETED]");
	          value = 'Y';
	        }

	        var node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\">"])));
	        node.name = name;
	        node.value = value;
	        main_core.Dom.append(node, this._valueNode);
	      }
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout(options) {
	      this.initializeFromModel();
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorUiAddressField.prototype), "refreshLayout", this).call(this, options);
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (this._hasLayout) {
	        return;
	      }

	      this.ensureWrapperCreated({
	        classNames: ["ui-entity-editor-content-block-field-address crm-entity-widget-content-block"]
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

	      var fieldContainer = this._field.layout(this._mode === BX.UI.EntityEditorMode.edit);

	      fieldContainer.classList.add('ui-entity-editor-content-block');

	      this._wrapper.appendChild(fieldContainer);

	      this._valueNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));

	      this._wrapper.appendChild(this._valueNode);

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
	    key: "createTitleMarker",
	    value: function createTitleMarker() {
	      if (this._mode === BX.UI.EntityEditorMode.view) {
	        return null;
	      }

	      if (this._restrictionsCallback && this._restrictionsCallback.length) {
	        var lockIcon = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral([" <span class=\"tariff-lock\"></span>"])));
	        lockIcon.setAttribute('onclick', this._restrictionsCallback);
	        return lockIcon;
	      }

	      return babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorUiAddressField.prototype), "createTitleMarker", this).call(this);
	    }
	  }, {
	    key: "onAddressListUpdate",
	    value: function onAddressListUpdate() {
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
	    key: "onInitializeEditorControlFactory",
	    value: function onInitializeEditorControlFactory(event) {
	      var data = event.getData();

	      if (data[0]) {
	        data[0].methods["crm_address"] = function (type, controlId, settings) {
	          if (type === "crm_address") {
	            return EntityEditorUiAddressField.create(controlId, settings);
	          }

	          return null;
	        };
	      }

	      event.setData(data);
	    }
	  }, {
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return EntityEditorUiAddressField;
	}(BX.UI.EntityEditorField);
	main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', EntityEditorUiAddressField.onInitializeEditorControlFactory);

	exports.EntityEditorUiAddressField = EntityEditorUiAddressField;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm,BX,BX.Event));
//# sourceMappingURL=address.bundle.js.map
