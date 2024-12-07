/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,ui_qrauthorization) {
	'use strict';

	class UserStatisticsLink {
	  constructor(props = {}) {
	    this.qrAuth = new ui_qrauthorization.QrAuthorization({
	      title: this.getTitle(props.intent),
	      content: this.getContent(props.intent),
	      intent: props.intent || UserStatisticsLink.CHECK_IN_INTENT,
	      showFishingWarning: true,
	      showBottom: false
	    });
	  }
	  show() {
	    this.qrAuth.show();
	  }
	  getTitle(intent) {
	    if (intent === UserStatisticsLink.CHECK_IN_SETTINGS_INTENT) {
	      return main_core.Loc.getMessage('STAFFTRACK_CHECK_IN_SETTINGS_QRCODE_TITLE');
	    }
	    return main_core.Loc.getMessage('STAFFTRACK_USER_STATISTICS_LINK_QRCODE_TITLE');
	  }
	  getContent(intent) {
	    if (intent === UserStatisticsLink.CHECK_IN_SETTINGS_INTENT) {
	      return main_core.Loc.getMessage('STAFFTRACK_CHECK_IN_SETTINGS_QRCODE_BODY');
	    }
	    return main_core.Loc.getMessage('STAFFTRACK_USER_STATISTICS_LINK_QRCODE_BODY');
	  }
	}
	UserStatisticsLink.CHECK_IN_INTENT = 'check-in';
	UserStatisticsLink.CHECK_IN_SETTINGS_INTENT = 'check-in-settings';

	exports.UserStatisticsLink = UserStatisticsLink;

}((this.BX.Stafftrack = this.BX.Stafftrack || {}),BX,BX.UI));
//# sourceMappingURL=user-statistics-link.bundle.js.map
