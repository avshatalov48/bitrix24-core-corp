/**
 * Bitrix OpenLines widget
 * Widget private interface (base class)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

// widget files
import {
	VoteType,
	LocationStyle,
	SubscriptionType,
	SubscriptionTypeCheck,
	RestMethod,
	RestAuth,
} from "./const";

import {WidgetModel} from "./model";
import {WidgetRestClient} from "./utils/restclient"
import {WidgetRestAnswerHandler} from "./rest.handler";
import {WidgetImPullCommandHandler, WidgetImopenlinesPullCommandHandler} from "./pull.handler";

// main
import {md5} from "main.md5";
import "main.date";

// pull
import {PullClient} from "pull.client";

// ui
import {Vue} from "ui.vue";
import {VuexBuilder} from "ui.vue.vuex";
import {Cookie} from "./utils/cookie";

// messenger files
import {ApplicationModel, MessagesModel, DialoguesModel, UsersModel, FilesModel} from 'im.model';
import {ApplicationController} from 'im.controller';
import {DeviceType, DeviceOrientation, RestMethod as ImRestMethod} from 'im.const';
import {Utils} from "im.utils";
import {LocalStorage} from "im.tools.localstorage";
import {ImRestAnswerHandler} from "im.provider.rest";
import {ImPullCommandHandler} from "im.provider.pull";
import {Logger} from "im.tools.logger";

// TODO change BX.Promise, BX.Main.Date to IMPORT

export class Widget
{
/* region 01. Initialize and store data */

	constructor(params = {})
	{
		this.ready = true;
		this.widgetDataRequested = false;

		this.offline = false;

		this.code = params.code || '';
		this.host = params.host || '';
		this.language = params.language || 'en';
		this.copyright = params.copyright !== false;
		this.copyrightUrl = this.copyright && params.copyrightUrl? params.copyrightUrl: '';
		this.buttonInstance = typeof params.buttonInstance === 'object' && params.buttonInstance !== null? params.buttonInstance: null;

		this.pageMode = typeof params.pageMode === 'object' && params.pageMode;
		if (this.pageMode)
		{
			this.pageMode.useBitrixLocalize = params.pageMode.useBitrixLocalize === true;
			this.pageMode.placeholder = document.getElementById(params.pageMode.placeholder);
		}

		if (typeof this.code === 'string')
		{
			if (this.code.length <= 0)
			{
				console.warn(`%cLiveChatWidget.constructor: code is not correct (%c${this.code}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				this.ready = false;
			}
		}

		if (typeof this.host === 'string')
		{
			if (this.host.length <= 0 || !this.host.startsWith('http'))
			{
				console.warn(`%cLiveChatWidget.constructor: host is not correct (%c${this.host}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				this.ready = false;
			}
		}

		this.inited = false;
		this.initEventFired = false;

		this.restClient = null;

		this.userRegisterData = {};
		this.customData = [];

		this.localize = this.pageMode && this.pageMode.useBitrixLocalize? window.BX.message: {};
		if (typeof params.localize === 'object')
		{
			this.addLocalize(params.localize);
		}

		this.subscribers = {};
		this.dateFormat = null;

		this.messagesQueue = [];
		this.filesQueue = [];
		this.filesQueueIndex = 0;

		this.configRequestXhr = null;

		if (this.pageMode && this.pageMode.placeholder)
		{
			this.rootNode = this.pageMode.placeholder;
		}
		else
		{
			this.rootNode = document.createElement('div');
			if (document.body.firstChild)
			{
				document.body.insertBefore(this.rootNode, document.body.firstChild);
			}
			else
			{
				document.body.appendChild(this.rootNode);
			}
		}

		this.template = null;

		window.addEventListener('orientationchange', () =>
		{
			if (!this.store)
			{
				return;
			}

			this.store.commit('application/set', {device: {
				orientation: Utils.device.getOrientation()
			}});

			if (
				this.store.state.widget.common.showed
				&& this.store.state.application.device.type == DeviceType.mobile
				&& this.store.state.application.device.orientation == DeviceOrientation.horizontal
			)
			{
				document.activeElement.blur();
			}
		});

		let serverVariables = LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		let widgetVariables = {
			common: {
				host: this.getHost(),
				pageMode: this.pageMode !== false,
				copyright: this.copyright,
				copyrightUrl: this.copyrightUrl,
			},
			vote: {
				messageText: this.getLocalize('BX_LIVECHAT_VOTE_TITLE'),
				messageLike: this.getLocalize('BX_LIVECHAT_VOTE_PLUS_TITLE'),
				messageDislike: this.getLocalize('BX_LIVECHAT_VOTE_MINUS_TITLE'),
			},
			textMessages: {
				bxLivechatOnlineLine1: this.getLocalize('BX_LIVECHAT_ONLINE_LINE_1'),
				bxLivechatOnlineLine2: this.getLocalize('BX_LIVECHAT_ONLINE_LINE_2'),
				bxLivechatOffline: this.getLocalize('BX_LIVECHAT_OFFLINE'),
			}
		};
		if (params.location && typeof LocationStyle[params.location] !== 'undefined')
		{
			widgetVariables.common.location = params.location;
		}
		if (
			Utils.types.isPlainObject(params.styles)
			&& (params.styles.backgroundColor || params.styles.iconColor)
		)
		{
			widgetVariables.styles = {};
			if (params.styles.backgroundColor)
			{
				widgetVariables.styles.backgroundColor = params.styles.backgroundColor;
			}
			if (params.styles.iconColor)
			{
				widgetVariables.styles.iconColor = params.styles.iconColor;
			}
		}

		this.controller = new ApplicationController();

		let applicationVariables = {
			common: {
				host: this.getHost(),
				siteId: this.getSiteId(),
				languageId: this.language,
			},
			device: {
				type: Utils.device.isMobile()? DeviceType.mobile: DeviceType.desktop,
				orientation: Utils.device.getOrientation(),
			},
			dialog: {
				messageLimit: this.controller.getDefaultMessageLimit()
			}
		};

		new VuexBuilder()
			.addModel(WidgetModel.create().setVariables(widgetVariables))
			.addModel(ApplicationModel.create().setVariables(applicationVariables))
			.addModel(MessagesModel.create())
			.addModel(DialoguesModel.create().setVariables({host: this.host}).useDatabase(false))
			.addModel(UsersModel.create().setVariables({host: this.host, defaultName: this.getLocalize('IM_MESSENGER_MESSAGE_USER_ANONYM')}).useDatabase(false))
			.addModel(FilesModel.create().setVariables({host: this.host}).useDatabase(false))
			.setDatabaseConfig({
				name: 'imol/widget',
				type: VuexBuilder.DatabaseType.localStorage,
				siteId: this.getSiteId(),
			})
		.build(result => {

			this.store = result.store;
			this.storeCollector = result.builder;

			this.initRestClient();

			this.controller.setVuexStore(this.store);
			this.controller.setRestClient(this.restClient);

			this.controller.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));

			this.imRestAnswer = ImRestAnswerHandler.create({
				store: this.store,
				controller: this.controller,
			});
			this.widgetRestAnswer = WidgetRestAnswerHandler.create({
				widget: this,
				store: this.store,
				controller: this.controller,
			});

			window.dispatchEvent(new CustomEvent('onBitrixLiveChat', {detail: {
				widget: this,
				widgetCode: this.code,
				widgetHost: this.host,
			}}));

			if (this.callStartFlag)
			{
				this.start();
			}

			if (this.pageMode || this.callOpenFlag)
			{
				this.open();
			}
		});
	}

	initRestClient()
	{
		this.restClient = new WidgetRestClient({endpoint: this.host+'/rest'});

		if (this.isUserRegistered())
		{
			this.restClient.setAuthId(this.getUserHash());
		}
		else
		{
			this.restClient.setAuthId(RestAuth.guest);
		}
	}

	requestWidgetData()
	{
		if (!this.isReady())
		{
			console.error('LiveChatWidget.start: widget code or host is not specified');
			return false;
		}

		this.widgetDataRequested = true;
		if (
			!this.isUserRegistered() && (
				this.userRegisterData.hash
				|| this.getUserHashCookie()
			)
		)
		{
			this.requestData();
			this.inited = true;
			this.fireInitEvent();
		}
		else if (this.isConfigDataLoaded() && this.isUserRegistered())
		{
			this.requestData();
			this.inited = true;
			this.fireInitEvent();
		}
		else
		{
			this.restClient.callMethod(RestMethod.widgetConfigGet, {code: this.code}, (xhr) => {this.configRequestXhr = xhr}).then((result) => {
				this.configRequestXhr = null;
				this.clearError();

				this.executeRestAnswer(RestMethod.widgetConfigGet, result);

				if (!this.inited)
				{
					this.inited = true;
					this.fireInitEvent();
				}
			}).catch(result => {
				this.configRequestXhr = null;

				this.setError(result.error().ex.error, result.error().ex.error_description);
			});

			if (this.isConfigDataLoaded())
			{
				this.inited = true;
				this.fireInitEvent();
			}
		}
	}

	requestData()
	{
		if (this.requestDataSend)
		{
			return true;
		}

		this.requestDataSend = true;

		if (this.configRequestXhr)
		{
			this.configRequestXhr.abort();
		}

		let query = {
			[RestMethod.widgetConfigGet]: [RestMethod.widgetConfigGet, {code: this.code}]
		};

		if (this.isUserRegistered())
		{
			query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {config_id: this.getConfigId(), trace_data: this.getCrmTraceData(), custom_data: this.getCustomData()}];
			query[ImRestMethod.imChatGet] = [ImRestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetDialogGet+'][dialogId]'}];
			query[ImRestMethod.imDialogMessagesGet] = [ImRestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.controller.getRequestMessageLimit(), convert_text: 'Y'}];
		}
		else
		{
			query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', ...this.getUserRegisterFields()}];
			query[ImRestMethod.imChatGet] = [ImRestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetUserRegister+'][dialogId]'}];

			if (this.userRegisterData.hash || this.getUserHashCookie())
			{
				query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', trace_data: this.getCrmTraceData(), custom_data: this.getCustomData()}];
				query[ImRestMethod.imDialogMessagesGet] = [ImRestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.controller.getRequestMessageLimit(), convert_text: 'Y'}];
			}
			if (this.isUserAgreeConsent())
			{
				query[RestMethod.widgetUserConsentApply] = [RestMethod.widgetUserConsentApply, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', consent_url: location.href}];
			}
		}

		query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
		query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {'CACHE': 'N'}];
		query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];

		this.restClient.callBatch(query, (response) =>
		{
			if (!response)
			{
				this.requestDataSend = false;
				this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
				return false;
			}

			let configGet = response[RestMethod.widgetConfigGet];
			if (configGet && configGet.error())
			{
				this.requestDataSend = false;

				this.setError(configGet.error().ex.error, configGet.error().ex.error_description);
				return false;
			}
			this.executeRestAnswer(RestMethod.widgetConfigGet, configGet);

			let userGetResult = response[RestMethod.widgetUserGet];
			if (userGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);
				return false;
			}
			this.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

			let chatGetResult = response[ImRestMethod.imChatGet];
			if (chatGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);
				return false;
			}
			this.executeRestAnswer(ImRestMethod.imChatGet, chatGetResult);

			let dialogGetResult = response[RestMethod.widgetDialogGet];
			if (dialogGetResult)
			{
				if (dialogGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);
					return false;
				}
				this.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);
			}

			let dialogMessagesGetResult = response[ImRestMethod.imDialogMessagesGet];
			if (dialogMessagesGetResult)
			{
				if (dialogMessagesGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);
					return false;
				}
				this.executeRestAnswer(ImRestMethod.imDialogMessagesGet, dialogMessagesGetResult);
			}

			let userRegisterResult = response[RestMethod.widgetUserRegister];
			if (userRegisterResult)
			{
				if (userRegisterResult.error())
				{
					this.requestDataSend = false;
					this.setError(userRegisterResult.error().ex.error, userRegisterResult.error().ex.error_description);
					return false;
				}
				this.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);
			}

			let timeShift = 0;

			let serverTimeResult = response[RestMethod.pullServerTime];
			if (serverTimeResult && !serverTimeResult.error())
			{
				timeShift = Math.floor(((new Date()).getTime() - new Date(serverTimeResult.data()).getTime())/1000);
			}

			let config = null;
			let pullConfigResult = response[RestMethod.pullConfigGet];
			if (pullConfigResult && !pullConfigResult.error())
			{
				config = pullConfigResult.data();
				config.server.timeShift = timeShift;
			}

			this.startPullClient(config).then(() => {
				this.processSendMessages();
				this.processSendFiles();
			}).catch((error) => {
				this.setError(error.ex.error, error.ex.error_description);
			});

			this.requestDataSend = false;
		}, false, false, Utils.getLogTrackingParams({name: 'widget.init.config', dialog: this.getDialogData()}));
	}

	executeRestAnswer(command, result, extra)
	{
		this.imRestAnswer.execute(command, result, extra);
		this.widgetRestAnswer.execute(command, result, extra);
	}

	prepareFileData(files)
	{
		if (Cookie.get(null, 'BITRIX_LIVECHAT_AUTH'))
		{
			return files;
		}

		return files.map(file =>
		{
			let hash = (window.md5 || md5)(this.getUserId()+'|'+file.id+'|'+this.getUserHash());
			let urlParam = 'livechat_auth_id='+hash+'&livechat_user_id='+this.getUserId();
			if (file.urlPreview)
			{
				file.urlPreview = file.urlPreview+'&'+urlParam;
			}
			if (file.urlShow)
			{
				file.urlShow = file.urlShow+'&'+urlParam;
			}
			if (file.urlDownload)
			{
				file.urlDownload = file.urlDownload+'&'+urlParam;
			}

			return file;
		});
	}

	checkBrowserVersion()
	{
		if (Utils.platform.isIos())
		{
			let version = Utils.platform.getIosVersion();
			if (version && version <= 10)
			{
				return false;
			}
		}

		return true;
	}

