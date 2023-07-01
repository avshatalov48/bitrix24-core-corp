"use strict";
/**
 * @bxjs_lang_path component.php
 */

var { ChatSelector } = jn.require('im/chat/selector/chat');
var { EntityReady } = jn.require('entity-ready');
var { SelectorDialogListAdapter } = jn.require('im/chat/selector/adapter/dialog-list');

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
		'queue', 'notify', 'notifier', 'event', 'promotion',
		'action', 'search', 'chatCreate', 'topMenu'
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

	this.viewLoaded = false;

	BX.onViewLoaded(() => {
		this.viewLoaded = true
	});

	this.imagePath = component.path+'images';

	this.ready = false;

	this.list = [];
	this.callList = [];
	this.listEmpty = true;
	this.blocked = {};

	this.lastSyncDate = null;
	this.lastMessageDate = null;

	this.haveElementsToLoad = false;
	this.isLoadingNextElements = false;

	this.siteId = BX.componentParameters.get('SITE_ID', 's1');
	this.siteDir = BX.componentParameters.get('SITE_DIR', '/');
	this.languageId = BX.componentParameters.get('LANGUAGE_ID', 'en');
	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));

	this.userData = {};

	this.colleaguesList = [];
	this.businessUsersList = null;

	this.searchMinTokenLength = BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	this.messageCount = 0;
	this.counterDetail = {};

	this.linesCount = 0;
	this.counterLinesDetail = {};

	this.loadMoreAfterErrorInterval = 5000;
	this.listRequestAfterErrorInterval = 10000;
	this.updateCounterInterval = 300;

	this.firstLoadFlag = false;
	this.loadingFlag = true;

	this.cache.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId);

	// this.dialogCache = new ChatDialogCache();
	//if (!Application.storage.getObject('settings.chat', {vueChat: true}).vueChat)
	//{
	//	this.dialogCache.setDatabase(this.cache.database);
	//	this.dialogCache.getStore();
	//}

	this.swipeHelperShowLimit = 2;

	this.options = Application.storage.getObject('settings.chat.recent', {
		swipeHelperShowCounter: 0
	});

	ChatDataConverter.init({
		'userId': this.userId,
		'generalChatId': this.generalChatId,
		'isIntranetInvitationAdmin': this.isIntranetInvitationAdmin(),
		'listType': this.isRecent()? 'recent': 'lines',
		'updateRuntimeDataFunction': this.updateRuntimeDataElement.bind(this)
	});

	/* events */

	BX.addCustomEvent("failRestoreConnection", () =>
	{
		BX.onViewLoaded(this.refresh.bind(this));
	});

	this.push.init();
	this.queue.init();
	this.search.init();
	this.event.init();
	this.cache.init();
	this.pull.init();
	this.notifier.init();

	if (this.isRecent())
	{
		this.notify.init();
	}

	BX.onViewLoaded(() =>
	{
		this.dialogOptionInit();
		this.action.init();
		this.topMenu.init();
		this.promotion.init();
		this.redraw();
	});

	if (this.isRecent())
	{
		this.refresh({start: true});
	}
	else
	{
		EntityReady.wait('chat').then(() => this.refresh({start: true}));
	}

	IntranetInvite.init();

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
	BX.addCustomEvent("onAppPaused", () =>
	{
		this.push.manager.clear();
	});

	BX.addCustomEvent("onAppActive", () => {
		this.push.actionExecute();
	});

	EntityReady.addCondition('chat', () => this.ready);

	return true;
};

RecentList.isDialogOpen = function()
{
	return PageManager.getNavigator().getAll().length > 1
};

RecentList.openDialog = function(dialogId, dialogTitleParams)
{
	/*
	clearTimeout(this.openDialogTimeout);
	if (waitHistory === true)
	{
		let history = this.push.manager.get();
		if (typeof history != "undefined" && Object.keys(history).length > 0)
		{
			this.openDialogTimeout = setTimeout(() =>{
				this.openDialog(dialogId, dialogTitleParams);
			}, 150);

			return true;
		}
	}
	*/

	let titleParams = {};

	let element = this.getElement(dialogId, true);
	if (element)
	{
		if (element.unread)
		{
			this.action.read(dialogId);
		}

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

	if (element.type === 'chat' && element.chat.entity_type === 'GENERAL')
	{
		titleParams.imageUrl = this.imagePath + '/avatar_general_x3.png';
	}

	if (element.type === 'chat' && element.chat.entity_type === 'SUPPORT24_QUESTION')
	{
		titleParams.imageUrl = this.imagePath + '/avatar_24_question_x3.png';
		titleParams.detailText = '';
	}

	if (element.type === 'notification' || dialogId === 'notify')
	{
		if (BX.componentParameters.get('NEXT_NAVIGATION', 'N') === 'Y')
		{
			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}
			BX.postComponentEvent("onTabChange", ["notifications"], "im.navigation");
		}
		else
		{
			let pageParams = {
				unique : true,
				cache: false,
				url : env.siteDir+"mobile/im/notify.php"
			};
			BX.postWebEvent("onNotifyRefresh", {});
			PageManager.openPage(pageParams);
		}

		return true;
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
			quoteEnable: ChatPerformance.isGestureQuoteSupported(),
			quoteFromRight: Application.getApiVersion() < 31,
			backgroundType: 'LIGHT_GRAY'
		});

		let isLines = (
			RecentList.isOpenlinesRecent()
			|| element && (element.chat && element.chat.type === 'lines' || typeof element.lines !== 'undefined')
			|| dialogTitleParams && dialogTitleParams.chatType === 'lines'
		);

		if (
			isLines
			|| Application.getApiVersion() < 29
		)
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
		else
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
				customInsets: true,
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
		}

		PageManager.openWebComponent(pageParams);

		let tab = isLines ? ["openlines"] : ["chats"];

		BX.postComponentEvent("onTabChange", tab, "im.navigation");
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
		let dialogEntity = false;

		let element = this.getElement(dialogId, true);
		if (element)
		{
			if (element.type == 'user')
			{
				dialogEntity = JSON.stringify(element.user);
			}
			else if (element.type == 'chat')
			{
				dialogEntity =  JSON.stringify(element.chat);
			}
		}

		return {
			PAGE_ID: 'im-'+dialogId,

			DIALOG_ID : dialogId,
			DIALOG_TYPE: element.type,
			DIALOG_ENTITY: dialogEntity,
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
	const { ProfileView } = jn.require("user/profile");
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
	this.linesCount = 0;

	this.list.forEach(element =>
	{
		if (element.type === 'notification')
		{
			this.notify.counter = element.counter;
			return;
		}

		if (element.lines)
		{
			delete this.counterLinesDetail[element.id];
		}
		else
		{
			delete this.counterDetail[element.id];
		}

		if (element.type !== 'chat' || !element.chat.mute_list[this.userId])
		{
			let counter = 0;

			if (element.counter)
			{
				counter = element.counter;
			}
			else if (element.unread)
			{
				counter = 1;
			}

			if (counter)
			{
				if (element.lines)
				{
					this.linesCount += counter;
				}
				else
				{
					this.messageCount += counter;
				}
			}
		}
	});

	for (let dialogId in this.counterDetail)
	{
		if (this.counterDetail.hasOwnProperty(dialogId))
		{
			this.messageCount += this.counterDetail[dialogId];
		}
	}

	for (let dialogId in this.counterLinesDetail)
	{
		if (this.counterLinesDetail.hasOwnProperty(dialogId))
		{
			this.linesCount += this.counterLinesDetail[dialogId];
		}
	}

	if (!this.isRecent())
	{
		return true;
	}

	let chatsCounter = this.messageCount;

	if (
		Application.getApiVersion() < 41
		&& !this.isOpenlinesOperator()
	)
	{
		chatsCounter += this.linesCount;
		this.linesCount = 0;
	}

	let counters = {
		'chats' : chatsCounter,
		'openlines' : this.linesCount,
		'notifications' : this.notify.counter
	};

	BX.postComponentEvent("ImRecent::counter::messages", [chatsCounter], "calls");
	BX.postComponentEvent("ImRecent::counter::list", [counters], "communication");
	BX.postComponentEvent("ImRecent::counter::list", [counters], "im.navigation");
};

