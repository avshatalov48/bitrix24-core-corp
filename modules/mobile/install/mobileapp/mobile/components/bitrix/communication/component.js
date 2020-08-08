"use strict";
(()=>{

	if (typeof this.SocketConnection == 'undefined')
	{
		this.SocketConnection = new Connection();
		this.SocketConnection.start();
	}
	else
	{
		SocketConnection.disconnect(1000, "restart");
		setTimeout(() => {
			this.SocketConnection = new Connection();
			this.SocketConnection.start();
		}, 2000);
	}

	class AppCounters
	{
		constructor()
		{
			this.total = 0;
			this.firstSetBadge = true;

			this.applicationCounterConfig = {};

			this.tabNameMapConfigName = {
				'stream': 'socialnetwork_livefeed',
				'messages': 'im_messenger',
				'openlines': 'im_messenger',
				'tasks_total': 'tasks_total',
			};

			this.userCounterMapTabName = {
				'**': 'stream',
				'im': 'messages',
				'tasks_total': 'tasks_total',
			};

			this.sharedStorage = Application.sharedStorage();

			let counters = this.sharedStorage.get('counters');
			this.counters = counters? JSON.parse(counters): {};

			let userCounters = this.sharedStorage.get('userCounters');
			this.userCounters = userCounters? JSON.parse(userCounters): {};

			let userCountersDates = this.sharedStorage.get('userCountersDates');
			this.userCountersDates = userCountersDates? JSON.parse(userCountersDates): {};

			BX.addCustomEvent("onUpdateConfig", this.onUpdateApplicationCounterConfig.bind(this));
			BX.addCustomEvent("onSetUserCounters", this.onSetUserCounters.bind(this));
			BX.addCustomEvent("onClearLiveFeedCounter", this.onClearLiveFeedCounter.bind(this));
			BX.addCustomEvent("onUpdateBadges", this.onUpdateBadges.bind(this));
			BX.addCustomEvent("onPullEvent-main", this.onPullEvent.bind(this));
			BX.addCustomEvent("requestUserCounters", this.requestUserCounters.bind(this));

			this.updateCacheTimeout = 500;

			this.databaseMessenger = new ReactDatabase(ChatDatabaseName, CONFIG.USER_ID, CONFIG.LANGUAGE_ID, CONFIG.SITE_ID);

			this.loadFromCache();
		}

		onSetUserCounters(counters, time)
		{
			let startTime = null;
			let siteId = CONFIG.SITE_ID;

			if (
				time
				&& typeof this.userCounters[siteId] == 'object'
				&& typeof this.userCountersDates[siteId] == 'object'
			)
			{
				startTime = time.start*1000;

				for (let counterName in this.userCountersDates[siteId])
				{
					if (!this.userCountersDates[siteId].hasOwnProperty(counterName))
					{
						continue;
					}

					if (
						typeof counters[siteId] == 'undefined'
						|| typeof counters[siteId][counterName] == 'undefined'
					)
					{
						continue;
					}

					if (this.userCountersDates[siteId][counterName] <= startTime)
					{
						delete this.userCounters[siteId][counterName];
						delete this.userCountersDates[siteId][counterName];
					}
				}
			}

			this.onUpdateUserCounters(counters, startTime);
		}

		onClearLiveFeedCounter(params)
		{
			let siteId = CONFIG.SITE_ID;
			if (!(
				BX.type.isNotEmptyString(params.counterCode)
				&& typeof params.serverTimeUnix != 'undefined'
				&& typeof this.userCounters[siteId] == 'object'
				&& typeof this.userCountersDates[siteId] == 'object'
			))
			{
				return false;
			}

			let startTime = params.serverTimeUnix * 1000;

			if (this.userCountersDates[siteId][params.counterCode] <= startTime)
			{
				delete this.userCounters[siteId][params.counterCode];
				delete this.userCountersDates[siteId][params.counterCode];
			}

			let counters = {};
			counters[siteId] = {};
			counters[siteId][params.counterCode] = 0;

			this.onUpdateUserCounters(counters, startTime);

			return true;
		}

		onPullEvent(command, params, extra)
		{
			if (command == 'user_counter')
			{
				this.onUpdateUserCounters(params, extra.server_time_unix*1000);
			}
		}

		onUpdateBadges(params, delay)
		{
			let needUpdate = false;

			for (let element in params)
			{
				if (!params.hasOwnProperty(element))
				{
					continue;
				}

				params[element] = Number(params[element]);

				if (Number.isNaN(params[element]))
				{
					continue;
				}

				if (this.counters[element] == params[element])
				{
					continue;
				}

				this.counters[element] = params[element];
				needUpdate = true;
			}

			if (needUpdate)
			{
				this.update(delay === false);
			}
		}

		onUpdateApplicationCounterConfig(config)
		{
			this.applicationCounterConfig = {};

			for (let counterName in config)
			{
				if (!config.hasOwnProperty(counterName))
				{
					continue;
				}

				this.applicationCounterConfig[counterName] = !!config[counterName];
			}

			this.update();
		}

		onUpdateUserCounters(counters, startTime)
		{
			let currentTime = (new Date()).getTime();
			startTime = startTime || currentTime;

			let siteId = CONFIG.SITE_ID;

			if (typeof counters != 'object' || typeof counters[siteId] != 'object')
			{
				return false;
			}

			for (let counter in counters[siteId])
			{
				if (!counters[siteId].hasOwnProperty(counter))
				{
					continue;
				}

				counters[siteId][counter] = Number(counters[siteId][counter]);

				if (Number.isNaN(counters[siteId][counter]))
				{
					delete counters[siteId][counter];
					continue;
				}

				if (typeof this.userCountersDates[siteId] == 'undefined')
				{
					this.userCountersDates[siteId] = {};
				}

				if (typeof this.userCountersDates[siteId][counter] == 'undefined')
				{
					this.userCountersDates[siteId][counter] = startTime;

				}
				else
				{
					if (this.userCountersDates[siteId][counter] >= startTime)
					{
						delete counters[siteId][counter];
					}
					else
					{
						this.userCountersDates[siteId][counter] = startTime;
					}
				}
			}

			this.userCounters = Utils.objectMerge(this.userCounters, counters);

			let needUpdate = false;
			for (let userCounter in this.userCounterMapTabName)
			{
				if (!this.userCounterMapTabName.hasOwnProperty(userCounter))
				{
					continue;
				}

				if (typeof this.userCounters[siteId][userCounter] == 'undefined')
				{
					continue;
				}

				let value = Number(this.userCounters[siteId][userCounter]);
				if (Number.isNaN(value))
				{
					delete this.userCounters[siteId][userCounter];
					continue;
				}

				let tabName = this.userCounterMapTabName[userCounter];
				if (this.counters[tabName] == value)
				{
					continue;
				}

				this.counters[tabName] = value;
				needUpdate = true;
			}

			if (needUpdate)
			{
				this.update(true);
			}
			else
			{
				this.updateCache();
			}

			BX.postComponentEvent("onUpdateUserCounters", [this.userCounters]);
			BX.postWebEvent("onUpdateUserCounters", this.userCounters);

			return true;
		}

		update(delay)
		{
			if (delay)
			{
				if (!this.updateCountersTimeout)
				{
					this.updateCountersTimeout = setTimeout(this.update.bind(this), 1000);
				}
				return true;
			}

			clearTimeout(this.updateCountersTimeout);
			this.updateCountersTimeout = null;

			let total = Object.keys(this.counters)
				.filter(counterType => this.isEnableApplicationCounterType(counterType))
				.reduce((currentTotal, key) => {
					let counter = Number(this.counters[key]);
					let value = Number.isNaN(counter)? 0: counter;
					return currentTotal + value;
				}, 0)
			;

			console.info("AppCounters.update: update counters: "+total+"\n", this.counters);
			Application.setBadges(this.counters);

			if (this.firstSetBadge || this.total != total)
			{
				this.total = total;

				if (!Application.isBackground())
				{
					Application.setIconBadge(this.total);
					this.firstSetBadge = false;
				}
			}

			this.updateCache();

			return true;
		}

		isEnableApplicationCounterType(tabName)
		{
			let counterName = this.tabNameMapConfigName[tabName];
			if (!counterName)
			{
				return false;
			}

			return this.applicationCounterConfig[counterName] === true;
		}

		loadFromCache()
		{
			this.databaseMessenger.table(ChatTables.notifyConfig).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length <= 0)
					{
						this.update();
						return false;
					}

					let cacheData = JSON.parse(items[0].VALUE);
					for (let counterType of cacheData.counterTypes)
					{
						this.applicationCounterConfig[counterType.type] = counterType.value;
					}

					console.info('SettingsNotify.loadCache: config load from cache', cacheData.counterTypes);

					this.update();

				}).catch(() => {
					this.update();
				});
			});

			return true;
		}

		updateCache()
		{
			clearTimeout(this.refreshUserCounterTimeout);
			this.refreshUserCounterTimeout = setTimeout(() =>
			{
				this.sharedStorage.set('counters', JSON.stringify(this.counters));
				this.sharedStorage.set('userCounters', JSON.stringify(this.userCounters));
				this.sharedStorage.set('userCountersDates', JSON.stringify(this.userCountersDates));
				console.info("AppCounters.updateCache: userCounters updated");
			}, this.updateCacheTimeout);

			return true;
		}

		requestUserCounters(params)
		{
			console.info('Counters.requestUserCounters: ', params);

			if (params.component && params.component.toString().length > 0)
			{
				BX.postComponentEvent("onUpdateUserCounters", [this.userCounters], params.component);
			}
			if (params.web)
			{
				BX.postWebEvent("onUpdateUserCounters", this.userCounters);
			}
		}
	}

	window.Counters = new AppCounters();


	/**
	 * Auth restore
	 */

	let Authorization =
		{
			restore:() => Application.auth(
				result => {
					console.info(
						(!result || result.status != "success")
							?"Authorization.restore: fail!"
							:"Authorization.restore: success!"
					);
				}
			),
			start:function(){
				if(typeof Application.auth === "function")
				{
					console.info("Authorization.start: auth restore is active\n", this);
					this.intervalId = setInterval(this.restore, this.interval);
				}
			},

			interval:24 * 60 * 1000,
			intervalId:0,
		};

	Authorization.start();
	window.Authorization = Authorization;


	/**
	 *  Push notification registration
	 */

	setTimeout(() =>
	{
		Cordova.exec(
			(deviceInfo) =>
			{
				this.device = deviceInfo;
				Application.registerPushNotifications(
					function (data)
					{
						console.log("registerPushNotifications", data)

						var dt = (Application.getPlatform() === "ios"
								? "APPLE"
								: "GOOGLE/REV2"
						);

						var token = null;

						if (typeof data == "object")
						{
							if (data.voipToken)
							{
								token = data.voipToken;
								dt = "APPLE/VOIP"
							}
							else if (data.token)
							{
								token = data.token;
							}
						}
						else
						{
							token = data;
						}

						BX.ajax({
							url : env.siteDir + "mobile/",
							method : "POST",
							dataType : "json",
							tokenSaveRequest : true,
							data : {
								mobile_action : "save_device_token",
								device_name : (typeof device.name == "undefined"? device.model: device.name),
								uuid : device.uuid,
								device_token : token,
								device_token_voip : data.voip ? data.voip : '',
								device_type : dt,
							}
						})
							.then((data) => console.log("save_device_token response ", data))
							.catch((e) => console.error(e))
						;
					}
				);
			},
			() =>
			{
			}, "Device", "getDeviceInfo", []);
	}, 0);

	/**
	 * Push handling
	 */


	let PushNotifications = {
		urlByTag: function(tag)
		{
			var link = (env.siteDir ? env.siteDir : '/');
			var result = false;
			var unique = false;
			var uniqueParams = {};

			var params = [];

			if (
				tag.substr(0, 10) == 'BLOG|POST|'
				|| tag.substr(0, 18) == 'BLOG|POST_MENTION|'
				|| tag.substr(0, 11) == 'BLOG|SHARE|'
				|| tag.substr(0, 17) == 'BLOG|SHARE2USERS|'
			)
			{
				params = tag.split("|");
				result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=" + params[2];
			}

			else if (
				tag.substr(0, 13) == 'BLOG|COMMENT|'
				|| tag.substr(0, 21) == 'BLOG|COMMENT_MENTION|'
			)
			{
				params = tag.split("|");
				result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=" + params[2] + "&commentId=" + params[3] + "#com" + params[3];
			}

			else if(
				tag.substr(0, 11) == 'TASKS|TASK|'
				|| tag.substr(0, 14) == 'TASKS|COMMENT|'
			)
			{
				params = tag.split("|");
				BX.postComponentEvent(
					'taskbackground::task::action',
					[{id: params[2]}, params[2], {taskId: params[2], getTaskInfo: true}]
				);
			}
			else if (tag.substr(0, 12) == 'SONET|EVENT|')
			{
				params = tag.split("|");
				result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=" + params[2];
			}
			else if (tag.substr(0, 11) == 'DISK_GROUP|')
			{
				params = tag.split("|");
				result = link + "mobile/?mobile_action=disk_folder_list&type=group&path=/&entityId=" + params[1];
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
		},
		handler: function ()
		{
			let push = Application.getLastNotification();
			let pushParams = {};

			if (typeof (push) !== 'object' || typeof (push.params) === 'undefined')
			{
				pushParams =  {'ACTION' : 'NONE'};
			}
			if(typeof push.params != "undefined")
			{
				try
				{
					pushParams = JSON.parse(push.params);
				}
				catch (e)
				{
					pushParams = {'ACTION' : push.params};
				}

				if (this.actions.includes(pushParams.ACTION))
				{
					var data = this.urlByTag(pushParams.TAG);

					if (
						typeof (data.LINK) != 'undefined'
						&& data.LINK.length > 0
					)
					{
						PageManager.openPage({
							url : data.LINK,
							unique : data.UNIQUE,
							data: data.DATA,
						});
					}
				}
			}
		},
		actions: ["post", "tasks", "comment", "mention", "share", "share2users", "sonet_group_event"],
		init:function(){
			this.handler(); //handle first start of the app
			BX.addCustomEvent("onAppActive", ()=> this.handler()); //listen for the app wake up
		}
	};

	PushNotifications.init();
	window.PushNotifications = PushNotifications;

	/**
	 * User Profile
	 */

	BX.addCustomEvent("onUserProfileOpen", (userId, options = {}) =>
	{
		console.log("onUserProfileOpen", userId, options);

		if(Application.getApiVersion() >= 27)
		{
			let url = "/mobile/mobile_component/user.profile/?version=1";

			if(availableComponents && availableComponents["user.profile"])
			{
				url = availableComponents["user.profile"]["publicUrl"];
			}

			let backdropOptions = {};
			let isBackdrop = false;
			if (options.backdrop)
			{
				if (typeof options.backdrop === 'object' && options.backdrop)
				{
					backdropOptions = {backdrop: options.backdrop};
					isBackdrop = true;
				}
				else if (typeof options.backdrop === 'boolean' && options.backdrop)
				{
					backdropOptions = {backdrop: {}};
					isBackdrop = true;
				}
			}

			PageManager.openComponent("JSStackComponent",
				{
					scriptPath: url,
					params: {userId, isBackdrop},
					canOpenInDefault:true,
					rootWidget: {
						name: "list",
						groupStyle: true,
						settings: Object.assign({
							objectName: "form",
							groupStyle: true,
						}, backdropOptions)
					}
				});
		}
		else
		{
			PageManager.openPage({url:"/mobile/users/?user_id="+userId});
		}
	});
})();










