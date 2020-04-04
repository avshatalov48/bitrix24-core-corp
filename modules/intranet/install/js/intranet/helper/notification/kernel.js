;(function() {
	"use strict";

	BX.namespace("BX.Intranet.Helper.Notification");

	BX.Intranet.Helper.Notification.Kernel = {
		loader: {},

		initLoader: function(params) {
			if(!(this.loader instanceof BX.Intranet.Helper.Notification.Loader))
			{
				this.loader = new BX.Intranet.Helper.Notification.Loader(params);
			}
		},

		getLoader: function() {
			return this.loader;
		}
	};
})();