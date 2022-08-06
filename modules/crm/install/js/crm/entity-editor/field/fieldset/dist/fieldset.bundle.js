this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var EntityEditorFieldsetField = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(EntityEditorFieldsetField, _BX$UI$EntityEditorFi);

	  function EntityEditorFieldsetField() {
	    var _this;

	    babelHelpers.classCallCheck(this, EntityEditorFieldsetField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityEditorFieldsetField).call(this));
	    _this._entityEditorList = {};
	    _this._entityId = null;
	    _this._fieldsetContainer = null;
	    _this._addEmptyValue = false;
	    _this._nextIndex = 0;
	    return _this;
	  }

	  babelHelpers.createClass(EntityEditorFieldsetField, [{
	    key: "doInitialize",
	    value: function doInitialize() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorFieldsetField.prototype), "doInitialize", this).call(this);
	      this._entityId = this._editor.getId() + '_' + this.getId() + '_fields';
	      this._addEmptyValue = this.getDataBooleanParam("addEmptyValue", false);
	      var nextIndex = BX.prop.getInteger(this.getSchemeElement().getData(), "nextIndex", 0);
	      this._nextIndex = nextIndex > 0 ? nextIndex : 0;
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      var _this2 = this;

	      if (this._hasLayout) {
	        return;
	      }

	      this.ensureWrapperCreated({
	        classNames: ["ui-entity-editor-content-block-field-fieldset-wrapper"]
	      });
	      this.adjustWrapper();

	      if (!this.isNeedToDisplay()) {
	        this.registerLayout(options);
	        this._hasLayout = true;
	        return;
	      }

	      this._fieldsetContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-entity-editor-container\"></div>"])));
	      main_core.Dom.append(this._fieldsetContainer, this._wrapper);

	      if (this._mode === BX.UI.EntityEditorMode.edit) {
	        var addButtonPanel = this.getAddButton();
	        main_core.Dom.append(addButtonPanel, this._wrapper);
	      }

	      setTimeout(function () {
	        return _this2.initializeExistedValues();
	      }, 0);
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EntityEditorFieldsetField.prototype), "clearLayout", this).call(this);
	      this._entityEditorList = {};
	    }
	  }, {
	    key: "createEntityEditor",
	    value: function createEntityEditor(id) {
	      var _this3 = this;

	      var values = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var context = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      var containerId = this._entityId + '_container_' + id;
	      main_core.Dom.append(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div id=\"", "\" class=\"ui-entity-editor-field-fieldset\"></div>"])), containerId), this._fieldsetContainer);
	      var entityEditorId = this._entityId + '_' + id;
	      var prefix = this.getName() + '[' + id + ']';
	      var section = {
	        'name': entityEditorId + '_SECTION',
	        'type': 'section',
	        'enableToggling': false,
	        'transferable': false,
	        'data': {
	          'isRemovable': false,
	          'enableTitle': false,
	          'enableToggling': false
	        },
	        'elements': this.getFields(prefix)
	      };
	      var config = BX.UI.EntityConfig.create(entityEditorId, {
	        data: [section],
	        scope: "C",
	        enableScopeToggle: false,
	        canUpdatePersonalConfiguration: false,
	        canUpdateCommonConfiguration: false,
	        options: []
	      });
	      var scheme = BX.UI.EntityScheme.create(entityEditorId, {
	        current: [section],
	        available: []
	      });
	      var entityEditor = BX.UI.EntityEditor.create(entityEditorId, {
	        model: BX.UI.EntityEditorModelFactory.create("", "", {
	          isIdentifiable: false,
	          data: this.getFieldsValues(prefix, values)
	        }),
	        config: config,
	        scheme: scheme,
	        context: context,
	        containerId: containerId,
	        serviceUrl: this._editor.getServiceUrl(),
	        entityTypeName: "",
	        entityId: 0,
	        validators: [],
	        controllers: [],
	        detailManagerId: "",
	        initialMode: BX.UI.EntityEditorMode.getName(this._mode),
	        enableModeToggle: true,
	        enableConfigControl: false,
	        enableVisibilityPolicy: true,
	        enableToolPanel: true,
	        enableBottomPanel: false,
	        enableFieldsContextMenu: true,
	        enablePageTitleControls: false,
	        readOnly: this._mode == BX.UI.EntityEditorMode.view,
	        enableAjaxForm: false,
	        enableRequiredUserFieldCheck: true,
	        enableSectionEdit: false,
	        enableSectionCreation: false,
	        enableSectionDragDrop: true,
	        enableFieldDragDrop: true,
	        enableSettingsForAll: false,
	        enableContextDataLayout: false,
	        formTagName: 'div',
	        externalContextId: "",
	        contextId: "",
	        options: {
	          'show_always': 'Y'
	        },
	        ajaxData: [],
	        isEmbedded: true
	      });
	      entityEditor._enableCloseConfirmation = false;
	      main_core_events.EventEmitter.subscribe(entityEditor, 'onControlChanged', function (event) {
	        if (!_this3.isChanged()) {
	          _this3.markAsChanged();
	        }
	      });
	      var container = entityEditor.getContainer();

	      if (main_core.Type.isDomNode(container)) {
	        main_core.Dom.prepend(this.getDeleteButton(id), container);
	      }

	      if (values.hasOwnProperty("DELETED") && values["DELETED"] === 'Y') {
	        this.layoutDeletedValue(entityEditor, id);
	      }

	      return entityEditor;
	    }
	  }, {
	    key: "layoutDeletedValue",
	    value: function layoutDeletedValue(entityEditor, id) {
	      if (entityEditor instanceof BX.UI.EntityEditor) {
	        var container = entityEditor.getContainer();

	        if (main_core.Type.isDomNode(container)) {
	          container.style.display = 'none';
	          var inputName = "".concat(this.getName(), "[").concat(id, "][DELETED]");

	          if (!container.querySelector("input[name=\"".concat(inputName, "\"]"))) {
	            main_core.Dom.append(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "\" value=\"Y\" />"])), inputName), container);
	          }
	        }
	      }
	    }
	  }, {
	    key: "onDeleteButtonClick",
	    value: function onDeleteButtonClick(id) {
	      if (this._entityEditorList.hasOwnProperty(id)) {
	        this.layoutDeletedValue(this._entityEditorList[id], id);
	        this.markAsChanged();
	      }
	    }
	  }, {
	    key: "onAddButtonClick",
	    value: function onAddButtonClick() {
	      this.addEmptyValue();
	    }
	  }, {
	    key: "addEmptyValue",
	    value: function addEmptyValue(options) {
	      var value = this.getValue();
	      var id = 'n' + this._nextIndex++;
	      value.push({
	        'ID': id
	      });
	      this.getModel().setField(this.getName(), value);
	      this._entityEditorList[id] = this.createEntityEditor(id);
	      this.markAsChanged();
	      return this._entityEditorList[id];
	    }
	  }, {
	    key: "getEditors",
	    value: function getEditors() {
	      return this._entityEditorList;
	    }
	  }, {
	    key: "getFields",
	    value: function getFields(prefix) {
	      var fields = BX.clone(BX.prop.getArray(this.getSchemeElement().getData(), 'fields', []));

	      for (var index = 0; index < fields.length; index++) {
	        fields[index].name = this.getFieldName(fields[index].name, prefix);
	      }

	      return fields;
	    }
	  }, {
	    key: "getFieldsValues",
	    value: function getFieldsValues(prefix, values) {
	      var result = {};

	      for (var fieldId in values) {
	        result[this.getFieldName(fieldId, prefix)] = values[fieldId];
	      }

	      return result;
	    }
	  }, {
	    key: "getFieldName",
	    value: function getFieldName(originalName, prefix) {
	      return prefix + '[' + originalName + ']';
	    }
	  }, {
	    key: "getDeleteButton",
	    value: function getDeleteButton(id) {
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"ui-entity-editor-field-fieldset-delete\">\n\t\t\t<span class=\"ui-link ui-link-secondary\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t</div>"])), this.onDeleteButtonClick.bind(this, id), main_core.Loc.getMessage('UI_ENTITY_EDITOR_DELETE'));
	    }
	  }, {
	    key: "getAddButton",
	    value: function getAddButton() {
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"ui-entity-editor-content-block-add-field\">\n\t\t\t<span class=\"ui-entity-card-content-add-field\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t</div>"])), this.onAddButtonClick.bind(this), main_core.Loc.getMessage('UI_ENTITY_EDITOR_ADD'));
	    }
	  }, {
	    key: "initializeExistedValues",
	    value: function initializeExistedValues() {
	      var existedItems = this.getValue();

	      if (existedItems.length) {
	        var _iterator = _createForOfIteratorHelper(existedItems),
	            _step;

	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var item = _step.value;

	            if (!this._entityEditorList[item.ID]) {
	              this._entityEditorList[item.ID] = this.createEntityEditor(item.ID, item);
	            }
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      } else if (this._mode === BX.UI.EntityEditorMode.edit && this._addEmptyValue) {
	        this.addEmptyValue();
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
	  return EntityEditorFieldsetField;
	}(BX.UI.EntityEditorField);
	main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', function (event) {
	  var data = event.getData();

	  if (data[0]) {
	    data[0].methods["fieldset"] = function (type, controlId, settings) {
	      if (type === "fieldset") {
	        return EntityEditorFieldsetField.create(controlId, settings);
	      }

	      return null;
	    };
	  }

	  event.setData(data);
	});

	exports.EntityEditorFieldsetField = EntityEditorFieldsetField;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event));
//# sourceMappingURL=fieldset.bundle.js.map
