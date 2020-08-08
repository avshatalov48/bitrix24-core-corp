/**
 * Bitrix im dialog mobile
 * Dialog runtime class
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

// main
import "main.date";

// pull
import {PULL as Pull, PullClient} from "mobile.pull.client";

// ui
import {Vue} from "ui.vue";
import {VuexBuilder} from "ui.vue.vuex";

// messenger files
import {ApplicationModel, MessagesModel, DialoguesModel, UsersModel, FilesModel} from 'im.model';
import {ApplicationController} from 'im.controller';
import {
	DeviceType,
	DeviceOrientation,
	RestMethod,
	RestMethodHandler,
	EventType,
	DialogType,
	DialogReferenceClassName,
	FileStatus,
	FileType, DialogCrmType,
} from 'im.const';
import {Utils} from "im.utils";
import {LocalStorage} from "im.tools.localstorage";
import {ImRestAnswerHandler} from "im.provider.rest";
import {ImPullCommandHandler} from "im.provider.pull";
import {MobileRestAnswerHandler} from "./rest.handler";
import {Timer} from "im.tools.timer";

// TODO change BX.Promise, BX.Main.Date to IMPORT

export class Dialog
{
	/* region 01. Initialize and store data */

	constructor(params = {})
	{
		this.ready = true;

		this.offline = false;

		this.host = this.getHost();

		this.inited = false;

		this.restClient = BX.rest;

		this.customData = [];

		this.localize = {...BX.message};

		this.subscribers = {};
		this.dateFormat = null;

		this.messagesQueue = [];

		this.configRequestXhr = null;

		this.windowFocused = true;

		this.rootNode = document.getElementById('messenger-root');

		this.template = null;

		this.timer = new Timer();

		window.addEventListener('orientationchange', () => {
			if (!this.store)
			{
				return;
			}

			this.store.commit('application/set', {
				device : {
					orientation : Utils.device.getOrientation()
				}
			});

			if (
				this.store.state.application.device.type === DeviceType.mobile
				&& this.store.state.application.device.orientation === DeviceOrientation.horizontal
			)
			{
				document.activeElement.blur();
			}
		});

		// todo change to dynamic storage (LocalStorage web, PageParams for mobile)
		let serverVariables = LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		//alert('Pause: open console for debug');

		BX.componentParameters.init()
			.then(result => this.initMobileSettings(result))
			.then(result => this.initStorage(result))
			.then(result => this.initComponent(result))
			.then(result => this.requestData(result))
			.then(result => this.initEnvironment(result))
			.then(result => this.initMobileEnvironment(result))
			.then(result => this.initPullClient(result))
		;
	}

	initMobileSettings(data)
	{
		console.log('1. initMobileSettings');

		return new Promise((resolve, reject) => {
			ApplicationStorage.getObject('settings.chat', {
				quoteEnable: ChatPerformance.isGestureQuoteSupported(),
				quoteFromRight: false,
				autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
				backgroundType: 'LIGHT_GRAY'
			}).then(value => {
				data.OPTIONS = value;
				resolve(data);
			})
		});
	}

	initStorage(data)
	{
		console.log('2. initStorage');

		this.controller = new ApplicationController();

		this.localize['SITE_DIR'] = data.SITE_DIR;

		this.storedEvents = data.STORED_EVENTS || [];

		let applicationVariables = {
			common: {
				host: this.host,
				userId: data.USER_ID,
				siteId: data.SITE_ID,
				languageId: data.LANGUAGE_ID,
			},
			device: {
				type: Utils.device.isMobile()? DeviceType.mobile: DeviceType.desktop,
				orientation: Utils.device.getOrientation(),
			},
			dialog: {
				dialogId: data.DIALOG_ID,
				messageLimit: this.controller.getDefaultMessageLimit(),
				enableReadMessages: false,
			},
			options: {
				quoteEnable: data.OPTIONS.quoteEnable,
				quoteFromRight: data.OPTIONS.quoteFromRight,
				autoplayVideo: data.OPTIONS.autoplayVideo,
				darkBackground: ChatDialogBackground && ChatDialogBackground[data.OPTIONS.backgroundType] && ChatDialogBackground[data.OPTIONS.backgroundType].dark
			}
		};

		return new VuexBuilder()
			.addModel(ApplicationModel.create().useDatabase(false).setVariables(applicationVariables))
			.addModel(MessagesModel.create().setVariables({host: this.host}))
			.addModel(DialoguesModel.create().setVariables({host: this.host}))
			.addModel(FilesModel.create().setVariables({host: this.host, default: {name: this.getLocalize('IM_MESSENGER_MESSAGE_FILE_DELETED')}}))
			.addModel(UsersModel.create().setVariables({host: this.host, default: {name: this.getLocalize('IM_MESSENGER_MESSAGE_USER_ANONYM')}}))
			.setDatabaseConfig({
				name: 'mobile/im',
				type: VuexBuilder.DatabaseType.jnSharedStorage,
				siteId: data.SITE_ID,
				userId: data.USER_ID,
			})
		.build();
	}

	initComponent(result)
	{
		console.log('3. initComponent');

		this.store = result.store;

		this.store.subscribe(mutation => this.eventStoreInteraction(mutation));

		this.storeCollector = result.builder;

		this.controller.setStore(this.store);
		this.controller.setRestClient(this.restClient);
		this.controller.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));

		this.imRestAnswer = ImRestAnswerHandler.create({
			store: this.store,
			controller: this.controller,
		});
		this.mobileRestAnswer = MobileRestAnswerHandler.create({
			store: this.store,
			controller: this.controller,
			context: this,
		});

		let dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());
		if (dialog)
		{
			this.store.commit('application/set', {dialog: {
				chatId: dialog.chatId,
				diskFolderId: dialog.diskFolderId || 0
			}});
		}

		return new Promise((resolve, reject) => resolve());
	}

	initEnvironment(result)
	{
		let executionTime = new Date();
		this.template = this.attachTemplate();
		console.log('5. initEnvironment', (+new Date() - executionTime)+'ms');

		this.controller.setTemplateEngine(this.template);

		this.setTextareaMessage = Utils.debounce(this.controller.setTextareaMessage, 300, this.controller);

		window.addEventListener('orientationchange', () =>
		{
			if (!this.store)
			{
				return;
			}

			this.store.commit('application/set', {device: {
				orientation: Utils.device.getOrientation()
			}});
		});

		return new Promise((resolve, reject) => resolve());
	}

	initMobileEnvironment(result)
	{
		console.log('6. initMobileEnvironment');

		BXMobileApp.UI.Page.Scroll.setEnabled(false);
		BXMobileApp.UI.Page.captureKeyboardEvents(true);

		BX.addCustomEvent("onKeyboardWillShow", () => {
			this.store.dispatch('application/set', {mobile: {
				keyboardShow: true,
			}});
		});
		BX.addCustomEvent("onKeyboardDidShow", () => {
			this.controller.emit('EventType.dialog.scrollToBottom', {duration: 300, cancelIfScrollChange: true});
		});
		BX.addCustomEvent("onKeyboardWillHide", () => {
			clearInterval(this.keyboardOpeningInterval);
			this.store.dispatch('application/set', {mobile: {
				keyboardShow: false,
			}});
		});

		const checkWindowFocused = () =>
		{
			BXMobileApp.UI.Page.isVisible({callback: (data) =>
			{
				this.windowFocused = data.status === 'visible';
				if (this.windowFocused)
				{
					Vue.event.$emit('bitrixmobile:controller:focus');
				}
				else
				{
					Vue.event.$emit('bitrixmobile:controller:blur');
				}
			}});
		};

		BX.addCustomEvent("onAppActive", () =>
		{
			checkWindowFocused();

			BXMobileApp.UI.Page.isVisible({callback: (data) =>
			{
				if (data.status !== 'visible')
				{
					return false;
				}

				this.getDialogUnread().then(() => {
					this.processSendMessages();
					this.controller.emit(EventType.dialog.sendReadMessages);
				}).catch(() => {
					this.processSendMessages();
				});
			}});
		});
		BX.addCustomEvent("onAppPaused", () => {
			this.windowFocused = false;
			Vue.event.$emit('bitrixmobile:controller:blur');
		});

		BX.addCustomEvent("onOpenPageAfter", checkWindowFocused);
		BX.addCustomEvent("onHidePageBefore", () => {
			this.windowFocused = false;
			Vue.event.$emit('bitrixmobile:controller:blur');
		});

		BXMobileApp.addCustomEvent("chatbackground::task::status::success", (params) => {
			let action = params.taskId.toString().split('|')[0];
			this.executeBackgroundTaskSuccess(action, params);
		});
		BXMobileApp.addCustomEvent("chatbackground::task::status::failure", (params) => {
			let action = params.taskId.toString().split('|')[0];
			this.executeBackgroundTaskFailure(action, params);
		});
		BXMobileApp.addCustomEvent("chatrecent::push::get", (params) => {
			if (this.pullCommandHandler)
			{
				params.optionImportant = true;
				this.pullCommandHandler.handleMessageAdd(params, {});
			}
		});

		BXMobileApp.UI.Page.TextPanel.setParams(this.getKeyboardParams());
		this.changeChatKeyboardStatus();

		BX.MobileUploadProvider.setListener(this.executeUploaderEvent.bind(this));

		this.fileUpdateProgress = Utils.throttle((chatId, fileId, progress, size) => {
			this.store.dispatch('files/update', {
				chatId: chatId,
				id: fileId,
				fields: {
					size: size,
					progress: progress
				}
			});
		}, 500);

		if (Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			let type = this.controller.getDialogData().type;
			if (type !== DialogType.call)
			{
				app.exec("setRightButtons", {items:[
					{
						type: "user_plus",
						callback: () => {
							fabric.Answers.sendCustomEvent("vueChatAddUserButton", {});
							this.openAddUserDialog()
						}
					}
				]});
			}
		}

		if (!Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			this.userShowWorkPosition = true;
			setTimeout(() => {
				this.userShowWorkPosition = false;
				this.redrawHeader();
			}, 1500);

			setInterval(() => {
				this.redrawHeader();
			}, 60000);
		}

		this.redrawHeader();

		this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());

		return new Promise((resolve, reject) => resolve());
	}

	initPullClient()
	{
		console.log('7. initPullClient');

		Pull.subscribe(
			this.pullCommandHandler = new ImPullCommandHandler({
				store: this.store,
				controller: this.controller,
			})
		);

		if (this.storedEvents && this.storedEvents.length > 0)
		{
			setTimeout(() => {
				this.storedEvents = this.storedEvents.filter(event => {
					BX.onCustomEvent('chatrecent::push::get', [event]);
					return false;
				});
			}, 50);
		}

		Pull.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'im',
			command: 'chatUserLeave',
			callback: (params) =>
			{
				if (
					params.userId === this.controller.getUserId()
					&& params.dialogId === this.controller.getDialogId()
				)
				{
					app.closeController();
				}
			}
		});

		Pull.subscribe({
			type: PullClient.SubscriptionType.Status,
			callback: this.eventStatusInteraction.bind(this)
		});

		if (!Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			Pull.subscribe({
				type: PullClient.SubscriptionType.Online,
				callback: this.eventOnlineInteraction.bind(this)
			});
		}

		return new Promise((resolve, reject) => resolve());
	}

	requestData()
	{
		console.log('4. requestData');

		if (this.requestDataSend)
		{
			return true;
		}
		this.timer.start('data', 'load', .5, () => {
			console.warn("ChatDialog.requestData: slow connection show progress icon");
			app.titleAction("setParams", {useProgress: true, useLetterImage: false});
		});

		this.requestDataSend = true;

		let query = {
			[RestMethodHandler.mobileBrowserConstGet]: [RestMethod.mobileBrowserConstGet, {}],
			[RestMethodHandler.imChatGet]: [RestMethod.imChatGet, {dialog_id: this.controller.getDialogId()}],
			[RestMethodHandler.imDialogMessagesGetInit]: [RestMethod.imDialogMessagesGet, {
				dialog_id: this.controller.getDialogId(),
				limit: this.controller.getRequestMessageLimit(),
				convert_text: 'Y'
			}],
		};
		if (Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			query[RestMethodHandler.imUserGet] = [RestMethod.imUserGet, {}];
		}
		else
		{
			query[RestMethodHandler.imUserListGet] = [RestMethod.imUserListGet, {id: [this.controller.getUserId(), this.controller.getDialogId()]}];
		}

		this.restClient.callBatch(query, (response) =>
		{
			if (!response)
			{
				this.requestDataSend = false;
				this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
				return false;
			}

			let constGet = response[RestMethodHandler.mobileBrowserConstGet];
			if (constGet.error())
			{
				// this.setError(constGet.error().ex.error, constGet.error().ex.error_description);
			}
			else
			{
				this.executeRestAnswer(RestMethodHandler.mobileBrowserConstGet, constGet);
			}

			let userGet = response[RestMethodHandler.imUserGet];
			if (userGet && !userGet.error())
			{
				this.executeRestAnswer(RestMethodHandler.imUserGet, userGet);
			}

			let userListGet = response[RestMethodHandler.imUserListGet];
			if (userListGet && !userListGet.error())
			{
				this.executeRestAnswer(RestMethodHandler.imUserListGet, userListGet);
			}

			let chatGetResult = response[RestMethodHandler.imChatGet];
			this.executeRestAnswer(RestMethodHandler.imChatGet, chatGetResult);

			let dialogMessagesGetResult = response[RestMethodHandler.imDialogMessagesGetInit];
			if (dialogMessagesGetResult.error())
			{
				//this.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);
			}
			else
			{
				app.titleAction("setParams", {useProgress: false, useLetterImage: true});
				this.timer.stop('data', 'load', true);

				this.store.dispatch('dialogues/saveDialog', {
					dialogId: this.controller.getDialogId(),
					chatId: this.controller.getChatId(),
				});

				if (this.pullCommandHandler)
				{
					this.pullCommandHandler.option.skip = false;
				}

				this.store.dispatch('application/set', {dialog: {
					enableReadMessages: true
				}}).then(() => {
					this.executeRestAnswer(RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
				});

				this.processSendMessages();
			}

			this.requestDataSend = false;
		}, false, false, Utils.getLogTrackingParams({name: 'mobile.im.dialog', dialog: this.controller.getDialogData()}));

		return new Promise((resolve, reject) => resolve());
	}

	executeRestAnswer(command, result, extra)
	{
		console.warn(command, result, extra);
		this.imRestAnswer.execute(command, result, extra);
		this.mobileRestAnswer.execute(command, result, extra);
	}

	executeUploaderEvent(eventName, eventData, taskId)
	{
		if (eventName !== BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS)
		{
			console.log("ChatDialog.disk.eventRouter: ", eventName, taskId, eventData);
		}

		if (eventName === BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS)
		{
			if (eventData.percent > 95)
			{
				eventData.percent = 95;
			}
			this.fileUpdateProgress(eventData.file.params.chatId, eventData.file.params.file.id, eventData.percent, eventData.byteTotal);
		}
		else if (eventName === BX.MobileUploaderConst.FILE_CREATED)
		{
			if (eventData.result.status === 'error')
			{
				this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
				console.error('File upload error', eventData.result.errors[0].message);
			}
			else
			{
				this.store.dispatch('files/update', {
					chatId: eventData.file.params.chatId,
					id: eventData.file.params.file.id,
					fields: {
						status: FileStatus.wait,
						progress: 95
					}
				});
			}
		}
		else if (eventName === 'onimdiskmessageaddsuccess')
		{
			console.info('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_SUCCESS: ', eventData, taskId);

			let file = eventData.result.FILES['upload'+eventData.result.DISK_ID[0]];

			this.store.dispatch('files/update', {
				chatId: eventData.file.params.chatId,
				id: eventData.file.params.file.id,
				fields: {
					status: FileStatus.upload,
					progress: 100,
					id: file.id,
					size: file.size,
					urlDownload: file.urlDownload,
					urlPreview: file.urlPreview,
					urlShow: file.urlShow,
				}
			});
		}
		else if (eventName === 'onimdiskmessageaddfail')
		{
			console.error('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_FAIL: ', eventData, taskId);
			this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
		}
		else if (
			eventName === BX.MobileUploaderConst.TASK_CANCELLED
			|| eventName === BX.MobileUploaderConst.TASK_NOT_FOUND
		)
		{
			this.cancelUploadFile(eventData.file.params.file.id);
		}
		else if (
			eventName === BX.MobileUploaderConst.FILE_CREATED_FAILED
			|| eventName === BX.MobileUploaderConst.FILE_UPLOAD_FAILED
			|| eventName === BX.MobileUploaderConst.FILE_READ_ERROR
			|| eventName === BX.MobileUploaderConst.TASK_STARTED_FAILED
		)
		{
			console.error('ChatDialog.disk.eventRouter: ', eventName, eventData, taskId);
			this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
		}

		return true;
	};

	prepareFileData(files)
	{
		let prepareFunction = (file) =>
		{
			if (file.urlPreview && file.urlPreview.startsWith('file://'))
			{
				file.urlPreview = 'bx'+file.urlPreview;
			}
			if (file.urlShow && file.urlShow.startsWith('file://'))
			{
				file.urlShow = 'bx'+file.urlShow;
			}

			if (file.type !== FileType.image)
			{
				return file;
			}

			if (file.urlPreview)
			{
				if (file.urlPreview.startsWith('/'))
				{
					file.urlPreview = currentDomain+file.urlPreview;
				}

				file.urlPreview = file.urlPreview
					.replace('http://', 'bxhttp://')
					.replace('https://', 'bxhttps://');
			}

			if (file.urlShow)
			{
				if (file.urlShow.startsWith('/'))
				{
					file.urlShow = currentDomain+file.urlShow;
				}

				file.urlShow = file.urlShow
					.replace('http://', 'bxhttp://')
					.replace('https://', 'bxhttps://');
			}

			return file;
		};

		if (files instanceof Array)
		{
			return files.map(file => prepareFunction(file));
		}
		else
		{
			return prepareFunction(files);
		}
	}

/* endregion 01. Initialize and store data */

/* region 02. Mobile environment methods */
	redrawHeader()
	{
		let headerProperties;
		if (Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			headerProperties = this.getChatHeaderParams();

			this.changeChatKeyboardStatus();
		}
		else
		{
			headerProperties = this.getUserHeaderParams();
			this.setCallMenu();
		}

		if (!headerProperties)
		{
			return false;
		}

		if (!this.headerMenuInited)
		{
			//BXMobileApp.UI.Page.TopBar.title.setUseLetterImage();
			BXMobileApp.UI.Page.TopBar.title.params.useLetterImage = true; // TODO remove this
			BXMobileApp.UI.Page.TopBar.title.setCallback(() => this.openHeaderMenu());

			this.headerMenuInited = true;
		}

		if (headerProperties.name)
		{
			BXMobileApp.UI.Page.TopBar.title.setText(headerProperties.name);
		}
		if (headerProperties.desc)
		{
			BXMobileApp.UI.Page.TopBar.title.setDetailText(headerProperties.desc);
		}
		if (headerProperties.avatar)
		{
			BXMobileApp.UI.Page.TopBar.title.setImage(headerProperties.avatar);
		}
		else if (headerProperties.color)
		{
			//BXMobileApp.UI.Page.TopBar.title.setImageColor(dialog.color);
			BXMobileApp.UI.Page.TopBar.title.params.imageColor = headerProperties.color;
		}

		return true;
	}

	getUserHeaderParams()
	{
		let user = this.store.getters['users/get'](this.controller.getDialogId());
		if (!user || !user.init)
		{
			return false;
		}

		let result = {
			'name': null,
			'desc': null,
			'avatar': null,
			'color': null,
		};

		if (user.avatar)
		{
			result.avatar = user.avatar;
		}
		else
		{
			result.color = user.color;
		}

		result.name = user.name;

		let showLastDate = false;

		if (!this.userShowWorkPosition && user.lastActivityDate)
		{
			showLastDate = Utils.user.getLastDateText(user, this.localize);
		}

		if (showLastDate)
		{
			result.desc = showLastDate;
		}
		else
		{
			if (user.workPosition)
			{
				result.desc = user.workPosition;
			}
			else
			{
				result.desc = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_USER'];
			}
		}

		return result;
	}

	getChatHeaderParams()
	{
		let dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());
		if (!dialog || !dialog.init)
		{
			return false;
		}

		let result = {
			'name': null,
			'desc': null,
			'avatar': null,
			'color': null,
		};

		if (dialog.avatar)
		{
			result.avatar = dialog.avatar;
		}
		else
		{
			result.color = dialog.color;
		}

		result.name = dialog.name;

		let chatTypeTitle = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_CHAT'];
		if (this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_'+dialog.type.toUpperCase()])
		{
			chatTypeTitle = this.localize['MOBILE_HEADER_MENU_CHAT_TYPE_'+dialog.type.toUpperCase()];
		}
		result.desc = chatTypeTitle;

		return result;
	}

	changeChatKeyboardStatus()
	{
		let dialog = this.store.getters['dialogues/get'](this.controller.getDialogId());
		if (!dialog || !dialog.init)
		{
			BXMobileApp.UI.Page.TextPanel.show();
			return true;
		}

		let keyboardShow = true;

		if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.getUserId()))
		{
			keyboardShow = false;
		}

		if (
			typeof this.keyboardShowFlag !== 'undefined'
			&& (
				this.keyboardShowFlag && keyboardShow
				|| !this.keyboardShowFlag && !keyboardShow
			)
		)
		{
			return this.keyboardShowFlag;
		}

		if (keyboardShow)
		{
			BXMobileApp.UI.Page.TextPanel.show();
			this.keyboardShowFlag = true;
		}
		else
		{
			BXMobileApp.UI.Page.TextPanel.hide();
			this.keyboardShowFlag = false;
		}

		return this.keyboardShowFlag;
	}

	setCallMenu()
	{
		if (this.callMenuSetted)
		{
			return true;
		}

		let userData = this.store.getters['users/get'](this.controller.getDialogId(), true);
		if (!userData.init)
		{
			return false;
		}

		if (
			this.controller.getUserId() === parseInt(this.controller.getDialogId())
			|| userData.bot
			|| userData.network
		)
		{
			app.exec("setRightButtons", {items: []});

			this.callMenuSetted = true;
			return true;
		}

		app.exec("setRightButtons", {items: [
			{
				type: "call_audio",
				callback: () => {
					this.openCallMenu();
				}
			},
			{
				type: "call_video",
				callback: () => {

					fabric.Answers.sendCustomEvent("vueChatCallVideoButton", {});

					let userData = {};
					userData[this.controller.getDialogId()] = this.store.getters['users/get'](this.controller.getDialogId(), true);

					BXMobileApp.Events.postToComponent("onCallInvite", {
						userId: this.controller.getDialogId(),
						video: true,
						userData
					}, "calls")
				}
			}
		]});

		this.callMenuSetted = true;

		return true;
	}

	openUserList(params = {})
	{
		let {users = false, title = '', listType = 'LIST', backdrop = true} = params;

		let settings = {
			title,
			objectName: "ChatUserListInterface",
		};
		if (backdrop)
		{
			settings.backdrop = {};
		}

		app.exec("openComponent", {
			name: "JSStackComponent",
			componentCode: 'im.dialog.list',
			scriptPath: "/mobileapp/jn/im.chat.user.list/?version="+BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
			params: {
				"DIALOG_ID": this.controller.getDialogId(),
				"DIALOG_OWNER_ID": this.controller.getDialogData().ownerId,
				"USER_ID": this.controller.getUserId(),
				"LIST_TYPE": listType,
				"USERS": users,
				"IS_BACKDROP": true,
			},
			rootWidget: {name: "list", settings}
		}, false);
	}

	openCallMenu()
	{
		fabric.Answers.sendCustomEvent("vueChatCallAudioButton", {});

		let userData = this.store.getters['users/get'](this.controller.getDialogId(), true);

		if (
			userData.phones.personalMobile
			|| userData.phones.workPhone
			|| userData.phones.personalPhone
			|| userData.phones.innerPhone
		)
		{
			BackdropMenu
				.create('im.dialog.menu.call|'+this.controller.getDialogId())
				.setItems([
					BackdropMenuItem.create('audio')
						.setTitle(this.localize['MOBILE_HEADER_MENU_AUDIO_CALL'])
					,
					BackdropMenuItem.create('personalMobile')
						.setTitle(userData.phones.personalMobile)
						.setSubTitle(this.localize['MOBILE_MENU_CALL_MOBILE'])
						.skip(!userData.phones.personalMobile)
					,
					BackdropMenuItem.create('workPhone')
						.setTitle(userData.phones.workPhone)
						.setSubTitle(this.localize['MOBILE_MENU_CALL_WORK'])
						.skip(!userData.phones.workPhone)
					,
					BackdropMenuItem.create('personalPhone')
						.setTitle(userData.phones.personalPhone)
						.setSubTitle(this.localize['MOBILE_MENU_CALL_PHONE'])
						.skip(!userData.phones.personalPhone)
					,
					BackdropMenuItem.create('innerPhone')
						.setTitle(userData.phones.innerPhone)
						.setSubTitle(this.localize['MOBILE_MENU_CALL_PHONE'])
						.skip(!userData.phones.innerPhone)
					,
				])
				.setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'))
				.setEventListener((name, params, user, backdrop) =>
				{
					if (name !== 'selected')
					{
						return false;
					}

					if (params.id === 'audio')
					{
						BXMobileApp.Events.postToComponent("onCallInvite", {
							userId: this.controller.getDialogId(),
							video: false,
							userData: {[user.id]: user}
						}, "calls");
					}
					else if (params.id === 'innerPhone')
					{
						BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'telephony'});
					}
					else
					{
						BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'device'});

						// items options
						//.setType(BackdropMenuItemType.menu)
						//.disableClose(BX.MobileTools.canUseTelephony())
						// if (!BX.MobileTools.canUseTelephony())
						// {
						// 	BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'device'});
						// 	return false
						// }
						//
						// let subMenu = BackdropMenu
						// 	.create('im.dialog.menu.call.submenu|'+this.controller.getDialogId())
						// 	.setItems([
						// 		BackdropMenuItem.create('number')
						// 			.setType(BackdropMenuItemType.info)
						// 			.setTitle(this.localize["MOBILE_MENU_CALL_TO"]
						// 			.replace('#PHONE_NUMBER#', user.phones[params.id]))
						// 			.setHeight(50)
						// 			.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setFontStyle('bold')))
						// 			.setDisabled(),
						// 		BackdropMenuItem.create('telephony').setTitle(this.localize["MOBILE_CALL_BY_B24"]),
						// 		BackdropMenuItem.create('device').setTitle(this.localize["MOBILE_CALL_BY_MOBILE"]),
						// 	])
						// 	.setEventListener((name, params, options, backdrop) =>
						// 	{
						// 		if (name !== 'selected')
						// 		{
						// 			return false;
						// 		}
						// 		BX.MobileTools.phoneTo(options.phone, {callMethod: params.id});
						// 	})
						// 	.setCustomParams({phone: user.phones[params.id]})
						// ;
						// backdrop.showSubMenu(subMenu);
					}
				})
				.setCustomParams(userData)
				.show()
			;
		}
		else
		{
			BXMobileApp.Events.postToComponent("onCallInvite", {
				userId: this.controller.getDialogId(),
				video: false,
				userData: {[this.controller.getDialogId()]: userData}
			}, "calls");
		}
	}

	leaveChat(confirm = false)
	{
		if (!confirm)
		{
			app.confirm({
				title: this.localize.MOBILE_HEADER_MENU_LEAVE_CONFIRM,
				text: '',
				buttons: [
					this.localize.MOBILE_HEADER_MENU_LEAVE_YES,
					this.localize.MOBILE_HEADER_MENU_LEAVE_NO
				],
				callback: (button) =>
				{
					if (button === 1)
					{
						this.leaveChat(true);
					}
				}
			});

			return true;
		}

		let dialogId = this.controller.getDialogId();

		this.restClient.callMethod(RestMethod.imChatLeave, {DIALOG_ID: dialogId}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imChatLeave,
			dialog: this.controller.getDialogData(dialogId)
		})).then(response => {
			app.closeController();
		});
	}

	openAddUserDialog()
	{
		let listUsers = this.getItemsForAddUserDialog();

		app.exec("openComponent", {
			name: "JSStackComponent",
			componentCode: "im.chat.user.selector",
			scriptPath: "/mobileapp/jn/im.chat.user.selector/?version="+BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
			params: {
				"DIALOG_ID": this.controller.getDialogId(),
				"USER_ID": this.controller.getUserId(),

				"LIST_USERS": listUsers,
				"LIST_DEPARTMENTS": [],
				"SKIP_LIST": [],

				"SEARCH_MIN_SIZE": BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3),
			},
			rootWidget: {
				name: "chat.recipients",
				settings: {
					objectName: "ChatUserSelectorInterface",
					title: BX.message('MOBILE_HEADER_MENU_USER_ADD'),
					limit: 100,
					items: listUsers.map(element => ChatDataConverter.getListElementByUser(element)),
					scopes: [
						{ title: BX.message('MOBILE_SCOPE_USERS'), id: "user" },
						{ title: BX.message('MOBILE_SCOPE_DEPARTMENTS'), id: "department" }
					],
					modal: true,
				}
			}
		}, false);
	}

	getItemsForAddUserDialog()
	{
		let items = [];
		let itemsIndex = {};

		if (!this.widgetCache)
		{
			this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());
		}

		if (this.widgetCache.recentList.length > 0)
		{
			this.widgetCache.recentList.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}
				if (element.type === 'user')
				{
					items.push(element.user);
					itemsIndex[element.id] = true;
				}

				return true;
			});
		}

		this.widgetCache.colleaguesList.map(element =>
		{
			if (!element || itemsIndex[element.id])
			{
				return false;
			}
			items.push(element);
			itemsIndex[element.id] = true;
		});

		this.widgetCache.lastSearchList.map(element =>
		{
			if (!element || itemsIndex[element.id])
			{
				return false;
			}

			if (!element)
			{
				return false;
			}
			if (element.type === 'user')
			{
				items.push(element.user);
				itemsIndex[element.id] = true;
			}

			return true;
		});

		/*
		let skipList = ChatMessengerCommon.getChatUsers();
		if (skipList.indexOf(this.base.userId) === -1)
		{
			skipList.push(this.base.userId)
		}
		items = items.filter((element) => skipList.indexOf(element.id) === -1);
		*/

		return items;
	};

