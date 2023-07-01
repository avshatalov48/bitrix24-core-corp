/**
 * Bitrix Im mobile
 * Dialog application
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2020 Bitrix
 */

// vue
import {VueVendorV2, Vue} from "ui.vue";
import {Core} from "immobile.chat.application.core";

// main
import "main.date";
import {EventEmitter} from "main.core.events";

// pull
import {PULL as Pull, PullClient} from "mobile.pull.client";

// messenger files
import {FilesModel} from 'im.model';

import {
	RestMethod,
	RestMethodHandler,
	EventType,
	DialogType,
	FileStatus,
	FileType, DialogCrmType,
} from 'im.const';
import {Utils} from "im.lib.utils";
import {LocalStorage} from "im.lib.localstorage";

import {MobileImCommandHandler} from "./pull.handler";
import {MobileRestAnswerHandler} from "./rest.handler";
import {Timer} from "im.lib.timer";

// TODO change BX.Promise, BX.Main.Date to IMPORT

// vue components
import 'pull.component.status';

// dialog files
import "./dialog.css";
import 'ui.fonts.opensans';

// widget components
import "./component/dialog";

const STORAGE_PREFIX = 'chatBackgroundQueue';
const FILES_STORAGE_NAME = 'uploadTasks';
const MESSAGES_STORAGE_NAME = 'tasks';
const NO_INTERNET_CONNECTION_ERROR_CODE = -2;
const HTTP_OK_STATUS_CODE = 200;

export class MobileDialogApplication
{
	/* region 01. Initialize and store data */

	constructor(params = {})
	{
		this.inited = false;
		this.initPromise = new BX.Promise;

		this.params = params;

		this.template = null;
		this.rootNode = this.params.node || document.createElement('div');

		this.eventBus = new VueVendorV2;
		this.timer = new Timer();

		this.messagesQueue = [];
		this.windowFocused = true;

		window.imDialogUploadTasks = [];
		window.imDialogMessagesTasks = [];

		this.messagesSet = false;

		//alert('Pause: open console for debug');

		this.initCore()
			.then(() => this.subscribeToEvents())
			.then(() => this.initComponentParams())
			.then(result => this.initLangAdditional(result))
			.then(result => this.initMobileEntity(result))
			.then(result => this.initMobileSettings(result))
			.then(() => this.initComponent())
			.then(() => this.initEnvironment())
			.then(() => this.initMobileEnvironment())
			.then(() => this.initUnsentStorage())
			.then(() => this.initPullClient())
			.then(() => this.initComplete())
		;
	}

	initCore()
	{
		return new Promise((resolve, reject) => {
			Core.ready().then(controller => {
				this.controller = controller;
				resolve();
			})
		});
	}

	subscribeToEvents()
	{
		EventEmitter.subscribe(EventType.dialog.messagesSet, this.onMessagesSet.bind(this));
	}

	initComponentParams()
	{
		return BX.componentParameters.init();
	}

	initLangAdditional(data)
	{
		const langAdditional = data.LANG_ADDITIONAL || {};

		console.log('0. initLangAdditional', langAdditional);

		return new Promise((resolve, reject) => {
			if (data.LANG_ADDITIONAL)
			{
				Object.keys(langAdditional).forEach(code => {
					if (typeof langAdditional[code] !== 'string')
					{
						return;
					}

					BX.message[code] = langAdditional[code];
				});
			}

			resolve(data);
		});
	}

	initMobileEntity(data)
	{
		console.log('1. initMobileEntity');

		return new Promise((resolve, reject) =>
		{
			if (data.DIALOG_ENTITY)
			{
				data.DIALOG_ENTITY = JSON.parse(data.DIALOG_ENTITY);
				if (data.DIALOG_TYPE === 'user')
				{
					this.controller.getStore().dispatch('users/set', data.DIALOG_ENTITY).then(() => {
						resolve(data);
					});
				}
				else if (data.DIALOG_TYPE === 'chat')
				{
					this.controller.getStore().dispatch('dialogues/set', data.DIALOG_ENTITY).then(() => {
						resolve(data);
					});
				}
			}
			else
			{
				resolve(data);
			}
		});
	}

	initMobileSettings(data)
	{
		console.log('2. initMobileSettings');

		// todo change to dynamic storage (LocalStorage web, PageParams for mobile)
		let serverVariables = LocalStorage.get(this.controller.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		this.storedEvents = data.STORED_EVENTS || [];

		return new Promise((resolve, reject) => {
			ApplicationStorage.getObject('settings.chat', {
				quoteEnable: ChatPerformance.isGestureQuoteSupported(),
				quoteFromRight: false,
				autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
				backgroundType: 'LIGHT_GRAY'
			}).then(options => {
				this.controller.getStore().dispatch('application/set', {
					dialog: {
						dialogId: data.DIALOG_ID
					},
					options: {
						quoteEnable: options.quoteEnable,
						quoteFromRight: options.quoteFromRight,
						autoplayVideo: options.autoplayVideo,
						darkBackground: ChatDialogBackground && ChatDialogBackground[options.backgroundType] && ChatDialogBackground[options.backgroundType].dark
					}
				}).then(() => resolve());
			})
		});
	}

	initComponent()
	{
		console.log('3. initComponent');

		this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));

		this.controller.addRestAnswerHandler(
			MobileRestAnswerHandler.create({
				store: this.controller.getStore(),
				controller: this.controller,
				context: this,
			})
		);

		let dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
		if (dialog)
		{
			this.controller.getStore().commit('application/set', {dialog: {
				chatId: dialog.chatId,
				diskFolderId: dialog.diskFolderId || 0
			}});
		}

