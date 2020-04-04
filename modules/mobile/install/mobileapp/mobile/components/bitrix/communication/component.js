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
		/**
		 *  Mobile interface badges
		 */
		constructor()
		{
			this.total = 0;
			this.config = {};
			this.configAssociation = {
				'stream': 'socialnetwork_livefeed',
				'notifications': 'im_messenger',
				'messages': 'im_messenger',
				'openlines': 'im_messenger',
				'tasks_total': 'tasks_total',
			};

			this.sharedStorage = Application.sharedStorage();

			let counters = this.sharedStorage.get('counters');
			this.counters = counters? JSON.parse(counters): {};

			let userCounters = this.sharedStorage.get('userCounters');
			this.userCounters = userCounters? JSON.parse(userCounters): {};

			let userCountersDates = this.sharedStorage.get('userCountersDates');
			this.userCountersDates = userCountersDates? JSON.parse(userCountersDates): {};

			BX.addCustomEvent("onSetUserCounters", this.onSetUserCounters.bind(this));
			BX.addCustomEvent("onClearLiveFeedCounter", this.onClearLiveFeedCounter.bind(this));
			BX.addCustomEvent("onUpdateBadges", this.onUpdateBadges.bind(this));
			BX.addCustomEvent("onUpdateConfig", this.onUpdateConfig.bind(this));
			BX.addCustomEvent("onPullEvent-main", this.onPull.bind(this));
			BX.addCustomEvent("requestUserCounters", this.requestUserCounters.bind(this));

			this.updateCountersInterval = 500;
			this.updateUserCountersInterval = 500;

			this.databaseMessenger = new ReactDatabase(ChatDatabaseName, CONFIG.USER_ID, CONFIG.LANGUAGE_ID, CONFIG.SITE_ID);

			this.loadFromCache();
		}

		onSetUserCounters(counters, time)
		{
			let startTime = null;

			if (
				time
				&& typeof this.userCounters[CONFIG.SITE_ID] == 'object'
				&& typeof this.userCountersDates[CONFIG.SITE_ID] == 'object'
			)
			{
				startTime = time.start*1000;

				for (let counter in this.userCountersDates[CONFIG.SITE_ID])
				{
					if (!this.userCountersDates[CONFIG.SITE_ID].hasOwnProperty(counter))
					{
						continue;
					}
					if (typeof counters[CONFIG.SITE_ID][counter] == 'undefined')
					{
						if (this.userCountersDates[CONFIG.SITE_ID][counter] <= startTime)
						{
							delete this.userCounters[CONFIG.SITE_ID][counter];
							delete this.userCountersDates[CONFIG.SITE_ID][counter];
						}
					}
				}
			}

			this.onUpdateUserCounters(counters, startTime);
		}

		onClearLiveFeedCounter(params)
		{
			let startTime = null;

			if (
				BX.type.isNotEmptyString(params.counterCode)
				&& typeof params.serverTimeUnix != 'undefined'
				&& typeof this.userCounters[CONFIG.SITE_ID] == 'object'
				&& typeof this.userCountersDates[CONFIG.SITE_ID] == 'object'
			)
			{
				startTime = params.serverTimeUnix * 1000;

				var counters = {};
				counters[CONFIG.SITE_ID] = {};
				counters[CONFIG.SITE_ID][params.counterCode] = 0;

				if (this.userCountersDates[CONFIG.SITE_ID][params.counterCode] <= startTime)
				{
					delete this.userCounters[CONFIG.SITE_ID][params.counterCode];
					delete this.userCountersDates[CONFIG.SITE_ID][params.counterCode];
				}

				this.onUpdateUserCounters(counters, startTime);
			}
		}

		onUpdateUserCounters(counters, startTime)
		{
			let currentTime = (new Date()).getTime();
			startTime = startTime || currentTime;

			for (let site in counters)
			{
				if (!counters.hasOwnProperty(site))
				{
					continue;
				}

				for (let counter in counters[site])
				{
					if (!counters[site].hasOwnProperty(counter))
					{
						continue;
					}

					if (typeof this.userCountersDates[site] == 'undefined')
					{
						this.userCountersDates[site] = {};
					}
					if (typeof this.userCountersDates[site][counter] == 'undefined')
					{
						this.userCountersDates[site][counter] = startTime;
					}
					else
					{
						if (this.userCountersDates[site][counter] >= startTime)
						{
							delete counters[site][counter];
						}
						else
						{
							this.userCountersDates[site][counter] = startTime;
						}
					}
				}
			}

			this.userCounters = Utils.objectMerge(this.userCounters, counters);
			if (!counters[CONFIG.SITE_ID])
				return false;

			let needUpdate = false;
			if (counters[CONFIG.SITE_ID].hasOwnProperty('**'))
			{
				counters[CONFIG.SITE_ID]['**'] = parseInt(counters[CONFIG.SITE_ID]['**']);
				if (this.counters['stream'] != counters[CONFIG.SITE_ID]['**'])
				{
					this.counters['stream'] = counters[CONFIG.SITE_ID]['**'];
					needUpdate = true;
				}
			}

			if (this.counters['tasks_total'] != counters[CONFIG.SITE_ID]['tasks_total'])
			{
				this.counters['tasks_total'] = counters[CONFIG.SITE_ID]['tasks_total'];
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

		onPull(command, params, extra)
		{
			if (command == 'user_counter')
			{
				this.onUpdateUserCounters(params, extra.server_time_unix*1000);
			}
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

		onUpdateBadges(params, delay)
		{
			let needUpdate = false;
			for (let element in params)
			{
				if (!params.hasOwnProperty(element))
				{
					continue;
				}

				params[element] = parseInt(params[element]);
				if (this.counters[element] != params[element])
				{
					this.counters[element] = params[element];
					needUpdate = true;
				}
			}
			if (needUpdate)
			{
				this.update(delay === false);
			}
		}

		onUpdateConfig(config)
		{
			this.config = config;
			this.update();
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

			let total = 0;

			for (let element in this.counters)
			{
				if (!this.counters.hasOwnProperty(element))
				{
					continue;
				}

				this.counters[element] = parseInt(this.counters[element]);
				if (this.counters[element] <= 0)
				{
					this.counters[element] = 0;
				}

				if (this.checkConfigCounter(element))
				{
					total += this.counters[element];
				}
			}

			console.info("AppCounters.update: update counters: "+this.total+"\n", this.counters);
			Application.setBadges(this.counters);

			if (this.total != total)
			{
				this.total = total;
				if (!Application.isBackground())
				{
					Application.setIconBadge(this.total);
				}
			}

			this.updateCache();

			return true;
		}

		checkConfigCounter(counter)
		{
			let configName = this.configAssociation[counter];
			if (!configName)
			{
				return true;
			}

			return this.config[configName] === true;
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
						this.config[counterType.type] = counterType.value;
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
			}, this.updateUserCountersInterval);

			return true;
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
								device_type : dt,
							}
						})
							.then((data) => console.log(data))
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
				result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=" + params[2] + "&commentId=" + params[3];
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










