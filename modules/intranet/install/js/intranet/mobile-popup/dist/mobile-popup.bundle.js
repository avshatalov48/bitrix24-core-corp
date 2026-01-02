/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,main_core_events,ui_buttons,ui_analytics,ui_bannerDispatcher) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _authLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("authLink");
	var _isTablet = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTablet");
	var _continueInMobileBrowserClicked = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("continueInMobileBrowserClicked");
	var _continueInTabletBrowserClicked = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("continueInTabletBrowserClicked");
	var _collabId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("collabId");
	var _isCollaber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCollaber");
	var _isMobileContinueButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMobileContinueButton");
	var _isTabletContinueButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTabletContinueButton");
	var _position = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("position");
	var _calculatePopupPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculatePopupPosition");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _getContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _renderImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderImage");
	var _renderFooter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFooter");
	var _renderContinueButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContinueButton");
	var _renderHideButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHideButton");
	var _sendAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalytics");
	class MobilePopup extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _sendAnalytics, {
	      value: _sendAnalytics2
	    });
	    Object.defineProperty(this, _renderHideButton, {
	      value: _renderHideButton2
	    });
	    Object.defineProperty(this, _renderContinueButton, {
	      value: _renderContinueButton2
	    });
	    Object.defineProperty(this, _renderFooter, {
	      value: _renderFooter2
	    });
	    Object.defineProperty(this, _renderImage, {
	      value: _renderImage2
	    });
	    Object.defineProperty(this, _getContent, {
	      value: _getContent2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _calculatePopupPosition, {
	      value: _calculatePopupPosition2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _authLink, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isTablet, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _continueInMobileBrowserClicked, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _continueInTabletBrowserClicked, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _collabId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isCollaber, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isMobileContinueButton, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isTabletContinueButton, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _position, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Intranet.MobilePopup');
	    babelHelpers.classPrivateFieldLooseBase(this, _authLink)[_authLink] = main_core.Extension.getSettings('intranet.mobile-popup').authLink;
	    babelHelpers.classPrivateFieldLooseBase(this, _collabId)[_collabId] = main_core.Extension.getSettings('intranet.mobile-popup').collabId;
	    babelHelpers.classPrivateFieldLooseBase(this, _isCollaber)[_isCollaber] = main_core.Extension.getSettings('intranet.mobile-popup').isCollaber;
	    babelHelpers.classPrivateFieldLooseBase(this, _continueInMobileBrowserClicked)[_continueInMobileBrowserClicked] = main_core.Extension.getSettings('intranet.mobile-popup').continueInMobileBrowserClicked;
	    babelHelpers.classPrivateFieldLooseBase(this, _continueInTabletBrowserClicked)[_continueInTabletBrowserClicked] = main_core.Extension.getSettings('intranet.mobile-popup').continueInTabletBrowserClicked;
	    babelHelpers.classPrivateFieldLooseBase(this, _isMobileContinueButton)[_isMobileContinueButton] = main_core.Extension.getSettings('intranet.mobile-popup').isMobileContinueButton;
	    babelHelpers.classPrivateFieldLooseBase(this, _isTabletContinueButton)[_isTabletContinueButton] = main_core.Extension.getSettings('intranet.mobile-popup').isTabletContinueButton;
	    babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] = window.screen.width >= 768;
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('push_mobapp_show');
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _continueInTabletBrowserClicked)[_continueInTabletBrowserClicked] || !babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _continueInMobileBrowserClicked)[_continueInMobileBrowserClicked]) {
	      return;
	    }
	    ui_bannerDispatcher.BannerDispatcher.critical.toQueue(onDone => {
	      var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	      babelHelpers.classPrivateFieldLooseBase(this, _calculatePopupPosition)[_calculatePopupPosition]();
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup))[_popup]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup](onDone);
	      (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr3.show();
	      document.body.scrollIntoView();
	    });
	  }
	}
	function _calculatePopupPosition2() {
	  const isAirTemplate = BX.Reflection.getClass('BX.Intranet.Bitrix24.Template') !== null;
	  const minHeight = 602;
	  const minWidth = babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] ? 375 : 321;
	  const height = window.screen.height - minHeight >= 0 ? (window.screen.height - minHeight) / 2 : 0;
	  const width = window.screen.width - minWidth >= 0 ? (window.screen.width - minWidth) / 2 : 0;
	  let offsetTop = height - window.screen.height;
	  if (isAirTemplate) {
	    offsetTop = height - document.body.scrollHeight;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _position)[_position] = {
	    minHeight,
	    minWidth,
	    offsetTop,
	    offsetLeft: width
	  };
	}
	function _createPopup2(onDone) {
	  return main_popup.PopupWindowManager.create('main-intranet-mobile-popup', document.body, {
	    overlay: true,
	    borderRadius: '24px',
	    contentBorderRadius: '24px',
	    closeIcon: false,
	    minWidth: babelHelpers.classPrivateFieldLooseBase(this, _position)[_position].minWidth,
	    minHeight: babelHelpers.classPrivateFieldLooseBase(this, _position)[_position].minHeight,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getContent)[_getContent](),
	    //disableScroll: true,
	    padding: 0,
	    offsetTop: babelHelpers.classPrivateFieldLooseBase(this, _position)[_position].offsetTop,
	    offsetLeft: babelHelpers.classPrivateFieldLooseBase(this, _position)[_position].offsetLeft,
	    className: 'intranet-mobile-popup-wrapper',
	    events: {
	      onClose: () => {
	        this.emit('onClose');
	        onDone();
	      }
	    }
	  });
	}
	function _getContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="intranet-mobile-popup">
				<div class="intranet-mobile-popup__content-wrapper">
					<div class="intranet-mobile-popup__main">
						<span class="intranet-mobile-popup__title">${0}</span>
						${0}
						<p class="intranet-mobile-popup__description">${0}</p>
						${0}
					</div>
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_RECOGNIZE_LINKS_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _renderImage)[_renderImage](), main_core.Loc.getMessage('INTRANET_RECOGNIZE_LINKS_DESCRIPTION'), babelHelpers.classPrivateFieldLooseBase(this, _renderContinueButton)[_renderContinueButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderFooter)[_renderFooter]());
	}
	function _renderImage2() {
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="intranet-mobile-popup__image"></div>
		`));
	}
	function _renderFooter2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _isTabletContinueButton)[_isTabletContinueButton] || !babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _isMobileContinueButton)[_isMobileContinueButton]) {
	    return main_core.Tag.render(_t3 || (_t3 = _`
				<div class="intranet-mobile-popup__footer">
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _renderHideButton)[_renderHideButton]());
	  }
	  return null;
	}
	function _renderContinueButton2() {
	  let text = main_core.Loc.getMessage('INTRANET_RECOGNIZE_LINKS_CONTINUE');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _isTabletContinueButton)[_isTabletContinueButton] || !babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] && babelHelpers.classPrivateFieldLooseBase(this, _isMobileContinueButton)[_isMobileContinueButton]) {
	    text = main_core.Loc.getMessage('INTRANET_RECOGNIZE_LINKS_OPEN_APP');
	  }
	  return new ui_buttons.Button({
	    text,
	    className: 'intranet-mobile-popup__button intranet-mobile-popup__button--continue',
	    size: ui_buttons.Button.Size.LARGE,
	    color: ui_buttons.Button.Color.SUCCESS,
	    round: true,
	    onclick: () => {
	      var _babelHelpers$classPr4;
	      (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr4.close();
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('push_mobapp_click_start');
	      setTimeout(() => {
	        window.location.href = babelHelpers.classPrivateFieldLooseBase(this, _authLink)[_authLink];
	      });
	    }
	  }).render();
	}
	function _renderHideButton2() {
	  return new ui_buttons.CloseButton({
	    text: main_core.Loc.getMessage('INTRANET_RECOGNIZE_LINKS_CONTINUE_IN_BROWSER'),
	    className: 'intranet-mobile-popup__button intranet-mobile-popup__button--hide',
	    size: ui_buttons.Button.Size.SMALL,
	    onclick: () => {
	      var _babelHelpers$classPr5;
	      (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr5.close();
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('push_mobapp_click_stay_browser');
	      const optionName = babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] ? 'tabletPopupContinueToApp' : 'mobilePopupContinueToApp';
	      BX.userOptions.save('intranet', optionName, null, 'Y');
	    }
	  }).render();
	}
	function _sendAnalytics2(event) {
	  const params = {
	    tool: 'intranet',
	    category: 'activation',
	    event,
	    type: 'inside_portal',
	    c_sub_section: 'push_to_mobapp',
	    p2: `popupClosable_${babelHelpers.classPrivateFieldLooseBase(this, _isTablet)[_isTablet] ? 'Y' : 'N'}`
	  };
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isCollaber)[_isCollaber]) {
	    params.p5 = `collabId_${babelHelpers.classPrivateFieldLooseBase(this, _collabId)[_collabId]}`;
	  }
	  ui_analytics.sendData(params);
	}

	exports.MobilePopup = MobilePopup;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Main,BX.Event,BX.UI,BX.UI.Analytics,BX.UI));
//# sourceMappingURL=mobile-popup.bundle.js.map
