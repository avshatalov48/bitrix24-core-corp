;
(function ()
{
	if (BX.Menu)
		return;

	/**
	 * @bxjs_lang_path template.php
	 */
	BX.Menu = function ()
	{
		BXMobileApp.addCustomEvent('mobile_calendar_first_page_trigger', BX.proxy(function ()
		{
			window.bCalendarShowMobileHelp = false;
			this.calendarList(this.MenuSettings.userId);
		}, this));


		BX.addCustomEvent("onMobileMenuSettingsSet", BX.proxy(function ()
		{
			var pullParams = {
				enable: true,
				pulltext: BX.message("PULL_TEXT"),
				downtext: BX.message("DOWN_TEXT"),
				loadtext: BX.message("LOAD_TEXT")
			};
			if (app.enableInVersion(2))
				pullParams.action = "RELOAD";
			else
				pullParams.callback = function ()
				{
					document.location.reload();
				};
			app.pullDown(pullParams);
		}, this));

		BX.addCustomEvent("onPullOnline", BX.delegate(function(command) {

			var params = command.params;
			command = command.command;

			if ((command == "user_status") && this.MenuSettings.userId == params.USER_ID && params.COLOR != "")
			{
				BX.style(BX('menu-user'), 'background-color', params.COLOR);
			}
		}, this));

		BXMobileApp.addCustomEvent('onClearLFCounter', function (counterCode)
		{
			var ob = {};
			ob[counterCode] = 0;
			BX.Menu.updateCounters(ob);
		});

		BX.addCustomEvent("onPullEvent-main", function (command, params)
		{
			if (command == "user_counter" && params[BX.message("SITE_ID")])
			{
				var counters = params[BX.message("SITE_ID")];
				BX.Menu.updateCounters(counters);
			}
		});

		BX.addCustomEvent("onImUpdateCounter", function (counters)
		{
			if (counters)
			{
				if (typeof counters["obZeroDate"] != 'undefined')
				{
					var obZeroCounter = counters["obZeroDate"];
					delete counters["obZeroDate"];
				}

				BX.Menu.updateCounters(
					counters,
					(
						typeof obZeroCounter != 'undefined'
							? obZeroCounter
							: null
					)
				);
			}
		});
		BX.addCustomEvent("onUpdateUserCounters", function (counters)
		{
			if (counters)
			{
				if (typeof counters["obZeroDate"] != 'undefined')
				{
					var obZeroCounter = counters["obZeroDate"];
					delete counters["obZeroDate"];
				}

				BX.Menu.updateCounters(
					counters,
					(
						typeof obZeroCounter != 'undefined'
							? obZeroCounter
							: null
					)
				);
			}
		});

		this.MenuSettings = {
			lang: {},
			userId: false,
			siteDir: '/',
			canInvite: false,
			calendarFirstVisit: false,
			profileUrl: null,
			helpUrl: null,
			timemanUrl: null,
			marketPlaceApps:[],
			set: function (settings)
			{
				if (settings)
				{
					if (settings.userId)
						this.userId = settings.userId;
					if (settings.siteDir)
						this.siteDir = settings.siteDir;
					if (settings.marketPlaceApps)
						this.marketPlaceApps = settings.marketPlaceApps;
					if (settings.canInvite)
						this.canInvite = settings.canInvite;
					if (settings.calendarFirstVisit)
						this.calendarFirstVisit = settings.calendarFirstVisit;
					if (BX.type.isNotEmptyString(settings.profileUrl))
					{
						this.profileUrl = settings.profileUrl;
					}
					if (BX.type.isNotEmptyString(settings.helpUrl))
					{
						this.helpUrl = settings.helpUrl;
					}
					if (BX.type.isNotEmptyString(settings.timemanUrl))
					{
						this.timemanUrl = settings.timemanUrl;
					}

				}

				BX.onCustomEvent("onMobileMenuSettingsSet", [settings]);
			}
		};

		this.getMarketPlaceAppName = function(id)
		{
			var apps =  this.MenuSettings.marketPlaceApps;
			var count = apps.length;

			for (var i = 0; i < count; i++)
			{
				if(apps[i]["id"] == id)
				{
					return apps[i]["name"];
				}
			}

			return null;
		};

		this.currentItem = null;
		this.init = function (currentItem)
		{
			this.currentItem = currentItem;
			var items = document.getElementById("menu-items");
			var that = this;

			new FastButton(
				items,
				function (event)
				{
					that.onItemClick(event);
				}
			);

			var buttons = {
				"menu-user-accounts": function (event)
				{
					app.exec('showAuthForm');
					BX.eventCancelBubble(event);
				},
				"menu-user-help": function (event)
				{
					BXMobileApp.PageManager.loadPageStart({url: that.MenuSettings.helpUrl});
					BX.eventCancelBubble(event);
				},
				"menu-user-timeman": function (event)
				{
					BXMobileApp.PageManager.loadPageStart({ url: that.MenuSettings.timemanUrl, bx24ModernStyle: true });
					BX.eventCancelBubble(event);
				},
				"menu-user-logout": function (event)
				{
					app.logOut();
					BX.eventCancelBubble(event);
				}
			};

			for (var buttonId in buttons)
			{
				var button = BX(buttonId);
				if (!button)
				{
					continue;
				}

				BX.bind(button, "touchstart", function ()
				{
					BX.addClass(this, "menu-user-action-selected");
				});

				BX.bind(button, "touchend", function ()
				{
					BX.removeClass(this, "menu-user-action-selected");
				});

				new FastButton(button, buttons[buttonId]);
			}

			new FastButton(BX("menu-user"), function ()
			{
				BXMobileApp.PageManager.loadPageStart({
					url: that.MenuSettings.profileUrl,
					bx24ModernStyle: true,
					page_id: "user_profile"
				});
			});

		};

		this.onItemClick = function (event)
		{
			var target = event.target;
			var isChild = (BX.hasClass(target.parentNode, "menu-item"));
			if (target && target.nodeType && target.nodeType == 1 && (BX.hasClass(target, "menu-item") || isChild))
			{
				if (isChild)
					target = target.parentNode;
				if (this.currentItem != null)
					this.unselectItem(this.currentItem);

				this.selectItem(target);

				if (target.getAttribute("data-highlight") == 'N')
				{
					setTimeout(BX.delegate(function ()
					{
						this.unselectItem(target);
					}, this), 500);
				}
				var url = target.getAttribute("data-url");
				var name = target.getAttribute("data-name");
				var mpAppFlag = target.getAttribute("data-mp-app");
				var mpAppId = target.getAttribute("data-mp-app-id");
				var mpAppName = target.getAttribute("data-mp-app-name");
				var pageId = target.getAttribute("data-page-id");
				var sideNotifyPanel = target.getAttribute("data-modern-style");

				if (BX.type.isNotEmptyString(url))
				{
					var pageParams = {"url": url};


					if(mpAppFlag === "Y")
					{
						if(mpAppName !== null)
						{
							pageParams["title"] = mpAppName;

						}

						pageParams.url = location.protocol+"//"+location.host+pageParams.url;
						app.exec("openMarketplaceApp", pageParams);
					}
					else
					{
						if(name)
						{
							pageParams["title"] = name;
						}

						if (BX.type.isNotEmptyString(pageId))
							pageParams.page_id = pageId;
						if (BX.type.isNotEmptyString(sideNotifyPanel) && sideNotifyPanel == "Y")
							pageParams.bx24ModernStyle = true;
						BXMobileApp.PageManager.loadPageStart(pageParams);
					}
				}
				else
				{
					target.onclick();
				}

				this.currentItem = target;
			}

		};

		this.selectItem = function (item)
		{
			if (!BX.hasClass(item, "menu-item-selected"))
				BX.addClass(item, "menu-item-selected");
		};

		this.unselectItem = function (item)
		{
			BX.removeClass(item, "menu-item-selected");
		}
	};

	BX.Menu.counters = {};
	BXMobileApp.addCustomEvent('onGetLFCounter', function (params)
	{
		if (
			typeof params.counterType != 'undefined'
			&& typeof BX.Menu.counters[params.counterType] != 'undefined'
		)
		{
			BXMobileApp.onCustomEvent('onSetLFCounter', {
				value: BX.Menu.counters[params.counterType]["value"],
				zeroCounterTS: BX.Menu.counters[params.counterType]["zeroCounter"]
			}, true);
		}
	});

	BX.Menu.updateCounters = function (counters, obZeroCounter)
	{
		var totalCount = 0;
		for (var id in counters)
		{
			var counter = BX(id == "**" ? "menu-counter-live-feed" : "menu-counter-" + id.toLowerCase(), true);
			if (!counter)
				continue;

			if (counters[id] > 0)
			{
				var plus = counters[id] > 50;
				var valueContainer = BX.findChild(counter, {className:"menu-item-counter-value"});
				if(valueContainer)
				{
					valueContainer.innerHTML = plus ? "50" : counters[id];
					BX.addClass(counter, "menu-item-counter-show-value" + (plus ? " menu-item-counter-show-plus" : ""));
				}
			}
			else
			{
				BX.removeClass(counter, "menu-item-counter-show-value menu-item-counter-show-plus");
			}
			window.test = counter;


			BX.Menu.counters[id] = {
				value: counters[id],
				zeroCounter: (
					typeof obZeroCounter == 'object'
 					&& obZeroCounter != null
					&& typeof obZeroCounter[id] != 'undefined'
						? obZeroCounter[id]
						: null
				)
			};
		}

		for (var key in BX.Menu.counters)
		{
			totalCount = totalCount + parseInt(BX.Menu.counters[key]["value"]);
		}
	};

	BX.Menu.prototype.userList = function (isExtranetUser)
	{
		var listUserSettings = {
			url: this.MenuSettings.siteDir + "mobile/?mobile_action=get_user_list&tags=Y&detail_url=" + this.MenuSettings.siteDir + "mobile/users/?user_id=",
			isroot: true,
			table_settings: {
				type:"users",
				name: (isExtranetUser ? BX.message("MB_CONTACTS") : BX.message("MB_COMPANY")),
				alphabet_index: true,
				outsection: false

			}
		};

		if (this.MenuSettings.canInvite)
		{
			listUserSettings["table_settings"]["button"] = {
				type: "plus",
				callback: BX.delegate(function ()
				{
					app.openNewPage(this.MenuSettings.siteDir + "mobile/users/invite.php");
				}, this)
		};

		}
		app.openBXTable(listUserSettings);

		app.closeMenu();

		/**
		 * We call the follow function to show dialog with a question about synchronization of contacts
		 */
		if(platform == "android")
		{
			app.exec("offerAndroidAccountContactsSync");
		}
		else
		{

			if(BX.localStorage.get("carddav_ask") != "Y")
			{
				BX.localStorage.set("carddav_ask", "Y", 3600 * 24 * 30);
				app.confirm({
					text:BX.message("MB_ASK_SYNC_CARDDAV"),
					buttons:[BX.message("MB_ASK_SYNC_YES"),BX.message("MB_ASK_SYNC_NO")],
					callback:function(buttonIndex){
						if(buttonIndex == 1)
						{
							BX.ajax.get("/bitrix/tools/dav_profile.php?action=token&params[resources]=carddav", {}, function(json){
								console.log(json);
								var response  = JSON.parse(json);
								if(response.token)
								{
									var urlPath = "/bitrix/tools/dav_profile.php?action=payload&params[resources]=carddav&params[access_token]=";
									app.openUrl(window.document.location.protocol+"//"+document.location.host+urlPath+response.token);
								}
							});
						}
					}

				});
			}

		}
	};

	BX.Menu.prototype.bpList = function (p)
	{
		app.openBXTable({
			url: this.MenuSettings.siteDir + 'mobile/webdav/' + p,
			isroot: true,
			table_settings: {
				type: "files",
				useTagsInSearch: false
			}
		});
		app.closeMenu();
	}

	BX.Menu.prototype.webdavList = function (p)
	{
		app.openBXTable({
			url: this.MenuSettings.siteDir + 'mobile/webdav/' + p,
			isroot: true,
			table_settings: {
				type: "files",
				name: BX.message("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM"),
				useTagsInSearch: false
			}
		});
		app.closeMenu();
	};

	BX.Menu.prototype.diskList = function (storageData, path)
	{

		path = path || '/';
		storageData = storageData || {};
		path = encodeURIComponent(path);
		var type = encodeURIComponent(storageData.type);
		var entityId = encodeURIComponent(storageData.entityId);

		BX.MobileUI.List.show(
			{
				url: this.MenuSettings.siteDir + 'mobile/?mobile_action=disk_folder_list&type=' + type + '&path=' + path + '&entityId=' + entityId,
				isroot: true,
				table_settings: {
					type: "files",
					showTitle:true,
					name: (storageData.entityId === "shared_files_s1")
						?BX.message("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW")
						:BX.message("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
					useTagsInSearch: false,
					overflowmenu:true
				}
			},
			"disk"
		);

		app.closeMenu();
	};

	BX.Menu.prototype.calendarList = function (userId)
	{

		BX.addCustomEvent('mobile_calendar_first_page', function ()
		{
			window.bCalendarShowMobileHelp = false;
		});

		if (window.bCalendarShowMobileHelp == undefined)
		{
			window.bCalendarShowMobileHelp = this.MenuSettings.calendarFirstVisit;
		}

		if (window.bCalendarShowMobileHelp === false || window.platform == 'android')
		{
			app.openBXTable(
				{
					url: this.MenuSettings.siteDir + 'mobile/?mobile_action=calendar&user_id=' + userId,
					isroot: true,
					table_id: 'calendar_list',
					table_settings: {
						cache: true,
						name: BX.message("MB_CALENDAR_LIST"),
						useTagsInSearch: false,
						use_sections: true,
						button: {
							type: 'plus',
							callback: BX.delegate(function ()
							{

								app.openNewPage(this.MenuSettings.siteDir + 'mobile/calendar/edit_event.php');
								// BXMPager.loadPageModal(
								// 	{
								// 		url:this.MenuSettings.siteDir + 'mobile/calendar/edit_event.php',
								// 		data:{
								// 			modal:"Y"
								// 		}
								// 	}
								// );
							}, this)
						}
					}
				}
			);
		}
		else
		{
			app.loadPage(this.MenuSettings.siteDir + 'mobile/calendar/first_page.php');
		}
		app.closeMenu();
	};

	window.MobileMenu = new BX.Menu();

	//Listeners for lists
	BX.MobileUI.List.addListener(
		function(eventName, params, listObject)
		{

			if(eventName == BX.MobileUI.List.Events.ON_ITEM_MORE_CHOOSED)
			{
				listObject.showMenu([
					{
						title:BX.message("MB_COPY_PUBLIC_LINK"),
						sort:2,
						params: params,
						eventName: "createLink"
					}
				], params.NAME);
			}
			else if(eventName == "createLink")
			{
				if(params.ID)
				{
					BX.ajax.post("/bitrix/components/bitrix/disk.folder.list/ajax.php?action=generateExternalLink",{
						objectId:params.ID,
						sessid:BX.bitrix_sessid()
					}, function(response){
						var json = JSON.parse(response);
						if(json.link)
						{
							app.exec("copyToClipboard", {text: json.link});
							app.alert(BX.message("MB_COPY_LINK_COPIED_MESSAGE"))
						}

					})
				}
			}

		},
		"disk"
	);
})();
