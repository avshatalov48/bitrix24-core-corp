/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,main_popup,ai_copilot,main_core) {
	'use strict';

	var _templateObject, _templateObject2;
	var PROPERTIES = ['direction', 'boxSizing', 'width', 'height', 'overflowX', 'overflowY', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'borderStyle', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize', 'fontSizeAdjust', 'lineHeight', 'fontFamily', 'textAlign', 'textTransform', 'textIndent', 'textDecoration', 'letterSpacing', 'wordSpacing', 'tabSize', 'MozTabSize'];
	// eslint-disable-next-line sonarjs/cognitive-complexity
	function getCaretCoordinates(element, position) {
	  // eslint-disable-next-line no-eq-null
	  var isFirefox = window.mozInnerScreenX !== null;
	  var dummyEl = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div id='textarea-caret-position-dummy-div'></div>"])));
	  main_core.Dom.append(dummyEl, document.body);
	  var style = dummyEl.style;
	  var computed = window.getComputedStyle ? window.getComputedStyle(element) : element.currentStyle;
	  var isInput = element.nodeName === 'INPUT';
	  style.whiteSpace = 'pre-wrap';
	  if (!isInput) {
	    style.wordWrap = 'break-word';
	  }

	  // Position off-screen
	  style.position = 'absolute'; // required to return coordinates properly
	  style.visibility = 'hidden'; // not 'display: none' because we want rendering

	  // Transfer the element's properties to the div
	  PROPERTIES.forEach(function (prop) {
	    if (isInput && prop === 'lineHeight') {
	      // Special case for <input>s because text is rendered centered and line height may be != height
	      if (computed.boxSizing === 'border-box') {
	        var height = parseInt(computed.height, 10);
	        var outerHeight = parseInt(computed.paddingTop, 10) + parseInt(computed.paddingBottom, 10) + parseInt(computed.borderTopWidth, 10) + parseInt(computed.borderBottomWidth, 10);
	        var targetHeight = outerHeight + parseInt(computed.lineHeight, 10);
	        if (height > targetHeight) {
	          style.lineHeight = "".concat(height - outerHeight, "px");
	        } else if (height === targetHeight) {
	          style.lineHeight = computed.lineHeight;
	        } else {
	          style.lineHeight = 0;
	        }
	      } else {
	        style.lineHeight = computed.height;
	      }
	    } else {
	      style[prop] = computed[prop];
	    }
	  });
	  if (isFirefox) {
	    if (element.scrollHeight > parseInt(computed.height, 10)) {
	      style.overflowY = 'scroll';
	    }
	  } else {
	    style.overflow = 'hidden';
	  }
	  dummyEl.textContent = element.value.slice(0, Math.max(0, position));
	  if (isInput) {
	    dummyEl.textContent = dummyEl.textContent.replaceAll(/\s/g, "\xA0");
	  }
	  var spanEl = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	  spanEl.textContent = element.value.slice(Math.max(0, position)) || '.'; // || because a completely empty faux span doesn't render at all
	  main_core.Dom.append(spanEl, dummyEl);
	  var coordinates = {
	    top: spanEl.offsetTop + parseInt(computed.borderTopWidth, 10),
	    left: spanEl.offsetLeft + parseInt(computed.borderLeftWidth, 10)
	  };
	  main_core.Dom.remove(dummyEl);
	  return coordinates;
	}

	var _templateObject$1;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var COPILOT_BUTTON_WIDTH = 80;
	var COPILOT_BUTTON_HEIGHT = 32;
	var COPILOT_RESULT_TEXT_WRAP_LEFT = '<<<';
	var COPILOT_RESULT_TEXT_WRAP_RIGHT = '>>>';
	var Events = {
	  EVENT_VALUE_CHANGE: 'crm:ai:copilot-textarea:value-change'
	};
	var _id = /*#__PURE__*/new WeakMap();
	var _copilot = /*#__PURE__*/new WeakMap();
	var _element = /*#__PURE__*/new WeakMap();
	var _isDebugEnabled = /*#__PURE__*/new WeakMap();
	var _copilotLoaded = /*#__PURE__*/new WeakMap();
	var _copilotBtnPopup = /*#__PURE__*/new WeakMap();
	var _currentSelectedText = /*#__PURE__*/new WeakMap();
	var _showCopilot = /*#__PURE__*/new WeakSet();
	var _showCopilotButton = /*#__PURE__*/new WeakSet();
	var _bindHandlers = /*#__PURE__*/new WeakSet();
	var _handleKeyDown = /*#__PURE__*/new WeakSet();
	var _handleSelect = /*#__PURE__*/new WeakSet();
	var _handleKeyUpEscape = /*#__PURE__*/new WeakSet();
	var _assertValidParams = /*#__PURE__*/new WeakSet();
	var _isCursorAtBeginningOfLine = /*#__PURE__*/new WeakSet();
	var _getTextAreaValue = /*#__PURE__*/new WeakSet();
	var _setTextAreaValue = /*#__PURE__*/new WeakSet();
	var _getElementCoordinates = /*#__PURE__*/new WeakSet();
	var _wrapText = /*#__PURE__*/new WeakSet();
	var _cleanWrappedText = /*#__PURE__*/new WeakSet();
	var _cleanWrapChars = /*#__PURE__*/new WeakSet();
	var _replaceSelectionText = /*#__PURE__*/new WeakSet();
	var _getElement = /*#__PURE__*/new WeakSet();
	var _logEventInfo = /*#__PURE__*/new WeakSet();
	var CopilotTextarea = /*#__PURE__*/function () {
	  function CopilotTextarea(_params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, CopilotTextarea);
	    _classPrivateMethodInitSpec(this, _logEventInfo);
	    _classPrivateMethodInitSpec(this, _getElement);
	    _classPrivateMethodInitSpec(this, _replaceSelectionText);
	    _classPrivateMethodInitSpec(this, _cleanWrapChars);
	    _classPrivateMethodInitSpec(this, _cleanWrappedText);
	    _classPrivateMethodInitSpec(this, _wrapText);
	    _classPrivateMethodInitSpec(this, _getElementCoordinates);
	    _classPrivateMethodInitSpec(this, _setTextAreaValue);
	    _classPrivateMethodInitSpec(this, _getTextAreaValue);
	    _classPrivateMethodInitSpec(this, _isCursorAtBeginningOfLine);
	    _classPrivateMethodInitSpec(this, _assertValidParams);
	    _classPrivateMethodInitSpec(this, _handleKeyUpEscape);
	    _classPrivateMethodInitSpec(this, _handleSelect);
	    _classPrivateMethodInitSpec(this, _handleKeyDown);
	    _classPrivateMethodInitSpec(this, _bindHandlers);
	    _classPrivateMethodInitSpec(this, _showCopilotButton);
	    _classPrivateMethodInitSpec(this, _showCopilot);
	    _classPrivateFieldInitSpec(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _copilot, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _element, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isDebugEnabled, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _copilotLoaded, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _copilotBtnPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _currentSelectedText, {
	      writable: true,
	      value: ''
	    });
	    _classPrivateMethodGet(this, _assertValidParams, _assertValidParams2).call(this, _params);
	    babelHelpers.classPrivateFieldSet(this, _id, _params.id);
	    babelHelpers.classPrivateFieldSet(this, _element, _params.target);
	    babelHelpers.classPrivateFieldSet(this, _copilot, new ai_copilot.Copilot(_params.copilotParams)); // @see CopilotOptions [ai/install/js/ai/copilot/src/copilot.js]
	    babelHelpers.classPrivateFieldSet(this, _isDebugEnabled, _params.isDebugEnabled || false);
	    _classPrivateMethodGet(this, _bindHandlers, _bindHandlers2).call(this);
	    babelHelpers.classPrivateFieldGet(this, _copilot).init();
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _element), 'keydown', function (event) {
	      return _classPrivateMethodGet(_this, _handleKeyDown, _handleKeyDown2).call(_this, event);
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _element), 'select', function (event) {
	      return _classPrivateMethodGet(_this, _handleSelect, _handleSelect2).call(_this, event);
	    });
	  }
	  babelHelpers.createClass(CopilotTextarea, [{
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _id);
	    }
	  }, {
	    key: "setReadOnly",
	    value: function setReadOnly() {
	      var flag = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      // NOTE: Dom.attr method NOT WORKED, so use setAttribute/removeAttribute
	      if (flag) {
	        babelHelpers.classPrivateFieldGet(this, _element).setAttribute('readonly', 1);
	      } else {
	        babelHelpers.classPrivateFieldGet(this, _element).removeAttribute('readonly');
	      }
	    } // endregion
	  }]);
	  return CopilotTextarea;
	}();
	function _showCopilot2(params) {
	  var _this2 = this;
	  var coordinates = _classPrivateMethodGet(this, _getElementCoordinates, _getElementCoordinates2).call(this);
	  if (!coordinates) {
	    return;
	  }
	  var context = params.context || '';
	  var selectedText = params.selectedText || '';
	  babelHelpers.classPrivateFieldGet(this, _copilot).setContext(context);
	  babelHelpers.classPrivateFieldGet(this, _copilot).setSelectedText(selectedText);
	  babelHelpers.classPrivateFieldGet(this, _copilot).show({
	    bindElement: coordinates,
	    width: babelHelpers.classPrivateFieldGet(this, _element).offsetWidth - 10
	  });
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('cancel', function (event) {
	    _classPrivateMethodGet(_this2, _logEventInfo, _logEventInfo2).call(_this2, 'CoPilot canceled', event);
	    _classPrivateMethodGet(_this2, _cleanWrappedText, _cleanWrappedText2).call(_this2);
	    babelHelpers.classPrivateFieldGet(_this2, _copilot).adjust({
	      hide: false,
	      position: _classPrivateMethodGet(_this2, _getElementCoordinates, _getElementCoordinates2).call(_this2)
	    });
	  });
	  var handleKeyUpEscape = _classPrivateMethodGet(this, _handleKeyUpEscape, _handleKeyUpEscape2).bind(this);
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('hide', function (event) {
	    _classPrivateMethodGet(_this2, _logEventInfo, _logEventInfo2).call(_this2, 'CoPilot hidden', event);
	    main_core.Event.unbind(window, 'keyup', handleKeyUpEscape);
	  });
	  main_core.Event.bind(window, 'keyup', handleKeyUpEscape);
	}
	function _showCopilotButton2() {
	  var _this3 = this;
	  var copilotButton = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"show-copilot-btn\">\n\t\t\t\t<div class=\"show-copilot-btn-icon ui-icon-set --copilot-ai\"></div>\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), main_core.Loc.getMessage('CRM_COMMON_COPILOT').toUpperCase());
	  main_core.Event.bind(copilotButton, 'click', function (event) {
	    _classPrivateMethodGet(_this3, _showCopilot, _showCopilot2).call(_this3, {
	      context: _classPrivateMethodGet(_this3, _getTextAreaValue, _getTextAreaValue2).call(_this3),
	      selectedText: babelHelpers.classPrivateFieldGet(_this3, _currentSelectedText)
	    });
	    babelHelpers.classPrivateFieldGet(_this3, _copilotBtnPopup).close();
	  });
	  var coordinates = _classPrivateMethodGet(this, _getElementCoordinates, _getElementCoordinates2).call(this);
	  babelHelpers.classPrivateFieldSet(this, _copilotBtnPopup, new main_popup.Popup({
	    id: "copilot_textarea_popup_button_".concat(main_core.Text.getRandom(5)),
	    content: copilotButton,
	    bindElement: {
	      top: coordinates.top - COPILOT_BUTTON_HEIGHT / 2,
	      left: coordinates.left + (babelHelpers.classPrivateFieldGet(this, _element).offsetWidth / 2 - COPILOT_BUTTON_WIDTH / 2)
	    },
	    padding: 5,
	    borderRadius: '4px'
	  }));
	  main_core.Event.bind(document, 'keyup', function (event) {
	    _classPrivateMethodGet(_this3, _cleanWrappedText, _cleanWrappedText2).call(_this3);
	    babelHelpers.classPrivateFieldGet(_this3, _copilotBtnPopup).close();
	  });
	  main_core.Event.bind(copilotButton, 'click', function () {
	    babelHelpers.classPrivateFieldGet(_this3, _copilotBtnPopup).close();
	  });
	  setTimeout(function () {
	    main_core.Event.bind(window, 'mouseup', function (event) {
	      babelHelpers.classPrivateFieldGet(_this3, _copilotBtnPopup).close();
	    });
	  }, 100);
	  babelHelpers.classPrivateFieldGet(this, _copilotBtnPopup).show();
	}
	function _bindHandlers2() {
	  var _this4 = this;
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('start-init', function (event) {
	    _classPrivateMethodGet(_this4, _logEventInfo, _logEventInfo2).call(_this4, 'CoPilot load start', event);
	    _this4.setReadOnly();
	  });
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('finish-init', function (event) {
	    _classPrivateMethodGet(_this4, _logEventInfo, _logEventInfo2).call(_this4, 'CoPilot loaded', event);
	    babelHelpers.classPrivateFieldSet(_this4, _copilotLoaded, true);
	    _this4.setReadOnly(false);
	  });
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('aiResult', function (event) {
	    _classPrivateMethodGet(_this4, _logEventInfo, _logEventInfo2).call(_this4, 'CoPilot result received', event);
	    var newValue = '';
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(_this4, _currentSelectedText))) {
	      var start = babelHelpers.classPrivateFieldGet(_this4, _element).selectionStart;
	      var end = babelHelpers.classPrivateFieldGet(_this4, _element).selectionEnd;
	      var allText = _classPrivateMethodGet(_this4, _getTextAreaValue, _getTextAreaValue2).call(_this4);
	      newValue = allText.slice(0, Math.max(0, start)) + _classPrivateMethodGet(_this4, _wrapText, _wrapText2).call(_this4, event.data.result) + allText.slice(end, allText.length);
	    } else {
	      newValue = _classPrivateMethodGet(_this4, _getTextAreaValue, _getTextAreaValue2).call(_this4) + _classPrivateMethodGet(_this4, _wrapText, _wrapText2).call(_this4, event.data.result);
	    }
	    _classPrivateMethodGet(_this4, _setTextAreaValue, _setTextAreaValue2).call(_this4, newValue);
	    babelHelpers.classPrivateFieldGet(_this4, _copilot).adjust({
	      hide: false,
	      position: _classPrivateMethodGet(_this4, _getElementCoordinates, _getElementCoordinates2).call(_this4)
	    });
	  });
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('save', function (event) {
	    _classPrivateMethodGet(_this4, _logEventInfo, _logEventInfo2).call(_this4, 'CoPilot result saved', event);
	    _classPrivateMethodGet(_this4, _replaceSelectionText, _replaceSelectionText2).call(_this4, event.data.result);
	    _classPrivateMethodGet(_this4, _cleanWrapChars, _cleanWrapChars2).call(_this4);
	    babelHelpers.classPrivateFieldGet(_this4, _copilot).hide();
	  });
	  babelHelpers.classPrivateFieldGet(this, _copilot).subscribe('add_below', function (event) {
	    _classPrivateMethodGet(_this4, _logEventInfo, _logEventInfo2).call(_this4, 'CoPilot result text place below', event);
	    var currentText = _classPrivateMethodGet(_this4, _getTextAreaValue, _getTextAreaValue2).call(_this4);
	    _classPrivateMethodGet(_this4, _setTextAreaValue, _setTextAreaValue2).call(_this4, "".concat(currentText, "\n").concat(event.data.result));
	    babelHelpers.classPrivateFieldGet(_this4, _copilot).hide();
	  });
	}
	function _handleKeyDown2(event) {
	  var isSpacePressed = event.key === ' ' || event.code === 'Space';
	  if (!isSpacePressed || !babelHelpers.classPrivateFieldGet(this, _copilotLoaded) || babelHelpers.classPrivateFieldGet(this, _copilot).isShown() || !_classPrivateMethodGet(this, _isCursorAtBeginningOfLine, _isCursorAtBeginningOfLine2).call(this)) {
	    return;
	  }
	  _classPrivateMethodGet(this, _logEventInfo, _logEventInfo2).call(this, 'Space pressed', event);
	  _classPrivateMethodGet(this, _showCopilot, _showCopilot2).call(this, {
	    context: _classPrivateMethodGet(this, _getTextAreaValue, _getTextAreaValue2).call(this),
	    selectedText: ''
	  });
	  event.preventDefault();
	}
	function _handleSelect2(event) {
	  var _babelHelpers$classPr,
	    _this5 = this;
	  var target = event.target;
	  if (!target) {
	    return;
	  }
	  if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _copilotBtnPopup)) !== null && _babelHelpers$classPr !== void 0 && _babelHelpers$classPr.isShown()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _currentSelectedText, target.value.slice(target.selectionStart, target.selectionEnd));
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _currentSelectedText))) {
	    _classPrivateMethodGet(this, _logEventInfo, _logEventInfo2).call(this, 'Text selected', event);
	    setTimeout(function () {
	      return _classPrivateMethodGet(_this5, _showCopilotButton, _showCopilotButton2).call(_this5);
	    }, 100);
	  }
	}
	function _handleKeyUpEscape2(event) {
	  if (event.key === 'Escape' && babelHelpers.classPrivateFieldGet(this, _copilot).isShown()) {
	    _classPrivateMethodGet(this, _cleanWrapChars, _cleanWrapChars2).call(this);
	    babelHelpers.classPrivateFieldGet(this, _copilot).hide();
	    babelHelpers.classPrivateFieldGet(this, _element).focus();
	  }
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new TypeError('BX.Crm.AI.CopilotTextarea: The CoPilot textarea params must be object');
	  }
	  if (!main_core.Type.isStringFilled(params.id)) {
	    throw new TypeError('BX.Crm.AI.CopilotTextarea: The "id" argument must be filled');
	  }
	  if (!main_core.Type.isDomNode(params.target)) {
	    throw new Error('BX.Crm.AI.CopilotTextarea: The "target" argument must be DOM node');
	  }
	  if (params.target.tagName.toLowerCase() !== 'textarea') {
	    throw new Error('BX.Crm.AI.CopilotTextarea: The "target" argument must be textarea element');
	  }
	}
	function _isCursorAtBeginningOfLine2() {
	  var val = _classPrivateMethodGet(this, _getTextAreaValue, _getTextAreaValue2).call(this);
	  var element = _classPrivateMethodGet(this, _getElement, _getElement2).call(this);
	  var currentLineIndex = val.lastIndexOf('\n', element.selectionStart - 1) + 1;
	  return !main_core.Type.isStringFilled(val.slice(currentLineIndex, element.selectionStart));
	}
	function _getTextAreaValue2() {
	  return _classPrivateMethodGet(this, _getElement, _getElement2).call(this).value;
	}
	function _setTextAreaValue2(value) {
	  if (_classPrivateMethodGet(this, _getElement, _getElement2).call(this).value === value) {
	    return;
	  }
	  _classPrivateMethodGet(this, _getElement, _getElement2).call(this).value = value;
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _element), 'height', 'auto');
	  var currentTextareaHeight = _classPrivateMethodGet(this, _getElement, _getElement2).call(this).scrollHeight;
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _element), 'height', "".concat(currentTextareaHeight, "px"));
	  main_core_events.EventEmitter.emit(this, Events.EVENT_VALUE_CHANGE, {
	    id: babelHelpers.classPrivateFieldGet(this, _id),
	    value: value
	  });
	}
	function _getElementCoordinates2() {
	  var pressToLeft = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	  var elementRect = babelHelpers.classPrivateFieldGet(this, _element).getBoundingClientRect();
	  if (elementRect.top === 0 && elementRect.right === 0 && elementRect.bottom === 0 && elementRect.left === 0) {
	    return null;
	  }
	  var coordinates = getCaretCoordinates(babelHelpers.classPrivateFieldGet(this, _element), babelHelpers.classPrivateFieldGet(this, _element).selectionEnd);
	  return {
	    left: pressToLeft ? elementRect.left + window.scrollX + 5 : elementRect.left + window.scrollX + coordinates.left + 2,
	    top: elementRect.top + window.scrollY + coordinates.top + 21
	  };
	}
	function _wrapText2(text) {
	  return COPILOT_RESULT_TEXT_WRAP_LEFT + text + COPILOT_RESULT_TEXT_WRAP_RIGHT;
	}
	function _cleanWrappedText2() {
	  var re = new RegExp("".concat(COPILOT_RESULT_TEXT_WRAP_LEFT, "(.*)").concat(COPILOT_RESULT_TEXT_WRAP_RIGHT), 'gs');
	  _classPrivateMethodGet(this, _setTextAreaValue, _setTextAreaValue2).call(this, _classPrivateMethodGet(this, _getTextAreaValue, _getTextAreaValue2).call(this).replaceAll(re, ''));
	}
	function _cleanWrapChars2() {
	  var wrapLeftRe = new RegExp(COPILOT_RESULT_TEXT_WRAP_LEFT, 'gs');
	  var wrapRightRe = new RegExp(COPILOT_RESULT_TEXT_WRAP_RIGHT, 'gs');
	  _classPrivateMethodGet(this, _setTextAreaValue, _setTextAreaValue2).call(this, _classPrivateMethodGet(this, _getTextAreaValue, _getTextAreaValue2).call(this).replaceAll(wrapLeftRe, '').replaceAll(wrapRightRe, ''));
	}
	function _replaceSelectionText2(text) {
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _currentSelectedText)) && main_core.Type.isStringFilled(text)) {
	    _classPrivateMethodGet(this, _setTextAreaValue, _setTextAreaValue2).call(this, _classPrivateMethodGet(this, _getTextAreaValue, _getTextAreaValue2).call(this).replace(babelHelpers.classPrivateFieldGet(this, _currentSelectedText), text));
	  }
	}
	function _getElement2() {
	  return BX(babelHelpers.classPrivateFieldGet(this, _element));
	}
	function _logEventInfo2(name, event) {
	  if (babelHelpers.classPrivateFieldGet(this, _isDebugEnabled)) {
	    // eslint-disable-next-line no-console
	    console.debug(name, event);
	  }
	}

	exports.Events = Events;
	exports.CopilotTextarea = CopilotTextarea;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX.Event,BX.Main,BX.AI,BX));
//# sourceMappingURL=copilot-textarea.bundle.js.map
