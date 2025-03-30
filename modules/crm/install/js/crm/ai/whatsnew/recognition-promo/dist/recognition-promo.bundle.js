/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.AI = this.BX.Crm.AI || {};
(function (exports,main_core,main_popup,ui_buttons,ui_iconSet_api_core,ui_iconSet_main,ui_iconSet_actions,ui_lottie) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _events = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("events");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderHeaderCopilotIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHeaderCopilotIcon");
	var _renderHidePopupButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHidePopupButton");
	var _renderLottieAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLottieAnimation");
	var _renderContentText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContentText");
	var _renderConnectTelephonyButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderConnectTelephonyButton");
	var _renderRemindLaterButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderRemindLaterButton");
	var _preloadMainLottieAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preloadMainLottieAnimation");
	var _getMainAnimationPath = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMainAnimationPath");
	class RecognitionPromo {
	  constructor(options) {
	    var _options$events;
	    Object.defineProperty(this, _getMainAnimationPath, {
	      value: _getMainAnimationPath2
	    });
	    Object.defineProperty(this, _preloadMainLottieAnimation, {
	      value: _preloadMainLottieAnimation2
	    });
	    Object.defineProperty(this, _renderRemindLaterButton, {
	      value: _renderRemindLaterButton2
	    });
	    Object.defineProperty(this, _renderConnectTelephonyButton, {
	      value: _renderConnectTelephonyButton2
	    });
	    Object.defineProperty(this, _renderContentText, {
	      value: _renderContentText2
	    });
	    Object.defineProperty(this, _renderLottieAnimation, {
	      value: _renderLottieAnimation2
	    });
	    Object.defineProperty(this, _renderHidePopupButton, {
	      value: _renderHidePopupButton2
	    });
	    Object.defineProperty(this, _renderHeaderCopilotIcon, {
	      value: _renderHeaderCopilotIcon2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _events, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _events)[_events] = (_options$events = options == null ? void 0 : options.events) != null ? _options$events : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _preloadMainLottieAnimation)[_preloadMainLottieAnimation]();
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	  subscribe(eventName, callback) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].subscribe(eventName, callback);
	  }
	  shouldShowAgain() {
	    const checkbox = document.getElementById('crm__ai-recognition-promo_checkbox_dont_show_again');
	    return checkbox ? !checkbox.checked : true;
	  }
	}
	function _initPopup2() {
	  return new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    padding: 0,
	    width: 528,
	    noAllPaddings: true,
	    overlay: {
	      backgroundColor: '#000',
	      opacity: 40
	    },
	    cacheable: false,
	    borderRadius: 16,
	    background: 'transparent',
	    contentBackground: 'transparent',
	    animation: 'fading-slide',
	    events: {
	      onPopupShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onShow) {
	          babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onShow();
	        }
	      },
	      onPopupClose: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onHide) {
	          babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onHide();
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	      }
	    }
	  });
	}
	function _renderPopupContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="crm__ai-recognition-promo">
				<header class="crm__ai-recognition-promo_header">
					<div class="crm__ai-recognition-promo_header-left">
						<div class="crm__ai-recognition-promo_header-icon">
							${0}
						</div>
						<h4 class="crm__ai-recognition-promo_header-title">
							${0}
						</h4>
					</div>
					<div class="crm__ai-recognition-promo_header-close-button">
						${0}
					</div>
				</header>
				<main class="crm__ai-recognition-promo_content">
					${0}
					${0}
				</main>
				<footer class="crm__ai-recognition-promo_footer">
					${0}
					${0}
				</footer>
				<div class="crm__ai-recognition-promo_checkbox_dont_show_again">
					<input type="checkbox" id="crm__ai-recognition-promo_checkbox_dont_show_again">
					<label>${0}</label>
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderHeaderCopilotIcon)[_renderHeaderCopilotIcon](), main_core.Loc.getMessage('RECOGNITION_PROMO_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _renderHidePopupButton)[_renderHidePopupButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderLottieAnimation)[_renderLottieAnimation](), babelHelpers.classPrivateFieldLooseBase(this, _renderContentText)[_renderContentText](), babelHelpers.classPrivateFieldLooseBase(this, _renderConnectTelephonyButton)[_renderConnectTelephonyButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderRemindLaterButton)[_renderRemindLaterButton](), main_core.Loc.getMessage('RECOGNITION_PROMO_DONT_SHOW_AGAIN'));
	}
	function _renderHeaderCopilotIcon2() {
	  var _getComputedStyle$get;
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.COPILOT_AI,
	    size: 40,
	    color: (_getComputedStyle$get = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary')) != null ? _getComputedStyle$get : '#8E52EC'
	  });
	  return icon.render();
	}
	function _renderHidePopupButton2() {
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Actions.CROSS_40,
	    size: 24
	  });
	  const button = main_core.Tag.render(_t2 || (_t2 = _`
			<button class="crm__ai-recognition-promo_close-popup-button">
				${0}
			</button>
		`), icon.render());
	  main_core.bind(button, 'click', () => {
	    var _babelHelpers$classPr2;
	    if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr2.onClickOnClosePopup) {
	      babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onClickOnClosePopup();
	    }
	    {
	      this.hide();
	    }
	  });
	  return button;
	}
	function _renderLottieAnimation2() {
	  const container = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="crm__ai-recognition-promo_video-container">
				<canvas ref="canvas"></canvas>
				<div ref="lottie" class="crm__ai-recognition-promo_content-lottie"></div>
				<div ref="confetti" class="crm__ai-recognition-promo_content-confetti"></div>
			</div>
		`));
	  const mainAnimation = ui_lottie.Lottie.loadAnimation({
	    path: babelHelpers.classPrivateFieldLooseBase(this, _getMainAnimationPath)[_getMainAnimationPath](),
	    container: container.lottie,
	    renderer: 'svg',
	    loop: true,
	    autoplay: true
	  });
	  mainAnimation.setSpeed(0.75);
	  const confettiAnimation = ui_lottie.Lottie.loadAnimation({
	    path: '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/confetti-animation.json',
	    container: container.confetti,
	    renderer: 'svg',
	    loop: true,
	    autoplay: false
	  });
	  confettiAnimation.setSpeed(1.3);
	  main_core.Dom.style(container.confetti, 'opacity', 0);
	  main_core.bind(confettiAnimation, 'loopComplete', () => {
	    confettiAnimation.pause();
	    main_core.Dom.style(container.confetti, 'opacity', 0);
	  });
	  let confettiWereShown = false;
	  main_core.bind(mainAnimation, 'loopComplete', () => {
	    confettiWereShown = false;
	    main_core.Dom.style(container.confetti, 'opacity', 0);
	  });
	  main_core.bind(mainAnimation, 'enterFrame', e => {
	    if (confettiWereShown === false && e.currentTime > 350) {
	      confettiAnimation.play();
	      main_core.Dom.style(container.confetti, 'opacity', 1);
	      confettiWereShown = true;
	    }
	  });
	  main_core.bind(mainAnimation, 'enterFrame', e => {
	    if (e.currentTime > 350 && confettiWereShown === false) {
	      confettiAnimation.play();
	      main_core.Dom.style(container.confetti, 'opacity', 1);
	      confettiWereShown = true;
	    }
	  });
	  return container.root;
	}
	function _renderContentText2() {
	  const content = main_core.Loc.getMessage('RECOGNITION_PROMO_CONTENT', {
	    '[P]': '<p>',
	    '[/P]': '</p>',
	    '[LINK1]': '<a ref="link1">',
	    '[/LINK1]': '</a>',
	    '[LINK2]': '<a ref="link2">',
	    '[/LINK2]': '</a>'
	  });
	  const container = main_core.Tag.render(_t4 || (_t4 = _`
			<div class="crm__ai-recognition-promo_content-description">
				${0}
			</div>
		`), content);
	  const Helper = main_core.Reflection.getClass('top.BX.Helper');
	  main_core.bind(container.link1, 'click', () => {
	    const articleCode = '19092894'; // todo replace with the real article code

	    Helper == null ? void 0 : Helper.show(`redirect=detail&code=${articleCode}`);
	  });
	  main_core.bind(container.link2, 'click', () => {
	    const articleCode = '6450911'; // todo replace with the real article code

	    Helper == null ? void 0 : Helper.show(`redirect=detail&code=${articleCode}`);
	  });
	  return container.root;
	}
	function _renderConnectTelephonyButton2() {
	  const button = new ui_buttons.Button({
	    color: ui_buttons.ButtonColor.SUCCESS,
	    text: main_core.Loc.getMessage('RECOGNITION_PROMO_CONNECT_TELEPHONY'),
	    round: true,
	    onclick: btn => {
	      var _babelHelpers$classPr3;
	      if ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr3.onClickOnConnectButton) {
	        babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onClickOnConnectButton(btn);
	      }
	    }
	  });
	  return button.render();
	}
	function _renderRemindLaterButton2() {
	  const button = new ui_buttons.Button({
	    color: ui_buttons.ButtonColor.LINK,
	    text: main_core.Loc.getMessage('RECOGNITION_PROMO_REMIND_LATER'),
	    round: true,
	    onclick: btn => {
	      var _babelHelpers$classPr4;
	      if ((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr4.onClickOnRemindLaterButton) {
	        babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onClickOnRemindLaterButton(btn);
	      }
	    }
	  });
	  return button.render();
	}
	function _preloadMainLottieAnimation2() {
	  ui_lottie.Lottie.loadAnimation({
	    path: babelHelpers.classPrivateFieldLooseBase(this, _getMainAnimationPath)[_getMainAnimationPath](),
	    renderer: 'svg'
	  });
	}
	function _getMainAnimationPath2() {
	  return main_core.Loc.getMessage('LANGUAGE_ID') === 'ru' ? '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/animation-ru.json' : '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/animation-en.json';
	}

	exports.RecognitionPromo = RecognitionPromo;

}((this.BX.Crm.AI.Whatsnew = this.BX.Crm.AI.Whatsnew || {}),BX,BX.Main,BX.UI,BX.UI.IconSet,BX,BX,BX.UI));
//# sourceMappingURL=recognition-promo.bundle.js.map
