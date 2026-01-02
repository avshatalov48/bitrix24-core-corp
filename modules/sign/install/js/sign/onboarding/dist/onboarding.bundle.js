/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,sign_tour,sign_v2_b2e_signSettingsOnboarding,sign_v2_api,ui_bannerDispatcher,ui_iconSet_api_core,ui_buttons) {
	'use strict';

	var b2eWelcomeGif = "/bitrix/js/sign/onboarding/dist/video/b2e_welcome.gif";

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5;
	const b2bHelpdeskCode = 16571388;
	const b2eCreateHelpdeskCode = 20338910;
	const b2eTemplatesHelpdeskCode = 24354462;
	const b2eWelcomeTourId = 'sign-b2e-onboarding-tour-id';
	const b2eTestSigningWelcomeTourId = 'sign-b2e-onboarding-tour-id-test-signing';
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _backend = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("backend");
	var _showCloseOnboardingSigningWarningPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCloseOnboardingSigningWarningPopup");
	var _getB2eWelcomeGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getB2eWelcomeGuide");
	var _createB2eWelcomePopupWithTestSigning = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eWelcomePopupWithTestSigning");
	var _createB2eWelcomePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eWelcomePopup");
	var _renderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderIcon");
	var _openSigningSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSigningSlider");
	var _createB2eNewDocumentButtonStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eNewDocumentButtonStep");
	var _createB2eTemplatesStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eTemplatesStep");
	var _createB2eKanbanRouteStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createB2eKanbanRouteStep");
	var _shouldStartB2eOnboarding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldStartB2eOnboarding");
	var _isTemplateBtnVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTemplateBtnVisible");
	class Onboarding {
	  constructor() {
	    Object.defineProperty(this, _isTemplateBtnVisible, {
	      value: _isTemplateBtnVisible2
	    });
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
	    Object.defineProperty(this, _openSigningSlider, {
	      value: _openSigningSlider2
	    });
	    Object.defineProperty(this, _renderIcon, {
	      value: _renderIcon2
	    });
	    Object.defineProperty(this, _createB2eWelcomePopup, {
	      value: _createB2eWelcomePopup2
	    });
	    Object.defineProperty(this, _createB2eWelcomePopupWithTestSigning, {
	      value: _createB2eWelcomePopupWithTestSigning2
	    });
	    Object.defineProperty(this, _getB2eWelcomeGuide, {
	      value: _getB2eWelcomeGuide2
	    });
	    Object.defineProperty(this, _showCloseOnboardingSigningWarningPopup, {
	      value: _showCloseOnboardingSigningWarningPopup2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    Object.defineProperty(this, _backend, {
	      writable: true,
	      value: new sign_tour.Backend()
	    });
	  }
	  async startB2eWelcomeOnboarding(options) {
	    const tourId = b2eWelcomeTourId;
	    const startOnboarding = await babelHelpers.classPrivateFieldLooseBase(this, _shouldStartB2eOnboarding)[_shouldStartB2eOnboarding](tourId);
	    if (!startOnboarding) {
	      return;
	    }
	    ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	      const guide = babelHelpers.classPrivateFieldLooseBase(this, _getB2eWelcomeGuide)[_getB2eWelcomeGuide](tourId, options, onDone);
	      const welcomePopup = babelHelpers.classPrivateFieldLooseBase(this, _createB2eWelcomePopup)[_createB2eWelcomePopup](guide);
	      babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].saveVisit(tourId);
	      welcomePopup.show();
	    });
	  }
	  async startB2eWelcomeOnboardingWithTestSigning(options) {
	    const tourId = b2eTestSigningWelcomeTourId;
	    const startOnboarding = await babelHelpers.classPrivateFieldLooseBase(this, _shouldStartB2eOnboarding)[_shouldStartB2eOnboarding](tourId, true);
	    if (!startOnboarding) {
	      return;
	    }
	    ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	      const guide = babelHelpers.classPrivateFieldLooseBase(this, _getB2eWelcomeGuide)[_getB2eWelcomeGuide](tourId, options, onDone);
	      const welcomePopup = babelHelpers.classPrivateFieldLooseBase(this, _createB2eWelcomePopupWithTestSigning)[_createB2eWelcomePopupWithTestSigning](guide, options);
	      babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].saveVisit(tourId);
	      welcomePopup.show();
	    });
	  }
	  showTestSigningBanner(options) {
	    const header = document.querySelector('header.page__header');
	    if (header) {
	      const signButton = new ui_buttons.Button({
	        color: ui_buttons.Button.Color.PRIMARY,
	        size: ui_buttons.Button.Size.MEDIUM,
	        round: true,
	        noCaps: true,
	        useAirDesign: true,
	        style: ui_buttons.Button.AirStyle.FILL,
	        text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_BTN_SIGN_TEST_TEXT'),
	        className: `sign__b2e-onboarding-signing-test-banner-button ${options.showTariffSlider ? 'sign-b2e-js-tarriff-slider-trigger' : ''}`,
	        events: options.showTariffSlider ? {} : {
	          click: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _openSigningSlider)[_openSigningSlider](options);
	          }
	        }
	      });
	      const onboardingBanner = main_core.Tag.render(_t || (_t = _`
				<div class="sign__b2e-onboarding-signing-test-banner">
					<div class="sign__onboarding-banner-content_img"></div>
					<div class="sign__b2e-onboarding-signing-test-banner_content">
						<div class="sign__b2e-onboarding-signing-test-banner-title-container">
							<div class="sign__b2e-onboarding-signing-test-banner-title">
								${0}
							</div>
							<button
									class="sign__b2e-onboarding-signing-test-banner_close_btn"
									onclick="${0}">
							</button>
						</div>
						<div class="sign__b2e-onboarding-signing-test-banner-title-description">
						${0}
						</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_TITLE_SIGN_TEST_TEXT'), () => babelHelpers.classPrivateFieldLooseBase(this, _showCloseOnboardingSigningWarningPopup)[_showCloseOnboardingSigningWarningPopup](), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_DESCRIPTION_SIGN_TEST_TEXT'));
	      main_core.Dom.append(signButton.render(), onboardingBanner.querySelector('.sign__b2e-onboarding-signing-test-banner_content'));
	      header.insertAdjacentElement('afterend', onboardingBanner);
	    }
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
	function _showCloseOnboardingSigningWarningPopup2() {
	  const popupContent = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign__b2e-close-onboarding-signing-warning-popup-content">
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CONTENT'));
	  const popup = new main_popup.Popup({
	    id: 'sign__b2e-close-onboarding-signing-banner-warning-popup',
	    content: popupContent,
	    minHeigh: 180,
	    width: 480,
	    padding: 20,
	    contentColor: 'white',
	    overlay: true,
	    closeByEsc: true,
	    closeIcon: true,
	    buttons: [new ui_buttons.Button({
	      id: 'sign__b2e-close-onboarding-signing-banner-warning-popup-confirm-button',
	      text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CONFIRM_BUTTON'),
	      color: ui_buttons.Button.Color.PRIMARY,
	      events: {
	        click: () => {
	          popup.close();
	          const banner = document.querySelector('.sign__b2e-onboarding-signing-test-banner');
	          if (banner) {
	            banner.remove();
	            babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].hideOnboardingSigningBanner();
	          }
	        }
	      }
	    }), new ui_buttons.Button({
	      id: 'sign__b2e-close-onboarding-signing-banner-warning-popup-cancel-button',
	      text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CANCEL_BUTTON'),
	      color: ui_buttons.Button.Color.LINK,
	      events: {
	        click() {
	          popup.close();
	        }
	      }
	    })]
	  });
	  popup.show();
	}
	function _getB2eWelcomeGuide2(tourId, options, onFinish) {
	  return new sign_tour.Guide({
	    id: tourId,
	    autoSave: true,
	    simpleMode: false,
	    events: {
	      onFinish
	    },
	    steps: [babelHelpers.classPrivateFieldLooseBase(this, _createB2eNewDocumentButtonStep)[_createB2eNewDocumentButtonStep]('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region), ...(options.byEmployeeEnabled ? [babelHelpers.classPrivateFieldLooseBase(this, _createB2eKanbanRouteStep)[_createB2eKanbanRouteStep]('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-route')] : []), babelHelpers.classPrivateFieldLooseBase(this, _createB2eTemplatesStep)[_createB2eTemplatesStep](babelHelpers.classPrivateFieldLooseBase(this, _isTemplateBtnVisible)[_isTemplateBtnVisible]() ? 'div#sign_sign_b2e_employee_template_list' : 'div#sign_more_button')]
	  });
	}
	function _createB2eWelcomePopupWithTestSigning2(guide, options) {
	  const popupTitle = main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE');
	  const buttons = [];
	  if (options.canEditDocument && options.canCreateDocument) {
	    buttons.push(new ui_buttons.Button({
	      id: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-signing-popup-button',
	      color: ui_buttons.Button.Color.PRIMARY,
	      size: ui_buttons.Button.Size.MEDIUM,
	      round: true,
	      noCaps: true,
	      useAirDesign: true,
	      style: ui_buttons.Button.AirStyle.FILL,
	      text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_SIGN_TEST_TEXT'),
	      className: `sign__b2e-onboarding-welcome-popup_sign__onboarding-popup-button ${options.showTariffSlider ? 'sign-b2e-js-tarriff-slider-trigger' : ''}`,
	      events: options.showTariffSlider ? {} : {
	        click: () => {
	          popup.close();
	          babelHelpers.classPrivateFieldLooseBase(this, _openSigningSlider)[_openSigningSlider](options);
	        }
	      }
	    }));
	  }
	  buttons.push(new ui_buttons.Button({
	    id: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-tour-popup-button',
	    color: ui_buttons.Button.Color.PRIMARY,
	    size: ui_buttons.Button.Size.MEDIUM,
	    round: true,
	    noCaps: true,
	    useAirDesign: true,
	    style: ui_buttons.Button.AirStyle.OUTLINE,
	    text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_TEXT_RU'),
	    className: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-popup-button',
	    events: {
	      click() {
	        popup.close();
	        guide.start();
	      }
	    }
	  }));
	  const popup = new main_popup.Popup({
	    id: 'sign__b2e-onboarding-welcome-popup',
	    className: 'sign__b2e-onboarding-welcome-popup',
	    closeIcon: true,
	    width: 690,
	    height: 322,
	    padding: 20,
	    overlay: true,
	    buttons,
	    content: main_core.Tag.render(_t3 || (_t3 = _`
				<div class="sign__onboarding-popup-content">
					<div class="sign__onboarding-popup-content_header">
						<div class="sign__onboarding-popup-content_header-title">
							${0}
						</div>
					</div>
					<div class="sign__onboarding-popup-content_body">
						<div class="sign__onboarding-popup-content_img"></div>
						<div class="sign__onboarding-popup-content_text_container">
							<div class="sign__onboarding-popup-content_text">
								${0}
							</div>
						</div>
					</div>
				</div>
			`), popupTitle, main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TEXT'))
	  });
	  return popup;
	}
	function _createB2eWelcomePopup2(guide) {
	  const popupTitle = main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE_WEST');
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
	    content: main_core.Tag.render(_t4 || (_t4 = _`
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
			`), babelHelpers.classPrivateFieldLooseBase(this, _renderIcon)[_renderIcon](), popupTitle, b2eWelcomeGif, main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TEXT'))
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
	function _openSigningSlider2(options) {
	  BX.SidePanel.Instance.open('onboarding-signing-slider', {
	    width: 750,
	    contentCallback: () => {
	      const containerId = 'onboarding-signing-slider-container';
	      const container = main_core.Tag.render(_t5 || (_t5 = _`<div id="${0}"></div>`), containerId);
	      const onboardingSignSettings = new sign_v2_b2e_signSettingsOnboarding.B2EOnboardingSignSettings(containerId, Boolean(options.hasCompanies));
	      onboardingSignSettings.renderToContainer(container);
	      return container;
	    }
	  });
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
	    title: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TITLE_V1'),
	    text: main_core.Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TEXT_V1'),
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
	async function _shouldStartB2eOnboarding2(tourId, checkDocuments = false) {
	  const {
	    lastVisitDate
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _backend)[_backend].getLastVisitDate(tourId);
	  if (main_core.Type.isNull(lastVisitDate) && checkDocuments) {
	    const {
	      hasSignedDocuments
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].hasSignedDocuments();
	    if (hasSignedDocuments) {
	      return false;
	    }
	  }
	  return main_core.Type.isNull(lastVisitDate);
	}
	function _isTemplateBtnVisible2() {
	  var _document$querySelect;
	  return ((_document$querySelect = document.querySelector('div#sign_sign_b2e_employee_template_list')) == null ? void 0 : _document$querySelect.offsetParent) !== null;
	}

	exports.Onboarding = Onboarding;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX.Main,BX.Sign.Tour,BX.Sign.V2.B2e,BX.Sign.V2,BX.UI,BX.UI.IconSet,BX.UI));
//# sourceMappingURL=onboarding.bundle.js.map
