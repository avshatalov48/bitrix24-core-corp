/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_promoVideoPopup,ui_buttons,ui_iconSet_api_core,ui_iconSet_main,main_core) {
	'use strict';

	const CopilotPromoPopupPresetData = Object.freeze({
	  task: {
	    videoSrc: {
	      en: '/bitrix/js/ai/copilot-promo-popup/videos/en/tasks.webm',
	      ru: '/bitrix/js/ai/copilot-promo-popup/videos/ru/tasks.webm'
	    },
	    title: 'CoPilot',
	    text: getTextWithReplaceAccent('COPILOT_PROMO_POPUP_TASKS_TEXT')
	  },
	  liveFeedEditor: {
	    videoSrc: {
	      en: '/bitrix/js/ai/copilot-promo-popup/videos/en/liveFeedEditor.webm',
	      ru: '/bitrix/js/ai/copilot-promo-popup/videos/ru/liveFeedEditor.webm'
	    },
	    videoContainerMinHeight: 213,
	    title: 'CoPilot',
	    text: getTextWithReplaceAccent('COPILOT_PROMO_POPUP_LIVEFEED_EDITOR_TEXT')
	  },
	  chat: {
	    videoSrc: {
	      en: '/bitrix/js/ai/copilot-promo-popup/videos/en/chat.webm',
	      ru: '/bitrix/js/ai/copilot-promo-popup/videos/ru/chat.webm'
	    },
	    title: 'CoPilot',
	    text: getTextWithReplaceAccent('COPILOT_PROMO_POPUP_CHATS_TEXT')
	  }
	});
	function getTextWithReplaceAccent(messageCode) {
	  return main_core.Loc.getMessage(messageCode, {
	    '#ACCENT#': '<span style="color: var(--ui-color-copilot-primary);">',
	    '#/ACCENT#': '</span>'
	  });
	}

	var _checkPreset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkPreset");
	var _isPresetExist = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPresetExist");
	var _getVideoLang = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getVideoLang");
	var _isWestZone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isWestZone");
	class CopilotPromoPopup {
	  static getWidth() {
	    return ui_promoVideoPopup.PromoVideoPopup.getWidth();
	  }
	  static createByPresetId(options) {
	    babelHelpers.classPrivateFieldLooseBase(CopilotPromoPopup, _checkPreset)[_checkPreset](options.presetId);
	    const presetId = options.presetId;
	    const preset = CopilotPromoPopupPresetData[presetId];
	    const promoVideoPopup = new ui_promoVideoPopup.PromoVideoPopup({
	      targetOptions: options.targetOptions,
	      videoSrc: preset.videoSrc[babelHelpers.classPrivateFieldLooseBase(CopilotPromoPopup, _getVideoLang)[_getVideoLang]()],
	      videoContainerMinHeight: preset.videoContainerMinHeight,
	      title: preset.title,
	      text: preset.text,
	      icon: ui_iconSet_api_core.Main.COPILOT_AI,
	      angleOptions: options.angleOptions,
	      offset: options.offset,
	      colors: {
	        title: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-secondary'),
	        iconBackground: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary'),
	        button: ui_buttons.Button.Color.AI
	      }
	    });
	    promoVideoPopup.subscribe(ui_promoVideoPopup.PromoVideoPopupEvents.ACCEPT, () => {
	      promoVideoPopup.hide();
	    });
	    return promoVideoPopup;
	  }
	}
	function _checkPreset2(presetId) {
	  if (main_core.Type.isStringFilled(presetId) === false) {
	    throw new Error('AI.CopilotPromoPopup: presetId is required option and must be the string');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(CopilotPromoPopup, _isPresetExist)[_isPresetExist](presetId) === false) {
	    throw new Error(`AI.CopilotPromoPopup: preset with id '${presetId}' doesn't exist`);
	  }
	}
	function _isPresetExist2(presetId) {
	  return Boolean(CopilotPromoPopupPresetData[presetId]);
	}
	function _getVideoLang2() {
	  return babelHelpers.classPrivateFieldLooseBase(CopilotPromoPopup, _isWestZone)[_isWestZone]() ? 'en' : 'ru';
	}
	function _isWestZone2() {
	  return main_core.Extension.getSettings('ai.copilot-promo-popup').isWestZone;
	}
	Object.defineProperty(CopilotPromoPopup, _isWestZone, {
	  value: _isWestZone2
	});
	Object.defineProperty(CopilotPromoPopup, _getVideoLang, {
	  value: _getVideoLang2
	});
	Object.defineProperty(CopilotPromoPopup, _isPresetExist, {
	  value: _isPresetExist2
	});
	Object.defineProperty(CopilotPromoPopup, _checkPreset, {
	  value: _checkPreset2
	});
	CopilotPromoPopup.AnglePosition = ui_promoVideoPopup.AnglePosition;
	CopilotPromoPopup.Preset = Object.freeze({
	  TASK: 'task',
	  LIVE_FEED_EDITOR: 'liveFeedEditor',
	  CHAT: 'chat'
	});
	CopilotPromoPopup.PromoVideoPopupEvents = ui_promoVideoPopup.PromoVideoPopupEvents;

	exports.CopilotPromoPopup = CopilotPromoPopup;

}((this.BX.AI = this.BX.AI || {}),BX.UI,BX.UI,BX.UI.IconSet,BX,BX));
//# sourceMappingURL=copilot-promo-popup.bundle.js.map
