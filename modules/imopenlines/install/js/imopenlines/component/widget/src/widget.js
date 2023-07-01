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
	LocationStyle,
	SubscriptionType,
	SubscriptionTypeCheck,
	RestMethod,
	RestAuth,
	WidgetEventType
} from "./const";

import {WidgetModel} from "./model";
import {WidgetRestClient} from "./utils/restclient";
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
import 'ui.vue.components.crm.form';

// messenger files
import {Controller} from 'im.controller';
import {
	RestMethod as ImRestMethod,
	RestMethodHandler as ImRestMethodHandler,
	EventType
} from 'im.const';

import {Cookie} from "im.lib.cookie";
import {Utils} from "im.lib.utils";
import {LocalStorage} from "im.lib.localstorage";
import {Logger} from "im.lib.logger";

import { EventEmitter } from "main.core.events";
import { ZIndexManager } from "main.core.minimal";

export class Widget
{
/* region 01. Initialize and store data */
	params: Object = null;
	template: Object = null; // Vue instance
	rootNode: Object = null;

	restClient: Object = null;
	pullClient: Object = null;

	ready: boolean = true; // true if there are no initialization errors
	inited: boolean = false; // true if all preparations are done
	offline: boolean = false; // true if Pull-client is offline
	widgetConfigRequest: Object = null; // XHR-request from widget.config.get, can be aborted before completion

	// this block can be set from public config
	userRegisterData: Object = {}; // user info
	customData: Array = []; // additional info to send to server
	options: Object = { checkSameDomain: true };
	subscribers: Object = {}; // external event subscribers

	// fields from params
	code: string = ''; // livechat code
	host: string = '';
	language: string = '';
	copyright: boolean = true;
	copyrightUrl: string = '';
	buttonInstance: Object = null; // widget button
	localize: Object = null;
	pageMode: Object = null; // fullscreen livechat mode options

	constructor(params = {})
	{
		this.params = params;

		//TODO: remove
		this.messagesQueue = [];

		EventEmitter.subscribe(WidgetEventType.requestData, this.requestData.bind(this));
		EventEmitter.subscribe(WidgetEventType.createSession, this.createChat.bind(this));
		EventEmitter.subscribe(WidgetEventType.openSession, this.openSession.bind(this));

		this.initParams();
		this.initRestClient();
		this.initPullClient();
		this.initCore().then(() => {
			this.initWidget();
			this.initComplete();
		});
	}

	initParams()
	{
		this.rootNode = this.params.node || document.createElement('div');

		this.code = this.params.code || '';
		this.host = this.params.host || '';
		this.language = this.params.language || 'en';
		this.copyright = this.params.copyright !== false;
		this.copyrightUrl = (this.copyright && this.params.copyrightUrl) ? this.params.copyrightUrl: '';
		if (this.params.buttonInstance && typeof this.params.buttonInstance === 'object')
		{
			this.buttonInstance = this.params.buttonInstance;
		}

		if (this.params.pageMode && typeof this.params.pageMode === 'object')
		{
			this.pageMode = {
				useBitrixLocalize: this.params.pageMode.useBitrixLocalize === true,
				placeholder: document.querySelector(`#${this.params.pageMode.placeholder}`)
			};
		}

		const errors = this.checkRequiredFields();
		if (errors.length > 0)
		{
			errors.forEach(error => console.warn(error));
			this.ready = false;
		}

		this.setRootNode();

		this.localize = this.pageMode && this.pageMode.useBitrixLocalize? window.BX.message: {};
		this.setLocalize();
	}

	initRestClient()
	{
		this.restClient = new WidgetRestClient({endpoint: `${this.host}/rest`});
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
	}

