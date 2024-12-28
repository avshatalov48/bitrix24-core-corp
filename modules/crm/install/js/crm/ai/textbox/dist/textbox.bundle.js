/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_iconSet_api_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var AttentionPresets = {
	  DEFAULT: {
	    className: '--crm-textbox-attention-default',
	    iconOptions: {
	      icon: ui_iconSet_api_core.Set.INFO_1,
	      color: '#BDC1C6',
	      size: 16
	    }
	  },
	  COPILOT: {
	    className: '--crm-textbox-attention-copilot',
	    iconOptions: {
	      icon: ui_iconSet_api_core.Set.EARTH,
	      color: '#B6AAC8',
	      size: 16
	    }
	  }
	};
	var _getIconNode = /*#__PURE__*/new WeakSet();
	var _getContentNode = /*#__PURE__*/new WeakSet();
	var Attention = /*#__PURE__*/function () {
	  function Attention(options) {
	    var _options$preset;
	    babelHelpers.classCallCheck(this, Attention);
	    _classPrivateMethodInitSpec(this, _getContentNode);
	    _classPrivateMethodInitSpec(this, _getIconNode);
	    this.setContent(options.content);
	    this.setPreset((_options$preset = options.preset) !== null && _options$preset !== void 0 ? _options$preset : AttentionPresets.DEFAULT);
	  }
	  babelHelpers.createClass(Attention, [{
	    key: "setContent",
	    value: function setContent(content) {
	      if (main_core.Type.isElementNode(content)) {
	        this.content = content;
	        return;
	      }
	      this.content = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), content);
	    }
	  }, {
	    key: "setPreset",
	    value: function setPreset(preset) {
	      this.preset = preset;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.getContainer().innerHTML = '';
	      main_core.Dom.append(_classPrivateMethodGet(this, _getIconNode, _getIconNode2).call(this), this.getContainer());
	      main_core.Dom.append(_classPrivateMethodGet(this, _getContentNode, _getContentNode2).call(this), this.getContainer());
	      return this.getContainer();
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      if (!this.container) {
	        this.container = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-textbox-attention ", "\"></div>"])), this.preset.className);
	      }
	      return this.container;
	    }
	  }]);
	  return Attention;
	}();
	function _getIconNode2() {
	  var iconNode = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-textbox-attention__icon\"></span>"])));
	  var icon = new ui_iconSet_api_core.Icon(this.preset.iconOptions);
	  main_core.Dom.append(icon.render(), iconNode);
	  return iconNode;
	}
	function _getContentNode2() {
	  var contentNode = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-textbox-attention__content\"></span>"])));
	  main_core.Dom.append(this.content, contentNode);
	  return contentNode;
	}

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var ID_TEXT_CONTAINER = 'crm-copilot-text-container';
	var CLASS_SEARCH_ICON = 'ui-ctl-icon-search';
	var CLASS_CLEAR_ICON = 'ui-ctl-icon-clear';
	var ROOT_CONTAINER_BOTTOM_PADDING = '28px';
	var _id = /*#__PURE__*/new WeakMap();
	var _text = /*#__PURE__*/new WeakMap();
	var _title = /*#__PURE__*/new WeakMap();
	var _enableSearch = /*#__PURE__*/new WeakMap();
	var _enableCollapse = /*#__PURE__*/new WeakMap();
	var _isCollapsed = /*#__PURE__*/new WeakMap();
	var _previousTextContent = /*#__PURE__*/new WeakMap();
	var _attentions = /*#__PURE__*/new WeakMap();
	var _className = /*#__PURE__*/new WeakMap();
	var _prepareText = /*#__PURE__*/new WeakSet();
	var _getHeaderContainer = /*#__PURE__*/new WeakSet();
	var _getBodyContainer = /*#__PURE__*/new WeakSet();
	var _getContentContainer = /*#__PURE__*/new WeakSet();
	var _getTextContainer = /*#__PURE__*/new WeakSet();
	var _getSearchContainer = /*#__PURE__*/new WeakSet();
	var _getAttentionsContainer = /*#__PURE__*/new WeakSet();
	var _resetTextContainer = /*#__PURE__*/new WeakSet();
	var _switchStyle = /*#__PURE__*/new WeakSet();
	var _handleCollapse = /*#__PURE__*/new WeakSet();
	var Textbox = /*#__PURE__*/function () {
	  function Textbox() {
	    var _options$attentions;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Textbox);
	    _classPrivateMethodInitSpec$1(this, _handleCollapse);
	    _classPrivateMethodInitSpec$1(this, _switchStyle);
	    _classPrivateMethodInitSpec$1(this, _resetTextContainer);
	    _classPrivateMethodInitSpec$1(this, _getAttentionsContainer);
	    _classPrivateMethodInitSpec$1(this, _getSearchContainer);
	    _classPrivateMethodInitSpec$1(this, _getTextContainer);
	    _classPrivateMethodInitSpec$1(this, _getContentContainer);
	    _classPrivateMethodInitSpec$1(this, _getBodyContainer);
	    _classPrivateMethodInitSpec$1(this, _getHeaderContainer);
	    _classPrivateMethodInitSpec$1(this, _prepareText);
	    _classPrivateFieldInitSpec(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _text, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _title, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _enableSearch, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _enableCollapse, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isCollapsed, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _previousTextContent, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _attentions, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _className, {
	      writable: true,
	      value: {
	        searchIcon: '--search-1',
	        clearIcon: '--cross-30',
	        arrowTopIcon: '--chevron-up',
	        arrowDownIcon: '--chevron-down',
	        bodyExpanded: '--body-expanded',
	        nodeHidden: '--hidden'
	      }
	    });
	    this.setText(options.text);
	    babelHelpers.classPrivateFieldSet(this, _id, "crm-copilot-textbox-container-".concat(main_core.Text.getRandom(8)));
	    babelHelpers.classPrivateFieldSet(this, _title, main_core.Type.isString(options.title) ? options.title : '');
	    babelHelpers.classPrivateFieldSet(this, _enableSearch, main_core.Type.isBoolean(options.enableSearch) ? options.enableSearch : true);
	    babelHelpers.classPrivateFieldSet(this, _enableCollapse, main_core.Type.isBoolean(options.enableCollapse) ? options.enableCollapse : false);
	    babelHelpers.classPrivateFieldSet(this, _isCollapsed, main_core.Type.isBoolean(options.isCollapsed) ? options.isCollapsed : false);
	    babelHelpers.classPrivateFieldSet(this, _previousTextContent, main_core.Type.isElementNode(options.previousTextContent) ? options.previousTextContent : null);
	    babelHelpers.classPrivateFieldSet(this, _attentions, (_options$attentions = options.attentions) !== null && _options$attentions !== void 0 ? _options$attentions : []);
	  }
	  babelHelpers.createClass(Textbox, [{
	    key: "setText",
	    value: function setText(text) {
	      babelHelpers.classPrivateFieldSet(this, _text, main_core.Type.isString(text) ? _classPrivateMethodGet$1(this, _prepareText, _prepareText2).call(this, text) : '');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.rootContainer = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div \n\t\t\t\tid=\"", "\" \n\t\t\t\tclass=\"crm-copilot-textbox\"\n\t\t\t></div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _id));
	      if (babelHelpers.classPrivateFieldGet(this, _isCollapsed)) {
	        main_core.Dom.style(this.rootContainer, 'padding-bottom', 0);
	      } else {
	        main_core.Dom.style(this.rootContainer, 'padding-bottom', ROOT_CONTAINER_BOTTOM_PADDING);
	      }
	      var sectionWrapper = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__wrapper ", " ", "\"></div>"])), babelHelpers.classPrivateFieldGet(this, _isCollapsed) ? '' : babelHelpers.classPrivateFieldGet(this, _className).bodyExpanded, babelHelpers.classPrivateFieldGet(this, _enableCollapse) ? 'clickable' : '');
	      main_core.Dom.append(_classPrivateMethodGet$1(this, _getHeaderContainer, _getHeaderContainer2).call(this), sectionWrapper);
	      main_core.Dom.append(_classPrivateMethodGet$1(this, _getBodyContainer, _getBodyContainer2).call(this), sectionWrapper);
	      main_core.Dom.append(sectionWrapper, this.rootContainer);
	    }
	  }, {
	    key: "get",
	    value: function get() {
	      return this.rootContainer;
	    }
	  }]);
	  return Textbox;
	}();
	function _prepareText2(text) {
	  return text.replaceAll(/\r?\n/g, '<br>');
	}
	function _getHeaderContainer2() {
	  var _this = this;
	  var collapseIconElement = babelHelpers.classPrivateFieldGet(this, _enableCollapse) ? main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__collapse-icon clickable ui-icon-set ", "\"></div>"])), babelHelpers.classPrivateFieldGet(this, _isCollapsed) ? babelHelpers.classPrivateFieldGet(this, _className).arrowDownIcon : babelHelpers.classPrivateFieldGet(this, _className).arrowTopIcon) : '';
	  main_core.Event.bind(collapseIconElement, 'click', function () {
	    return _classPrivateMethodGet$1(_this, _handleCollapse, _handleCollapse2).call(_this);
	  });
	  return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-copilot-textbox__header\">\n\t\t\t\t<div class=\"crm-copilot-textbox__title\">", "</div>\n\t\t\t\t<div class=\"crm-copilot-textbox__title-icon-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _title)), _classPrivateMethodGet$1(this, _getSearchContainer, _getSearchContainer2).call(this), collapseIconElement);
	}
	function _getBodyContainer2() {
	  var bodyContainer = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__body-container\"></div>"])));
	  main_core.Dom.append(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__previous-text\">", "</div>"])), babelHelpers.classPrivateFieldGet(this, _previousTextContent)), bodyContainer);
	  main_core.Dom.append(_classPrivateMethodGet$1(this, _getContentContainer, _getContentContainer2).call(this), bodyContainer);
	  main_core.Dom.append(_classPrivateMethodGet$1(this, _getAttentionsContainer, _getAttentionsContainer2).call(this), bodyContainer);
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__body\">", "</div>"])), bodyContainer);
	}
	function _getContentContainer2() {
	  var contentContainer = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__content\"></div>"])));
	  var textContainer = _classPrivateMethodGet$1(this, _getTextContainer, _getTextContainer2).call(this);
	  main_core.Event.bind(textContainer, 'beforeinput', function (e) {
	    e.preventDefault();
	  });
	  main_core.Dom.append(textContainer, contentContainer);
	  return contentContainer;
	}
	function _getTextContainer2() {
	  if (this.textContainer) {
	    return this.textContainer;
	  }
	  this.textContainer = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div \n\t\t\t\tid=\"", "\" \n\t\t\t\tclass=\"crm-copilot-textbox__text-container\" \n\t\t\t\tcontenteditable=\"true\" \n\t\t\t\tspellcheck=\"false\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), ID_TEXT_CONTAINER, babelHelpers.classPrivateFieldGet(this, _text));
	  return this.textContainer;
	}
	function _getSearchContainer2() {
	  var _this2 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _enableSearch)) {
	    return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral([""])));
	  }
	  var searchNode = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-no-border crm-copilot-textbox__search ", "\"></div>"])), babelHelpers.classPrivateFieldGet(this, _isCollapsed) ? '--hidden' : '');
	  var searchBtn = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<a class=\"ui-ctl-after ", " crm-copilot-textbox__search-btn\"></a>"])), CLASS_SEARCH_ICON);
	  var searchInput = main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input \n\t\t\t\ttype=\"text\" \n\t\t\t\tplaceholder=\"", "\" \n\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox crm-copilot-textbox__search-input\"\n\t\t\t>\n\t\t"])), main_core.Text.encode(main_core.Loc.getMessage('CRM_COPILOT_TEXTBOX_SEARCH_PLACEHOLDER')));
	  searchInput.oninput = function () {
	    _classPrivateMethodGet$1(_this2, _resetTextContainer, _resetTextContainer2).call(_this2);
	    var value = searchInput.value;
	    if (!value) {
	      _classPrivateMethodGet$1(_this2, _switchStyle, _switchStyle2).call(_this2, searchBtn, CLASS_CLEAR_ICON, CLASS_SEARCH_ICON);
	      return;
	    }
	    _classPrivateMethodGet$1(_this2, _switchStyle, _switchStyle2).call(_this2, searchBtn, CLASS_SEARCH_ICON, CLASS_CLEAR_ICON);

	    // Highlights pieces of text that are not part of a tag
	    var regexp = new RegExp("((?<!<[^>]*?)(".concat(value, ")(?![^<]*?>))"), 'gi');
	    var textContainer = _classPrivateMethodGet$1(_this2, _getTextContainer, _getTextContainer2).call(_this2);
	    textContainer.innerHTML = textContainer.innerHTML.replace(regexp, '<span class="search-item">$&</span>');
	  };
	  var searchInputFocused = false;
	  searchInput.onblur = function () {
	    if (searchInput.value.length === 0) {
	      main_core.Dom.removeClass(searchNode, 'with-input-node');
	      main_core.Dom.remove(searchInput);
	      searchInputFocused = false;
	    }
	  };
	  searchBtn.onclick = function () {
	    if (searchNode.contains(searchInput)) {
	      if (searchInput.value.length > 0) {
	        searchInput.value = '';
	        _classPrivateMethodGet$1(_this2, _switchStyle, _switchStyle2).call(_this2, searchBtn, CLASS_CLEAR_ICON, CLASS_SEARCH_ICON);
	        _classPrivateMethodGet$1(_this2, _resetTextContainer, _resetTextContainer2).call(_this2);
	      }
	      searchInputFocused = true;
	      searchInput.focus();
	      return;
	    }
	    main_core.Dom.append(searchInput, searchNode);
	    main_core.Dom.addClass(searchNode, ['with-input-node']);
	    searchInputFocused = true;
	    searchInput.focus();
	  };
	  searchBtn.onmousedown = function (event) {
	    if (searchInputFocused) {
	      event.preventDefault();
	    }
	  };
	  main_core.Dom.append(searchBtn, searchNode);
	  return searchNode;
	}
	function _getAttentionsContainer2() {
	  if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldGet(this, _attentions))) {
	    return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral([""])));
	  }
	  var attentionsContainer = main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__attentions\"></div>"])));
	  babelHelpers.classPrivateFieldGet(this, _attentions).forEach(function (attention) {
	    return main_core.Dom.append(attention.render(), attentionsContainer);
	  });
	  return attentionsContainer;
	}
	function _resetTextContainer2() {
	  _classPrivateMethodGet$1(this, _getTextContainer, _getTextContainer2).call(this).innerHTML = babelHelpers.classPrivateFieldGet(this, _text);
	}
	function _switchStyle2(node, fromStyle, toStyle) {
	  if (main_core.Dom.hasClass(node, fromStyle) && !main_core.Dom.hasClass(node, toStyle)) {
	    main_core.Dom.addClass(node, toStyle);
	    main_core.Dom.removeClass(node, fromStyle);
	  }
	}
	function _handleCollapse2() {
	  babelHelpers.classPrivateFieldSet(this, _isCollapsed, !babelHelpers.classPrivateFieldGet(this, _isCollapsed));
	  var rootNode = this.get();
	  var wrapperNode = rootNode.querySelector('.crm-copilot-textbox__wrapper');
	  var iconNode = rootNode.querySelector('.crm-copilot-textbox__collapse-icon');
	  var bodyNode = rootNode.querySelector('.crm-copilot-textbox__body');
	  var searchNode = rootNode.querySelector('.crm-copilot-textbox__search');

	  // some animation
	  main_core.Dom.removeClass(bodyNode, 'body-toggle-animation');
	  main_core.Dom.addClass(bodyNode, 'body-toggle-animation');
	  if (babelHelpers.classPrivateFieldGet(this, _isCollapsed)) {
	    main_core.Dom.style(rootNode, 'padding-bottom', 0);
	    main_core.Dom.removeClass(wrapperNode, babelHelpers.classPrivateFieldGet(this, _className).bodyExpanded);
	    main_core.Dom.addClass(searchNode, babelHelpers.classPrivateFieldGet(this, _className).nodeHidden);
	    _classPrivateMethodGet$1(this, _switchStyle, _switchStyle2).call(this, iconNode, babelHelpers.classPrivateFieldGet(this, _className).arrowTopIcon, babelHelpers.classPrivateFieldGet(this, _className).arrowDownIcon);
	  } else {
	    main_core.Dom.style(rootNode, 'padding-bottom', ROOT_CONTAINER_BOTTOM_PADDING);
	    main_core.Dom.addClass(wrapperNode, babelHelpers.classPrivateFieldGet(this, _className).bodyExpanded);
	    main_core.Dom.removeClass(searchNode, babelHelpers.classPrivateFieldGet(this, _className).nodeHidden);
	    _classPrivateMethodGet$1(this, _switchStyle, _switchStyle2).call(this, iconNode, babelHelpers.classPrivateFieldGet(this, _className).arrowDownIcon, babelHelpers.classPrivateFieldGet(this, _className).arrowTopIcon);
	  }
	}

	exports.Textbox = Textbox;
	exports.Attention = Attention;
	exports.AttentionPresets = AttentionPresets;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX,BX.UI.IconSet));
//# sourceMappingURL=textbox.bundle.js.map