RecentList.getTabCode = function()
{
	return BX.componentParameters.get('TAB_CODE', 'none');
}

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

RecentList.isIntranetInvitationAdmin = function()
{
	return BX.componentParameters.get('INTRANET_INVITATION_IS_ADMIN', false);
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
	if (!this.viewLoaded)
	{
		return false;
	}

	if (!this.isRecent())
	{
		dialogList.setSections([
			{title : '', id : "general", backgroundColor: "#ffffff", sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_NEW"), id : "new", backgroundColor: "#ffffff", height: 30,  styles : { title: {font: {size:16, color:"#e66467", fontStyle: "medium"}}}, sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_PIN"), id : "pinned", backgroundColor: "#f6f6f6", sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_WORK_2"), id : "work", backgroundColor: "#ffffff", height: 30, styles : { title: {font: {size:16, color:"#225be5", fontStyle: "medium"}}}, sortItemParams:{order: "asc"}},
			{title : BX.message("OL_SECTION_ANSWERED"), id : "answered", backgroundColor: "#ffffff", height: 30, styles: { title : {font: {size:16, color:"#6EA44E", fontStyle: "medium"}}}, sortItemParams:{order: "desc"}}
		]);

		return true;
	}

	dialogList.setSections([
		{title : '', id : "call", backgroundColor: "#ffffff", sortItemParams:{order: "desc"}},
		{title : '', id : "pinned", backgroundColor: "#ffffff", sortItemParams:{order: "desc"}},
		{title : '', id : "general", backgroundColor: "#ffffff", sortItemParams:{order: "desc"}}
	]);

	return true;
};

RecentList.refresh = function(params)
{
	if (!params)
	{
		params = {start: !this.firstLoadFlag};
	}

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
		recentParams['ONLY_OPENLINES'] = 'Y';
	}

	ChatRestRequest.abort('refresh');
	console.info("RecentList.refresh: send request to server", recentParams);

	let requestMethods = {
		serverTime: ['server.time'],
		revision: ['im.revision.get'],
		counters: ['im.counters.get', {JSON: 'Y'}],
		desktopStatus: ['im.desktop.status.get']
	};

	if (params.start)
	{
		if (this.isRecent())
		{
			this.haveElementsToLoad = true;
		}

		requestMethods.promotion = ['im.promotion.get', {DEVICE_TYPE: 'mobile'}];
		requestMethods.userData = ['im.user.get'];
		requestMethods.businessUsers = ['im.user.business.get', {'USER_DATA': 'Y'}];
	}

	if (this.lastSyncDate || this.isOpenlinesRecent())
	{
		if (this.lastSyncDate)
		{
			recentParams['LAST_SYNC_DATE'] = this.lastSyncDate;
		}
		requestMethods.recent = ['im.recent.get', recentParams];
	}
	else
	{
		requestMethods.recent = ['im.recent.list', recentParams];
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

	if (this.viewLoaded)
	{
		this.loadingFlag = true;
		dialogList.setTitle({text: BX.message('COMPONENT_TITLE'), useProgress:true, largeMode:true});
	}

	ChatTimer.start('recent', 'load', 3000, () => {
		console.warn("RecentList.refresh: slow connection show progress icon");
	});

	let executeTime = new Date();
	BX.rest.callBatch(requestMethods, (result) =>
	{
		ChatRestRequest.unregister('refresh');
		ChatTimer.stop('recent', 'load', true);

		if (this.loadingFlag)
		{
			if (this.viewLoaded)
			{
				dialogList.setTitle({text: BX.message('COMPONENT_TITLE'), useProgress:false, largeMode:true});
			}
			this.loadingFlag = false;
		}

		let revisionError = result.revision.error();
		let serverTimeError = result.serverTime.error();
		let recentError = result.recent.error();
		let countersError = result.counters.error();
		let desktopStatusError = result.desktopStatus.error();
		let userDataError = params.start? result.userData.error(): false;
		let promotionError = params.start? result.promotion.error(): false;
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
			this.lastSyncDate = result.recent.time().date_start;
			console.info("RecentList request: list", result.recent.data());

			if (result.recent.data().items)
			{
				let items = result.recent.data().items;
				if (items.length > 0)
				{
					this.lastMessageDate = items.slice(-1)[0].message.date;
				}
				else
				{
					this.lastMessageDate = '';
				}

				this.list = ChatDataConverter.getListFormat(items);
				this.redraw();

				if (result.recent.data().hasMore)
				{
					this.drawBottomLoader();
				}
				else
				{
					this.haveElementsToLoad = false;
				}
			}
			else
			{
				let recentList = ChatDataConverter.getListFormat(result.recent.data());
				if (recentList.length > 0)
				{
					let recentIndex = recentList.map(element => element.id);
					this.list = this.list.filter(element => !recentIndex.includes(element.id)).concat(recentList);
				}
				else if (this.viewLoaded)
				{
					dialogList.stopRefreshing();
				}
				this.redraw();
			}

			if (
				this.isRecent()
				&& params.start
				&& this.list.length > 0
				&& this.options.swipeHelperShowCounter < this.swipeHelperShowLimit
			)
			{
				let firstElement = ChatDataConverter.getElementFormat(this.list[0]);
				firstElement.showSwipeActions = true;

				if (this.viewLoaded)
				{
					dialogList.updateItem({id: firstElement.id}, firstElement);
				}

				this.options.swipeHelperShowCounter++;
				Application.storage.setObject('settings.chat.recent', this.options);
			}
		}
		// counters block
		if (result.counters && !countersError)
		{
			let counters = result.counters.data();
			console.info("RecentList request: counters", counters);

			this.counterDetail = counters.dialog;

			counters.dialogUnread.forEach(function(dialogId)
			{
				this.counterDetail[dialogId] = 1;
			}.bind(this));

			counters.chatUnread.forEach(function(chatId)
			{
				this.counterDetail['chat'+chatId] = 1;
			}.bind(this));

			for (let chatId in counters.chat)
			{
				if (counters.chat.hasOwnProperty(chatId))
				{
					this.counterDetail['chat'+chatId] = counters.chat[chatId];
				}
			}

			for (let chatId in counters.lines)
			{
				if (counters.lines.hasOwnProperty(chatId))
				{
					this.counterLinesDetail['chat'+chatId] = counters.lines[chatId];
				}
			}

			this.messageCount = counters.type.chat + counters.type.dialog;
			this.linesCount = counters.type.lines;
			this.notify.counter = counters.type.notify;

			this.notify.refresh();
			this.updateCounter(false);
		}

		// userData block
		if (result.userData && !userDataError)
		{
			this.userData = ChatDataConverter.getUserDataFormat(result.userData.data());
		}

		// userData block
		if (result.promotion && !promotionError)
		{
			this.promotion.promoActive = result.promotion.data();
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

		if (result.desktopStatus && !desktopStatusError)
		{
			BX.postComponentEvent("setDesktopStatus", [result.desktopStatus.data()], "communication");
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
				this.loadingFlag = true;
				if (this.viewLoaded)
				{
					dialogList.setTitle({text: BX.message('COMPONENT_TITLE'), useProgress:true, largeMode:true});
				}

				if (error.ex.error == 'REQUEST_CANCELED')
				{
					console.error("RecentList.refresh: execute request canceled by user", error.ex);
				}
				else
				{
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
			if (!this.firstLoadFlag)
			{
				this.firstLoadFlag = true;
			}
			this.errorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);
		}

		if (this.viewLoaded)
		{
			dialogList.stopRefreshing();
		}

		if (this.isRecent() && params.start)
		{
			this.ready = true;
			BX.postComponentEvent('EntityReady::ready', ['chat']);
		}

	}, false, (xhr) => {
		ChatRestRequest.register('refresh', xhr);
	});

	return true;
};

RecentList.loadMore = function()
{
	clearTimeout(this.loadMoreTimeout);

	let recentParams = this.prepareLoadMoreParams();

	BX.rest.callMethod('im.recent.list', recentParams).then(result =>
	{
		let hasMore = result.data().hasMore;
		if (!hasMore)
		{
			this.haveElementsToLoad = false;
		}

		let items = result.data().items;
		if (items.length > 0)
		{
			this.lastMessageDate = items.slice(-1)[0].message.date;

			let listConverted = this.prepareListWithNewElements(items);
			if (this.viewLoaded)
			{
				dialogList.setItems([...this.callList,...listConverted]);
				if (hasMore)
				{
					this.drawBottomLoader();
				}
			}

			this.isLoadingNextElements = false;
		}
		else
		{
			this.lastMessageDate = '';
			this.removeBottomLoader();
		}
	}).catch(error => {
		console.warn('Error during im.recent.list', error);
		this.loadMoreAfterTimeout()
	});
};

RecentList.prepareLoadMoreParams = function()
{
	let params = {};

	if (this.isRecent())
	{
		if (this.isOpenlinesOperator())
		{
			params['SKIP_OPENLINES'] = 'Y';
		}
	}
	else if (this.isOpenlinesRecent())
	{
		if (this.isOpenlinesOperator())
		{
			params['SKIP_CHAT'] = 'Y';
			params['SKIP_DIALOG'] = 'Y';
			params['SKIP_NOTIFICATION'] = 'Y';
			params['ONLY_OPENLINES'] = 'Y';
		}
	}

	params['LAST_MESSAGE_DATE'] = this.lastMessageDate;

	return params;
};

RecentList.isReadyToLoadMore = function(event)
{
	return event.offset.y >= event.contentSize.height * 0.8
	&& !this.isLoadingNextElements
	&& this.haveElementsToLoad
};

RecentList.prepareListWithNewElements = function(newItems)
{
	let newList = ChatDataConverter.getListFormat(newItems);
	let currentListIndex = this.list.map(element => element.id);
	this.list = newList.filter(element => !currentListIndex.includes(element.id)).concat(this.list);

	let listConverted = [];

	this.list.forEach((element) => {
		listConverted.push(ChatDataConverter.getElementFormat(element));
	});

	return listConverted;
};

RecentList.drawBottomLoader = function()
{
	if (!this.viewLoaded)
	{
		return false;
	}

	if (this.listEmpty)
	{
		dialogList.removeItem({"params.id" : "empty"});
	}

	let animate = Application.getApiVersion() >= 39;
	dialogList.addItems(this.getLoadingElement(), animate);

	return true;
};

RecentList.removeBottomLoader = function()
{
	if (!this.viewLoaded)
	{
		return false;
	}

	dialogList.removeItem({'id': 'loading'});

	if (this.listEmpty)
	{
		let animate = Application.getApiVersion() >= 39;
		dialogList.addItems(this.getEmptyElement(), animate);
	}

	return true;
};

RecentList.loadMoreAfterTimeout = function()
{
	clearTimeout(this.loadMoreTimeout);
	this.loadMoreTimeout = setTimeout(() =>
	{
		this.loadMore();
	}, this.loadMoreAfterErrorInterval);
};

RecentList.clearAllCounters = function()
{
	this.counterDetail = {};

	if (this.viewLoaded)
	{
		let newList = [...this.callList];

		this.list.forEach(element => {
			element.counter = 0;
			element.unread = false;

			let formattedElement = ChatDataConverter.getElementFormat(element);
			newList.push(formattedElement);
		});

		dialogList.setItems(newList);
	}
	else
	{
		this.list.forEach(element => {
			element.counter = 0;
			element.unread = false;
		});
	}

	this.updateCounter(false);
	this.cache.update({recent: true});
};

RecentList.redraw = function()
{
	this.queue.clear();

	let listConverted = [...this.callList];

	this.list.forEach((element) =>
	{
		if (this.viewLoaded)
		{
			listConverted.push(ChatDataConverter.getElementFormat(element));
		}
	});

	if (!this.viewLoaded)
	{
		return false
	}

	if (listConverted.length <= 0)
	{
		if (this.loadingFlag)
		{
			listConverted = this.getLoadingElement();
		}
		else
		{
			listConverted = this.getEmptyElement();
			this.openEmptyScreen();
		}
		this.listEmpty = true;
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
	if (!this.viewLoaded)
	{
		return false;
	}

	if (Application.getApiVersion() < 23)
	{
		return false;
	}

	let params = {};
	if (this.isRecent())
	{
		if (BX.componentParameters.get('INTRANET_INVITATION_CAN_INVITE', false))
		{
			params = {
				"upperText": BX.message('IM_EMPTY_TEXT_1'),
				"lowerText": BX.message('IM_EMPTY_TEXT_INVITE'),
				"iconName":"ws_employees",
				"listener": () => IntranetInvite.openRegisterSlider({
					originator: 'im.recent',
					registerUrl: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_URL', ''),
					rootStructureSectionId: BX.componentParameters.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
					adminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
					disableAdminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
					sharingMessage: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', '')
				})
			};
		}
		else
		{
			params = {
				"upperText": BX.message('IM_EMPTY_TEXT_1'),
				"lowerText": BX.message('IM_EMPTY_TEXT_CREATE'),
				"iconName":"ws_employees",
				"listener": () => this.chatCreate.open()
			};
		}

		params["startChatButton"] = {
			"text": BX.message('IM_EMPTY_BUTTON'),
			"iconName": "ws_plus"
		};
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
	if (!this.viewLoaded)
	{
		return false;
	}

	if (Application.getApiVersion() < 23)
	{
		return false;
	}

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

RecentList.getLoadingElement = function()
{
	return [{
		id: 'loading',
		title: BX.message('IM_LIST_LOADING'),
		type: "loading",
		unselectable: true,
		params: { action: 'progress' },
		sectionCode: 'general'
	}];
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
		this.queue.add(this.queue.TYPE_ADD, elementId, data);
	}
	else
	{
		elementId = this.list[index].id;
		this.list[index] = data;
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
	ChatTimer.delete(elementId);

	this.updateCounter(false);
	this.cache.update({recent: true});

	this.queue.delete(this.queue.TYPE_ALL, elementId);

	if (!this.viewLoaded)
	{
		return false;
	}

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

RecentList.drawCall = function(call)
{
	let elementIndex = this.callList.findIndex(element => element.id === call.id);
	if (elementIndex >= 0)
	{
		this.callList[elementIndex] = call;
	}
	else
	{
		this.callList.push(call);
	}

	this.drawCallNative(call);
}

RecentList.drawCallNative = function(element)
{
	if (!this.viewLoaded)
	{
		return false;
	}

	dialogList.findItem({id: element.id}, (find) =>
	{
		if (find)
		{
			dialogList.updateItem({id: element.id}, element);
		}
		else
		{
			let animate = Application.getApiVersion() >= 39;
			dialogList.addItems([element], animate);
		}
	});
}

RecentList.removeCall = function(id)
{
	console.warn("removeCall", id);
	this.callList = this.callList.filter(element => element.id !== id);
	dialogList.removeItem({id});
}

RecentList.updateCallState = function()
{
	this.callList.forEach(element => this.drawCallNative(element));
}

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
		this.inited = true;
		this.push.actionExecute();
		this.base.redraw();
	}

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
		this.notifyManager = Application.getNotificationHistory("im_notify");
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

		if (push.senderCut)
		{
			event.params.message.text = event.params.message.text.substr(push.senderCut)
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
			this.base.openDialog(user);
		}
	}
	else if (pushParams.ACTION && pushParams.ACTION.substr(0, 8) === 'IM_CHAT_')
	{
		if (this.base.isRecent())
		{
			if (this.base.isOpenlinesOperator() && pushParams.CHAT_TYPE === 'L')
			{
				if (!PageManager.getNavigator().isActiveTab())
				{
					PageManager.getNavigator().makeTabActive();
				}

				BX.postComponentEvent("onTabChange", ["openlines"], "im.navigation");
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
			this.base.openDialog('chat' + chatId);
		}
	}
	else if (pushParams.ACTION && pushParams.ACTION === 'IM_NOTIFY')
	{
		this.base.openDialog('notify');
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
	this.notifyStoredEvents = [];
};

RecentList.pull.getStoredEvents = function()
{
	let list = [].concat(this.storedEvents);

	this.storedEvents = [];

	return list;
};

RecentList.pull.getNotifyStoredEvents = function()
{
	let list = [].concat(this.notifyStoredEvents);
	this.notifyStoredEvents = [];

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
		newElement.message.text = ChatMessengerCommon.purifyText(element.message.text, element.message.params);
		newElement.message.author_id = element.message.senderId && element.message.system !== 'Y'? element.message.senderId: 0;
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

	if (command === 'message' || command === 'messageChat')
	{
		if (this.base.isRecent())
		{
			if (
				command === 'messageChat'
				&& params.lines
				&& this.base.isOpenlinesOperator()
			)
			{
				this.base.counterLinesDetail[params.dialogId] = params.counter;
				this.base.updateCounter();
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

		params.message.textOriginal = params.message.text;
		params.message.text = ChatMessengerCommon.purifyText(params.message.text, params.message.params);

		params.message.status = params.message.senderId == this.base.userId? 'received': '';

		if (params.lines)
		{
			delete this.base.counterLinesDetail[params.dialogId];
		}
		else
		{
			delete this.base.counterDetail[params.dialogId];
		}

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

			// this.base.dialogCache.getDialog(recipientId).then(dialog =>
			// {
			// 	dialog.unreadList.push(params.message.id);
			// 	this.base.dialogCache.updateDialog(dialog.id, {unreadList: dialog.unreadList});
			// });
			//
			// this.base.dialogCache.addMessage(recipientId, this.base.dialogCache.getMessageFormat({
			// 	message: messageOriginal,
			// 	files: params.files? params.files: {},
			// 	users: params.users? params.users: {}
			// }));

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
				lines: params.lines,
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

			// this.base.dialogCache.getDialog(params.message.recipientId).then(dialog =>
			// {
			// 	dialog.unreadList.push(params.message.id);
			// 	this.base.dialogCache.updateDialog(dialog.id, {unreadList: dialog.unreadList});
			// });
			//
			// this.base.dialogCache.addMessage(params.message.recipientId, this.base.dialogCache.getMessageFormat({
			// 	message: messageOriginal,
			// 	files: params.files? params.files: {},
			// 	users: params.users? params.users: {}
			// }));

			if (
				extra && extra.server_time_ago <= 5
				//&& formattedElement.chat.type != 'lines'
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

		// this.base.dialogCache.getDialog(params.dialogId).then(dialog =>
		// {
		// 	if (params.dialogId.toString().startsWith('chat'))
		// 	{
		// 		if (command == 'readMessageChatOpponent')
		// 		{
		// 			dialog.readList[params.userId] = {
		// 				messageId: params.lastId,
		// 				date: new Date(params.date)
		// 			};
		// 		}
		// 		else
		// 		{
		// 			delete dialog.readList[params.userId];
		// 		}
		// 	}
		// 	else
		// 	{
		// 		if (command == 'readMessageOpponent')
		// 		{
		// 			dialog.readList[params.dialogId] = {
		// 				messageId: params.lastId,
		// 				date: new Date(params.date)
		// 			};
		// 		}
		// 		else
		// 		{
		// 			dialog.readList[params.dialogId] = {};
		// 		}
		// 	}
		//
		// 	this.base.dialogCache.updateDialog(params.dialogId, {readList: dialog.readList});
		// });
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

		if (params.lines)
		{
			this.base.counterLinesDetail[params.dialogId] = params.counter;
			this.base.updateCounter(false);
		}
		else
		{
			this.base.counterDetail[params.dialogId] = params.muted? 0: params.counter;
		}

		this.base.updateElement(params.dialogId, {
			counter: params.counter
		});

		// if (command == 'readMessage' || command == 'readMessageChat')
		// {
		// 	this.base.dialogCache.updateDialog(params.dialogId, {unreadList: []});
		// }
	}
	else if (command == 'readAllChats')
	{
		if (this.base.isRecent())
		{
			this.base.clearAllCounters();
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

		// if (command == 'messageDeleteComplete')
		// {
		// 	this.base.dialogCache.deleteMessage(params.dialogId, params.id);
		// }
		// else
		// {
		// 	this.base.dialogCache.updateMessage(params.dialogId, this.base.dialogCache.getUpdateMessageFormat({
		// 		message: {id: params.id, text: params.text, params: params.params},
		// 		hasFiles: element.message.file,
		// 		hasAttach: element.message.attach
		// 	}));
		// }
	}
	else if (command == 'dialogChange')
	{
		if (
			this.base.isRecent()
			&& PageManager.getNavigator().isActiveTab()
		)
		{
			this.base.openDialog(params.dialogId);
		}
	}
	else if (command == 'chatRename')
	{
		this.base.updateElement('chat'+params.chatId, {
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
	}
	else if (command == 'chatChangeColor')
	{
		this.base.updateElement('chat'+params.chatId, {
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
	}
	else if (command == 'chatMuteNotify')
	{
		if (
			this.base.isRecent() && !params.lines
			|| this.base.isRecent() && params.lines && !this.base.isOpenlinesOperator()
			|| this.base.isOpenlinesRecent() && params.lines
		)
		{
			this.base.counterDetail[params.dialogId] = params.muted? 0: params.counter;
			this.base.updateCounter(false);
		}

		let muteList = {};
		muteList[this.base.userId] = params.muted;

		this.base.updateElement(params.dialogId, {
			chat: { mute_list: muteList }
		});
	}
	else if (command == 'chatHide')
	{
		delete this.base.counterLinesDetail[params.dialogId];
		delete this.base.counterDetail[params.dialogId];

		this.base.deleteElement(params.dialogId);
		this.base.updateCounter();
	}
	else if (command == 'chatShow')
	{
		if (params.lines)
		{
			this.base.counterLinesDetail[params.dialogId] = 0;
		}
		else
		{
			this.base.counterDetail[params.dialogId] = 0;
		}

		if (this.base.isRecent())
		{
			if (params.lines && this.base.isOpenlinesOperator())
			{
				return false;
			}
		}
		else if (!params.lines)
		{
			return false;
		}

		let formattedElement = ChatDataConverter.getListElement(params);
		this.base.setElement(params.id, formattedElement);

		if (
			params.message.author_id > 0
			&& params.message.author_id != this.base.userId
			&& params.counter > 0
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
	else if (command == 'chatPin')
	{
		this.base.updateElement(params.dialogId, {
			pinned: params.active,
		}, true);
	}
	else if (command == 'chatUnread')
	{
		if (params.lines)
		{
			this.base.counterLinesDetail[params.dialogId] = params.muted? 0: (params.counter? params.counter: 1);
		}
		else
		{
			this.base.counterDetail[params.dialogId] = params.muted? 0: (params.counter? params.counter: 1);
		}

		this.base.updateCounter(false);
		this.base.updateElement(params.dialogId, {
			unread: params.active,
		});
	}
	else if (command == 'deleteBot')
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}
		this.base.deleteElement(params.botId);
	}
	else if (command == 'chatUserLeave')
	{
		if (params.userId == this.base.userId)
		{
			this.base.deleteElement(params.dialogId);
		}

		delete this.base.counterLinesDetail[params.dialogId];
		delete this.base.counterDetail[params.dialogId];

		this.base.updateCounter(false);
	}
	else if (
		command == 'userUpdate'
		|| command == 'updateUser'
		|| command == 'botUpdate'
		|| command == 'updateBot'
	)
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}
		this.base.updateElement(params.user.id, this.getFormattedElement({
			id: params.user.id,
			user: params.user,
		}));
	}
	else if (command == 'userInvite')
	{
		if (this.base.isOpenlinesRecent())
		{
			return false;
		}
		let index = this.base.list.findIndex((listElement) => listElement && listElement.id == params.userId);
		if (index == -1)
		{
			let element = ChatDataConverter.getElementByEntity('user', params.user);
			element.invited = params.invited;

			this.base.setElement(params.userId, element);
		}
		else
		{
			this.base.updateElement(params.userId, {
				user: params.user,
				invited: params.invited,
			});
		}
	}
	else if (command == 'generalChatId')
	{
		this.base.generalChatId = params.id;
		ChatDataConverter.generalChatId = this.base.generalChatId;
		BX.componentParameters.set('IM_GENERAL_CHAT_ID', params.id)
	}
	else if (command === 'desktopOnline')
	{
		BX.postComponentEvent("setDesktopStatus", [{
			isOnline: true,
			version: params.version
		}], "communication");
	}
	else if (command === 'desktopOffline')
	{
		BX.postComponentEvent("setDesktopStatus", [{
			isOnline: false
		}], "communication");
	}
	else if (this.base.isRecent())
	{
		if (command == 'notifyAdd')
		{
			// auto read for notification, if it is "I like the message" notification for the opened dialog.
			const dialog = PageManager.getNavigator().getVisible();
			const isDialogOpened = dialog && dialog.data && typeof(dialog.data.DIALOG_ID) !== 'undefined';
			const isLikeNotification = params.settingName === 'im|like' && params.originalTag.startsWith('RATING|IM|');

			if (isDialogOpened && isLikeNotification)
			{
				const message = params.originalTag.split('|');
				const dialogType = message[2];
				const chatId = message[3];
				const dialogId = dialogType === 'P' ? chatId : `chat${chatId}`;

				const isSameDialog = dialogId === dialog.data.DIALOG_ID.toString();
				if (isSameDialog)
				{
					BX.postComponentEvent('chatbackground::task::action', [
						'readNotification',
						'readNotification|'+params.id,
						{
							action: 'Y',
							id: params.id
						},
					], 'background');

					return;
				}
			}

			this.notify.counter = params.counter;
			this.notify.refresh();
			this.base.updateCounter(false);

			let userName = params.userName ? params.userName : "";
			let firstName = userName? params.userName.split(" ")[0] : "";
			let lastName = userName? params.userName.split(" ")[1] : "";

			if (!params.onlyFlash)
			{
				const notifyStoredEvent = ChatUtils.objectClone(params);
				this.notifyStoredEvents = this.notifyStoredEvents.filter(element => element.id !== notifyStoredEvent.id);
				this.notifyStoredEvents.push(notifyStoredEvent);
			}

			if (extra && extra.server_time_ago <= 5)
			{
				const purifiedNotificationText = ChatMessengerCommon.purifyText(params.text, params.params);
				this.notifier.show({
					dialogId: 'notify',
					title: BX.message('IM_LIST_NOTIFICATIONS'),
					text: (userName ? userName + ': ' : '') + purifiedNotificationText,
					avatar: params.userAvatar ? params.userAvatar : '',
				});
			}
		}
		else if (command == 'notifyRead' || command == 'notifyUnread' || command == 'notifyConfirm')
		{
			this.notify.counter = params.counter;
			this.base.updateCounter(false);
			if (command != 'notifyRead')
			{
				this.notify.refresh();
			}
		}
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

	let listChange = false;

	let listAdd = [];
	for (let id in this.list[this.TYPE_ADD])
	{
		if(!this.list[this.TYPE_ADD].hasOwnProperty(id))
		{
			continue;
		}
		if (this.base.viewLoaded)
		{
			listAdd.push(ChatDataConverter.getElementFormat(this.list[this.TYPE_ADD][id]));
		}
		listChange = true;
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

		let animate = Application.getApiVersion() >= 39;
		dialogList.addItems(listAdd, animate);
	}

	let listUpdate = [];
	for (let id in this.list[this.TYPE_UPDATE])
	{
		if(!this.list[this.TYPE_UPDATE].hasOwnProperty(id))
		{
			continue;
		}
		if (this.base.viewLoaded)
		{
			listUpdate.push({
				filter: {"params.id" : this.list[this.TYPE_UPDATE][id]['id']},
				element: ChatDataConverter.getElementFormat(this.list[this.TYPE_UPDATE][id])
			});
		}
		listChange = true;
		delete this.list[this.TYPE_UPDATE][id];
	}
	if (listUpdate.length > 0)
	{
		dialogList.updateItems(listUpdate);
	}

	if (listChange)
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

	let notify = {
		title: ChatUtils.htmlspecialcharsback(params.title),
		backgroundColor: "#E6000000",
		message: ChatUtils.htmlspecialcharsback(params.text),
		data: params
	};

	let avatar = ChatUtils.getAvatar(params.avatar);
	if (avatar)
	{
		notify.imageUrl = avatar;
	}

	InAppNotifier.showNotification(notify);

	return true;
};

/* Promotion API */
RecentList.promotion = {};

RecentList.promotion.init = function ()
{
	this.promo = {
		'im:video:01042020:mobile': {
			type: 'spotlight',
			config: {
				target: 'call_video',
				text: BX.message('IM_PROMO_VIDEO_01042020_MOBILE')
					.split(' #BR# ').join("\n")
					.split('#BR# ').join("\n")
					.split(' #BR#').join("\n")
					.split('#BR#').join("\n")
			}
		},
	};

	this.promoActive = [];
};

RecentList.promotion.dialogCheck = function (dialogId)
{
	if (
		!dialogId.startsWith('chat')
		&& dialogId !== this.base.userId.toString()
		&& Application.getApiVersion() >= 34
	)
	{
		this.show('im:video:01042020:mobile');
	}
};

RecentList.promotion.showSpotlight = function(config)
{
	const spotlight = dialogs.createSpotlight();

	spotlight.setTarget(config.target);
	spotlight.setHint({text: config.text});
	spotlight.show();
};

RecentList.promotion.show = function(id)
{
	if (!this.promo[id] || !this.promoActive.includes(id))
	{
		return false;
	}

	const promo = this.promo[id];

	if (promo.type === 'spotlight')
	{
		this.showSpotlight(promo.config);
	}

	this.read(id);
	this.save(id);

	return true;
};

RecentList.promotion.read = function(id)
{
	this.promoActive = this.promoActive.filter(element => element !== id);
};

RecentList.promotion.save = function(id)
{
	BX.rest.callMethod('im.promotion.read', {id: id});
};


/* Top-menu API */

RecentList.topMenu = {};

RecentList.topMenu.init = function()
{
	if (!this.base.viewLoaded)
	{
		return false;
	}

	if (!this.base.isRecent())
	{
		return false;
	}

	let topMenuInstance = dialogs.createPopupMenu();
	topMenuInstance.setData(
		[{ id: "readAll", title: BX.message('IM_READ_ALL'), sectionCode: "general", iconName: "read"}],
		[{ id: "general" }],
		(event, item) => {
			if (event === 'onItemSelected' && item.id === 'readAll')
			{
				this.onReadAll();
			}
		}
	);

	dialogList.setRightButtons([{type: "search", callback: () => {
		dialogList.showSearchBar();
	}}, {type: "more", callback: () => {
		topMenuInstance.show();
	}}]);

	return true;
};

RecentList.topMenu.onReadAll = function()
{
	this.base.clearAllCounters();

	BX.rest.callMethod('im.dialog.read.all')
		.then(result => {
			console.log('im.dialog.read.all result:', result);
		})
		.catch(error => {
			console.log('im.dialog.read.all error:', error);
		})
	;
};

/* Actions API */
RecentList.action = {};

RecentList.action.init = function()
{
	if (!this.base.viewLoaded)
	{
		return false;
	}

	if (!this.base.isRecent())
	{
		return false;
	}

	let openCreateWidget = {
		type:"plus",
		callback: () => this.chatCreate.open(),
		icon:"plus",
		animation: "hide_on_scroll",
		color: "#60C7EF"
	};

	if(typeof dialogList["setFloatingButton"] !== "undefined")
	{
		dialogList.setFloatingButton(openCreateWidget);
	}
	else
	{
		dialogList.setRightButtons([openCreateWidget]);
	}

	return true;
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

RecentList.action.read = function(elementId)
{
	let element = this.base.getElement(elementId, true);

	if (Application.getApiVersion() < 34)
	{
		this.base.updateElement(elementId, {
			counter: 0
		}, true);
	}
	else
	{
		this.base.updateElement(elementId, {
			unread: false,
			counter: 0
		}, true);
	}

	this.base.updateCounter(false);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.updateElement(id, {
			unread: params.unread,
			counter: params.counter
		}, true);
	}, {unread: element.unread, counter: element.counter});

	ChatRestRequest.abort('read');

	let requestMethods = {
		setReadStatus: ['im.recent.unread', {'DIALOG_ID': elementId, 'ACTION': 'N'}],
	};

	if (element.counter)
	{
		requestMethods.dialogRead = ['im.dialog.read', {'DIALOG_ID': elementId}];
	}

	BX.rest.callBatch(requestMethods, (result) => {
		ChatRestRequest.unregister('read');

		let unreadError = result.setReadStatus.error();
		let dialogReadError = element.counter? result.dialogRead.error() : false;

		if (unreadError || dialogReadError)
		{
			console.log('Action.read error', result);
			this.base.unblockElement(elementId, true);
		}
		else
		{
			console.log('Action.read result', result);
			this.base.unblockElement(elementId);
		}
	}, false, (xhr) => {ChatRestRequest.register('read', xhr);});

	return true;
};

RecentList.action.unread = function(elementId)
{
	let element = this.base.getElement(elementId, true);

	if (Application.getApiVersion() < 34)
	{
		this.base.updateElement(elementId, {
			counter: 1
		}, true);
	}
	else
	{
		this.base.updateElement(elementId, {
			unread: true,
			counter: element.counter
		}, true);
	}

	this.base.updateCounter(false);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.updateElement(id, {
			unread: params.unread,
			counter: params.counter
		}, true);
	}, {unread: element.unread, counter: element.counter});

	BX.rest.callMethod('im.recent.unread', {'DIALOG_ID': elementId, 'ACTION': 'Y'})
		.then((result) =>
		{
			console.log('Result action.unread', result);
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

RecentList.action.inviteResend = function(elementId)
{
	BX.ajax.runAction('intranet.controller.invite.reinvite', {data: {
	   params: {
		  userId: elementId
	   }
	}}).then((response) => {
		InAppNotifier.showNotification({
			backgroundColor: "#E6000000",
			message: BX.message('INVITE_RESEND_DONE')
		});
	}, (response) => {
		if (response.status === 'error')
		{
			InAppNotifier.showNotification({
				backgroundColor: "#E6000000",
				message: response.errors.map(element => element.message).join('. ')
			});
		}
		else
		{
			InAppNotifier.showNotification({
				backgroundColor: "#E6000000",
				message: BX.message('IM_LIST_ACTION_ERROR')
			});
		}
	});

	return true;
};

RecentList.action.inviteCancel = function(elementId)
{
	let element = this.base.getElement(elementId, true);

	this.base.deleteElement(elementId);

	this.base.blockElement(elementId, true, (id, params) => {
		this.base.setElement(id, params, true);
	}, element);

	BX.ajax.runAction('intranet.controller.invite.deleteinvitation', {data: {
	   params: {
		  userId: elementId
	   }
	}}).then((response) => {
		this.base.unblockElement(elementId);
	}, (response) => {
		if (response.status == 'error')
		{
			InAppNotifier.showNotification({
				backgroundColor: "#E6000000",
				message: response.errors.map(element => element.message).join('. ')
			});
		}
		else
		{
			InAppNotifier.showNotification({
				backgroundColor: "#E6000000",
				message: BX.message('IM_LIST_ACTION_ERROR')
			});
		}

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
	this.lastSearchList = [];

	this.handlersList =
	{
		onItemSelected : this.onItemSelected,
		onItemAction : this.onItemAction,
		onRefresh : this.onRefresh,
		onScrollAtTheTop : this.onScrollAtTheTop,
		onSearchShow: this.search.onSearchShow,
		onSearchHide: this.search.onSearchHide,
		onScopeSelected : this.search.ui.onScopeSelected,
		onUserTypeText : this.search.ui.onUserTypeText,
		onSearchItemSelected : this.search.ui.onSearchItemSelected,
		onScroll: ChatUtils.throttle(this.onScroll, 50, this),
	};

	this.handlersCustomEventList =
	{
		onOpenProfile: this.onOpenProfile,
		onOpenDialog: this.onOpenDialog,
		onDialogIsOpen: this.onDialogIsOpen,
		//onLoadLastMessage: this.onLoadLastMessage,
		"chatdialog::init::complete": this.onDialogInitComplete,
		"chatdialog::counter::change": this.onDialogCounterChange,
		"chatdialog::notification::readAll": this.onNotificationReadAll,
		"chatbackground::task::status::success": this.onReadMessage,
		"CallEvents::active": this.callActive,
		"CallEvents::inactive": this.callInactive,
	};

	dialogList.setListener(this.router.bind(this));

	for (let eventName in this.handlersCustomEventList)
	{
		BX.addCustomEvent(eventName, this.handlersCustomEventList[eventName].bind(this));
	}
};

RecentList.event.router = function(eventName, listElement)
{
	if (this.handlersList[eventName])
	{
		if (eventName != 'onUserTypeText' && eventName != 'onScroll')
		{
			console.log('RecentList.event.router: catch event - '+eventName, listElement);
		}

		if (eventName === 'onScopeSelected' || eventName === 'onUserTypeText' || eventName === 'onSearchItemSelected')
		{
			this.handlersList[eventName].apply(this.search.ui, [listElement]);
		}
		else
		{
			this.handlersList[eventName].apply(this, [listElement]);
		}
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
	// if (this.base.dialogCache.dialogs.has(event.dialogId.toString()))
	// {
	// 	this.base.dialogCache.dialogs.get(event.dialogId.toString()).unreadList = [];
	// }
};

RecentList.event.onDialogInitComplete = function(event)
{
	console.info('RecentList.event.onDialogInitComplete: ', event);
	this.promotion.dialogCheck(event.dialogId.toString());
};

RecentList.event.onDialogCounterChange = function(event)
{
	console.info('RecentList.event.onDialogCounterChange: ', event);
	this.base.updateElement(event.dialogId, {counter: event.counter});
};

RecentList.event.onNotificationReadAll = function(event) {
	console.info('RecentList.event.onNotificationReadAll');
	this.notify.counter = 0;
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

	// this.base.dialogCache.dialogs.delete(event.dialogId);
	// this.base.dialogCache.getDialog(event.dialogId, false);
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
	else if (listElement.params.type == 'call')
	{
		if (listElement.params.canJoin)
		{
			BX.postComponentEvent("CallEvents::joinCall", [listElement.params.call.id], 'calls');
		}
		else
		{
			this.base.openDialog(listElement.params.call.associatedEntity.id);
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
	else if (listElement.action.identifier === "unread")
	{
		this.action.unread(listElement.item.params.id)
	}
	else if (listElement.action.identifier === "read")
	{
		this.action.read(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "mute")
	{
		this.action.mute(listElement.item.params.id, true)
	}
	else if (listElement.action.identifier === "unmute")
	{
		this.action.mute(listElement.item.params.id, false);
	}
	else if (listElement.action.identifier === "inviteResend")
	{
		this.action.inviteResend(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "inviteCancel")
	{
		this.action.inviteCancel(listElement.item.params.id);
	}
	else if (listElement.action.identifier === "profile")
	{
		this.base.openUserProfile(listElement.item.params.id);
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
};

RecentList.event.onRefresh = function()
{
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

RecentList.event.onScroll = function(event)
{
	if (this.base.isReadyToLoadMore(event))
	{
		this.base.isLoadingNextElements = true;
		this.base.loadMore();
	}
};

RecentList.event.callActive = function(call, callStatus)
{
	console.log('RecentList: call active', callStatus, call);

	if (
		call.associatedEntity.advanced.entityType === 'VIDEOCONF'
		&& call.associatedEntity.advanced.entityData1 === 'BROADCAST'
	)
	{
		callStatus = 'remote';
	}

	this.base.drawCall(
		ChatDataConverter.getCallListElement(callStatus, call)
	);
};

RecentList.event.callInactive = function(callId)
{
	console.log('RecentList: call inactive', callId);
	this.base.removeCall('call'+callId);
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
		scriptPath: "/mobile/mobile_component/im:im.chat.create/?version="+BX.componentParameters.get('WIDGET_CHAT_CREATE_VERSION', '1.0.0'),
		params: {
			"USER_ID": this.base.userId,
			"SITE_ID": this.base.siteId,
			"LANGUAGE_ID": this.base.languageId,

			"LIST_USERS": listUsers,
			"LIST_DEPARTMENTS": [],
			"SKIP_LIST": [this.base.userId],

			"SEARCH_MIN_SIZE": this.base.searchMinTokenLength,

			"INTRANET_INVITATION_CAN_INVITE": BX.componentParameters.get('INTRANET_INVITATION_CAN_INVITE', false),
			"INTRANET_INVITATION_REGISTER_URL": BX.componentParameters.get('INTRANET_INVITATION_REGISTER_URL', ''),
			"INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID": BX.componentParameters.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
			"INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM": BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
			"INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE": BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
			"INTRANET_INVITATION_REGISTER_SHARING_MESSAGE": BX.componentParameters.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', ''),
			"INTRANET_INVITATION_IS_ADMIN": BX.componentParameters.get('INTRANET_INVITATION_IS_ADMIN', false)
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
				backdrop: {
					shouldResizeContent: true,
					showOnTop: true,
					topPosition: 100
				},
				supportInvites: (
					BX.componentParameters.get('INTRANET_INVITATION_CAN_INVITE', false)
					&& Application.getApiVersion() >= 34
				)
			},
		},
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

	return items;
};

/* Search API */
RecentList.search = {};

RecentList.search.init = function ()
{
	this.selector = null;
	this.ui = new SelectorDialogListAdapter(dialogList);
};

RecentList.search.onSearchShow = function ()
{
	this.selector = new ChatSelector({
		context: 'IM_CHAT_SEARCH',
		ui: this.search.ui,
		providerOptions: {
			customItems: [
				this.search.getUserCarouselItem(),
			]
		}
	});

	this.selector
		.setSingleChoose(true)
		.open()
	;

	//hack to work on old android clients
	this.selector.onResult = chat => {
		this.selector.resolve(chat);
		this.search.openChat(chat);
	}
};

RecentList.search.openChat = function (chat)
{
	const dialogId = chat.id;
	const dialogParams = {
		name: chat.name,
		avatar: chat.avatar,
		description: chat.description,
	};

	if (chat.customData['imChat'])
	{
		dialogParams.chatType = chat.customData['imChat'].TYPE;

		// TODO: delete when the mobile chat learns about open lines, call chats and others.
		if (chat.customData['imChat'].TYPE === 'open')
		{
			dialogParams.description = BX.message('MOBILE_EXT_CHAT_SELECTOR_CHANNEL_SUBTITLE');
		}
		else
		{
			dialogParams.description = BX.message('MOBILE_EXT_CHAT_SELECTOR_GROUP_SUBTITLE');
		}
	}

	this.base.openDialog(dialogId, dialogParams);
};

RecentList.search.getUserCarouselItem = function() {
	let employees = [];
	let employeesIndex = {};

	if (this.base.list.length > 0)
	{
		this.base.list.map(element => {
			if (!element)
			{
				return false;
			}

			if (element.type === 'user')
			{
				let item = ChatDataConverter.getSearchElementFormat(element, true);
				item = this.getChatProviderItem(item);

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
		item = this.getChatProviderItem(item);

		employees.push(item);

		return true;
	});

	employees =
		employees
			.filter(element => element.userId != this.base.userId)
			.sort((a, b) => {
				if (a.message && a.message.date && b.message && b.message.date)
				{
					return b.message.date - a.message.date;
				}

				return 0;
			})
	;

	return {
		type: 'carousel',
		sectionCode: 'custom',
		childItems: employees,
		hideBottomLine: true,
	};
}

RecentList.search.getChatProviderItem = function (item)
{
	item.title = item.shortTitle;
	item.userId = item.id;
	item.id = 'custom/' + item.id;
	item.type = 'info';

	return item;
}

RecentList.search.onSearchHide = function ()
{

};

/* Initialization */
RecentList.init();
