/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_iconSet_api_core,ui_hint,main_core_events) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6;
	const CopilotBannerEvents = Object.freeze({
	  actionStart: 'action-start',
	  actionFinishSuccess: 'action-finish-success',
	  actionFinishFailed: 'action-finish-failed'
	});
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _isWestZone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isWestZone");
	var _buttonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonClickHandler");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderPlatesByZone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPlatesByZone");
	var _renderCopilotBannerIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCopilotBannerIcon");
	var _getTextWithAccents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextWithAccents");
	var _renderTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTitle");
	var _renderButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderButton");
	var _handleButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleButtonClick");
	class CopilotBanner extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$buttonClickH;
	    super(options);
	    Object.defineProperty(this, _handleButtonClick, {
	      value: _handleButtonClick2
	    });
	    Object.defineProperty(this, _renderButton, {
	      value: _renderButton2
	    });
	    Object.defineProperty(this, _renderTitle, {
	      value: _renderTitle2
	    });
	    Object.defineProperty(this, _getTextWithAccents, {
	      value: _getTextWithAccents2
	    });
	    Object.defineProperty(this, _renderCopilotBannerIcon, {
	      value: _renderCopilotBannerIcon2
	    });
	    Object.defineProperty(this, _renderPlatesByZone, {
	      value: _renderPlatesByZone2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isWestZone, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _buttonClickHandler, {
	      writable: true,
	      value: void 0
	    });
	    const settings = main_core.Extension.getSettings('ai.copilot-banner');
	    babelHelpers.classPrivateFieldLooseBase(this, _isWestZone)[_isWestZone] = settings.get('isWestZone');
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonClickHandler)[_buttonClickHandler] = (_options$buttonClickH = options.buttonClickHandler) != null ? _options$buttonClickH : () => {};
	    this.setEventNamespace('AI:CopilotBanner');
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().close();
	  }
	}
	function _getPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _createPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    maxWidth: 854,
	    minWidth: 700,
	    minHeight: 520,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    padding: 0,
	    borderRadius: '18px',
	    overlay: {
	      backgroundColor: '#000',
	      opacity: 70
	    },
	    animation: 'fading',
	    disableScroll: false,
	    className: 'ai__copilot-banner_popup'
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _renderPopupContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="ai__copilot-banner_content">
				${0}
				<div class="ai__copilot-banner_content-inner">
					<div class="ai__copilot-banner_starlight"></div>
					${0}
					<div class="ai__copilot-banner_main">
						<p class="ai__copilot-banner_text">${0}</p>
						<p class="ai__copilot-banner_text">${0}</p>
						<p class="ai__copilot-banner_text">${0}</p>
					</div>
					<footer class="ai__copilot-banner_footer">
					<div class="ai__copilot-banner_footer-text">
						${0}
					</div>
					${0}
				</footer>
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderCopilotBannerIcon)[_renderCopilotBannerIcon](), babelHelpers.classPrivateFieldLooseBase(this, _renderPlatesByZone)[_renderPlatesByZone](), babelHelpers.classPrivateFieldLooseBase(this, _getTextWithAccents)[_getTextWithAccents]('AI_COPILOT_BANNER_TEXT_1'), babelHelpers.classPrivateFieldLooseBase(this, _getTextWithAccents)[_getTextWithAccents]('AI_COPILOT_BANNER_TEXT_2'), babelHelpers.classPrivateFieldLooseBase(this, _getTextWithAccents)[_getTextWithAccents]('AI_COPILOT_BANNER_TEXT_3'), babelHelpers.classPrivateFieldLooseBase(this, _renderTitle)[_renderTitle](), babelHelpers.classPrivateFieldLooseBase(this, _renderButton)[_renderButton]());
	}
	function _renderPlatesByZone2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isWestZone)[_isWestZone]) {
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<div class="ai__copilot-banner_plates">
					<div class="ai__copilot-banner_plate --google"></div>
					<div class="ai__copilot-banner_plate --open-ai"></div>
					<div class="ai__copilot-banner_plate --market"></div>
					<div class="ai__copilot-banner_plate --meta"></div>
				</div>
			`));
	  }
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="ai__copilot-banner_plates">
				<div class="ai__copilot-banner_plate --ygpt"></div>
				<div class="ai__copilot-banner_plate --its"></div>
				<div class="ai__copilot-banner_plate --market"></div>
				<div class="ai__copilot-banner_plate --giga-chat"></div>
			</div>
		`));
	}
	function _renderCopilotBannerIcon2() {
	  const icon = new ui_iconSet_api_core.Icon({
	    size: 88,
	    color: '#fff',
	    icon: ui_iconSet_api_core.Main.COPILOT_AI
	  });
	  return main_core.Tag.render(_t4 || (_t4 = _`
			<div class="ai__copilot-banner_icon-wrapper">
				<div class="ai__copilot-banner_icon-bg"></div>
				${0}
			</div>
		`), icon.render());
	}
	function _getTextWithAccents2(phraseCode) {
	  return main_core.Loc.getMessage(phraseCode, {
	    '#accent#': '<span class="--accent">',
	    '#/accent#': '</span>'
	  });
	}
	function _renderTitle2() {
	  const titleText = main_core.Loc.getMessage('AI_COPILOT_BANNER_TITLE', {
	    '#hint-start#': '<span class="ai__copilot-banner_title-hint">',
	    '#hint-end#': '</span>'
	  });
	  const title = main_core.Tag.render(_t5 || (_t5 = _`
			<h4 class="ai__copilot-banner_title">
				${0}
			</h4>
		`), titleText);
	  const titlePartWithHint = title.querySelector('.ai__copilot-banner_title-hint');
	  const hintContent = `<div>${main_core.Loc.getMessage('AI_COPILOT_BANNER_TITLE_HINT')}</div>`;
	  const hint = BX.UI.Hint.createInstance({
	    popupParameters: {
	      className: 'ai__copilot-banner-hint-popup',
	      borderRadius: '3px'
	    }
	  });
	  main_core.bind(titlePartWithHint, 'mouseenter', () => {
	    hint.show(titlePartWithHint, hintContent, true);
	  });
	  main_core.bind(titlePartWithHint, 'mouseleave', () => {
	    hint.hide(titlePartWithHint);
	  });
	  return title;
	}
	function _renderButton2() {
	  const btn = main_core.Tag.render(_t6 || (_t6 = _`
			<button class="ai__copilot-banner_btn">
				${0}
			</button>
		`), main_core.Loc.getMessage('AI_COPILOT_START_USING_BUTTON'));
	  main_core.bind(btn, 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleButtonClick)[_handleButtonClick].bind(this));
	  return btn;
	}
	async function _handleButtonClick2() {
	  this.emit(CopilotBannerEvents.actionStart);
	  try {
	    await babelHelpers.classPrivateFieldLooseBase(this, _buttonClickHandler)[_buttonClickHandler]();
	    this.emit(CopilotBannerEvents.actionFinishSuccess);
	  } catch (e) {
	    console.error(e);
	    this.emit(CopilotBannerEvents.actionFinishFailed);
	  } finally {
	    this.hide();
	  }
	}

	const AppsInstallerBannerEvents = Object.freeze({
	  ...CopilotBannerEvents
	});
	var _copilotBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotBanner");
	var _copilotBannerOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotBannerOptions");
	var _installApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("installApp");
	class AppsInstallerBanner extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$copilotBanne;
	    super();
	    Object.defineProperty(this, _installApp, {
	      value: _installApp2
	    });
	    Object.defineProperty(this, _copilotBanner, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotBannerOptions, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI:AppsInstallerBanner');
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBannerOptions)[_copilotBannerOptions] = (_options$copilotBanne = options.copilotBannerOptions) != null ? _options$copilotBanne : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner] = new CopilotBanner({
	      ...babelHelpers.classPrivateFieldLooseBase(this, _copilotBannerOptions)[_copilotBannerOptions],
	      buttonClickHandler: babelHelpers.classPrivateFieldLooseBase(this, _installApp)[_installApp].bind(this)
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].subscribe(CopilotBannerEvents.actionStart, () => {
	      this.emit(AppsInstallerBannerEvents.actionStart);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].subscribe(CopilotBannerEvents.actionFinishSuccess, () => {
	      this.emit(AppsInstallerBannerEvents.actionFinishSuccess);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].subscribe(CopilotBannerEvents.actionFinishFailed, () => {
	      this.emit(AppsInstallerBannerEvents.actionFinishFailed);
	    });
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].show();
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].hide();
	  }
	}
	async function _installApp2() {
	  // eslint-disable-next-line no-useless-return
	  return;
	}

	exports.CopilotBanner = CopilotBanner;
	exports.CopilotBannerEvents = CopilotBannerEvents;
	exports.AppsInstallerBanner = AppsInstallerBanner;
	exports.AppsInstallerBannerEvents = AppsInstallerBannerEvents;

}((this.BX.AI = this.BX.AI || {}),BX,BX.Main,BX.UI.IconSet,BX,BX.Event));
//# sourceMappingURL=copilot-banner.bundle.js.map
