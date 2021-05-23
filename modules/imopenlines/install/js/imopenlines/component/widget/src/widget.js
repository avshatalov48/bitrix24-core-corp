/**
 * Bitrix OpenLines widget
 * Widget private interface (base class)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2020 Bitrix
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

// messenger files
import {Controller} from 'im.controller';
import {
	RestMethod as ImRestMethod,
	RestMethodHandler as ImRestMethodHandler,
	EventType,
	FileStatus
} from 'im.const';

import {Cookie} from "im.lib.cookie";
import {Utils} from "im.lib.utils";
import {LocalStorage} from "im.lib.localstorage";
import {Logger} from "im.lib.logger";

import {Uploader} from "im.lib.uploader";

// TODO change BX.Promise, BX.Main.Date to IMPORT

export class Widget
{
/* region 01. Initialize and store data */

	constructor(params = {})
	{
		this.params = params;

		this.template = null;
		this.rootNode = this.params.node || document.createElement('div');

		this.messagesQueue = [];

		this.ready = true;
		this.widgetDataRequested = false;
		this.offline = false;

		this.inited = false;
		this.initEventFired = false;

		this.restClient = null;

		this.userRegisterData = {};
		this.customData = [];

		this.subscribers = {};

		this.configRequestXhr = null;

		this.initParams()
			.then(() => this.initRestClient())
			.then(() => this.initPullClient())
			.then(() => this.initCore())
			.then(() => this.initWidget())
			.then(() => this.initUploader())
			.then(() => this.initComplete())
		;
	}

	initParams()
	{
		this.code = this.params.code || '';
		this.host = this.params.host || '';
		this.language = this.params.language || 'en';
		this.copyright = this.params.copyright !== false;
		this.copyrightUrl = this.copyright && this.params.copyrightUrl? this.params.copyrightUrl: '';
		this.buttonInstance = typeof this.params.buttonInstance === 'object' && this.params.buttonInstance !== null? this.params.buttonInstance: null;

		this.pageMode = typeof this.params.pageMode === 'object' && this.params.pageMode;
		if (this.pageMode)
		{
			this.pageMode.useBitrixLocalize = this.params.pageMode.useBitrixLocalize === true;
			this.pageMode.placeholder = document.getElementById(this.params.pageMode.placeholder);
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

		if (this.pageMode && this.pageMode.placeholder)
		{
			this.rootNode = this.pageMode.placeholder;
		}
		else
		{
			if (document.body.firstChild)
			{
				document.body.insertBefore(this.rootNode, document.body.firstChild);
			}
			else
			{
				document.body.appendChild(this.rootNode);
			}
		}

		this.localize = this.pageMode && this.pageMode.useBitrixLocalize? window.BX.message: {};
		if (typeof this.params.localize === 'object')
		{
			this.addLocalize(this.params.localize);
		}

		let serverVariables = LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		return new Promise((resolve, reject) => resolve());
	}

	initRestClient()
	{
		this.restClient = new WidgetRestClient({endpoint: this.host+'/rest'});

		return new Promise((resolve, reject) => resolve());
	}

	initPullClient()
	{
		this.pullClient = new PullClient({
			serverEnabled: true,
			userId: 0,
			siteId: this.getSiteId(),
			restClient: this.restClient,
			skipStorageInit: true,
			configTimestamp: 0,
			skipCheckRevision: true,
			getPublicListMethod: 'imopenlines.widget.operator.get',
		});
		this.pullClientInited = false;

		return new Promise((resolve, reject) => resolve());
	}

	initCore()
	{
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
		if (
			Utils.types.isPlainObject(this.params.styles)
			&& (this.params.styles.backgroundColor || this.params.styles.iconColor)
		)
		{
			widgetVariables.styles = {};
			if (this.params.styles.backgroundColor)
			{
				widgetVariables.styles.backgroundColor = this.params.styles.backgroundColor;
			}
			if (this.params.styles.iconColor)
			{
				widgetVariables.styles.iconColor = this.params.styles.iconColor;
			}
		}

		this.controller = new Controller({
			host: this.getHost(),
			siteId: this.getSiteId(),
			userId: 0,
			languageId: this.language,
			pull: {client: this.pullClient},
			rest: {client: this.restClient},
			localize: this.localize,
			vuexBuilder: {
				database: !Utils.browser.isIe(),
				databaseName: 'imol/widget',
				databaseType: VuexBuilder.DatabaseType.localStorage,
				models: [
					WidgetModel.create().setVariables(widgetVariables)
				],
			}
		});

		return new Promise((resolve, reject) => {
			this.controller.ready().then(() => resolve());
		});
	}

	initWidget()
	{
		if (this.isUserRegistered())
		{
			this.restClient.setAuthId(this.getUserHash());
		}
		else
		{
			this.restClient.setAuthId(RestAuth.guest);
		}

		if (this.params.location && typeof LocationStyle[this.params.location] !== 'undefined')
		{
			this.controller.getStore().commit('widget/common', {location: this.params.location});
		}

		this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));

		this.controller.addRestAnswerHandler(
			WidgetRestAnswerHandler.create({
				widget: this,
				store: this.controller.getStore(),
				controller: this.controller,
			})
		);

		return new Promise((resolve, reject) => resolve());
	}

	initUploader()
	{
		this.uploader = new Uploader({
			generatePreview: true,
			sender: {
				host: this.host,
				customHeaders: {
					'Livechat-Auth-Id': this.getUserHash()
				},
				actionUploadChunk: 'imopenlines.widget.disk.upload',
				actionCommitFile: 'imopenlines.widget.disk.commit',
				actionRollbackUpload: 'imopenlines.widget.disk.rollbackUpload',
			}
		});

		this.uploader.subscribe('onStartUpload', event => {
			const eventData = event.getData();
			Logger.log('Uploader: onStartUpload', eventData);

			this.controller.getStore().dispatch('files/update', {
				chatId: this.getChatId(),
				id: eventData.id,
				fields: {
					status: FileStatus.upload,
					progress: 0
				}
			});
		});

		this.uploader.subscribe('onProgress', (event) => {
			const eventData = event.getData();
			Logger.log('Uploader: onProgress', eventData);

			this.controller.getStore().dispatch('files/update', {
				chatId: this.getChatId(),
				id: eventData.id,
				fields: {
					status: FileStatus.upload,
					progress: (eventData.progress === 100 ? 99 : eventData.progress),
				}
			});
		});

		this.uploader.subscribe('onSelectFile', (event) => {
			const eventData = event.getData();
			const file = eventData.file;
			Logger.log('Uploader: onSelectFile', eventData);

			let fileType = 'file';
			if (file.type.toString().startsWith('image'))
			{
				fileType = 'image';
			}
			else if (file.type.toString().startsWith('video'))
			{
				fileType = 'video';
			}

			this.controller.getStore().dispatch('files/add', {
				chatId: this.getChatId(),
				authorId: this.getUserId(),
				name: eventData.file.name,
				type: fileType,
				extension: file.name.split('.').splice(-1)[0],
				size: eventData.file.size,
				image: !eventData.previewData? false: {
					width: eventData.previewDataWidth,
					height: eventData.previewDataHeight,
				},
				status: FileStatus.upload,
				progress: 0,
				authorName: this.controller.application.getCurrentUser().name,
				urlPreview: eventData.previewData? URL.createObjectURL(eventData.previewData) : "",
			}).then(fileId => {
				this.addMessage('', {id: fileId, source: eventData, previewBlob: eventData.previewData})
			});
		});

		this.uploader.subscribe('onComplete', (event) => {
			const eventData = event.getData();
			Logger.log('Uploader: onComplete', eventData);

			this.controller.getStore().dispatch('files/update', {
				chatId: this.getChatId(),
				id: eventData.id,
				fields: {
					status: FileStatus.wait,
					progress: 100
				}
			});

			const message = this.messagesQueue.find(message => {
				return message.file.id === eventData.id
			});
			const fileType = this.controller.getStore().getters['files/get'](this.getChatId(), message.file.id, true).type;

			this.fileCommit({
				chatId: this.getChatId(),
				uploadId: eventData.result.data.file.id,
				messageText: message.text,
				messageId: message.id,
				fileId: message.file.id,
				fileType
			}, message);
		});

		this.uploader.subscribe('onUploadFileError', (event) => {
			const eventData = event.getData();
			Logger.log('Uploader: onUploadFileError', eventData);

			const message = this.messagesQueue.find(message => {
				return message.file.id === eventData.id
			});

			if (typeof message === 'undefined')
			{
				return;
			}

			this.fileError(this.getChatId(), message.file.id, message.id);
		});

		this.uploader.subscribe('onCreateFileError', (event) => {
			const eventData = event.getData();
			Logger.log('Uploader: onCreateFileError', eventData);

			const message = this.messagesQueue.find(message => {
				return message.file.id === eventData.id
			});

			this.fileError(this.getChatId(), message.file.id, message.id);
		});

		return new Promise((resolve, reject) => resolve());
	}

	initComplete()
	{
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

		return new Promise((resolve, reject) => resolve());
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
			this.controller.restClient.callMethod(RestMethod.widgetConfigGet, {code: this.code}, (xhr) => {this.configRequestXhr = xhr}).then((result) => {
				this.configRequestXhr = null;
				this.clearError();

				this.controller.executeRestAnswer(RestMethod.widgetConfigGet, result);

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
			query[ImRestMethodHandler.imChatGet] = [ImRestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetDialogGet+'][dialogId]'}];
			query[ImRestMethodHandler.imDialogMessagesGetInit] = [ImRestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.controller.application.getRequestMessageLimit(), convert_text: 'Y'}];
		}
		else
		{
			query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', ...this.getUserRegisterFields()}];
			query[ImRestMethodHandler.imChatGet] = [ImRestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetUserRegister+'][dialogId]'}];

			if (this.userRegisterData.hash || this.getUserHashCookie())
			{
				query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', trace_data: this.getCrmTraceData(), custom_data: this.getCustomData()}];
				query[ImRestMethodHandler.imDialogMessagesGetInit] = [ImRestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.controller.application.getRequestMessageLimit(), convert_text: 'Y'}];
			}
			if (this.isUserAgreeConsent())
			{
				query[RestMethod.widgetUserConsentApply] = [RestMethod.widgetUserConsentApply, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', consent_url: location.href}];
			}
		}

		query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
		query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {'CACHE': 'N'}];
		query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];

		this.controller.restClient.callBatch(query, (response) =>
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
			this.controller.executeRestAnswer(RestMethod.widgetConfigGet, configGet);

			let userGetResult = response[RestMethod.widgetUserGet];
			if (userGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);
				return false;
			}
			this.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

			let chatGetResult = response[ImRestMethodHandler.imChatGet];
			if (chatGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);
				return false;
			}
			this.controller.executeRestAnswer(ImRestMethodHandler.imChatGet, chatGetResult);

			let dialogGetResult = response[RestMethod.widgetDialogGet];
			if (dialogGetResult)
			{
				if (dialogGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);
					return false;
				}

				this.controller.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);
			}

			let dialogMessagesGetResult = response[ImRestMethodHandler.imDialogMessagesGetInit];
			if (dialogMessagesGetResult)
			{
				if (dialogMessagesGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);
					return false;
				}

				this.controller.getStore().dispatch('dialogues/saveDialog', {
					dialogId: this.controller.application.getDialogId(),
					chatId: this.controller.application.getChatId(),
				});

				this.controller.executeRestAnswer(ImRestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
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
				this.controller.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);
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
			}).catch((error) => {
				this.setError(error.ex.error, error.ex.error_description);
			});

			this.requestDataSend = false;
		}, false, false, Utils.getLogTrackingParams({name: 'widget.init.config', dialog: this.controller.application.getDialogData()}));
	}

	prepareFileData(files)
	{
		if (!Utils.types.isArray(files))
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

		if (!this.getUserId() || !this.getSiteId() || !this.restClient)
		{
			promise.reject({
				ex: { error: 'WIDGET_NOT_LOADED', error_description: 'Widget is not loaded.'}
			});
			return promise;
		}

		if (this.pullClientInited)
		{
			if (!this.pullClient.isConnected())
			{
				this.pullClient.scheduleReconnect();
			}
			promise.resolve(true);
			return promise;
		}

		this.controller.userId = this.getUserId();
		this.pullClient.userId = this.getUserId();
		this.pullClient.configTimestamp = config? config.server.config_timestamp: 0;
		this.pullClient.skipStorageInit = false;
		this.pullClient.storage = PullClient.StorageManager({
			userId: this.getUserId(),
			siteId: this.getSiteId()
		});

		this.pullClient.subscribe(
			new WidgetImPullCommandHandler({
				store: this.controller.getStore(),
				controller: this.controller,
				widget: this,
			})
		);
		this.pullClient.subscribe(
			new WidgetImopenlinesPullCommandHandler({
				store: this.controller.getStore(),
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
				if (result.status === PullClient.PullStatus.Online)
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

		this.pullClient.start({
			...config,
			skipReconnectToLastSession: true
		}).catch(function(){
			promise.reject({
				ex: { error: 'PULL_CONNECTION_ERROR', error_description: 'Pull is not connected.'}
			});
		});

		this.pullClientInited = true;

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

/* endregion 02. Push & Pull */

/* region 03. Template engine */

	attachTemplate()
	{
		if (this.template)
		{
			this.controller.getStore().commit('widget/common', {showed: true});
			return true;
		}

		this.rootNode.innerHTML = '';
		this.rootNode.appendChild(document.createElement('div'));

		let application = this;

		return this.controller.createVue(application, {
			el: this.rootNode.firstChild,
			template: '<bx-livechat/>',
			beforeCreate()
			{
				application.sendEvent({
					type: SubscriptionType.widgetOpen,
					data: {}
				});
				application.template = this;
			},
			destroyed()
			{
				application.sendEvent({
					type: SubscriptionType.widgetClose,
					data: {}
				});
				application.template = null;
				application.templateAttached = false;
				application.rootNode.innerHTML = '';
			}
		}).then(() => {
			return new Promise((resolve, reject) => resolve());
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

	mutateTemplateComponent(id, params)
	{
		return Vue.mutateComponent(id, params);
	}

/* endregion 03. Template engine */

/* region 04. Rest methods */

	addMessage(text = '', file = null)
	{
		if (!text && !file)
		{
			return false;
		}

		Logger.warn('addMessage', text, file);

		if (!this.controller.application.isUnreadMessagesLoaded())
		{
			this.sendMessage({id: 0, text, file});
			this.processSendMessages();

			return true;
		}

		this.controller.getStore().commit('application/increaseDialogExtraCount');

		let params = {};
		if (file)
		{
			params.FILE_ID = [file.id];
		}

		this.controller.getStore().dispatch('messages/add', {
			chatId: this.getChatId(),
			authorId: this.getUserId(),
			text: text,
			params,
			sending: !file,
		}).then(messageId => {

			if (!this.isDialogStart())
			{
				this.controller.getStore().commit('widget/common', {dialogStart:true});
			}

			this.messagesQueue.push({
				id: messageId,
				text,
				file,
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

	uploadFile(event)
	{
		if (!event)
		{
			return false;
		}

		if (!this.getChatId())
		{
			this.requestData();
		}

		this.uploader.addFilesFromEvent(event);
	}

	cancelUploadFile(fileId)
	{
		let element = this.messagesQueue.find(element => element.file && element.file.id === fileId);
		if (element)
		{
			this.uploader.deleteTask(fileId);

			if (element.xhr)
			{
				element.xhr.abort();
			}
			this.controller.getStore().dispatch('messages/delete', {
				chatId: this.getChatId(),
				id: element.id,
			}).then(() => {
				this.controller.getStore().dispatch('files/delete', {
					chatId: this.getChatId(),
					id: element.file.id,
				});
				this.messagesQueue = this.messagesQueue.filter(el => el.id !== element.id);
			});
		}
	}

	processSendMessages()
	{
		if (!this.getDiskFolderId())
		{
			this.requestDiskFolderId().then(() => {
				this.processSendMessages();
			}).catch(() => {
				Logger.warn('uploadFile', 'Error get disk folder id');
				return false;
			});

			return false;
		}

		if (this.offline)
		{
			return false;
		}

		this.messagesQueue.filter(element => !element.sending).forEach(element => {
			element.sending = true;
			if (element.file)
			{
				this.sendMessageWithFile(element);
			}
			else
			{
				this.sendMessage(element);
			}
		});

		return true;
	}

	sendMessage(message)
	{
		this.controller.application.stopWriting();

		let quiteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.getDialogId());
		if (quiteId)
		{
			let quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.getChatId(), quiteId);
			if (quoteMessage)
			{
				let user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);

				let newMessage = [];
				newMessage.push("------------------------------------------------------");
				newMessage.push((user.name? user.name: this.getLocalize('BX_LIVECHAT_SYSTEM_MESSAGE')));
				newMessage.push(quoteMessage.text);
				newMessage.push('------------------------------------------------------');
				newMessage.push(message.text);
				message.text = newMessage.join("\n");

				this.quoteMessageClear();
			}
		}

		message.chatId = this.getChatId();

		this.controller.restClient.callMethod(ImRestMethod.imMessageAdd, {
			'TEMPLATE_ID': message.id,
			'CHAT_ID': message.chatId,
			'MESSAGE': message.text
		}, null, null, Utils.getLogTrackingParams({
			name: ImRestMethod.imMessageAdd,
			data: {timMessageType: 'text'},
			dialog: this.getDialogData()
		})).then(response => {
			this.controller.executeRestAnswer(ImRestMethodHandler.imMessageAdd, response, message);
		}).catch(error => {
			this.controller.executeRestAnswer(ImRestMethodHandler.imMessageAdd, error, message);
		});

		return true;
	}

	sendMessageWithFile(message)
	{
		this.controller.application.stopWriting();

		const diskFolderId = this.getDiskFolderId();
		message.chatId = this.getChatId();

		this.uploader.senderOptions.customHeaders['Livechat-Dialog-Id'] = message.chatId;
		this.uploader.senderOptions.customHeaders['Livechat-Auth-Id'] = this.getUserHash();

		this.uploader.addTask({
			taskId: message.file.id,
			fileData: message.file.source.file,
			fileName: message.file.source.file.name,
			generateUniqueName: true,
			diskFolderId: diskFolderId,
			previewBlob: message.file.previewBlob,
			chunkSize: this.localize.isCloud ? Uploader.CLOUD_MAX_CHUNK_SIZE : Uploader.BOX_MIN_CHUNK_SIZE,
		});
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
				retry: false,
			});
		}
	}

	requestDiskFolderId()
	{
		if (this.requestDiskFolderPromise)
		{
			return this.requestDiskFolderPromise;
		}

		this.requestDiskFolderPromise = new Promise((resolve, reject) =>
		{
			if (
				this.flagRequestDiskFolderIdSended
				|| this.getDiskFolderId()
			)
			{
				this.flagRequestDiskFolderIdSended = false;
				resolve();
				return true;
			}

			this.flagRequestDiskFolderIdSended = true;

			this.controller.restClient.callMethod(ImRestMethod.imDiskFolderGet, {chat_id: this.controller.application.getChatId()}).then(response => {
				this.controller.executeRestAnswer(ImRestMethodHandler.imDiskFolderGet, response);
				this.flagRequestDiskFolderIdSended = false;
				resolve();
			}).catch(error => {
				this.flagRequestDiskFolderIdSended = false;
				this.controller.executeRestAnswer(ImRestMethodHandler.imDiskFolderGet, error);
				reject();
			});
		});

		return this.requestDiskFolderPromise;
	}

	fileCommit(params, message)
	{
		this.controller.restClient.callMethod(ImRestMethod.imDiskFileCommit, {
			chat_id: params.chatId,
			upload_id: params.uploadId,
			message: params.messageText,
			template_id: params.messageId,
			file_template_id: params.fileId,
		}, null, null, Utils.getLogTrackingParams({
			name: ImRestMethod.imDiskFileCommit,
			data: {timMessageType: params.fileType},
			dialog: this.getDialogData()
		})).then(response => {
			this.controller.executeRestAnswer(ImRestMethodHandler.imDiskFileCommit, response, message);
		}).catch(error => {
			this.controller.executeRestAnswer(ImRestMethodHandler.imDiskFileCommit, error, message);
		});

		return true;
	}

	getDialogHistory(lastId, limit = this.controller.application.getRequestMessageLimit())
	{
		this.controller.restClient.callMethod(ImRestMethod.imDialogMessagesGet, {
			'CHAT_ID': this.getChatId(),
			'LAST_ID': lastId,
			'LIMIT': limit,
			'CONVERT_TEXT': 'Y'
		}).then(result => {
			this.controller.executeRestAnswer(ImRestMethodHandler.imDialogMessagesGet, result);
			this.template.$emit(EventType.dialog.requestHistoryResult, {count: result.data().messages.length});
		}).catch(result => {
			this.template.$emit(EventType.dialog.requestHistoryResult, {error: result.error().ex});
		});
	}

	getDialogUnread(lastId, limit = this.controller.application.getRequestMessageLimit())
	{
		const promise = new BX.Promise();

		if (!lastId)
		{
			lastId = this.controller.getStore().getters['messages/getLastId'](this.controller.application.getChatId());
		}

		if (!lastId)
		{
			this.template.$emit(EventType.dialog.requestUnreadResult, {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});
			promise.reject();
			return promise;
		}

		this.controller.application.readMessage(lastId, true, true).then(() =>
		{
			let query = {
				[ImRestMethodHandler.imDialogRead]: [ImRestMethod.imDialogRead, {
					dialog_id: this.getDialogId(),
					message_id: lastId
				}],
				[ImRestMethodHandler.imChatGet]: [ImRestMethod.imChatGet, {
					dialog_id: this.getDialogId()
				}],
				[ImRestMethodHandler.imDialogMessagesGetUnread]: [ImRestMethod.imDialogMessagesGet, {
					chat_id: this.getChatId(),
					first_id: lastId,
					limit: limit,
					convert_text: 'Y'
				}]
			};

			this.controller.restClient.callBatch(query, (response) =>
			{
				if (!response)
				{
					this.template.$emit(EventType.dialog.requestUnreadResult, {error: {error: 'EMPTY_RESPONSE', error_description: 'Server returned an empty response.'}});

					promise.reject();
					return false;
				}

				let chatGetResult = response[ImRestMethodHandler.imChatGet];
				if (!chatGetResult.error())
				{
					this.controller.executeRestAnswer(ImRestMethodHandler.imChatGet, chatGetResult);
				}

				let dialogMessageUnread = response[ImRestMethodHandler.imDialogMessagesGetUnread];
				if (dialogMessageUnread.error())
				{
					this.template.$emit(EventType.dialog.requestUnreadResult, {error: dialogMessageUnread.error().ex});
				}
				else
				{
					this.controller.executeRestAnswer(ImRestMethodHandler.imDialogMessagesGetUnread, dialogMessageUnread);
					this.template.$emit(EventType.dialog.requestUnreadResult, {
						firstMessageId: dialogMessageUnread.data().messages.length > 0? dialogMessageUnread.data().messages[0].id: 0,
						count: dialogMessageUnread.data().messages.length
					});
				}

				promise.fulfill(response);

			}, false, false, Utils.getLogTrackingParams({name: ImRestMethodHandler.imDialogMessagesGetUnread, dialog: this.getDialogData()}));
		});

		return promise;
	}

	retrySendMessage(message)
	{
		if (this.messagesQueue.find(el => el.id === message.id))
		{
			return false;
		}

		this.messagesQueue.push({
			id: message.id,
			text: message.text,
			sending: false
		});

		this.controller.application.setSendingMessageFlag(message.id);

		this.processSendMessages();
	}

	readMessage(messageId)
	{
		if (this.offline)
		{
			return false;
		}

		return this.controller.application.readMessage(messageId);
	}

	quoteMessage(id)
	{
		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				quoteId: id
			}
		});
	}

	reactMessage(id, reaction)
	{
		this.controller.application.reactMessage(id, reaction.type, reaction.action);
	}

	execMessageKeyboardCommand(data)
	{
		if (data.action === 'ACTION' && data.params.action === 'LIVECHAT')
		{
			let {dialogId, messageId} = data.params;
			let values = JSON.parse(data.params.value);

			let sessionId = parseInt(values.SESSION_ID);
			if (sessionId !== this.getSessionId() || this.isSessionClose())
			{
				alert(this.localize.BX_LIVECHAT_ACTION_EXPIRED);
				return false;
			}

			this.controller.restClient.callMethod(RestMethod.widgetActionSend, {
				'MESSAGE_ID': messageId,
				'DIALOG_ID': dialogId,
				'ACTION_VALUE': data.params.value,
			});

			return true;
		}

		if (data.action !== 'COMMAND')
		{
			return false;
		}

		let {dialogId, messageId, botId, command, params} = data.params;

		this.controller.restClient.callMethod(ImRestMethod.imMessageCommand, {
			'MESSAGE_ID': messageId,
			'DIALOG_ID': dialogId,
			'BOT_ID': botId,
			'COMMAND': command,
			'COMMAND_PARAMS': params,
		});

		return true;
	}

	quoteMessageClear()
	{
		this.controller.getStore().dispatch('dialogues/update', {
			dialogId: this.controller.application.getDialogId(),
			fields: {
				quoteId: 0
			}
		});
	}

	sendDialogVote(result)
	{
		if (!this.getSessionId())
		{
			return false;
		}

		this.controller.restClient.callMethod(RestMethod.widgetVoteSend, {
			'SESSION_ID': this.getSessionId(),
			'ACTION': result
		}).catch((result) => {
			this.controller.getStore().commit('widget/dialog', {userVote: VoteType.none});
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
		this.controller.restClient.callBatch(query, (response) =>
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
			this.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

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

		this.controller.getStore().commit('widget/dialog', {userConsent: result});

		if (result && this.isUserRegistered())
		{
			this.controller.restClient.callMethod(RestMethod.widgetUserConsentApply, {
				config_id: this.getConfigId(),
				consent_url: location.href
			});
		}
	}

/* endregion 05. Templates and template interaction */

/* region 05. Widget interaction and utils */

	start()
	{
		if (!this.controller || !this.controller.getStore())
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
		if (!this.controller.getStore())
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
		if (!this.controller.getStore())
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

		if (this.controller.getStore().state.widget.common.reopen)
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
		return this.controller.getStore().state.widget.common.configId;
	}

	isWidgetDataRequested()
	{
		return this.widgetDataRequested;
	}

	isChatLoaded()
	{
		return this.controller.getStore().state.application.dialog.chatId > 0;
	}

	isSessionActive()
	{
		return !this.controller.getStore().state.widget.dialog.sessionClose;
	}

	isUserAgreeConsent()
	{
		return this.controller.getStore().state.widget.dialog.userConsent;
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
			customData = [{MESSAGE: this.localize.BX_LIVECHAT_EXTRA_SITE+': [URL]'+location.href+'[/URL]'}];
		}

		return JSON.stringify(customData);
	}

	isUserLoaded()
	{
		return this.controller.getStore().state.widget.user.id > 0;
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
		return this.controller.getStore().state.widget.common.configId;
	}

	isDialogStart()
	{
		return this.controller.getStore().state.widget.common.dialogStart;
	}

	getChatId()
	{
		return this.controller.getStore().state.application.dialog.chatId;
	}

	getDialogId()
	{
		return this.controller.getStore().state.application.dialog.dialogId;
	}

	getDiskFolderId()
	{
		return this.controller.getStore().state.application.dialog.diskFolderId;
	}

	getDialogData(dialogId = this.getDialogId())
	{
		return this.controller.getStore().state.dialogues.collection[dialogId];
	}

	getSessionId()
	{
		return this.controller.getStore().state.widget.dialog.sessionId;
	}

	isSessionClose()
	{
		return this.controller.getStore().state.widget.dialog.sessionClose;
	}

	getUserHash()
	{
		return this.controller.getStore().state.widget.user.hash;
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
		return this.controller.getStore().state.widget.user.id;
	}

	getUserData()
	{
		if (!this.controller.getStore())
		{
			console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
			return false;
		}

		return this.controller.getStore().state.widget.user;
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
			'user_hash': this.userRegisterData.hash || this.getUserHashCookie() || '',
			'consent_url': this.controller.getStore().state.widget.common.consentUrl? location.href: '',
			'trace_data': this.getCrmTraceData(),
			'custom_data': this.getCustomData()
		}
	}

	getWidgetLocationCode()
	{
		return LocationStyle[this.controller.getStore().state.widget.common.location];
	}

	setUserRegisterData(params)
	{
		if (!this.controller.getStore())
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
			&& this.userRegisterData.hash !== this.getUserHash()
		)
		{
			this.setNewAuthToken(this.userRegisterData.hash);
		}
	}

	setNewAuthToken(authToken = '')
	{
		this.controller.getStoreBuilder().clearModelState();
		Cookie.set(null, 'LIVECHAT_HASH', '', {expires: 365*86400, path: '/'});

		this.controller.restClient.setAuthId(RestAuth.guest, authToken);
	}

	setCustomData(params)
	{
		if (!this.controller.getStore())
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
		if (code === 'LIVECHAT_AUTH_FAILED')
		{
			localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="javascript:void();" onclick="location.reload()">').replace('#LINK_END#', '</a>');
			this.setNewAuthToken();
		}
		else if (code === 'LIVECHAT_AUTH_PORTAL_USER')
		{
			localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="'+this.host+'">').replace('#LINK_END#', '</a>')
		}
		else if (code.endsWith('LOCALIZED'))
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