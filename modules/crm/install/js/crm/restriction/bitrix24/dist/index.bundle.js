this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var Bitrix24 = {
	  data: null,
	  getData: function getData(entityId) {
	    if (this.data === null) {
	      this.data = main_core.Extension.getSettings('crm.restriction.bitrix24');
	    }

	    if (main_core.Type.isStringFilled(entityId)) {
	      return this.data.get(entityId);
	    }

	    return this.data;
	  },
	  isRestricted: function isRestricted(entityId) {
	    return !!this.getData(entityId);
	  },
	  getHandler: function getHandler(entityId) {
	    var restrictions = this.getData(entityId);

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
