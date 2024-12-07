/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
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
	    _this._isDeleted = false;
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
	      this._config = BX.prop.getObject(this.getSchemeElement().getData(), "config", {});
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
	        'name': this._entityId + '_SECTION',
	        'type': 'section',
	        'enableToggling': false,
	        'transferable': false,
	        'data': {
	          'isRemovable': false,
	          'enableTitle': false,
	          'enableToggling': false
	        },
	        'elements': this.prepareFieldsWithPrefix(this.getSchemeSectionElements(), prefix)
	      };
	      var configId = main_core.Type.isPlainObject(this._config) && this._config.hasOwnProperty("GUID") && main_core.Type.isStringFilled(this._config["GUID"]) ? this._config["GUID"] : entityEditorId;
	      var config = BX.UI.EntityConfig.create(configId, {
	        data: [section],
	        scope: this._editor.getConfigScope(),
	        enableScopeToggle: false,
	        canUpdatePersonalConfiguration: this._editor._config._canUpdatePersonalConfiguration,
	        canUpdateCommonConfiguration: this._editor.canChangeCommonConfiguration(),
	        options: {},
	        signedParams: BX.prop.getString(this._config, 'ENTITY_CONFIG_SIGNED_PARAMS', '')
	      });
	      var availableFields = this.prepareFieldsWithPrefix(BX.clone(BX.prop.getArray(this._config, 'ENTITY_AVAILABLE_FIELDS', [])), prefix);
	      var scheme = BX.UI.EntityScheme.create(entityEditorId, {
	        current: [section],
	        available: availableFields
	      });
	      var entityEditor = BX.UI.EntityEditor.create(entityEditorId, {
	        model: BX.UI.EntityEditorModelFactory.create("", "", {
	          isIdentifiable: false,
	          data: this.getFieldsValues(prefix, values)
	        }),
	        config: config,
	        userFieldManager: null,
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
	        enableShowAlwaysFeauture: this.getEditor().isShowAlwaysFeautureEnabled(),
	        enableVisibilityPolicy: true,
	        enableToolPanel: true,
	        enableBottomPanel: false,
	        enableFieldsContextMenu: true,
	        enablePageTitleControls: false,
	        readOnly: this._mode === BX.UI.EntityEditorMode.view,
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

	      // Set CRM attribute manager
	      var settings = this.getAttributeManagerSettings();
	      if (BX.Type.isPlainObject(settings)) {
	        var attributeManager = BX.Crm.EntityFieldAttributeManager.create(entityEditor.getId() + "_ATTR_MANAGER", {
	          entityTypeId: BX.prop.getInteger(settings, "ENTITY_TYPE_ID", BX.CrmEntityType.enumeration.undefined),
	          entityScope: BX.prop.getString(settings, "ENTITY_SCOPE", ""),
	          isPermitted: BX.prop.getBoolean(settings, "IS_PERMITTED", true),
	          isPhaseDependent: BX.prop.getBoolean(settings, "IS_PHASE_DEPENDENT", true),
	          isAttrConfigButtonHidden: BX.prop.getBoolean(settings, "IS_ATTR_CONFIG_BUTTON_HIDDEN", true),
	          lockScript: BX.prop.getString(settings, "LOCK_SCRIPT", ""),
	          captions: BX.prop.getObject(settings, "CAPTIONS", {}),
	          entityPhases: BX.prop.getArray(settings, 'ENTITY_PHASES', null)
	        });
	        entityEditor.setAttributeManager(attributeManager);
	      }
	      main_core_events.EventEmitter.subscribe(entityEditor, 'onControlChanged', function (event) {
	        if (!_this3.isChanged()) {
	          _this3.markAsChanged();
	        }
	      });
	      this.subscribeEditorEvents(entityEditor, ['onControlMove', 'onFieldModify', 'onFieldModifyAttributeConfigs', 'onControlAdd', 'onControlRemove', 'onSchemeSave']);
	      var container = entityEditor.getContainer();
	      if (main_core.Type.isDomNode(container)) {
	        main_core.Dom.prepend(this.getDeleteButton(id), container);
	      }
	      if (values.hasOwnProperty("DELETED") && values["DELETED"] === 'Y') {
	        this._isDeleted = true;
	        this.layoutDeletedValue(entityEditor, id);
	      }
	      BX.Crm.RequisiteDetailsManager.create({
	        entityEditorId: entityEditorId
	      });
	      return entityEditor;
	    }
	  }, {
	    key: "getCorrespondedControl",
	    value: function getCorrespondedControl(eventCode, controlId, editor) {
	      return eventCode === "add" ? editor.getAvailableControlByCombinedId(controlId) : editor.getControlByCombinedIdRecursive(controlId);
	    }
	  }, {
	    key: "getEditorSchemeSectionElements",
	    value: function getEditorSchemeSectionElements(editor) {
	      var elements = [];
	      var schemeElements = editor.getScheme().getElements();
	      if (main_core.Type.isArray(schemeElements) && schemeElements.length > 0) {
	        var section = schemeElements[0];
	        if (section && section instanceof BX.UI.EntitySchemeElement && section.getType() === "section") {
	          elements = section.getElements();
	        }
	      }
	      return elements;
	    }
	  }, {
	    key: "prepareSectionElementsBySchemeElements",
	    value: function prepareSectionElementsBySchemeElements(schemeElements) {
	      var elements = [];
	      if (main_core.Type.isArray(schemeElements)) {
	        for (var i = 0; i < schemeElements.length; i++) {
	          var element = {
	            "name": schemeElements[i].getName(),
	            "title": schemeElements[i].getTitle(),
	            "type": schemeElements[i].getType(),
	            "required": schemeElements[i].isRequired(),
	            "optionFlags": schemeElements[i].getOptionFlags(),
	            "options": schemeElements[i].getOptions()
	          };
	          elements.push(element);
	        }
	      }
	      return elements;
	    }
	  }, {
	    key: "syncEditorEvent",
	    value: function syncEditorEvent(eventName, target, params) {
	      var _this4 = this;
	      var eventMap = {
	        "onControlAdd": "add",
	        "onControlMove": "move",
	        "onFieldModify": "modify",
	        "onFieldModifyAttributeConfigs": "modifyAttributes",
	        "onControlRemove": "remove",
	        "onSchemeSave": "saveScheme"
	      };
	      if (main_core.Type.isStringFilled(eventName) && eventMap.hasOwnProperty(eventName) && target instanceof BX.UI.EntityEditor) {
	        if (eventMap[eventName] === "saveScheme") {
	          this.setSchemeSectionElements(this.prepareFieldsWithoutPrefix(this.prepareSectionElementsBySchemeElements(this.getEditorSchemeSectionElements(target))));
	          this.setSchemeAvailableElements(this.prepareFieldsWithoutPrefix(this.getEditorAvailableElements(target)));
	        } else if (main_core.Type.isArray(params) && params.length > 1 && main_core.Type.isPlainObject(params[1])) {
	          var eventParams = params[1];
	          var _loop = function _loop() {
	            if (_this4._entityEditorList.hasOwnProperty(index)) {
	              var editor = _this4._entityEditorList[index];
	              if (editor instanceof BX.UI.EntityEditor && editor !== target) {
	                if (eventMap[eventName] === "modify" || eventMap[eventName] === "modifyAttributes") {
	                  setTimeout(function () {
	                    var field = BX.prop.get(eventParams, "field", null);
	                    if (field && field instanceof BX.UI.EntityEditorField) {
	                      var control = _this4.getCorrespondedControl(eventMap[eventName], field.getId(), editor);
	                      if (control) {
	                        var needRefreshTitleLayout = false;
	                        if (eventMap[eventName] === "modifyAttributes") {
	                          var exists = [];
	                          var configs = BX.prop.getArray(eventParams, "attrConfigs", null);
	                          if (main_core.Type.isArray(configs) && configs.length > 0) {
	                            for (var i = 0, length = configs.length; i < length; i++) {
	                              var config = configs[i];
	                              var typeId = BX.prop.getInteger(config, "typeId", BX.UI.EntityFieldAttributeType.undefined);
	                              if (typeId !== BX.UI.EntityFieldAttributeType.undefined) {
	                                exists.push(typeId);
	                                control.getSchemeElement().setAttributeConfiguration(config);
	                              }
	                            }
	                          }
	                          for (var _index in BX.UI.EntityFieldAttributeType) {
	                            if (BX.UI.EntityFieldAttributeType.hasOwnProperty(_index)) {
	                              var _typeId = BX.UI.EntityFieldAttributeType[_index];
	                              if (_typeId !== BX.UI.EntityFieldAttributeType.undefined && exists.indexOf(_typeId) < 0) {
	                                control.getSchemeElement().removeAttributeConfiguration(_typeId);
	                              }
	                            }
	                          }
	                          needRefreshTitleLayout = true;
	                        } else {
	                          var label = BX.prop.getString(eventParams, "label", "");
	                          if (main_core.Type.isStringFilled(label)) {
	                            control.getSchemeElement().setTitle(label);
	                            needRefreshTitleLayout = true;
	                          }
	                        }
	                        if (needRefreshTitleLayout) {
	                          control.refreshTitleLayout();
	                        }
	                      }
	                    }
	                  });
	                } else if (eventParams.hasOwnProperty("control") && main_core.Type.isObject(eventParams["control"])) {
	                  var options = BX.prop.getObject(eventParams, "params", {});
	                  var controlId = eventParams["control"].getId();
	                  var control = _this4.getCorrespondedControl(eventMap[eventName], controlId, editor);
	                  if (control) {
	                    if (eventMap[eventName] === "add") {
	                      setTimeout(function () {
	                        editor.getControlByIndex(0).addChild(control, {
	                          layout: {
	                            forceDisplay: true
	                          },
	                          enableSaving: false,
	                          skipEvents: true
	                        });
	                      });
	                    } else if (eventMap[eventName] === "move") {
	                      var _index2 = BX.prop.getInteger(options, "index", -1);
	                      if (_index2 >= 0) {
	                        setTimeout(function () {
	                          control.getParent().moveChild(control, _index2, {
	                            enableSaving: false,
	                            skipEvents: true
	                          });
	                          editor.processSchemeChange();
	                        });
	                      }
	                    } else if (eventMap[eventName] === "remove") {
	                      setTimeout(function () {
	                        control.hide({
	                          enableSaving: false,
	                          skipEvents: true
	                        });
	                        editor.processSchemeChange();
	                      });
	                    }
	                  }
	                }
	              }
	            }
	          };
	          for (var index in this._entityEditorList) {
	            _loop();
	          }
	        }
	      }
	    }
	  }, {
	    key: "subscribeEditorEvents",
	    value: function subscribeEditorEvents(editor, eventNames) {
	      var _this5 = this;
	      var _loop2 = function _loop2(i) {
	        main_core_events.EventEmitter.subscribe(editor, "BX.UI.EntityEditor:" + eventNames[i], function (event) {
	          _this5.syncEditorEvent(eventNames[i], event.getTarget(), event.getData());
	        });
	      };
	      for (var i = 0; i < eventNames.length; i++) {
	        _loop2(i);
	      }
	    }
	  }, {
	    key: "unsubscribeEditorEvents",
	    value: function unsubscribeEditorEvents(editor) {
	      main_core_events.EventEmitter.unsubscribeAll(editor);
	    }
	  }, {
	    key: "isDeleted",
	    value: function isDeleted() {
	      return this._isDeleted;
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
	        this.unsubscribeEditorEvents(this._entityEditorList[id]);
	        this._isDeleted = true;
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
	      this._entityEditorList[id] = this.createEntityEditor(id, {}, this.prepareEntityEditorContext());
	      this.markAsChanged();
	      return this._entityEditorList[id];
	    }
	  }, {
	    key: "getEditors",
	    value: function getEditors() {
	      return this._entityEditorList;
	    }
	  }, {
	    key: "getSchemeSection",
	    value: function getSchemeSection() {
	      var section = null;
	      var entityScheme = BX.prop.getArray(this._config, 'ENTITY_SCHEME', []);
	      if (main_core.Type.isArray(entityScheme) && entityScheme.length > 0) {
	        var column = entityScheme[0];
	        if (main_core.Type.isPlainObject(column) && column.hasOwnProperty("elements") && main_core.Type.isArray(column["elements"]) && column["elements"].length > 0 && main_core.Type.isPlainObject(column["elements"][0])) {
	          section = column["elements"][0];
	        }
	      }
	      return section;
	    }
	  }, {
	    key: "getEditorAvailableElements",
	    value: function getEditorAvailableElements(editor) {
	      var elements = [];
	      if (editor && editor instanceof BX.UI.EntityEditor) {
	        var schemeElements = editor.getAvailableSchemeElements();
	        for (var i = 0; i < schemeElements.length; i++) {
	          var element = {
	            "name": schemeElements[i].getName(),
	            "title": schemeElements[i].getTitle(),
	            "type": schemeElements[i].getType(),
	            "required": schemeElements[i].isRequired()
	          };
	          elements.push(element);
	        }
	      }
	      return elements;
	    }
	  }, {
	    key: "setSchemeAvailableElements",
	    value: function setSchemeAvailableElements(availableElements) {
	      this._config["ENTITY_AVAILABLE_FIELDS"] = availableElements;
	    }
	  }, {
	    key: "setSchemeSectionElements",
	    value: function setSchemeSectionElements(sectionElements) {
	      var section = null;
	      if (!main_core.Type.isArray(sectionElements)) {
	        sectionElements = [];
	      }
	      var entityScheme = BX.prop.getArray(this._config, 'ENTITY_SCHEME', []);
	      if (main_core.Type.isArray(entityScheme) && entityScheme.length > 0) {
	        var column = entityScheme[0];
	        if (main_core.Type.isPlainObject(column) && column.hasOwnProperty("elements") && main_core.Type.isArray(column["elements"]) && column["elements"].length > 0 && main_core.Type.isPlainObject(column["elements"][0])) {
	          section = column["elements"][0];
	        }
	      }
	      if (main_core.Type.isPlainObject(section)) {
	        section["elements"] = sectionElements;
	      }
	    }
	  }, {
	    key: "getSchemeSectionElements",
	    value: function getSchemeSectionElements() {
	      var elements = [];
	      var section = this.getSchemeSection();
	      if (section && section.hasOwnProperty("elements") && main_core.Type.isArray(section["elements"])) {
	        elements = main_core.Runtime.clone(section["elements"]);
	      }
	      return elements;
	    }
	  }, {
	    key: "setSchemeSectionElements",
	    value: function setSchemeSectionElements(elements) {
	      var section = this.getSchemeSection();
	      if (section) {
	        section["elements"] = main_core.Runtime.clone(elements);
	      }
	    }
	  }, {
	    key: "getFields",
	    value: function getFields(prefix) {
	      var fields = BX.clone(BX.prop.getArray(this.getSchemeElement().getData(), 'fields', []));
	      return this.prepareFieldsWithPrefix(fields, prefix);
	    }
	  }, {
	    key: "prepareFieldsWithPrefix",
	    value: function prepareFieldsWithPrefix(fields, prefix) {
	      for (var index = 0; index < fields.length; index++) {
	        fields[index].name = this.getFieldName(fields[index].name, prefix);
	      }
	      return fields;
	    }
	  }, {
	    key: "prepareFieldsWithoutPrefix",
	    value: function prepareFieldsWithoutPrefix(fields) {
	      for (var index = 0; index < fields.length; index++) {
	        if (main_core.Type.isStringFilled(fields[index].name)) {
	          var matches = fields[index].name.match(/\[(\w+)]$/);
	          if (matches && matches.length > 1 && main_core.Type.isStringFilled(matches[1])) {
	            fields[index].name = matches[1];
	          }
	        }
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
	              this._entityEditorList[item.ID] = this.createEntityEditor(item.ID, item, this.prepareEntityEditorContext());
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
	  }, {
	    key: "getAttributeManagerSettings",
	    value: function getAttributeManagerSettings() {
	      return BX.prop.getObject(this._config, "ATTRIBUTE_CONFIG", null);
	    }
	  }, {
	    key: "getResolverProperty",
	    value: function getResolverProperty() {
	      return BX.prop.getObject(this._settings, "resolverProperty", null);
	    }
	  }, {
	    key: "getActiveControlById",
	    value: function getActiveControlById(id) {
	      for (var pseudoId in this._entityEditorList) {
	        if (this._entityEditorList.hasOwnProperty(pseudoId)) {
	          var control = this._entityEditorList[pseudoId].getActiveControlById(id, true);
	          if (control) {
	            return control;
	          }
	        }
	      }
	    }
	  }, {
	    key: "validate",
	    value: function validate(result) {
	      if (this._isDeleted || this._mode !== BX.UI.EntityEditorMode.edit) {
	        return true;
	      }
	      var validator = BX.UI.EntityAsyncValidator.create();
	      for (var pseudoId in this._entityEditorList) {
	        if (this._entityEditorList.hasOwnProperty(pseudoId)) {
	          var field = this._entityEditorList[pseudoId];
	          if (field.getMode() !== BX.UI.EntityEditorMode.edit) {
	            continue;
	          }
	          validator.addResult(field.validate(result));
	        }
	      }
	      return validator.validate();
	    }
	  }, {
	    key: "prepareEntityEditorContext",
	    value: function prepareEntityEditorContext() {
	      return {};
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
