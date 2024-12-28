/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports,im_v2_const,imopenlines_v2_const) {
	'use strict';

	const OpenLinesComponentList = new Set([imopenlines_v2_const.OpenLinesMessageComponent.StartDialogMessage, imopenlines_v2_const.OpenLinesMessageComponent.HiddenMessage, imopenlines_v2_const.OpenLinesMessageComponent.FeedbackFormMessage, imopenlines_v2_const.OpenLinesMessageComponent.ImOpenLinesForm, imopenlines_v2_const.OpenLinesMessageComponent.ImOpenLinesMessage]);
	const componentForReplace = new Set([imopenlines_v2_const.OpenLinesMessageComponent.ImOpenLinesForm, imopenlines_v2_const.OpenLinesMessageComponent.ImOpenLinesMessage]);
	var _message = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("message");
	var _getUpdatedComponentId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUpdatedComponentId");
	class OpenLinesMessageManager {
	  constructor(message) {
	    Object.defineProperty(this, _getUpdatedComponentId, {
	      value: _getUpdatedComponentId2
	    });
	    Object.defineProperty(this, _message, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _message)[_message] = message;
	  }
	  checkComponentInOpenLinesList() {
	    return OpenLinesComponentList.has(babelHelpers.classPrivateFieldLooseBase(this, _message)[_message].componentId);
	  }
	  getMessageComponent() {
	    if (componentForReplace.has(babelHelpers.classPrivateFieldLooseBase(this, _message)[_message].componentId)) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _getUpdatedComponentId)[_getUpdatedComponentId]();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _message)[_message].componentId;
	  }
	}
	function _getUpdatedComponentId2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _message)[_message].componentParams.imolForm === imopenlines_v2_const.FormType.like) {
	    return imopenlines_v2_const.OpenLinesMessageComponent.FeedbackFormMessage;
	  }
	  return im_v2_const.MessageComponent.system;
	}

	exports.OpenLinesMessageManager = OpenLinesMessageManager;

}((this.BX.OpenLines.v2.Lib = this.BX.OpenLines.v2.Lib || {}),BX.Messenger.v2.Const,BX.OpenLines.v2.Const));
//# sourceMappingURL=message-manager.bundle.js.map
