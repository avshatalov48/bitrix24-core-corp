/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_promoVideoPopup,ai_copilotPromoPopup,main_core,ui_bannerDispatcher) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	class TasksAiPromo {
	  constructor(params) {
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	  }
	  show() {
	    ui_bannerDispatcher.BannerDispatcher.normal.toQueue(onDone => {
	      const promoPopup = babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]();
	      if (!promoPopup) {
	        onDone();
	        return;
	      }
	      promoPopup.subscribe(ui_promoVideoPopup.PromoVideoPopupEvents.HIDE, this.onCopilotPromoHide.bind(this, onDone));
	      promoPopup.show();
	    });
	  }
	  onCopilotPromoHide(onDone) {
	    main_core.ajax.runAction('tasks.promotion.setViewed', {
	      data: {
	        promotion: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].promotionType
	      }
	    }).catch(err => {
	      console.error(err);
	    });
	    onDone();
	  }
	}
	function _getPopup2() {
	  const copilotButton = document.querySelector('#mpf-copilot-task-form');
	  if (!copilotButton) {
	    return null;
	  }
	  return ai_copilotPromoPopup.CopilotPromoPopup.createByPresetId({
	    presetId: ai_copilotPromoPopup.CopilotPromoPopup.Preset.TASK,
	    targetOptions: copilotButton,
	    offset: {
	      left: 85,
	      top: -150
	    },
	    angleOptions: {
	      position: ui_promoVideoPopup.AnglePosition.LEFT,
	      offset: 122
	    }
	  });
	}

	exports.TasksAiPromo = TasksAiPromo;

}((this.BX.Tasks.Edit = this.BX.Tasks.Edit || {}),BX.UI,BX.AI,BX,BX.UI));
//# sourceMappingURL=script.js.map
