/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_buttons,ui_notification,ai_engine) {
	'use strict';

	let _ = t => t,
	  _t;
	var _events = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("events");
	var _wasAccepted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wasAccepted");
	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _checkAgreementResult = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkAgreementResult");
	var _showAgreementPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAgreementPopup");
	var _hideAgreementPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideAgreementPopup");
	var _initAgreementPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initAgreementPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderApplyButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderApplyButton");
	var _renderCancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCancelButton");
	var _handleClickOnAcceptBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleClickOnAcceptBtn");
	var _getFullAgreementLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFullAgreementLink");
	var _acceptAgreement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("acceptAgreement");
	var _validateOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateOptions");
	class CopilotAgreement {
	  constructor(_options) {
	    Object.defineProperty(this, _validateOptions, {
	      value: _validateOptions2
	    });
	    Object.defineProperty(this, _acceptAgreement, {
	      value: _acceptAgreement2
	    });
	    Object.defineProperty(this, _getFullAgreementLink, {
	      value: _getFullAgreementLink2
	    });
	    Object.defineProperty(this, _handleClickOnAcceptBtn, {
	      value: _handleClickOnAcceptBtn2
	    });
	    Object.defineProperty(this, _renderCancelButton, {
	      value: _renderCancelButton2
	    });
	    Object.defineProperty(this, _renderApplyButton, {
	      value: _renderApplyButton2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _initAgreementPopup, {
	      value: _initAgreementPopup2
	    });
	    Object.defineProperty(this, _hideAgreementPopup, {
	      value: _hideAgreementPopup2
	    });
	    Object.defineProperty(this, _showAgreementPopup, {
	      value: _showAgreementPopup2
	    });
	    Object.defineProperty(this, _events, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wasAccepted, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _validateOptions)[_validateOptions](_options);
	    babelHelpers.classPrivateFieldLooseBase(this, _events)[_events] = _options.events || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = new ai_engine.Engine();
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setContextId(_options.contextId);
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setModuleId(_options.moduleId);
	  }
	  static getFullAgreementLink() {
	    const zone = main_core.Extension.getSettings('ai.copilot-agreement').zone;
	    const linksByZone = {
	      ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
	      kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
	      by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
	      en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php'
	    };
	    return linksByZone[zone] || linksByZone.en;
	  }
	  async checkAgreement() {
	    if (babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult] !== null && babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult] !== undefined) {
	      if (babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult] === false) {
	        babelHelpers.classPrivateFieldLooseBase(this, _showAgreementPopup)[_showAgreementPopup]();
	      }
	      return Promise.resolve(babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult]);
	    }
	    try {
	      const result = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].checkAgreement();
	      if (result.data.isAccepted === false) {
	        babelHelpers.classPrivateFieldLooseBase(this, _showAgreementPopup)[_showAgreementPopup]();
	      }
	      babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult] = result.data.isAccepted;
	      return result.data.isAccepted;
	    } catch (e) {
	      console.error(e);
	      return true;
	    }
	  }
	}
	function _showAgreementPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initAgreementPopup)[_initAgreementPopup]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	}
	function _hideAgreementPopup2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	}
	function _initAgreementPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    cacheable: false,
	    overlay: true,
	    disableScroll: true,
	    width: 492,
	    minHeight: 448,
	    closeByEsc: true,
	    autoHide: true,
	    closeIcon: true,
	    closeIconSize: main_popup.CloseIconSize.LARGE,
	    padding: 20,
	    borderRadius: '10px',
	    events: {
	      onDestroy: () => {
	        var _babelHelpers$classPr2, _babelHelpers$classPr4;
	        if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr2.onAgreementPopupHide) {
	          var _babelHelpers$classPr3;
	          (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) == null ? void 0 : _babelHelpers$classPr3.onAgreementPopupHide();
	        }
	        if (babelHelpers.classPrivateFieldLooseBase(this, _wasAccepted)[_wasAccepted] === false && (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr4.onCancel) {
	          var _babelHelpers$classPr5;
	          (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) == null ? void 0 : _babelHelpers$classPr5.onCancel();
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	      },
	      onPopupShow: () => {
	        var _babelHelpers$classPr6;
	        if ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr6.onAgreementPopupShow) {
	          var _babelHelpers$classPr7;
	          (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) == null ? void 0 : _babelHelpers$classPr7.onAgreementPopupShow();
	        }
	      }
	    }
	  });
	}
	function _renderPopupContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div
				class="ai__copilot-agreement-popup-content"
			>
				<header class="ai__copilot-agreement-popup-content_header">
					<h3 class="ai__copilot-agreement-popup-content_title">
						${0}
					</h3>
				</header>
				<main class="ai__copilot-agreement-popup-content_main">
					<div class="ai__copilot-agreement-popup-content_img"></div>
					<p class="ai__copilot-agreement-popup-content_text">
						${0}
					</p>
					<p class="ai__copilot-agreement-popup-content_text">
						${0}
					</p>
				</main>
				<footer class="ai__copilot-agreement-popup-content_footer">
					<div class="ai__copilot-agreement-popup_footer-content-buttons">
						${0}
						${0}
					</div>
				</footer>
			</div>
		`), main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_TITLE'), main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_PARAGRAPH_1'), main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_PARAGRAPH_2', {
	    '#LINK#': `<a target="_blank" href="${CopilotAgreement.getFullAgreementLink()}">`,
	    '#/LINK#': '</a>'
	  }), babelHelpers.classPrivateFieldLooseBase(this, _renderApplyButton)[_renderApplyButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderCancelButton)[_renderCancelButton]());
	}
	function _renderApplyButton2() {
	  const applyBtn = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_BTN'),
	    color: ui_buttons.Button.Color.SUCCESS,
	    round: true,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleClickOnAcceptBtn)[_handleClickOnAcceptBtn].bind(this)
	  });
	  return applyBtn.render();
	}
	function _renderCancelButton2() {
	  const cancelBtn = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_CANCEL_BTN'),
	    round: true,
	    color: ui_buttons.Button.Color.LIGHT,
	    onclick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].destroy();
	    }
	  });
	  return cancelBtn.render();
	}
	async function _handleClickOnAcceptBtn2(button) {
	  try {
	    var _babelHelpers$classPr8;
	    button.setState(ui_buttons.Button.State.WAITING);
	    babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult] = await babelHelpers.classPrivateFieldLooseBase(this, _acceptAgreement)[_acceptAgreement]();
	    babelHelpers.classPrivateFieldLooseBase(this, _wasAccepted)[_wasAccepted] = babelHelpers.classPrivateFieldLooseBase(CopilotAgreement, _checkAgreementResult)[_checkAgreementResult];
	    if ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr8.onAccept) {
	      babelHelpers.classPrivateFieldLooseBase(this, _events)[_events].onAccept();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _hideAgreementPopup)[_hideAgreementPopup]();
	  } catch (err) {
	    var _babelHelpers$classPr9;
	    if ((_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) != null && _babelHelpers$classPr9.onAcceptError) {
	      var _babelHelpers$classPr10;
	      (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]) == null ? void 0 : _babelHelpers$classPr10.onAcceptError();
	    }
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_ERROR')
	    });
	    console.error(err);
	  } finally {
	    button.setState(null);
	  }
	}
	function _getFullAgreementLink2() {
	  const zone = main_core.Extension.getSettings('ai.copilot-agreement').zone;
	  const linksByZone = {
	    ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
	    kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
	    by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
	    en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php'
	  };
	  return linksByZone[zone] || linksByZone.en;
	}
	async function _acceptAgreement2() {
	  const result = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].acceptAgreement();
	  return result.data.isAccepted;
	}
	function _validateOptions2(options) {
	  if (!options.moduleId || main_core.Type.isStringFilled(options.moduleId) === false) {
	    throw new Error('AI: CopilotAgreement: moduleId option is required and must be the string');
	  }
	  if (!options.contextId || main_core.Type.isStringFilled(options.contextId) === false) {
	    throw new Error('AI: CopilotAgreement: moduleId option is required and must be the string');
	  }
	  if (options.events && main_core.Type.isObject(options.events) === false) {
	    throw new Error('AI: CopilotAgreement: events option must be the object');
	  }
	}
	Object.defineProperty(CopilotAgreement, _checkAgreementResult, {
	  writable: true,
	  value: null
	});

	exports.CopilotAgreement = CopilotAgreement;

}((this.BX.AI = this.BX.AI || {}),BX,BX.Main,BX.UI,BX,BX.AI));
//# sourceMappingURL=copilot-agreement.bundle.js.map
