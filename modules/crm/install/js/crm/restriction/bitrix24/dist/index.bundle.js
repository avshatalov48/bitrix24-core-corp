this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var Bitrix24 = {
	  settings: null,
	  getSettings: function getSettings(entityId) {
	    if (this.settings === null) {
	      this.settings = main_core.Extension.getSettings('crm.restriction.bitrix24');
	    }

	    if (main_core.Type.isStringFilled(entityId)) {
	      return this.settings.get(entityId);
	    }

	    return this.settings;
	  },
	  isRestricted: function isRestricted(entityId) {
	    return !!this.getSettings(entityId);
	  },
	  getHandler: function getHandler(entityId) {
	    var restrictions = this.getSettings(entityId);

	    if (restrictions) {
	      return function (e) {
	        if (e) {
	          BX.PreventDefault(e);
	        }

	        if (BX.Type.isStringFilled(restrictions['infoHelperScript'])) {
	          eval(restrictions['infoHelperScript']);
	        } else if (restrictions['id']) {
	          top.BX.UI.InfoHelper.show(restrictions['id']);
	        }

	        return false;
	      }.bind(this);
	    }

	    return null;
	  }
	};

	exports.Bitrix24 = Bitrix24;

}((this.BX.Crm.Restriction = this.BX.Crm.Restriction || {}),BX));
//# sourceMappingURL=index.bundle.js.map
