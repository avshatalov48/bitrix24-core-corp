this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_qrcode,main_core,main_popup) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _link = /*#__PURE__*/new WeakMap();

	var _qrNode = /*#__PURE__*/new WeakMap();

	var _button = /*#__PURE__*/new WeakMap();

	var _containerCopyLink = /*#__PURE__*/new WeakMap();

	var _containerInputLink = /*#__PURE__*/new WeakMap();

	var _renderButton = /*#__PURE__*/new WeakSet();

	var _getImageContainer = /*#__PURE__*/new WeakSet();

	var _getPopup = /*#__PURE__*/new WeakSet();

	var _renderImage = /*#__PURE__*/new WeakSet();

	var _getContainerInputLink = /*#__PURE__*/new WeakSet();

	var _getContainerCopyLink = /*#__PURE__*/new WeakSet();

	var Qr = /*#__PURE__*/function () {
	  function Qr(options) {
	    babelHelpers.classCallCheck(this, Qr);

	    _getContainerCopyLink.add(this);

	    _getContainerInputLink.add(this);

	    _renderImage.add(this);

	    _getPopup.add(this);

	    _getImageContainer.add(this);

	    _renderButton.add(this);

	    _link.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _qrNode.set(this, {
	      writable: true,
	      value: null
	    });

	    _button.set(this, {
	      writable: true,
	      value: null
	    });

	    _containerCopyLink.set(this, {
	      writable: true,
	      value: null
	    });

	    _containerInputLink.set(this, {
	      writable: true,
	      value: null
	    });

	    babelHelpers.classPrivateFieldSet(this, _link, options.link);
	  }

	  babelHelpers.createClass(Qr, [{
	    key: "renderTo",
	    value: function renderTo(target) {
	      var button = _classPrivateMethodGet(this, _renderButton, _renderButton2).call(this);

	      target.appendChild(button);
	      return button;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      _classPrivateMethodGet(this, _renderImage, _renderImage2).call(this);

	      if (!_classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).isShown()) {
	        _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).show();
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (_classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).isShown()) {
	        _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).close();
	      }
	    }
	  }]);
	  return Qr;
	}();

	function _renderButton2() {
	  var _this = this;

	  if (!babelHelpers.classPrivateFieldGet(this, _button)) {
	    babelHelpers.classPrivateFieldSet(this, _button, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button\n\t\t\t\t\ttype=\"button\"\n\t\t\t\t\tclass=\"crm-webform-qr-btn ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps ui-btn-icon-share\"\n\t\t\t\t>\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), main_core.Loc.getMessage('CRM_WEBFORM_QR_OPEN')));
	    babelHelpers.classPrivateFieldGet(this, _button).addEventListener("click", function (e) {
	      e.stopPropagation();

	      _this.show();
	    });
	  }

	  return babelHelpers.classPrivateFieldGet(this, _button);
	}

	function _getImageContainer2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _qrNode)) {
	    babelHelpers.classPrivateFieldSet(this, _qrNode, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-webform__popup-image\"></div>\n\t\t\t"]))));
	  }

	  return babelHelpers.classPrivateFieldGet(this, _qrNode);
	}

	function _getPopup2() {
	  if (!this.popup) {
	    var container = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-webform__scope\">\n\t\t\t\t\t<div class=\"crm-webform__popup-container --qr\">\n\t\t\t\t\t\t<div class=\"crm-webform__popup-wrapper\">\n\t\t\t\t\t\t\t<div class=\"crm-webform__popup-content\">\n\t\t\t\t\t\t\t\t<div class=\"crm-webform__popup-text\">", "</div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t<div class=\"crm-webform__popup-text --sm\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"crm-webform__popup-buttons\">\n\t\t\t\t\t\t\t\t\t<a href=\"", "\" target=\"_blank\" class=\"ui-btn ui-btn-light-border ui-btn-round\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-webform__popup-bottom\">\n\t\t\t\t\t\t\t\t<a href=\"", "\" target=\"_blank\" class=\"crm-webform__popup-url\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('CRM_WEBFORM_QR_TITLE'), _classPrivateMethodGet(this, _getImageContainer, _getImageContainer2).call(this), main_core.Loc.getMessage('CRM_WEBFORM_QR_DESC'), babelHelpers.classPrivateFieldGet(this, _link), main_core.Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_OPEN_SITE'), babelHelpers.classPrivateFieldGet(this, _link), babelHelpers.classPrivateFieldGet(this, _link), _classPrivateMethodGet(this, _getContainerInputLink, _getContainerInputLink2).call(this), _classPrivateMethodGet(this, _getContainerCopyLink, _getContainerCopyLink2).call(this));
	    this.popup = new main_popup.Popup({
	      className: 'crm-webform__status-popup',
	      content: container,
	      bindElement: window,
	      width: 405,
	      minWidth: 220,
	      closeByEsc: true,
	      autoHide: true,
	      animation: 'fading-slide',
	      closeIcon: true,
	      padding: 0
	    });
	  }

	  return this.popup;
	}

	function _renderImage2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _qrNode)) {
	    new QRCode(_classPrivateMethodGet(this, _getImageContainer, _getImageContainer2).call(this), {
	      text: babelHelpers.classPrivateFieldGet(this, _link),
	      width: 250,
	      height: 250
	    });
	  }
	}

	function _getContainerInputLink2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _containerInputLink)) {
	    babelHelpers.classPrivateFieldSet(this, _containerInputLink, main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input \n\t\t\t\t\ttype=\"text\" \n\t\t\t\t\tstyle=\"position: absolute; opacity: 0; pointer-events: none\"\n\t\t\t\t\tvalue=\"", "\">\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _link)));
	  }

	  return babelHelpers.classPrivateFieldGet(this, _containerInputLink);
	}

	function _getContainerCopyLink2() {
	  var _this2 = this;

	  if (!babelHelpers.classPrivateFieldGet(this, _containerCopyLink)) {
	    babelHelpers.classPrivateFieldSet(this, _containerCopyLink, main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-webform__popup-copy\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_COPY_LINK')));
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _containerCopyLink), 'click', function () {
	      _classPrivateMethodGet(_this2, _getContainerInputLink, _getContainerInputLink2).call(_this2).select();

	      document.execCommand('copy');
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_COPY_LINK_COMPLETE'),
	        autoHideDelay: 2000
	      });
	    });
	  }

	  return babelHelpers.classPrivateFieldGet(this, _containerCopyLink);
	}

	exports.Qr = Qr;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX,BX,BX.Main));
//# sourceMappingURL=qr.bundle.js.map
