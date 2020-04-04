"use strict";
/**
 * @bxjs_lang_path component.php
 */

var REVISION = 19; // api revision - sync with im/lib/revision.php

/* Clean session variables after page restart */
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

if (typeof clearInterval == 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}
if (typeof RecentList != 'undefined' && typeof RecentList.cleaner != 'undefined')
{
	RecentList.cleaner();
}

/* Recent list API */
var RecentList = {};

RecentList.init = function()
{
	/* set cross-links in class */
	let links = [
		'base', 'cache', 'pull', 'push',
		'queue', 'notify', 'notifier', 'event',
		'action', 'search', 'chatCreate'
	];
	links.forEach((subClass) => {
		if (typeof this[subClass] != 'undefined')
		{
			links.forEach((element) => {
				if (element == 'base')
				{
					this[subClass]['base'] = this;
				}
				else if (subClass != element)
				{
					this[subClass][element] = this[element];
				}
			});
		}
	});

	let configMessages = BX.componentParameters.get("MESSAGES", {});
	for (let messageId in configMessages)
	{
		if (configMessages.hasOwnProperty(messageId))
		{
			BX.message[messageId] = configMessages[messageId];
		}
	}

	/* vars */
	this.debugLog = BX.componentParameters.get('PULL_DEBUG', false);
	this.generalChatId = BX.componentParameters.get('IM_GENERAL_CHAT_ID', 0);

	this.imagePath = component.path+'images';

	this.list = [];
	this.listEmpty = true;
	this.blocked = {};

	this.lastRecentRequest = null;

	this.siteId = BX.componentParameters.get('SITE_ID', 's1');
	this.siteDir = BX.componentParameters.get('SITE_DIR', '/');
	this.languageId = BX.componentParameters.get('LANGUAGE_ID', 'en');
	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));

	this.userData = {};

	this.colleaguesList = [];
	this.businessUsersList = null;

	this.searchMinTokenLength = BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	this.messageCount = 0;
	this.messageCountArray = {};

	this.listRequestAfterErrorInterval = 10000;
	this.updateCounterInterval = 1000;

	this.loadingFlag = true;

	this.cache.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId);

	this.dialogCache = new ChatDialogCache();

	if (!Application.storage.getObject('settings.chat', {vueChat: true}).vueChat)
	{
		this.dialogCache.setDatabase(this.cache.database);
		this.dialogCache.getStore();
	}

	ChatDataConverter.init({
		'userId': this.userId,
		'generalChatId': this.generalChatId,
		'listType': this.isRecent()? 'recent': 'lines',
		'updateRuntimeDataFunction': this.updateRuntimeDataElement.bind(this)
	});

	/* events */

	BX.addCustomEvent("failRestoreConnection", () =>
	{
		BX.onViewLoaded(this.refresh.bind(this));
	});

	/* start */
	/**
	 * Push object must be initiated before View will be loaded
	 * to avoid race-condition bug in method "actionExecute" (see code below)
	 */
	this.push.init();

	BX.onViewLoaded(() =>
	{
		this.dialogOptionInit();
		this.action.init();
		this.event.init();
		this.search.init();

		if (this.isRecent())
		{
			this.notify.init();
		}

		this.notifier.init();
		this.queue.init();
		this.cache.init();
		this.pull.init();

		this.refresh({start: true});
	});

	BX.addCustomEvent("onImDetailShowed", (data) =>
	{
		this.updateElement(data.dialogId, {
			counter: 0
		});

		if (Application.getApiVersion() >= 25 && Application.isWebComponentSupported())
		{
			return true;
		}

		let params = this.getOpenDialogParams(data.dialogId);
		params.logAction = "onImDetailShowed";

		if (this.isRecent())
		{
			if (params.type == 'chat' && params.chat.type == 'lines' && this.isOpenlinesOperator())
			{
				return false;
			}
		}
		else if (this.isOpenlinesRecent())
		{
			if (params.type != 'chat' || params.chat.type != 'lines')
			{
				return false;
			}
		}

		BX.postWebEvent("onPageParamsChangedLegacy", {
			"url" : "/mobile/im/dialog.php",
			"data" : params
		});
	});

	BX.addCustomEvent("onAppActiveBefore", () =>
	{
		BX.onViewLoaded(() => {
			if (this.cache.inited)
			{
				this.push.updateList();
				this.refresh();
			}
		});
	});

	BX.addCustomEvent("onAppActive", () => {
		this.push.actionExecute();
	});

	return true;
};

RecentList.isDialogOpen = function()
{
	return PageManager.getNavigator().getAll().length > 1
};

RecentList.openDialog = function(dialogId, dialogTitleParams, waitHistory)
{
	/*
	clearTimeout(this.openDialogTimeout);
	if (waitHistory === true)
	{
		let history = this.push.manager.get();
		if (typeof history != "undefined" && Object.keys(history).length > 0)
		{
			this.openDialogTimeout = setTimeout(() =>{
				this.openDialog(dialogId, dialogTitleParams, true);
			}, 150);

			return true;
		}
	}
	*/

	let titleParams = {};

	let element = this.getElement(dialogId, true);

	if (element)
	{
		titleParams = {
			text: element.title,
			imageUrl: encodeURI(element.avatar.url),
			useLetterImage:true,
			imageColor:element.avatar.color,
			detailText: element.type == 'user'? ChatMessengerCommon.getUserPosition(element.user): ChatMessengerCommon.getChatDescription(element.chat),
			callback: -1
		}
	}
	else if (dialogTitleParams)
	{
		titleParams = {
			text: dialogTitleParams.name,
			imageUrl: dialogTitleParams.avatar,
			useLetterImage:true,
			detailText: dialogTitleParams.description,
			callback: -1
		}
	}
	else
	{
		titleParams = {
			text: BX.message('IM_DIALOG_UNNAMED'),
			callback: -1
		};
	}

	if (Application.getApiVersion() >= 25 && Application.isWebComponentSupported())
	{
		let page = PageManager.getNavigator().getVisible();
		let withAnimation = true;
		if (page.type === 'Web' && page.pageId === 'im-'+dialogId)
		{
			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}
			return false;
		}
		else if (Application.getApiVersion() === 25)
		{
			let pageIsOpened = false;
			PageManager.getNavigator().getAll().forEach(page =>
			{
				if (pageIsOpened)
					return true;

				if (page.type == 'Web' && page.pageId == 'im-'+dialogId)
				{
					pageIsOpened = true;
					PageManager.getNavigator().toPageByCode(page.uniqueCode);
					if (!PageManager.getNavigator().isActiveTab())
					{
						PageManager.getNavigator().makeTabActive();
					}
				}
			});
			if (pageIsOpened)
			{
				return true;
			}
		}
		else if (Application.getApiVersion() >= 30)
		{
			if (page.type === 'Web' && page.pageId.toString().startsWith('im-'))
			{
				withAnimation = false;
			}
			PageManager.getNavigator().cleanUpToCurrent();
		}
		else
		{
			let result = PageManager.getNavigator().toPageByID('im-'+dialogId);
			if (result)
			{
				if (!PageManager.getNavigator().isActiveTab())
				{
					PageManager.getNavigator().makeTabActive();
				}
				return true;
			}
		}

		let pageParams = {};

		let mobileConfig = Application.storage.getObject('settings.chat', {
			vueChat: true,
			quoteEnable: ChatPerformance.isGestureQuoteSupported(),
			quoteFromRight: Application.getApiVersion() < 31,
			backgroundType: 'LIGHT_GRAY'
		});

		let element = this.getElement(dialogId, true);
		if (
			!RecentList.isOpenlinesRecent()
			&& Application.getApiVersion() >= 29
			&& mobileConfig.vueChat === true
			&& (
				/^[0-9]+$/.test(dialogId) // digit
				|| !element
				|| (element.chat.type !== 'lines' || typeof element.lines === 'undefined')
			)
		)
		{
			if (!ChatDialogBackground[mobileConfig.backgroundType])
			{
				mobileConfig.backgroundType = 'LIGHT_GRAY';
			}

			let backgroundConfig = Object.assign({}, ChatDialogBackground[mobileConfig.backgroundType]);
			backgroundConfig.url = currentDomain+backgroundConfig.url;

			pageParams = {
				page_id: 'im-'+dialogId,
				data : this.getOpenDialogParams(dialogId, true, true),
				url : "/mobile/web_mobile_component/im.dialog.vue/?version="+BX.componentParameters.get('COMPONENT_CHAT_DIALOG_VUE_VERSION', '1.0.0'),
				titleParams: titleParams,
				animated: withAnimation,
				useSystemSwipeBehavior: mobileConfig.quoteEnable && !mobileConfig.quoteFromRight,
				textPanelParams:
				{
					smileButton: {},
					attachButton: {},
					useImageButton: true,
					useAudioMessages: true,
					placeholder: BX.message('IM_M_TEXTAREA'),
					mentionDataSource:
					{
						outsection: "NO",
						url: env.siteDir+"/mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots"
					}
				},
				background: backgroundConfig
			};
			console.warn(pageParams);
		}
		else
		{
			pageParams = {
				page_id: 'im-'+dialogId,
				data : this.getOpenDialogParams(dialogId, true),
				url : "/mobile/web_mobile_component/im.dialog/?version="+BX.componentParameters.get('COMPONENT_CHAT_DIALOG_VERSION', '1.0.0'),
				animated: withAnimation,
				titleParams: titleParams,
				textPanelParams:
				{
					smileButton: {},
					attachButton:{},
					useImageButton:true,
					placeholder: BX.message('IM_M_TEXTAREA'),
					mentionDataSource: {
						outsection: "NO",
						url: env.siteDir+"/mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots"
					}
				},
			};
		}

		PageManager.openWebComponent(pageParams);
	}
	else
	{
		let dialogParams = this.getOpenDialogParams(dialogId);
		let pageParams = {
			data : dialogParams,
			unique : true,
			url : env.siteDir+"mobile/im/dialog.php"
		};
		BX.postWebEvent("onPageParamsChangedLegacy", {
			"url" : env.siteDir+"mobile/im/dialog.php",
			"data" : dialogParams
		});
		PageManager.openPage(pageParams);
	}

	return true;
};

RecentList.getOpenDialogParams = function(dialogId, modern, push)
{
	modern = modern === true;
	push = push === true;

	if (modern)
	{
		return {
			PAGE_ID: 'im-'+dialogId,

			DIALOG_ID : dialogId,
			USER_ID : this.userId,
			SITE_ID : this.siteId,
			SITE_DIR : env.siteDir,
			LANGUAGE_ID : this.languageId,
			STORED_EVENTS : push? this.pull.getStoredEvents(): [],

			SEARCH_MIN_TOKEN_SIZE : this.searchMinTokenLength,
			WIDGET_CHAT_USERS_VERSION : BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
			WIDGET_CHAT_RECIPIENTS_VERSION : BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
			WIDGET_CHAT_TRANSFER_VERSION : BX.componentParameters.get('WIDGET_CHAT_TRANSFER_VERSION', '1.0.0'),

			WIDGET_BACKDROP_MENU_VERSION : BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'),
		};
	}
	else
	{
		let chatData = null;
		let userData = null;

		let element = this.getElement(dialogId, true);
		if (element)
		{
			if (element.type == 'user')
			{
				userData = JSON.stringify(element.user);
			}
			else if (element.type == 'chat')
			{
				chatData =  JSON.stringify(element.chat);
				userData = JSON.stringify(element.user);
			}
		}

		return {
			dialogId : dialogId,
			userId : this.userId,
			type : element.type,
			chat : chatData,
			user : userData,
			messageHistory : null,
		};
	}
};

