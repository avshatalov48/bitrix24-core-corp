/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,ui_bannerDispatcher,ui_mobilePromoter) {
	'use strict';

	var _appLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appLink");
	var _createMobilePromoter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createMobilePromoter");
	class InviteToMobile {
	  constructor(data) {
	    Object.defineProperty(this, _createMobilePromoter, {
	      value: _createMobilePromoter2
	    });
	    Object.defineProperty(this, _appLink, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _appLink)[_appLink] = data.appLink;
	  }
	  show() {
	    ui_bannerDispatcher.BannerDispatcher.normal.toQueue(onDone => {
	      const promoter = babelHelpers.classPrivateFieldLooseBase(this, _createMobilePromoter)[_createMobilePromoter]();
	      const popup = promoter.getPopup();
	      popup.subscribe('onAfterClose', event => {
	        onDone();
	      });
	      popup.show();
	    });
	  }
	}
	function _createMobilePromoter2() {
	  return new ui_mobilePromoter.MobilePromoter({
	    title: main_core.Loc.getMessage('TASKS_PROMO_INVITE_TO_MOBILE_TITLE'),
	    position: {
	      right: 83,
	      bottom: 46
	    },
	    qrContent: babelHelpers.classPrivateFieldLooseBase(this, _appLink)[_appLink],
	    analytics: {
	      c_section: 'tasks'
	    }
	  });
	}

	exports.InviteToMobile = InviteToMobile;

}((this.BX.Tasks.Promo = this.BX.Tasks.Promo || {}),BX,BX.UI,BX.UI));
//# sourceMappingURL=invite-to-mobile.bundle.js.map