		return this.controller.createVue(this, {
			el: this.rootNode,
			template: `<bx-mobile-im-component-dialog/>`,
		})
		.then(vue => {
			this.template = vue;
			return new Promise((resolve, reject) => resolve());
		});
	}

	initEnvironment()
	{
		console.log('4. initEnvironment');

		this.setTextareaMessage = Utils.debounce(this.controller.application.setTextareaMessage, 300, this.controller.application);

		return new Promise((resolve, reject) => resolve());
	}

	initMobileEnvironment()
	{
		console.log('5. initMobileEnvironment');

		BXMobileApp.UI.Page.Scroll.setEnabled(false);
		BXMobileApp.UI.Page.captureKeyboardEvents(true);

		BX.addCustomEvent("onKeyboardWillShow", () => {
			// EventEmitter.emit(EventType.dialog.beforeMobileKeyboard);
			this.controller.getStore().dispatch('application/set', {mobile: {
				keyboardShow: true,
			}});
		});

		BX.addCustomEvent("onKeyboardDidShow", () => {
			console.log('Keyboard was showed');
			EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.controller.application.getChatId(), duration: 300, cancelIfScrollChange: true});
		});

		BX.addCustomEvent("onKeyboardWillHide", () => {
			clearInterval(this.keyboardOpeningInterval);
			this.controller.getStore().dispatch('application/set', {mobile: {
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

		BXMobileApp.addCustomEvent("CallEvents::viewOpened", () => {
			console.warn('CallView show - disable read message');
			Vue.event.$emit('bitrixmobile:controller:blur');
		});

		BXMobileApp.addCustomEvent("CallEvents::viewClosed", () => {
			console.warn('CallView hide - enable read message');
			checkWindowFocused();
		});

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
					EventEmitter.emit(EventType.dialog.readVisibleMessages, {chatId: this.controller.application.getChatId()});
					EventEmitter.emit(EventType.dialog.scrollOnStart, {chatId: this.controller.application.getChatId()});
				}).catch(() => {
					this.processSendMessages();
				});
			}});
		});

		BX.addCustomEvent("onAppPaused", () => {
			this.windowFocused = false;
			Vue.event.$emit('bitrixmobile:controller:blur');
			//app.closeController();z
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
			Pull.emit({
				type: PullClient.SubscriptionType.Server,
				moduleId: this.pullMobileHandler.getModuleId(),
				data: {command: 'messageAdd', params: {...params, optionImportant: true}}
			});
		});

		BXMobileApp.UI.Page.TextPanel.getText((initialText) => {
			BXMobileApp.UI.Page.TextPanel.setParams(this.getKeyboardParams({
				text: initialText
			}));
		})

		this.changeChatKeyboardStatus();

		BX.MobileUploadProvider.setListener(this.executeUploaderEvent.bind(this));

		this.fileUpdateProgress = Utils.throttle((chatId, fileId, progress, size) => {
			this.controller.getStore().dispatch('files/update', {
				chatId: chatId,
				id: fileId,
				fields: {
					status: FileStatus.upload,
					size: size,
					progress: progress
				}
			});
		}, 500);

		if (!Utils.dialog.isChatId(this.controller.application.getDialogId()))
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
		else
		{
			this.chatShowUserCounter = false;
			setTimeout(() => {
				this.chatShowUserCounter = true;
				this.redrawHeader();
			}, 1500);
		}

		this.redrawHeader();

		this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());

		return new Promise((resolve, reject) => resolve());
	}

	initPullClient()
	{
		console.log('7. initPullClient');

		if (this.storedEvents && this.storedEvents.length > 0 && this.controller.application.isUnreadMessagesLoaded())
		{
			//sort events and get first 50 (to match unread messages cache size)
			this.storedEvents = this.storedEvents.sort((a, b) => {
				return a.message.id - b.message.id;
			});
			this.storedEvents = this.storedEvents.slice(0, 50);
			setTimeout(() => {
				this.storedEvents = this.storedEvents.filter(event => {
					BX.onCustomEvent('chatrecent::push::get', [event]);
					return false;
				});
				// scroll to first push message in dialog before load all messages from server
				EventEmitter.emit(EventType.dialog.scrollToBottom, {
					chatId: this.controller.application.getChatId(),
					duration: 300,
					cancelIfScrollChange: true
				});
			}, 50);
		}

		Pull.subscribe(
			this.pullMobileHandler = new MobileImCommandHandler({
				store: this.controller.getStore(),
				controller: this.controller,
				dialog: this,
			})
		);

		Pull.subscribe({
			type: PullClient.SubscriptionType.Status,
			callback: this.eventStatusInteraction.bind(this)
		});

		if (!Utils.dialog.isChatId(this.controller.application.getDialogId()))
		{
			Pull.subscribe({
				type: PullClient.SubscriptionType.Online,
				callback: this.eventOnlineInteraction.bind(this)
			});
		}

		return new Promise((resolve, reject) => resolve());
	}

	initComplete()
	{
		console.log('8. initComplete')
		this.controller.getStore().subscribe(mutation => this.eventStoreInteraction(mutation));

		this.inited = true;
		this.initPromise.resolve(this);

		BXMobileApp.Events.postToComponent("chatdialog::init::complete", [{
			dialogId: this.controller.application.getDialogId()
		}, true], 'im.recent');

		BXMobileApp.Events.postToComponent("chatdialog::init::complete", [{
			dialogId: this.controller.application.getDialogId()
		}, true], 'im.messenger');

		return this.requestData();
	}

	cancelUnsentFile(fileId)
	{
		const taskId = `imDialogFileUpload|${fileId}`;

		BX.MobileUploadProvider.cancelTasks([taskId]);

		window.imDialogUploadTasks = window.imDialogUploadTasks.filter(entry => {
			return taskId !== entry.taskId;
		});
	}

	initUnsentStorage()
	{
		console.log('6. initUnsentStorage');

		return new Promise(resolve => {
			const filesPromise = this.loadUnsentFiles();
			const messagesPromise = this.loadUnsentMessages();

			Promise.all([filesPromise, messagesPromise]).then(resolve);
		});

	}

	loadUnsentMessages()
	{
		const userId = this.controller.application.getUserId();
		const dialogId = this.controller.application.getDialogId();
		const storageId = `${STORAGE_PREFIX}_${userId}`;

		return ApplicationStorage.getObject(MESSAGES_STORAGE_NAME, {}, storageId)
			.then(tasks => {
				for (const queueType in tasks)
				{
					if (queueType === dialogId)
					{
						tasks[queueType].forEach(task => {
							if (dialogId === task.extra.dialogId)
							{
								window.imDialogMessagesTasks.push(task);
							}
						});
					}
				}
			});
	}

	loadUnsentFiles()
	{
		const userId = this.controller.application.getUserId();
		const dialogId = this.controller.application.getDialogId();
		const storageId = `${STORAGE_PREFIX}_${userId}`;

		return ApplicationStorage.getObject(FILES_STORAGE_NAME, {}, storageId)
			.then(result => {
				Object.values(result).forEach(task => {
					if (
						typeof task.eventData.file !== 'undefined'
						&& dialogId === task.eventData.file.params.dialogId
					)
					{
						window.imDialogUploadTasks.push(task);
					}
				});

				// we need it to show a progress bar for the uploading
				if (window.imDialogUploadTasks.length > 0)
				{
					BX.MobileUploadProvider.registerTaskLoaders(window.imDialogUploadTasks);
				}
			});
	}

	ready()
	{
		if (this.inited)
		{
			let promise = new BX.Promise;
			promise.resolve(this);

			return promise;
		}

		return this.initPromise;
	}

	requestData()
	{
		console.log('-> requestData');
		if (this.requestDataSend)
		{
			return this.requestDataSend;
		}

		this.timer.start('data', 'load', .5, () => {
			console.warn("ChatDialog.requestData: slow connection show progress icon");
			app.titleAction("setParams", {useProgress: true, useLetterImage: false});
		});

		this.requestDataSend = new Promise((resolve, reject) =>
		{
			let query = {
				[RestMethodHandler.mobileBrowserConstGet]: [RestMethod.mobileBrowserConstGet, {}],
				[RestMethodHandler.imChatGet]: [RestMethod.imChatGet, {dialog_id: this.controller.application.getDialogId()}],
				[RestMethodHandler.imDialogMessagesGetInit]: [RestMethod.imDialogMessagesGet, {
					dialog_id: this.controller.application.getDialogId(),
					limit: this.controller.application.getRequestMessageLimit(),
					convert_text: 'Y'
				}],
				[RestMethodHandler.imRecentUnread]: [RestMethod.imRecentUnread, {dialog_id: this.controller.application.getDialogId(), action: 'N'}],
				[RestMethodHandler.imCallGetCallLimits]: [RestMethod.imCallGetCallLimits, {}],
			};
			if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
			{
				query[RestMethodHandler.imUserGet] = [RestMethod.imUserGet, {}];
			}
			else
			{
				query[RestMethodHandler.imUserListGet] = [RestMethod.imUserListGet, {id: [this.controller.application.getUserId(), this.controller.application.getDialogId()]}];
			}

			this.controller.restClient.callBatch(query, (response) =>
			{
				if (!response)
				{
					this.requestDataSend = null;
					this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

					resolve();
					return false;
				}
				console.log('<-- requestData', response);

				let constGet = response[RestMethodHandler.mobileBrowserConstGet];
				if (constGet.error())
				{
					console.warn('Error load dialog', constGet.error().ex.error, constGet.error().ex.error_description)
					console.warn('Try connect...');
					setTimeout(() => this.requestData(), 5000);
				}
				else
				{
					this.controller.executeRestAnswer(RestMethodHandler.mobileBrowserConstGet, constGet);
				}

				let callLimits = response[RestMethodHandler.imCallGetCallLimits];
				if (callLimits && !callLimits.error())
				{
					this.controller.executeRestAnswer(RestMethodHandler.imCallGetCallLimits, callLimits);
				}

				let userGet = response[RestMethodHandler.imUserGet];
				if (userGet && !userGet.error())
				{
					this.controller.executeRestAnswer(RestMethodHandler.imUserGet, userGet);
				}

				let userListGet = response[RestMethodHandler.imUserListGet];
				if (userListGet && !userListGet.error())
				{
					this.controller.executeRestAnswer(RestMethodHandler.imUserListGet, userListGet);
				}

				let chatGetResult = response[RestMethodHandler.imChatGet];
				this.controller.executeRestAnswer(RestMethodHandler.imChatGet, chatGetResult);
				this.redrawHeader();

				let dialogMessagesGetResult = response[RestMethodHandler.imDialogMessagesGetInit];
				if (dialogMessagesGetResult.error())
				{
					//this.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);
				}
				else
				{
					app.titleAction("setParams", {useProgress: false, useLetterImage: true});
					this.timer.stop('data', 'load', true);

					this.controller.getStore().dispatch('dialogues/saveDialog', {
						dialogId: this.controller.application.getDialogId(),
						chatId: this.controller.application.getChatId(),
					});

					if (this.controller.pullBaseHandler)
					{
						this.controller.pullBaseHandler.option.skip = false;
					}

					this.controller.getStore().dispatch('application/set', {dialog: {
						enableReadMessages: true
					}}).then(() => {
						this.controller.executeRestAnswer(RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
					});

					this.processSendMessages();
				}

				this.requestDataSend = null;
				resolve();

			}, false, false, Utils.getLogTrackingParams({name: 'mobile.im.dialog', dialog: this.controller.application.getDialogData()}));
		});

		return this.requestDataSend;
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
				this.controller.getStore().dispatch('files/update', {
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

			this.controller.getStore().dispatch('files/update', {
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
			// show file error only if it is not internet connection error
			if (eventData?.error?.error?.code !== NO_INTERNET_CONNECTION_ERROR_CODE)
			{
				this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
			}
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
		if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
		{
			headerProperties = this.getChatHeaderParams();
			this.changeChatKeyboardStatus();
		}
		else
		{
			headerProperties = this.getUserHeaderParams();
		}

		if (!headerProperties)
		{
			return false;
		}

		this.setHeaderButtons();

		if (!this.headerMenuInited)
		{
			BXMobileApp.UI.Page.TopBar.title.params.useLetterImage = true;
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

	setIosInset()
	{
		if (
			!Utils.platform.isIos()
			|| Application.getApiVersion() <= 39
		)
		{
			return false;
		}

		const getScrollElement = () =>
		{
			return document.getElementsByClassName("bx-im-dialog-list")[0];
		}

		const setTopInset = (scrollElement) =>
		{
			scrollElement.style.paddingTop = window.safeAreaInsets.top + "px"
		}

		const onScrollChange = () =>
		{
			const scrollElement = getScrollElement();
			if (!scrollElement)
			{
				return false;
			}

			if (scrollElement.scrollTop <= window.safeAreaInsets.top)
			{
				setTopInset(scrollElement);
				scrollElement.removeEventListener("scroll", onScrollChange);
			}
		}

		if (this.iosInsetEventSetted)
		{
			return true;
		}

		this.iosInsetEventSetted = true;

		const onInsetsChanged = Utils.debounce(() =>
		{
			const scrollElement = getScrollElement();
			if (!scrollElement)
			{
				return false;
			}

			if (
				window.safeAreaInsets
				&& scrollElement.scrollTop <= window.safeAreaInsets.top
			)
			{
				setTopInset(scrollElement);
			}
			else
			{
				scrollElement.removeEventListener("scroll", onScrollChange);
				scrollElement.addEventListener("scroll", onScrollChange);
			}
		}, 100);

		BXMobileApp.addCustomEvent("onInsetsChanged", onInsetsChanged);
		setTimeout(onInsetsChanged, 1000);

		return true;
	}

	getUserHeaderParams()
	{
		let user = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId());
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
			showLastDate = Utils.user.getLastDateText(user, this.getLocalize());
		}

		if (showLastDate)
		{
			result.desc = showLastDate;
		}
		else
		{
			if (user.extranet)
			{
				result.desc = this.getLocalize('IM_LIST_EXTRANET');
			}
			else if (user.workPosition)
			{
				result.desc = user.workPosition;
			}
			else
			{
				result.desc = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_USER');
			}
		}

		return result;
	}

	getChatHeaderParams()
	{
		let dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
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

		if (dialog.entityType === 'GENERAL')
		{
			result.avatar = encodeURI(
				this.controller.getHost() + "/bitrix/mobileapp/immobile/components/im/messenger/images/avatar_general_x3.png"
			);
		}

		result.name = dialog.name;

		let chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_CHAT_NEW');
		if (this.chatShowUserCounter && this.getLocalize()['MOBILE_HEADER_MENU_CHAT_USER_COUNT'])
		{
			chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_USER_COUNT').replace('#COUNT#', dialog.userCounter);
		}
		else if (this.getLocalize()['MOBILE_HEADER_MENU_CHAT_TYPE_'+dialog.type.toUpperCase()+'_NEW'])
		{
			chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_'+dialog.type.toUpperCase()+'_NEW');
		}
		result.desc = chatTypeTitle;

		if (dialog.entityType === 'SUPPORT24_QUESTION')
		{
			result.avatar = encodeURI(
				this.controller.getHost() + "/bitrix/mobileapp/immobile/components/im/messenger/images/avatar_24_question_x3.png"
			);
			result.desc = '';
		}

		console.warn(result);

		return result;
	}

	changeChatKeyboardStatus()
	{
		let dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
		if (!dialog || !dialog.init)
		{
			BXMobileApp.UI.Page.TextPanel.show();
			return true;
		}

		let keyboardShow = true;

		if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.application.getUserId()))
		{
			keyboardShow = false;
		}
		else if (dialog.restrictions.send === false)
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

	setHeaderButtons()
	{
		if (this.callMenuSetted)
		{
			return true;
		}

		if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
		{
			let dialogData = this.controller.application.getDialogData();
			if (!dialogData.init)
			{
				return false;
			}

			let isAvailableChatCall = Application.getApiVersion() >= 36;
			let maxParticipants = this.controller.application.getData().call.maxParticipants;
			if (
				dialogData.userCounter > maxParticipants
				|| !isAvailableChatCall
				|| dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST'
			)
			{
				if (dialogData.type !== DialogType.call && dialogData.restrictions.extend)
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
				else
				{
					app.exec("setRightButtons", {items: []});
				}

				this.callMenuSetted = true;
				return true;
			}

			if (!dialogData.restrictions.call)
			{
				app.exec("setRightButtons", {items: []});

				this.callMenuSetted = true;
				return true;
			}
		}
		else
		{
			let userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
			if (!userData.init)
			{
				return false;
			}

			if (
				!userData
				|| userData.bot
				|| userData.network
				|| this.controller.application.getUserId() === parseInt(this.controller.application.getDialogId())
			)
			{
				app.exec("setRightButtons", {items: []});

				this.callMenuSetted = true;
				return true;
			}
		}

		app.exec("setRightButtons", {items: [
			{
				type: "call_audio",
				callback: () => {
					if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
					{
						BXMobileApp.Events.postToComponent("onCallInvite", {
							dialogId: this.controller.application.getDialogId(),
							video: false,
							chatData: this.controller.application.getDialogData()
						}, "calls")
					}
					else
					{
						let userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
						BXMobileApp.Events.postToComponent("onCallInvite", {
							userId: this.controller.application.getDialogId(),
							video: false,
							userData: {[userData.id]: userData}
						}, "calls");
					}
				}
			},
			{
				type: "call_video",
				badgeCode: "call_video",
				callback: () =>
				{
					fabric.Answers.sendCustomEvent("vueChatCallVideoButton", {});

					if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
					{
						BXMobileApp.Events.postToComponent("onCallInvite", {
							dialogId: this.controller.application.getDialogId(),
							video: true,
							chatData: this.controller.application.getDialogData()
						}, "calls")
					}
					else
					{
						BXMobileApp.Events.postToComponent("onCallInvite", {
							dialogId: this.controller.application.getDialogId(),
							video: true,
							userData: {
								[this.controller.application.getDialogId()]: this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true)
							}
						}, "calls")
					}
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
			scriptPath: "/mobileapp/jn/im:im.chat.user.list/?version="+BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
			params: {
				"DIALOG_ID": this.controller.application.getDialogId(),
				"DIALOG_OWNER_ID": this.controller.application.getDialogData().ownerId,
				"USER_ID": this.controller.application.getUserId(),
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

		let userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);

		if (
			userData.phones.personalMobile
			|| userData.phones.workPhone
			|| userData.phones.personalPhone
			|| userData.phones.innerPhone
		)
		{
			BackdropMenu
				.create('im.dialog.menu.call|'+this.controller.application.getDialogId())
				.setItems([
					BackdropMenuItem.create('audio')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL'))
					,
					BackdropMenuItem.create('personalMobile')
						.setTitle(userData.phones.personalMobile)
						.setSubTitle(this.getLocalize('MOBILE_MENU_CALL_MOBILE'))
						.skip(!userData.phones.personalMobile)
					,
					BackdropMenuItem.create('workPhone')
						.setTitle(userData.phones.workPhone)
						.setSubTitle(this.getLocalize('MOBILE_MENU_CALL_WORK'))
						.skip(!userData.phones.workPhone)
					,
					BackdropMenuItem.create('personalPhone')
						.setTitle(userData.phones.personalPhone)
						.setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE'))
						.skip(!userData.phones.personalPhone)
					,
					BackdropMenuItem.create('innerPhone')
						.setTitle(userData.phones.innerPhone)
						.setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE'))
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
							userId: this.controller.application.getDialogId(),
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
						// 	.create('im.dialog.menu.call.submenu|'+this.controller.application.getDialogId())
						// 	.setItems([
						// 		BackdropMenuItem.create('number')
						// 			.setType(BackdropMenuItemType.info)
						// 			.setTitle(this.getLocalize("MOBILE_MENU_CALL_TO")
						// 			.replace('#PHONE_NUMBER#', user.phones[params.id]))
						// 			.setHeight(50)
						// 			.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setFontStyle('bold')))
						// 			.setDisabled(),
						// 		BackdropMenuItem.create('telephony').setTitle(this.getLocalize("MOBILE_CALL_BY_B24")),
						// 		BackdropMenuItem.create('device').setTitle(this.getLocalize("MOBILE_CALL_BY_MOBILE")),
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
				userId: this.controller.application.getDialogId(),
				video: false,
				userData: {[this.controller.application.getDialogId()]: userData}
			}, "calls");
		}
	}

	leaveChat(confirm = false)
	{
		if (!confirm)
		{
			app.confirm({
				title: this.getLocalize('MOBILE_HEADER_MENU_LEAVE_CONFIRM'),
				text: '',
				buttons: [
					this.getLocalize('MOBILE_HEADER_MENU_LEAVE_YES'),
					this.getLocalize('MOBILE_HEADER_MENU_LEAVE_NO')
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

		let dialogId = this.controller.application.getDialogId();

		this.controller.restClient.callMethod(RestMethod.imChatLeave, {DIALOG_ID: dialogId}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imChatLeave,
			dialog: this.controller.application.getDialogData(dialogId)
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
			scriptPath: "/mobileapp/jn/im:im.chat.user.selector/?version="+BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
			params: {
				"DIALOG_ID": this.controller.application.getDialogId(),
				"USER_ID": this.controller.application.getUserId(),

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
					backdrop: {showOnTop: true}
				}
			}
		}, false);
	}

	getItemsForAddUserDialog()
	{
		let items = [];
		let itemsIndex = {};

		if (this.widgetCache.recentList.length > 0)
		{
			this.widgetCache.recentList.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}

				if (element.type !== 'user')
				{
					return false;
				}

				if (element.user.network || element.user.connector)
				{
					return false;
				}

				items.push(element.user);
				itemsIndex[element.id] = true;

				return true;
			});
		}

		this.widgetCache.colleaguesList.map(element =>
		{
			if (!element || itemsIndex[element.id])
			{
				return false;
			}

			if (element.network || element.connector)
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

			if (element.type !== 'user')
			{
				return false;
			}

			if (element.user.network || element.user.connector)
			{
				return false;
			}

			items.push(element.user);
			itemsIndex[element.id] = true;

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
			if (data.payload.dialogId !== this.controller.application.getDialogId())
			{
				return;
			}

			if (
				typeof data.payload.fields.name !== 'undefined'
				|| typeof data.payload.fields.userCounter !== 'undefined'
			)
			{
				if (typeof data.payload.fields.userCounter !== 'undefined')
				{
					this.callMenuSetted = false;
				}
				this.redrawHeader();
			}

			if (
				typeof data.payload.fields.counter !== 'undefined'
				&& typeof data.payload.dialogId !== 'undefined'
			)
			{
				BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
					dialogId: data.payload.dialogId,
					counter: data.payload.fields.counter,
				}, true], 'im.recent');

				BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
					dialogId: data.payload.dialogId,
					counter: data.payload.fields.counter,
				}, true], 'im.messenger');
			}
		}
		else if (data.type === 'dialogues/set')
		{
			data.payload.forEach((dialog) => {
				if (dialog.dialogId !== this.controller.application.getDialogId())
				{
					return;
				}

				BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
					dialogId: dialog.dialogId,
					counter: dialog.counter,
				}, true], 'im.recent');

				BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
					dialogId: dialog.dialogId,
					counter: dialog.counter,
				}, true], 'im.messenger');
			});
		}
	}

	eventStatusInteraction(data)
	{
		if (data.status === PullClient.PullStatus.Online)
		{
			// restart background tasks (messages and files) to resend files after we got connection again
			if (this.messagesSet)
			{
				BXMobileApp.Events.postToComponent('chatbackground::task::restart', [], 'background');
				BXMobileApp.Events.postToComponent('chatuploader::task::restart', [], 'background');
			}

			if (this.pullRequestMessage)
			{
				this.controller.pullBaseHandler.option.skip = true;

				this.getDialogUnread().then(() => {
					this.controller.pullBaseHandler.option.skip = false;
					this.processSendMessages();
					EventEmitter.emit(EventType.dialog.readVisibleMessages, {chatId: this.controller.application.getChatId()});
					EventEmitter.emit(EventType.dialog.scrollOnStart, {chatId: this.controller.application.getChatId()});
				}).catch(() => {
					this.controller.pullBaseHandler.option.skip = false;
					this.processSendMessages();
				});

				this.pullRequestMessage = false;
			}
			else
			{
				EventEmitter.emit(EventType.dialog.readMessage);
				this.processSendMessages();
			}
		}
		else if (data.status === PullClient.PullStatus.Offline)
		{
			this.pullRequestMessage = true;
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

				this.controller.getStore().dispatch('users/update', {
					id: data.params.users[userId].id,
					fields: data.params.users[userId]
				});

				if (userId.toString() === this.controller.application.getDialogId())
				{
					this.redrawHeader();
				}
			}
		}
	}