RecentList.openUserProfile = function(userId, userData = {})
{
	let element = this.getElement(userId, true);
	let params = {};
	if (element)
	{
		params = {
			userId: element.user.id,
			imageUrl: ChatUtils.getAvatar(element.user.avatar),
			title: element.user.name,
			workPosition: element.user.work_position,
			name: element.user.name,
			url: currentDomain+'/mobile/users/?user_id='+userId+'&FROM_DIALOG=Y',
		};
	}
	else
	{
		params = {
			userId,
			imageUrl: ChatUtils.getAvatar(userData.avatar),
			title: userData.name,
			workPosition: userData.work_position,
			name: userData.name,
			url: currentDomain+'/mobile/users/?user_id='+userId+'&FROM_DIALOG=Y',
		};
	}
	ProfileView.open(params);
};

RecentList.callUser = function(userId, action, number)
{
	let element = this.getElement(userId);
	if (!element)
	{
		return false;
	}

	let userData = {};
	userData[this.userId] = this.userData;
	userData[element.id] = element.user;

	if (typeof action == 'undefined')
	{
		// TODO context menu calls
		BX.postComponentEvent("onCallInvite", [{userId: element.id, video: false, userData: userData}], "calls");
	}
	else
	{
		if (action == 'video')
		{
			BX.postComponentEvent("onCallInvite", [{userId: element.id, video: true, userData: userData}], "calls");
		}
		else if (action == 'phone')
		{
			BX.postComponentEvent("onPhoneTo", [{number: number, userData: userData}], "calls");
		}
		else
		{
			BX.postComponentEvent("onCallInvite", [{userId: element.id, video: false, userData: userData}], "calls");
		}
	}
};

RecentList.updateCounter = function(delay)
{
	if (delay !== false)
	{
		if (!this.updateCounterTimeout)
		{
			this.updateCounterTimeout = setTimeout(() => this.updateCounter(false), this.updateCounterInterval);
		}
		return true;
	}
	clearTimeout(this.updateCounterTimeout);
	this.updateCounterTimeout = null;

	this.messageCount = 0;
	this.messageGroupCount = [];
	for (let entityId in this.messageCountArray)
	{
		if (this.messageCountArray.hasOwnProperty(entityId))
		{
			if (this.messageGroupCount.indexOf(entityId) == -1 && this.messageCountArray[entityId] > 0)
			{
				this.messageGroupCount.push(entityId);
			}
			this.messageCount += this.messageCountArray[entityId];
		}
	}

	if (this.isRecent())
	{
		BX.postComponentEvent("onUpdateBadges", [{
			'messages' : this.messageCount,
			'notifications' : this.notify.counter
		}, true], "communication");
	}
	else
	{
		BX.postComponentEvent("onUpdateBadges", [{
			'openlines' : this.messageCount,
		}, true], "communication");
	}
};

RecentList.isRecent = function()
{
	return BX.componentParameters.get('COMPONENT_CODE') == "im.recent";
};

RecentList.isOpenlinesRecent = function()
{
	return BX.componentParameters.get('COMPONENT_CODE') == "im.openlines.recent";
};

RecentList.isOpenlinesOperator = function()
{
	return BX.componentParameters.get('OPENLINES_USER_IS_OPERATOR', false);
};

RecentList.cleaner = function()
{
	BX.listeners = {};

	this.queue.destroy();
	this.closeEmptyScreen();

	console.warn('RecentList.cleaner: OK');
};

RecentList.checkRevision = function(newRevision)
{
	if (typeof(newRevision) != "number" || REVISION >= newRevision)
	{
		return true;
	}

	console.warn('RecentList.checkRevision: reload scripts because revision up ('+REVISION+' -> '+newRevision+')');
	reloadAllScripts();

	return false;
};

RecentList.dialogOptionInit = function()
{
	if (this.isRecent())
	{
		dialogList.setSections([
			{title : '', id : "pinned", backgroundColor: "#ffffff", sortItemParams:{order: "desc"}},
			{title : '', id : "general", backgroundColor: "#ffffff", sortItemParams:{order: "desc"}}
		]);
	}
	else
	{
		dialogList.setSections([
			{title : '', id : "general", backgroundColor: "#ffffff", sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_PIN"), id : "pinned", backgroundColor: "#f6f6f6", sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_WORK"), id : "work", backgroundColor: "#ffffff", styles : { title: {color:"#e66467"}}, sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_ANSWERED"), id : "answered", backgroundColor: "#ffffff", styles: { title : {color:"#6EA44E"}}, sortItemParams:{order: "desc"}}
		]);
	}

	return true;
};

RecentList.refresh = function(params)
{
	params = params || {start: false};

	clearTimeout(this.refreshTimeout);

	let recentParams = {};
	if (this.isRecent())
	{
		if (this.isOpenlinesOperator())
		{
			recentParams['SKIP_OPENLINES'] = 'Y';
		}
	}
	else if (this.isOpenlinesRecent())
	{
		if (this.isOpenlinesOperator())
		{
			recentParams['SKIP_CHAT'] = 'Y';
			recentParams['SKIP_DIALOG'] = 'Y';
		}
	}

	let sendRecentLimit = false;
	if (this.lastRecentRequest)
	{
		sendRecentLimit = true;
		recentParams['LAST_UPDATE'] = this.lastRecentRequest;
	}

	ChatRestRequest.abort('refresh');
	console.info("RecentList.refresh: send request to server", recentParams);

	let requestMethods = {
		serverTime: ['server.time'],
		revision: ['im.revision.get'],
		recent: ['im.recent.get', recentParams],
		counters: ['im.counters.get', {}],
	};

	if (params.start)
	{
		requestMethods.userData = ['im.user.get'];
		requestMethods.businessUsers = ['im.user.business.get', {'USER_DATA': 'Y'}];
	}

	if (this.isRecent())
	{
		requestMethods.userCounters = ['user.counters'];
		if (params.start)
		{
			requestMethods.lastSearch = ['im.search.last.get'];
			requestMethods.colleagues = ['im.department.colleagues.get', {'USER_DATA': 'Y', 'LIMIT': 50}];
		}
	}

	ChatTimer.start('recent', 'load', 3000, () => {
		this.loadingFlag = true;
		dialogList.setTitle({text: BX.message('IM_REFRESH_TITLE'), useProgress:true});
		console.warn("RecentList.refresh: slow connection show progress icon");
	});

	let executeTime = new Date();
	BX.rest.callBatch(requestMethods, (result) =>
	{
		ChatRestRequest.unregister('refresh');
		ChatTimer.stop('recent', 'load', true);

		if (this.loadingFlag)
		{
			dialogList.setTitle({text: BX.message('COMPONENT_TITLE'), useProgress:false});
			this.loadingFlag = false;
		}

		let revisionError = result.revision.error();
		let serverTimeError = result.serverTime.error();
		let recentError = result.recent.error();
		let countersError = result.counters.error();
		let userDataError = params.start? result.userData.error(): false;
		let userCountersError = this.isRecent()? result.userCounters.error(): false;
		let lastSearchError = this.isRecent() && params.start? result.lastSearch.error(): false;
		let colleaguesError = this.isRecent() && params.start? result.colleagues.error(): false;
		let businessUsersError = params.start? result.businessUsers.error(): false;

		// revision block
		if (result.revision && !revisionError)
		{
			let data = result.revision.data();

			if (!this.checkRevision(data.mobile))
			{
				return true;
			}
		}

		// recent block
		if (result.recent && !recentError)
		{
			this.lastRecentRequest = result.recent.time().date_start;

			console.info("RecentList.update: recent list", result.recent.data());

			if (sendRecentLimit)
			{
				let recentList = ChatDataConverter.getListFormat(result.recent.data());
				if (recentList.length > 0)
				{
					let recentIndex = recentList.map(element => element.id);
					this.list = this.list.filter(element => !recentIndex.includes(element.id)).concat(recentList);
					this.redraw();
				}
				else
				{
					dialogList.stopRefreshing();
				}
			}
			else
			{
				this.list = ChatDataConverter.getListFormat(result.recent.data());
				this.redraw();
			}
		}
		// counters block
		if (result.counters && !countersError)
		{
			let counters = result.counters.data();
			if (this.isRecent())
			{
				this.messageCount = counters['TYPE']['DIALOG']+counters['TYPE']['CHAT'];
				this.messageCountArray = {};
				for (let i in counters["DIALOG"])
				{
					if (counters["DIALOG"].hasOwnProperty(i))
					{
						this.messageCountArray[i] = counters["DIALOG"][i];
					}
				}
				for (let i in counters["CHAT"])
				{
					if (counters["CHAT"].hasOwnProperty(i))
					{
						this.messageCountArray['chat'+i] = counters["CHAT"][i];
					}
				}

				if (!this.isOpenlinesOperator())
				{
					this.messageCount += counters['TYPE']['LINES'];
					for (let i in counters["LINES"])
					{
						if (counters["LINES"].hasOwnProperty(i))
						{
							this.messageCountArray['chat'+i] = counters["LINES"][i];
						}
					}
				}
			}
			else
			{
				this.messageCount = counters['TYPE']['LINES'];
				this.messageCountArray = {};
				for (let i in counters["LINES"])
				{
					if (counters["LINES"].hasOwnProperty(i))
					{
						this.messageCountArray['chat'+i] = counters["LINES"][i];
					}
				}
			}

			this.notify.counter = counters['TYPE']['NOTIFY'];

			this.notify.refresh();
			this.updateCounter(false);
		}

		// userData block
		if (result.userData && !userDataError)
		{
			this.userData = ChatDataConverter.getUserDataFormat(result.userData.data());
		}

		// last search block
		if (this.isRecent() && result.lastSearch && !lastSearchError)
		{
			console.info("RecentList.refresh: update last search", result.lastSearch.data());

			this.search.lastSearchList = ChatDataConverter.getListFormat(result.lastSearch.data());
		}

		// colleagues list block
		if (this.isRecent() && result.colleagues && !colleaguesError)
		{
			console.info("RecentList.refresh: update colleagues list", result.colleagues.data());

			this.colleaguesList = ChatDataConverter.getUserListFormat(result.colleagues.data());
		}

		// colleagues list block
		if (result.businessUsers && !businessUsersError)
		{
			console.info("RecentList.refresh: update business users list", result.businessUsers.data());

			let businessUsersData = result.businessUsers.data();
			this.businessUsersList = businessUsersData? ChatDataConverter.getUserListFormat(result.businessUsers.data()): false;
		}

		if (!recentError || !userDataError || !lastSearchError || !colleaguesError || !businessUsersError)
		{
			this.cache.update({recent: true, colleagues: this.isRecent() && params.start, businessUsers: this.isRecent() && params.start, lastSearch: this.isRecent()});
		}

		// general actions for all modules
		// userCounters block
		if (this.isRecent() && result.userCounters && !userCountersError)
		{
			BX.postComponentEvent("onSetUserCounters", [result.userCounters.data(), result.userCounters.time? result.userCounters.time(): null], "communication");
		}

		// serverTime block
		if (result.serverTime && !serverTimeError)
		{
			BX.postComponentEvent("onUpdateServerTime", [result.serverTime.data()], "communication");
		}

		console.info("RecentList.refresh: receive answer from server and update variables ("+(new Date() - executeTime)+'ms)', result);

		if (revisionError || recentError || countersError || userCountersError || userDataError)
		{
			let error = null;
			if (revisionError)
			{
				error = revisionError;
			}
			else if (recentError)
			{
				error = recentError;
			}
			else if (countersError)
			{
				error = countersError;
			}
			else if (userCountersError)
			{
				error = userCountersError;
			}
			else if (userDataError)
			{
				error = userDataError;
			}

			if (error)
			{
				if (error.ex.error == 'REQUEST_CANCELED')
				{
					console.error("RecentList.refresh: execute request canceled by user", error.ex);
				}
				else
				{
					this.loadingFlag = true;
					dialogList.setTitle({text: BX.message('IM_REFRESH_TITLE'), useProgress:true});

					console.error("RecentList.refresh: we have some problems with request, we will be check again soon", error.ex);

					clearTimeout(this.refreshTimeout);
					this.refreshTimeout = setTimeout(() =>
					{
						if (!this.errorNoticeFlag && this.isRecent())
						{
							ChatTimer.start('recent', 'error', 2000, () => {

								this.errorNoticeFlag = true;

								InAppNotifier.showNotification({
									message: BX.message('IM_REFRESH_ERROR'),
									backgroundColor: '#E6000000',
									time: this.listRequestAfterErrorInterval/1000-2
								});
							});
						}
						this.refresh();

					}, this.listRequestAfterErrorInterval);
				}
			}
			else
			{
				this.errorNoticeFlag = false;
				ChatTimer.stop('recent', 'error', true);
			}
		}
		else
		{
			this.errorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);
		}

		dialogList.stopRefreshing();
	}, false, (xhr) => {
		ChatRestRequest.register('refresh', xhr);
	});

	return true;
};

