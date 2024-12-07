/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_designTokens,ui_fonts_opensans,main_core,ui_iconSet_api_core) {
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

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11;
	var Textbox = /*#__PURE__*/function () {
	  function Textbox() {
	    var _options$attentions;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Textbox);
	    babelHelpers.defineProperty(this, "textContainerID", 'crm-copilot-text-container');
	    babelHelpers.defineProperty(this, "searchIcon", 'ui-ctl-icon-search');
	    babelHelpers.defineProperty(this, "clearIcon", 'ui-ctl-icon-clear');
	    babelHelpers.defineProperty(this, "searchInputPlaceholder", main_core.Loc.getMessage('CRM_COPILOT_TEXTBOX_SEARCH_PLACEHOLDER'));
	    this.setText(options.text);
	    this.title = main_core.Type.isString(options.title) ? options.title : '';
	    this.enableSearch = main_core.Type.isBoolean(options.enableSearch) ? options.enableSearch : true;
	    this.previousTextContent = main_core.Type.isElementNode(options.previousTextContent) ? options.previousTextContent : null;
	    this.attentions = (_options$attentions = options.attentions) !== null && _options$attentions !== void 0 ? _options$attentions : [];
	  }
	  babelHelpers.createClass(Textbox, [{
	    key: "setText",
	    value: function setText(text) {
	      this.text = main_core.Type.isString(text) ? this.prepareText(text) : '';
	    }
	  }, {
	    key: "prepareText",
	    value: function prepareText(text) {
	      return text.replaceAll(/\r?\n/g, '<br>');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.rootContainer = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox\"></div>"])));
	      main_core.Dom.append(this.getHeaderContainer(), this.rootContainer);
	      main_core.Dom.append(this.getPreviousTextContainer(), this.rootContainer);
	      main_core.Dom.append(this.getContentContainer(), this.rootContainer);
	      main_core.Dom.append(this.getAttentionsContainer(), this.rootContainer);
	    }
	  }, {
	    key: "get",
	    value: function get() {
	      return this.rootContainer;
	    }
	  }, {
	    key: "getContentContainer",
	    value: function getContentContainer() {
	      var contentContainer = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__content\"></div>"])));
	      var textContainer = this.getTextContainer();
	      main_core.Event.bind(textContainer, 'beforeinput', function (e) {
	        e.preventDefault();
	      });
	      main_core.Dom.append(textContainer, contentContainer);
	      return contentContainer;
	    }
	  }, {
	    key: "getTextContainer",
	    value: function getTextContainer() {
	      if (this.textContainer) {
	        return this.textContainer;
	      }
	      this.textContainer = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div \n\t\t\t\tid=\"", "\" \n\t\t\t\tclass=\"crm-copilot-textbox__text-container\" \n\t\t\t\tcontenteditable=\"true\" \n\t\t\t\tspellcheck=\"false\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.textContainerID, this.text);
	      return this.textContainer;
	    }
	  }, {
	    key: "getHeaderContainer",
	    value: function getHeaderContainer() {
	      return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-copilot-textbox__header\">\n\t\t\t\t<div class=\"crm-copilot-textbox__title\">", "</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.title), this.getSearchContainer());
	    }
	  }, {
	    key: "getSearchContainer",
	    value: function getSearchContainer() {
	      var _this = this;
	      if (!this.enableSearch) {
	        return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral([""])));
	      }
	      var searchNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-no-border crm-copilot-textbox__search\"></div>"])));
	      var searchBtn = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<a class=\"ui-ctl-after ", " crm-copilot-textbox__search-btn\"></a>"])), this.searchIcon);
	      var searchInput = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input \n\t\t\t\ttype=\"text\" \n\t\t\t\tplaceholder=\"", "\" \n\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox crm-copilot-textbox__search-input\"\n\t\t\t>\n\t\t"])), main_core.Text.encode(this.searchInputPlaceholder));
	      searchInput.oninput = function () {
	        _this.resetTextContainer();
	        var value = searchInput.value;
	        if (!value) {
	          _this.switchStyle(searchBtn, _this.clearIcon, _this.searchIcon);
	          return;
	        }
	        _this.switchStyle(searchBtn, _this.searchIcon, _this.clearIcon);

	        // Highlights pieces of text that are not part of a tag
	        var regexp = new RegExp("((?<!<[^>]*?)(".concat(value, ")(?![^<]*?>))"), 'gi');
	        var textContainer = _this.getTextContainer();
	        textContainer.innerHTML = textContainer.innerHTML.replace(regexp, '<span class="search-item">$&</span>');
	      };
	      var searchInputFocused = false;
	      searchInput.onblur = function (event) {
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
	            _this.switchStyle(searchBtn, _this.clearIcon, _this.searchIcon);
	            _this.resetTextContainer();
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
	  }, {
	    key: "getAttentionsContainer",
	    value: function getAttentionsContainer() {
	      if (!main_core.Type.isArrayFilled(this.attentions)) {
	        return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral([""])));
	      }
	      var attentionsContainer = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__attentions\"></div>"])));
	      this.attentions.forEach(function (attention) {
	        main_core.Dom.append(attention.render(), attentionsContainer);
	      });
	      return attentionsContainer;
	    }
	  }, {
	    key: "getPreviousTextContainer",
	    value: function getPreviousTextContainer() {
	      return main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__previous-text\">", "</div>"])), this.previousTextContent);
	    }
	  }, {
	    key: "resetTextContainer",
	    value: function resetTextContainer() {
	      this.getTextContainer().innerHTML = this.text;
	    }
	  }, {
	    key: "switchStyle",
	    value: function switchStyle(node, fromStyle, toStyle) {
	      if (main_core.Dom.hasClass(node, fromStyle) && !main_core.Dom.hasClass(node, toStyle)) {
	        main_core.Dom.addClass(node, toStyle);
	        main_core.Dom.removeClass(node, fromStyle);
	      }
	    }
	  }]);
	  return Textbox;
	}();

	exports.Attention = Attention;
	exports.AttentionPresets = AttentionPresets;
	exports.Textbox = Textbox;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX,BX,BX,BX.UI.IconSet));
//# sourceMappingURL=textbox.bundle.js.map
