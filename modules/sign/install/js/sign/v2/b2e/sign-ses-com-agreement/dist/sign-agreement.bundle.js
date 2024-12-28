/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_popup,ui_buttons,sign_v2_api) {
	'use strict';

	let _ = t => t,
	  _t;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _subscribeToEndOfScroll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToEndOfScroll");
	var _getSuccessButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSuccessButton");
	class SignSesComAgreement {
	  constructor(data) {
	    Object.defineProperty(this, _getSuccessButton, {
	      value: _getSuccessButton2
	    });
	    Object.defineProperty(this, _subscribeToEndOfScroll, {
	      value: _subscribeToEndOfScroll2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	  }
	}
	function _getPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  const content = main_core.Tag.render(_t || (_t = _`${0}`), babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].body);
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    titleBar: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].title,
	    content,
	    className: 'sign__agreement_popup',
	    lightShadow: true,
	    maxWidth: 700,
	    overlay: true,
	    width: 700,
	    height: 600,
	    autoHide: false,
	    closeByEsc: false,
	    draggable: false,
	    closeIcon: false,
	    animation: 'fading-slide',
	    cacheable: true,
	    buttons: [babelHelpers.classPrivateFieldLooseBase(this, _getSuccessButton)[_getSuccessButton]()]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeToEndOfScroll)[_subscribeToEndOfScroll]();
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _subscribeToEndOfScroll2() {
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().getContentContainer();
	  const detectEndOfScroll = () => {
	    const gap = container.offsetHeight / 15;
	    const endOfScroll = container.scrollHeight - container.scrollTop - container.offsetHeight <= gap;
	    if (endOfScroll) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().getButton('success').setDisabled(false).setActive(true);
	    }
	  };
	  container.addEventListener('scroll', main_core.Runtime.throttle(detectEndOfScroll, 200));
	}
	function _getSuccessButton2() {
	  return new ui_buttons.Button({
	    id: 'success',
	    size: ui_buttons.Button.Size.MEDIUM,
	    color: ui_buttons.Button.Color.SUCCESS,
	    text: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].buttonText,
	    round: true,
	    events: {
	      click: () => {
	        return new sign_v2_api.Api().setDecisionToSesB2eAgreement().then(() => babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().close());
	      }
	    }
	  }).setDisabled(true);
	}

	exports.SignSesComAgreement = SignSesComAgreement;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Main,BX.UI,BX.Sign.V2));
//# sourceMappingURL=sign-agreement.bundle.js.map