RecentList.redraw = function()
{
	this.queue.clear();

	let listConverted = [];
	this.list.forEach((element) => {
		listConverted.push(ChatDataConverter.getElementFormat(element));
		this.messageCountArray[element.id] = element.counter;
	});

	if (listConverted.length <= 0)
	{
		listConverted = this.getEmptyElement();
		this.listEmpty = true;
		this.openEmptyScreen();
	}
	else if (this.listEmpty)
	{
		this.listEmpty = false;
		dialogList.removeItem({"params.id" : "empty"});
		this.closeEmptyScreen();
	}

	dialogList.setItems(listConverted);

	dialogList.stopRefreshing();

	return true;
};

RecentList.openEmptyScreen = function()
{
	if (Application.getApiVersion() < 23)
		return false;

	let params = {};
	if (this.isRecent())
	{
		params = {
			"upperText": BX.message('IM_EMPTY_TEXT_1'),
			"lowerText": Application.getPlatform() == "ios"? BX.message('IM_EMPTY_TEXT_APPLE'): BX.message('IM_EMPTY_TEXT_ANDROID'),
			"iconName":"ws_chat",
			"listener": () => this.chatCreate.open()
		};
		if (Application.getPlatform() == "ios")
		{
			params["startChatButton"] = {
				"text": BX.message('IM_EMPTY_BUTTON')
			};
		}
	}
	else
	{
		params = {
			"upperText": BX.message('IM_EMPTY_OL_TEXT_1'),
			"lowerText": BX.message('IM_EMPTY_OL_TEXT_2'),
			"iconName":"ws_openlines",
		};
	}

	dialogList.welcomeScreen.show(params);

	return true;
};

RecentList.closeEmptyScreen = function()
{
	if (Application.getApiVersion() < 23)
		return false;

	dialogList.welcomeScreen.hide();

	return true;
};

RecentList.getEmptyElement = function()
{
	let list = [];
	if (this.isRecent())
	{
		list.push({
			title : BX.message('IM_LIST_EMPTY'),
			type : "button",
			sectionCode: 'general',
			params: { id: "empty", type: 'openSearch'},
		});
	}
	else
	{
		list.push({
			title : BX.message('OL_LIST_EMPTY'),
			type : "button",
			sectionCode: 'general',
			params: { id: "empty", type: 'openSearch'},
			unselectable : true
		});
	}

	return list;
};

RecentList.getElement = function(elementId, clone)
{
	let index = this.list.findIndex((listElement) => listElement && listElement.id == elementId);
	if (index == -1)
	{
		return false;
	}

	return clone === true? ChatUtils.objectClone(this.list[index]): this.list[index];
};

RecentList.getElementByMessageId = function(messageId, clone)
{
	let index = this.list.findIndex((listElement) => listElement && listElement.message.id == messageId);
	if (index == -1)
	{
		return false;
	}

	return clone === true? ChatUtils.objectClone(this.list[index]): this.list[index];
};

RecentList.setElement = function(elementId, data, immediately)
{
	if (!data)
	{
		return false;
	}

	immediately = immediately === true;

	this.unblockElement(elementId);

	let index = this.list.findIndex((listElement) => listElement && listElement.id == elementId);
	if (index == -1)
	{
		elementId = data.id;

		this.list.push(data);
		this.messageCountArray[elementId] = data.counter;

		this.queue.add(this.queue.TYPE_ADD, elementId, data);
	}
	else
	{
		elementId = this.list[index].id;
		this.list[index] = data;
		this.messageCountArray[elementId] = data.counter;

		this.queue.add(this.queue.TYPE_UPDATE, elementId, data);
	}

	if (immediately)
	{
		this.queue.worker();
	}

	return true;
};

RecentList.updateElement = function(elementId, data, immediately)
{
	if (!data)
	{
		return false;
	}

	let index = this.list.findIndex((listElement) => listElement && listElement.id == elementId);
	if (index == -1)
	{
		return false;
	}

	immediately = immediately === true;

	this.unblockElement(elementId);

	if (!ChatUtils.isObjectChanged(this.list[index], data))
	{
		return true;
	}

	elementId = this.list[index].id;

	this.list[index] = ChatUtils.objectMerge(this.list[index], data);
	this.messageCountArray[elementId] = this.list[index].counter;

	this.queue.add(this.queue.TYPE_UPDATE, elementId, this.list[index]);

	if (immediately)
	{
		this.queue.worker();
	}

	return true;
};

RecentList.deleteElement = function(elementId)
{
	let index = this.list.findIndex((listElement) => listElement && listElement.id == elementId);
	if (index == -1)
	{
		return false;
	}

	this.unblockElement(elementId);

	elementId = this.list[index].id;

	delete this.list[index];
	delete this.messageCountArray[elementId];
	ChatTimer.delete(elementId);

	this.updateCounter(false);
	this.cache.update({recent: true});

	this.queue.delete(this.queue.TYPE_ALL, elementId);
	dialogList.removeItem({"params.id" : elementId});

	this.listEmpty = true;
	for (let element of this.list)
	{
		if (typeof element != 'undefined')
		{
			this.listEmpty = false;
			break;
		}
	}
	if (this.listEmpty)
	{
		dialogList.setItems(this.getEmptyElement());
		this.openEmptyScreen();
	}

	return true;
};

RecentList.blockElement = function(elementId, action, autoUnblockCallback, autoUnblockCallbackParams)
{
	this.blocked[elementId] = true;

	autoUnblockCallbackParams = typeof autoUnblockCallbackParams == 'undefined'? {}: autoUnblockCallbackParams;
	autoUnblockCallbackParams.__callback = typeof autoUnblockCallback == 'function'? autoUnblockCallback: () => {};

	ChatTimer.start('block', elementId, 30000, (id, params) => {
		this.unblockElement(id, false);
		params.__callback(id, params);
	}, autoUnblockCallbackParams);

	return true;
};

RecentList.unblockElement = function(elementId, runCallback)
{
	delete this.blocked[elementId];

	let skipCallback = runCallback !== true;
	ChatTimer.stop('block', elementId, skipCallback);

	return true;
};

RecentList.isElementBlocked = function (elementId)
{
	return this.blocked[elementId] === true;
};

RecentList.updateRuntimeDataElement = function(element)
{
	let status = ChatDataConverter.getUserImageCode(element);

	let updateRuntime = typeof element.runtime == 'undefined' || element.runtime.status != status;
	if (!updateRuntime)
	{
		return true;
	}

	let index = this.list.findIndex((listElement) => listElement && listElement.id == element.id);
	if (index == -1)
	{
		return false;
	}

	if (typeof element.runtime == 'undefined')
	{
		element.runtime = {};
		this.list[index].runtime = {};
	}

	if (element.runtime.status != status)
	{
		element.runtime.status = status;
		this.list[index].runtime.status = status;
	}

	return true;
};

RecentList.capturePullEvent = function (status)
{
	if (typeof(status) == 'undefined')
	{
		status = !this.debugLog;
	}

	console.info('RecentList.capturePullEvent: capture "Pull Event" '+(status? 'enabled': 'disabled'));
	this.debugLog = !!status;

	BX.componentParameters.set('PULL_DEBUG', this.debugLog)
};



/* Cache API */
RecentList.cache = {
	updateTimeout: '',
	updateInterval: 2000,
	database: {},
	inited: false,
};

RecentList.cache.init = function ()
{
	let executeTimeRecent = new Date();

	if (this.base.isRecent())
	{
		this.database.table(ChatTables.recent).then(table =>
		{
			table.get().then(items =>
			{
				this.inited = true;

				if (items.length > 0)
				{
					let cacheData = JSON.parse(items[0].VALUE);

					if (this.base.list.length > 0)
					{
						console.info("RecentList.cache.init: cache file \"recent\" has been ignored because it was loaded a very late");
						this.push.updateList();
						this.push.actionExecute();
						this.base.redraw();

						return false
					}

					if (typeof(cacheData.list) == 'undefined')
					{
						console.info("RecentList.cache.init: cache file \"recent\" has been ignored because it's old");
						this.push.updateList();
						this.push.actionExecute();
						this.base.redraw();
						return false;
					}

					this.base.list = ChatDataConverter.getListFormat(cacheData.list);
					this.push.updateList();
					this.push.actionExecute();
					this.base.redraw();

					console.info("RecentList.cache.init: list items load from cache \"recent\" ("+(new Date() - executeTimeRecent)+'ms)', "count: "+this.base.list.length);

					if (cacheData.userData)
					{
						this.base.userData = cacheData.userData;
					}
				}
				else
				{
					this.push.updateList();
					this.push.actionExecute();
					this.base.redraw();
				}
			})
		});
	}
	else
	{
		this.base.redraw();
	}

	let executeTimeLastSearch = new Date();
	this.database.table(ChatTables.lastSearch).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				if (this.search.lastSearchList.length > 0)
				{
					console.info("RecentList.cache.init: cache file \"last search\" has been ignored because it was loaded a very late");
					return false
				}

				this.search.lastSearchList = ChatDataConverter.getListFormat(cacheData.recent);

				console.info("RecentList.cache.init: list items load from cache \"last search\" ("+(new Date() - executeTimeLastSearch)+'ms)', "count: "+this.search.lastSearchList.length);
			}
		})
	});

	let executeTimeColleaguesList = new Date();
	this.database.table(ChatTables.colleaguesList).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				if (this.base.colleaguesList.length > 0)
				{
					console.info("RecentList.cache.init: cache file \"colleagues list\" has been ignored because it was loaded a very late");
					return false
				}

				this.base.colleaguesList = ChatDataConverter.getUserListFormat(cacheData.colleaguesList);

				console.info("RecentList.cache.init: list items load from cache \"colleagues list\" ("+(new Date() - executeTimeColleaguesList)+'ms)', "count: "+this.base.colleaguesList.length);
			}
		})
	});

	let executeTimeBusinessUsersList = new Date();
	this.database.table(ChatTables.businessUsersList).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				if (this.base.businessUsersList !== null)
				{
					console.info("RecentList.cache.init: cache file \"business users list\" has been ignored because it was loaded a very late");
					return false
				}

				this.base.businessUsersList = cacheData.businessUsersList !== false? ChatDataConverter.getUserListFormat(cacheData.businessUsersList): false;

				console.info("RecentList.cache.init: list items load from cache \"business users list\" ("+(new Date() - executeTimeBusinessUsersList)+'ms)', this.base.businessUsersList !== false? "count: "+this.base.businessUsersList.length: "not available");
			}
		})
	});

	return true;
};

