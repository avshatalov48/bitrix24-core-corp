/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_buttons,ui_notification) {
	'use strict';

	let _ = t => t,
	  _t;
	var _onApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onApply");
	var _onCancel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCancel");
	var _wasApplied = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wasApplied");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderApplyButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderApplyButton");
	var _renderCancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCancelButton");
	var _getFullAgreementLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFullAgreementLink");
	class CopilotAgreementPopup {
	  constructor(options) {
	    Object.defineProperty(this, _getFullAgreementLink, {
	      value: _getFullAgreementLink2
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
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _onApply, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onCancel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wasApplied, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    if (options != null && options.onApply) {
	      this.setOnApply(options.onApply);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _onCancel)[_onCancel] = main_core.Type.isFunction(options.onCancel) ? options.onCancel : null;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	  }
	  setOnApply(onApply) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply] = onApply;
	  }
	  setOnCancel(onCancel) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onCancel)[_onCancel] = onCancel;
	  }
	}
	function _initPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    cacheable: false,
	    overlay: true,
	    disableScroll: true,
	    width: 492,
	    minHeight: 448,
	    closeByEsc: true,
	    closeIcon: true,
	    closeIconSize: main_popup.CloseIconSize.LARGE,
	    padding: 20,
	    borderRadius: '10px',
	    events: {
	      onDestroy: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _wasApplied)[_wasApplied] === false && babelHelpers.classPrivateFieldLooseBase(this, _onCancel)[_onCancel]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _onCancel)[_onCancel]();
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
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
	    '#LINK#': `<a target="_blank" href="${babelHelpers.classPrivateFieldLooseBase(this, _getFullAgreementLink)[_getFullAgreementLink]()}">`,
	    '#/LINK#': '</a>'
	  }), babelHelpers.classPrivateFieldLooseBase(this, _renderApplyButton)[_renderApplyButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderCancelButton)[_renderCancelButton]());
	}
	function _renderApplyButton2() {
	  const applyBtn = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_BTN'),
	    color: ui_buttons.Button.Color.SUCCESS,
	    round: true,
	    onclick: async button => {
	      try {
	        button.setState(ui_buttons.Button.State.WAITING);
	        await babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply]();
	        this.hide();
	      } catch (err) {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_ERROR')
	        });
	        console.error(err);
	      } finally {
	        button.setState(null);
	      }
	    }
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
	function _getFullAgreementLink2() {
	  const zone = main_core.Extension.getSettings('ai.copilot-agreement-popup').zone;
	  const linksByZone = {
	    ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
	    kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
	    by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
	    en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php'
	  };
	  return linksByZone[zone] || linksByZone.en;
	}

	exports.CopilotAgreementPopup = CopilotAgreementPopup;

}((this.BX.AI = this.BX.AI || {}),BX,BX.Main,BX.UI,BX));
//# sourceMappingURL=copilot-agreement-popup.bundle.js.map