/* endregion 01. Initialize and store data */

/* region 02. Push & Pull */

	startPullClient(config)
	{
		let promise = new BX.Promise();

		if (this.pullClient)
		{
			if (!this.pullClient.isConnected())
			{
				this.pullClient.scheduleReconnect();
			}
			promise.resolve(true);
			return promise;
		}
		if (!this.getUserId() || !this.getSiteId() || !this.restClient)
		{
			promise.reject({
				ex: { error: 'WIDGET_NOT_LOADED', error_description: 'Widget is not loaded.'}
			});
			return promise;
		}

		this.pullClient = new PullClient({
			serverEnabled: true,
			userId: this.getUserId(),
			siteId: this.getSiteId(),
			restClient: this.restClient,
			configTimestamp: config? config.server.config_timestamp: 0,
			skipCheckRevision: true,
		});

		this.pullClient.subscribe(
			new ImPullCommandHandler({
				store: this.store,
				controller: this.controller,
			})
		);
		this.pullClient.subscribe(
			new WidgetImPullCommandHandler({
				store: this.store,
				controller: this.controller,
				widget: this,
			})
		);
		this.pullClient.subscribe(
			new WidgetImopenlinesPullCommandHandler({
				store: this.store,
				controller: this.controller,
				widget: this,
			})
		);

		this.pullClient.subscribe({
			type: PullClient.SubscriptionType.Status,
			callback: this.eventStatusInteraction.bind(this)
		});

		this.pullConnectedFirstTime = this.pullClient.subscribe({
			type: PullClient.SubscriptionType.Status,
			callback: (result) => {
				if (result.status == PullClient.PullStatus.Online)
				{
					promise.resolve(true);
					this.pullConnectedFirstTime();
				}
			}
		});

		if (this.template)
		{
			this.template.$root.$bitrixPullClient = this.pullClient;
			this.template.$root.$emit('onBitrixPullClientInited', this.pullClient);
		}

		this.pullClient.start(config).catch(function(){
			promise.reject({
				ex: { error: 'PULL_CONNECTION_ERROR', error_description: 'Pull is not connected.'}
			});
		});

		return promise;
	}

	stopPullClient()
	{
		if (this.pullClient)
		{
			this.pullClient.stop(PullClient.CloseReasons.MANUAL, 'Closed manually');
		}
	}

	recoverPullConnection()
	{
		// this.pullClient.session.mid = 0; // TODO specially for disable pull history, remove after recode im
		this.pullClient.restart(PullClient.CloseReasons.MANUAL, 'Restart after click by connection status button.');
	}

	eventStatusInteraction(data)
	{
		if (data.status === PullClient.PullStatus.Online)
		{
			this.offline = false;

			if (this.pullRequestMessage)
			{
				this.getDialogUnread().then(() => {
					this.readMessage();
					this.processSendMessages();
					this.processSendFiles();
				});
				this.pullRequestMessage = false;
			}
			else
			{
				this.readMessage();
				this.processSendMessages();
				this.processSendFiles();
			}
		}
		else if (data.status === PullClient.PullStatus.Offline)
		{
			this.pullRequestMessage = true;
			this.offline = true;
		}
	}