/* endregion 02. Mobile environment methods */

/* region 02. Push & Pull */

	eventStoreInteraction(data)
	{
		if (data.type === 'dialogues/update' && data.payload && data.payload.fields)
		{
			 if (
			 	typeof data.payload.fields.counter !== 'undefined'
				&& typeof data.payload.dialogId !== 'undefined'
			 )
			 {
			 	BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
			 		dialogId: data.payload.dialogId,
			 		counter: data.payload.fields.counter,
				}, true], 'im.recent');
			 }
		}
		else if (data.type === 'dialogues/set')
		{
			data.payload.forEach((dialog) => {
				BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
			 		dialogId: dialog.dialogId,
			 		counter: dialog.counter,
				}, true], 'im.recent');
			});
		}
	}

	eventStatusInteraction(data)
	{
		if (data.status === PullClient.PullStatus.Online)
		{
			this.offline = false;

			if (this.pullRequestMessage)
			{
				this.pullCommandHandler.option.skip = true;

				this.getDialogUnread().then(() => {
					this.pullCommandHandler.option.skip = false;
					this.processSendMessages();
					this.controller.emit(EventType.dialog.sendReadMessages);
				}).catch(() => {
					this.pullCommandHandler.option.skip = false;
					this.processSendMessages();
				});

				this.pullRequestMessage = false;
			}
			else
			{
				this.readMessage();
				this.processSendMessages();
			}
		}
		else if (data.status === PullClient.PullStatus.Offline)
		{
			this.pullRequestMessage = true;
			this.offline = true;
		}
	}

	eventOnlineInteraction(data)
	{
		if (data.command === 'list' || data.command === 'userStatus')
		{
			for (let userId in data.params.users)
			{
				if (!data.params.users.hasOwnProperty(userId))
				{
					continue;
				}

				this.store.dispatch('users/update', {
					id: data.params.users[userId].id,
					fields: data.params.users[userId]
				});

				if (userId.toString() === this.controller.getDialogId())
				{
					this.redrawHeader();
				}
			}
		}
	}