RecentList.cache.update = function (params)
{
	if (!this.base.isRecent())
	{
		return true;
	}

	params = params || {recent: true, lastSearch: true, colleagues: false, businessUsers: false};

	clearTimeout(this.refreshTimeout);
	this.refreshTimeout = setTimeout(() =>
	{
		let executeTimeRecent = new Date();
		let executeTimeLastSearch = new Date();
		let executeTimeColleagues = new Date();
		let executeTimeBusinessUsers = new Date();

		if (params.recent)
		{
			this.database.table(ChatTables.recent).then(table => {
				table.delete().then(() =>
				{
					table.add({value : {
						list: this.base.list,
						userData: this.base.userData
					}}).then(() =>
					{
						console.info("RecentList.cache.update: recent list items updated ("+(new Date() - executeTimeRecent)+'ms)', "count: "+this.base.list.length);
					});
				})
			});
		}

		if (params.colleagues)
		{
			this.database.table(ChatTables.colleaguesList).then(table =>
			{
				table.delete().then(() =>
				{
					table.add({value : {colleaguesList: this.base.colleaguesList}}).then(() =>
					{
						console.info("RecentList.cache.update: colleagues list items updated ("+(new Date() - executeTimeColleagues)+'ms)', "count: "+this.base.colleaguesList.length);
					});
				})
			});
		}

		if (params.businessUsers)
		{
			this.database.table(ChatTables.businessUsersList).then(table =>
			{
				table.delete().then(() =>
				{
					table.add({value : {businessUsersList: this.base.businessUsersList}}).then(() =>
					{
						console.info("RecentList.cache.update: business users list items updated ("+(new Date() - executeTimeBusinessUsers)+'ms)', this.base.businessUsersList? "count: "+this.base.businessUsersList.length: "not available");
					});
				})
			});
		}

		if (params.lastSearch)
		{
			this.database.table(ChatTables.lastSearch).then(table =>
			{
				table.delete().then(() =>
				{
					table.add({value : {recent: this.search.lastSearchList}}).then(() =>
					{
						console.info("RecentList.cache.update: last search items updated ("+(new Date() - executeTimeLastSearch)+'ms)', "count: "+this.search.lastSearchList.length);
					});
				})
			});
		}
	}, this.updateInterval);

	return true;
};


/* Push & Pull API */
RecentList.push = {};

RecentList.push.init = function()
{
	if (this.base.isRecent())
	{
		this.manager = Application.getNotificationHistory("im_message");
	}
	else
	{
		this.manager = Application.getNotificationHistory("im_lines_message");
	}

	this.manager.setOnChangeListener(() => {
		BX.onViewLoaded(() => {
			if (this.cache.inited)
			{
				this.push.updateList();
			}
		});
	});
};

RecentList.push.updateList = function()
{
	let list = this.manager.get();
	if (!list || !list['IM_MESS'] || list['IM_MESS'].length <= 0)
	{
		console.info('RecentList.push.updateList: list is empty');
		return true;
	}

	console.info('RecentList.push.updateList: parse push messages', list['IM_MESS']);

	let isDialogOpen = this.base.isDialogOpen();

	list['IM_MESS'].forEach((push) =>
	{
		if (!push.data)
		{
			return false;
		}

		if (!(push.data.cmd === 'message' || push.data.cmd === 'messageChat'))
		{
			return false;
		}

		let senderMessage = '';
		if (typeof push.senderMessage !== 'undefined')
		{
			senderMessage = push.senderMessage;
		}
		else if (typeof push.aps !== 'undefined' && push.aps.alert.body)
		{
			senderMessage = push.aps.alert.body;
		}

		if (!senderMessage)
		{
			return false;
		}

		let event = {
			module_id : 'im',
			command : push.data.cmd,
			params : ChatDataConverter.preparePushFormat(push.data)
		};

		event.params.userInChat[event.params.chatId] = [this.base.userId];

		event.params.message.text = senderMessage.toString();

		if (push.senderCut)
		{
			event.params.message.text = event.params.message.text.substr(push.senderCut)
		}

		if (!event.params.message.textOriginal)
		{
			event.params.message.textOriginal = event.params.message.text;
		}

		let storedEvent = ChatUtils.objectClone(event.params);
		if (storedEvent.message.params.FILE_ID && storedEvent.message.params.FILE_ID.length > 0)
		{
			storedEvent.message.text = '';
			storedEvent.message.textOriginal = '';
		}

		if (isDialogOpen)
		{
			BX.postWebEvent("chatrecent::push::get", storedEvent)
		}
		else
		{
			this.pull.storedEvents = this.pull.storedEvents.filter(element => element.message.id !== storedEvent.message.id);
			this.pull.storedEvents.push(storedEvent);
		}

		let element = this.base.list.find((element) => element && element.id.toString() === event.params.dialogId.toString());
		if (!element || element.message.id < event.params.message.id)
		{
			this.pull.eventExecute(event);
		}
	});

	this.manager.clear();

	return true;
};

RecentList.push.actionExecute = function()
{
	if (Application.isBackground())
		return false;

	let push = Application.getLastNotification();
	if (push === {})
	{
		return false;
	}

	console.info("RecentList.push.actionExecute: execute push-notification", push);
	let pushParams = ChatDataConverter.getPushFormat(push);
	if (pushParams.ACTION && pushParams.ACTION.substr(0, 8) === 'IM_MESS_')
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}

		let user = parseInt(pushParams.ACTION.substr(8));
		if (user > 0)
		{
			this.base.openDialog(user, null, true);
		}
	}
	else if (pushParams.ACTION && pushParams.ACTION.substr(0, 8) === 'IM_CHAT_')
	{
		if (this.base.isRecent())
		{
			if (this.base.isOpenlinesOperator() && pushParams.CHAT_TYPE === 'L')
			{
				return false;
			}
		}
		else
		{
			if (pushParams.CHAT_TYPE !== 'L')
			{
				return false;
			}
		}

		let chatId = parseInt(pushParams.ACTION.substr(8));
		if (chatId > 0)
		{
			this.base.openDialog('chat' + chatId, null, true);
		}
	}

	return true;
};


RecentList.pull = {};

RecentList.pull.init = function ()
{
	BX.PULL.subscribe({
		moduleId: 'im',
		callback: this.eventExecute.bind(this)
	});

	if (this.base.isRecent())
	{
		BX.PULL.subscribe({
			type: BX.PullClient.SubscriptionType.Online,
			callback: this.eventOnlineExecute.bind(this)
		});
	}
	else
	{
		BX.PULL.subscribe({
			moduleId: 'imopenlines',
			callback: this.eventLinesExecute.bind(this)
		});
	}

	this.storedEvents = [];
};

RecentList.pull.getStoredEvents = function()
{
	let list = [].concat(this.storedEvents);

	this.storedEvents = [];

	return list;
};

RecentList.pull.getUserDataFormat = function (user)
{
	user = ChatDataConverter.getUserDataFormat(user);

	if (user.id > 0)
	{
		if (typeof (user.name) != 'undefined')
		{
			user.name = ChatUtils.htmlspecialcharsback(user.name);
		}
		if (typeof (user.last_name) != 'undefined')
		{
			user.last_name = ChatUtils.htmlspecialcharsback(user.last_name);
		}
		if (typeof (user.first_name) != 'undefined')
		{
			user.first_name = ChatUtils.htmlspecialcharsback(user.first_name);
		}
		if (typeof (user.work_position) != 'undefined')
		{
			user.work_position = ChatUtils.htmlspecialcharsback(user.work_position);
		}
	}

	return user;
};

RecentList.pull.getFormattedElement = function(element)
{
	let newElement = {};
	let index = this.base.list.findIndex((listElement) => listElement && listElement.id == element.id);
	if (index > -1)
	{
		newElement = ChatUtils.objectClone(this.base.list[index]);
	}
	else
	{
		newElement = {
			avatar: {},
			user: {id: 0},
			message: {},
			counter: 0,
			blocked: false,
			writing: false,
		};
		if (element.id.toString().indexOf('chat') == 0)
		{
			newElement.type = 'chat';
			newElement.id = element.id;
			newElement.chat = {};
			if (typeof element.chat == 'undefined')
			{
				return false;
			}
		}
		else
		{
			newElement.type = 'user';
			newElement.id = parseInt(element.id);
			newElement.user = {};
			if (typeof element.user == 'undefined')
			{
				return false;
			}
		}
		if (typeof element.message == 'undefined')
		{
			return false;
		}
	}

	if (typeof element.message != 'undefined')
	{
		newElement.message.id = parseInt(element.message.id);
		newElement.message.text = element.message.text;
		newElement.message.author_id = element.message.senderId && element.message.system != 'Y'? element.message.senderId: 0;
		newElement.message.date = new Date(element.message.date);
		newElement.message.file = element.message.params && element.message.params.FILE_ID? element.message.params.FILE_ID.length > 0: false;
		newElement.message.attach = element.message.params && element.message.params.ATTACH? element.message.params.ATTACH.length > 0: false;
		newElement.message.status = element.message.status? element.message.status: '';
	}

	if (typeof element.counter != 'undefined')
	{
		newElement.counter = element.counter;
	}
	if (typeof element.writing != 'undefined')
	{
		newElement.writing = element.writing;
	}

	if (typeof element.user != 'undefined')
	{
		element.user.id = parseInt(element.user.id);
		if (element.user.id > 0)
		{
			newElement.user = element.user = this.getUserDataFormat(element.user);

			if (newElement.type == 'user')
			{
				newElement.avatar.url = element.user.avatar;
				newElement.avatar.color = element.user.color;
				newElement.title = element.user.name;
			}
		}
		else
		{
			newElement.user = element.user;
		}
	}

	if (newElement.type == 'chat' && typeof element.chat != 'undefined')
	{
		element.chat.id = parseInt(element.chat.id);
		element.chat.date_create = new Date(element.chat.date_create);
		newElement.chat = element.chat;

		newElement.avatar.url = element.chat.avatar;
		newElement.avatar.color = element.chat.color;
		newElement.title = element.chat.name;

		if (element.chat.type == 'lines' && element.lines != 'undefined')
		{
			if (typeof newElement.lines == 'undefined')
			{
				newElement.lines = {};
			}
			newElement.lines.id = parseInt(element.lines.id);
			newElement.lines.status = parseInt(element.lines.status);
		}
	}

	return newElement;
};

