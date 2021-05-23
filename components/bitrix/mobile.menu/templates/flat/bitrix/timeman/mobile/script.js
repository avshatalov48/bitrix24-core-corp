(function(){
	if (BX.MenuTimeman)
		return;
	BX.MenuTimeman = (function(){
		var d = function(params) {
			this.params = params;
			BX.addCustomEvent(window, "onMobileTimeManStatusHasBeenChanged", BX.proxy(this.onMobileTimeManStatusHasBeenChanged, this));
			BX.addCustomEvent('onPull-timeman', BX.delegate(this.onPull, this));
			this.date = new Date();
			this.notificationBar = {start : null, expired : null};
			this.timemanUrl = BX.message("SITE_DIR") + "mobile/timeman/";
			setTimeout(function() { BXMobileApp.UI.Page.reload(); }, (this.date.getHours()*3600 + (this.date.getMinutes() + 1) * 60 + this.date.getSeconds()) * 1000);
			app.onCustomEvent('onPullExtendWatch', {id : "TIMEMANWORKINGDAY_" + BX.message("USER_ID")});
			this.destroy = BX.proxy(this.destroy, this);
			if (BX.browser.IsIOS())
				window.addEventListener("pagehide", this.destroy);
			else
				BX.bind(window, 'beforeunload', this.destroy);
			this.init();
		};
		d.prototype = {
			init : function() {
				this.node = BX("menu-user-timeman");
				setTimeout(BX.proxy(this.checkNotification, this), 500);
			},
			destroy : function() {
				if (this.notificationBar["expired"])
					this.notificationBar["expired"].hide();
				if (this.notificationBar["start"])
					this.notificationBar["start"].hide();
			},
			checkNotification : function() {
				var status = this.node.getAttribute("data-bx-timeman-status"),
					d = new Date(),
					nowTime = d.getHours() * 60 * 60 + d.getMinutes() * 60 + d.getSeconds();
				if (status == "start")
				{
					if (BX.localStorage.get("timemanNSS") !== "hidden")
					{
						this.notificationBar["start"] = (new BXMobileApp.UI.NotificationBar({
							message: BX.message("TM_NOTIF_START"),
							color: "#bb50c119",
							textColor: "#ffffff",
							maxLines: 2,
							align: "center",
							indicatorHeight: 30,
							isGlobal: true,
							groupId: "timeman",
							useCloseButton: true,
							hideOnTap: true,
							onTap: BX.proxy(function(){ this.tapNotificationStart = true; }, this),
							onHideAfter: BX.proxy(this.onHideAfterStart, this)
						}, "timemanStart"));
						this.notificationBar["start"].show();
					}
					else
					{
						if (this.notificationBar["expired"])
							this.notificationBar["expired"].hide();
						if (this.params["UF_TM_MAX_START"] > 0 && this.params["UF_TM_MAX_START"] > nowTime)
							setTimeout(BX.proxy(this.checkNotification, this), (this.params["UF_TM_MAX_START"] - nowTime + 1) * 1000);
					}
				}
				else if (status == "expired")
				{
					if (BX.localStorage.get("timemanNSE") !== "hidden")
					{
						this.notificationBar["expired"] = (new BXMobileApp.UI.NotificationBar({
							message: BX.message("TM_NOTIF_EXPIRED"),
							color: "#bbf23b3b",
							textColor: "#ffffff",
							maxLines: 2,
							align: "center",
							indicatorHeight: 30,
							isGlobal: true,
							groupId: "timeman",
							useCloseButton: true,
							hideOnTap: true,
							onTap: BX.proxy(function(){ this.tapNotificationExpired = true; }, this),
							onHideAfter: BX.proxy(this.onHideAfterExpired, this)
						}, "timemanExpired"));
						this.notificationBar["expired"].show();
					}
					else
					{
						if (this.notificationBar["start"])
							this.notificationBar["start"].hide();
						setTimeout(BX.proxy(this.checkNotification, this), 30 * 1000);
					}
				}
				else
				{
					if (this.notificationBar["expired"])
						this.notificationBar["expired"].hide();
					if (this.notificationBar["start"])
						this.notificationBar["start"].hide();
				}
			},
			onHideAfterStart : function(p) {
				this.notificationBar["start"] = null;
				if (p && p["isAutoHide"] == true)
					return;
				var d = new Date(),
					nowTime = d.getHours() * 60 * 60 + d.getMinutes() * 60 + d.getSeconds(),
					ttl = 86400 - nowTime;

				if (this.params["UF_TM_MAX_START"] > 0 && this.params["UF_TM_MAX_START"] > nowTime)
				{
					ttl = this.params["UF_TM_MAX_START"] - nowTime;
					setTimeout(BX.proxy(this.checkNotification, this), (ttl + 1) * 1000);
				}
				BX.localStorage.set("timemanNSS", "hidden", ttl);
				if (this.tapNotificationStart === true)
				{
					delete this.tapNotificationStart;
					BXMobileApp.PageManager.loadPageStart({ url: this.timemanUrl, bx24ModernStyle: true });
				}
			},
			onHideAfterExpired : function(p) {
				this.notificationBar["expired"] = null;
				if (p && p["isAutoHide"] == true)
					return;
				var period = 30*60;
				BX.localStorage.set("timemanNSE", "hidden", period);
				if (this.tapNotificationExpired === true)
				{
					delete this.tapNotificationExpired;
					BXMobileApp.PageManager.loadPageStart({ url: this.timemanUrl, bx24ModernStyle: true });
				}
				setTimeout(BX.proxy(this.checkNotification, this), (period + 1) * 1000);
			},
			onMobileTimeManStatusHasBeenChanged : function(status) {
				status = BX.type.isArray(status) ? status[0] : status;
				var node = this.node,
					oldStatus = this.node.getAttribute("data-bx-timeman-status");

				if (oldStatus == status)
					return;

				if (oldStatus == "expired" && BX.type.isPlainObject(this.notificationBar.expired))
					this.notificationBar.expired.hide();
				else if (oldStatus == "start" && BX.type.isPlainObject(this.notificationBar.start))
					this.notificationBar.start.hide();
				BX.removeClass(node, "menu-user-timeman-status-opened menu-user-timeman-status-completed menu-user-timeman-status-start menu-user-timeman-status-paused menu-user-timeman-status-expired");
				BX.addClass(node, "menu-user-timeman-status-" + status);
				this.node.setAttribute("data-bx-timeman-status", status);
				this.checkNotification();
			},
			onPull : function(data) {
				var command = data["command"];
				data = data["params"];
				var status = "ready";

				if (data["STATE"] == "OPENED")
				{
					status = "opened";
				}
				else if (data["STATE"] == "CLOSED")
				{
					if (data["CAN_OPEN"] == "REOPEN" || !data["CAN_OPEN"])
					{
						status = "completed";
					}
					else
					{
						status = "start";
					}
				}
				else if (data["STATE"] == "PAUSED")
				{
					status = "paused";
				}
				else if (data["STATE"] == "EXPIRED")
				{
					status = "expired";
				}
				this.onMobileTimeManStatusHasBeenChanged(status);
			}
		};
		return d;
	})();
	var obj = null;
	BX.MenuTimeman.instance = function(params) {
		if (obj === null)
			obj = new BX.MenuTimeman(params);
		else
			obj.init();
	}
})();