/* endregion 02. Push & Pull */

	getKeyboardParams()
	{
		let dialogData = this.controller.getDialogData();

		let siteDir = this.localize.SITE_DIR? this.localize.SITE_DIR: '/';

		return {
			text: dialogData? dialogData.textareaMessage: '',
			placeholder: this.getLocalize('MOBILE_CHAT_PANEL_PLACEHOLDER'),
			smileButton: {},
			useImageButton:true,
			useAudioMessages: true,
			mentionDataSource:
			{
				outsection: "NO",
				url: siteDir + "mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots"
			},
			attachFileSettings:
			{
				previewMaxWidth: 640,
				previewMaxHeight: 640,
				resize:
				{
					targetWidth: -1,
					targetHeight: -1,
					sourceType: 1,
					encodingType: 0,
					mediaType: 2,
					allowsEdit: false,
					saveToPhotoAlbum: true,
					popoverOptions: false,
					cameraDirection: 0,
				},
				sendFileSeparately: true,
				showAttachedFiles: true,
				editingMediaFiles:false,
				maxAttachedFilesCount: 100
			},
			attachButton: {items:
			[
				{
					id:"disk",
					name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK"),
					dataSource:
					{
						multiple:false,
						url: siteDir + "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId="+this.getLocalize("USER_ID"),
						TABLE_SETTINGS:
						{
							searchField: true,
							showtitle: true,
							modal: true,
							name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK_FILES")
						}
					}
				},
				{
					id: "mediateka",
					name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_GALLERY")
				},
				{
					id: "camera",
					name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_CAMERA")
				}
			]},
			action: (data) =>
			{
				if (typeof data === "string")
				{
					data = {text: data, attachedFiles: []};
				}

				const text = data.text.toString().trim();
				const attachedFiles = data.attachedFiles instanceof Array? data.attachedFiles: [];

				if (attachedFiles.length <= 0)
				{
					this.clearText();
					this.addMessage(text);
				}
				else
				{
					attachedFiles.forEach(file => {
						// disk
						if (typeof file.dataAttributes !== 'undefined')
						{
							fabric.Answers.sendCustomEvent("vueChatFileDisk", {});

							return this.uploadFile({
								source: 'disk',
								name: file.name,
								type: file.type.toString().toLowerCase(),
								preview: !file.dataAttributes.URL || !file.dataAttributes.URL.PREVIEW? null: {
									url: file.dataAttributes.URL.PREVIEW,
									width: file.dataAttributes.URL.PREVIEW.match(/(width=(\d+))/i)[2],
									height: file.dataAttributes.URL.PREVIEW.match(/(height=(\d+))/i)[2]
								},
								uploadLink: parseInt(file.dataAttributes.ID),
							});
						}

						// audio
						if (file.type === 'audio/mp4')
						{
							fabric.Answers.sendCustomEvent("vueChatFileAudio", {});

							return this.uploadFile({
								source: 'audio',
								name: 'mobile_audio_'+(new Date).toJSON().slice(0, 19).replace('T', '_').split(':').join('-')+'.mp3',
								type: 'mp3',
								preview: null,
								uploadLink: file.url,
							});
						}

						let filename = file.name;
						let fileType = FilesModel.getType(file.name);

						if (fileType === FileType.video)
						{
							fabric.Answers.sendCustomEvent("vueChatFileVideo", {});
						}
						else if (fileType === FileType.image)
						{
							fabric.Answers.sendCustomEvent("vueChatFileImage", {});
						}
						else
						{
							fabric.Answers.sendCustomEvent("vueChatFileOther", {});
						}

						if (
							fileType === FileType.image
							|| fileType === FileType.video
						)
						{
							let extension = file.name.split('.').slice(-1)[0].toLowerCase();
							if (file.type === 'image/heic')
							{
								extension = 'jpg';
							}
							filename = 'mobile_file_'+(new Date).toJSON().slice(0, 19).replace('T', '_').split(':').join('-')+'.'+extension;
						}

						// file
						return this.uploadFile({
							source: 'gallery',
							name: filename,
							type: file.type.toString().toLowerCase(),
							preview: !file.previewUrl? null: {
								url: file.previewUrl,
								width: file.previewWidth,
								height: file.previewHeight
							},
							uploadLink: file.url,
						});
					});
				}

			},
			callback: (data) =>
			{
				console.log('Textpanel callback', data);
				if (!data.event)
				{
					return false;
				}
				if (data.event === "onKeyPress")
				{
					let text = data.text.toString();
					if (text.trim().length > 2)
					{
						this.controller.startWriting();
					}

					if (text.length === 0)
					{
						this.controller.setTextareaMessage({message: ''});
						this.controller.stopWriting();
					}
					else
					{
						this.setTextareaMessage({message: text});
					}
				}
				else if (Application.getPlatform() !== "android")
				{
					if (data.event === "getFocus")
					{
						if (Utils.platform.isIos() && Utils.platform.getIosVersion() > 12)
						{
							this.controller.emit(EventType.dialog.scrollToBottom, {duration: 300, cancelIfScrollChange: true})
						}
					}
					else if (data.event === "removeFocus")
					{

					}
				}
			},
		}
	}