	initCore()
	{
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
					WidgetModel.create().setVariables(this.getWidgetVariables())
				],
			}
		});

		return this.controller.ready();
	}

	initWidget()
	{
		this.restClient.setAuthId(this.getRestAuthId());
		this.setModelData();
		// TODO: move from controller
		this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));

		this.controller.addRestAnswerHandler(
			WidgetRestAnswerHandler.create({
				widget: this,
				store: this.controller.getStore(),
				controller: this.controller,
			})
		);
	}

	// if start or open methods were called before core init - we will have appropriate flags
	// for full-page livechat we always call open
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
	}

	// public method
	// initially called from imopenlines/lib/livechatmanager.php:16
	// if core is not ready yet - set flag and call start once again in this.initComplete()
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

	// public method
	// if core is not ready yet - set flag and call start once again in this.initComplete()
	// if not inited yet - request widget data
	open(params = {})
	{
		if (!this.controller.getStore())
		{
			this.callOpenFlag = true;
			return true;
		}

		if (!params.openFromButton && this.buttonInstance)
		{
			this.buttonInstance.wm.showById('openline_livechat');
		}

		const {error, stop} = this.checkForErrorsBeforeOpen();
		if (stop)
		{
			return false;
		}

		if (!error && !this.inited)
		{
			this.requestWidgetData();
		}

		this.attachTemplate();
	}

	requestWidgetData()
	{
		if (!this.ready)
		{
			console.error('LiveChatWidget.start: widget code or host is not specified');
			return false;
		}

		// if user is registered or we have its hash - proceed to getting chat and messages
		if (this.isUserReady() || this.isHashAvailable())
		{
			this.requestData();
			this.inited = true;
			this.fireInitEvent();

			return true;
		}

		// if there is no info about user - we need to get config and wait for first message
		this.controller.restClient.callMethod(
			RestMethod.widgetConfigGet,
			{code: this.code},
			(xhr) => { this.widgetConfigRequest = xhr; }
		).then((result) => {
			this.widgetConfigRequest = null;
			this.clearError();

			this.controller.executeRestAnswer(RestMethod.widgetConfigGet, result);

			if (!this.inited)
			{
				this.inited = true;
				this.fireInitEvent();
			}
		}).catch(error => {
			this.widgetConfigRequest = null;
			this.setError(error.error().ex.error, error.error().ex.error_description);
		});

		if (this.isConfigDataLoaded())
		{
			this.inited = true;
			this.fireInitEvent();
		}
	}

	// get all other info (dialog, chat, messages etc)
	requestData()
	{
		Logger.log('requesting data from widget');
		if (this.requestDataSend)
		{
			return true;
		}

		this.requestDataSend = true;

		// if there is uncompleted widget.config.get request - abort it (because we will do it anyway)
		if (this.widgetConfigRequest)
		{
			this.widgetConfigRequest.abort();
		}

		const callback = this.handleBatchRequestResult.bind(this);
		this.controller.restClient.callBatch(
			this.getDataRequestQuery(),
			callback,
			false,
			false,
			Utils.getLogTrackingParams(
				{
					name: 'widget.init.config',
					dialog: this.controller.application.getDialogData()
				}
			)
		);
	}

	createChat()
	{
		return new Promise((resolve, reject) => {
			this.controller.restClient.callBatch(
				this.getCreateChatRequestQuery(),
				(result) => {
					this.handleBatchCreateChatRequestResult(result).then(() => {
						resolve();
					});
				},
				false,
				false,
			);
		});
	}

	handleBatchRequestResult(response)
	{
		if (!response)
		{
			this.requestDataSend = false;
			this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
			return false;
		}

		this.handleConfigGet(response)
			.then(() => this.handleUserGet(response))
			.then(() => this.handleChatGet(response))
			.then(() => this.handleDialogGet(response))
			.then(() => this.handleDialogMessagesGet(response))
			.then(() => this.handleUserRegister(response))
			.then(() => this.handlePullRequests(response))
			.catch(({code, description}) => {
				this.setError(code, description);
			})
			.finally(() => {
				this.requestDataSend = false;
			});
	}

	handleBatchCreateChatRequestResult(response)
	{
		if (!response)
		{
			this.requestDataSend = false;
			this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
			return false;
		}

		return this.handleChatCreate(response)
			.then(() => this.handleChatGet(response))
			.then(() => this.handleDialogGet(response))
			.catch(({code, description}) => {
				this.setError(code, description);
			})
			.finally(() => {
				this.requestDataSend = false;
			});
	}

	handleBatchOpenSessionRequestResult(response)
	{
		if (!response)
		{
			this.requestDataSend = false;
			this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
			return false;
		}

		return this.handleChatGet(response)
			.then(() => this.handleDialogGet(response))
			.then(() => this.handleDialogMessagesGet(response))
			.catch(({code, description}) => {
				this.setError(code, description);
			})
			.finally(() => {
				this.requestDataSend = false;
			});
	}

	getDataRequestQuery(): Object
	{
		// always widget.config.get
		const query = {
			[RestMethod.widgetConfigGet]: [RestMethod.widgetConfigGet, {code: this.code}]
		};

		if (this.isUserRegistered())
		{
			// widget.dialog.get
			query[RestMethod.widgetDialogGet] = [
				RestMethod.widgetDialogGet,
				{
					config_id: this.getConfigId(),
					trace_data: this.getCrmTraceData(),
					custom_data: this.getCustomData()
				}
			];

			// im.chat.get
			query[ImRestMethodHandler.imChatGet] = [
				ImRestMethod.imChatGet,
				{
					dialog_id: `$result[${RestMethod.widgetDialogGet}][dialogId]`
				}
			];

			// im.dialog.messages.get
			query[ImRestMethodHandler.imDialogMessagesGetInit] = [
				ImRestMethod.imDialogMessagesGet,
				{
					chat_id: `$result[${RestMethod.widgetDialogGet}][chatId]`,
					limit: this.controller.application.getRequestMessageLimit(),
					convert_text: 'Y'
				}
			];
		}
		else
		{
			// widget.user.register
			query[RestMethod.widgetUserRegister] = [
				RestMethod.widgetUserRegister,
				{
					config_id: `$result[${RestMethod.widgetConfigGet}][configId]`,
					...this.getUserRegisterFields()
				}
			];

			// im.chat.get
			query[ImRestMethodHandler.imChatGet] = [
				ImRestMethod.imChatGet,
				{dialog_id: `$result[${RestMethod.widgetUserRegister}][dialogId]`}
			];

			if (this.userRegisterData.hash || this.getUserHashCookie())
			{
				// widget.dialog.get
				query[RestMethod.widgetDialogGet] = [
					RestMethod.widgetDialogGet,
					{
						config_id: `$result[${RestMethod.widgetConfigGet}][configId]`,
						trace_data: this.getCrmTraceData(),
						custom_data: this.getCustomData()
					}
				];

				// im.dialog.messages.get
				query[ImRestMethodHandler.imDialogMessagesGetInit] = [
					ImRestMethod.imDialogMessagesGet,
					{
						chat_id: `$result[${RestMethod.widgetDialogGet}][chatId]`,
						limit: this.controller.application.getRequestMessageLimit(),
						convert_text: 'Y'
					}
				];
			}

			if (this.isUserAgreeConsent())
			{
				// widget.user.consent.apply
				query[RestMethod.widgetUserConsentApply] = [
					RestMethod.widgetUserConsentApply,
					{
						config_id: `$result[${RestMethod.widgetConfigGet}][configId]`,
						consent_url: location.href
					}
				];
			}
		}

		query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
		query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {'CACHE': 'N'}];
		query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];

		return query;
	}

	getOpenSessionQuery(chatId): Object
	{
		// imopenlines.widget.dialog.get
		const query = {
			[RestMethod.widgetDialogGet]: [RestMethod.widgetDialogGet, {
				config_id: this.getConfigId(),
				chat_id: chatId
			}]
		};

		query[ImRestMethodHandler.imChatGet] = [
			ImRestMethod.imChatGet,
			{
				dialog_id: `chat${chatId}`
			}
		];

		// im.dialog.messages.get
		query[ImRestMethodHandler.imDialogMessagesGetInit] = [
			ImRestMethod.imDialogMessagesGet,
			{
				chat_id: chatId,
				limit: 50,
				convert_text: 'Y'
			}
		];

		return query;
	}

	getCreateChatRequestQuery(): Object
	{
		const query = {};

		// widget.chat.register
		query[RestMethod.widgetChatCreate] = [
			RestMethod.widgetChatCreate,
			{
				config_id: this.getConfigId(),
				...this.getUserRegisterFields()
			}
		];

		// im.chat.get
		query[ImRestMethodHandler.imChatGet] = [
			ImRestMethod.imChatGet,
			{dialog_id: `$result[${RestMethod.widgetChatCreate}][dialogId]`}
		];

		// widget.dialog.get
		query[RestMethod.widgetDialogGet] = [
			RestMethod.widgetDialogGet,
			{
				config_id: this.getConfigId(),
				trace_data: this.getCrmTraceData(),
				custom_data: this.getCustomData()
			}
		];

		if (this.isUserAgreeConsent())
		{
			// widget.user.consent.apply
			query[RestMethod.widgetUserConsentApply] = [
				RestMethod.widgetUserConsentApply,
				{
					config_id: this.getConfigId(),
					consent_url: location.href
				}
			];
		}

		query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
		query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {'CACHE': 'N'}];
		query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];

		return query;
	}

	openSession(event)
	{
		const eventData = event.getData();

		return new Promise((resolve, reject) => {
			const dialog = this.controller.getStore().getters['dialogues/get'](eventData.session.dialogId);
			if (dialog)
			{
				this.controller.getStore().commit('application/set', {dialog: {
						chatId: eventData.session.chatId,
						dialogId: eventData.session.dialogId,
						diskFolderId: 0,
					}});

				this.controller.getStore().commit('widget/common', {isCreateSessionMode: false});

				resolve();
				return;
			}

			this.controller.restClient.callBatch(
				this.getOpenSessionQuery(eventData.session.chatId),
				(result) => {
					this.handleBatchOpenSessionRequestResult(result).then(() => {
						this.controller.getStore().commit('widget/common', {isCreateSessionMode: false});
						resolve();
					});
				},
				false,
				false,
			);
		});
	}

	prepareFileData(files)
	{
		if (!Array.isArray(files))
		{
			return files;
		}

		return files.map(file =>
		{
			const hash = (window.md5 || md5)(`${this.getUserId()}|${file.id}|${this.getUserHash()}`);
			const urlParam = `livechat_auth_id=${hash}&livechat_user_id=${this.getUserId()}`;
			if (file.urlPreview)
			{
				file.urlPreview = `${file.urlPreview}&${urlParam}`;
			}
			if (file.urlShow)
			{
				file.urlShow = `${file.urlShow}&${urlParam}`;
			}
			if (file.urlDownload)
			{
				file.urlDownload = `${file.urlDownload}&${urlParam}`;
			}

			return file;
		});
	}

	checkRequiredFields(): Array
	{
		const errors = [];
		if (typeof this.code === 'string' && this.code.length <= 0)
		{
			errors.push(`LiveChatWidget.constructor: code is not correct (${this.code})`);
		}

		if (typeof this.host === 'string' && (this.host.length <= 0 || !this.host.startsWith('http')))
		{
			errors.push(`LiveChatWidget.constructor: host is not correct (${this.host})`);
		}

		return errors;
	}

	setRootNode()
	{
		if (this.pageMode && this.pageMode.placeholder)
		{
			this.rootNode = this.pageMode.placeholder;
		}
		else if (document.body.firstChild)
		{
			document.body.insertBefore(this.rootNode, document.body.firstChild);
		}
		else
		{
			document.body.append(this.rootNode);
		}
	}

	setLocalize()
	{
		if (typeof this.params.localize === 'object')
		{
			this.addLocalize(this.params.localize);
		}

		const serverVariables = LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}
	}

	getWidgetVariables(): Object
	{
		const variables = {
			common: {
				host: this.getHost(),
				pageMode: !!this.pageMode,
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

		if (this.params.styles)
		{
			variables.styles = {};
			if (this.params.styles.backgroundColor)
			{
				variables.styles.backgroundColor = this.params.styles.backgroundColor;
			}
			if (this.params.styles.iconColor)
			{
				variables.styles.iconColor = this.params.styles.iconColor;
			}
		}

		return variables;
	}

	getRestAuthId()
	{
		return this.isUserRegistered() ? this.getUserHash() : RestAuth.guest;
	}

	setModelData()
	{
		if (this.params.location && LocationStyle[this.params.location])
		{
			this.controller.getStore().commit('widget/common', {location: this.params.location});
		}
	}

	checkForErrorsBeforeOpen()
	{
		const result = {
			error: false,
			stop: false
		};

		if (!this.checkBrowserVersion())
		{
			this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
			result.error = true;
		}
		else if (Utils.versionCompare(Vue.version(), '2.1') < 0)
		{
			alert(this.localize.BX_LIVECHAT_OLD_VUE);
			console.error(`LiveChatWidget.error: OLD_VUE_VERSION (${this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', Vue.version())})`);
			result.error = true;
			result.stop = true;
		}
		else if (this.isSameDomain())
		{
			this.setError('LIVECHAT_SAME_DOMAIN', this.localize.BX_LIVECHAT_SAME_DOMAIN);
			result.error = true;
		}

		return result;
	}

	isSameDomain()
	{
		if (typeof BX === 'undefined' || !BX.isReady)
		{
			return false;
		}

		if (!this.options.checkSameDomain)
		{
			return false;
		}

		return this.host.lastIndexOf(`.${location.hostname}`) > -1;
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

	startPullClient(config): Promise
	{
		return new Promise((resolve, reject) => {
			if (!this.getUserId() || !this.getSiteId() || !this.restClient)
			{
				return reject({
					ex: { error: 'WIDGET_NOT_LOADED', error_description: 'Widget is not loaded.'}
				});
			}

			if (this.pullClientInited)
			{
				if (!this.pullClient.isConnected())
				{
					this.pullClient.scheduleReconnect();
				}
				return resolve(true);
			}

			this.controller.userId = this.getUserId();
			this.pullClient.userId = this.getUserId();
			this.pullClient.configTimestamp = config? config.server.config_timestamp: 0;
			this.pullClient.skipStorageInit = false;
			this.pullClient.storage = new PullClient.StorageManager({
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
						resolve(true);
						this.pullConnectedFirstTime();
					}
				}
			});

			if (this.template)
			{
				this.template.$Bitrix.PullClient.set(this.pullClient);
			}

			this.pullClient.start({
				...config,
				skipReconnectToLastSession: true
			}).catch(() => {
				reject({
					ex: { error: 'PULL_CONNECTION_ERROR', error_description: 'Pull is not connected.'}
				});
			});

			this.pullClientInited = true;
		});
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
			this.onPullOnlineStatus();
		}
		else if (data.status === PullClient.PullStatus.Offline)
		{
			this.pullRequestMessage = true;
			this.offline = true;
		}
	}

	onPullOnlineStatus()
	{
		this.offline = false;

		// if we go online after going offline - we need to request messages
		if (this.pullRequestMessage)
		{
			this.controller.pullBaseHandler.option.skip = true;

			Logger.warn('Requesting getDialogUnread after going online');
			EventEmitter.emitAsync(EventType.dialog.requestUnread, {chatId: this.controller.application.getChatId()}).then(() => {
					EventEmitter.emit(EventType.dialog.scrollOnStart, {chatId: this.controller.application.getChatId()});
					this.controller.pullBaseHandler.option.skip = false;
					EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
				})
				.catch(() => {
					this.controller.pullBaseHandler.option.skip = false;
				});

			this.pullRequestMessage = false;
		}
		else
		{
			EventEmitter.emit(EventType.dialog.readMessage);
			EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
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
		this.rootNode.append(document.createElement('div'));

		const application = this;

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

				if (ZIndexManager !== undefined)
				{
					const stack = ZIndexManager.getOrAddStack(document.body);
					stack.setBaseIndex(1000000); // some big value
					this.$bitrix.Data.set('zIndexStack', stack);
				}
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

	// public method
	mutateTemplateComponent(id, params)
	{
		return Vue.mutateComponent(id, params);
	}

/* endregion 03. Template engine */

/* region 04. Widget interaction and utils */

	// public method
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
	}

	isUserRegistered()
	{
		return !!this.getUserHash();
	}

	isConfigDataLoaded()
	{
		return this.controller.getStore().state.widget.common.configId;
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

	isUserReady()
	{
		return this.isConfigDataLoaded() && this.isUserRegistered();
	}

	isHashAvailable()
	{
		return !this.isUserRegistered()
			&& (this.userRegisterData.hash || this.getUserHashCookie());
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
		if (!this.controller || !this.controller.getStore())
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
		};
	}

	getWidgetLocationCode()
	{
		return LocationStyle[this.controller.getStore().state.widget.common.location];
	}

	// public method
	setUserRegisterData(params)
	{
		if (!this.controller || !this.controller.getStore())
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

	// public method
	setOption(name, value)
	{
		this.options[name] = value;
		return true;
	}

	// public method
	setCustomData(params)
	{
		if (!this.controller || !this.controller.getStore())
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
			localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="'+this.host+'">').replace('#LINK_END#', '</a>');
		}
		else if (code === 'LIVECHAT_SAME_DOMAIN')
		{
			localizeDescription = this.getLocalize('BX_LIVECHAT_SAME_DOMAIN');
			let link = this.getLocalize('BX_LIVECHAT_SAME_DOMAIN_LINK');
			if (link)
			{
				localizeDescription += '<br><br><a href="'+link+'">'+this.getLocalize('BX_LIVECHAT_SAME_DOMAIN_MORE')+'</a>';
			}
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

	// public method
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

	// public method
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

/* endregion 04. Widget interaction and utils */

/* region 05. Rest batch handlers */
	handleConfigGet(response): Promise
	{
		return new Promise((resolve, reject) => {
			const configGet = response[RestMethod.widgetConfigGet];
			if (configGet && configGet.error())
			{
				return reject({
					code: configGet.error().ex.error,
					description: configGet.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(RestMethod.widgetConfigGet, configGet);

			resolve();
		});
	}

	handleUserGet(response): Promise
	{
		return new Promise((resolve, reject) => {
			const userGetResult = response[RestMethod.widgetUserGet];
			if (userGetResult.error())
			{
				return reject({
					code: userGetResult.error().ex.error,
					description: userGetResult.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

			resolve();
		});
	}

	handleChatGet(response): Promise
	{
		return new Promise((resolve, reject) => {
			const chatGetResult = response[ImRestMethodHandler.imChatGet];
			if (chatGetResult.error())
			{
				return reject({
					code: chatGetResult.error().ex.error,
					description: chatGetResult.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(ImRestMethodHandler.imChatGet, chatGetResult);

			resolve();
		});
	}

	handleDialogGet(response): Promise
	{
		return new Promise((resolve, reject) => {
			const dialogGetResult = response[RestMethod.widgetDialogGet];
			if (!dialogGetResult)
			{
				return resolve();
			}

			if (dialogGetResult.error())
			{
				return reject({
					code: dialogGetResult.error().ex.error,
					description: dialogGetResult.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);

			resolve();
		});
	}

	handleDialogMessagesGet(response): Promise
	{
		return new Promise((resolve, reject) => {
			const dialogMessagesGetResult = response[ImRestMethodHandler.imDialogMessagesGetInit];
			if (!dialogMessagesGetResult)
			{
				return resolve();
			}

			if (dialogMessagesGetResult.error())
			{
				return reject({
					code: dialogMessagesGetResult.error().ex.error,
					description: dialogMessagesGetResult.error().ex.error_description
				});
			}

			this.controller.getStore().dispatch('dialogues/saveDialog', {
				dialogId: this.controller.application.getDialogId(),
				chatId: this.controller.application.getChatId(),
			});

			this.controller.executeRestAnswer(ImRestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);

			resolve();
		});
	}

	handleUserRegister(response): Promise
	{
		return new Promise((resolve, reject) => {
			const userRegisterResult = response[RestMethod.widgetUserRegister];
			if (!userRegisterResult)
			{
				return resolve();
			}

			if (userRegisterResult.error())
			{
				return reject({
					code: userRegisterResult.error().ex.error,
					description: userRegisterResult.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);

			resolve();
		});
	}

	handleChatCreate(response): Promise
	{
		return new Promise((resolve, reject) => {
			const chatCreateResult = response[RestMethod.widgetChatCreate];
			if (!chatCreateResult)
			{
				return resolve();
			}

			if (chatCreateResult.error())
			{
				return reject({
					code: chatCreateResult.error().ex.error,
					description: chatCreateResult.error().ex.error_description
				});
			}

			this.controller.executeRestAnswer(RestMethod.widgetChatCreate, chatCreateResult);

			resolve();
		});
	}

	handlePullRequests(response): Promise
	{
		return new Promise((resolve) => {
			let timeShift = 0;

			const serverTimeResult = response[RestMethod.pullServerTime];
			if (serverTimeResult && !serverTimeResult.error())
			{
				timeShift = Math.floor((Date.now() - new Date(serverTimeResult.data()).getTime()) / 1000);
			}

			let config = null;
			const pullConfigResult = response[RestMethod.pullConfigGet];
			if (pullConfigResult && !pullConfigResult.error())
			{
				config = pullConfigResult.data();
				config.server.timeShift = timeShift;
			}

			this.startPullClient(config).then(() => {
				EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
			}).catch((error) => {
				this.setError(error.ex.error, error.ex.error_description);
			}).finally(resolve);
		});
	}
/* endregion 05. Rest batch handlers */
}