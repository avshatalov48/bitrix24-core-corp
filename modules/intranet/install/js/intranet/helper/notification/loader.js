;(function() {
	"use strict";

	BX.namespace("BX.Intranet.Helper.Notification");

	BX.Intranet.Helper.Notification.Loader = function(params) {
		this.params = params || {};

		this.errors = [];
		this.helper = {};
		this.baseLoadScriptDelay = 60 * 60 * 6;
		this.lsTimeParamName = 'intranet_helper_notification_loader_time';
		this.lsDelayParamName = 'intranet_helper_notification_loader_delay';

		this.init();
	};

	BX.Intranet.Helper.Notification.Loader.prototype =
		{
			init: function() {
				this.initParams();
				this.defineB24Helper();
				this.initDelay();
				this.checkLoadScript();
			},

			defineB24Helper: function() {
				if (BX && BX.Helper)
				{
					this.helper = BX.Helper;
				}
			},

			initDelay: function() {
				this.loadScriptDelayInSeconds = this.getDelayFromValue(localStorage.getItem(this.lsDelayParamName));
			},

			initParams: function() {
				this.timeNow = this.params.timeNow || '';
				this.lastCheckNotificationsTime = this.params.lastCheckNotificationsTime || '';
				this.currentNotificationsString = this.params.currentNotificationsString || '';
				this.managerScriptUrl = this.params.managerScriptUrl || '';
				this.maxScriptCacheTime = this.params.maxScriptCacheTime || this.baseLoadScriptDelay;

				if (!BX.type.isNotEmptyString(this.managerScriptUrl))
				{
					this.addError({ code: 'PARAMS_REQUIRED', data: { paramName: 'managerScriptUrl' } })
				}
			},

			loadManagerScript: function() {
				var lastTimeCheckTimeStamp = parseInt(localStorage.getItem(this.lsTimeParamName));
				if (isNaN(lastTimeCheckTimeStamp))
				{
					lastTimeCheckTimeStamp = 0;
				}

				var dateLastCheck = new Date();
				var dateNow = new Date();
				dateLastCheck.setTime(lastTimeCheckTimeStamp);
				if ((dateNow.getTime() - dateLastCheck.getTime()) / 1000 > this.loadScriptDelayInSeconds)
				{
					lastTimeCheckTimeStamp = dateNow.getTime();
					localStorage.setItem(this.lsTimeParamName, lastTimeCheckTimeStamp.toString());
				}

				BX.loadScript(BX.util.add_url_param(this.managerScriptUrl, { actual: lastTimeCheckTimeStamp }));
			},

			checkLoadScript: function() {
				if (!this.hasErrors())
				{
					this.loadManagerScript();
				}
			},

			addError: function(error) {
				this.errors.push(error);
			},

			hasErrors: function() {
				return this.errors.length > 0;
			},

			getAjaxUrl: function() {
				return this.helper.ajaxUrl || '';
			},

			getHelpUrl: function() {
				return this.helper.helpUrl || '';
			},

			getNotifyData: function() {
				return this.helper.notifyData || {};
			},

			getTimeNow: function() {
				return this.timeNow;
			},

			getLastCheckNotificationsTime: function() {
				return this.lastCheckNotificationsTime;
			},

			getCurrentNotificationsString: function() {
				return this.currentNotificationsString;
			},

			getDelayFromValue: function(newDelayValue) {
				var delayInt = parseInt(newDelayValue);
				if(isNaN(delayInt))
				{
					delayInt = this.baseLoadScriptDelay;
				}
				else if(delayInt <= 0 || delayInt > this.maxScriptCacheTime)
				{
					delayInt = this.baseLoadScriptDelay;
				}
				return delayInt;
			},

			setDelay: function(delay) {
				localStorage.setItem(this.lsDelayParamName, this.getDelayFromValue(delay).toString());
			}
		};
})();