/* region 03. Template engine */

	attachTemplate()
	{
		if (this.template)
		{
			return true;
		}

		const application = this;
		const controller = this.controller;
		const restClient = this.restClient;
		const pullClient = this.pullClient || null;

		return Vue.create({
			el: this.rootNode,
			store: this.store,
			template: '<bx-messenger/>',
			beforeCreate()
			{
				this.$bitrixApplication = application;
				this.$bitrixController = controller;
				this.$bitrixRestClient = restClient;
				this.$bitrixPullClient = pullClient;
				this.$bitrixMessages = application.localize;
			},
			destroyed()
			{
				this.$bitrixApplication.template = null;
				this.$bitrixApplication = null;
				this.$bitrixController = null;
				this.$bitrixRestClient = null;
				this.$bitrixPullClient = null;
				this.$bitrixMessages = null;
			}
		});
	}

	detachTemplate()
	{
		if (!this.template)
		{
			return true;
		}

		this.template.$destroy();

		return true;
	}

/* endregion 03. Template engine */

/* region 04. Rest methods */

	addMessage(text, file = null)
	{
		if (!text && !file)
		{
			return false;
		}

		let quiteId = this.store.getters['dialogues/getQuoteId'](this.controller.getDialogId());
		if (quiteId)
		{
			let quoteMessage = this.store.getters['messages/getMessage'](this.controller.getChatId(), quiteId);
			if (quoteMessage)
			{
				let user = null;
				if (quoteMessage.authorId)
				{
					user = this.store.getters['users/get'](quoteMessage.authorId);
				}

				let files = this.store.getters['files/getList'](this.controller.getChatId());

				let message = [];
				message.push('-'.repeat(54));
				message.push((user && user.name? user.name: this.getLocalize('MOBILE_CHAT_SYSTEM_MESSAGE'))+' ['+Utils.date.format(quoteMessage.date, null, this.localize)+']');
				message.push(Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.localize));
				message.push('-'.repeat(54));
				message.push(text);
				text = message.join("\n");

				this.quoteMessageClear();
			}
		}

		console.warn('addMessage', text, file);

		if (!this.controller.isUnreadMessagesLoaded())
		{
			this.sendMessage({id: 0, chatId: this.controller.getChatId(), dialogId: this.controller.getDialogId(), text, file});
			this.processSendMessages();

			return true;
		}

		this.store.commit('application/increaseDialogExtraCount');

		let params = {};
		if (file)
		{
			params.FILE_ID = [file.id];
		}

		this.store.dispatch('messages/add', {
			chatId: this.controller.getChatId(),
			authorId: this.controller.getUserId(),
			text: text,
			params,
			sending: !file,
		}).then(messageId => {

			this.messagesQueue.push({
				id: messageId,
				chatId: this.controller.getChatId(),
				dialogId: this.controller.getDialogId(),
				text,
				file,
				sending: false
			});

			if (this.controller.getChatId())
			{
				this.processSendMessages();
			}
			else
			{
				this.requestData();
			}

		});

		return true;
	}

	uploadFile(file, text = '')
	{
		if (!file)
		{
			return false;
		}

		console.warn('addFile', file, text);

		if (!this.controller.isUnreadMessagesLoaded())
		{
			this.addMessage(text, {id: 0, source: file});
			return true;
		}

		this.store.dispatch('files/add', this.controller.prepareFilesBeforeSave({
			chatId: this.controller.getChatId(),
			authorId: this.controller.getUserId(),
			name: file.name,
			type: FilesModel.getType(file.name),
			extension: file.name.split('.').splice(-1)[0],
			size: 0,
			image: !file.preview? false: {
				width: file.preview.width,
				height: file.preview.height,
			},
			status: file.source === 'disk'? FileStatus.wait: FileStatus.upload,
			progress: 0,
			authorName: this.controller.getCurrentUser().name,
			urlPreview: !file.preview? '': file.preview.url,
		})).then(fileId => this.addMessage(text, {id: fileId, ...file}));

		return true;
	}

	cancelUploadFile(fileId)
	{
		let element = this.messagesQueue.find(element => element.file && element.file.id === fileId);
		if (element)
		{
			BX.MobileUploadProvider.cancelTasks(['imDialog'+fileId]);

			this.store.dispatch('messages/delete', {
				chatId: element.chatId,
				id: element.id,
			}).then(() => {
				this.store.dispatch('files/delete', {
					chatId: element.chatId,
					id: element.file.id,
				});
				this.messagesQueue = this.messagesQueue.filter(el => el.id !== element.id);
			});
		}
	}

	retryUploadFile(fileId)
	{
		let element = this.messagesQueue.find(element => element.file && element.file.id === fileId);
		if (!element)
		{
			return false;
		}

		this.store.dispatch('messages/actionStart', {
			chatId: element.chatId,
			id: element.id
		}).then(() => {
			this.store.dispatch('files/update', {
				chatId: element.chatId,
				id: element.file.id,
				fields: {
					status: FileStatus.upload,
					progress: 0
				}
			});
		});

		element.sending = false;
		this.processSendMessages();

		return true;
	}

	processSendMessages()
	{
		this.messagesQueue.filter(element => !element.sending).forEach(element =>
		{
			element.sending = true;

			if (element.file)
			{
				if (element.file.source === 'disk')
				{
					this.fileCommit({
						chatId: element.chatId,
						dialogId: element.dialogId,
						diskId: element.file.uploadLink,
						messageText: element.text,
						messageId: element.id,
						fileId: element.file.id,
						fileType: FilesModel.getType(element.file.name),
					}, element);
				}
				else
				{
					if (this.controller.getDiskFolderId())
					{
						this.sendMessageWithFile(element);
					}
					else
					{
						element.sending = false;
						this.requestDiskFolderId();
					}
				}
			}
			else
			{
				element.sending = true;
				this.sendMessage(element);
			}
		});

		return true;
	}

	processMarkReadMessages()
	{
		this.controller.readMessageExecute(this.controller.getChatId(), true);
		return true;
	}

	sendMessage(message)
	{
		message.text = message.text.replace(/^([-]{21}\n)/gm, '-'.repeat(54)+'\n');

		this.controller.stopWriting(message.dialogId);

		BXMobileApp.Events.postToComponent('chatbackground::task::add', [
			'sendMessage|'+message.id,
			[RestMethod.imMessageAdd, {
				'TEMPLATE_ID': message.id,
				'DIALOG_ID': message.dialogId,
				'MESSAGE': message.text
			}],
			message
		], 'background');
	}

	sendMessageWithFile(message)
	{
		let fileType = FilesModel.getType(message.file.name);
		let fileExtension = message.file.name.toString().toLowerCase().split('.').splice(-1)[0];
		let attachPreviewFile = fileType !== FileType.image && message.file.preview;
		let needConvert = fileType === FileType.image && message.file.type !== 'image/gif' || fileType === FileType.video;

		BX.MobileUploadProvider.addTasks([{
			url: message.file.uploadLink,
			params: message,
			name: message.file.name,
			type: fileExtension,
			mimeType: fileType === FileType.audio? 'audio/mp4': null,
			resize: !needConvert? null: {
				"quality":80,
				"width":1920,
				"height":1080,
			},
			previewUrl: attachPreviewFile? message.file.preview.url: '',
			folderId: this.controller.getDiskFolderId(),
			taskId: 'imDialog'+message.file.id,
			onDestroyEventName: 'onimdiskmessageaddsuccess'
		}]);
	}

	fileError(chatId, fileId, messageId = 0)
	{
		this.store.dispatch('files/update', {
			chatId: chatId,
			id: fileId,
			fields: {
				status: FileStatus.error,
				progress: 0
			}
		});
		if (messageId)
		{
			this.store.dispatch('messages/actionError', {
				chatId: chatId,
				id: messageId,
				retry: true,
			});
		}
	}

	fileCommit(params, message)
	{
		let queryParams = {
			chat_id: params.chatId,
			message: params.messageText,
			template_id: params.messageId? params.messageId: 0,
			file_template_id: params.fileId? params.fileId: 0,
		};
		if (params.uploadId)
		{
			queryParams.upload_id = params.uploadId;
		}
		else if (params.diskId)
		{
			queryParams.disk_id = params.diskId;
		}

		this.restClient.callMethod(RestMethod.imDiskFileCommit, queryParams, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imDiskFileCommit,
			data: {timMessageType: params.fileType},
			dialog: this.controller.getDialogData(params.dialogId)
		})).then(response => {
			this.executeRestAnswer(RestMethodHandler.imDiskFileCommit, response, message);
		}).catch(error => {
			this.executeRestAnswer(RestMethodHandler.imDiskFileCommit, error, message);
		});

		return true;
	}

	requestDiskFolderId()
	{
		if (this.flagRequestDiskFolderIdSended || this.controller.getDiskFolderId())
		{
			return true;
		}

		this.flagRequestDiskFolderIdSended = true;

		this.restClient.callMethod(RestMethod.imDiskFolderGet, {chat_id: this.controller.getChatId()}).then(response => {
			this.executeRestAnswer(RestMethodHandler.imDiskFolderGet, response);
			this.flagRequestDiskFolderIdSended = false;
			this.processSendMessages();
		}).catch(error => {
			this.flagRequestDiskFolderIdSended = false;
			this.executeRestAnswer(RestMethodHandler.imDiskFolderGet, error);
		});

		return true;
	}

	getDialogHistory(lastId, limit = this.controller.getRequestMessageLimit())
	{
		this.restClient.callMethod(RestMethod.imDialogMessagesGet, {
			'CHAT_ID': this.controller.getChatId(),
			'LAST_ID': lastId,
			'LIMIT': limit,
			'CONVERT_TEXT': 'Y'
		}).then(result => {
			this.executeRestAnswer(RestMethodHandler.imDialogMessagesGet, result);
			this.controller.emit(EventType.dialog.requestHistoryResult, {count: result.data().messages.length});
		}).catch(result => {
			this.controller.emit(EventType.dialog.requestHistoryResult, {error: result.error().ex});
		});
	}

	getDialogUnread(lastId, limit = this.controller.getRequestMessageLimit())
	{
		if (this.promiseGetDialogUnreadWait)
		{
			return this.promiseGetDialogUnread;
		}

		this.promiseGetDialogUnread = new BX.Promise();
		this.promiseGetDialogUnreadWait = true;

		if (!lastId)
		{
			lastId = this.store.getters['messages/getLastId'](this.controller.getChatId());
		}

		if (!lastId)
		{
			this.controller.emit(EventType.dialog.requestUnreadResult, {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});

			this.promiseGetDialogUnread.reject();
			this.promiseGetDialogUnreadWait = false;

			return this.promiseGetDialogUnread;
		}

		this.controller.readMessage(lastId, true, true).then(() =>
		{
			this.timer.start('data', 'load', .5, () => {
				console.warn("ChatDialog.requestData: slow connection show progress icon");
				app.titleAction("setParams", {useProgress: true, useLetterImage: false});
			});

			let query = {
				[RestMethodHandler.imDialogRead]: [RestMethod.imDialogRead, {
					dialog_id: this.controller.getDialogId(),
					message_id: lastId
				}],
				[RestMethodHandler.imChatGet]: [RestMethod.imChatGet, {
					dialog_id: this.controller.getDialogId()
				}],
				[RestMethodHandler.imDialogMessagesGetUnread]: [RestMethod.imDialogMessagesGet, {
					chat_id: this.controller.getChatId(),
					first_id: lastId,
					limit: limit,
					convert_text: 'Y'
				}]
			};

			this.restClient.callBatch(query, (response) =>
			{
				if (!response)
				{
					this.controller.emit(EventType.dialog.requestUnreadResult, {error: {error: 'EMPTY_RESPONSE', error_description: 'Server returned an empty response.'}});

					this.promiseGetDialogUnread.reject();
					this.promiseGetDialogUnreadWait = false;

					return false;
				}

				let chatGetResult = response[RestMethodHandler.imChatGet];
				if (!chatGetResult.error())
				{
					this.executeRestAnswer(RestMethodHandler.imChatGet, chatGetResult);
				}

				let dialogMessageUnread = response[RestMethodHandler.imDialogMessagesGetUnread];
				if (dialogMessageUnread.error())
				{
					this.controller.emit(EventType.dialog.requestUnreadResult, {error: dialogMessageUnread.error().ex});
				}
				else
				{
					this.executeRestAnswer(RestMethodHandler.imDialogMessagesGetUnread, dialogMessageUnread);

					this.controller.emit(EventType.dialog.requestUnreadResult, {
						firstMessageId: dialogMessageUnread.data().messages.length > 0? dialogMessageUnread.data().messages[0].id: 0,
						count: dialogMessageUnread.data().messages.length
					});

					app.titleAction("setParams", {useProgress: false, useLetterImage: true});
					this.timer.stop('data', 'load', true);
				}

				this.promiseGetDialogUnread.fulfill(response);
				this.promiseGetDialogUnreadWait = false;

			}, false, false, Utils.getLogTrackingParams({name: RestMethodHandler.imDialogMessagesGetUnread, dialog: this.controller.getDialogData()}));
		});

		return this.promiseGetDialogUnread;
	}

	retrySendMessage(message)
	{
		let element = this.messagesQueue.find(el => el.id === message.id);
		if (element)
		{
			if (element.file && element.file.id)
			{
				this.retryUploadFile(element.file.id)
			}

			return false;
		}

		this.messagesQueue.push({
			id: message.id,
			chatId: this.controller.getChatId(),
			dialogId: this.controller.getDialogId(),
			text: message.text,
			sending: false
		});

		this.controller.setSendingMessageFlag(message.id);

		this.processSendMessages();
	}

	openProfile(userId)
	{
		BXMobileApp.Events.postToComponent("onUserProfileOpen", [userId, {backdrop: true}], 'communication');
	}

	openDialog(dialogId)
	{
		BXMobileApp.Events.postToComponent("onOpenDialog", [{dialogId}, true], 'im.recent');
	}

	openPhoneMenu(number)
	{
		BX.MobileTools.phoneTo(number);
	}

	openMessageMenu(message)
	{
		if (this.messagesQueue.find(el => el.id === message.id))
		{
			return false;
		}

		this.store.dispatch('messages/update', {
			id: message.id,
			chatId: message.chatId,
			fields: {
				blink: true
			}
		});

		if (!this.messageMenuInstance)
		{
			let currentUser = this.controller.getCurrentUser();
			let dialog = this.controller.getDialogData();

			this.messageMenuInstance = BackdropMenu
				.create('im.dialog.menu.mess|'+this.controller.getDialogId())
				.setItems([
					BackdropMenuItem.create('reply')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_REPLY'])
						.setIcon(BackdropMenuIcon.reply)
						.skip((message) => {
							let dialog = this.controller.getDialogData();
							if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.getUserId()))
							{
								return true;
							}

							return !message.authorId || message.authorId === this.controller.getUserId()
						})
					,

					BackdropMenuItem.create('copy')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_COPY'])
						.setIcon(BackdropMenuIcon.copy)
						.skip((message) => message.params.IS_DELETED === 'Y')
					,

					BackdropMenuItem.create('quote')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_QUOTE'])
						.setIcon(BackdropMenuIcon.quote)
						.skip((message) =>
						{
							let dialog = this.controller.getDialogData();
							if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.getUserId()))
							{
								return true;
							}

							return message.params.IS_DELETED === 'Y';
						})
					,

					BackdropMenuItem.create('unread')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_UNREAD'])
						.setIcon(BackdropMenuIcon.unread)
						.skip((message) => message.authorId === this.controller.getUserId() || message.unread)
					,

					BackdropMenuItem.create('read')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_READ'])
						.setIcon(BackdropMenuIcon.checked)
						.skip((message) => !message.unread)
					,

					BackdropMenuItem.create('edit')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_EDIT'])
						.setIcon(BackdropMenuIcon.edit)
						.skip((message) => message.authorId !== this.controller.getUserId() || message.params.IS_DELETED === 'Y')
					,

					BackdropMenuItem.create('share')
						.setType(BackdropMenuItemType.menu)
						.setIcon(BackdropMenuIcon.circle_plus)
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_SHARE_MENU'])
						.disableClose()
						.skip(currentUser.extranet || dialog.type === 'announcement')
					,

					BackdropMenuItem.create('profile')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_PROFILE'])
						.setIcon(BackdropMenuIcon.user)
						.skip((message) => message.authorId <= 0 || !Utils.dialog.isChatId(this.controller.getDialogId()) || message.authorId === this.controller.getUserId())
					,

					BackdropMenuItem.create('delete')
						.setTitle(this.localize['MOBILE_MESSAGE_MENU_DELETE'])
						.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setColor('#c50000')))
						.setIcon(BackdropMenuIcon.trash)
						.skip((message) => message.authorId !== this.controller.getUserId() || message.params.IS_DELETED === 'Y')
					,
				])
				.setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'))
				.setEventListener((name, params, message, backdrop) => {
					if (name !== 'selected')
					{
						return false;
					}

					if (params.id === 'reply')
					{
						this.replyToUser(message.authorId);
					}
					else if (params.id === 'copy')
					{
						this.copyMessage(message.id);
					}
					else if (params.id === 'quote')
					{
						this.quoteMessage(message.id);
					}
					else if (params.id === 'edit')
					{
						this.editMessage(message.id);
					}
					else if (params.id === 'delete')
					{
						this.deleteMessage(message.id);
					}
					else if (params.id === 'unread')
					{
						this.unreadMessage(message.id);
					}
					else if (params.id === 'read')
					{
						this.readMessage(message.id);
					}
					else if (params.id === 'share')
					{
						let dialog = this.controller.getDialogData();
						let subMenu = BackdropMenu
							.create('im.dialog.menu.mess.submenu|'+this.controller.getDialogId())
							.setItems([
								BackdropMenuItem.create('share_task')
									.setIcon(BackdropMenuIcon.task)
									.setTitle(this.localize['MOBILE_MESSAGE_MENU_SHARE_TASK'])
								,
								BackdropMenuItem.create('share_post')
									.setIcon(BackdropMenuIcon.lifefeed)
									.setTitle(this.localize['MOBILE_MESSAGE_MENU_SHARE_POST'])
								,
								BackdropMenuItem.create('share_chat')
									.setIcon(BackdropMenuIcon.chat)
									.setTitle(this.localize['MOBILE_MESSAGE_MENU_SHARE_CHAT'])
								,
							])
							.setEventListener((name, params, options, backdrop) =>
							{
								if (name !== 'selected')
								{
									return false;
								}


								if (params.id === 'share_task')
								{
									this.shareMessage(message.id, 'TASK');
								}
								else if (params.id === 'share_post')
								{
									this.shareMessage(message.id, 'POST');
								}
								else if (params.id === 'share_chat')
								{
									this.shareMessage(message.id, 'CHAT');
								}
							})
						;
						backdrop.showSubMenu(subMenu);
					}
					else if (params.id === 'profile')
					{
						this.openProfile(message.authorId);
					}
					else
					{
						console.warn('BackdropMenuItem is not implemented', params);
					}
				})
			;
		}
		this.messageMenuInstance.setCustomParams(message).show();

		fabric.Answers.sendCustomEvent("vueChatOpenDropdown", {});
	}

	openHeaderMenu()
	{
		fabric.Answers.sendCustomEvent("vueChatOpenHeaderMenu", {});

		if (!this.headerMenu)
		{
			this.headerMenu = HeaderMenu.create()
				.setUseNavigationBarColor()
				.setEventListener((name, params, customParams) => {
					if (name !== 'selected')
					{
						return false;
					}

					if (params.id === 'profile')
					{
						this.openProfile(this.controller.getDialogId());
					}
					else if (params.id === 'user_list')
					{
						this.openUserList({listType: 'USERS', title: this.localize.MOBILE_HEADER_MENU_USER_LIST, backdrop: false});
					}
					else if (params.id === 'user_add')
					{
						this.openAddUserDialog();
					}
					else if (params.id === 'leave')
					{
						this.leaveChat();
					}
					else if (params.id === 'notify')
					{
						this.controller.muteDialog();
					}
					else if (params.id === 'call_chat_call')
					{
						BX.MobileTools.phoneTo(this.controller.getDialogData().entityId);
					}
					else if (params.id === 'goto_crm')
					{
						let crmData = this.controller.getDialogCrmData();
						let openWidget = BX.MobileTools.resolveOpenFunction('/crm/'+crmData.entityType+'/show/'+crmData.entityId+'/');
						if (openWidget)
						{
							openWidget();
						}
					}
					else if (params.id === 'reload')
					{
						(new BXMobileApp.UI.NotificationBar({
							message: this.localize.MOBILE_HEADER_MENU_RELOAD_WAIT,
							color: "#d920b0ff",
							textColor: "#ffffff",
							groupId: "refresh",
							useLoader: true,
							maxLines: 1,
							align: "center",
							hideOnTap: true
						}, "copy")).show();

						ChatDialog.storeCollector.clearDatabase();

						reload();
					}
				})
			;
		}

		if (Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			let dialogData = this.controller.getDialogData();
			let notifyToggleText = !this.controller.isDialogMuted()? this.localize['MOBILE_HEADER_MENU_NOTIFY_DISABLE']: this.localize['MOBILE_HEADER_MENU_NOTIFY_ENABLE'];
			let notifyToggleIcon = !this.controller.isDialogMuted()? HeaderMenuIcon.notify: HeaderMenuIcon.notify_off;

			let gotoCrmLocalize = '';
			if (
				dialogData.type === DialogType.call
				|| dialogData.type === DialogType.crm
			)
			{
				let crmData = this.controller.getDialogCrmData();
				if (crmData.enabled)
				{
					if (crmData.entityType === DialogCrmType.lead)
					{
						gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_LEAD'];
					}
					else if (crmData.entityType === DialogCrmType.company)
					{
						gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_COMPANY'];
					}
					else if (crmData.entityType === DialogCrmType.contact)
					{
						gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_CONTACT'];
					}
					else if (crmData.entityType === DialogCrmType.deal)
					{
						gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM_DEAL'];
					}
					else
					{
						gotoCrmLocalize = this.localize['MOBILE_GOTO_CRM'];
					}
				}
			}

			if (dialogData.type === DialogType.call)
			{
				this.headerMenu.setItems([
					HeaderMenuItem.create('call_chat_call')
						.setTitle(this.localize['MOBILE_HEADER_MENU_AUDIO_CALL'])
						.setIcon(HeaderMenuIcon.phone)
						.skip(dialogData.entityId === 'UNIFY_CALL_CHAT')
					,
					HeaderMenuItem.create('goto_crm')
						.setTitle(gotoCrmLocalize)
						.setIcon(HeaderMenuIcon.lifefeed)
						.skip(dialogData.entityId === 'UNIFY_CALL_CHAT')
					,
					HeaderMenuItem.create('reload')
						.setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD'])
						.setIcon(HeaderMenuIcon.reload)
					,
				]);
			}
			else
			{
				let items = [
					HeaderMenuItem.create('notify')
						.setTitle(notifyToggleText)
						.setIcon(notifyToggleIcon)
					,
					HeaderMenuItem.create('user_list')
						.setTitle(this.localize['MOBILE_HEADER_MENU_USER_LIST'])
						.setIcon(HeaderMenuIcon.user)
					,
					HeaderMenuItem.create('user_add')
						.setTitle(this.localize['MOBILE_HEADER_MENU_USER_ADD'])
						.setIcon(HeaderMenuIcon.user_plus)
					,
					HeaderMenuItem.create('leave')
						.setTitle(this.localize['MOBILE_HEADER_MENU_LEAVE'])
						.setIcon(HeaderMenuIcon.cross)
					,
					HeaderMenuItem.create('reload')
						.setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD'])
						.setIcon(HeaderMenuIcon.reload)
					,
				];

				if (
					dialogData.type === DialogType.crm
					&& gotoCrmLocalize
				)
				{
					items.unshift(HeaderMenuItem.create('goto_crm')
						.setTitle(gotoCrmLocalize)
						.setIcon(HeaderMenuIcon.lifefeed)
					);
				}

				this.headerMenu.setItems(items);
			}
		}
		else
		{
			this.headerMenu.setItems([
				HeaderMenuItem.create('profile')
					.setTitle(this.localize['MOBILE_HEADER_MENU_PROFILE'])
					.setIcon('user')
					.skip(Utils.dialog.isChatId(this.controller.getDialogId()))
				,
				HeaderMenuItem.create('user_add')
					.setTitle(this.localize['MOBILE_HEADER_MENU_USER_ADD'])
					.setIcon(HeaderMenuIcon.user_plus)
				,
				HeaderMenuItem.create('reload')
					.setTitle(this.localize['MOBILE_HEADER_MENU_RELOAD'])
					.setIcon(HeaderMenuIcon.reload)
				,
			]);
		}

		this.headerMenu.show(true);
	}

	shareMessage(messageId, type)
	{
		if (this.offline)
		{
			return false;
		}

		return this.controller.shareMessage(messageId, type);
	}

	readMessage(messageId)
	{
		// if (this.offline)
		// {
		// 	return false;
		// }

		this.controller.readMessage(messageId, true, true).then((result) =>
		{
			if (result.lastId <= 0)
			{
				return true;
			}
			BXMobileApp.Events.postToComponent('chatbackground::task::action', [
				'readMessage',
				'readMessage|'+result.dialogId,
				result,
				false,
				200
			], 'background');
		});

		return true;
	}

	unreadMessage(messageId)
	{
		if (this.offline)
		{
			return false;
		}

		return this.controller.unreadMessage(messageId);
	}

	openReadedList(list)
	{
		if (!Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			return false;
		}

		if (!list || list.length <= 1)
		{
			return false;
		}

		this.openUserList({
			users: list.map(element => element.userId),
			title: this.localize.MOBILE_MESSAGE_LIST_VIEW
		});
	}

	replyToUser(userId, userData = null)
	{
		if (this.offline)
		{
			return false;
		}

		if (!userData)
		{
			userData = this.store.getters['users/get'](userId);
		}

		return this.insertText({text: `[USER=${userId}]${userData.firstName}[/USER] `});
	}

	copyMessage(id)
	{
		let quoteMessage = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);

		let text = '';

		if (quoteMessage.params.FILE_ID && quoteMessage.params.FILE_ID.length)
		{
			text = quoteMessage.params.FILE_ID.map(fileId => '[DISK='+fileId+']').join(" ");
		}

		if (quoteMessage.text)
		{
			if (text)
			{
				text += '\n';
			}

			text += quoteMessage.text.replace(/^([-]{54}\n)/gm, '-'.repeat(21)+'\n');
		}

		app.exec("copyToClipboard", {text});

		(new BXMobileApp.UI.NotificationBar({
			message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
			color: "#af000000",
			textColor: "#ffffff",
			groupId: "clipboard",
			maxLines: 1,
			align: "center",
			isGlobal: true,
			useCloseButton: true,
			autoHideTimeout: 1500,
			hideOnTap: true
		}, "copy")).show();
	}

	quoteMessage(id)
	{
		this.store.dispatch('dialogues/update', {
			dialogId: this.controller.getDialogId(),
			fields: {
				quoteId: id
			}
		}).then(() => {
			if (this.store.state.application.mobile.keyboardShow)
			{
				setTimeout(() => this.controller.emit(EventType.dialog.scrollToBottom, {duration: 300, cancelIfScrollChange: true}), 300);
			}
			else
			{
				this.setTextFocus();
			}
		});
	}

	reactMessage(id, reaction)
	{
		BXMobileApp.Events.postToComponent('chatbackground::task::action', [
			'reactMessage',
			'reactMessage|'+id,
			{
				messageId: id,
				action: reaction.action === 'auto'? 'auto': (reaction.action === 'set'? 'plus': 'minus')
			},
			false,
			1000
		], 'background');

		if (reaction.action === 'set')
		{
			setTimeout(() => app.exec("callVibration"), 200);
		}
	}

	openMessageReactionList(id, reactions)
	{
		if (!Utils.dialog.isChatId(this.controller.getDialogId()))
		{
			return false;
		}

		let users = [];
		for (let reaction in reactions)
		{
			if (!reactions.hasOwnProperty(reaction))
			{
				continue;
			}

			users = users.concat(reactions[reaction]);
		}

		this.openUserList({
			users: users,
			title: this.localize.MOBILE_MESSAGE_LIST_LIKE
		});
	}

	execMessageKeyboardCommand(data)
	{
		if (data.action !== 'COMMAND')
		{
			return false;
		}

		let {dialogId, messageId, botId, command, params} = data.params;

		this.restClient.callMethod(RestMethod.imMessageCommand, {
			'MESSAGE_ID': messageId,
			'DIALOG_ID': dialogId,
			'BOT_ID': botId,
			'COMMAND': command,
			'COMMAND_PARAMS': params,
		});

		return true;
	}

	execMessageOpenChatTeaser(data)
	{
		this.controller.joinParentChat(data.message.id, 'chat'+data.message.params.CHAT_ID).then((dialogId) => {
			this.openDialog(dialogId);
		}).catch(() => {});

		return true;
	}

	quoteMessageClear()
	{
		this.store.dispatch('dialogues/update', {
			dialogId: this.controller.getDialogId(),
			fields: {
				quoteId: 0
			}
		});
	}

	editMessage(id)
	{
		//if (!this.checkEditMessage(messageId, 'edit'))
		//	return false;

		let message = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);

		this.store.dispatch('application/set', {mobile: {
			keyboardShow: true,
		}});

		let siteDir = this.localize.SITE_DIR? this.localize.SITE_DIR: '/';

		app.exec('showPostForm', {
			mentionButton: {
				dataSource: {
					return_full_mode: "YES",
					outsection: "NO",
					multiple: "NO",
					alphabet_index: "YES",
					url: siteDir + 'mobile/index.php?mobile_action=get_user_list'
				}
			},
			smileButton: {},
			message: {
				text: message.text
			},
			okButton: {
				callback: (data) => this.editMessageSend(id, data.text),
				name: BX.message('MOBILE_EDIT_SAVE')
			},
			cancelButton: {
				callback: () => {
					this.store.dispatch('application/set', {mobile: {
						keyboardShow: false,
					}});
				},
				name: BX.message('MOBILE_EDIT_CANCEL')
			}
		});
	}

	editMessageSend(id, text)
	{
		this.restClient.callMethod(RestMethod.imMessageUpdate, {
			'MESSAGE_ID': id,
			'MESSAGE': text
		}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imMessageUpdate,
			data: {timMessageType: 'text'},
			dialog: this.controller.getDialogData()
		}));
	}

	deleteMessage(id)
	{
		let message = this.store.getters['messages/getMessage'](this.controller.getChatId(), id);
		let files = this.store.getters['files/getList'](this.controller.getChatId());

		let messageText = Utils.text.purify(message.text, message.params, files, this.localize);
		messageText = messageText.length > 50? messageText.substr(0, 47) + '...': messageText;

		app.confirm({
			title: this.localize.MOBILE_MESSAGE_MENU_DELETE_CONFIRM,
			text: messageText? '"' + messageText + '"': '',
			buttons: [
				this.localize.MOBILE_MESSAGE_MENU_DELETE_YES,
				this.localize.MOBILE_MESSAGE_MENU_DELETE_NO
			],
			callback: (button) =>
			{
				if (button === 1)
				{
					this.deleteMessageSend(id);
				}
			}
		});
	}

	deleteMessageSend(id)
	{
		this.restClient.callMethod(RestMethod.imMessageDelete, {
			'MESSAGE_ID': id
		}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imMessageDelete,
			data: {},
			dialog: this.controller.getDialogData(this.controller.getDialogId())
		}));
	}

	insertText(params)
	{
		BXMobileApp.UI.Page.TextPanel.getText((text) => {
			text = text.toString().trim();

			text = text? text+' '+params.text: params.text;

			this.setText(text);
			this.setTextFocus();
		});
	}

	setText(text = '')
	{
		text = text.toString();

		if (text)
		{
			BXMobileApp.UI.Page.TextPanel.setText(text);
		}
		else
		{
			BXMobileApp.UI.Page.TextPanel.clear();
		}

		this.setTextareaMessage({message: text});

		console.log('Set new text in textarea', text? text: '-- empty --');
	}

	clearText()
	{
		this.setText();
	}

	setTextFocus()
	{
		if (!this.store.state.application.mobile.keyboardShow)
		{
			BXMobileApp.UI.Page.TextPanel.focus();
		}
	}

	getHost()
	{
		return currentDomain || '';
	}

	getSiteId()
	{
		return 's1';
	}

	isBackground()
	{
		if (typeof BXMobileAppContext !== "object")
		{
			return false;
		}

		if (
			typeof BXMobileAppContext.isAppActive === "function"
			&& !BXMobileAppContext.isAppActive()
		)
		{
			return true;
		}

		if (typeof BXMobileAppContext.isBackground === "function")
		{
			return BXMobileAppContext.isBackground();
		}

		return false;
	}

