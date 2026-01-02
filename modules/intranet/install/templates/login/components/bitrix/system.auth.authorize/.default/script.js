this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var _authContainerNode = new WeakMap();

	var _isQrAvailable = new WeakMap();

	var _qrText = new WeakMap();

	var _qrConfig = new WeakMap();

	var SystemAuthAuthorize = /*#__PURE__*/function () {
	  function SystemAuthAuthorize(params) {
	    babelHelpers.classCallCheck(this, SystemAuthAuthorize);

	    _authContainerNode.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _isQrAvailable.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _qrText.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _qrConfig.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _authContainerNode, params.authContainerNode);
	    babelHelpers.classPrivateFieldSet(this, _isQrAvailable, params.isQrAvailable === 'Y');
	    babelHelpers.classPrivateFieldSet(this, _qrText, params.qrText || '');
	    babelHelpers.classPrivateFieldSet(this, _qrConfig, params.qrConfig || '');

	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _authContainerNode))) {
	      return;
	    }

	    this.buttonNode = babelHelpers.classPrivateFieldGet(this, _authContainerNode).querySelector("[data-role='auth-form-button']");
	    this.init();

	    if (babelHelpers.classPrivateFieldGet(this, _isQrAvailable)) {
	      this.initQrCode();
	    }
	  }

	  babelHelpers.createClass(SystemAuthAuthorize, [{
	    key: "init",
	    value: function init() {
	      var _this = this;

	      BX.UI.Hint.init(babelHelpers.classPrivateFieldGet(this, _authContainerNode));

	      if (main_core.Type.isDomNode(this.buttonNode)) {
	        main_core.Event.bind(this.buttonNode, 'click', function () {
	          var spinnerNode = _this.buttonNode.querySelector('.intranet-text-btn__spinner');

	          if (main_core.Type.isDomNode(spinnerNode)) {
	            main_core.Dom.style(spinnerNode, 'display', 'block');
	          }
	        });
	      }
	    }
	  }, {
	    key: "initQrCode",
	    value: function initQrCode() {
	      var _this2 = this;

	      new QRCode('bx_auth_qr_code', {
	        text: babelHelpers.classPrivateFieldGet(this, _qrText),
	        width: 220,
	        height: 220,
	        colorDark: '#000000',
	        colorLight: '#ffffff'
	      });

	      if (!babelHelpers.classPrivateFieldGet(this, _qrConfig)) {
	        return;
	      }

	      this.qrCodeSuccessIcon = babelHelpers.classPrivateFieldGet(this, _authContainerNode).querySelector('.b24net-qr-scan-form__code-overlay');
	      var Pull = new BX.PullClient();
	      Pull.subscribe({
	        moduleId: 'main',
	        command: 'qrAuthorize',
	        callback: function callback(params) {
	          if (params.token) {
	            _this2.showQrCodeSuccessIcon();

	            BX.ajax.runAction('main.qrcodeauth.authenticate', {
	              data: {
	                token: params.token,
	                remember: BX('USER_REMEMBER_QR') && BX('USER_REMEMBER_QR').checked ? 1 : 0
	              }
	            }).then(function (response) {
	              _this2.hideQrCodeSuccessIcon();

	              if (response.status === 'success') {
	                window.location = params.redirectUrl !== '' ? params.redirectUrl : window.location;
	              }
	            });
	          }
	        }
	      });
	      Pull.start(babelHelpers.classPrivateFieldGet(this, _qrConfig));
	    }
	  }, {
	    key: "showQrCodeSuccessIcon",
	    value: function showQrCodeSuccessIcon() {
	      if (!main_core.Type.isDomNode(this.qrCodeSuccessIcon)) {
	        return;
	      }

	      main_core.Dom.addClass(this.qrCodeSuccessIcon, 'b24net-qr-scan-form__code-overlay--active');
	    }
	  }, {
	    key: "hideQrCodeSuccessIcon",
	    value: function hideQrCodeSuccessIcon() {
	      if (!main_core.Type.isDomNode(this.qrCodeSuccessIcon)) {
	        return;
	      }

	      main_core.Dom.removeClass(this.qrCodeSuccessIcon, 'b24net-qr-scan-form__code-overlay--active');
	    }
	  }]);
	  return SystemAuthAuthorize;
	}();

	exports.SystemAuthAuthorize = SystemAuthAuthorize;

}((this.BX.Intranet = this.BX.Intranet || {}),BX));
//# sourceMappingURL=script.js.map
