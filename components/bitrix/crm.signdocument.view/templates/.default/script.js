/* eslint-disable */
(function (exports,main_core,ui_buttons) {
	'use strict';

	var ButtonState = function ButtonState() {
	  babelHelpers.classCallCheck(this, ButtonState);
	};
	babelHelpers.defineProperty(ButtonState, "DEFAULT", '');
	babelHelpers.defineProperty(ButtonState, "LOADING", 'loading');
	babelHelpers.defineProperty(ButtonState, "DISABLED", 'disabled');
	babelHelpers.defineProperty(ButtonState, "HIDDEN", 'hidden');
	babelHelpers.defineProperty(ButtonState, "AI_LOADING", 'ai-loading');

	var _templateObject, _templateObject2;
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var Viewer = main_core.Reflection.namespace('BX.UI.Viewer');
	var defaultComponent = null;

	/**
	 * @memberOf BX.Crm.Component
	 */
	var _initViewer = /*#__PURE__*/new WeakSet();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var SignDocumentView = /*#__PURE__*/function () {
	  function SignDocumentView(parameters) {
	    babelHelpers.classCallCheck(this, SignDocumentView);
	    _classPrivateMethodInitSpec(this, _bindEvents);
	    _classPrivateMethodInitSpec(this, _initViewer);
	    this.pdfNode = parameters.pdfNode;
	    this.pdfSource = parameters.pdfSource;
	    this.printButton = ui_buttons.ButtonManager.createByUniqId('crm-document-print');
	    this.downloadButton = ui_buttons.ButtonManager.createByUniqId('crm-document-download');
	    _classPrivateMethodGet(this, _initViewer, _initViewer2).call(this);
	    _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	    defaultComponent = this;
	  }
	  babelHelpers.createClass(SignDocumentView, [{
	    key: "getViewer",
	    value: function getViewer() {
	      var _this$viewer;
	      if (!this.viewer && this.pdfNode) {
	        this.viewer = new Viewer.SingleDocumentController({
	          baseContainer: this.pdfNode,
	          stretch: true
	        });
	      }
	      return (_this$viewer = this.viewer) !== null && _this$viewer !== void 0 ? _this$viewer : null;
	    }
	  }], [{
	    key: "getDefaultComponent",
	    value: function getDefaultComponent() {
	      return defaultComponent;
	    }
	  }]);
	  return SignDocumentView;
	}();
	function _initViewer2() {
	  var viewer = this.getViewer();
	  if (!viewer) {
	    return;
	  }
	  viewer.setItems([Viewer.buildItemByNode(this.pdfNode)]);
	  viewer.setPdfSource(this.pdfSource);
	  viewer.setScale(1.2);
	  viewer.open();
	}
	function _bindEvents2() {
	  var _this3 = this;
	  if (this.printButton && this.getViewer()) {
	    this.printButton.bindEvent('click', function () {
	      _this3.getViewer().print();
	    });
	  }
	  if (this.downloadButton) {
	    this.downloadButton.bindEvent('click', function () {
	      window.open(_this3.pdfSource, '_blank');
	    });
	  }
	}
	var _container = /*#__PURE__*/new WeakMap();
	var _button = /*#__PURE__*/new WeakMap();
	var _docSend = /*#__PURE__*/new WeakMap();
	var _memberIds = /*#__PURE__*/new WeakMap();
	var _updateStatus = /*#__PURE__*/new WeakSet();
	var _createButton = /*#__PURE__*/new WeakSet();
	var _createButtonText = /*#__PURE__*/new WeakSet();
	var _setButtonText = /*#__PURE__*/new WeakSet();
	var _formatSeconds = /*#__PURE__*/new WeakSet();
	var _formatNumber = /*#__PURE__*/new WeakSet();
	var SignDocumentViewSendWidget = /*#__PURE__*/function () {
	  function SignDocumentViewSendWidget() {
	    var _options$memberIds,
	      _BX,
	      _BX$Sign,
	      _BX$Sign$V,
	      _this = this;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, SignDocumentViewSendWidget);
	    _classPrivateMethodInitSpec(this, _formatNumber);
	    _classPrivateMethodInitSpec(this, _formatSeconds);
	    _classPrivateMethodInitSpec(this, _setButtonText);
	    _classPrivateMethodInitSpec(this, _createButtonText);
	    _classPrivateMethodInitSpec(this, _createButton);
	    _classPrivateMethodInitSpec(this, _updateStatus);
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _button, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _docSend, {
	      writable: true,
	      value: undefined
	    });
	    _classPrivateFieldInitSpec(this, _memberIds, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldSet(this, _memberIds, (_options$memberIds = options === null || options === void 0 ? void 0 : options.memberIds) !== null && _options$memberIds !== void 0 ? _options$memberIds : []);
	    babelHelpers.classPrivateFieldSet(this, _button, _classPrivateMethodGet(this, _createButton, _createButton2).call(this));
	    if (!main_core.Type.isUndefined((_BX = BX) === null || _BX === void 0 ? void 0 : (_BX$Sign = _BX.Sign) === null || _BX$Sign === void 0 ? void 0 : (_BX$Sign$V = _BX$Sign.V2) === null || _BX$Sign$V === void 0 ? void 0 : _BX$Sign$V.DocumentSend)) {
	      babelHelpers.classPrivateFieldSet(this, _docSend, new BX.Sign.V2.DocumentSend());
	      babelHelpers.classPrivateFieldGet(this, _docSend).subscribeOnce('ready', function (event) {
	        var _event$getData, _event$getData$readyM;
	        if ((_event$getData = event.getData()) !== null && _event$getData !== void 0 && (_event$getData$readyM = _event$getData.readyMembers) !== null && _event$getData$readyM !== void 0 && _event$getData$readyM.length) {
	          _this.enableButton();
	        }
	      });
	      babelHelpers.classPrivateFieldGet(this, _docSend).loadStatus(babelHelpers.classPrivateFieldGet(this, _memberIds));
	    }
	  }
	  babelHelpers.createClass(SignDocumentViewSendWidget, [{
	    key: "enableButton",
	    value: function enableButton() {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _button), 'crm__sign-document-view-resend--button-disabled');
	    }
	  }, {
	    key: "disableButton",
	    value: function disableButton() {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _button), 'crm__sign-document-view-resend--button-disabled');
	    }
	  }, {
	    key: "isButtonDisabled",
	    value: function isButtonDisabled() {
	      return main_core.Dom.hasClass(babelHelpers.classPrivateFieldGet(this, _button), 'crm__sign-document-view-resend--button-disabled');
	    }
	  }, {
	    key: "renderTo",
	    value: function renderTo(node) {
	      main_core.Dom.append(this.render(), node);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm__sign-document-view-resend--container\">\n\t\t\t\t\t<div class=\"crm__sign-document-view-resend--list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _button)));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "send",
	    value: function send(memberIds) {
	      return babelHelpers.classPrivateFieldGet(this, _docSend).send(memberIds);
	    }
	  }, {
	    key: "disableWithTimer",
	    value: function disableWithTimer(sec) {
	      var _this2 = this;
	      this.disableButton();
	      var remainingSeconds = sec;
	      _classPrivateMethodGet(this, _setButtonText, _setButtonText2).call(this, _classPrivateMethodGet(this, _createButtonText, _createButtonText2).call(this, remainingSeconds));
	      var timer = setInterval(function () {
	        if (remainingSeconds < 1) {
	          clearInterval(timer);
	          _classPrivateMethodGet(_this2, _setButtonText, _setButtonText2).call(_this2, _classPrivateMethodGet(_this2, _createButtonText, _createButtonText2).call(_this2, 0));
	          _this2.enableButton();
	          return;
	        }
	        remainingSeconds--;
	        _classPrivateMethodGet(_this2, _setButtonText, _setButtonText2).call(_this2, _classPrivateMethodGet(_this2, _createButtonText, _createButtonText2).call(_this2, remainingSeconds));
	      }, 1000);
	    }
	  }]);
	  return SignDocumentViewSendWidget;
	}();
	function _updateStatus2(memberIds) {
	  return babelHelpers.classPrivateFieldGet(this, _docSend).loadStatus(memberIds);
	}
	function _createButton2() {
	  var _this4 = this;
	  var onResendBtnClick = function onResendBtnClick(event) {
	    if (_this4.isButtonDisabled()) {
	      return;
	    }
	    _classPrivateMethodGet(_this4, _updateStatus, _updateStatus2).call(_this4, babelHelpers.classPrivateFieldGet(_this4, _memberIds)).then(function (readyMembers) {
	      if (readyMembers.length > 0) {
	        return _this4.send(readyMembers).then(function () {
	          _this4.disableWithTimer(60);
	          BX.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_NOTIFY_SUCCESS')
	          });
	        });
	      }
	      throw new Error('no members in appropriate status');
	    });
	  };
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"crm__sign-document-view-resend--button crm__sign-document-view-resend--button-disabled\"\n\t\t\t\tonclick=\"", "\"\n\t\t\t>\n\t\t\t\t<div class=\"crm__sign-document-view-resend--button-icon --service-sms\"></div>\n\t\t\t\t<div class=\"crm__sign-document-view-resend--button-main-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm__sign-document-view-resend--button-helper\"></div>\n\t\t\t</div>\n\t\t"])), onResendBtnClick.bind(this), main_core.Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN'));
	}
	function _createButtonText2(remainingSeconds) {
	  return remainingSeconds > 0 ? main_core.Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN_TIMER', {
	    '#COUNTDOWN#': _classPrivateMethodGet(this, _formatSeconds, _formatSeconds2).call(this, remainingSeconds)
	  }) : main_core.Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN');
	}
	function _setButtonText2(text) {
	  babelHelpers.classPrivateFieldGet(this, _button).querySelector('.crm__sign-document-view-resend--button-main-title').textContent = text;
	}
	function _formatSeconds2(sec) {
	  var minutes = Math.floor(sec / 60);
	  var seconds = sec % 60;
	  var formatMinutes = _classPrivateMethodGet(this, _formatNumber, _formatNumber2).call(this, minutes);
	  var formatSeconds = _classPrivateMethodGet(this, _formatNumber, _formatNumber2).call(this, seconds);
	  return "".concat(formatMinutes, ":").concat(formatSeconds);
	}
	function _formatNumber2(num) {
	  return num < 10 ? "0".concat(num) : num;
	}
	namespace.SignDocumentView = SignDocumentView;
	namespace.SignDocumentViewSendWidget = SignDocumentViewSendWidget;

}((this.window = this.window || {}),BX,BX.UI));
//# sourceMappingURL=script.js.map