/* endregion 05. Templates and template interaction */

/* region 05. Interaction and utils */

	executeBackgroundTaskSuccess(action, data)
	{
		let successObject =
		{
			error: () => false,
			data: () => data.result,
		};

		console.log('Dialog.executeBackgroundTaskSuccess', action, data);

		if (action === 'sendMessage')
		{
			this.executeRestAnswer(RestMethodHandler.imMessageAdd, successObject, data.extra);
		}
		else if (action === 'readMessage')
		{
			this.processMarkReadMessages();
		}
	}

	executeBackgroundTaskFailure(action, data)
	{
		let errorObject =
		{
			error: () => {
				return {
					error: data.code,
					error_description: data.text,
					ex: {
						status: data.status,
					}
				}
			},
			data: () => false,
		};

		console.log('Dialog.executeBackgroundTaskFailure', action, data);

		if (action === 'sendMessage')
		{
			this.executeRestAnswer(RestMethodHandler.imMessageAdd, errorObject, data.extra);
		}
	}

/* endregion 05. Interaction and utils */

/* region 06. Interaction and utils */

	setError(code = '', description = '')
	{
		console.error(`MobileChat.error: ${code} (${description})`);

		let localizeDescription = '';
		if (code.endsWith('LOCALIZED'))
		{
			localizeDescription = description;
		}

		this.store.commit('application/set', {error: {
			active: true,
			code,
			description: localizeDescription
		}});
	}

	clearError()
	{
		this.store.commit('application/set', {error: {
			active: false,
			code: '',
			description: ''}
		});
	}

	addLocalize(phrases)
	{
		if (typeof phrases !== "object" || !phrases)
		{
			return false;
		}

		for (let name in phrases)
		{
			if (phrases.hasOwnProperty(name))
			{
				this.localize[name] = phrases[name];
			}
		}

		return true;
	}

	getLocalize(name)
	{
		let phrase = '';
		if (typeof name === 'undefined')
		{
			return this.localize;
		}
		else if (typeof this.localize[name.toString()] === 'undefined')
		{
			console.warn(`MobileChat.getLocalize: message with code '${name.toString()}' is undefined.`)
		}
		else
		{
			phrase = this.localize[name];
		}

		return phrase;
	}

/* endregion 06. Interaction and utils */
}