/* endregion 02. Push & Pull */

/* region 03. Template engine */

	attachTemplate()
	{
		if (this.template)
		{
			this.store.commit('widget/common', {showed: true});
			return true;
		}

		this.rootNode.innerHTML = '';
		this.rootNode.appendChild(document.createElement('div'));

		const widgetContext = this;
		const controller = this.controller;
		const restClient = this.restClient;
		const pullClient = this.pullClient || null;

		this.template = Vue.create({
			el: this.rootNode.firstChild,
			store: this.store,
			template: '<bx-livechat/>',
			beforeCreate()
			{
				this.$bitrixWidget = widgetContext;
				this.$bitrixController = controller;
				this.$bitrixRestClient = restClient;
				this.$bitrixPullClient = pullClient;
				this.$bitrixMessages = widgetContext.localize;

				widgetContext.sendEvent({
					type: SubscriptionType.widgetOpen,
					data: {}
				});
			},
			destroyed()
			{
				widgetContext.sendEvent({
					type: SubscriptionType.widgetClose,
					data: {}
				});

				this.$bitrixWidget.template = null;
				this.$bitrixWidget.templateAttached = false;
				this.$bitrixWidget.rootNode.innerHTML = '';

				this.$bitrixWidget = null;
				this.$bitrixRestClient = null;
				this.$bitrixPullClient = null;
				this.$bitrixMessages = null;
			}
		});

		return true;
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

	mutateTemplateComponent(id, params)
	{
		return Vue.mutateComponent(id, params);
	}

/* endregion 03. Template engine */

/* region 04. Rest methods */

	addMessage(text = '')
	{
		if (!text)
		{
			return false;
		}

		Logger.warn('addMessage', text);

		if (!this.controller.isUnreadMessagesLoaded())
		{
			this.sendMessage({id: 0, text});
			this.processSendMessages();

			return true;
		}

		this.store.dispatch('messages/add', {
			chatId: this.getChatId(),
			authorId: this.getUserId(),
			text: text,
		}).then(messageId => {

			if (!this.isDialogStart())
			{
				this.store.commit('widget/common', {dialogStart:true});
			}

			this.messagesQueue.push({
				id: messageId,
				text,
				sending: false
			});

			if (this.getChatId())
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

	addFile(fileInput)
	{
		if (!fileInput)
		{
			return false;
		}

		Logger.warn('addFile', fileInput.files[0].name, fileInput.files[0].size);

		if (!this.isDialogStart())
		{
			this.store.commit('widget/common', {dialogStart:true});
		}

		this.filesQueue.push({
			id: this.filesQueueIndex,
			fileInput
		});
		this.filesQueueIndex++;

		if (this.getChatId())
		{
			this.processSendFiles();
		}
		else
		{
			this.requestData();
		}

		return true;
	}

	processSendMessages()
	{
		if (this.offline)
		{
			return false;
		}

		this.messagesQueue.filter(element => !element.sending).forEach(element => {
			element.sending = true;
			this.sendMessage(element);
		});

		return true;
	}

	processSendFiles()
	{
		if (this.offline)
		{
			return false;
		}

		this.filesQueue.filter(element => !element.sending).forEach(element => {
			element.sending = true;
			this.sendFile(element);
		});

		return true;
	}

	sendMessage(message)
	{
		this.controller.stopWriting();

		this.restClient.callMethod(ImRestMethod.imMessageAdd, {
			'TEMP_ID': message.id,
			'CHAT_ID': this.getChatId(),
			'MESSAGE': message.text
		}, null, null, Utils.getLogTrackingParams({
			name: ImRestMethod.imMessageAdd,
			data: {timMessageType: 'text'},
			dialog: this.getDialogData()
		})).then(response => {
			this.executeRestAnswer(ImRestMethod.imMessageAdd, response, message);
		}).catch(error => {
			this.executeRestAnswer(ImRestMethod.imMessageAdd, error, message);
		});
	}

	sendFile(file)
	{
		let fileName = file.fileInput.files[0].name;
		let fileType = 'file'; // TODO set type by fileInput type

		let diskFolderId = this.getDiskFolderId();

		let query = {};

		if (diskFolderId)
		{
			query[ImRestMethod.imDiskFileUpload] = [ImRestMethod.imDiskFileUpload, {
				id : diskFolderId,
				data : {NAME : fileName},
				fileContent: file.fileInput,
				generateUniqueName: true
			}];
		}
		else
		{
			query[ImRestMethod.imDiskFolderGet] = [ImRestMethod.imDiskFolderGet, {chat_id: this.getChatId()}];
			query[ImRestMethod.imDiskFileUpload] = [ImRestMethod.imDiskFileUpload, {
				id : '$result[' + ImRestMethod.imDiskFolderGet + '][ID]',
				data : {
					NAME : fileName
				},
				fileContent: file.fileInput,
				generateUniqueName: true
			}];
		}
		query[ImRestMethod.imDiskFileCommit] = [ImRestMethod.imDiskFileCommit, {
			chat_id : this.getChatId(),
			upload_id : '$result[' + ImRestMethod.imDiskFileUpload + '][ID]',
		}];

		this.store.commit('widget/common', {uploadFilePlus: true}); // TODO remove this after create new file-loader

		this.restClient.callBatch(query, (response) =>
		{
			this.store.commit('widget/common', {uploadFileMinus: true}); // TODO  remove this after create new file-loader

			if (!response)
			{
				this.requestDataSend = false;
				this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
				return false;
			}

			if (!diskFolderId)
			{
				let diskFolderGet = response[ImRestMethod.imDiskFolderGet];
				if (diskFolderGet && diskFolderGet.error())
				{
					console.warn(diskFolderGet.error().ex.error, diskFolderGet.error().ex.error_description);
					return false;
				}
				this.executeRestAnswer(ImRestMethod.imDiskFolderGet, diskFolderGet);
			}

			let diskFileUpload = response[ImRestMethod.imDiskFileUpload];
			if (diskFileUpload && diskFileUpload.error())
			{
				console.warn(diskFileUpload.error().ex.error, diskFileUpload.error().ex.error_description);
				return false;
			}
			else
			{
				Logger.log('upload success', diskFileUpload.data())
			}

			let diskFileCommit = response[ImRestMethod.imDiskFileCommit];
			if (diskFileCommit && diskFileCommit.error())
			{
				console.warn(diskFileCommit.error().ex.error, diskFileCommit.error().ex.error_description);
				return false;
			}
			else
			{
				Logger.log('commit success', diskFileCommit.data())
			}
		}, false, false, Utils.getLogTrackingParams({
			name: ImRestMethod.imDiskFileCommit,
			data: {timMessageType: fileType},
			dialog: this.getDialogData()
		}));

		this.filesQueue = this.filesQueue.filter(el => el.id != file.id);
	}

	getDialogHistory(lastId, limit = this.controller.getRequestMessageLimit())
	{
		this.restClient.callMethod(ImRestMethod.imDialogMessagesGet, {
			'CHAT_ID': this.getChatId(),
			'LAST_ID': lastId,
			'LIMIT': limit,
			'CONVERT_TEXT': 'Y'
		}).then(result => {
			this.executeRestAnswer(ImRestMethod.imDialogMessagesGet, result);
			this.template.$emit('onDialogRequestHistoryResult', {count: result.data().messages.length});
		}).catch(result => {
			this.template.$emit('onDialogRequestHistoryResult', {error: result.error().ex});
		});
	}

	getDialogUnread(lastId, limit = this.controller.getRequestMessageLimit())
	{
		const promise = new BX.Promise();

		if (!lastId)
		{
			lastId = this.store.getters['messages/getLastId'](this.getChatId());
		}

		if (!lastId)
		{
			this.template.$emit('onDialogRequestUnreadResult', {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});
			promise.reject();
			return promise;
		}

		let query = {
			[ImRestMethod.imChatGet]: [ImRestMethod.imChatGet, {
				dialog_id: this.getDialogId()
			}],
			[ImRestMethod.imDialogMessagesUnread]: [ImRestMethod.imDialogMessagesGet, {
				chat_id: this.getChatId(),
				first_id: lastId,
				limit: limit,
				convert_text: 'Y'
			}]
		};

		this.restClient.callBatch(query, (response) =>
		{
			if (!response)
			{
				this.template.$emit('onDialogRequestUnreadResult', {error: {error: 'EMPTY_RESPONSE', error_description: 'Server returned an empty response.'}});

				promise.reject();
				return false;
			}

			let chatGetResult = response[ImRestMethod.imChatGet];
			if (!chatGetResult.error())
			{
				this.executeRestAnswer(ImRestMethod.imChatGet, chatGetResult);
			}

			let dialogMessageUnread = response[ImRestMethod.imDialogMessagesUnread];
			if (dialogMessageUnread.error())
			{
				this.template.$emit('onDialogRequestUnreadResult', {error: dialogMessageUnread.error().ex});
			}
			else
			{
				this.executeRestAnswer(ImRestMethod.imDialogMessagesUnread, dialogMessageUnread);
				this.template.$emit('onDialogRequestUnreadResult', {count: dialogMessageUnread.data().messages.length});
			}

			promise.fulfill(response);

		}, false, false, Utils.getLogTrackingParams({name: ImRestMethod.imDialogMessagesUnread, dialog: this.getDialogData()}));

		return promise;
	}

	retrySendMessage(message)
	{
		if (this.messagesQueue.find(el => el.id == message.id))
		{
			return false;
		}

		this.messagesQueue.push({
			id: message.id,
			text: message.text,
			sending: false
		});

		this.controller.setSendingMessageFlag(message.id);

		this.processSendMessages();
	}

	readMessage(messageId)
	{
		if (this.offline)
		{
			return false;
		}

		return this.controller.readMessage(messageId);
	}

	sendDialogVote(result)
	{
		if (!this.getSessionId())
		{
			return false;
		}

		this.restClient.callMethod(RestMethod.widgetVoteSend, {
			'SESSION_ID': this.getSessionId(),
			'ACTION': result
		}).catch((result) => {
			this.store.commit('widget/dialog', {userVote: VoteType.none});
		});

		this.sendEvent({
			type: SubscriptionType.userVote,
			data: {
				vote: result
			}
		});
	}

	sendForm(type, fields)
	{
		Logger.info('LiveChatWidgetPrivate.sendForm:', type, fields);

		let query = {
			[RestMethod.widgetFormSend]: [RestMethod.widgetFormSend, {
				'CHAT_ID': this.getChatId(),
				'FORM': type.toUpperCase(),
				'FIELDS': fields
			}],
			[RestMethod.widgetUserGet]: [RestMethod.widgetUserGet, {}]
		};
		this.restClient.callBatch(query, (response) =>
		{
			if (!response)
			{
				this.requestDataSend = false;
				this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
				return false;
			}

			let userGetResult = response[RestMethod.widgetUserGet];
			if (userGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);
				return false;
			}
			this.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

			this.sendEvent({
				type: SubscriptionType.userForm,
				data: {
					form: type,
					fields: fields
				}
			});

		}, false, false, Utils.getLogTrackingParams({name: RestMethod.widgetUserGet, dialog: this.getDialogData()}));

	}

	sendConsentDecision(result)
	{
		result = result === true;

		this.store.commit('widget/dialog', {userConsent: result});

		if (result && this.isUserRegistered())
		{
			this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
				config_id: this.getConfigId(),
				consent_url: location.href
			});
		}
	}

/* endregion 05. Templates and template interaction */

/* region 05. Widget interaction and utils */

	start()
	{
		if (!this.store)
		{
			this.callStartFlag = true;
			return true;
		}

		if (this.isSessionActive())
		{
			this.requestWidgetData();
		}

		return true;
	}

	open(params = {})
	{
		clearTimeout(this.openTimeout);
		if (!this.store)
		{
			this.callOpenFlag = true;
			return true;
		}

		if (!params.openFromButton && this.buttonInstance)
		{
			this.buttonInstance.wm.showById('openline_livechat');
		}

		if (!this.checkBrowserVersion())
		{
			this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
		}
		else if (Utils.versionCompare(Vue.version(), '2.1') < 0)
		{
			alert(this.localize.BX_LIVECHAT_OLD_VUE);
			console.error(`LiveChatWidget.error: OLD_VUE_VERSION (${this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', Vue.version())})`);

			return false;
		}
		else if (!this.isWidgetDataRequested())
		{
			this.requestWidgetData();
		}

		this.attachTemplate();
	}

	close()
	{
		if (this.pageMode)
		{
			return false;
		}

		if (this.buttonInstance)
		{
			this.buttonInstance.onWidgetClose();
		}

		this.detachTemplate();
	}

	showNotification(params)
	{
		if (!this.store)
		{
			console.error('LiveChatWidget.showNotification: method can be called after fired event - onBitrixLiveChat');
			return false;
		}
		// TODO show popup notification and set badge on button
		// operatorName
		// notificationText
		// counter
	}

	fireInitEvent()
	{
		if (this.initEventFired)
		{
			return true;
		}

		this.sendEvent({
			type: SubscriptionType.configLoaded,
			data: {}
		});

		if (this.store.state.widget.common.reopen)
		{
			this.open();
		}

		this.initEventFired = true;

		return true;
	}

	isReady()
	{
		return this.ready;
	}

	isInited()
	{
		return this.inited;
	}

	isUserRegistered()
	{
		return !!this.getUserHash();
	}

	isConfigDataLoaded()
	{
		return this.store.state.widget.common.configId;
	}

	isWidgetDataRequested()
	{
		return this.widgetDataRequested;
	}

	isChatLoaded()
	{
		return this.store.state.application.dialog.chatId > 0;
	}

	isSessionActive()
	{
		return !this.store.state.widget.dialog.sessionClose;
	}

	isUserAgreeConsent()
	{
		return this.store.state.widget.dialog.userConsent;
	}

	getCrmTraceData()
	{
		let traceData = '';

		if (!this.buttonInstance)
		{
			return traceData;
		}

		if (typeof this.buttonInstance.getTrace !== 'function')
		{
			traceData = this.buttonInstance.getTrace();
		}
		else if (
			typeof this.buttonInstance.b24Tracker !== 'undefined'
			&& typeof this.buttonInstance.b24Tracker.guest !== 'undefined'
		)
		{
			traceData = this.buttonInstance.b24Tracker.guest.getTrace();
		}

		return traceData;
	}

	getCustomData()
	{
		let customData = [];

		if (this.customData.length > 0)
		{
			customData = this.customData;
		}
		else
		{
			customData = [{MESSAGE: this.localize.BX_LIVECHAT_EXTRA_SITE+': '+location.href}];
		}

		return JSON.stringify(customData);
	}

	isUserLoaded()
	{
		return this.store.state.widget.user.id > 0;
	}

	getSiteId()
	{
		return this.host.replace(/(http.?:\/\/)|([:.\\\/])/mg, "")+this.code;
	}

	getHost()
	{
		return this.host;
	}

	getConfigId()
	{
		return this.store.state.widget.common.configId;
	}

	getChatId()
	{
		return this.store.state.application.dialog.chatId;
	}

	isDialogStart()
	{
		return this.store.state.widget.common.dialogStart;
	}

	getDialogId()
	{
		return this.store.state.application.dialog.dialogId;
	}

	getDialogData(dialogId = this.getDialogId())
	{
		return this.store.state.dialogues.collection[dialogId];
	}

	getDiskFolderId()
	{
		return this.store.state.application.dialog.diskFolderId;
	}

	getSessionId()
	{
		return this.store.state.widget.dialog.sessionId;
	}

	getUserHash()
	{
		return this.store.state.widget.user.hash;
	}

	getUserHashCookie()
	{
		let userHash = '';

		let cookie = Cookie.get(null, 'LIVECHAT_HASH');
		if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/))
		{
			userHash= cookie;
		}
		else
		{
			let cookie = Cookie.get(this.getSiteId(), 'LIVECHAT_HASH');
			if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/))
			{
				userHash = cookie;
			}
		}

		return userHash;
	}

	getUserId()
	{
		return this.store.state.widget.user.id;
	}

	getUserData()
	{
		if (!this.store)
		{
			console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
			return false;
		}

		return this.store.state.widget.user;
	}

	getUserRegisterFields()
	{
		return {
			'name': this.userRegisterData.name || '',
			'last_name': this.userRegisterData.lastName || '',
			'avatar': this.userRegisterData.avatar || '',
			'email': this.userRegisterData.email || '',
			'www': this.userRegisterData.www || '',
			'gender': this.userRegisterData.gender || '',
			'position': this.userRegisterData.position || '',
			'user_hash': this.userRegisterData.hash || '',
			'consent_url': this.store.state.widget.common.consentUrl? location.href: '',
			'trace_data': this.getCrmTraceData(),
			'custom_data': this.getCustomData()
		}
	}

	getWidgetLocationCode()
	{
		return LocationStyle[this.store.state.widget.common.location];
	}

	setUserRegisterData(params)
	{
		if (!this.store)
		{
			console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
			return false;
		}

		const validUserFields = ['hash', 'name', 'lastName', 'avatar', 'email', 'www', 'gender', 'position'];

		if (!Utils.types.isPlainObject(params))
		{
			console.error(`%cLiveChatWidget.setUserData: params is not a object`, "color: black;");
			return false;
		}

		for (let field in this.userRegisterData)
		{
			if (!this.userRegisterData.hasOwnProperty(field))
			{
				continue;
			}
			if (!params[field])
			{
				delete this.userRegisterData[field];
			}
		}

		for (let field in params)
		{
			if (!params.hasOwnProperty(field))
			{
				continue;
			}

			if (validUserFields.indexOf(field) === -1)
			{
				console.warn(`%cLiveChatWidget.setUserData: user field is not set, because you are trying to set an unknown field (%c${field}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				continue;
			}

			this.userRegisterData[field] = params[field];
		}

		if (
			this.userRegisterData.hash
			&& this.getUserHash()
			&& this.userRegisterData.hash != this.getUserHash()
		)
		{
			this.setNewAuthToken(this.userRegisterData.hash);
		}
	}

	setNewAuthToken(authToken = '')
	{
		let siteId = this.getSiteId();
		this.storeCollector.clearModelState();
		Cookie.set(null, 'LIVECHAT_HASH', '', {expires: 365*86400, path: '/'});

		this.restClient.setAuthId(RestAuth.guest, authToken);
	}

	setCustomData(params)
	{
		if (!this.store)
		{
			console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
			return false;
		}


		let result = [];
		if (params instanceof Array)
		{
			params.forEach(element =>
			{
				if (element && typeof element === 'object')
				{
					result.push(element);
				}
			});

			if (result.length <= 0)
			{
				console.error('LiveChatWidget.setCustomData: params is empty');
				return false;
			}
		}
		else
		{
			if (!params)
			{
				return false;
			}

			result = [{'MESSAGE': params}]
		}

		this.customData = this.customData.concat(result);

		return true;
	}

	setError(code = '', description = '')
	{
		console.error(`LiveChatWidget.error: ${code} (${description})`);

		let localizeDescription = '';
		if (code == 'LIVECHAT_AUTH_FAILED')
		{
			localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="javascript:void();" onclick="location.reload()">').replace('#LINK_END#', '</a>');
			this.setNewAuthToken();
		}
		else if (code == 'LIVECHAT_AUTH_PORTAL_USER')
		{
			localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="'+this.host+'">').replace('#LINK_END#', '</a>')
		}
		else if (code.endsWith('LOCALIZED'))
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

	/**
	 *
	 * @param params {Object}
	 * @returns {Function|Boolean} - Unsubscribe callback function or False
	 */
	subscribe(params)
	{
		if (!Utils.types.isPlainObject(params))
		{
			console.error(`%cLiveChatWidget.subscribe: params is not a object`, "color: black;");
			return false;
		}

		if (!SubscriptionTypeCheck.includes(params.type))
		{
			console.error(`%cLiveChatWidget.subscribe: subscription type is not correct (%c${params.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
			return false;
		}

		if (typeof params.callback !== 'function')
		{
			console.error(`%cLiveChatWidget.subscribe: callback is not a function (%c${typeof params.callback}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
			return false;
		}

		if (typeof (this.subscribers[params.type]) === 'undefined')
		{
			this.subscribers[params.type] = [];
		}

		this.subscribers[params.type].push(params.callback);

		return function () {
			this.subscribers[params.type] = this.subscribers[params.type].filter(function(element) {
				return element !== params.callback;
			});
		}.bind(this);
	}

	/**
	 *
	 * @param params {Object}
	 * @returns {boolean}
	 */
	sendEvent(params)
	{
		params = params || {};

		if (!params.type)
		{
			return false;
		}

		if (typeof params.data !== 'object' || !params.data)
		{
			params.data = {};
		}

		if (this.subscribers[params.type] instanceof Array && this.subscribers[params.type].length > 0)
		{
			this.subscribers[params.type].forEach(callback => callback(params.data));
		}

		if (this.subscribers[SubscriptionType.every] instanceof Array && this.subscribers[SubscriptionType.every].length > 0)
		{
			this.subscribers[SubscriptionType.every].forEach(callback => callback({type: params.type, data: params.data}));
		}

		return true;
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
			console.warn(`LiveChatWidget.getLocalize: message with code '${name.toString()}' is undefined.`)
		}
		else
		{
			phrase = this.localize[name];
		}

		return phrase;
	}

/* endregion 05. Widget interaction and utils */
}