/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_integration_analytics,crm_integration_ui_bannerDispatcher,main_core,main_popup,ui_analytics,ui_buttons,ui_iconSet_api_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const MAX_STEP_NUMBER = 3;
	var _bannerDispatcher = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bannerDispatcher");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _originalOverflowValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("originalOverflowValue");
	var _targetContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetContainer");
	var _step = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("step");
	var _closeOptionName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeOptionName");
	var _closeOptionCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeOptionCategory");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _getPopupParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupParams");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _renderVideo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderVideo");
	var _getVideoPath = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getVideoPath");
	var _getStepContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStepContent");
	var _getButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButton");
	var _getButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonText");
	var _isLastStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLastStep");
	var _goToNextStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("goToNextStep");
	var _setTargetOverflow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTargetOverflow");
	var _resetTargetOverflow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetTargetOverflow");
	var _getTarget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTarget");
	var _sendViewAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendViewAnalytics");
	var _sendCloseAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendCloseAnalytics");
	var _sendClickAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendClickAnalytics");
	var _sendAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalytics");
	class OnboardingPopup {
	  constructor(params) {
	    var _params$closeOptionNa, _params$closeOptionCa, _params$analytics;
	    Object.defineProperty(this, _sendAnalytics, {
	      value: _sendAnalytics2
	    });
	    Object.defineProperty(this, _sendClickAnalytics, {
	      value: _sendClickAnalytics2
	    });
	    Object.defineProperty(this, _sendCloseAnalytics, {
	      value: _sendCloseAnalytics2
	    });
	    Object.defineProperty(this, _sendViewAnalytics, {
	      value: _sendViewAnalytics2
	    });
	    Object.defineProperty(this, _getTarget, {
	      value: _getTarget2
	    });
	    Object.defineProperty(this, _resetTargetOverflow, {
	      value: _resetTargetOverflow2
	    });
	    Object.defineProperty(this, _setTargetOverflow, {
	      value: _setTargetOverflow2
	    });
	    Object.defineProperty(this, _goToNextStep, {
	      value: _goToNextStep2
	    });
	    Object.defineProperty(this, _isLastStep, {
	      value: _isLastStep2
	    });
	    Object.defineProperty(this, _getButtonText, {
	      value: _getButtonText2
	    });
	    Object.defineProperty(this, _getButton, {
	      value: _getButton2
	    });
	    Object.defineProperty(this, _getStepContent, {
	      value: _getStepContent2
	    });
	    Object.defineProperty(this, _getVideoPath, {
	      value: _getVideoPath2
	    });
	    Object.defineProperty(this, _renderVideo, {
	      value: _renderVideo2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _getPopupParams, {
	      value: _getPopupParams2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _bannerDispatcher, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _originalOverflowValue, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _targetContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _step, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _closeOptionName, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _closeOptionCategory, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _closeOptionName)[_closeOptionName] = (_params$closeOptionNa = params == null ? void 0 : params.closeOptionName) != null ? _params$closeOptionNa : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _closeOptionCategory)[_closeOptionCategory] = (_params$closeOptionCa = params == null ? void 0 : params.closeOptionCategory) != null ? _params$closeOptionCa : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = (_params$analytics = params == null ? void 0 : params.analytics) != null ? _params$analytics : {};
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().isShown()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _bannerDispatcher)[_bannerDispatcher] = new crm_integration_ui_bannerDispatcher.BannerDispatcher();
	    babelHelpers.classPrivateFieldLooseBase(this, _bannerDispatcher)[_bannerDispatcher].toQueue(onDone => {
	      babelHelpers.classPrivateFieldLooseBase(this, _setTargetOverflow)[_setTargetOverflow]('hidden');
	      babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().subscribe('onAfterClose', onDone);
	      babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	    }, crm_integration_ui_bannerDispatcher.Priority.CRITICAL);
	  }
	}
	function _getPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _createPopup2() {
	  return new main_popup.Popup(babelHelpers.classPrivateFieldLooseBase(this, _getPopupParams)[_getPopupParams]());
	}
	function _getPopupParams2() {
	  return {
	    id: 'crm_repeat_sale_onboarding_popup',
	    targetContainer: babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](),
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	    cacheable: true,
	    isScrollBlock: false,
	    className: 'crm-repeat-sale-onboarding-popup',
	    closeByEsc: true,
	    closeIcon: true,
	    padding: 16,
	    width: 733,
	    overlay: {
	      opacity: 40,
	      backgroundColor: '#000000'
	    },
	    animation: 'fading-slide',
	    autoHide: false,
	    events: {
	      onFirstShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _sendViewAnalytics)[_sendViewAnalytics]();
	      },
	      onclose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _resetTargetOverflow)[_resetTargetOverflow]();
	        BX.userOptions.save(babelHelpers.classPrivateFieldLooseBase(this, _closeOptionCategory)[_closeOptionCategory], babelHelpers.classPrivateFieldLooseBase(this, _closeOptionName)[_closeOptionName], 'closed', 'Y');
	        babelHelpers.classPrivateFieldLooseBase(this, _sendCloseAnalytics)[_sendCloseAnalytics]();
	      }
	    }
	  };
	}
	function _getPopupContent2() {
	  const icon = new ui_iconSet_api_core.Icon({
	    size: 29,
	    color: '#853AF5',
	    icon: ui_iconSet_api_core.Outline.COPILOT
	  });
	  return main_core.Tag.render(_t || (_t = _`
			<div class="crm-repeat-sale__onboarding-popup-container">
				<div class="crm-repeat-sale__onboarding-popup-title">
					${0}
					${0}
				</div>
				<div class="crm-repeat-sale__onboarding-popup-video">
					<div>
						${0}
					</div>
				</div>
				<div class="crm-repeat-sale__onboarding-popup-content">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('CRM_REPEAT_SALE_ONBOARDING_TITLE'), icon.render(), babelHelpers.classPrivateFieldLooseBase(this, _renderVideo)[_renderVideo](), babelHelpers.classPrivateFieldLooseBase(this, _getStepContent)[_getStepContent]());
	}
	function _renderVideo2() {
	  const videoElement = main_core.Tag.render(_t2 || (_t2 = _`
			<video
				src="${0}"
				autoplay
				preload
				loop
			></video>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getVideoPath)[_getVideoPath]());

	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-events-binding
	  videoElement.addEventListener('canplay', () => {
	    videoElement.muted = true;
	    videoElement.play();
	  });
	  return videoElement;
	}
	function _getVideoPath2() {
	  const region = main_core.Extension.getSettings('crm.repeat-sale.onboarding-popup').get('region');
	  let name = 'how-it-work-en';
	  if (['kz', 'ru', 'by', 'uz'].includes(region)) {
	    name = 'how-it-work-ru';
	  }
	  return `/bitrix/js/crm/repeat-sale/onboarding-popup/video/${name}.webm`;
	}
	function _getStepContent2() {
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="crm-repeat-sale__onboarding-popup-step-container">
				<div class="crm-repeat-sale__onboarding-popup-step-text">
					${0}
				</div>
				<div class="crm-repeat-sale__onboarding-popup-button">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage(`CRM_REPEAT_SALE_ONBOARDING_TEXT_STEP_${babelHelpers.classPrivateFieldLooseBase(this, _step)[_step]}`), babelHelpers.classPrivateFieldLooseBase(this, _getButton)[_getButton]().render());
	}
	function _getButton2() {
	  const style = babelHelpers.classPrivateFieldLooseBase(this, _isLastStep)[_isLastStep]() ? ui_buttons.AirButtonStyle.TINTED : ui_buttons.AirButtonStyle.FILLED_COPILOT;
	  const icon = babelHelpers.classPrivateFieldLooseBase(this, _isLastStep)[_isLastStep]() ? ui_iconSet_api_core.Outline.CHECK_M : ui_iconSet_api_core.Outline.NEXT;
	  return new ui_buttons.Button({
	    useAirDesign: true,
	    text: babelHelpers.classPrivateFieldLooseBase(this, _getButtonText)[_getButtonText](),
	    round: true,
	    size: BX.UI.Button.Size.LARGE,
	    icon,
	    style,
	    onclick: () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isLastStep)[_isLastStep]()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().close();
	        babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().destroy();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _goToNextStep)[_goToNextStep]();
	      }
	    }
	  });
	}
	function _getButtonText2() {
	  const code = babelHelpers.classPrivateFieldLooseBase(this, _isLastStep)[_isLastStep]() ? 'CRM_REPEAT_SALE_ONBOARDING_BUTTON_CLOSE' : 'CRM_REPEAT_SALE_ONBOARDING_BUTTON_NEXT';
	  return main_core.Loc.getMessage(code);
	}
	function _isLastStep2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _step)[_step] === MAX_STEP_NUMBER;
	}
	function _goToNextStep2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _step)[_step]++;
	  main_core.Dom.replace(document.querySelector('.crm-repeat-sale__onboarding-popup-step-container'), babelHelpers.classPrivateFieldLooseBase(this, _getStepContent)[_getStepContent]());
	  babelHelpers.classPrivateFieldLooseBase(this, _sendClickAnalytics)[_sendClickAnalytics]();
	}
	function _setTargetOverflow2(value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _originalOverflowValue)[_originalOverflowValue] = main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](), 'overflow');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](), 'overflow', value);
	}
	function _resetTargetOverflow2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _originalOverflowValue)[_originalOverflowValue] === null) {
	    return;
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](), 'overflow', babelHelpers.classPrivateFieldLooseBase(this, _originalOverflowValue)[_originalOverflowValue]);
	  babelHelpers.classPrivateFieldLooseBase(this, _originalOverflowValue)[_originalOverflowValue] = null;
	}
	function _getTarget2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _targetContainer)[_targetContainer] === null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _targetContainer)[_targetContainer] = document.body;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _targetContainer)[_targetContainer];
	}
	function _sendViewAnalytics2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('view');
	}
	function _sendCloseAnalytics2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('close');
	}
	function _sendClickAnalytics2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _sendAnalytics)[_sendAnalytics]('click');
	}
	function _sendAnalytics2(eventName) {
	  var _babelHelpers$classPr;
	  const type = crm_integration_analytics.Dictionary.TYPE_REPEAT_SALE_BANNER_NULL;
	  const subSection = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].c_sub_section) != null ? _babelHelpers$classPr : crm_integration_analytics.Dictionary.SUB_SECTION_KANBAN;
	  let instance = null;
	  if (eventName === 'view') {
	    instance = crm_integration_analytics.Builder.RepeatSale.Banner.ViewEvent.createDefault(type, subSection);
	  } else if (eventName === 'close') {
	    instance = crm_integration_analytics.Builder.RepeatSale.Banner.CloseEvent.createDefault(type, subSection);
	  } else if (eventName === 'click') {
	    instance = crm_integration_analytics.Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	  }
	  if (instance) {
	    var _babelHelpers$classPr2;
	    const section = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].c_section) != null ? _babelHelpers$classPr2 : '';
	    ui_analytics.sendData(instance.setSection(section).buildData());
	  }
	}

	exports.OnboardingPopup = OnboardingPopup;

}((this.BX.Crm.RepeatSale = this.BX.Crm.RepeatSale || {}),BX.Crm.Integration.Analytics,BX.Crm.Integration.UI,BX,BX.Main,BX.UI.Analytics,BX.UI,BX.UI.IconSet));
//# sourceMappingURL=onboarding-popup.bundle.js.map