RecentList.pull.eventExecute = function(data)
{
	let {command, params, extra} = data;

	if (extra && (!this.base.checkRevision(extra.revision_im_mobile) || extra.server_time_ago > 30))
	{
		return true;
	}

	if (this.base.debugLog)
	{
		console.warn("RecentList.pull.eventExecute: receive \""+command+"\"", params);
	}

	if (command == 'message' || command == 'messageChat')
	{
		if (this.base.isRecent())
		{
			if (command == 'messageChat' && params.chat[params.chatId].type == 'lines' && this.base.isOpenlinesOperator())
			{
				return false;
			}

			if (!this.base.isDialogOpen() && !params.message.push)
			{
				let storedEvent = ChatUtils.objectClone(params);
				storedEvent.message.push = true;

				this.storedEvents = this.storedEvents.filter(element => element.message.id !== storedEvent.message.id);
				this.storedEvents.push(storedEvent);
			}
		}
		else
		{
			if (command == 'message')
			{
				return false;
			}
			else if (params.chat[params.chatId].type != 'lines')
			{
				return false;
			}
		}

		if (command == 'messageChat' && params.userInChat[params.chatId].indexOf(this.base.userId) == -1)
		{
			this.base.updateElement(params.userId, {
				user: { idle: false, last_activity_date: new Date()}
			});

			return false;
		}

		let messageOriginal = Object.assign({}, params.message);

		params.message.text = ChatMessengerCommon.purifyText(params.message.text, params.message.params);
		params.message.status = params.message.senderId == this.base.userId? 'received': '';

		if (command == 'message')
		{
			let recipientId = params.message.senderId == this.base.userId? params.message.recipientId: params.message.senderId;

			let formattedElement = this.getFormattedElement({
				id: recipientId,
				user: params.users[recipientId],
				message: params.message,
				counter: params.counter
			});

			let addToRecent = params.notify !== true && params.notify.indexOf(this.base.userId) == -1? this.base.getElement(recipientId): true;
			if (addToRecent)
			{
				this.base.setElement(recipientId, formattedElement);
			}
			this.action.writing(recipientId, false);

			this.base.dialogCache.getDialog(recipientId).then(dialog =>
			{
				dialog.unreadList.push(params.message.id);
				this.base.dialogCache.updateDialog(dialog.id, {unreadList: dialog.unreadList});
			});

			this.base.dialogCache.addMessage(recipientId, this.base.dialogCache.getMessageFormat({
				message: messageOriginal,
				files: params.files? params.files: {},
				users: params.users? params.users: {}
			}));

			if (extra && extra.server_time_ago <= 5 && params.message.senderId != this.base.userId)
			{
				this.notifier.show({
					dialogId: recipientId,
					title: formattedElement.user.name,
					text: formattedElement.message.text,
					avatar: formattedElement.user.avatar,
				});
			}
		}
		else if (command == 'messageChat')
		{
			let formattedElement = this.getFormattedElement({
				id: params.message.recipientId,
				chat: params.chat[params.chatId],
				user: params.message.senderId > 0? params.users[params.message.senderId]: {id: 0},
				lines: params.lines[params.chatId],
				message: params.message,
				counter: params.counter
			});

			let addToRecent = params.notify !== true && params.notify.indexOf(this.base.userId) == -1? this.base.getElement(params.message.recipientId): true;
			if (addToRecent)
			{
				this.base.setElement(params.message.recipientId, formattedElement);
			}

			this.action.writing(params.message.recipientId, false);

			this.base.updateElement(params.userId, {
				user: { idle: false, last_activity_date: new Date()}
			});

			this.base.dialogCache.getDialog(params.message.recipientId).then(dialog =>
			{
				dialog.unreadList.push(params.message.id);
				this.base.dialogCache.updateDialog(dialog.id, {unreadList: dialog.unreadList});
			});

			this.base.dialogCache.addMessage(params.message.recipientId, this.base.dialogCache.getMessageFormat({
				message: messageOriginal,
				files: params.files? params.files: {},
				users: params.users? params.users: {}
			}));

			if (
				extra && extra.server_time_ago <= 5
				&& formattedElement.chat.type != 'lines'
				&& params.message.senderId != this.base.userId
				&& !formattedElement.chat.mute_list[this.base.userId]
			)
			{
				this.notifier.show({
					dialogId: formattedElement.id,
					title: formattedElement.chat.name,
					text: (formattedElement.user.name? formattedElement.user.name+': ': '')+formattedElement.message.text,
					avatar: formattedElement.chat.avatar,
				});
			}
		}
	}
	else if (
		command == 'readMessageOpponent' || command == 'readMessageChatOpponent' ||
		command == 'unreadMessageOpponent' || command == 'unreadMessageChatOpponent'
	)
	{
		if (
			this.base.isOpenlinesRecent()
			&& (command == 'readMessageOpponent' || command == 'unreadMessageOpponent')
		)
		{
			return false;
		}

		let element = this.base.getElement(params.dialogId);
		if (!element)
		{
			return false;
		}

		if (
			params.chatMessageStatus
			&& params.chatMessageStatus != element.message.status
		)
		{
			this.base.updateElement(params.dialogId, {
				message: { status: params.chatMessageStatus},
			});
		}

		this.base.updateElement(params.userId, {
			user: { idle: false, last_activity_date: new Date(params.date)}
		});

		this.base.dialogCache.getDialog(params.dialogId).then(dialog =>
		{
			if (params.dialogId.toString().startsWith('chat'))
			{
				if (command == 'readMessageChatOpponent')
				{
					dialog.readList[params.userId] = {
						messageId: params.lastId,
						date: new Date(params.date)
					};
				}
				else
				{
					delete dialog.readList[params.userId];
				}
			}
			else
			{
				if (command == 'readMessageOpponent')
				{
					dialog.readList[params.dialogId] = {
						messageId: params.lastId,
						date: new Date(params.date)
					};
				}
				else
				{
					dialog.readList[params.dialogId] = {};
				}
			}

			this.base.dialogCache.updateDialog(params.dialogId, {readList: dialog.readList});
		});
	}
	else if (
		command == 'readMessage' || command == 'readMessageChat' ||
		command == 'unreadMessage' || command == 'unreadMessageChat'
	)
	{
		if (
			this.base.isOpenlinesRecent()
			&& (command == 'readMessage' || command == 'unreadMessage')
		)
		{
			return false;
		}

		this.base.updateElement(params.dialogId, {
			counter: params.counter
		});

		if (command == 'readMessage' || command == 'readMessageChat')
		{
			this.base.dialogCache.updateDialog(params.dialogId, {unreadList: []});
		}
	}
	else if (command == 'startWriting')
	{
		if (
			this.base.isOpenlinesRecent()
			&& (params.dialogId.toString().substr(0,4) != 'chat')
		)
		{
			return false;
		}
		this.action.writing(params.dialogId, true);
	}
	else if (
		command == 'messageUpdate'
		|| command == 'messageDelete'
		|| command == 'messageDeleteComplete'
	)
	{
		let element = this.base.getElementByMessageId(params.id, true);
		if (!element)
		{
			return false;
		}

		element.message.text = ChatMessengerCommon.purifyText(params.text, params.params);
		element.message.params = params.params;
		element.message.file = params.params && params.params.FILE_ID? params.params.FILE_ID.length > 0: false;
		element.message.attach = params.params && params.params.ATTACH? params.params.ATTACH.length > 0: false;

		this.base.updateElement(element.id, element);
		this.action.writing(element.id, false);

		if (command == 'messageDeleteComplete')
		{
			this.base.dialogCache.deleteMessage(params.dialogId, params.id);
		}
		else
		{
			this.base.dialogCache.updateMessage(params.dialogId, this.base.dialogCache.getUpdateMessageFormat({
				message: {id: params.id, text: params.text, params: params.params},
				hasFiles: element.message.file,
				hasAttach: element.message.attach
			}));
		}
	}
	else if (command == 'chatRename')
	{
		this.base.updateElement('chat'+params.chatId, {
			title: params.name,
			chat: { name: params.name}
		});

		this.search.updateElement('chat'+params.chatId, {
			title: params.name,
			chat: { name: params.name}
		});
	}
	else if (command == 'chatAvatar')
	{
		this.base.updateElement('chat'+params.chatId, {
			avatar: {url: params.avatar},
			chat: {avatar: params.avatar}
		});

		this.search.updateElement('chat'+params.chatId, {
			avatar: {url: params.avatar},
			chat: {avatar: params.avatar}
		});
	}
	else if (command == 'chatChangeColor')
	{
		this.base.updateElement('chat'+params.chatId, {
			avatar: {color: params.color},
			chat: {color: params.color}
		});
		this.search.updateElement('chat'+params.chatId, {
			avatar: {color: params.color},
			chat: {color: params.color}
		});
	}
	else if (command == 'chatUpdate')
	{
		let params = {};
		if (params.name == 'name')
		{
			params.title = params.value;
			params.chat = {};
			params.chat.name = params.value;
		}
		else if (params.name == 'color')
		{
			params.avatar = {};
			params.avatar.color = params.value;
			params.chat = {};
			params.chat.color = params.value;
		}
		else if (params.name == 'avatar')
		{
			params.avatar = {};
			params.avatar.url = params.value;
			params.chat = {};
			params.chat.avatar = params.value;
		}
		else if (params.name == 'date_create')
		{
			params.chat = {};
			params.chat.date_create = new Date(params.value);
		}

		this.base.updateElement('chat'+params.chatId, params);
		this.search.updateElement('chat'+params.chatId, params);
	}
	else if (command == 'updateUser' || command == 'updateBot')
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}
		this.base.updateElement(params.user.id, this.getFormattedElement({
			id: params.user.id,
			user: params.user,
		}));
		this.search.updateElement(params.user.id, this.getFormattedElement({
			id: params.user.id,
			user: params.user,
		}));
	}
	else if (command == 'chatMuteNotify')
	{
		let muteList = {};
		muteList[this.base.userId] = params.mute;

		this.base.updateElement(params.dialogId, {
			chat: { mute_list: muteList }
		});
		this.search.updateElement(params.dialogId, {
			chat: { mute_list: muteList }
		});
	}
	else if (command == 'chatHide')
	{
		this.base.deleteElement(params.dialogId);
	}
	else if (command == 'chatShow')
	{
		if (this.base.isRecent())
		{
			if (params.chat[params.chatId].type == 'lines' && this.base.isOpenlinesOperator())
			{
				return false;
			}
		}
		else if (params.chat[params.chatId].type != 'lines')
		{
			return false;
		}

		if (params.userInChat[params.chatId].indexOf(this.base.userId) == -1)
		{
			this.base.updateElement(params.userId, {
				user: { idle: false, last_activity_date: new Date()}
			});

			return false;
		}

		params.message.params = params.message.params ? params.message.params : {};
		let messageOriginal = Object.assign({}, params.message);

		params.message.text = ChatMessengerCommon.purifyText(params.message.text, params.message.params);
		params.message.status = params.message.senderId == this.base.userId? 'received': '';

		let formattedElement = this.getFormattedElement({
			id: params.message.recipientId,
			chat: params.chat[params.chatId],
			user: params.message.senderId > 0? params.users[params.message.senderId]: {id: 0},
			lines: params.lines[params.chatId],
			message: params.message,
			counter: params.counter > 0 ? params.counter : 1
		});

		let addToRecent = params.notify !== true && params.notify.indexOf(this.base.userId) == -1? this.base.getElement(params.message.recipientId): true;
		if (addToRecent)
		{
			this.base.setElement(params.message.recipientId, formattedElement);
		}

		this.action.writing(params.message.recipientId, false);

		this.base.updateElement(params.userId, {
			user: { idle: false, last_activity_date: new Date()}
		});

		this.base.dialogCache.getDialog(params.message.recipientId).then(dialog =>
		{
			dialog.unreadList.push(params.message.id);
			this.base.dialogCache.updateDialog(dialog.id, {unreadList: dialog.unreadList});
		});

		this.base.dialogCache.addMessage(params.message.recipientId, this.base.dialogCache.getMessageFormat({
			message: messageOriginal,
			files: params.files? params.files: {},
			users: params.users? params.users: {}
		}));

		if (params.message.senderId != this.base.userId)
		{
			this.notifier.show({
				dialogId: formattedElement.id,
				title: formattedElement.chat.name,
				text: (formattedElement.user.name? formattedElement.user.name+': ': '')+formattedElement.message.text,
				avatar: formattedElement.chat.avatar,
			});
		}
	}
	else if (command == 'chatPin')
	{
		this.base.updateElement(params.dialogId, {
			pinned: params.active,
		}, true);
	}
	else if (command == 'deleteBot')
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}
		this.base.deleteElement(params.botId);
		this.search.deleteElement(params.botId);
	}
	else if (command == 'chatUserLeave')
	{
		if (params.userId == this.base.userId)
		{
			this.base.deleteElement('chat'+params.chatId);
			this.search.deleteElement('chat'+params.chatId);
		}
	}
	else if (this.base.isRecent())
	{
		if (command == 'notify')
		{
			this.notify.counter = params.counter;
			this.notify.refresh();
			this.base.updateCounter(false);
		}
		else if (command == 'readNotifyList' || command == 'unreadNotifyList' || command == 'confirmNotify')
		{
			this.notify.counter = params.counter;
			this.base.updateCounter(false);
			if (command != 'readNotifyList')
			{
				this.notify.refresh();
			}
		}
	}
	else if (command == 'generalChatId')
	{
		this.base.generalChatId = params.id;
		ChatDataConverter.generalChatId = this.base.generalChatId;
		BX.componentParameters.set('IM_GENERAL_CHAT_ID', params.id)
	}
};

