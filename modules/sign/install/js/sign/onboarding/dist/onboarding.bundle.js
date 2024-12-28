/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,sign_tour,ui_bannerDispatcher,ui_buttons,ui_designTokens,ui_iconSet_api_core) {
	'use strict';

	var b2eWelcomeGif = "/bitrix/js/sign/onboarding/dist/video/b2e_welcome.gif";

	let _ = t => t,
	  _t,
	  _t2;
	const b2bHelpdeskCode = 16571388;
	const b2eCreateHelpdeskCode = 20338910;
	const b2eTemplatesHelpdeskCode = 23174934;
	const b2ePopupTourId = 'sign-b2e-onboarding-tour-id';
	var _backend = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("backend");
	var _getB2eByEmployeeGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getB2eByEmployeeGuide");
	var _getB2eWelcomeGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getB2eWelcomeGuide");
	var _getB2eFallbackGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getB2eFallbackGuide");
	var _createB2eByEmployeePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eByEmployeePopup");
	var _createB2eWelcomePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eWelcomePopup");
	var _renderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderIcon");
	var _createB2eNewDocumentButtonStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eNewDocumentButtonStep");
	var _createB2eTemplatesStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eTemplatesStep");
	var _createB2eKanbanRouteStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eKanbanRouteStep");
	var _shouldStartB2eOnboarding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldStartB2eOnboarding");
	class Onboarding {
	  constructor() {
	    Object.defineProperty(this, _shouldStartB2eOnboarding, {
	      value: _shouldStartB2eOnboarding2
	    });
	    Object.defineProperty(this, _createB2eKanbanRouteStep, {
	      value: _createB2eKanbanRouteStep2
	    });
	    Object.defineProperty(this, _createB2eTemplatesStep, {
	      value: _createB2eTemplatesStep2
	    });
	    Object.defineProperty(this, _createB2eNewDocumentButtonStep, {
	      value: _createB2eNewDocumentButtonStep2
	    });
	    Object.defineProperty(this, _renderIcon, {
	      value: _renderIcon2
	    });
	    Object.defineProperty(this, _createB2eWelcomePopup, {
	      value: _createB2eWelcomePopup2
	    });
	    Object.defineProperty(this, _createB2eByEmployeePopup, {
	      value: _createB2eByEmployeePopup2
	    });
	    Object.defineProperty(this, _getB2eFallbackGuide, {
	      value: _getB2eFallbackGuide2
	    });
	    Object.defineProperty(this, _getB2eWelcomeGuide, {
	      value: _getB2eWelcomeGuide2
	    });
	    Object.defineProperty(this, _getB2eByEmployeeGuide, {
	      value: _getB2eByEmployeeGuide2
	    });
	    Object.defineProperty(this, _backend, {
	      writable: true,
	      value: new sign_tour.Backend()
	    });
	  }
	  async startB2eByEmployeeOnboarding(options) {
	    const startOnboarding = await babelHelpers.classPrivateFieldLooseBase(this, _shouldStartB2eOnboarding)[_shouldStartB2eOnboarding]();
	    if (!startOnboarding) {
	      return;
	    }
	    ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	      const guide = babelHelpers.classPrivateFieldLooseBase(this, _getB2eByEmployeeGuide)[_getB2eByEmployeeGuide](options, onDone);
	      const welcomePopup = babelHelpers.classPrivateFieldLooseBase(this, _createB2eByEmployeePopup)[_createB2eByEmployeePopup](guide);
	      babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].saveVisit(b2ePopupTourId);
	      welcomePopup.show();
	    });
	  }
	  async startB2eWelcomeOnboarding(options) {
	    const startOnboarding = await babelHelpers.classPrivateFieldLooseBase(this, _shouldStartB2eOnboarding)[_shouldStartB2eOnboarding]();
	    if (!startOnboarding) {
	      return;
	    }
	    ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	      const guide = babelHelpers.classPrivateFieldLooseBase(this, _getB2eWelcomeGuide)[_getB2eWelcomeGuide](options, onDone);
	      const welcomePopup = babelHelpers.classPrivateFieldLooseBase(this, _createB2eWelcomePopup)[_createB2eWelcomePopup](guide);
	      babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].saveVisit(b2ePopupTourId);
	      welcomePopup.show();
	    });
	  }
	  async startB2eFallbackOnboarding(options) {
	    ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	      const guide = babelHelpers.classPrivateFieldLooseBase(this, _getB2eFallbackGuide)[_getB2eFallbackGuide](options, onDone);
	      guide.startOnce();
	    });
	  }
	  getB2bGuide(target) {
	    return new sign_tour.Guide({
	      id: 'sign-tour-guide-sign-start-kanban',
	      autoSave: true,
	      simpleMode: true,
	      steps: [{
	        target,
	        title: main_core.Loc.getMessage('SIGN_ONBOARDING_B2B_BTN_TITLE'),
	        text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2B_BTN_TEXT'),
	        article: b2bHelpdeskCode
	      }]
	    });
	  }
	}
	function _getB2eByEmployeeGuide2(options, onFinish) {
	  var _options$tourId;
	  return new sign_tour.Guide({
	    id: (_options$tourId = options.tourId) != null ? _options$tourId : 'sign-tour-guide-sign-start-kanban-b2e-by-employee',
	    autoSave: true,
	    simpleMode: false,
	    events: {
	      onFinish
	    },
	    steps: [babelHelpers.classPrivateFieldLooseBase(this, _createB2eKanbanRouteStep)[_createB2eKanbanRouteStep]('.ui-toolbar-after-title-buttons > button.sign-b2e-onboarding-route'), babelHelpers.classPrivateFieldLooseBase(this, _createB2eTemplatesStep)[_createB2eTemplatesStep]('div#sign_sign_b2e_employee_template_list')]
	  });
	}
	function _getB2eWelcomeGuide2(options, onFinish) {
	  var _options$tourId2;
	  return new sign_tour.Guide({
	    id: (_options$tourId2 = options.tourId) != null ? _options$tourId2 : 'sign-tour-guide-sign-start-kanban-b2e-by-employee',
	    autoSave: true,
	    simpleMode: false,
	    events: {
	      onFinish
	    },
	    steps: [babelHelpers.classPrivateFieldLooseBase(this, _createB2eNewDocumentButtonStep)[_createB2eNewDocumentButtonStep]('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region), babelHelpers.classPrivateFieldLooseBase(this, _createB2eKanbanRouteStep)[_createB2eKanbanRouteStep]('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-route'), babelHelpers.classPrivateFieldLooseBase(this, _createB2eTemplatesStep)[_createB2eTemplatesStep]('div#sign_sign_b2e_employee_template_list')]
	  });
	}
	function _getB2eFallbackGuide2(options, onFinish) {
	  var _options$tourId3;
	  return new sign_tour.Guide({
	    id: (_options$tourId3 = options.tourId) != null ? _options$tourId3 : 'sign-tour-guide-sign-start-kanban-b2e',
	    autoSave: true,
	    simpleMode: true,
	    events: {
	      onFinish
	    },
	    steps: [babelHelpers.classPrivateFieldLooseBase(this, _createB2eNewDocumentButtonStep)[_createB2eNewDocumentButtonStep]('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region)]
	  });
	}
	function _createB2eByEmployeePopup2(guide) {
	  const popup = new main_popup.Popup({
	    content: main_core.Tag.render(_t || (_t = _`
				<div>
					<div class="sign__b2e_by_employee_onboarding_popup-title">${0}</div>
					<div class="sign__b2e_by_employee_onboarding_popup-text">${0}</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_TITLE'), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_TEXT')),
	    closeIcon: false,
	    width: 371,
	    height: 180,
	    padding: 20,
	    overlay: true,
	    className: 'sign__b2e_by_employee_onboarding_popup',
	    contentColor: 'white',
	    buttons: [new ui_buttons.Button({
	      color: ui_buttons.Button.Color.PRIMARY,
	      size: ui_buttons.Button.Size.SMALL,
	      round: true,
	      noCaps: true,
	      text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_BTN_TEXT'),
	      events: {
	        click() {
	          popup.close();
	          guide.start();
	        }
	      }
	    })]
	  });
	  return popup;
	}
	function _createB2eWelcomePopup2(guide) {
	  const popup = new main_popup.Popup({
	    className: 'sign__b2e-onboarding-welcome-popup',
	    closeIcon: false,
	    width: 500,
	    height: 517,
	    padding: 20,
	    buttons: [new ui_buttons.Button({
	      color: ui_buttons.Button.Color.PRIMARY,
	      size: ui_buttons.Button.Size.SMALL,
	      round: true,
	      noCaps: true,
	      text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_TEXT'),
	      className: 'sign__b2e-onboarding-welcome-popup_start-guide',
	      events: {
	        click() {
	          popup.close();
	          guide.start();
	        }
	      }
	    })],
	    content: main_core.Tag.render(_t2 || (_t2 = _`
				<div class="sign__onboarding-popup-content">
					<div class="sign__onboarding-popup-content_header">
						<div class="sign__onboarding-popup-content_header-icon">
							${0}
						</div>
						<div class="sign__onboarding-popup-content_header-title">
							${0}
						</div>
					</div>
					<div class="sign__onboarding-popup-content_promo-video-wrapper">
						<img src="${0}" alt="video">
					</div>
					<div class="sign__onboarding-popup-content_footer">
						${0}
					</div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _renderIcon)[_renderIcon](), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE'), b2eWelcomeGif, main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TEXT'))
	  });
	  return popup;
	}
	function _renderIcon2() {
	  const color = getComputedStyle(document.body).getPropertyValue('--ui-color-on-primary');
	  const icon = new ui_iconSet_api_core.Icon({
	    color,
	    size: 18,
	    icon: ui_iconSet_api_core.Actions.PENCIL_DRAW
	  });
	  return icon.render();
	}
	function _createB2eNewDocumentButtonStep2(target, region) {
	  const firstStepMsgTitle = region === 'ru' ? main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TITLE_RU') : main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TITLE');
	  const firstStepMsgText = region === 'ru' ? main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TEXT_RU') : main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TEXT');
	  return {
	    target,
	    title: firstStepMsgTitle,
	    text: firstStepMsgText,
	    article: b2eCreateHelpdeskCode
	  };
	}
	function _createB2eTemplatesStep2(target) {
	  return {
	    target,
	    title: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TITLE'),
	    text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TEXT'),
	    article: b2eTemplatesHelpdeskCode
	  };
	}
	function _createB2eKanbanRouteStep2(target) {
	  return {
	    target,
	    title: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_ROUTE_TITLE'),
	    text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_ROUTE_TEXT')
	  };
	}
	async function _shouldStartB2eOnboarding2() {
	  const {
	    lastVisitDate
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].getLastVisitDate(b2ePopupTourId);
	  return main_core.Type.isNull(lastVisitDate);
	}

	exports.Onboarding = Onboarding;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX.Main,BX.Sign.Tour,BX.UI,BX.UI,BX,BX.UI.IconSet));
//# sourceMappingURL=onboarding.bundle.js.map