/* endregion 02. Push & Pull */

	getKeyboardParams(params = {})
	{
		let dialogData = this.controller.application.getDialogData();

		let initialText = dialogData? dialogData.textareaMessage: '';
		initialText = initialText || params.text;

		let siteDir = this.getLocalize('SITE_DIR');

		return {
			text: initialText,
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
						url: siteDir + "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId="+this.controller.application.getUserId(),
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
					this.hideSmiles();

					let editId = this.controller.getStore().getters['dialogues/getEditId'](this.controller.application.getDialogId());
					if (editId)
					{
						this.updateMessage(editId, text);
					}
					else
					{
						this.addMessage(text);
					}
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
						this.controller.application.startWriting();
					}

					if (text.length === 0)
					{
						this.setTextareaMessage({message: ''});
						this.controller.application.stopWriting();
					}
					else
					{
						this.setTextareaMessage({message: text});
					}
				}
				else if (data.event === "onSmileSelect")
				{
					this.controller.showSmiles();
				}
				else if (Application.getPlatform() !== "android")
				{
					if (data.event === "getFocus")
					{
						if (Utils.platform.isIos() && Utils.platform.getIosVersion() > 12)
						{
							EventEmitter.emit(EventType.dialog.scrollToBottom, {
								chatId: this.controller.application.getChatId(),
								duration: 300, cancelIfScrollChange: true
							});
						}
					}
					else if (data.event === "removeFocus")
					{

					}
				}
			},
		}
	}

