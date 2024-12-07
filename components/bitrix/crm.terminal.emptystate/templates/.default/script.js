/* eslint-disable */
(function (exports,main_core,crm_terminal) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var _openPaySystemSlider = /*#__PURE__*/new WeakSet();
	var TerminalEmptyState = /*#__PURE__*/function () {
	  function TerminalEmptyState() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, TerminalEmptyState);
	    _classPrivateMethodInitSpec(this, _openPaySystemSlider);
	    this.emptyState = null;
	    this.renderNode = options.renderNode || null;
	    this.zone = options.zone || null;
	    this.templateFolder = options.templateFolder || '';
	    this.sberbankPaySystemPath = options.sberbankPaySystemPath || null;
	    this.spbPaySystemPath = options.spbPaySystemPath || null;
	  }
	  babelHelpers.createClass(TerminalEmptyState, [{
	    key: "getEmptyState",
	    value: function getEmptyState() {
	      var _this = this;
	      var phrase4 = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_ITEM_4'));
	      var yookassaSbp = phrase4.querySelector('yookassa_sbp');
	      if (this.spbPaySystemPath && main_core.Type.isDomNode(yookassaSbp)) {
	        var yookassaSbpLink = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<a href=\"javascript:void(0)\">", "</a>"])), yookassaSbp.innerHTML);
	        main_core.Event.bind(yookassaSbpLink, 'click', function () {
	          _classPrivateMethodGet(_this, _openPaySystemSlider, _openPaySystemSlider2).call(_this, _this.spbPaySystemPath);
	        });
	        phrase4.replaceChild(yookassaSbpLink, yookassaSbp);
	      }
	      var yookassaSberbank = phrase4.querySelector('yookassa_sberbank');
	      if (this.sberbankPaySystemPath && main_core.Type.isDomNode(yookassaSberbank)) {
	        var yookassaSberbankLink = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<a href=\"javascript:void(0)\">", "</a>"])), yookassaSberbank.innerHTML);
	        main_core.Event.bind(yookassaSberbankLink, 'click', function () {
	          _classPrivateMethodGet(_this, _openPaySystemSlider, _openPaySystemSlider2).call(_this, _this.sberbankPaySystemPath);
	        });
	        phrase4.replaceChild(yookassaSberbankLink, yookassaSberbank);
	      }
	      if (!this.emptyState) {
	        var container = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-terminal-payment-list__empty--all-info\">\n\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--info-text-container\">\n\t\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--info-block-title\">\n\t\t\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--title-quickly\">", "</div>\n\t\t\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--title\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--info-block-content\">\n\t\t\t\t\t\t\t<ul class=\"crm-terminal-payment-list__empty--list-items\">\n\t\t\t\t\t\t\t\t<li class=\"crm-terminal-payment-list__empty--list-item\">", "</li>\n\t\t\t\t\t\t\t\t<li class=\"crm-terminal-payment-list__empty--list-item\">", "</li>\n\t\t\t\t\t\t\t\t<li class=\"crm-terminal-payment-list__empty--list-item\">", "</li>\n\t\t\t\t\t\t\t\t<li class=\"crm-terminal-payment-list__empty--list-item\">", "</li>\n\t\t\t\t\t\t\t\t<li class=\"crm-terminal-payment-list__empty--list-item\">", "</li>\n\t\t\t\t\t\t\t</ul>\n\t\t\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--bth-container\">\n\t\t\t\t\t\t\t\t<a href=\"javascript:void(0)\" class=\"ui-btn ui-btn-lg ui-btn-success crm-terminal-payment-list__empty--bth-radiance\">\n\t\t\t\t\t\t\t\t\t<span class=\"crm-terminal-payment-list__empty--bth-radiance-left\"></span>\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t<span class=\"crm-terminal-payment-list__empty--bth-radiance-right\"></span>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-terminal-payment-list__empty--info-image-block\">\n\t\t\t\t\t\t<img src=\"", "\" alt=\"\" class=\"crm-terminal-payment-list__empty--info-image\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>"])), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_TITLE_MSGVER_1'), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_SUB_TITLE'), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_ITEM_1'), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_ITEM_2'), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_ITEM_3'), phrase4, main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_ITEM_5'), main_core.Loc.getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_TEMPLATE_BUTTON'), this.getImageSrc());
	        this.emptyState = container;
	      }
	      return this.emptyState;
	    }
	  }, {
	    key: "getImageSrc",
	    value: function getImageSrc() {
	      if (this.zone === 'ru') {
	        return this.templateFolder + '/images/terminal_ru.svg';
	      }
	      return this.templateFolder + '/images/terminal_en.svg';
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      main_core.Dom.append(this.getEmptyState(), this.renderNode);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.show();
	      var buttonCreate = this.emptyState.querySelector('.crm-terminal-payment-list__empty--bth-radiance');
	      buttonCreate.addEventListener('click', function () {
	        new crm_terminal.QrAuth().show();
	      });
	    }
	  }]);
	  return TerminalEmptyState;
	}();
	function _openPaySystemSlider2(path) {
	  var _this2 = this;
	  var sliderOptions = {
	    cacheable: false,
	    allowChangeHistory: false,
	    width: 1000,
	    events: {
	      onClose: function onClose() {
	        _this2.emptyState = null;
	        main_core.ajax.runComponentAction('bitrix:crm.terminal.emptystate', 'prepareResult', {
	          mode: 'class',
	          data: {}
	        }).then(function (response) {
	          _this2.sberbankPaySystemPath = response.data.sberbankPaySystemPath;
	          _this2.spbPaySystemPath = response.data.spbPaySystemPath;
	          _this2.renderNode.innerHTML = '';
	          _this2.render();
	        }, function () {
	          return window.location.reload();
	        });
	      }
	    }
	  };
	  BX.SidePanel.Instance.open(path, sliderOptions);
	}
	namespace.TerminalEmptyState = TerminalEmptyState;

}((this.window = this.window || {}),BX,BX.Crm));
//# sourceMappingURL=script.js.map
