if (typeof(getToken) == "undefined")
{
	var createLink = function(tag)
	{
		var link = (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/');
		var result = false;
		var unique = false;
		var uniqueParams = {};

		var params = [];

		if (
			tag.substr(0, 10) == 'BLOG|POST|'
			|| tag.substr(0, 13) == 'BLOG|COMMENT|'
			|| tag.substr(0, 18) == 'BLOG|POST_MENTION|'
			|| tag.substr(0, 21) == 'BLOG|COMMENT_MENTION|'
			|| tag.substr(0, 11) == 'BLOG|SHARE|'
			|| tag.substr(0, 17) == 'BLOG|SHARE2USERS|'
		)
		{
			params = tag.split("|");
			result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=" + params[2];
		}

		if(
			tag.substr(0, 11) == 'TASKS|TASK|'
			|| tag.substr(0, 14) == 'TASKS|COMMENT|'
		)
		{
			params = tag.split("|");
			result = link + "mobile/tasks/snmrouter/?routePage=view&TASK_ID=" + params[2];
		}

		if (result)
		{
			result = {
				LINK: result,
				UNIQUE: unique,
				DATA: uniqueParams
			};
		}

		return result;
	};

	var imPullMessageTimeOut = 0;
	var imData = [];

	/* PULL EVENTS */
	BXMobileApp.addCustomEvent("onPullExtendWatch", function (data)
	{
		BX.PULL.extendWatch(data.id, data.force);
	});
	BX.addCustomEvent("onPullClearWatch", function (data)
	{
		BX.PULL.extendWatch(data.id);
	});

	BX.addCustomEvent("thisPageWillDie", function (data)
	{
		BX.PULL.clearWatch(data.page_id);
	});

	BX.addCustomEvent("onPullEvent", function (module_id, command, params, extra)
	{
		if(module_id == "im")
		{
			imData.push({'command': command, 'params': params, 'extra': extra});
			clearTimeout(imPullMessageTimeOut);
			imPullMessageTimeOut = setTimeout(function(){
				BXMobileApp.onCustomEvent('onPull-'+module_id, {data:imData}, true);
				imData = [];
			}, 100);
		}
		else
		{
			BXMobileApp.onCustomEvent('onPull-'+module_id, {'command': command, 'params': params, 'extra': extra}, true);
			BXMobileApp.onCustomEvent('onPull', {'module_id': module_id, 'command': command, 'params': params, 'extra': extra}, true);
		}

	});

	BX.addCustomEvent("onPullOnlineEvent", function (command, params, extra)
	{
		BXMobileApp.onCustomEvent('onPullOnline', {'command': command, 'params': params, 'extra': extra}, true);
	});

	BX.PULL.authTimeout = null;
	BX.addCustomEvent("onPullError", function (error)
	{
		if (error == 'AUTHORIZE_ERROR')
		{
			clearTimeout(BX.PULL.authTimeout);
			BX.PULL.authTimeout = setTimeout(function(){
				app.BasicAuth({
					success:function ()
					{
						BX.PULL.setPrivateVar('_pullTryConnect', true);
						BX.PULL.updateState('13', true);
					}
				});
			}, 500);
		}
	});

	/* WEBRTC */
	BXMobileApp.addCustomEvent("onCallInvite", function (data)
	{
		if (data.userId)
		{
			var useVideo = (data.video != false && data.video != "NO");
			mwebrtc.callInvite(data.userId, useVideo);
		}
	});

	BX.addCustomEvent("onOpenPush", function (push)
	{
		var pushParams = BXMobileApp.PushManager.prepareParams(push);

		if (BX.util.in_array(pushParams.ACTION, ["post", "tasks", "comment", "mention", "share", "share2users"]))
		{
			var data = createLink(pushParams.TAG);

			if (
				typeof (data.LINK) != 'undefined'
				&& data.LINK.length > 0
			)
			{
				BXMobileApp.PageManager.loadPageUnique({
					url : data.LINK,
					unique : data.UNIQUE,
					data: data.DATA,
					bx24ModernStyle : true
				});
			}
		}
	});

	/* IM EVENTS */
	BX.ready(function()
	{
		BXIM = new BX.ImMobile({
			'mobileAction': 'INIT',
			'userId': BX.message('USER_ID'),
			'user_tz_offset':0
		});
	});

	var getToken = function(repeatable)
	{
		//get device token
		var dt = (window.platform == "ios"
			? "APPLE"
			: "GOOGLE"+(app.enableInVersion(14) ? "/REV2" :"")
		);

		var params = {
			iOSUseVoipService:true,
			callback: function (data)
			{
				var token = null;

				if(typeof data == "object" )
				{
					if(data.voipToken)
					{
						token = data.voipToken;
						dt = "APPLE/VOIP"
					}
					else if(data.token)
					{
						token = data.token;
					}
				}
				else
				{
					token = data;
				}

				var config =
				{
					url:app.dataBrigePath,
					method:"POST",
					tokenSaveRequest:true,
					data:{
						mobile_action: "save_device_token",
						device_name: (typeof device.name == "undefined" ? device.model : device.name),
						uuid: device.uuid,
						device_token: token,
						device_type: dt,
						sessid: BX.bitrix_sessid()
					}
				};

				if(repeatable)
				{
					config.repeatble = true;
					var failHandler = function (field, statusCode, config)
					{
						BX.removeCustomEvent("onAjaxFailure", failHandler);
						if(config.tokenSaveRequest && statusCode == 401 && config.repeatble)
						{
							app.BasicAuth(
							{
								success:function()
								{
									getToken(false);
								}
							})
						}
					};

					BX.addCustomEvent("onAjaxFailure",  failHandler);
				}

				BX.ajax(config);
			}
		};

		app.exec("getToken", params);
	};
	BX.ready(function(){
		setTimeout(function(){
			getToken(true);
		},2000);
	});

	if(BX.PULL.supportWebSocket())
	{
		if(app.enableInVersion(16))
		{
			BX.addCustomEvent("onAppPaused", function()
			{
				BX.PULL.tryConnectSet(0, false);
				BX.PULL.returnPrivateVar('_WS').close(1000, "The app went to background");
			});

			BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function()
			{
				if(!BX.PULL.isWebSoketConnected())
				{
					BX.PULL.setPrivateVar('_pullTryConnect', false);
					BX.PULL.tryConnect();
				}
			});
		}
	}




	var connectionManager =
	{
		init:function(){

			BX.addCustomEvent('onPullStatus', function (status)
			{
				connectionManager.lastStatus = status;
				clearTimeout(connectionManager.showConnectingTimeout);
				if(status == "connect")
				{
					connectionManager.isConnecting = true;
					connectionManager.showConnectingTimeout = setTimeout(function(){
						connectionManager.connect.show();
					}, 2000);
				}
				else
				{
					if(connectionManager.connect.isShown)
					{
						setTimeout(function(){
							connectionManager.connect.hide();
						}, 1000);
					}
					else if(connectionManager.offline.isShown && status == "online")
					{
						connectionManager.offline.hide();
						setTimeout(function(){
							connectionManager.online.show();
						}, 400);
					}

				}
			});

			BX.addCustomEvent('onPullError', function (status)
			{
				connectionManager.lastStatus = status;
				clearTimeout(connectionManager.showConnectingTimeout);
				connectionManager.showConnectingTimeout = setTimeout(function(){
					connectionManager.offline.show();
				}, 2000);

			});
		},
		connect: new BXMobileApp.UI.NotificationBar({
			message: "Установка соединения...",
			color:"#afF0B31C",
			textColor: "#ffffff",
			groupId: "websoket",
			maxLines: 2,
			align: "center",
			indicatorHeight: 30,
			isGlobal:true,
			useCloseButton:true,
			autoHideTimeout: 20000,
			useLoader:true,
			onHideAfter:function(){

				if(connectionManager.isConnecting)
				{
					if(connectionManager.lastStatus == "online" || connectionManager.lastStatus == "offline")
					{
						setTimeout(function(){
							connectionManager[connectionManager.lastStatus].show(connectionManager.lastStatus);
							connectionManager.lastStatus = null;
						},500);
					}

					connectionManager.isConnecting = false;
				}
			},
			hideOnTap:true
		}, "process"),
		offline: new BXMobileApp.UI.NotificationBar({
			message: "Соединение потеряно",
			color:"#affb0000",
			textColor: "#ffffff",
			groupId: "websoket",
			maxLines: 2,
			align: "center",
			indicatorHeight: 30,
			isGlobal:true,
			useCloseButton:true,
			autoHideTimeout: 30000,
			hideOnTap:true
		}, "fail"),
		online: new BXMobileApp.UI.NotificationBar({
			message: "Соединение установлено!",
			color:"#af09A11F",
			textColor: "#ffffff",
			groupId: "websoket",
			maxLines: 2,
			align: "center",
			indicatorHeight: 30,
			isGlobal:true,
			useCloseButton:true,
			autoHideTimeout: 2000,
			hideOnTap:true
		}, "success"),
		show:function (status)
		{
			if(this[status])
			{
				this[status].show();
			}
		},
		lastStatus:null,
		isConnecting:false,
		showConnectingTimeout:0
	};

	// connectionManager.init();

}
