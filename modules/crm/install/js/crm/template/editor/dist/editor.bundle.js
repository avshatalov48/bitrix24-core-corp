this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_entitySelector,main_core_events,ui_designTokens,ui_entitySelector,main_core,main_popup,ui_buttons) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _menu = /*#__PURE__*/new WeakMap();
	var _bindElement = /*#__PURE__*/new WeakMap();
	var _isTextItemFirst = /*#__PURE__*/new WeakMap();
	var _onEditorItemClick = /*#__PURE__*/new WeakMap();
	var _onTextItemClick = /*#__PURE__*/new WeakMap();
	var _getMenuPopup = /*#__PURE__*/new WeakSet();
	var _getItems = /*#__PURE__*/new WeakSet();
	var _getEditorItem = /*#__PURE__*/new WeakSet();
	var _getTextItem = /*#__PURE__*/new WeakSet();
	var _getItemTitle = /*#__PURE__*/new WeakSet();
	var MenuPopup = /*#__PURE__*/function () {
	  function MenuPopup(_ref) {
	    var bindElement = _ref.bindElement,
	      isTextItemFirst = _ref.isTextItemFirst,
	      onEditorItemClick = _ref.onEditorItemClick,
	      onTextItemClick = _ref.onTextItemClick;
	    babelHelpers.classCallCheck(this, MenuPopup);
	    _classPrivateMethodInitSpec(this, _getItemTitle);
	    _classPrivateMethodInitSpec(this, _getTextItem);
	    _classPrivateMethodInitSpec(this, _getEditorItem);
	    _classPrivateMethodInitSpec(this, _getItems);
	    _classPrivateMethodInitSpec(this, _getMenuPopup);
	    _classPrivateFieldInitSpec(this, _menu, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _bindElement, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _isTextItemFirst, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _onEditorItemClick, {
	      writable: true,
	      value: function value() {}
	    });
	    _classPrivateFieldInitSpec(this, _onTextItemClick, {
	      writable: true,
	      value: function value() {}
	    });
	    babelHelpers.classPrivateFieldSet(this, _bindElement, bindElement);
	    babelHelpers.classPrivateFieldSet(this, _isTextItemFirst, isTextItemFirst);
	    babelHelpers.classPrivateFieldSet(this, _onEditorItemClick, onEditorItemClick);
	    babelHelpers.classPrivateFieldSet(this, _onTextItemClick, onTextItemClick);
	  }
	  babelHelpers.createClass(MenuPopup, [{
	    key: "show",
	    value: function show() {
	      _classPrivateMethodGet(this, _getMenuPopup, _getMenuPopup2).call(this).show();
	    }
	  }]);
	  return MenuPopup;
	}();
	function _getMenuPopup2() {
	  if (babelHelpers.classPrivateFieldGet(this, _menu) === null) {
	    babelHelpers.classPrivateFieldSet(this, _menu, main_popup.MenuManager.create({
	      id: 'crm-template-editor-placeholder-selector',
	      bindElement: babelHelpers.classPrivateFieldGet(this, _bindElement),
	      autoHide: true,
	      offsetLeft: 20,
	      angle: true,
	      closeByEsc: false,
	      cacheable: false,
	      items: _classPrivateMethodGet(this, _getItems, _getItems2).call(this)
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _menu);
	}
	function _getItems2() {
	  var editorItem = _classPrivateMethodGet(this, _getEditorItem, _getEditorItem2).call(this);
	  var textItem = _classPrivateMethodGet(this, _getTextItem, _getTextItem2).call(this);
	  if (babelHelpers.classPrivateFieldGet(this, _isTextItemFirst)) {
	    return [textItem, editorItem];
	  }
	  return [editorItem, textItem];
	}
	function _getEditorItem2() {
	  var _this = this;
	  return {
	    html: _classPrivateMethodGet(this, _getItemTitle, _getItemTitle2).call(this, 'CRM_TEMPLATE_EDITOR_SELECT_FIELD'),
	    onclick: function onclick() {
	      babelHelpers.classPrivateFieldGet(_this, _onEditorItemClick).call(_this, babelHelpers.classPrivateFieldGet(_this, _bindElement));
	    }
	  };
	}
	function _getTextItem2() {
	  var _this2 = this;
	  var code = babelHelpers.classPrivateFieldGet(this, _isTextItemFirst) ? 'CRM_TEMPLATE_EDITOR_UPDATE_TEXT' : 'CRM_TEMPLATE_EDITOR_CREATE_TEXT';
	  return {
	    html: _classPrivateMethodGet(this, _getItemTitle, _getItemTitle2).call(this, code),
	    onclick: function onclick() {
	      _classPrivateMethodGet(_this2, _getMenuPopup, _getMenuPopup2).call(_this2).close();
	      babelHelpers.classPrivateFieldGet(_this2, _onTextItemClick).call(_this2, babelHelpers.classPrivateFieldGet(_this2, _bindElement));
	    }
	  };
	}
	function _getItemTitle2(code) {
	  var placeholder = '<span class="crm-template-editor-placeholder-selector-menu-item">#ITEM_TEXT#</span>';
	  return placeholder.replace('#ITEM_TEXT#', main_core.Text.encode(main_core.Loc.getMessage(code)));
	}

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _popup = /*#__PURE__*/new WeakMap();
	var _input = /*#__PURE__*/new WeakMap();
	var _bindElement$1 = /*#__PURE__*/new WeakMap();
	var _value = /*#__PURE__*/new WeakMap();
	var _onApply = /*#__PURE__*/new WeakMap();
	var _getPopup = /*#__PURE__*/new WeakSet();
	var _getContent = /*#__PURE__*/new WeakSet();
	var _bindInputEvents = /*#__PURE__*/new WeakSet();
	var _getMenuButtons = /*#__PURE__*/new WeakSet();
	var _getApplyButton = /*#__PURE__*/new WeakSet();
	var _adjustButtonState = /*#__PURE__*/new WeakSet();
	var _getApplyButtonText = /*#__PURE__*/new WeakSet();
	var _onApplyButtonClick = /*#__PURE__*/new WeakSet();
	var _getApplyButtonInstance = /*#__PURE__*/new WeakSet();
	var _getCancelButton = /*#__PURE__*/new WeakSet();
	var _setCursorToEnd = /*#__PURE__*/new WeakSet();
	var TextPopup = /*#__PURE__*/function () {
	  function TextPopup(_ref) {
	    var bindElement = _ref.bindElement,
	      _value2 = _ref.value,
	      onApply = _ref.onApply;
	    babelHelpers.classCallCheck(this, TextPopup);
	    _classPrivateMethodInitSpec$1(this, _setCursorToEnd);
	    _classPrivateMethodInitSpec$1(this, _getCancelButton);
	    _classPrivateMethodInitSpec$1(this, _getApplyButtonInstance);
	    _classPrivateMethodInitSpec$1(this, _onApplyButtonClick);
	    _classPrivateMethodInitSpec$1(this, _getApplyButtonText);
	    _classPrivateMethodInitSpec$1(this, _adjustButtonState);
	    _classPrivateMethodInitSpec$1(this, _getApplyButton);
	    _classPrivateMethodInitSpec$1(this, _getMenuButtons);
	    _classPrivateMethodInitSpec$1(this, _bindInputEvents);
	    _classPrivateMethodInitSpec$1(this, _getContent);
	    _classPrivateMethodInitSpec$1(this, _getPopup);
	    _classPrivateFieldInitSpec$1(this, _popup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _input, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _bindElement$1, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _value, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _onApply, {
	      writable: true,
	      value: function value() {}
	    });
	    babelHelpers.classPrivateFieldSet(this, _bindElement$1, bindElement);
	    babelHelpers.classPrivateFieldSet(this, _value, _value2);
	    babelHelpers.classPrivateFieldSet(this, _onApply, onApply);
	  }
	  babelHelpers.createClass(TextPopup, [{
	    key: "destroy",
	    value: function destroy() {
	      var _babelHelpers$classPr;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _popup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.destroy();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      _classPrivateMethodGet$1(this, _getPopup, _getPopup2).call(this).show();
	    }
	  }]);
	  return TextPopup;
	}();
	function _getPopup2() {
	  var _this = this;
	  if (babelHelpers.classPrivateFieldGet(this, _popup) === null) {
	    babelHelpers.classPrivateFieldSet(this, _popup, main_popup.PopupWindowManager.create('crm-template-editor-text-popup', babelHelpers.classPrivateFieldGet(this, _bindElement$1), {
	      autoHide: true,
	      content: _classPrivateMethodGet$1(this, _getContent, _getContent2).call(this),
	      closeByEsc: true,
	      closeIcon: false,
	      buttons: _classPrivateMethodGet$1(this, _getMenuButtons, _getMenuButtons2).call(this),
	      cacheable: false
	    }));
	    babelHelpers.classPrivateFieldGet(this, _popup).subscribe('onShow', function () {
	      babelHelpers.classPrivateFieldGet(_this, _input).focus();
	      _classPrivateMethodGet$1(_this, _setCursorToEnd, _setCursorToEnd2).call(_this);
	    });
	  }
	  return babelHelpers.classPrivateFieldGet(this, _popup);
	}
	function _getContent2() {
	  var content = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-text-popup-wrapper\"></div>"])));
	  babelHelpers.classPrivateFieldSet(this, _input, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input \n\t\t\t\ttype=\"text\" \n\t\t\t\tvalue=\"", "\"\n\t\t\t\tplaceholder=\"", "\n\t\t\t\">\n\t\t"])), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _value)), main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_SELECT_FIELD_PLACEHOLDER')));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _input), content);
	  _classPrivateMethodGet$1(this, _bindInputEvents, _bindInputEvents2).call(this);
	  return content;
	}
	function _bindInputEvents2() {
	  var _this2 = this;
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _input), 'keyup', function (event) {
	    var button = _classPrivateMethodGet$1(_this2, _getApplyButtonInstance, _getApplyButtonInstance2).call(_this2);
	    if (!button) {
	      return;
	    }
	    var value = event.target.value;
	    _classPrivateMethodGet$1(_this2, _adjustButtonState, _adjustButtonState2).call(_this2, button, value);
	  });
	}
	function _getMenuButtons2() {
	  return [_classPrivateMethodGet$1(this, _getApplyButton, _getApplyButton2).call(this), _classPrivateMethodGet$1(this, _getCancelButton, _getCancelButton2).call(this)];
	}
	function _getApplyButton2() {
	  var _this3 = this;
	  var button = new ui_buttons.Button({
	    id: 'apply-button',
	    text: _classPrivateMethodGet$1(this, _getApplyButtonText, _getApplyButtonText2).call(this),
	    className: 'ui-btn ui-btn-xs ui-btn-primary ui-btn-round',
	    onclick: function onclick() {
	      _classPrivateMethodGet$1(_this3, _onApplyButtonClick, _onApplyButtonClick2).call(_this3);
	    }
	  });
	  var _babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _input),
	    value = _babelHelpers$classPr2.value;
	  _classPrivateMethodGet$1(this, _adjustButtonState, _adjustButtonState2).call(this, button, value);
	  return button;
	}
	function _adjustButtonState2(button, value) {
	  button.setState(main_core.Type.isStringFilled(value) && main_core.Type.isStringFilled(value.trim()) ? ui_buttons.ButtonState.ACTIVE : ui_buttons.ButtonState.DISABLED);
	}
	function _getApplyButtonText2() {
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _value))) {
	    return main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_UPDATE');
	  }
	  return main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_ADD');
	}
	function _onApplyButtonClick2() {
	  var button = _classPrivateMethodGet$1(this, _getApplyButtonInstance, _getApplyButtonInstance2).call(this);
	  if (button.getState() !== ui_buttons.ButtonState.ACTIVE) {
	    return;
	  }
	  this.destroy();
	  var _babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _input),
	    value = _babelHelpers$classPr3.value;
	  babelHelpers.classPrivateFieldGet(this, _bindElement$1).textContent = main_core.Text.encode(value);
	  babelHelpers.classPrivateFieldGet(this, _onApply).call(this, value.trim());
	}
	function _getApplyButtonInstance2() {
	  return babelHelpers.classPrivateFieldGet(this, _popup).getButton('apply-button');
	}
	function _getCancelButton2() {
	  var _this4 = this;
	  return new ui_buttons.Button({
	    text: main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_TEXT_POPUP_CANCEL'),
	    className: 'ui-btn ui-btn-xs ui-btn-light ui-btn-round',
	    onclick: function onclick() {
	      _this4.destroy();
	    }
	  });
	}
	function _setCursorToEnd2() {
	  var length = babelHelpers.classPrivateFieldGet(this, _input).value.length;
	  babelHelpers.classPrivateFieldGet(this, _input).selectionStart = length;
	  babelHelpers.classPrivateFieldGet(this, _input).selectionEnd = length;
	}

	var _templateObject$1, _templateObject2$1, _templateObject3, _templateObject4, _templateObject5;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var UPDATE_ACTION = 'update';
	var DELETE_ACTION = 'delete';
	var HEADER_POSITION = 'HEADER';
	var PREVIEW_POSITION = 'PREVIEW';
	var FOOTER_POSITION = 'FOOTER';
	var _id = /*#__PURE__*/new WeakMap();
	var _target = /*#__PURE__*/new WeakMap();
	var _entityTypeId = /*#__PURE__*/new WeakMap();
	var _entityId = /*#__PURE__*/new WeakMap();
	var _categoryId = /*#__PURE__*/new WeakMap();
	var _canUseFieldsDialog = /*#__PURE__*/new WeakMap();
	var _canUseFieldValueInput = /*#__PURE__*/new WeakMap();
	var _headerContainerEl = /*#__PURE__*/new WeakMap();
	var _bodyContainerEl = /*#__PURE__*/new WeakMap();
	var _footerContainerEl = /*#__PURE__*/new WeakMap();
	var _placeHoldersDialogDefaultOptions = /*#__PURE__*/new WeakMap();
	var _headerRaw = /*#__PURE__*/new WeakMap();
	var _bodyRaw = /*#__PURE__*/new WeakMap();
	var _footerRaw = /*#__PURE__*/new WeakMap();
	var _popupMenu = /*#__PURE__*/new WeakMap();
	var _inputPopup = /*#__PURE__*/new WeakMap();
	var _createContainer = /*#__PURE__*/new WeakSet();
	var _createContainerWithSelectors = /*#__PURE__*/new WeakSet();
	var _onApplyInputPopup = /*#__PURE__*/new WeakSet();
	var _getInputContainer = /*#__PURE__*/new WeakSet();
	var _getPlaceholders = /*#__PURE__*/new WeakSet();
	var _prepareDlgOptions = /*#__PURE__*/new WeakSet();
	var _adjustFilledPlaceholders = /*#__PURE__*/new WeakSet();
	var _deleteFromFilledPlaceholders = /*#__PURE__*/new WeakSet();
	var _updateForFilledPlaceholders = /*#__PURE__*/new WeakSet();
	var _getFilledPlaceholderByElement = /*#__PURE__*/new WeakSet();
	var _getPlaceholderIdByElement = /*#__PURE__*/new WeakSet();
	var _getFilledPlaceholderById = /*#__PURE__*/new WeakSet();
	var _getPlainText = /*#__PURE__*/new WeakSet();
	var _getRawTextByPosition = /*#__PURE__*/new WeakSet();
	var _assertValidParams = /*#__PURE__*/new WeakSet();
	var Editor = /*#__PURE__*/function () {
	  // @todo replace this variables with a generic container

	  function Editor(_params) {
	    var _params$canUseFieldsD, _params$canUseFieldVa;
	    babelHelpers.classCallCheck(this, Editor);
	    _classPrivateMethodInitSpec$2(this, _assertValidParams);
	    _classPrivateMethodInitSpec$2(this, _getRawTextByPosition);
	    _classPrivateMethodInitSpec$2(this, _getPlainText);
	    _classPrivateMethodInitSpec$2(this, _getFilledPlaceholderById);
	    _classPrivateMethodInitSpec$2(this, _getPlaceholderIdByElement);
	    _classPrivateMethodInitSpec$2(this, _getFilledPlaceholderByElement);
	    _classPrivateMethodInitSpec$2(this, _updateForFilledPlaceholders);
	    _classPrivateMethodInitSpec$2(this, _deleteFromFilledPlaceholders);
	    _classPrivateMethodInitSpec$2(this, _adjustFilledPlaceholders);
	    _classPrivateMethodInitSpec$2(this, _prepareDlgOptions);
	    _classPrivateMethodInitSpec$2(this, _getPlaceholders);
	    _classPrivateMethodInitSpec$2(this, _getInputContainer);
	    _classPrivateMethodInitSpec$2(this, _onApplyInputPopup);
	    _classPrivateMethodInitSpec$2(this, _createContainerWithSelectors);
	    _classPrivateMethodInitSpec$2(this, _createContainer);
	    _classPrivateFieldInitSpec$2(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _target, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _categoryId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _canUseFieldsDialog, {
	      writable: true,
	      value: true
	    });
	    _classPrivateFieldInitSpec$2(this, _canUseFieldValueInput, {
	      writable: true,
	      value: true
	    });
	    babelHelpers.defineProperty(this, "placeholders", []);
	    babelHelpers.defineProperty(this, "filledPlaceholders", []);
	    babelHelpers.defineProperty(this, "onSelect", function () {});
	    _classPrivateFieldInitSpec$2(this, _headerContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _bodyContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _footerContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _placeHoldersDialogDefaultOptions, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _headerRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _bodyRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _footerRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _popupMenu, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _inputPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateMethodGet$2(this, _assertValidParams, _assertValidParams2).call(this, _params);
	    babelHelpers.classPrivateFieldSet(this, _id, _params.id || "crm-template-editor-".concat(main_core.Text.getRandom()));
	    babelHelpers.classPrivateFieldSet(this, _target, _params.target);
	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, _params.entityTypeId);
	    babelHelpers.classPrivateFieldSet(this, _entityId, _params.entityId);
	    babelHelpers.classPrivateFieldSet(this, _categoryId, main_core.Type.isNumber(_params.categoryId) ? _params.categoryId : null);
	    this.onSelect = _params.onSelect;
	    babelHelpers.classPrivateFieldSet(this, _canUseFieldsDialog, Boolean((_params$canUseFieldsD = _params.canUseFieldsDialog) !== null && _params$canUseFieldsD !== void 0 ? _params$canUseFieldsD : true));
	    babelHelpers.classPrivateFieldSet(this, _canUseFieldValueInput, Boolean((_params$canUseFieldVa = _params.canUseFieldValueInput) !== null && _params$canUseFieldVa !== void 0 ? _params$canUseFieldVa : true));
	    this.onPlaceholderClick = this.onPlaceholderClick.bind(this);
	    this.onShowInputPopup = this.onShowInputPopup.bind(this);
	    babelHelpers.classPrivateFieldSet(this, _placeHoldersDialogDefaultOptions, {
	      multiple: false,
	      showAvatars: false,
	      dropdownMode: true,
	      compactView: true,
	      enableSearch: true,
	      tagSelectorOptions: {
	        textBoxWidth: '100%'
	      },
	      entities: [{
	        id: 'placeholder',
	        options: {
	          entityTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	          entityId: babelHelpers.classPrivateFieldGet(this, _entityId),
	          categoryId: babelHelpers.classPrivateFieldGet(this, _categoryId)
	        }
	      }]
	    });
	    _classPrivateMethodGet$2(this, _createContainer, _createContainer2).call(this);
	  }
	  babelHelpers.createClass(Editor, [{
	    key: "setPlaceholders",
	    value: function setPlaceholders(placeholders) {
	      this.placeholders = placeholders;
	      return this;
	    }
	  }, {
	    key: "setFilledPlaceholders",
	    value: function setFilledPlaceholders(filledPlaceholders) {
	      this.filledPlaceholders = filledPlaceholders;
	      return this;
	    } // region Public methods
	  }, {
	    key: "setHeader",
	    value: function setHeader(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _headerRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet$2(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _headerContainerEl));
	    }
	  }, {
	    key: "setBody",
	    value: function setBody(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _bodyRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet$2(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _bodyContainerEl));
	    }
	  }, {
	    key: "setFooter",
	    value: function setFooter(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _footerRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet$2(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _footerContainerEl));
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      if (this.placeholders === null) {
	        return null;
	      }
	      return {
	        header: _classPrivateMethodGet$2(this, _getPlainText, _getPlainText2).call(this, HEADER_POSITION),
	        body: _classPrivateMethodGet$2(this, _getPlainText, _getPlainText2).call(this, PREVIEW_POSITION),
	        footer: _classPrivateMethodGet$2(this, _getPlainText, _getPlainText2).call(this, FOOTER_POSITION)
	      };
	    }
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return {
	        header: babelHelpers.classPrivateFieldGet(this, _headerRaw),
	        body: babelHelpers.classPrivateFieldGet(this, _bodyRaw),
	        footer: babelHelpers.classPrivateFieldGet(this, _footerRaw)
	      };
	    } // endregion
	  }, {
	    key: "onPlaceholderClick",
	    value: function onPlaceholderClick(_ref) {
	      var _babelHelpers$classPr,
	        _this = this;
	      var dialog = _ref.dialog,
	        event = _ref.event;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _inputPopup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.destroy();
	      var filledPlaceholder = _classPrivateMethodGet$2(this, _getFilledPlaceholderByElement, _getFilledPlaceholderByElement2).call(this, event.target, PREVIEW_POSITION);
	      var isTextItemFirst = main_core.Type.isStringFilled(filledPlaceholder === null || filledPlaceholder === void 0 ? void 0 : filledPlaceholder.FIELD_VALUE);
	      if (babelHelpers.classPrivateFieldGet(this, _canUseFieldsDialog) && babelHelpers.classPrivateFieldGet(this, _canUseFieldValueInput)) {
	        babelHelpers.classPrivateFieldSet(this, _popupMenu, new MenuPopup({
	          bindElement: event.target,
	          isTextItemFirst: isTextItemFirst,
	          onEditorItemClick: function onEditorItemClick() {
	            _this.onShowDialogPopup(filledPlaceholder, dialog);
	          },
	          onTextItemClick: function onTextItemClick(element) {
	            _this.onShowInputPopup(element);
	          }
	        }));
	        babelHelpers.classPrivateFieldGet(this, _popupMenu).show();
	      } else if (babelHelpers.classPrivateFieldGet(this, _canUseFieldsDialog)) {
	        this.onShowDialogPopup(filledPlaceholder, dialog);
	      } else if (babelHelpers.classPrivateFieldGet(this, _canUseFieldValueInput)) {
	        this.onShowInputPopup(event.target);
	      }
	    }
	  }, {
	    key: "onShowDialogPopup",
	    value: function onShowDialogPopup(filledPlaceholder, dialog) {
	      if (main_core.Type.isStringFilled(filledPlaceholder === null || filledPlaceholder === void 0 ? void 0 : filledPlaceholder.FIELD_VALUE)) {
	        dialog.getPreselectedItems().forEach(function (preselectedItem) {
	          var item = dialog.getItem(preselectedItem);
	          if (item) {
	            item.deselect();
	          }
	        });
	      }
	      dialog.show();
	    }
	  }, {
	    key: "onShowInputPopup",
	    value: function onShowInputPopup(bindElement) {
	      var _this2 = this;
	      var filledPlaceholder = _classPrivateMethodGet$2(this, _getFilledPlaceholderByElement, _getFilledPlaceholderByElement2).call(this, bindElement);
	      var value = main_core.Type.isStringFilled(filledPlaceholder === null || filledPlaceholder === void 0 ? void 0 : filledPlaceholder.FIELD_VALUE) ? filledPlaceholder.FIELD_VALUE : '';
	      babelHelpers.classPrivateFieldSet(this, _inputPopup, new TextPopup({
	        bindElement: bindElement,
	        value: value,
	        onApply: function onApply(newValue) {
	          _classPrivateMethodGet$2(_this2, _onApplyInputPopup, _onApplyInputPopup2).call(_this2, newValue, bindElement);
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _inputPopup).show();
	    }
	  }]);
	  return Editor;
	}();
	function _createContainer2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _target)) {
	    return;
	  }
	  var containerEl = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"crm-template-editor crm-template-editor__scope\"></div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _id));
	  babelHelpers.classPrivateFieldSet(this, _headerContainerEl, main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-header\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _headerContainerEl), containerEl);
	  babelHelpers.classPrivateFieldSet(this, _bodyContainerEl, main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-body\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _bodyContainerEl), containerEl);
	  babelHelpers.classPrivateFieldSet(this, _footerContainerEl, main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-footer\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _footerContainerEl), containerEl);
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _target));
	  main_core.Dom.append(containerEl, babelHelpers.classPrivateFieldGet(this, _target));
	}
	function _createContainerWithSelectors2(input) {
	  var _this3 = this;
	  var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : PREVIEW_POSITION;
	  var placeholders = _classPrivateMethodGet$2(this, _getPlaceholders, _getPlaceholders2).call(this, position);
	  if (placeholders === null) {
	    return null;
	  }
	  var container = _classPrivateMethodGet$2(this, _getInputContainer, _getInputContainer2).call(this, input, position);
	  var dlgOptions = babelHelpers.classPrivateFieldGet(this, _placeHoldersDialogDefaultOptions);
	  placeholders.forEach(function (placeholder, key) {
	    var element = babelHelpers.toConsumableArray(container.childNodes).find(function (node) {
	      return node.dataset && Number(node.dataset.templatePlaceholder) === key;
	    });
	    if (!element) {
	      return;
	    }
	    _classPrivateMethodGet$2(_this3, _prepareDlgOptions, _prepareDlgOptions2).call(_this3, dlgOptions, element, position);
	    var dialog = new crm_entitySelector.Dialog(dlgOptions);
	    main_core.Event.bind(element, 'click', function (event) {
	      _this3.onPlaceholderClick({
	        dialog: dialog,
	        event: event
	      });
	    });
	  });
	  return container;
	}
	function _onApplyInputPopup2(value, bindElement) {
	  var placeholderId = _classPrivateMethodGet$2(this, _getPlaceholderIdByElement, _getPlaceholderIdByElement2).call(this, bindElement, PREVIEW_POSITION);
	  var params = {
	    id: placeholderId,
	    parentTitle: null,
	    text: value,
	    title: value,
	    entityType: BX.CrmEntityType.resolveName(babelHelpers.classPrivateFieldGet(this, _entityTypeId)).toLowerCase()
	  };

	  // eslint-disable-next-line no-param-reassign
	  bindElement.textContent = value;
	  main_core.Dom.addClass(bindElement, '--selected');
	  _classPrivateMethodGet$2(this, _adjustFilledPlaceholders, _adjustFilledPlaceholders2).call(this, params);
	  this.onSelect(params);
	}
	function _getInputContainer2(input, position) {
	  var _this4 = this;
	  var placeholders = _classPrivateMethodGet$2(this, _getPlaceholders, _getPlaceholders2).call(this, position);
	  if (placeholders === null) {
	    return null;
	  }
	  var i = 0;
	  placeholders.forEach(function (placeholder) {
	    var filledPlaceholder = _classPrivateMethodGet$2(_this4, _getFilledPlaceholderById, _getFilledPlaceholderById2).call(_this4, placeholder);
	    var title = main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL');
	    var spanClass = 'crm-template-editor-element-pill';
	    if (filledPlaceholder) {
	      if (main_core.Type.isStringFilled(filledPlaceholder.PARENT_TITLE) && main_core.Type.isStringFilled(filledPlaceholder.TITLE)) {
	        title = "".concat(filledPlaceholder.PARENT_TITLE, ": ").concat(filledPlaceholder.TITLE);
	      } else if (main_core.Type.isStringFilled(filledPlaceholder.TITLE)) {
	        title = filledPlaceholder.TITLE;
	      } else if (main_core.Type.isStringFilled(filledPlaceholder.FIELD_NAME)) {
	        title = filledPlaceholder.FIELD_NAME;
	      } else {
	        title = filledPlaceholder.FIELD_VALUE;
	      }
	      title = main_core.Text.encode(title);
	      spanClass += ' --selected';
	    }
	    var replaceValue = "<span class=\"".concat(spanClass, "\" data-template-placeholder=\"").concat(i++, "\">").concat(title, "</span>");

	    // eslint-disable-next-line no-param-reassign
	    input = input.replace(placeholder, replaceValue);
	  });
	  return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), input);
	}
	function _getPlaceholders2(position) {
	  var allPlaceholders = main_core.Type.isPlainObject(this.placeholders) ? this.placeholders : {};
	  var placeholders = main_core.Type.isArrayFilled(allPlaceholders[position]) ? allPlaceholders[position] : [];
	  return main_core.Type.isArrayLike(placeholders) ? placeholders : null;
	}
	function _prepareDlgOptions2(dlgOptions, element, position) {
	  var _placeholders$element,
	    _this5 = this;
	  var placeholders = _classPrivateMethodGet$2(this, _getPlaceholders, _getPlaceholders2).call(this, position);
	  var placeholderId = (_placeholders$element = placeholders[element.dataset.templatePlaceholder]) !== null && _placeholders$element !== void 0 ? _placeholders$element : null;
	  if (placeholderId) {
	    var filledPlaceholder = _classPrivateMethodGet$2(this, _getFilledPlaceholderById, _getFilledPlaceholderById2).call(this, placeholderId);
	    if (filledPlaceholder) {
	      // eslint-disable-next-line no-param-reassign
	      dlgOptions.preselectedItems = [[filledPlaceholder.FIELD_ENTITY_TYPE, filledPlaceholder.FIELD_NAME]];
	    }
	  }

	  // eslint-disable-next-line no-param-reassign
	  dlgOptions.events = {
	    onShow: function onShow() {
	      var keyframes = [{
	        transform: 'rotate(0)'
	      }, {
	        transform: 'rotate(90deg)'
	      }, {
	        transform: 'rotate(180deg)'
	      }];
	      var options = {
	        duration: 200,
	        pseudoElement: '::after'
	      };
	      element.animate(keyframes, options);
	      main_core.Dom.addClass(element, '--flipped');
	    },
	    onHide: function onHide() {
	      var keyframes = [{
	        transform: 'rotate(180deg)'
	      }, {
	        transform: 'rotate(90deg)'
	      }, {
	        transform: 'rotate(0)'
	      }];
	      var options = {
	        duration: 200,
	        pseudoElement: '::after'
	      };
	      element.animate(keyframes, options);
	      main_core.Dom.removeClass(element, '--flipped');
	    },
	    'Item:onSelect': function ItemOnSelect(event) {
	      main_core.Dom.addClass(element, '--selected');
	      var item = event.getData().item;
	      var parentTitle = item.supertitle.text;
	      var title = item.title.text;

	      // eslint-disable-next-line no-param-reassign
	      element.textContent = "".concat(parentTitle, ": ").concat(title);
	      var value = item.id;
	      var entityType = item.entityId;
	      var params = {
	        id: placeholderId,
	        value: value,
	        parentTitle: parentTitle,
	        title: title,
	        entityType: entityType
	      };
	      _classPrivateMethodGet$2(_this5, _adjustFilledPlaceholders, _adjustFilledPlaceholders2).call(_this5, params);
	      _this5.onSelect(params);
	    }
	  };

	  // eslint-disable-next-line no-param-reassign
	  dlgOptions.targetNode = element;
	}
	function _adjustFilledPlaceholders2(_ref2) {
	  var id = _ref2.id,
	    value = _ref2.value,
	    text = _ref2.text,
	    parentTitle = _ref2.parentTitle,
	    title = _ref2.title;
	  var action = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : UPDATE_ACTION;
	  if (action === DELETE_ACTION) {
	    _classPrivateMethodGet$2(this, _deleteFromFilledPlaceholders, _deleteFromFilledPlaceholders2).call(this, id, value);
	    return;
	  }
	  _classPrivateMethodGet$2(this, _updateForFilledPlaceholders, _updateForFilledPlaceholders2).call(this, {
	    id: id,
	    value: value,
	    text: text,
	    parentTitle: parentTitle,
	    title: title
	  });
	}
	function _deleteFromFilledPlaceholders2(id, value) {
	  this.filledPlaceholders = this.filledPlaceholders.filter(function (filledPlaceholder) {
	    return filledPlaceholder.PLACEHOLDER_ID !== id || filledPlaceholder.FIELD_NAME !== value;
	  });
	}
	function _updateForFilledPlaceholders2(_ref3) {
	  var id = _ref3.id,
	    value = _ref3.value,
	    text = _ref3.text,
	    parentTitle = _ref3.parentTitle,
	    title = _ref3.title;
	  var filledPlaceholder = _classPrivateMethodGet$2(this, _getFilledPlaceholderById, _getFilledPlaceholderById2).call(this, id);
	  if (filledPlaceholder) {
	    filledPlaceholder.FIELD_NAME = value !== null && value !== void 0 ? value : null;
	    filledPlaceholder.FIELD_VALUE = text !== null && text !== void 0 ? text : null;
	    filledPlaceholder.PARENT_TITLE = parentTitle;
	    filledPlaceholder.TITLE = title;
	  } else {
	    this.filledPlaceholders.push({
	      PLACEHOLDER_ID: id,
	      FIELD_NAME: value,
	      FIELD_VALUE: text,
	      PARENT_TITLE: parentTitle,
	      TITLE: title
	    });
	  }
	}
	function _getFilledPlaceholderByElement2(element) {
	  var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : PREVIEW_POSITION;
	  var placeholderId = _classPrivateMethodGet$2(this, _getPlaceholderIdByElement, _getPlaceholderIdByElement2).call(this, element, position);
	  return _classPrivateMethodGet$2(this, _getFilledPlaceholderById, _getFilledPlaceholderById2).call(this, placeholderId);
	}
	function _getPlaceholderIdByElement2(element) {
	  var _placeholders$element2;
	  var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : PREVIEW_POSITION;
	  var placeholders = _classPrivateMethodGet$2(this, _getPlaceholders, _getPlaceholders2).call(this, position);
	  return (_placeholders$element2 = placeholders[element.dataset.templatePlaceholder]) !== null && _placeholders$element2 !== void 0 ? _placeholders$element2 : null;
	}
	function _getFilledPlaceholderById2(placeholderId) {
	  return this.filledPlaceholders.find(function (filledPlaceholderItem) {
	    return filledPlaceholderItem.PLACEHOLDER_ID === placeholderId;
	  });
	}
	function _getPlainText2(position) {
	  var text = _classPrivateMethodGet$2(this, _getRawTextByPosition, _getRawTextByPosition2).call(this, position);
	  if (text === null) {
	    return null;
	  }
	  if (main_core.Type.isArrayFilled(this.filledPlaceholders)) {
	    this.filledPlaceholders.forEach(function (filledPlaceholder) {
	      if (main_core.Type.isStringFilled(filledPlaceholder.FIELD_NAME)) {
	        text = text.replace(filledPlaceholder.PLACEHOLDER_ID, "{".concat(filledPlaceholder.FIELD_NAME, "}"));
	      } else if (main_core.Type.isStringFilled(filledPlaceholder.FIELD_VALUE)) {
	        text = text.replace(filledPlaceholder.PLACEHOLDER_ID, filledPlaceholder.FIELD_VALUE);
	      }
	    });
	  }
	  var placeholders = this.placeholders[position];
	  if (main_core.Type.isArrayFilled(placeholders)) {
	    placeholders.forEach(function (placeholder) {
	      text = text.replace(placeholder, ' ');
	    });
	  }
	  return text;
	}
	function _getRawTextByPosition2(position) {
	  if (position === HEADER_POSITION) {
	    return babelHelpers.classPrivateFieldGet(this, _headerRaw);
	  }
	  if (position === PREVIEW_POSITION) {
	    return babelHelpers.classPrivateFieldGet(this, _bodyRaw);
	  }
	  if (position === FOOTER_POSITION) {
	    return babelHelpers.classPrivateFieldGet(this, _footerRaw);
	  }
	  return null;
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new TypeError('BX.Crm.Template.Editor: The "params" argument must be object');
	  }
	  if (!main_core.Type.isDomNode(params.target)) {
	    throw new Error('BX.Crm.Template.Editor: The "target" argument must be DOM node');
	  }
	  if (!BX.CrmEntityType.isDefined(params.entityTypeId)) {
	    throw new TypeError('BX.Crm.Template.Editor: The "entityTypeId" argument is not correct');
	  }
	  if (!main_core.Type.isNumber(params.entityId) || params.entityId <= 0) {
	    throw new TypeError('BX.Crm.Template.Editor: The "entityId" argument is not correct');
	  }
	  if (!main_core.Type.isFunction(params.onSelect)) {
	    throw new TypeError('BX.Crm.Template.Editor: The "onSelect" argument is not correct');
	  }
	}

	exports.Editor = Editor;

}((this.BX.Crm.Template = this.BX.Crm.Template || {}),BX.Crm.EntitySelectorEx,BX.Event,BX,BX.UI.EntitySelector,BX,BX.Main,BX.UI));
//# sourceMappingURL=editor.bundle.js.map