/* region 04. Rest methods */

	addMessage(text, file = null, messageUuid = null)
	{
		if (!text && !file)
		{
			return false;
		}

		const uuid = messageUuid || ChatUtils.getUuidv4();

		const quoteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.controller.application.getDialogId());
		if (quoteId)
		{
			const quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), quoteId);
			if (quoteMessage)
			{
				let user = null;
				if (quoteMessage.authorId)
				{
					user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);
				}

				const files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());

				const message = [];
				message.push('-'.repeat(54));
				message.push((user && user.name? user.name: this.getLocalize('MOBILE_CHAT_SYSTEM_MESSAGE'))+' ['+Utils.date.format(quoteMessage.date, null, this.getLocalize())+']');
				message.push(Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.getLocalize()));
				message.push('-'.repeat(54));
				message.push(text);
				text = message.join("\n");

				this.quoteMessageClear();
			}
		}

		console.warn('addMessage', text, file, uuid);

		if (!this.controller.application.isUnreadMessagesLoaded())
		{
			this.sendMessage({
				id: uuid,
				chatId: this.controller.application.getChatId(),
				dialogId: this.controller.application.getDialogId(),
				text,
				file
			});
			this.processSendMessages();

			return true;
		}

		this.controller.getStore().commit('application/increaseDialogExtraCount');

		const params = {};
		if (file)
		{
			params.FILE_ID = [file.id];
		}

		this.controller.getStore().dispatch('messages/add', {
			id: uuid,
			chatId: this.controller.application.getChatId(),
			authorId: this.controller.application.getUserId(),
			text: text,
			params,
			sending: !file,
		}).then(messageId => {
			EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.controller.application.getChatId(), cancelIfScrollChange: true});

			this.messagesQueue.push({
				id: messageId,
				chatId: this.controller.application.getChatId(),
				dialogId: this.controller.application.getDialogId(),
				text,
				file,
				sending: false
			});

			if (this.controller.application.getChatId())
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

		const fileMessageUuid = ChatUtils.getUuidv4();

		console.warn('addFile', file, text, fileMessageUuid);

		if (!this.controller.application.isUnreadMessagesLoaded())
		{
			this.addMessage(text, {id: 0, source: file}, fileMessageUuid);
			return true;
		}

		this.controller.getStore().dispatch('files/add', this.controller.application.prepareFilesBeforeSave({
			chatId: this.controller.application.getChatId(),
			authorId: this.controller.application.getUserId(),
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
			authorName: this.controller.application.getCurrentUser().name,
			urlPreview: !file.preview? '': file.preview.url,
		})).then(fileId => this.addMessage(text, {id: fileId, ...file}, fileMessageUuid));

		return true;
	}

	cancelUploadFile(fileId)
	{
		this.cancelUnsentFile(fileId);
		let element = this.messagesQueue.find(element => element.file && element.file.id === fileId);
		if (!element)
		{
			const messages = this.controller.getStore().getters['messages/get'](this.controller.application.getChatId());
			const messageToDelete = messages.find(element => element.params.FILE_ID && element.params.FILE_ID.includes(fileId));
			if (messageToDelete)
			{
				element = {
					id: messageToDelete.id,
					chatId: messageToDelete.chatId,
					file: { id: messageToDelete.params.FILE_ID[0] }
				};
			}
		}

		if (element)
		{
			BX.MobileUploadProvider.cancelTasks(['imDialogFileUpload|'+fileId]);

			this.controller.getStore().dispatch('messages/delete', {
				chatId: element.chatId,
				id: element.id,
			}).then(() => {
				this.controller.getStore().dispatch('files/delete', {
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

		this.controller.getStore().dispatch('messages/actionStart', {
			chatId: element.chatId,
			id: element.id
		}).then(() => {
			this.controller.getStore().dispatch('files/update', {
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
					if (this.controller.application.getDiskFolderId())
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
		this.controller.application.readMessageExecute(this.controller.application.getChatId(), true);
		return true;
	}

	sendMessage(message)
	{
		message.text = message.text.replace(/^([-]{21}\n)/gm, '-'.repeat(54)+'\n');

		this.controller.application.stopWriting(message.dialogId);

		window.imDialogMessagesTasks.push({taskId: 'sendMessage|'+message.id });

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

		window.imDialogUploadTasks.push({taskId: 'imDialogFileUpload|'+message.file.id,});

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
			folderId: this.controller.application.getDiskFolderId(),
			taskId: 'imDialogFileUpload|'+message.file.id,
			onDestroyEventName: 'onimdiskmessageaddsuccess'
		}]);
	}

	fileError(chatId, fileId, messageId = 0)
	{
		this.controller.getStore().dispatch('files/update', {
			chatId: chatId,
			id: fileId,
			fields: {
				status: FileStatus.error,
				progress: 0
			}
		});
		if (messageId)
		{
			this.controller.getStore().dispatch('messages/actionError', {
				chatId: chatId,
				id: messageId,
				retry: true,
			});
		}
	}

	fileCommit(params, message)
	{
		const queryParams = {
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

		BXMobileApp.Events.postToComponent('chatbackground::task::add', [
			'uploadFileFromDisk|'+message.id,
			[RestMethod.imDiskFileCommit, queryParams],
			message
		], 'background');
	}

	requestDiskFolderId()
	{
		if (this.flagRequestDiskFolderIdSended || this.controller.application.getDiskFolderId())
		{
			return true;
		}

		this.flagRequestDiskFolderIdSended = true;

		this.controller.restClient.callMethod(RestMethod.imDiskFolderGet, {chat_id: this.controller.application.getChatId()}).then(response => {
			this.controller.executeRestAnswer(RestMethodHandler.imDiskFolderGet, response);
			this.flagRequestDiskFolderIdSended = false;
			this.processSendMessages();
		}).catch(error => {
			this.flagRequestDiskFolderIdSended = false;
			this.controller.executeRestAnswer(RestMethodHandler.imDiskFolderGet, error);
		});

		return true;
	}

	getDialogHistory(lastId, limit = this.controller.application.getRequestMessageLimit())
	{
		this.controller.restClient.callMethod(RestMethod.imDialogMessagesGet, {
			'CHAT_ID': this.controller.application.getChatId(),
			'LAST_ID': lastId,
			'LIMIT': limit,
			'CONVERT_TEXT': 'Y'
		}).then(result => {
			this.controller.executeRestAnswer(RestMethodHandler.imDialogMessagesGet, result);
			// this.controller.application.emit(EventType.dialog.requestHistoryResult, {count: result.data().messages.length});
		}).catch(result => {
			// this.controller.emit(EventType.dialog.requestHistoryResult, {error: result.error().ex});
		});
	}

	getDialogUnread(lastId, limit = this.controller.application.getRequestMessageLimit())
	{
		if (this.promiseGetDialogUnreadWait)
		{
			return this.promiseGetDialogUnread;
		}

		this.promiseGetDialogUnread = new BX.Promise();
		this.promiseGetDialogUnreadWait = true;

		if (!lastId)
		{
			lastId = this.controller.getStore().getters['messages/getLastId'](this.controller.application.getChatId());
		}

		if (!lastId)
		{
			// this.controller.application.emit(EventType.dialog.requestUnreadResult, {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});

			this.promiseGetDialogUnread.reject();
			this.promiseGetDialogUnreadWait = false;

			return this.promiseGetDialogUnread;
		}

		EventEmitter.emitAsync(EventType.dialog.readMessage, {id: lastId, skipAjax: true}).then(() =>
		{
			this.timer.start('data', 'load', .5, () => {
				console.warn("ChatDialog.requestData: slow connection show progress icon");
				app.titleAction("setParams", {useProgress: true, useLetterImage: false});
			});

			let query = {
				[RestMethodHandler.imDialogRead]: [RestMethod.imDialogRead, {
					dialog_id: this.controller.application.getDialogId(),
					message_id: lastId
				}],
				[RestMethodHandler.imChatGet]: [RestMethod.imChatGet, {
					dialog_id: this.controller.application.getDialogId()
				}],
				[RestMethodHandler.imDialogMessagesGetUnread]: [RestMethod.imDialogMessagesGet, {
					chat_id: this.controller.application.getChatId(),
					first_id: lastId,
					limit: limit,
					convert_text: 'Y'
				}]
			};

			this.controller.restClient.callBatch(query, (response) =>
			{
				if (!response)
				{
					this.promiseGetDialogUnread.reject();
					this.promiseGetDialogUnreadWait = false;

					return false;
				}

				let chatGetResult = response[RestMethodHandler.imChatGet];
				if (!chatGetResult.error())
				{
					this.controller.executeRestAnswer(RestMethodHandler.imChatGet, chatGetResult);
				}

				let dialogMessageUnread = response[RestMethodHandler.imDialogMessagesGetUnread];
				if (!dialogMessageUnread.error())
				{
					dialogMessageUnread = dialogMessageUnread.data();
					this.controller.getStore().dispatch('users/set', dialogMessageUnread.users);
					this.controller.getStore().dispatch('files/set', this.controller.application.prepareFilesBeforeSave(dialogMessageUnread.files));
					this.controller.getStore().dispatch('messages/setAfter', dialogMessageUnread.messages).then(() => {
						app.titleAction("setParams", { useProgress: false, useLetterImage: true });
						this.timer.stop('data', 'load', true);

						this.promiseGetDialogUnread.fulfill(response);
						this.promiseGetDialogUnreadWait = false;

						return true;
					});
				}
				else
				{
					this.promiseGetDialogUnread.reject();
					this.promiseGetDialogUnreadWait = false;

					return false;
				}

			}, false, false, Utils.getLogTrackingParams({name: RestMethodHandler.imDialogMessagesGetUnread, dialog: this.controller.application.getDialogData()}));
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
			chatId: this.controller.application.getChatId(),
			dialogId: this.controller.application.getDialogId(),
			text: message.text,
			sending: false
		});

		this.controller.application.setSendingMessageFlag(message.id);

		this.processSendMessages();
	}

	openProfile(userId)
	{
		BXMobileApp.Events.postToComponent("onUserProfileOpen", [userId, {backdrop: true}], 'communication');
	}

	openDialog(dialogId)
	{
		BXMobileApp.Events.postToComponent("onOpenDialog", [{dialogId}, true], 'im.recent');
		BXMobileApp.Events.postToComponent('ImMobile.Messenger.Dialog:open', [{dialogId}], 'im.messenger');
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

		this.controller.getStore().dispatch('messages/update', {
			id: message.id,
			chatId: message.chatId,
			fields: {
				blink: true
			}
		});

		let currentUser = this.controller.application.getCurrentUser();
		let dialog = this.controller.application.getDialogData();
		let messageUser = message.authorId > 0? this.controller.getStore().getters['users/get'](message.authorId, true): null;

		this.messageMenuInstance = BackdropMenu
			.create('im.dialog.menu.mess|'+this.controller.application.getDialogId())
			.setTestId('im-dialog-menu-mess')
			.setItems([
				BackdropMenuItem.create('reply')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_REPLY'))
					.setIcon(BackdropMenuIcon.reply)
					.skip((message) => {
						let dialog = this.controller.application.getDialogData();
						if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.application.getUserId()))
						{
							return true;
						}

						return !message.authorId || message.authorId === this.controller.application.getUserId()
					})
				,

				BackdropMenuItem.create('copy')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_COPY'))
					.setIcon(BackdropMenuIcon.copy)
					.skip((message) => message.params.IS_DELETED === 'Y')
				,

				BackdropMenuItem.create('quote')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_QUOTE'))
					.setIcon(BackdropMenuIcon.quote)
					.skip((message) =>
					{
						let dialog = this.controller.application.getDialogData();
						if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.application.getUserId()))
						{
							return true;
						}

						return message.params.IS_DELETED === 'Y';
					})
				,

				BackdropMenuItem.create('unread')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_UNREAD'))
					.setIcon(BackdropMenuIcon.unread)
					.skip((message) => message.authorId === this.controller.application.getUserId() || message.unread)
				,

				BackdropMenuItem.create('read')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_READ'))
					.setIcon(BackdropMenuIcon.checked)
					.skip((message) => !message.unread)
				,

				BackdropMenuItem.create('edit')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_EDIT'))
					.setIcon(BackdropMenuIcon.edit)
					.skip((message) => message.authorId !== this.controller.application.getUserId() || message.params.IS_DELETED === 'Y')
				,

				BackdropMenuItem.create('share')
					.setType(BackdropMenuItemType.menu)
					.setIcon(BackdropMenuIcon.circle_plus)
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_MENU'))
					.disableClose()
					.skip(currentUser.extranet || dialog.type === 'announcement')
				,

				BackdropMenuItem.create('profile')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_PROFILE'))
					.setIcon(BackdropMenuIcon.user)
					.skip((message) => {

						if (message.authorId <= 0 || !messageUser)
							return true;

						if (!Utils.dialog.isChatId(this.controller.application.getDialogId()))
							return true;

						if (message.authorId === this.controller.application.getUserId())
							return true;

						if (
							messageUser.externalAuthId === 'imconnector'
							|| messageUser.externalAuthId === 'call'
						)
						{
							return true;
						}

						return  false;
					})
				,

				BackdropMenuItem.create('delete')
					.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_DELETE'))
					.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setColor('#c50000')))
					.setIcon(BackdropMenuIcon.trash)
					.skip((message) => message.authorId !== this.controller.application.getUserId() || message.params.IS_DELETED === 'Y')
				,
			])
			.setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'))
			.setEventListener((name, params, message, backdrop) => {
				if (name === 'destroyed')
				{
					Vue.event.$emit('bitrixmobile:controller:focus');
				}

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
					this.quoteMessageClear();
					this.quoteMessage(message.id);
				}
				else if (params.id === 'edit')
				{
					this.quoteMessageClear();
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
					EventEmitter.emit(EventType.dialog.readMessage, {id: message.id});
				}
				else if (params.id === 'share')
				{
					let dialog = this.controller.application.getDialogData();
					let subMenu = BackdropMenu
						.create('im.dialog.menu.mess.submenu|'+this.controller.application.getDialogId())
						.setTestId('im-dialog-menu-mess-submenu-share')
						.setItems([
							BackdropMenuItem.create('share_task')
								.setIcon(BackdropMenuIcon.task)
								.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_TASK'))
							,
							BackdropMenuItem.create('share_post')
								.setIcon(BackdropMenuIcon.lifefeed)
								.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_POST_NEWS'))
							,
							BackdropMenuItem.create('share_chat')
								.setIcon(BackdropMenuIcon.chat)
								.setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_CHAT'))
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
						this.openProfile(this.controller.application.getDialogId());
					}
					else if (params.id === 'user_list')
					{
						this.openUserList({listType: 'USERS', title: this.getLocalize('MOBILE_HEADER_MENU_USER_LIST'), backdrop: true});
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
						this.controller.application.muteDialog();
					}
					else if (params.id === 'call_chat_call')
					{
						BX.MobileTools.phoneTo(this.controller.application.getDialogData().entityId);
					}
					else if (params.id === 'goto_crm')
					{
						let crmData = this.controller.application.getDialogCrmData();
						let openWidget = BX.MobileTools.resolveOpenFunction('/crm/'+crmData.entityType+'/details/'+crmData.entityId+'/');
						if (openWidget)
						{
							openWidget();
						}
					}
					else if (params.id === 'reload')
					{
						(new BXMobileApp.UI.NotificationBar({
							message: this.getLocalize('MOBILE_HEADER_MENU_RELOAD_WAIT'),
							color: "#d920b0ff",
							textColor: "#ffffff",
							groupId: "refresh",
							useLoader: true,
							maxLines: 1,
							align: "center",
							hideOnTap: true
						}, "copy")).show();

						this.controller.getStoreBuilder().clearDatabase();

						reload();
					}
				})
			;
		}

		if (Utils.dialog.isChatId(this.controller.application.getDialogId()))
		{
			let dialogData = this.controller.application.getDialogData();
			let notifyToggleText = !this.controller.application.isDialogMuted()? this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_DISABLE'): this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_ENABLE');
			let notifyToggleIcon = !this.controller.application.isDialogMuted()? HeaderMenuIcon.notify: HeaderMenuIcon.notify_off;

			let gotoCrmLocalize = '';
			if (
				dialogData.type === DialogType.call
				|| dialogData.type === DialogType.crm
			)
			{
				let crmData = this.controller.application.getDialogCrmData();
				if (crmData.enabled)
				{
					if (crmData.entityType === DialogCrmType.lead)
					{
						gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_LEAD');
					}
					else if (crmData.entityType === DialogCrmType.company)
					{
						gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_COMPANY');
					}
					else if (crmData.entityType === DialogCrmType.contact)
					{
						gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_CONTACT');
					}
					else if (crmData.entityType === DialogCrmType.deal)
					{
						gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_DEAL');
					}
					else
					{
						gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM');
					}
				}
			}

			if (dialogData.type === DialogType.call)
			{
				this.headerMenu.setItems([
					HeaderMenuItem.create('call_chat_call')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL'))
						.setIcon(HeaderMenuIcon.phone)
						.skip(dialogData.entityId === 'UNIFY_CALL_CHAT')
					,
					HeaderMenuItem.create('goto_crm')
						.setTitle(gotoCrmLocalize)
						.setIcon(HeaderMenuIcon.lifefeed)
						.skip(dialogData.entityId === 'UNIFY_CALL_CHAT' || !gotoCrmLocalize)
					,
					HeaderMenuItem.create('reload')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD'))
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
						.skip(!dialogData.restrictions.mute)
					,
					HeaderMenuItem.create('user_list')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_LIST'))
						.setIcon(HeaderMenuIcon.user)
						.skip(!dialogData.restrictions.userList)
					,
					HeaderMenuItem.create('user_add')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD'))
						.setIcon(HeaderMenuIcon.user_plus)
						.skip(!dialogData.restrictions.extend)
					,
					HeaderMenuItem.create('leave')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_LEAVE'))
						.setIcon(HeaderMenuIcon.cross)
						.skip(!dialogData.restrictions.leave)
					,
					HeaderMenuItem.create('reload')
						.setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD'))
						.setIcon(HeaderMenuIcon.reload)
					,
				];

				items.push(HeaderMenuItem.create('reload')
					.setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD'))
					.setIcon(HeaderMenuIcon.reload));

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
			let shouldSkipUserAdd = false;

			const userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
			if (userData.bot && userData.externalAuthId === 'support24')
			{
				shouldSkipUserAdd = true;
			}

			this.headerMenu.setItems([
				HeaderMenuItem.create('profile')
					.setTitle(this.getLocalize('MOBILE_HEADER_MENU_PROFILE'))
					.setIcon('user')
					.skip(() => {
						if(Utils.dialog.isChatId(this.controller.application.getDialogId()))
						{
							return true;
						}

						const userData =
							this.controller
								.getStore()
								.getters['users/get'](this.controller.application.getDialogId(), true)
						;

						if (userData.bot)
						{
							return true;
						}

						return false;
					})
				,
				HeaderMenuItem.create('user_add')
					.setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD'))
					.setIcon(HeaderMenuIcon.user_plus)
					.skip(shouldSkipUserAdd)
				,
				HeaderMenuItem.create('reload')
					.setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD'))
					.setIcon(HeaderMenuIcon.reload)
				,
			]);
		}

		this.headerMenu.show(true);
	}

	shareMessage(messageId, type)
	{
		if (!this.controller.isOnline())
		{
			return false;
		}

		return this.controller.application.shareMessage(messageId, type);
	}

	unreadMessage(messageId)
	{
		if (!this.controller.isOnline())
		{
			return false;
		}

		return this.controller.application.unreadMessage(messageId);
	}

	openReadedList(list)
	{
		if (!Utils.dialog.isChatId(this.controller.application.getDialogId()))
		{
			return false;
		}

		if (!list || list.length <= 1)
		{
			return false;
		}

		this.openUserList({
			users: list.map(element => element.userId),
			title: this.getLocalize('MOBILE_MESSAGE_LIST_VIEW')
		});
	}

	replyToUser(userId, userData = null)
	{
		if (!this.controller.isOnline())
		{
			return false;
		}

		if (!userData)
		{
			userData = this.controller.getStore().getters['users/get'](userId);
		}

		return this.insertText({text: `[USER=${userId}]${userData.firstName}[/USER] `});
	}

	copyMessage(id)
	{
		let quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);

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

		text = text.replace(/\[url](.*?)\[\/url]/ig, (whole, link) => link);

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
		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				quoteId: id
			}
		}).then(() => {
			if (!this.controller.getStore().state.application.mobile.keyboardShow)
			{
				this.setTextFocus();
				setTimeout(() => {
					EventEmitter.emit(EventType.dialog.scrollToBottom, {
						chatId: this.controller.application.getChatId(),
						duration: 300,
						cancelIfScrollChange: false,
						force: true
					});
				}, 300);
			}
		});
	}

	quoteMessageClear()
	{
		this.setText('');

		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				quoteId: 0,
				editId: 0
			}
		});
	}

	editMessage(id)
	{
		let message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);

		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				quoteId: id,
				editId: id
			}
		}).then(() => {
			setTimeout(() => EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.controller.application.getChatId(), duration: 300, cancelIfScrollChange: false, force: true}), 300);
			this.setTextFocus();
		});

		this.setText(message.text);
	}

	updateMessage(id, text)
	{
		this.quoteMessageClear();

		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				editId: 0
			}
		});

		this.editMessageSend(id, text);
	}

	editMessageSend(id, text)
	{
		this.controller.restClient.callMethod(RestMethod.imMessageUpdate, {
			'MESSAGE_ID': id,
			'MESSAGE': text
		}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imMessageUpdate,
			data: {timMessageType: 'text'},
			dialog: this.controller.application.getDialogData()
		}));
	}

	deleteMessage(id)
	{
		let message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
		let files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());

		let messageText = Utils.text.purify(message.text, message.params, files, this.getLocalize());
		messageText = messageText.length > 50? messageText.substr(0, 47) + '...': messageText;

		app.confirm({
			title: this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_CONFIRM'),
			text: messageText? '"' + messageText + '"': '',
			buttons: [
				this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_YES'),
				this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_NO')
			],
			callback: (button) =>
			{
				if (button === 1)
				{
					this.quoteMessageClear();
					this.deleteMessageSend(id);
				}
			}
		});
	}

	deleteMessageSend(id)
	{
		this.controller.restClient.callMethod(RestMethod.imMessageDelete, {
			'MESSAGE_ID': id
		}, null, null, Utils.getLogTrackingParams({
			name: RestMethod.imMessageDelete,
			data: {},
			dialog: this.controller.application.getDialogData(this.controller.application.getDialogId())
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
		if (!this.controller.getStore().state.application.mobile.keyboardShow)
		{
			BXMobileApp.UI.Page.TextPanel.focus();
		}
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

	hideSmiles()
	{
		// this.controller.hideSmiles();
	}

	changeDialogState(state)
	{
		console.log(`changeDialogState -> ${state}`);

		if (state === 'show')
		{
			this.setIosInset();
		}
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
			this.controller.executeRestAnswer(RestMethodHandler.imMessageAdd, successObject, data.extra);
		}
		else if (action === 'readMessage')
		{
			this.processMarkReadMessages();
		}
		else if (action === 'uploadFileFromDisk')
		{
			this.controller.executeRestAnswer(RestMethodHandler.imMessageAdd, successObject, data.extra);
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

		// Handle an error only for API error when server status is 200.
		// Otherwise we don't want to draw errors, because background queue will resend messages.
		if (data.status === HTTP_OK_STATUS_CODE)
		{
			if (action === 'sendMessage' || action === 'uploadFileFromDisk')
			{
				this.controller.executeRestAnswer(RestMethodHandler.imMessageAdd, errorObject, data.extra);
			}
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

		this.controller.getStore().commit('application/set', {error: {
			active: true,
			code,
			description: localizeDescription
		}});
	}

	clearError()
	{
		this.controller.getStore().commit('application/set', {error: {
			active: false,
			code: '',
			description: ''}
		});
	}

	addLocalize(phrases)
	{
		return this.controller.addLocalize(phrases);
	}

	getLocalize(name)
	{
		return this.controller.getLocalize(name);
	}

/* endregion 06. Interaction and utils */
/* region 07. Event handlers */
	onMessagesSet()
	{
		this.messagesSet = true;

		BXMobileApp.Events.postToComponent('chatbackground::task::restart', [], 'background');
		BXMobileApp.Events.postToComponent('chatuploader::task::restart', [], 'background');
	}
/* endregion 07. Event handlers */
}