RecentList.pull.eventLinesExecute = function(data)
{
	let {command, params, extra} = data;

	if (extra.server_time_ago > 30)
	{
		return false;
	}
	if (this.base.debugLog)
	{
		console.warn("RecentList.pull.eventLinesExecute: receive \""+command+"\"", params);
	}

	if (command == 'updateSessionStatus')
	{
		this.base.updateElement('chat'+params.chatId, {
			lines: { status: params.status }
		});
	}
};

RecentList.pull.eventOnlineExecute = function(data)
{
	let {command, params, extra} = data;

	if (extra.server_time_ago > 30)
	{
		return false;
	}
	if (this.base.debugLog)
	{
		console.warn("RecentList.pull.eventOnlineExecute: receive \""+command+"\"", params);
	}

	if (command == 'list' || command == 'userStatus')
	{
		for (let i in params.users)
		{
			if(params.users.hasOwnProperty(i))
			{
				this.base.updateElement(params.users[i].id, {
					user: this.getUserDataFormat(params.users[i])
				});
			}
		}
	}
};


/* Queue API */
RecentList.queue = {
	TYPE_ALL: 'all',
	TYPE_ADD: 'add',
	TYPE_UPDATE: 'update',
};

RecentList.queue.init = function()
{
	this.list = {};
	this.list[this.TYPE_ADD] = {};
	this.list[this.TYPE_UPDATE] = {};

	this.updateInterval = 1000;
	this.updateListInterval = 59000;

	clearInterval(this.updateIntervalId);
	this.updateIntervalId = setInterval(this.worker.bind(this), this.updateInterval);

	clearInterval(this.updateListIntervalId);
	this.updateListIntervalId = setInterval(this.listWorker.bind(this), this.updateListInterval);
};

RecentList.queue.add = function(type, id, element)
{
	if (type == this.TYPE_ALL)
	{
		return false;
	}
	this.list[type][id] = element;
	return true;
};

RecentList.queue.delete = function(type, id)
{
	if (type == this.TYPE_ALL)
	{
		delete this.list[this.TYPE_ADD][id];
		delete this.list[this.TYPE_UPDATE][id];
	}
	else
	{
		delete this.list[type][id];
	}

	return true;
};

RecentList.queue.clear = function()
{
	this.list[this.TYPE_ADD] = {};
	this.list[this.TYPE_UPDATE] = {};

	return true;
};

RecentList.queue.worker = function()
{
	let executeTime = new Date();

	let listAdd = [];
	for (let id in this.list[this.TYPE_ADD])
	{
		if(!this.list[this.TYPE_ADD].hasOwnProperty(id))
		{
			continue;
		}
		listAdd.push(ChatDataConverter.getElementFormat(this.list[this.TYPE_ADD][id]));
		delete this.list[this.TYPE_ADD][id];
	}
	if (listAdd.length > 0)
	{
		if (this.base.listEmpty)
		{
			this.base.listEmpty = false;
			dialogList.removeItem({"params.id" : "empty"});
			this.base.closeEmptyScreen();
		}

		dialogList.addItems(listAdd);
	}

	let listUpdate = [];
	for (let id in this.list[this.TYPE_UPDATE])
	{
		if(!this.list[this.TYPE_UPDATE].hasOwnProperty(id))
		{
			continue;
		}
		listUpdate.push({
			filter: {"params.id" : this.list[this.TYPE_UPDATE][id]['id']},
			element: ChatDataConverter.getElementFormat(this.list[this.TYPE_UPDATE][id])
		});
		delete this.list[this.TYPE_UPDATE][id];
	}
	if (listUpdate.length > 0)
	{
		dialogList.updateItems(listUpdate);
	}

	if (listAdd.length > 0 || listUpdate.length > 0)
	{
		console.info('RecentList.queue.worker: added - '+listAdd.length+' / updated - '+listUpdate.length+' ('+(new Date() - executeTime)+'ms)', {add: listAdd, update: listUpdate});
		this.base.updateCounter(false);
		this.cache.update({recent: true});
	}

	return true;
};

RecentList.queue.listWorker = function()
{
	let executeTime = new Date();
	let listUpdate = [];
	for (let i=0, l=this.base.list.length; i<l; i++)
	{
		if(!this.base.list[i] || !this.base.list[i].runtime)
		{
			continue;
		}

		if (this.base.list[i].type != 'user')
		{
			continue;
		}

		let updateNeeded = false;

		if (this.base.list[i].runtime.status != ChatDataConverter.getUserImageCode(this.base.list[i]))
		{
			updateNeeded = true;
		}

		if (updateNeeded)
		{
			this.add(this.TYPE_UPDATE, this.base.list[i].id, this.base.list[i]);
			listUpdate.push(this.base.list[i].id);
		}
	}

	if (listUpdate.length > 0)
	{
		this.cache.update({recent: true});
	}

	executeTime = (new Date() - executeTime);
	if (listUpdate.length > 0 || executeTime > 3000)
	{
		console.info('RecentList.queue.listWorker: need updated elements - '+listUpdate.length+' ('+executeTime+'ms)', listUpdate);
	}

	return true;
};

RecentList.queue.destroy = function()
{
	clearInterval(this.updateIntervalId);
	clearInterval(this.updateListIntervalId);

	return true;
};


/* Notify API */
RecentList.notify = {};

RecentList.notify.init = function ()
{
	this.counter = 0;
	this.show = false;

	BX.addCustomEvent("onNotificationsOpen", this.onNotificationsOpen.bind(this));
};

RecentList.notify.refresh = function()
{
	BX.postWebEvent("onBeforeNotificationsReload", {});
	Application.refreshNotifications();

	return true;
};

RecentList.notify.read = function(id)
{
	id = parseInt(id);
	if (id <= 0)
		return false;

	BX.rest.callMethod('im.notify.read', {'ID': id});

	return true;
};

RecentList.notify.onNotificationsOpen = function(params)
{
	console.info('RecentList.notify: window is open', params);

	this.counter = 0;
	this.base.updateCounter(false);
};


/* Notifier API */
RecentList.notifier = {};

RecentList.notifier.init = function ()
{
	include("InAppNotifier");

	this.inited = typeof InAppNotifier != 'undefined';
	if (this.inited)
	{
		InAppNotifier.setHandler(data => {
			if (data.dialogId)
			{
				this.base.openDialog(data.dialogId);
			}
		});
	}

	this.delayShow = {};
};

RecentList.notifier.show = function(params, delay)
{
	if (!this.inited || !params.dialogId)
	{
		return false;
	}

	clearTimeout(this.delayShow[params.dialogId]);
	if (delay !== false)
	{
		this.delayShow[params.dialogId] = setTimeout(() => this.show(params, false), 1500);
		return true;
	}

	if (PageManager.getNavigator().isActiveTab())
	{
		let page = PageManager.getNavigator().getVisible();
		if (page.type != "Web")
		{
			return false;
		}
		else if (page.type == 'Web' && page.pageId == 'im-'+params.dialogId)
		{
			return false;
		}
	}

	InAppNotifier.showNotification({
		title: ChatUtils.htmlspecialcharsback(params.title),
		backgroundColor: "#E6000000",
		imageUrl: ChatUtils.getAvatar(params.avatar),
		message: ChatUtils.htmlspecialcharsback(params.text),
		data: params
	});

	return true;
};


/* Actions API */
RecentList.action = {};

RecentList.action.init = function()
{
	//dialogList.setPreviewUrlProvider(this.showPreview.bind(this));

	if (this.base.isRecent())
	{
		let openCreateWidget = {
			type:"plus",
			callback: () => this.chatCreate.open(),
			icon:"plus",
			animation: "hide_on_scroll",
			color: "#515f69"
		};

		if(Application.getPlatform() == "ios")
		{
			dialogList.setRightButtons([openCreateWidget]);
		}
		else
		{
			if(Application.getApiVersion() >= 24)
			{
				dialogList.setFloatingButton(openCreateWidget);
			}
		}
	}
};

RecentList.action.showPreview = function(listElement)
{
	if (listElement.params.type === "user")
	{
		return "/mobile/users/?user_id=" + listElement.params.id;
	}

	return "";
};

