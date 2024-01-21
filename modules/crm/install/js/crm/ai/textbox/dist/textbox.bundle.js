/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	var Textbox = /*#__PURE__*/function () {
	  function Textbox() {
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
	      this.rootContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox\"></div>"])));
	      main_core.Dom.append(this.getHeaderContainer(), this.rootContainer);
	      main_core.Dom.append(this.getPreviousTextContainer(), this.rootContainer);
	      main_core.Dom.append(this.getContentContainer(), this.rootContainer);
	    }
	  }, {
	    key: "get",
	    value: function get() {
	      return this.rootContainer;
	    }
	  }, {
	    key: "getContentContainer",
	    value: function getContentContainer() {
	      var contentContainer = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__content\"></div>"])));
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
	      this.textContainer = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div \n\t\t\t\tid=\"", "\" \n\t\t\t\tclass=\"crm-copilot-textbox__text-container\" \n\t\t\t\tcontenteditable=\"true\" \n\t\t\t\tspellcheck=\"false\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.textContainerID, this.text);
	      return this.textContainer;
	    }
	  }, {
	    key: "getHeaderContainer",
	    value: function getHeaderContainer() {
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-copilot-textbox__header\">\n\t\t\t\t<div class=\"crm-copilot-textbox__title\">", "</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.title), this.getSearchContainer());
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
	    key: "getPreviousTextContainer",
	    value: function getPreviousTextContainer() {
	      return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-copilot-textbox__previous-text\">", "</div>"])), this.previousTextContent);
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

	exports.Textbox = Textbox;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX));
//# sourceMappingURL=textbox.bundle.js.map