RecentList.action.pin = function(elementId, active)
{
	let element = this.base.getElement(elementId, true);

	active = active === true;

	this.base.updateElement(elementId, {
		pinned: active,
	}, true);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.updateElement(id, {
			pinned: params.pinned,
		}, true);
	}, {pinned: element.pinned});

	BX.rest.callMethod('im.recent.pin', {'DIALOG_ID': elementId, 'ACTION': active? 'Y': 'N'})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{
				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.mute = function(elementId, active)
{
	let element = this.base.getElement(elementId, true);
	if (element.type != 'chat' || element.blocked === true)
	{
		return false;
	}

	active = active === true;

	let muteList = ChatUtils.objectClone(element.chat.mute_list);
	muteList[this.base.userId] = active;

	this.base.updateElement(elementId, {
		chat: {mute_list: muteList},
	}, true);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.updateElement(id, {
			chat: {mute_list: params.mute_list},
		}, true);
	}, {mute_list: element.chat.mute_list});

	BX.rest.callMethod('im.chat.mute', {'CHAT_ID': element.chat.id, 'ACTION': active? 'Y': 'N'})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{
				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.hide = function(elementId)
{
	let element = this.base.getElement(elementId, true);

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.rest.callMethod('im.recent.hide', {'DIALOG_ID': elementId})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{

				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.leave = function(elementId)
{
	let element = this.base.getElement(elementId, true);

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.rest.callMethod('im.chat.leave', {'DIALOG_ID': elementId})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{

				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.operatorAnswer = function(elementId)
{
	let element = this.base.getElement(elementId, true);
	if (element.type != 'chat' || element.blocked === true)
	{
		return false;
	}

	this.base.updateElement(elementId, {
		chat: {owner: this.base.userId},
		message: {
			date: new Date(),
			text: BX.message("IMOL_CHAT_ANSWER_"+this.base.userData.gender).replace('#USER#', this.base.userData.name)
		},
		counter: 0
	}, true);

	this.base.openDialog(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.updateElement(id, {
			chat: {owner: params.owner},
			message: {date: params.messageDate, text: params.messageText},
			counter: params.counter
		}, true);
	}, {
		owner: element.chat.owner,
		counter: element.counter,
		messageDate: element.message.date,
		messageText: element.message.text
	});

	BX.rest.callMethod('imopenlines.operator.answer', {'CHAT_ID': element.chat.id})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{
				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.operatorSkip = function(elementId)
{
	let element = this.base.getElement(elementId, true);
	if (element.type != 'chat' || element.blocked === true)
	{
		return false;
	}

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.rest.callMethod('imopenlines.operator.skip', {'CHAT_ID': element.chat.id})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{

				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.operatorSpam = function(elementId)
{
	let element = this.base.getElement(elementId, true);
	if (element.type != 'chat' || element.blocked === true)
	{
		return false;
	}

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.rest.callMethod('imopenlines.operator.spam', {'CHAT_ID': element.chat.id})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{

				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.operatorFinish = function(elementId)
{
	let element = this.base.getElement(elementId, true);
	if (element.type != 'chat' || element.blocked === true)
	{
		return false;
	}

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.rest.callMethod('imopenlines.operator.finish', {'CHAT_ID': element.chat.id})
		.then((result) =>
		{
			if (result.data()) // TODO ALERT IF ERROR
			{
				this.base.unblockElement(elementId);
			}
			else
			{

				this.base.unblockElement(elementId, true);
			}
		})
		.catch(() =>
		{
			this.base.unblockElement(elementId, true);
		});

	return true;
};

RecentList.action.writing = function(elementId, action)
{
	if (action)
	{
		RecentList.updateElement(elementId, {writing: true});
		ChatTimer.start('writing', elementId, 29500, (id) => {
			RecentList.updateElement(id, {writing: false});
		});
	}
	else
	{
		ChatTimer.stop('writing', elementId)
	}
};



/* Event API */
RecentList.event = {};

RecentList.event.init = function ()
{
	this.debug = false;
	this.handlersList = {
		onItemSelected : this.onItemSelected,
		onItemAction : this.onItemAction,
		onRefresh : this.onRefresh,
		onScrollAtTheTop : this.onScrollAtTheTop,
		onSearchShow : this.onSearchShow,
		onSearchHide : this.onSearchHide,
		onScopeSelected : this.onScopeSelected,
		onUserTypeText : this.onSearchTextType,
		onSearchItemSelected : this.onSearchItemSelected,
	};

	this.lastSearchList = [];

	dialogList.setListener(this.router.bind(this));

	BX.addCustomEvent("onOpenProfile", this.onOpenProfile.bind(this));
	BX.addCustomEvent("onOpenDialog", this.onOpenDialog.bind(this));
	BX.addCustomEvent("onDialogIsOpen", this.onDialogIsOpen.bind(this));
	BX.addCustomEvent("onLoadLastMessage", this.onLoadLastMessage.bind(this));
	BX.addCustomEvent("chatdialog::counter::change", this.onDialogCounterChange.bind(this));
	BX.addCustomEvent("chatbackground::task::status::success", this.onReadMessage.bind(this));
};

RecentList.event.router = function(eventName, listElement)
{
	if (this.handlersList[eventName])
	{
		if (eventName != 'onUserTypeText')
		{
			console.log('RecentList.event.router: catch event - '+eventName, listElement);
		}
		this.handlersList[eventName].apply(this, [listElement])
	}
	else if (this.debug)
	{
		console.info('RecentList.event.router: skipped event - '+eventName, listElement);
	}
};

RecentList.event.onOpenDialog = function(event)
{
	return this.base.openDialog(event.dialogId, event.dialogTitleParams);
};

RecentList.event.onOpenProfile = function(event)
{
	return this.base.openUserProfile(event.userId, event.userData);
};

RecentList.event.onDialogIsOpen = function(event)
{
	this.base.updateElement(event.dialogId, {counter: 0});
	if (this.base.dialogCache.dialogs.has(event.dialogId.toString()))
	{
		this.base.dialogCache.dialogs.get(event.dialogId.toString()).unreadList = [];
	}
};

RecentList.event.onDialogCounterChange = function(event)
{
	console.info('RecentList.event.onDialogCounterChange: ', event);
	this.base.updateElement(event.dialogId, {counter: event.counter});
};

RecentList.event.onReadMessage = function(taskId, result)
{
	let action = taskId.toString().split('|')[0];
	if (action === 'readMessage')
	{
		if (result)
		{
			this.base.updateElement(result.dialogId, {counter: result.counter});
		}
	}
};

RecentList.event.onLoadLastMessage = function(event)
{
	if (!this.base.getElement(event.dialogId))
	{
		return true;
	}

	this.base.dialogCache.dialogs.delete(event.dialogId);
	this.base.dialogCache.getDialog(event.dialogId, false);
};

RecentList.event.onItemSelected = function(listElement)
{
	if (listElement.params.type == 'openSearch')
	{
		if (this.base.isRecent())
		{
			console.info('RecentList.event.onItemSelected: open search dialog');
			dialogList.showSearchBar();
		}
	}
	else
	{
		console.info('RecentList.event.onItemSelected: open dialog', listElement.params.id);
		this.base.openDialog(listElement.params.id);
	}
};

RecentList.event.onItemAction = function(listElement)
{
	if (listElement.action.identifier === "hide")
	{
		this.action.hide(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "leave")
	{
		this.action.leave(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "call")
	{
		this.base.callUser(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "pin")
	{
		this.action.pin(listElement.item.params.id, true)
	}
	else if (listElement.action.identifier === "unpin")
	{
		this.action.pin(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "mute")
	{
		this.action.mute(listElement.item.params.id, true)
	}
	else if (listElement.action.identifier === "unmute")
	{
		this.action.mute(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "chatinfo")
	{
		PageManager.openPage({"url" : "/mobile/im/chat.php?chat_id=" + listElement.item.params.id.substr(4)});
	}
	else if (listElement.action.identifier === "operatorAnswer")
	{
		this.action.operatorAnswer(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "operatorSkip")
	{
		this.action.operatorSkip(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "operatorFinish")
	{
		this.action.operatorFinish(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "operatorSpam")
	{
		this.action.operatorSpam(listElement.item.params.id, false);
	}
	else
	{
		this.search.onItemAction(listElement);
	}
};

RecentList.event.onRefresh = function()
{
	//reloadAllScripts();
	this.base.refresh();
};

RecentList.event.onScrollAtTheTop = function()
{
	if (typeof dialogList.toggleSearchBar != "function")
	{
		return false;
	}

	dialogList.toggleSearchBar();

	return true;
};

RecentList.event.onSearchShow = function()
{
	this.search.onSearchShow();
};

RecentList.event.onSearchHide = function()
{
	this.search.onSearchHide();
};

RecentList.event.onSearchTextType = function(event)
{
	let text = event.text.trim();
	if (!text)
	{
		this.search.onSearchShow();
		this.search.onSearchHide();
	}
	else
	{
		ChatSearchScopes.find(text);

		if (!this.search.isScopeSetted)
		{
			dialogList.setSearchScopes([
				{ title: BX.message('IM_SCOPE_USERS'), id: ChatSearchScopes.TYPE_USER },
				{ title: BX.message('IM_SCOPE_CHATS'), id: ChatSearchScopes.TYPE_CHAT },
				{ title: BX.message('IM_SCOPE_DEPARTMENTS'), id: ChatSearchScopes.TYPE_DEPARTMENT }
			]);
			this.search.isScopeSetted = true;
		}
	}
};

RecentList.event.onSearchItemSelected = function(listElement)
{
	this.search.onSearchItemSelected(listElement);
};

RecentList.event.onScopeSelected = function(event)
{
	console.log('RecentList.event.onScopeSelected', event);
	this.search.listType = event.id;
	ChatSearchScopes.setType(event.id);
	this.onSearchTextType({text: ChatSearchScopes.result.text})
};




/* CreateChat API */
RecentList.chatCreate = {};

RecentList.chatCreate.open = function ()
{
	if(Application.getApiVersion() < 25)
	{
		dialogList.showSearchBar();
		return true;
	}

	let listUsers = this.prepareItems();

	PageManager.openComponent("JSStackComponent", {
		componentCode: "im.chat.create",
		scriptPath: "/mobile/mobile_component/im.chat.create/?version="+BX.componentParameters.get('WIDGET_CHAT_CREATE_VERSION', '1.0.0'),
		params: {
			"USER_ID": this.base.userId,
			"SITE_ID": this.base.siteId,
			"LANGUAGE_ID": this.base.languageId,

			"LIST_USERS": listUsers,
			"LIST_DEPARTMENTS": [],
			"SKIP_LIST": [this.base.userId],

			"SEARCH_MIN_SIZE": this.base.searchMinTokenLength,
		},
		rootWidget: {
			name: "chat.create",
			settings: {
				objectName: "ChatCreateInterface",
				title: BX.message('WIDGET_CHAT_CREATE_TITLE'),
				items: listUsers.map(element => ChatDataConverter.getListElementByUser(element)),
				scopes: [
					{ title: BX.message('IM_SCOPE_USERS'), id: ChatSearchScopes.TYPE_USER },
					{ title: BX.message('IM_SCOPE_DEPARTMENTS'), id: ChatSearchScopes.TYPE_DEPARTMENT },
				],
				modal: true,
			}
		}
	});
};

RecentList.chatCreate.prepareItems = function ()
{
	let items = [];
	let itemsIndex = {};

	if (this.base.list.length > 0)
	{
		this.base.list.map(element =>
		{
			if (!element || itemsIndex[element.id])
			{
				return false;
			}
			if (element.type == 'user')
			{
				items.push(element.user);
				itemsIndex[element.id] = true;
			}

			return true;
		});
	}

	this.base.colleaguesList.map(element =>
	{
		if (!element || itemsIndex[element.id])
		{
			return false;
		}

		items.push(element);
	});

	this.search.lastSearchList.map(element =>
	{
		if (!element || itemsIndex[element.id])
		{
			return false;
		}

		if (element.type == 'user')
		{
			items.push(element.user);
			itemsIndex[element.id] = true;
		}
	});

	return items;
};

/* Search API */
RecentList.search = {};

RecentList.search.init = function ()
{
	this.listType = ChatSearchScopes.TYPE_USER;

	this.lastSearchList = [];

	this.isScopeSetted = false;

	ChatSearchScopes.init({
		listType: this.listType,
		dataConverterInited: true,
		minTokenLength: this.base.searchMinTokenLength,
		onDrawSearchResult: this.drawSearchResult.bind(this)
	});
	ChatSearchScopes.setList([], ChatSearchScopes.TYPE_USER);

	ChatSearchScopes.setExternalSearchEnable(true, ChatSearchScopes.TYPE_CHAT);
	ChatSearchScopes.setList([], ChatSearchScopes.TYPE_CHAT);

	ChatSearchScopes.setList([], ChatSearchScopes.TYPE_DEPARTMENT);
	ChatSearchScopes.setMinTokenLength(1, ChatSearchScopes.TYPE_DEPARTMENT);
};

RecentList.search.prepareItems = function ()
{
	let users = [];
	let chats = [];
	let usersIndex = {};

	if (
		!ChatSearchScopes.list[ChatSearchScopes.TYPE_USER].length
		|| !ChatSearchScopes.list[ChatSearchScopes.TYPE_CHAT].length
	)
	{
		if (this.base.list.length > 0)
		{
			this.base.list.map(element =>
			{
				if (!element || usersIndex[element.id])
				{
					return false;
				}
				if (element.type == 'user')
				{
					users.push(element.user);
					usersIndex[element.id] = true;
				}
				else if (element.type == 'chat')
				{
					chats.push(element.chat);
				}

				return true;
			});
		}
	}

	if (!ChatSearchScopes.list[ChatSearchScopes.TYPE_USER].length)
	{
		this.base.colleaguesList.map(element =>
		{
			if (!element || usersIndex[element.id])
			{
				return false;
			}

			users.push(element);
		});
	}

	if (
		!ChatSearchScopes.list[ChatSearchScopes.TYPE_USER].length
		|| !ChatSearchScopes.list[ChatSearchScopes.TYPE_CHAT].length
	)
	{
		this.lastSearchList.map(element =>
		{
			if (!element || usersIndex[element.id])
			{
				return false;
			}

			if (element.type == 'user')
			{
				users.push(element.user);
				usersIndex[element.id] = true;
			}
			else if (element.type == 'chat')
			{
				chats.push(element.chat);
			}
		});
	}

	if (!ChatSearchScopes.list[ChatSearchScopes.TYPE_USER].length)
	{
		ChatSearchScopes.setList(users, ChatSearchScopes.TYPE_USER);
	}

	if (!ChatSearchScopes.list[ChatSearchScopes.TYPE_CHAT].length)
	{
		ChatSearchScopes.setList(chats, ChatSearchScopes.TYPE_CHAT);
	}
};

RecentList.search.drawSearchResult = function (items, sections)
{
	console.log('RecentList.search.drawSearchResult', items);
	dialogList.setSearchResultItems(items, sections);
};

RecentList.search.updateElement = function (dialogId, params)
{
	ChatSearchScopes.updateElement(dialogId, params);

	let index = this.lastSearchList.findIndex((listElement) => listElement && listElement.id == dialogId);
	if (index > -1)
	{
		this.lastSearchList[index] = ChatUtils.objectMerge(this.lastSearchList[index], params);
	}

	return true;
};

RecentList.search.deleteElement = function (dialogId)
{
	ChatSearchScopes.deleteElement(dialogId);

	let index = this.lastSearchList.findIndex((listElement) => listElement && listElement.id == dialogId);
	if (index > -1)
	{
		delete this.lastSearchList[index];
	}

	return true;
};

RecentList.search.lastSearchAdd = function(dialogId)
{
	let isExists = !this.lastSearchList.every(element => !(element.id == dialogId));
	if (isExists)
	{
		return true;
	}

	let elementScope = ChatSearchScopes.listType == ChatSearchScopes.TYPE_CHAT? ChatSearchScopes.TYPE_CHAT: ChatSearchScopes.TYPE_USER;
	let elementId = elementScope == ChatSearchScopes.TYPE_CHAT? dialogId.toString().substr(4): dialogId;

	let item = ChatSearchScopes.getElement(elementId, elementScope);
	if (item)
	{
		if (elementScope == ChatSearchScopes.TYPE_USER)
		{
			item = {
				id: item.id,
				type: "user",
				avatar: {url: item.avatar, color: item.color},
				title: item.name,
				user: item
			};
		}
		else
		{
			item = {
				id: item.id,
				type: "chat",
				avatar: {url: item.avatar, color: item.color},
				title: item.name,
				user: {},
				chat: item
			};
		}
	}
	else
	{
		item = this.base.getElement(dialogId, true);
		if (!item)
		{
			return false;
		}
	}

	this.lastSearchList.unshift(item);
	this.cache.update({lastSearch: true});

	BX.rest.callMethod('im.search.last.add', {'DIALOG_ID': dialogId});

	return true;
};

RecentList.search.lastSearchDelete = function(dialogId)
{
	BX.rest.callMethod('im.search.last.delete', {'DIALOG_ID': dialogId}).then((result) =>
	{
		if (result.data())
		{
			this.lastSearchList.every((element, index) => {
				if (element.id == dialogId)
				{
					delete this.lastSearchList[index];
					return false;
				}
				return true;
			});
			this.cache.update({lastSearch: true});
		}
	});
};

RecentList.search.searchDepartmentEmployees = function (departmentId, departmentTitle)
{
	ChatSearchScopes.onDrawSearchResult([
		{title : BX.message("SEARCH"), type : "loading", unselectable: true, sectionCode: ChatSearchScopes.TYPE_DEPARTMENT_USER, params: { action: 'progress'}}
	], [{
		title : BX.message(`SEARCH_CATEGORY_${ChatSearchScopes.TYPE_DEPARTMENT_USER}`),
		id : ChatSearchScopes.TYPE_DEPARTMENT_USER,
		backgroundColor : "#FFFFFF"
	}]);

	BX.rest.callMethod('im.department.employees.get', {ID: [departmentId], USER_DATA: 'Y'}, null, (xhr) => {
		ChatRestRequest.register('search', xhr);
	}).then(result => {
		let items = [
			{title : BX.message("SEARCH_BACK"), sectionCode: ChatSearchScopes.TYPE_DEPARTMENT_USER, type:"button", unselectable: true, params: { action: 'empty'}}
		];
		if (result.answer.result[departmentId])
		{
			result.answer.result[departmentId].map((element) => {
				let item = ChatDataConverter.getListElementByUser(element);
				item.sectionCode = ChatSearchScopes.TYPE_DEPARTMENT_USER;
				items.push(item);
			});

			ChatSearchScopes.indexItems(result.answer.result[departmentId], ChatSearchScopes.TYPE_USER);
		}
		else
		{
			items.push(
				{title : BX.message("SEARCH_EMPTY").replace("#TEXT#", departmentTitle), sectionCode: ChatSearchScopes.TYPE_DEPARTMENT_USER, type:"button", unselectable: true, params: { action: 'empty'}}
			);
		}

		ChatSearchScopes.onDrawSearchResult(items, [{
			title : BX.message(`SEARCH_CATEGORY_${ChatSearchScopes.TYPE_DEPARTMENT_USER}`),
			id : ChatSearchScopes.TYPE_DEPARTMENT_USER,
			backgroundColor : "#FFFFFF"
		}]);
	});
};

RecentList.search.stopSearchDepartmentEmployees = function()
{

};

RecentList.search.onSearchShow = function()
{
	let items = [];
	let sections = [];

	let employees = [];
	let employeesIndex = {};

	if (this.base.list.length > 0)
	{
		this.base.list.map(element =>
		{
			if (!element)
			{
				return false;
			}
			if (element.type == 'user')
			{
				let item = ChatDataConverter.getSearchElementFormat(element, true);
				item.title = item.shortTitle;
				item.params.action = 'item';
				employees.push(item);
				employeesIndex[element.id] = true;
			}

			return true;
		});
	}

	this.base.colleaguesList.map(element =>
	{
		if (!element || employeesIndex[element.id])
		{
			return false;
		}

		let item = ChatDataConverter.getSearchElementFormat(element);
		item.title = item.shortTitle;
		item.params.action = 'item';
		employees.push(item);

		return true;
	});

	employees = employees.filter((element) => element.id != this.base.userId);

	if (employees.length)
	{
		sections.push({title : BX.message("SEARCH_EMPLOYEES"), id : "user", backgroundColor : "#FFFFFF"});
		items.push({type : "carousel", sectionCode : "user", childItems : employees});
	}

	if (this.lastSearchList.length)
	{
		let recent = [];
		this.lastSearchList.map(element =>
		{
			if (!element)
			{
				return false;
			}

			recent.push(ChatDataConverter.getSearchElementFormat(element, true));
		});

		items = items.concat(recent);
		sections.push({title : BX.message("SEARCH_RECENT"), id : "recent", backgroundColor : "#FFFFFF"});
	}

	this.drawSearchResult(items, sections);

	this.prepareItems();
};

RecentList.search.onSearchHide = function()
{
	ChatSearchScopes.clear();

	dialogList.setSearchScopes([]);
	this.isScopeSetted = false;

	this.listType = ChatSearchScopes.TYPE_USER;
	ChatSearchScopes.setType(ChatSearchScopes.TYPE_USER);
};

RecentList.search.onItemAction = function(listElement)
{
	if (listElement.action.identifier === "delete")
	{
		this.lastSearchDelete(listElement.item.params.id)
	}
};

RecentList.search.onSearchItemSelected = function(listElement)
{
	ChatSearchScopes.selectElement(listElement);

	if (listElement.sectionCode == ChatSearchScopes.TYPE_DEPARTMENT_USER && listElement.params.action != 'item')
	{
		this.event.onSearchTextType({text: ChatSearchScopes.result.text})
	}
	else if (listElement.sectionCode == ChatSearchScopes.TYPE_DEPARTMENT)
	{
		if (listElement.params.action != 'item')
			return true;

		this.searchDepartmentEmployees(listElement.params.id.substr(10), listElement.subtitle);
	}
	else
	{
		if (listElement.params.action != 'item')
			return true;

		this.base.openDialog(listElement.params.id, {
			name: listElement.title,
			description: listElement.subtitle,
			avatar: listElement.imageUrl
		});
		this.lastSearchAdd(listElement.params.id);
	}

	return true;
};


/* Initialization */
RecentList.init();