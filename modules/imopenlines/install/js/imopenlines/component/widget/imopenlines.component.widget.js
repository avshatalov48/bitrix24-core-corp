import "./imopenlines.component.widget.css";

/* region 00. Startup operations */
const GetObjectValues = function(source)
{
	const destination = [];
	for (let value in source)
	{
		if (source.hasOwnProperty(value))
		{
			destination.push(source[value]);
		}
	}
	return destination;
};
/* endregion 00. Startup operations */

/* region 01. Constants */

const VoteType = Object.freeze({
	none: 'none',
	like: 'like',
	dislike: 'dislike',
});

const DeviceOrientation = Object.freeze({
	horizontal: 'horizontal',
	portrait: 'portrait',
});

const DeviceType = Object.freeze({
	mobile: 'mobile',
	desktop: 'desktop',
});

const LanguageType = Object.freeze({
	russian: 'ru',
	ukraine: 'ua',
	world: 'en',
});

const FormType = Object.freeze({
	none: 'none',
	like: 'like',
	smile: 'smile',
	consent: 'consent',
	welcome: 'welcome',
	offline: 'offline',
	history: 'history',
});

const LocationType = Object.freeze({
	topLeft: 1,
	topMiddle: 2,
	topBottom: 3,
	bottomLeft: 6,
	bottomMiddle: 5,
	bottomRight: 4,
});

const LocationStyle = Object.freeze({
	1: 'top-left',
	2: 'top-center',
	3: 'top-right',
	6: 'bottom-left',
	5: 'bottom-center',
	4: 'bottom-right',
});

const SubscriptionType = Object.freeze({
	configLoaded: 'configLoaded',
	widgetOpen: 'widgetOpen',
	widgetClose: 'widgetClose',
	sessionStart: 'sessionStart',
	sessionOperatorChange: 'sessionOperatorChange',
	sessionFinish: 'sessionFinish',
	operatorMessage: 'operatorMessage',
	userForm: 'userForm',
	userMessage: 'userMessage',
	userVote: 'userVote',
	every: 'every',
});
const SubscriptionTypeCheck = GetObjectValues(SubscriptionType);

const WidgetStore = Object.freeze({
	dialogData: 'widget/dialogData',
	widgetData: 'widget/widgetData',
	userData: 'widget/userData',
});

const MessengerStore = Object.freeze({
	messages: BX.Messenger.Model.Messages.getName(),
	dialogues: BX.Messenger.Model.Dialogues.getName(),
	users: BX.Messenger.Model.Users.getName(),
	files: BX.Messenger.Model.Files.getName(),
});

const MessengerMessageStore = Object.freeze({
	initCollection: MessengerStore.messages+'/initCollection',
	add: MessengerStore.messages+'/add',
	set: MessengerStore.messages+'/set',
	setBefore: MessengerStore.messages+'/setBefore',
	update: MessengerStore.messages+'/update',
	delete: MessengerStore.messages+'/delete',
	actionStart: MessengerStore.messages+'/actionStart',
	actionFinish: MessengerStore.messages+'/actionFinish',
	actionError: MessengerStore.messages+'/actionError',
	readMessages: MessengerStore.messages+'/readMessages',
});

const MessengerMessageGetters = Object.freeze({
	getLastId: MessengerStore.messages+'/getLastId',
});

const MessengerUserStore = Object.freeze({
	set: MessengerStore.users+'/set',
	update: MessengerStore.users+'/update',
	delete: MessengerStore.users+'/delete',
});

const MessengerFileStore = Object.freeze({
	initCollection: MessengerStore.files+'/initCollection',
	set: MessengerStore.files+'/set',
	setBefore: MessengerStore.files+'/setBefore',
	update: MessengerStore.files+'/update',
	delete: MessengerStore.dialogues+'/delete',
});

const MessengerDialogStore = Object.freeze({
	initCollection: MessengerStore.dialogues+'/initCollection',
	set: MessengerStore.dialogues+'/set',
	update: MessengerStore.dialogues+'/update',
	updateWriting: MessengerStore.dialogues+'/updateWriting',
	decreaseCounter: MessengerStore.dialogues+'/decreaseCounter',
	increaseCounter: MessengerStore.dialogues+'/increaseCounter',
	delete: MessengerStore.dialogues+'/delete',
});


const RestMethod = Object.freeze({
	widgetUserRegister: 'imopenlines.widget.user.register',
	widgetConfigGet: 'imopenlines.widget.config.get',
	widgetDialogGet: 'imopenlines.widget.dialog.get',
	widgetUserGet: 'imopenlines.widget.user.get',
	widgetUserConsentApply: 'imopenlines.widget.user.consent.apply',
	widgetVoteSend: 'imopenlines.widget.vote.send',
	widgetFormSend: 'imopenlines.widget.form.send',

	pullServerTime: 'server.time',
	pullConfigGet: 'pull.config.get',

	imMessageAdd: 'im.message.add',
	imMessageUpdate: 'im.message.update', // TODO: method is not implemented
	imMessageDelete: 'im.message.delete', // TODO: method is  not implemented
	imMessageLike: 'im.message.like', // TODO: method is  not implemented
	imChatGet: 'im.chat.get',
	imChatSendTyping: 'im.chat.sendTyping',
	imDialogMessagesGet: 'im.dialog.messages.get',
	imDialogMessagesUnread: 'im.dialog.messages.unread',
	imDialogRead: 'im.dialog.read',

	diskFolderGet: 'im.disk.folder.get',
	diskFileUpload: 'disk.folder.uploadfile',
	diskFileCommit: 'im.disk.file.commit',
});
const RestMethodCheck = GetObjectValues(RestMethod);

const RestAuth = Object.freeze({
	guest: 'guest',
});

/* endregion 01. Constants */

/* region 02. Widget public interface */

class LiveChatWidget
{
	constructor(config)
	{
		this.developerInfo = 'Do not use private methods.';
		this.__privateMethods__ = new LiveChatWidgetPrivate(config);

		this.createLegacyMethods();
	}

	open(params)
	{
		return this.__privateMethods__.open(params);
	}

	close()
	{
		return this.__privateMethods__.close();
	}

	showNotification(params)
	{
		return this.__privateMethods__.showNotification(params);
	}

	getUserData()
	{
		return this.__privateMethods__.getUserData();
	}

	setUserRegisterData(params)
	{
		return this.__privateMethods__.setUserRegisterData(params);
	}

	setCustomData(params)
	{
		return this.__privateMethods__.setCustomData(params);
	}

	mutateTemplateComponent(id, params)
	{
		return this.__privateMethods__.mutateTemplateComponent(id, params);
	}

	addLocalize(phrases)
	{
		return this.__privateMethods__.addLocalize(phrases);
	}

	/**
	 *
	 * @param params {Object}
	 * @returns {Function|Boolean} - Unsubscribe callback function or False
	 */
	subscribe(params)
	{
		return this.__privateMethods__.subscribe(params);
	}

	start()
	{
		return this.__privateMethods__.start();
	}

	createLegacyMethods()
	{
		if (typeof window.BX.LiveChat === 'undefined')
		{
			let sourceHref = document.createElement('a');
			sourceHref.href = this.__privateMethods__.host;

			let sourceDomain = sourceHref.protocol+'//'+sourceHref.hostname+(sourceHref.port && sourceHref.port != '80' && sourceHref.port != '443'? ":"+sourceHref.port: "");

			window.BX.LiveChat = {
				openLiveChat: () => {
					this.open({openFromButton: true});
				},
				closeLiveChat: () => {
					this.close();
				},
				addEventListener: (el, eventName, handler) =>
				{
					if (eventName === 'message')
					{
						this.subscribe({
							type: SubscriptionType.userMessage,
							callback: function (event)
							{
								handler({origin: sourceDomain, data: JSON.stringify({action: 'sendMessage'}), event});
							}
						});
					}
					else
					{
						console.warn('Method BX.LiveChat.addEventListener is not supported, user new format for subscribe.')
					}
				},
				setCookie: () => {},
				getCookie: () => {},
				sourceDomain
			};
		}

		if (typeof window.BxLiveChatInit === 'function')
		{
			let config = window.BxLiveChatInit();

			if (config.user)
			{
				this.__privateMethods__.setUserRegisterData(config.user);
			}
			if (config.firstMessage)
			{
				this.__privateMethods__.setCustomData(config.firstMessage)
			}
		}

		if (window.BxLiveChatLoader instanceof Array)
		{
			window.BxLiveChatLoader.forEach(callback => callback());
		}

		return true;
	}
}

/* endregion 02. Widget public interface */

/* region 03. Widget private interface */
class LiveChatWidgetPrivate
{
/* region 03-01. Initialize and store data */

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
		this.pullClient = null;

		this.userRegisterData = {};
		this.customData = [];

		this.localize = this.pageMode && this.pageMode.useBitrixLocalize? BX.message: {};
		this.subscribers = {};
		this.dateFormat = null;

		this.messagesQueue = [];
		this.filesQueue = [];
		this.filesQueueIndex = 0;

		this.messageLastReadId = null;
		this.messageReadQueue = [];

		this.defaultMessageLimit = 20;
		this.requestMessageLimit = this.defaultMessageLimit;

		this.configRequestXhr = null;

		const widgetData = {};
		if (params.location && typeof LocationStyle[params.location] !== 'undefined')
		{
			widgetData.location = params.location;
		}
		if (
			BX.Messenger.Utils.types.isPlainObject(params.styles)
			&& (params.styles.backgroundColor || params.styles.iconColor)
		)
		{
			if (typeof widgetData.styles === 'undefined')
			{
				widgetData.styles = {};
			}
			if (params.styles.backgroundColor)
			{
				widgetData.styles.backgroundColor = params.styles.backgroundColor;
			}
			if (params.styles.iconColor)
			{
				widgetData.styles.iconColor = params.styles.iconColor;
			}
		}

		/* store data */
		this.store = new BX.Vuex.store({
			name: 'Bitrix LiveChat Widget ('+this.code+' / '+this.host+')',
			modules: {
				widget: this.getWidgetStore({widgetData}),
				[MessengerStore.messages]: BX.Messenger.Model.Messages.getInstance().getStore(),
				[MessengerStore.dialogues]: BX.Messenger.Model.Dialogues.getInstance().getStore({host: this.host}),
				[MessengerStore.users]: BX.Messenger.Model.Users.getInstance().getStore({host: this.host}),
				[MessengerStore.files]: BX.Messenger.Model.Files.getInstance().getStore({host: this.host}),
			}
		});

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
			this.store.commit(WidgetStore.widgetData, {deviceOrientation: BX.Messenger.Utils.device.getOrientation()});

			if (
				this.store.state.widget.widgetData.showed
				&& this.store.state.widget.widgetData.deviceType == DeviceType.mobile
				&& this.store.state.widget.widgetData.deviceOrientation == DeviceOrientation.horizontal
			)
			{
				document.activeElement.blur();
			}
		});

		let serverVariables = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		this.initRestClient();

		window.dispatchEvent(new CustomEvent('onBitrixLiveChat', {detail: {
			widget: this,
			widgetCode: this.code,
			widgetHost: this.host,
		}}));

		if (this.pageMode)
		{
			this.open();
		}
	}

	getWidgetStore(params)
	{
		/* WIDGET DATA */

		let widgetDataDefault = {
			siteId: this.getSiteId(),
			host: this.getHost(),
			configId: 0,
			configName: '',
			vote: {
				enable: false,
				messageText: this.getLocalize('BX_LIVECHAT_VOTE_TITLE'),
				messageLike: this.getLocalize('BX_LIVECHAT_VOTE_PLUS_TITLE'),
				messageDislike: this.getLocalize('BX_LIVECHAT_VOTE_MINUS_TITLE')
			},
			pageMode: false,
			language: this.language,
			copyright: this.copyright,
			copyrightUrl: this.copyrightUrl,
			online: false,
			operators: [],
			connectors: [],
			disk: {
				enabled: false,
				maxFileSize: 5242880,
			},
			styles: {
				backgroundColor: '#17a3ea',
				iconColor: '#ffffff'
			},
			showForm: FormType.none,
			uploadFile: false,
			deviceType: DeviceType.desktop,
			deviceOrientation: DeviceOrientation.portrait,
			location: LocationType.bottomRight,
			showed: false,
			reopen: false,
			dragged: false,
			textareaHeight: 0,
			showConsent: false,
			consentUrl: '',
			dialogStart: false,
			error: {
				active: false,
				code: '',
				description: ''
			}
		};

		let widgetData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.widgetData, widgetDataDefault);

		widgetData.deviceType = BX.Messenger.Utils.device.isMobile()? DeviceType.mobile: DeviceType.desktop;
		widgetData.deviceOrientation = BX.Messenger.Utils.device.getOrientation();
		widgetData.language = this.language;
		widgetData.copyright = this.copyright;
		widgetData.copyrightUrl = this.copyrightUrl;
		widgetData.pageMode = this.pageMode !== false;
		widgetData.dragged = false;
		widgetData.showConsent = false;
		widgetData.showForm = FormType.none;
		widgetData.uploadFile = false;
		widgetData.error = {
			active: false,
			code: '',
			description: ''
		};
		widgetData.showed = false;

		if (BX.Messenger.Utils.types.isPlainObject(params.widgetData))
		{
			if (params.widgetData.location && typeof LocationStyle[params.widgetData.location] !== 'undefined')
			{
				widgetData.location = params.widgetData.location;
			}
			if (
				BX.Messenger.Utils.types.isPlainObject(params.widgetData.styles)
				&& (params.widgetData.styles.backgroundColor || params.widgetData.styles.iconColor)
			)
			{
				if (params.widgetData.styles.backgroundColor)
				{
					widgetData.styles.backgroundColor = params.widgetData.styles.backgroundColor;
				}
				if (params.widgetData.styles.iconColor)
				{
					widgetData.styles.iconColor = params.widgetData.styles.iconColor;
				}
			}
		}

		for (let param in widgetDataDefault)
		{
			if (!widgetDataDefault.hasOwnProperty(param))
				continue;

			if (typeof widgetData[param] == 'undefined')
			{
				widgetData[param] = widgetDataDefault[param];
			}
		}

		/* DIALOG DATA */
		let dialogDataDefault = {
			dialogId: 'chat0',
			chatId: 0,
			diskFolderId: 0,
			sessionId: 0,
			sessionClose: true,
			userVote: VoteType.none,
			userConsent: false,
			messageLimit: 0,
			operator: {
				name: '',
				firstName: '',
				lastName: '',
				workPosition: '',
				avatar: '',
				online: false,
			}
		};

		let dialogData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.dialogData, dialogDataDefault);

		for (let param in dialogDataDefault)
		{
			if (!dialogDataDefault.hasOwnProperty(param))
				continue;

			if (typeof dialogData[param] == 'undefined')
			{
				dialogData[param] = dialogDataDefault[param];
			}
		}

		dialogData.messageLimit = this.defaultMessageLimit;

		/* USER DATA */
		let userDataDefault = {
			id: -1,
			hash: '',
			name: '',
			firstName: '',
			lastName: '',
			avatar: '',
			email: '',
			phone: '',
			www: '',
			gender: 'M',
			position: '',
		};

		let userData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.userData, userDataDefault);
		if (!userData.hash)
		{
			let userHash = this.getUserHashCookie();
			if (userHash)
			{
				userData.hash = userHash;
			}
		}

		for (let param in userDataDefault)
		{
			if (!userDataDefault.hasOwnProperty(param))
				continue;

			if (typeof userData[param] == 'undefined')
			{
				userData[param] = userDataDefault[param];
			}
		}

		/* VUEX INIT DATA */

		return {
			namespaced: true,
			state: {
				widgetData,
				dialogData,
				userData
			},
			mutations: {
				widgetData: (state, params) =>
				{
					if (typeof params.configId === 'number')
					{
						state.widgetData.configId = params.configId;
					}
					if (typeof params.configName === 'string')
					{
						state.widgetData.configName = params.configName;
					}
					if (typeof params.language === 'string')
					{
						state.widgetData.language = params.language;
					}
					if (typeof params.online === 'boolean')
					{
						state.widgetData.online = params.online;
					}
					if (BX.Messenger.Utils.types.isPlainObject(params.vote))
					{
						if (typeof params.vote.enable === 'boolean')
						{
							state.widgetData.vote.enable = params.vote.enable;
						}
						if (typeof params.vote.messageText === 'string')
						{
							state.widgetData.vote.messageText = params.vote.messageText;
						}
						if (typeof params.vote.messageLike === 'string')
						{
							state.widgetData.vote.messageLike = params.vote.messageLike;
						}
						if (typeof params.vote.messageDislike === 'string')
						{
							state.widgetData.vote.messageDislike = params.vote.messageDislike;
						}
					}
					if (typeof params.dragged === 'boolean')
					{
						state.widgetData.dragged = params.dragged;
					}
					if (typeof params.textareaHeight === 'number')
					{
						state.widgetData.textareaHeight = params.textareaHeight;
					}
					if (typeof params.showConsent === 'boolean')
					{
						state.widgetData.showConsent = params.showConsent;
					}
					if (typeof params.consentUrl === 'string')
					{
						state.widgetData.consentUrl = params.consentUrl;
					}
					if (typeof params.showed === 'boolean')
					{
						state.widgetData.showed = params.showed;
						state.widgetData.reopen = params.showed;
					}
					if (typeof params.copyright === 'boolean')
					{
						state.widgetData.copyright = params.copyright;
					}
					if (typeof params.dialogStart === 'boolean')
					{
						state.widgetData.dialogStart = params.dialogStart;
					}
					if (BX.Messenger.Utils.types.isPlainObject(params.disk))
					{
						if (typeof params.disk.enabled === 'boolean')
						{
							state.widgetData.disk.enabled = params.disk.enabled;
						}
						if (typeof params.disk.maxFileSize === 'number')
						{
							state.widgetData.disk.maxFileSize = params.disk.maxFileSize;
						}
					}
					if (BX.Messenger.Utils.types.isPlainObject(params.error) && typeof params.error.active === 'boolean')
					{
						state.widgetData.error = {
							active: params.error.active,
							code: params.error.code || '',
							description: params.error.description || '',
						};
					}
					if (params.operators instanceof Array)
					{
						state.widgetData.operators = params.operators;
					}
					if (params.connectors instanceof Array)
					{
						state.widgetData.connectors = params.connectors;
					}
					if (typeof params.uploadFilePlus !== 'undefined')
					{
						state.widgetData.uploadFile = state.widgetData.uploadFile+1;
					}
					if (typeof params.uploadFileMinus !== 'undefined')
					{
						state.widgetData.uploadFile = state.widgetData.uploadFile-1;
					}
					if (typeof params.showForm === 'string' && typeof FormType[params.showForm] !== 'undefined')
					{
						state.widgetData.showForm = params.showForm;
					}
					if (typeof params.deviceType === 'string' && typeof DeviceType[params.deviceType] !== 'undefined')
					{
						state.widgetData.deviceType = params.deviceType;
					}
					if (typeof params.deviceOrientation === 'string' && typeof DeviceOrientation[params.deviceOrientation] !== 'undefined')
					{
						state.widgetData.deviceOrientation = params.deviceOrientation;
					}
					if (typeof params.location === 'number' && typeof LocationStyle[params.location] !== 'undefined')
					{
						state.widgetData.location = params.location;
					}

					BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.widgetData, state.widgetData);
				},
				dialogData: (state, params) =>
				{
					if (typeof params.chatId === 'number')
					{
						state.dialogData.chatId = params.chatId;
						state.dialogData.dialogId = params.chatId? 'chat'+params.chatId: 0;
					}
					if (typeof params.diskFolderId === 'number')
					{
						state.dialogData.diskFolderId = params.diskFolderId;
					}
					if (typeof params.sessionId === 'number')
					{
						state.dialogData.sessionId = params.sessionId;
					}
					if (typeof params.messageLimit === 'number')
					{
						state.dialogData.messageLimit = params.messageLimit;
					}
					if (typeof params.sessionClose === 'boolean')
					{
						state.dialogData.sessionClose = params.sessionClose;
					}
					if (typeof params.userConsent === 'boolean')
					{
						state.dialogData.userConsent = params.userConsent;
					}
					if (typeof params.userVote === 'string' && typeof params.userVote !== 'undefined')
					{
						state.dialogData.userVote = params.userVote;
					}
					if (BX.Messenger.Utils.types.isPlainObject(params.operator))
					{
						if (typeof params.operator.name === 'string' || typeof params.operator.name === 'number')
						{
							state.dialogData.operator.name = params.operator.name.toString();
						}
						if (typeof params.operator.lastName === 'string' || typeof params.operator.lastName === 'number')
						{
							state.dialogData.operator.lastName = params.operator.lastName.toString();
						}
						if (typeof params.operator.firstName === 'string' || typeof params.operator.firstName === 'number')
						{
							state.dialogData.operator.firstName = params.operator.firstName.toString();
						}
						if (typeof params.operator.workPosition === 'string' || typeof params.operator.workPosition === 'number')
						{
							state.dialogData.operator.workPosition = params.operator.workPosition.toString();
						}
						if (typeof params.operator.avatar === 'string')
						{
							if (!params.operator.avatar || params.operator.avatar.startsWith('http'))
							{
								state.dialogData.operator.avatar = params.operator.avatar;
							}
							else
							{
								state.dialogData.operator.avatar = state.widgetData.host+params.operator.avatar;
							}
						}
						if (typeof params.operator.online === 'boolean')
						{
							state.dialogData.operator.online = params.operator.online;
						}
					}
					BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.dialogData, state.dialogData);
				},
				userData: (state, params) =>
				{
					if (typeof params.id === 'number')
					{
						state.userData.id = params.id;
					}
					if (typeof params.hash === 'string' && params.hash !== state.userData.hash)
					{
						state.userData.hash = params.hash;
						Cookie.set(null, 'LIVECHAT_HASH', params.hash, {expires: 365*86400, path: '/'});
					}
					if (typeof params.name === 'string' || typeof params.name === 'number')
					{
						state.userData.name = params.name.toString();
					}
					if (typeof params.firstName === 'string' || typeof params.firstName === 'number')
					{
						state.userData.firstName = params.firstName.toString();
					}
					if (typeof params.lastName === 'string' || typeof params.lastName === 'number')
					{
						state.userData.lastName = params.lastName.toString();
					}
					if (typeof params.avatar === 'string')
					{
						state.userData.avatar = params.avatar;
					}
					if (typeof params.email === 'string')
					{
						state.userData.email = params.email;
					}
					if (typeof params.phone === 'string' || typeof params.phone === 'number')
					{
						state.userData.phone = params.phone.toString();
					}
					if (typeof params.www === 'string')
					{
						state.userData.www = params.www;
					}
					if (typeof params.gender === 'string')
					{
						state.userData.gender = params.gender;
					}
					if (typeof params.position === 'string')
					{
						state.userData.position = params.position;
					}
					BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.userData, state.userData);
				},
			}
		};
	}

	initRestClient()
	{
		this.restClient = new LiveChatRestClient({endpoint: this.host+'/rest'});

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
		if (!this.isUserRegistered() && this.userRegisterData.hash)
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

				this.storeDataFromRest(RestMethod.widgetConfigGet, result.data());

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

		this.timer = new BX.Messenger.Timer();
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
			query[RestMethod.imChatGet] = [RestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetDialogGet+'][dialogId]'}];
			query[RestMethod.imDialogMessagesGet] = [RestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.requestMessageLimit, convert_text: 'Y'}];
		}
		else
		{
			query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', ...this.getUserRegisterFields()}];
			query[RestMethod.imChatGet] = [RestMethod.imChatGet, {dialog_id: '$result['+RestMethod.widgetUserRegister+'][dialogId]'}];

			if (this.userRegisterData.hash)
			{
				query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {config_id: '$result['+RestMethod.widgetConfigGet+'][configId]', trace_data: this.getCrmTraceData(), custom_data: this.getCustomData()}];
				query[RestMethod.imDialogMessagesGet] = [RestMethod.imDialogMessagesGet, {chat_id: '$result['+RestMethod.widgetDialogGet+'][chatId]', limit: this.requestMessageLimit, convert_text: 'Y'}];
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
			this.storeDataFromRest(RestMethod.widgetConfigGet, configGet.data());

			let userGetResult = response[RestMethod.widgetUserGet];
			if (userGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);
				return false;
			}
			this.storeDataFromRest(RestMethod.widgetUserGet, userGetResult.data());

			let chatGetResult = response[RestMethod.imChatGet];
			if (chatGetResult.error())
			{
				this.requestDataSend = false;
				this.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);
				return false;
			}
			this.storeDataFromRest(RestMethod.imChatGet, chatGetResult.data());

			let dialogGetResult = response[RestMethod.widgetDialogGet];
			if (dialogGetResult)
			{
				if (dialogGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);
					return false;
				}
				this.storeDataFromRest(RestMethod.widgetDialogGet, dialogGetResult.data());
			}

			let dialogMessagesGetResult = response[RestMethod.imDialogMessagesGet];
			if (dialogMessagesGetResult)
			{
				if (dialogMessagesGetResult.error())
				{
					this.requestDataSend = false;
					this.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);
					return false;
				}
				this.storeDataFromRest(RestMethod.imDialogMessagesGet, dialogMessagesGetResult.data());
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
				this.storeDataFromRest(RestMethod.widgetUserRegister, userRegisterResult.data());
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
		}, false, false, BX.Messenger.Utils.getLogTrackingParams({name: 'widget.init.config', dialog: this.getDialogData()}));
	}

	storeDataFromRest(type, result)
	{
		if (!RestMethodCheck.includes(type))
		{
			console.warn(`%cLiveChatWidget.storeDataFromRest: config is not set, because you are trying to set as unknown type (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
			return false;
		}

		if (type == RestMethod.widgetConfigGet)
		{
			this.store.commit(WidgetStore.widgetData, {
				configId: result.configId,
				configName: result.configName,
				vote: result.vote,
				operators: result.operators || [],
				online: result.online,
				consentUrl: result.consentUrl,
				connectors: result.connectors || [],
				disk: result.disk,
			});

			this.addLocalize(result.serverVariables);
			BX.Messenger.LocalStorage.set(this.getSiteId(), 0, 'serverVariables', result.serverVariables || {});
		}
		else if (type == RestMethod.widgetUserRegister)
		{
			this.restClient.setAuthId(result.hash);

			let previousData = [];
			if (typeof this.store.state[MessengerStore.messages].collection[this.getChatId()] !== 'undefined')
			{
				previousData = this.store.state[MessengerStore.messages].collection[this.getChatId()];
			}
			this.store.commit(MessengerMessageStore.initCollection, {chatId: result.chatId, messages: previousData});

			this.store.commit(MessengerDialogStore.initCollection, {dialogId: result.dialogId, fields: {
				entityType: 'LIVECHAT',
				type: 'livechat'
			}});

			this.store.commit(WidgetStore.dialogData, {
				chatId: result.chatId
			});
		}
		else if (type == RestMethod.imChatGet)
		{
			this.store.dispatch(MessengerDialogStore.set, result);
		}
		else if (type == RestMethod.widgetUserGet)
		{
			this.store.commit(WidgetStore.userData, {
				id: result.id,
				hash: result.hash,
				name: result.name,
				firstName: result.firstName,
				lastName: result.lastName,
				phone: result.phone,
				avatar: result.avatar,
				email: result.email,
				www: result.www,
				gender: result.gender,
				position: result.position,
			});
		}
		else if (type == RestMethod.widgetDialogGet)
		{
			this.store.commit(MessengerMessageStore.initCollection, {chatId: result.chatId});

			this.store.commit(WidgetStore.dialogData, {
				chatId: result.chatId,
				diskFolderId: result.diskFolderId,
				sessionId: result.sessionId,
				sessionClose: result.sessionClose,
				userVote: result.userVote,
				userConsent: result.userConsent,
				operator: result.operator
			});
		}
		else if (type == RestMethod.diskFolderGet)
		{
			this.store.commit(WidgetStore.dialogData, {
				diskFolderGet: result.ID
			});
		}
		else if (type == RestMethod.imDialogMessagesGet)
		{
			this.store.dispatch(MessengerMessageStore.setBefore, result.messages);
			this.store.dispatch(MessengerUserStore.set, result.users);
			this.store.dispatch(MessengerFileStore.setBefore, this.prepareFileData(result.files));

			if (result.messages && result.messages.length > 0 && !this.isDialogStart())
			{
				this.store.commit(WidgetStore.widgetData, {dialogStart:true});
			}
		}
		else if (type == RestMethod.imDialogMessagesUnread)
		{
			this.store.dispatch(MessengerMessageStore.set, result.messages);
			this.store.dispatch(MessengerUserStore.set, result.users);
			this.store.dispatch(MessengerFileStore.set, this.prepareFileData(result.files));
		}

		return true;
	}

	prepareFileData(files)
	{
		if (Cookie.get(null, 'BITRIX_LIVECHAT_AUTH'))
		{
			return files;
		}

		return files.map(file =>
		{
			let hash = md5(this.getUserId()+'|'+file.id+'|'+this.getUserHash());
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
		if (BX.Messenger.Utils.platform.isIos())
		{
			let version = BX.Messenger.Utils.platform.getIosVersion();
			if (version && version <= 10)
			{
				return false;
			}
		}

		return true;
	}

/* endregion 03-01. Initialize and store data */

/* region 03-02. Push & Pull */

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

		this.pullClient = new BX.PullClient({
			serverEnabled: true,
			userId: this.getUserId(),
			siteId: this.getSiteId(),
			restClient: this.restClient,
			configTimestamp: config? config.server.config_timestamp: 0,
			skipCheckRevision: true,
		});
		this.pullClient.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'im',
			callback: this.eventMessengerInteraction.bind(this)
		});
		this.pullClient.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'imopenlines',
			callback: this.eventLinesInteraction.bind(this)
		});
		this.pullClient.subscribe({
			type: BX.PullClient.SubscriptionType.Status,
			callback: this.eventStatusInteraction.bind(this)
		});

		this.pullConnectedFirstTime = this.pullClient.subscribe({
			type: BX.PullClient.SubscriptionType.Status,
			callback: (result) => {
				if (result.status == BX.PullClient.PullStatus.Online)
				{
					promise.resolve(true);
					this.pullConnectedFirstTime();
				}
			}
		});

		if (this.template)
		{
			this.template.$bitrixPullClient = this.pullClient;
			this.template.$root.$emit('onBitrixPullClientInited');
		}

		this.pullClient.start(config).catch(function(){
			promise.reject({
				ex: { error: 'PULL_CONNECTION_ERROR', error_description: 'Pull is not connected.'}
			});
		});

		return promise;
	}

	recoverPullConnection()
	{
		// this.pullClient.session.mid = 0; // TODO specially for disable pull history, remove after recode im
		this.pullClient.restart(BX.PullClient.CloseReasons.MANUAL, 'Restart after click by connection status button.');
	}

	stopPullClient()
	{
		if (this.pullClient)
		{
			this.pullClient.stop(BX.PullClient.CloseReasons.MANUAL, 'Closed manually');
		}
	}

	eventMessengerInteraction(data)
	{
		BX.Messenger.Logger.info('eventMessengerInteraction', data);

		if (data.command == "messageChat")
		{
			if (data.params.chat && data.params.chat[data.params.chatId])
			{
				this.store.dispatch(MessengerDialogStore.update, {
					dialogId: 'chat'+data.params.chatId,
					fields: data.params.chat[data.params.chatId]
				});
			}

			if (data.params.users)
			{
				this.store.dispatch(MessengerUserStore.set, BX.Messenger.Model.Users.convertToArray(data.params.users));
			}

			if (data.params.files)
			{
				let dataParamsFiles = BX.Messenger.Model.Files.convertToArray(data.params.files);
				this.store.dispatch(MessengerFileStore.set, this.prepareFileData(dataParamsFiles));
			}

			let collection = this.store.state[MessengerStore.messages].collection[this.getChatId()];
			if (!collection)
			{
				collection = [];
			}

			let update = false;
			if (data.params.message.tempId && collection.length > 0)
			{
				for (let index = collection.length-1; index >= 0; index--)
				{
					if (collection[index].id == data.params.message.tempId)
					{
						update = true;
						break;
					}
				}
			}
			if (update)
			{
				this.store.dispatch(MessengerMessageStore.update, {
					id: data.params.message.tempId,
					chatId: data.params.message.chatId,
					fields: data.params.message
				});
			}
			else if (this.isUnreadMessagesLoaded())
			{
				let unreadCountInCollection = 0;
				if (collection.length > 0)
				{
					collection.forEach(element => element.unread? unreadCountInCollection++: 0);
				}

				if (unreadCountInCollection > 0)
				{
					this.store.commit(WidgetStore.dialogData, {
						messageLimit: this.requestMessageLimit + unreadCountInCollection
					});
				}
				else if (this.getMessageLimit() != this.requestMessageLimit)
				{
					this.store.commit(WidgetStore.dialogData, {
						messageLimit: this.requestMessageLimit
					});
				}

				this.store.dispatch(MessengerMessageStore.set, {...data.params.message, unread: true});
			}

			this.startWriting({
				dialogId: 'chat'+data.params.message.chatId,
				userId: data.params.message.senderId,
				action: false
			});

			if (data.params.message.senderId == this.getUserId())
			{
				this.store.dispatch(MessengerMessageStore.readMessages, {
					chatId: data.params.message.chatId
				}).then(result => {
					this.store.dispatch(MessengerDialogStore.update, {
						dialogId: 'chat'+data.params.message.chatId,
						fields: {
							counter: 0,
						}
					});
				});
			}
			else
			{
				this.store.dispatch(MessengerDialogStore.increaseCounter, {
					dialogId: 'chat'+data.params.message.chatId,
					count: 1,
				});

				this.sendEvent({
					type: SubscriptionType.operatorMessage,
					data: data.params
				});

				if (!this.store.state.widget.widgetData.showed && !this.onceShowed)
				{
					this.onceShowed = true;
					this.open();
				}
			}
		}
		else if (data.command == "messageUpdate" || data.command == "messageDelete")
		{
			this.store.dispatch(MessengerMessageStore.update, {
				id: data.params.id,
				chatId: data.params.chatId,
				fields: {
					text: data.command == "messageUpdate"? data.params.text: '',
					textOriginal: data.command == "messageUpdate"? data.params.textOriginal: '',
					params: data.params.params,
					blink: true
				}
			});

			this.startWriting({
				dialogId: data.params.dialogId,
				userId: data.params.senderId,
				action: false
			});
		}
		else if (data.command == "messageDeleteComplete")
		{
			this.store.dispatch(MessengerMessageStore.delete, {
				id: data.params.id,
				chatId: data.params.chatId,
			});

			this.startWriting({
				dialogId: data.params.dialogId,
				userId: data.params.senderId,
				action: false
			});
		}
		else if (data.command == "messageParamsUpdate")
		{
			this.store.dispatch(MessengerMessageStore.update, {
				id: data.params.id,
				chatId: data.params.chatId,
				fields: {params: data.params.params}
			});
		}
		else if (data.command == "startWriting")
		{
			this.startWriting(data.params);
		}
		else if (data.command == "readMessageChat")
		{
			this.store.dispatch(MessengerMessageStore.readMessages, {
				chatId: data.params.chatId,
				readId: data.params.lastId
			}).then(result => {
				this.store.dispatch(MessengerDialogStore.update, {
					dialogId: 'chat'+data.params.chatId,
					fields: {
						counter: data.params.counter,
					}
				});
			});
		}
	}

	eventLinesInteraction(data)
	{
		BX.Messenger.Logger.info('eventLinesInteraction', data);

		if (data.command == "sessionStart")
		{
			this.store.commit(WidgetStore.dialogData, {
				sessionId: data.params.sessionId,
				sessionClose: false,
				userVote: VoteType.none,
			});

			this.sendEvent({
				type: SubscriptionType.sessionStart,
				data: {
					sessionId: data.params.sessionId
				}
			});
		}
		else if (data.command == "sessionOperatorChange")
		{
			this.store.commit(WidgetStore.dialogData, {
				operator: data.params.operator
			});

			this.sendEvent({
				type: SubscriptionType.sessionOperatorChange,
				data: {
					operator: data.params.operator
				}
			});
		}
		else if (data.command == "sessionFinish")
		{
			this.store.commit(WidgetStore.dialogData, {
				sessionId: data.params.sessionId,
				sessionClose: true,
			});

			this.sendEvent({
				type: SubscriptionType.sessionFinish,
				data: {
					sessionId: data.params.sessionId
				}
			});

			if (!data.params.spam)
			{
				this.store.commit(WidgetStore.dialogData, {
					operator: {
						name: '',
						firstName: '',
						lastName: '',
						workPosition: '',
						avatar: '',
						online: false,
					}
				});
			}
		}
	}

	eventStatusInteraction(data)
	{
		if (data.status === BX.PullClient.PullStatus.Online)
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
		else if (data.status === BX.PullClient.PullStatus.Offline)
		{
			this.pullRequestMessage = true;
			this.offline = true;
		}
	}

/* endregion 03-02. Push & Pull */

/* region 03-03. Template engine */

	attachTemplate()
	{
		if (this.template)
		{
			this.store.commit(WidgetStore.widgetData, {showed: true});
			return true;
		}

		this.rootNode.innerHTML = '';
		this.rootNode.appendChild(document.createElement('div'));

		const widgetContext = this;
		const restClient = this.restClient;
		const pullClient = this.pullClient;

		this.template = BX.Vue.create({
			el: this.rootNode.firstChild,
			store: this.store,
			template: '<bx-livechat/>',
			beforeCreate()
			{
				this.$bitrixController = widgetContext;
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

				this.$bitrixController.template = null;
				this.$bitrixController.templateAttached = false;
				this.$bitrixController.rootNode.innerHTML = '';

				this.$bitrixController = null;
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
		return BX.Vue.mutateComponent(id, params);
	}

/* endregion 03-03. Template engine */

/* region 03-04. Rest methods */

	addMessage(text = '')
	{
		if (!text)
		{
			return false;
		}

		BX.Messenger.Logger.warn('addMessage', text);

		if (!this.isUnreadMessagesLoaded())
		{
			this.sendMessage({id: 0, text});
			this.processSendMessages();

			return true;
		}

		this.store.dispatch(MessengerMessageStore.add, {
			chatId: this.getChatId(),
			authorId: this.getUserId(),
			text: text,
		}).then(messageId => {

			if (!this.isDialogStart())
			{
				this.store.commit(WidgetStore.widgetData, {dialogStart:true});
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

		BX.Messenger.Logger.warn('addFile', fileInput.files[0].name, fileInput.files[0].size);

		if (!this.isDialogStart())
		{
			this.store.commit(WidgetStore.widgetData, {dialogStart:true});
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

	writesMessage()
	{
		if (
			!this.getChatId()
			|| this.timer.has('writes')
		)
		{
			return;
		}

		this.timer.start('writes', null, 28);

		this.timer.start('writesSend', null, 5, (id) => {
			this.restClient.callMethod(RestMethod.imChatSendTyping, {
				'CHAT_ID': this.getChatId()
			}).catch(() => {
				this.timer.stop('writes', this.getChatId());
			});
		});
	}

	stopWritesMessage()
	{
		this.timer.stop('writes');
		this.timer.stop('writesSend');
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
		this.stopWritesMessage();
		this.restClient.callMethod(RestMethod.imMessageAdd, {
			'TEMP_ID': message.id,
			'CHAT_ID': this.getChatId(),
			'MESSAGE': message.text
		}, null, null, BX.Messenger.Utils.getLogTrackingParams({
			name: RestMethod.imMessageAdd,
			data: {timMessageType: 'text'},
			dialog: this.getDialogData()
		})).then(response => {
			let messageId = response.data();
			if (typeof messageId === "number")
			{
				this.store.dispatch(MessengerMessageStore.update, {
					id:  message.id,
					chatId: this.getChatId(),
					fields: {
						id: messageId,
						sending: false,
						error: false,
					}
				});

				this.store.dispatch(MessengerMessageStore.actionFinish, {
					id: messageId,
					chatId: this.getChatId()
				});
			}
			else
			{
				this.store.dispatch(MessengerMessageStore.actionError, {
					id: message.id,
					chatId: this.getChatId()
				});
			}
			this.messagesQueue = this.messagesQueue.filter(el => el.id != message.id);

			this.sendEvent({
				type: SubscriptionType.userMessage,
				data: {
					id: messageId,
					text: message.text
				}
			});
		}).catch(error => {
			this.store.dispatch(MessengerMessageStore.actionError, {
				id: message.id,
				chatId: this.getChatId()
			});
			this.messagesQueue = this.messagesQueue.filter(el => el.id != message.id);
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
			query[RestMethod.diskFileUpload] = [RestMethod.diskFileUpload, {
				id : diskFolderId,
				data : {NAME : fileName},
				fileContent: file.fileInput,
				generateUniqueName: true
			}];
		}
		else
		{
			query[RestMethod.diskFolderGet] = [RestMethod.diskFolderGet, {chat_id: this.getChatId()}];
			query[RestMethod.diskFileUpload] = [RestMethod.diskFileUpload, {
				id : '$result[' + RestMethod.diskFolderGet + '][ID]',
				data : {
					NAME : fileName
				},
				fileContent: file.fileInput,
				generateUniqueName: true
			}];
		}
		query[RestMethod.diskFileCommit] = [RestMethod.diskFileCommit, {
			chat_id : this.getChatId(),
			upload_id : '$result[' + RestMethod.diskFileUpload + '][ID]',
		}];

		this.store.commit(WidgetStore.widgetData, {uploadFilePlus: true}); // TODO remove this after create new load workflow

		this.restClient.callBatch(query, (response) =>
		{
			this.store.commit(WidgetStore.widgetData, {uploadFileMinus: true}); // TODO  remove this after create new load workflow

			if (!response)
			{
				this.requestDataSend = false;
				this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
				return false;
			}

			if (!diskFolderId)
			{
				let diskFolderGet = response[RestMethod.diskFolderGet];
				if (diskFolderGet && diskFolderGet.error())
				{
					console.warn(diskFolderGet.error().ex.error, diskFolderGet.error().ex.error_description);
					return false;
				}
				this.storeDataFromRest(RestMethod.diskFolderGet, diskFolderGet.data());
			}

			let diskFileUpload = response[RestMethod.diskFileUpload];
			if (diskFileUpload && diskFileUpload.error())
			{
				console.warn(diskFileUpload.error().ex.error, diskFileUpload.error().ex.error_description);
				return false;
			}
			else
			{
				BX.Messenger.Logger.log('upload success', diskFileUpload.data())
			}

			let diskFileCommit = response[RestMethod.diskFileCommit];
			if (diskFileCommit && diskFileCommit.error())
			{
				console.warn(diskFileCommit.error().ex.error, diskFileCommit.error().ex.error_description);
				return false;
			}
			else
			{
				BX.Messenger.Logger.log('commit success', diskFileCommit.data())
			}
		}, false, false, BX.Messenger.Utils.getLogTrackingParams({
			name: RestMethod.diskFileCommit,
			data: {timMessageType: fileType},
			dialog: this.getDialogData()
		}));

		this.filesQueue = this.filesQueue.filter(el => el.id != file.id);
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
			this.store.commit(WidgetStore.dialogData, {userVote: VoteType.none});
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
		BX.Messenger.Logger.info('LiveChatWidgetPrivate.sendForm:', type, fields);

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
			this.storeDataFromRest(RestMethod.widgetUserGet, userGetResult.data());

			this.sendEvent({
				type: SubscriptionType.userForm,
				data: {
					form: type,
					fields: fields
				}
			});

		}, false, false, BX.Messenger.Utils.getLogTrackingParams({name: RestMethod.widgetUserGet, dialog: this.getDialogData()}));

	}

	sendConsentDecision(result)
	{
		result = result === true;

		this.store.commit(WidgetStore.dialogData, {userConsent: result});

		if (result && this.isUserRegistered())
		{
			this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
				config_id: this.getConfigId(),
				consent_url: location.href
			});
		}
	}

	getDialogHistory(lastId, limit = this.requestMessageLimit)
	{
		this.restClient.callMethod(RestMethod.imDialogMessagesGet, {
			'CHAT_ID': this.getChatId(),
			'LAST_ID': lastId,
			'LIMIT': limit,
			'CONVERT_TEXT': 'Y'
		}).then(result => {
			let requestResult = result.data();
			this.storeDataFromRest(RestMethod.imDialogMessagesGet, requestResult);
			this.template.$emit('onDialogRequestHistoryResult', {count: requestResult.messages.length});
		}).catch(result => {
			this.template.$emit('onDialogRequestHistoryResult', {error: result.error().ex});
		});
	}

	getDialogUnread(lastId, limit = this.requestMessageLimit)
	{
		const promise = new BX.Promise();

		if (!lastId)
		{
			lastId = this.store.getters[MessengerMessageGetters.getLastId](this.getChatId());
		}

		if (!lastId)
		{
			this.template.$emit('onDialogRequestUnreadResult', {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});
			promise.reject();
			return promise;
		}

		let query = {
			[RestMethod.imChatGet]: [RestMethod.imChatGet, {
				dialog_id: this.getDialogId()
			}],
			[RestMethod.imDialogMessagesUnread]: [RestMethod.imDialogMessagesGet, {
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

			let chatGetResult = response[RestMethod.imChatGet];
			if (!chatGetResult.error())
			{
				this.storeDataFromRest(RestMethod.imChatGet, chatGetResult.data());
			}

			let dialogMessageUnread = response[RestMethod.imDialogMessagesUnread];
			if (dialogMessageUnread.error())
			{
				this.template.$emit('onDialogRequestUnreadResult', {error: dialogMessageUnread.error().ex});
			}
			else
			{
				let dialogMessageUnreadResult = dialogMessageUnread.data();
				this.storeDataFromRest(RestMethod.imDialogMessagesUnread, dialogMessageUnreadResult);
				this.template.$emit('onDialogRequestUnreadResult', {count: dialogMessageUnreadResult.messages.length});
			}

			promise.fulfill(response);

		}, false, false, BX.Messenger.Utils.getLogTrackingParams({name: RestMethod.imDialogMessagesUnread, dialog: this.getDialogData()}));

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

		this.store.dispatch(MessengerMessageStore.actionStart, {
			id: message.id,
			chatId: this.getChatId()
		});

		this.processSendMessages();
	}

	readMessage(messageId)
	{
		if (messageId)
		{
			this.messageReadQueue.push(parseInt(messageId));
		}

		if (this.offline)
		{
			return false;
		}

		this.timer.start('readMessage', null, .1, (id, params) =>
		{
			this.messageReadQueue = this.messageReadQueue.filter(elementId => {
				if (!this.messageLastReadId)
				{
					this.messageLastReadId = elementId;
				}
				else if (this.messageLastReadId < elementId)
				{
					this.messageLastReadId = elementId;
				}
				return false;
			});

			if (this.messageLastReadId <= 0)
			{
				return false
			}

			this.store.dispatch(MessengerMessageStore.readMessages, {
				chatId: this.getChatId(),
				readId: this.messageLastReadId
			}).then(result => {
				this.store.dispatch(MessengerDialogStore.decreaseCounter, {
					dialogId: this.getDialogId(),
					count: result.count
				});
			});

			this.timer.start('readMessageServer', null, .5, (id, params) => {
				this.restClient.callMethod(RestMethod.imDialogRead, {
					'DIALOG_ID': this.getDialogId(),
					'MESSAGE_ID': this.messageLastReadId
				})
				// TODO catch set message to unread status
			});
		});
	}

/* endregion 05. Templates and template interaction */

/* region 03-05. Messenger interaction and utils */
	startWriting(params)
	{
		let {dialogId, userId, userName, action = true} = params;

		if (action)
		{
			this.store.dispatch(MessengerDialogStore.updateWriting, {
				dialogId,
				userId,
				userName,
				action : true
			});

			this.timer.start('writingEnd', dialogId+'|'+userId, 35, (id, params) => {
				let {dialogId, userId} = params;
				this.store.dispatch(MessengerDialogStore.updateWriting, {
					dialogId,
					userId,
					action: false
				});
			}, {dialogId, userId});
		}
		else
		{
			this.timer.stop('writingStart', dialogId+'|'+userId, true);
			this.timer.stop('writingEnd', dialogId+'|'+userId);
		}
	};
/* endregion */

/* region 03-06. Widget interaction and utils */

	start()
	{
		if (this.isSessionActive())
		{
			this.requestWidgetData();
		}
	}

	open(params = {})
	{
		if (!params.openFromButton && this.buttonInstance)
		{
			this.buttonInstance.wm.showById('openline_livechat');
		}

		if (!this.checkBrowserVersion())
		{
			this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
		}
		else if (BX.Messenger.Utils.versionCompare(Vue.version, '2.1') < 0)
		{
			alert(this.localize.BX_LIVECHAT_OLD_VUE);
			console.error(`LiveChatWidget.error: OLD_VUE_VERSION (${this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', Vue.version)})`);

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

		if (this.store.state.widget.widgetData.reopen)
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
		return this.store.state.widget.widgetData.configId;
	}

	isWidgetDataRequested()
	{
		return this.widgetDataRequested;
	}

	isChatLoaded()
	{
		return this.store.state.widget.dialogData.chatId > 0;
	}

	isSessionActive()
	{
		return !this.store.state.widget.dialogData.sessionClose;
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
		return this.store.state.widget.userData.id > 0;
	}

	getSiteId()
	{
		return this.host.replace(/(http.?:\/\/)|([:.\\\/])/mg, "")+this.code;
	}

	getMessageLimit()
	{
		return this.store.state.widget.dialogData.messageLimit;
	}

	isUnreadMessagesLoaded()
	{
		let dialog = this.store.state[MessengerStore.dialogues].collection[this.getDialogId()];
		if (!dialog)
		{
			return true;
		}

		if (dialog.unreadLastId <= 0)
		{
			return true;
		}

		let collection = this.store.state[MessengerStore.messages].collection[this.getChatId()];
		if (!collection || collection.length <= 0)
		{
			return true;
		}

		let lastElementId = 0;
		for (let index = collection.length-1; index >= 0; index--)
		{
			let lastElement = collection[index];
			if (typeof lastElement.id === "number")
			{
				lastElementId = lastElement.id;
				break;
			}
		}

		return lastElementId >= dialog.unreadLastId;
	}

	getHost()
	{
		return this.host;
	}

	getConfigId()
	{
		return this.store.state.widget.widgetData.configId;
	}

	getChatId()
	{
		return this.store.state.widget.dialogData.chatId;
	}

	isDialogStart()
	{
		return this.store.state.widget.widgetData.dialogStart;
	}

	getDialogId()
	{
		return 'chat'+this.getChatId();
	}

	getDialogData(dialogId = this.getDialogId())
	{
		return this.store.state[MessengerStore.dialogues].collection[dialogId];
	}

	getDiskFolderId()
	{
		return this.store.state.widget.dialogData.diskFolderId;
	}

	getSessionId()
	{
		return this.store.state.widget.dialogData.sessionId;
	}

	getUserHash()
	{
		return this.store.state.widget.userData.hash;
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
		return this.store.state.widget.userData.id;
	}

	getUserData()
	{
		return this.store.state.widget.userData;
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
			'consent_url': this.store.state.widget.widgetData.consentUrl? location.href: '',
			'trace_data': this.getCrmTraceData(),
			'custom_data': this.getCustomData()
		}
	}

	getWidgetLocationCode()
	{
		return LocationStyle[this.store.state.widget.widgetData.location];
	}

	setUserRegisterData(params)
	{
		const validUserFields = ['hash', 'name', 'lastName', 'avatar', 'email', 'www', 'gender', 'position'];

		if (!BX.Messenger.Utils.types.isPlainObject(params))
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

		this.store.commit(WidgetStore.userData, {
			id: 0,
			hash: '',
		});
		this.store.commit(WidgetStore.widgetData, {
			configId: 0,
			dialogStart: false,
		});
		this.store.commit(WidgetStore.dialogData, {
			chatId: 0,
			diskFolderId: 0,
			sessionId: 0,
			sessionClose: false,
			userVote: false,
			userConsent: false,
		});

		BX.LiveChatCookie = {};
		BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.widgetData);
		BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.dialogData);
		BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.userData);

		Cookie.set(null, 'LIVECHAT_HASH', '', {expires: 365*86400, path: '/'});

		this.restClient.setAuthId(RestAuth.guest, authToken);
	}

	setCustomData(params)
	{
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
			localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="#reload" onclick="location.reload()">').replace('#LINK_END#', '</a>');
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

		this.store.commit(WidgetStore.widgetData, {error: {active: true, code, description: localizeDescription}});
	}

	clearError()
	{
		this.store.commit(WidgetStore.widgetData, {error: {active: false, code: '', description: ''}});
	}

	/**
	 *
	 * @param params {Object}
	 * @returns {Function|Boolean} - Unsubscribe callback function or False
	 */
	subscribe(params)
	{
		if (!BX.Messenger.Utils.types.isPlainObject(params))
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
		if (typeof phrases !== "object")
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
	};

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

	getDateFormat()
	{
		if (this.dateFormat)
		{
			return this.dateFormat;
		}

		this.dateFormat = Object.create(BX.Main.Date);
		this.dateFormat._getMessage = (phrase) => this.getLocalize(phrase);

		return this.dateFormat;
	}

	/* endregion */
}
/* endregion 03. Widget private interface */




/* region 04. Cookie function */
const Cookie =
{
	get(siteId, name)
	{
		let cookieName = siteId? siteId+'_'+name: name;

		if (navigator.cookieEnabled)
		{
			let result = document.cookie.match(new RegExp(
				"(?:^|; )" + cookieName.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + "=([^;]*)"
			));

			if (result)
			{
				return decodeURIComponent(result[1]);
			}
		}

		if (BX.Messenger.LocalStorage.isEnabled())
		{
			let result = BX.Messenger.LocalStorage.get(siteId, 0, name, undefined);
			if (typeof result !== 'undefined')
			{
				return result;
			}
		}

		if (typeof BX.LiveChatCookie === 'undefined')
		{
			BX.LiveChatCookie = {};
		}

		return BX.LiveChatCookie[cookieName];
	},
	set(siteId, name, value, options)
	{
		options = options || {};

		let expires = options.expires;
		if (typeof(expires) == "number" && expires)
		{
			let currentDate = new Date();
			currentDate.setTime(currentDate.getTime() + expires * 1000);
			expires = options.expires = currentDate;
		}

		if (expires && expires.toUTCString)
		{
			options.expires = expires.toUTCString();
		}

		value = encodeURIComponent(value);

		let cookieName = siteId? siteId+'_'+name: name;
		let updatedCookie = cookieName + "=" + value;

		for (let propertyName in options)
		{
			if (!options.hasOwnProperty(propertyName))
			{
				continue;
			}
			updatedCookie += "; " + propertyName;

			let propertyValue = options[propertyName];
			if (propertyValue !== true)
			{
				updatedCookie += "=" + propertyValue;
			}
		}

		document.cookie = updatedCookie;

		if (typeof BX.LiveChatCookie === 'undefined')
		{
			BX.LiveChatCookie = {};
		}

		BX.LiveChatCookie[cookieName] = value;
		BX.Messenger.LocalStorage.set(siteId, 0, name, value);

		return true;
	}
};
/* endregion 04. Cookie function */

/* region 05. Rest client */

class LiveChatRestClient
{
	constructor(params)
	{
		this.queryAuthRestore = false;

		this.setAuthId(RestAuth.guest);

		this.restClient = new BX.RestClient({
			endpoint: params.endpoint,
			queryParams: this.queryParams,
			cors: true
		});
	}

	setAuthId(authId, customAuthId = '')
	{
		if (typeof this.queryParams !== 'object')
		{
			this.queryParams = {};
		}

		if (
			authId == RestAuth.guest
			|| typeof authId === 'string' && authId.match(/^[a-f0-9]{32}$/)
		)
		{
			this.queryParams.livechat_auth_id = authId;
		}
		else
		{
			console.error(`%LiveChatRestClient.setAuthId: auth is not correct (%c${authId}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
			return false;
		}

		if (
			authId == RestAuth.guest
			&& typeof customAuthId === 'string' && customAuthId.match(/^[a-f0-9]{32}$/)
		)
		{
			this.queryParams.livechat_custom_auth_id = customAuthId;
		}

		return true;
	}

	getAuthId()
	{
		if (typeof this.queryParams !== 'object')
		{
			this.queryParams = {};
		}

		return this.queryParams.livechat_auth_id || null;
	}

	callMethod(method, params, callback, sendCallback, logTag = null)
	{
		if (!logTag)
		{
			logTag = BX.Messenger.Utils.getLogTrackingParams({
				name: method,
			});
		}

		const promise = new BX.Promise();

		this.restClient.callMethod(method, params, null, sendCallback, logTag).then(result => {

			this.queryAuthRestore = false;
			promise.fulfill(result);

		}).catch(result => {

			let error = result.error();
			if (error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER')
			{
				this.setAuthId(error.ex.hash);

				if (method === RestMethod.widgetUserRegister)
				{
					console.warn(`BX.LiveChatRestClient: ${error.ex.error_description} (${error.ex.error})`);

					this.queryAuthRestore = false;
					promise.reject(result);
					return false;
				}

				if (!this.queryAuthRestore)
				{
					console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');

					this.queryAuthRestore = true;
					this.restClient.callMethod(method, params, null, sendCallback, logTag).then(result => {
						this.queryAuthRestore = false;
						promise.fulfill(result);
					}).catch(result => {
						this.queryAuthRestore = false;
						promise.reject(result);
					});

					return false;
				}
			}

			this.queryAuthRestore = false;
			promise.reject(result);
		});

		return promise;
	};

	callBatch(calls, callback, bHaltOnError, sendCallback, logTag)
	{
		let resultCallback = (result) =>
		{
			let error = null;
			for (let method in calls)
			{
				if (!calls.hasOwnProperty(method))
				{
					continue;
				}

				let error = result[method].error();
				if (error && error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER')
				{
					this.setAuthId(error.ex.hash);
					if (method === RestMethod.widgetUserRegister)
					{
						console.warn(`BX.LiveChatRestClient: ${error.ex.error_description} (${error.ex.error})`);

						this.queryAuthRestore = false;
						callback(result);
						return false;
					}

					if (!this.queryAuthRestore)
					{
						console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');

						this.queryAuthRestore = true;
						this.restClient.callBatch(calls, callback, bHaltOnError, sendCallback, logTag);

						return false;
					}
				}
			}

			this.queryAuthRestore = false;
			callback(result);

			return true;
		};

		return this.restClient.callBatch(calls, resultCallback, bHaltOnError, sendCallback, logTag);
	};
}

/* endregion 05. Rest client */

/* region 06. Vue Components */

/* region 06-01. bx-livechat component */
/**
 * @notice Do not mutate or clone this component! It is under development.
 */
BX.Vue.component('bx-livechat',
{
	data()
	{
		return {
			viewPortMetaSiteNode: null,
			viewPortMetaWidgetNode: null,
			storedMessage: '',
			storedFile: null,
			textareaFocused: false,
			textareaDrag: false,
			textareaHeight: 100,
			textareaMinimumHeight: 100,
			textareaMaximumHeight: BX.Messenger.Utils.device.isMobile()? 200: 300,
			chat: {
				id: 0
			},
		}
	},
	created()
	{
		this.onCreated();

		document.addEventListener('keydown', this.onWindowKeyDown);
		this.$root.$on('requestShowForm', this.onRequestShowForm);
	},
	beforeDestroy()
	{
		document.removeEventListener('keydown', this.onWindowKeyDown);
		this.$root.$off('requestShowForm', this.onRequestShowForm);

		this.onTextareaDragEventRemove();
	},
	computed:
	{
		FormType: () => FormType,
		VoteType: () => VoteType,
		DeviceType: () => DeviceType,
		textareaHeightStyle(state)
		{
			return 'flex: 0 0 '+this.textareaHeight+'px;'
		},
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		widgetMobileDisabled(state)
		{
			if (state.widgetData.deviceType == DeviceType.mobile)
			{
				if (navigator.userAgent.toString().includes('iPad'))
				{
				}
				else if (state.widgetData.deviceOrientation == DeviceOrientation.horizontal)
				{
					if (navigator.userAgent.toString().includes('iPhone'))
					{
						return true;
					}
					else
					{
						return !(typeof window.screen === 'object' && window.screen.availHeight >= 800);
					}
				}
			}

			return false;
		},
		widgetClassName(state)
		{
			let className = ['bx-livechat-wrapper'];

			className.push('bx-livechat-show');

			if (state.widgetData.pageMode)
			{
				className.push('bx-livechat-page-mode');
			}
			else
			{
				className.push('bx-livechat-position-'+LocationStyle[state.widgetData.location]);
			}

			if (state.widgetData.language == LanguageType.russian)
			{
				className.push('bx-livechat-logo-ru');
			}
			else if (state.widgetData.language == LanguageType.ukraine)
			{
				className.push('bx-livechat-logo-ua');
			}
			else
			{
				className.push('bx-livechat-logo-en');
			}

			if (!state.widgetData.online)
			{
				className.push('bx-livechat-offline-state');
			}

			if (state.widgetData.dragged)
			{
				className.push('bx-livechat-drag-n-drop');
			}

			if (state.widgetData.dialogStart)
			{
				className.push('bx-livechat-chat-start');
			}

			if (
				state.dialogData.operator.name
				&& !(state.widgetData.deviceType == DeviceType.mobile && state.widgetData.deviceOrientation == DeviceOrientation.horizontal)
			)
			{
				className.push('bx-livechat-has-operator');
			}

			if (BX.Messenger.Utils.device.isMobile())
			{
				className.push('bx-livechat-mobile');
			}
			else if (BX.Messenger.Utils.browser.isSafari())
			{
				className.push('bx-livechat-browser-safari');
			}
			else if (BX.Messenger.Utils.browser.isIe())
			{
				className.push('bx-livechat-browser-ie');
			}

			if (BX.Messenger.Utils.platform.isMac())
			{
				className.push('bx-livechat-mac');
			}
			else
			{
				className.push('bx-livechat-custom-scroll');
			}

			if (state.widgetData.styles.backgroundColor && BX.Messenger.Utils.isDarkColor(state.widgetData.styles.iconColor))
			{
				className.push('bx-livechat-bright-header');
			}

			return className.join(' ');
		},
		showMessageDialog()
		{
			return this.messageCollection.length > 0;
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData,
			userData: state => state.widget.userData,
			dialogData: state => state.widget.dialogData,
			messageCollection: state => state[MessengerStore.messages].collection[state.widget.dialogData.chatId]
		})
	},
	watch:
	{
		sessionClose(value)
		{
			BX.Messenger.Logger.log('sessionClose change', value);
		}
	},
	methods:
	{
		close(event)
		{
			if (this.$store.state.widget.widgetData.pageMode)
			{
				return false;
			}

			this.onBeforeClose();
			this.$store.commit(WidgetStore.widgetData, {showed: false});
		},
		showLikeForm()
		{
			if (this.offline)
			{
				return false;
			}

			clearTimeout(this.showFormTimeout);
			if (!this.$store.state.widget.widgetData.vote.enable)
			{
				return false;
			}
			if (
				this.$store.state.widget.dialogData.sessionClose
				&& this.$store.state.widget.dialogData.userVote != VoteType.none
			)
			{
				return false;
			}
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.like});
		},
		showWelcomeForm()
		{
			clearTimeout(this.showFormTimeout);
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.welcome});
		},
		showOfflineForm()
		{
			clearTimeout(this.showFormTimeout);

			if (this.$store.state.widget.dialogData.showForm !== FormType.welcome)
			{
				this.$store.commit(WidgetStore.widgetData, {showForm: FormType.offline});
			}
		},
		showHistoryForm()
		{
			clearTimeout(this.showFormTimeout);
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.history});
		},
		hideForm()
		{
			clearTimeout(this.showFormTimeout);
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
		},
		showConsentWidow()
		{
			this.$store.commit(WidgetStore.widgetData, {showConsent: true});
		},
		agreeConsentWidow()
		{
			this.$store.commit(WidgetStore.widgetData, {showConsent: false});

			this.$root.$bitrixController.sendConsentDecision(true);

			if (this.storedMessage || this.storedFile)
			{
				if (this.storedMessage)
				{
					this.onTextareaSend({focus: this.widgetData.deviceType != DeviceType.mobile});
					this.storedMessage = '';
				}
				if (this.storedFile)
				{
					this.onTextareaFileSelected();
					this.storedFile = '';
				}
			}
			else if (this.widgetData.showForm == FormType.none)
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		disagreeConsentWidow()
		{
			this.$store.commit(WidgetStore.widgetData, {showForm : FormType.none});
			this.$store.commit(WidgetStore.widgetData, {showConsent : false});

			this.$root.$bitrixController.sendConsentDecision(false);

			if (this.storedMessage)
			{
				this.$root.$emit('onMessengerTextareaInsertText', {
					text: this.storedMessage,
					focus: this.widgetData.deviceType != DeviceType.mobile
				});
				this.storedMessage = '';
			}
			if (this.storedFile)
			{
				this.storedFile = '';
			}

			if (this.widgetData.deviceType != DeviceType.mobile)
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		logEvent(name, ...params)
		{
			BX.Messenger.Logger.info(name, ...params);
		},
		onCreated()
		{
			if(BX.Messenger.Utils.device.isMobile())
			{
				let viewPortMetaSiteNode = Array.from(
					document.head.getElementsByTagName('meta')
				).filter(element => element.name == 'viewport')[0];

				if (viewPortMetaSiteNode)
				{
					this.viewPortMetaSiteNode = viewPortMetaSiteNode;
					document.head.removeChild(this.viewPortMetaSiteNode);
				}
				else
				{
					let contentWidth = document.body.offsetWidth;
					if (contentWidth < window.innerWidth)
					{
						contentWidth = window.innerWidth;
					}
					if (contentWidth < 1024)
					{
						contentWidth = 1024;
					}

					this.viewPortMetaSiteNode = document.createElement('meta');
					this.viewPortMetaSiteNode.setAttribute('name', 'viewport');
					this.viewPortMetaSiteNode.setAttribute('content', `width=${contentWidth}, initial-scale=1.0, user-scalable=1`);
				}

				if (!this.viewPortMetaWidgetNode)
				{
					this.viewPortMetaWidgetNode = document.createElement('meta');
					this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
					this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
					document.head.appendChild(this.viewPortMetaWidgetNode);
				}

				document.body.classList.add('bx-livechat-mobile-state');

				if (BX.Messenger.Utils.browser.isSafariBased())
				{
					document.body.classList.add('bx-livechat-mobile-safari-based');
				}

				setTimeout(() => {
					this.$store.commit(WidgetStore.widgetData, {showed: true});
				}, 50);
			}
			else
			{
				this.$store.commit(WidgetStore.widgetData, {showed: true});
			}

			this.textareaHeight = this.$store.state.widget.widgetData.textareaHeight || this.textareaHeight;

			this.$store.commit(MessengerFileStore.initCollection, {chatId: this.$root.$bitrixController.getChatId()});
			this.$store.commit(MessengerMessageStore.initCollection, {chatId: this.$root.$bitrixController.getChatId()});
			this.$store.commit(MessengerDialogStore.initCollection, {dialogId: this.$root.$bitrixController.getDialogId(), fields: {
				entityType: 'LIVECHAT',
				type: 'livechat'
			}});
		},
		onBeforeClose()
		{
			if(BX.Messenger.Utils.device.isMobile())
			{
				document.body.classList.remove('bx-livechat-mobile-state');

				if (BX.Messenger.Utils.browser.isSafariBased())
				{
					document.body.classList.remove('bx-livechat-mobile-safari-based');
				}

				if (this.viewPortMetaWidgetNode)
				{
					document.head.removeChild(this.viewPortMetaWidgetNode);
					this.viewPortMetaWidgetNode = null;
				}

				if (this.viewPortMetaSiteNode)
				{
					document.head.appendChild(this.viewPortMetaSiteNode);
					this.viewPortMetaSiteNode = null;
				}
			}
		},
		onAfterClose()
		{
			this.$root.$bitrixController.close();
		},
		onRequestShowForm(event)
		{
			clearTimeout(this.showFormTimeout);
			if (event.type == FormType.welcome)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showWelcomeForm();
					}, 5000);
				}
				else
				{
					this.showWelcomeForm();
				}
			}
			else if (event.type == FormType.offline)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showOfflineForm();
					}, 3000);
				}
				else
				{
					this.showOfflineForm();
				}
			}
			else if (event.type == FormType.like)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showLikeForm();
					}, 5000);
				}
				else
				{
					this.showLikeForm();
				}
			}
		},
		onDialogRequestHistory(event)
		{
			this.$root.$bitrixController.getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.$root.$bitrixController.getDialogUnread(event.lastId);
		},
		onDialogMessageClickByUserName(event)
		{
			// TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
			this.$root.$emit('onMessengerTextareaInsertText', {text: event.user.name+' '});
		},
		onDialogMessageClickByCommand(event)
		{
			if (event.type === 'put')
			{
				this.$root.$emit('onMessengerTextareaInsertText', {text: event.value+' '});
			}
			else if (event.type === 'send')
			{
				this.$root.$bitrixController.addMessage(event.value);
			}
			else
			{
				BX.Messenger.Logger.warn('Unprocessed command', event);
			}
		},
		onDialogMessageMenuClick(event)
		{
			BX.Messenger.Logger.warn('Message menu:', event);
		},
		onDialogMessageRetryClick(event)
		{
			BX.Messenger.Logger.warn('Message retry:', event);
			this.$root.$bitrixController.retrySendMessage(event.message);
		},
		onDialogReadMessage(event)
		{
			this.$root.$bitrixController.readMessage(event.id);
		},
		onDialogClick(event)
		{
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
		},
		onTextareaSend(event)
		{
			event.focus = event.focus !== false;

			if (this.widgetData.showForm == FormType.smile)
			{
				this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
			}

			if (!this.dialogData.userConsent && this.widgetData.consentUrl)
			{
				if (event.text)
				{
					this.storedMessage = event.text;
				}
				this.showConsentWidow();

				return false;
			}

			event.text = event.text? event.text: this.storedMessage;
			if (!event.text)
			{
				return false;
			}

			this.hideForm();
			this.$root.$bitrixController.addMessage(event.text);

			if (event.focus)
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}

			return true;
		},
		onTextareaWrites(event)
		{
			this.$root.$bitrixController.writesMessage();
		},
		onTextareaFocus(event)
		{
			if (
				this.widgetData.copyright &&
				this.widgetData.deviceType == DeviceType.mobile
			)
			{
				this.widgetData.copyright = false;
			}
			if (BX.Messenger.Utils.device.isMobile())
			{
				clearTimeout(this.onTextareaFocusScrollTimeout);
				this.onTextareaFocusScrollTimeout = setTimeout(() => {
					document.addEventListener('scroll', this.onWindowScroll);
				}, 1000);
			}
			this.textareaFocused = true;
		},
		onTextareaBlur(event)
		{
			if (!this.widgetData.copyright && this.widgetData.copyright !== this.$root.$bitrixController.copyright)
			{
				this.widgetData.copyright = this.$root.$bitrixController.copyright;
				this.$nextTick(() => {
					this.$root.$emit('onMessengerDialogScrollToBottom', {force: true});
				});
			}
			if (BX.Messenger.Utils.device.isMobile())
			{
				clearTimeout(this.onTextareaFocusScrollTimeout);
				document.removeEventListener('scroll', this.onWindowScroll);
			}

			this.textareaFocused = false;
		},
		onTextareaStartDrag(event)
		{
			if (this.textareaDrag)
			{
				return;
			}

			BX.Messenger.Logger.log('Livechat: textarea drag started');

			this.textareaDrag = true;

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.textareaDragCursorStartPoint = event.clientY;
			this.textareaDragHeightStartPoint = this.textareaHeight;

			this.onTextareaDragEventAdd();

			this.$root.$emit('onMessengerTextareaBlur', true);
		},
		onTextareaContinueDrag(event)
		{
			if (!this.textareaDrag)
			{
				return;
			}

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.textareaDragCursorControlPoint = event.clientY;

			let textareaHeight = Math.max(
				Math.min(this.textareaDragHeightStartPoint + this.textareaDragCursorStartPoint - this.textareaDragCursorControlPoint, this.textareaMaximumHeight)
			, this.textareaMinimumHeight);

			BX.Messenger.Logger.log('Livechat: textarea drag', 'new: '+textareaHeight, 'curr: '+this.textareaHeight);

			if (this.textareaHeight != textareaHeight)
			{
				this.textareaHeight = textareaHeight;
			}
		},
		onTextareaStopDrag()
		{
			if (!this.textareaDrag)
			{
				return;
			}

			BX.Messenger.Logger.log('Livechat: textarea drag ended');

			this.textareaDrag = false;

			this.onTextareaDragEventRemove();

			this.$store.commit(WidgetStore.widgetData, {textareaHeight: this.textareaHeight});
			this.$root.$emit('onMessengerDialogScrollToBottom', {force: true});
		},
		onTextareaDragEventAdd()
		{
			document.addEventListener('mousemove', this.onTextareaContinueDrag);
			document.addEventListener('touchmove', this.onTextareaContinueDrag);
			document.addEventListener('touchend', this.onTextareaStopDrag);
			document.addEventListener('mouseup', this.onTextareaStopDrag);
			document.addEventListener('mouseleave', this.onTextareaStopDrag);
		},
		onTextareaDragEventRemove()
		{
			document.removeEventListener('mousemove', this.onTextareaContinueDrag);
			document.removeEventListener('touchmove', this.onTextareaContinueDrag);
			document.removeEventListener('touchend', this.onTextareaStopDrag);
			document.removeEventListener('mouseup', this.onTextareaStopDrag);
			document.removeEventListener('mouseleave', this.onTextareaStopDrag);
		},
		onTextareaFileSelected(event)
		{
			let fileInput = event && event.fileInput? event.fileInput: this.storedFile;
			if (!fileInput)
			{
				return false;
			}

			if (fileInput.files[0].size > this.widgetData.disk.maxFileSize)
			{
				// TODO change alert to correct overlay window
				alert(this.localize.BX_LIVECHAT_FILE_SIZE_EXCEEDED.replace('#LIMIT#', Math.round(this.widgetData.disk.maxFileSize/1024/1024)));
				return false;
			}

			if (!this.dialogData.userConsent && this.widgetData.consentUrl)
			{
				this.storedFile = event.fileInput;
				this.showConsentWidow();

				return false;
			}

			this.$root.$bitrixController.addFile(fileInput);
		},
		onTextareaAppButtonClick(event)
		{
			if (event.appId == FormType.smile)
			{
				if (this.widgetData.showForm == FormType.smile)
				{
					this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
				}
				else
				{
					this.$store.commit(WidgetStore.widgetData, {showForm: FormType.smile});
				}
			}
			else
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		onPullRequestConfig(event)
		{
			this.$root.$bitrixController.recoverPullConnection();
		},
		onSmilesSelectSmile(event)
		{
			this.$root.$emit('onMessengerTextareaInsertText', {text: event.text});
		},
		onSmilesSelectSet()
		{
			this.$root.$emit('onMessengerTextareaFocus');
		},
		onWindowKeyDown(event)
		{
			if (event.keyCode == 27)
			{
				if (this.widgetData.showForm != FormType.none)
				{
					this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
				}
				else if (this.widgetData.showConsent)
				{
					this.disagreeConsentWidow();
				}
				else
				{
					this.close();
				}

				event.preventDefault();
				event.stopPropagation();

				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		onWindowScroll(event)
		{
			clearTimeout(this.onWindowScrollTimeout);
			this.onWindowScrollTimeout = setTimeout(() => {
				this.$root.$emit('onMessengerTextareaBlur', true);
			}, 50);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-show" leave-active-class="bx-livechat-close" @after-leave="onAfterClose">
			<div :class="widgetClassName" v-if="widgetData.showed">
				<div class="bx-livechat-box">
					<bx-livechat-head :isWidgetDisabled="widgetMobileDisabled" @like="showLikeForm" @history="showHistoryForm" @close="close"/>
					<template v-if="widgetMobileDisabled">
						<bx-livechat-body-orientation-disabled/>
					</template>
					<template v-else-if="widgetData.error.active">
						<bx-livechat-body-error/>
					</template>
					<template v-else-if="!widgetData.configId">
						<div class="bx-livechat-body" key="loading-body">
							<bx-livechat-body-loading/>
						</div>
					</template>			
					<template v-else>
						<template v-if="!widgetData.dialogStart">
							<div class="bx-livechat-body" key="welcome-body">
								<bx-livechat-body-operators/>
								<keep-alive include="bx-livechat-smiles">
									<template v-if="widgetData.showForm == FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>	
									</template>
								</keep-alive>
							</div>
						</template>
						<template v-else-if="widgetData.dialogStart">
							<bx-pull-status :canReconnect="true" @reconnect="onPullRequestConfig"/>
							<div :class="['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]" key="with-message">
								<transition name="bx-livechat-animation-upload-file">
									<template v-if="widgetData.uploadFile">
										<div class="bx-livechat-file-upload">	
											<div class="bx-livechat-file-upload-sending"></div>
											<div class="bx-livechat-file-upload-text">{{localize.BX_LIVECHAT_FILE_UPLOAD}}</div>
										</div>	
									</template>
								</transition>	
								<template v-if="showMessageDialog">
									<div class="bx-livechat-dialog">
										<bx-messenger-dialog
											:userId="userData.id" 
											:dialogId="dialogData.dialogId"
											:chatId="dialogData.chatId"
											:messageLimit="dialogData.messageLimit"
											:enableEmotions="false"
											:enableDateActions="false"
											:enableCreateContent="false"
											:showMessageAvatar="false"
											:showMessageMenu="false"
											listenEventScrollToBottom="onMessengerDialogScrollToBottom"
											listenEventRequestHistory="onDialogRequestHistoryResult"
											listenEventRequestUnread="onDialogRequestUnreadResult"
											@readMessage="onDialogReadMessage"
											@requestHistory="onDialogRequestHistory"
											@requestUnread="onDialogRequestUnread"
											@clickByCommand="onDialogMessageClickByCommand"
											@clickByUserName="onDialogMessageClickByUserName"
											@clickByMessageMenu="onDialogMessageMenuClick"
											@clickByMessageRetry="onDialogMessageRetryClick"
											@click="onDialogClick"
										 />
									</div>	 
								</template>
								<template v-else>
									<bx-livechat-body-loading/>
								</template>
								<keep-alive include="bx-livechat-smiles">
									<template v-if="widgetData.showForm == FormType.like && widgetData.vote.enable">
										<bx-livechat-form-vote/>
									</template>
									<template v-else-if="widgetData.showForm == FormType.welcome">
										<bx-livechat-form-welcome/>	
									</template>
									<template v-else-if="widgetData.showForm == FormType.offline">
										<bx-livechat-form-offline/>	
									</template>
									<template v-else-if="widgetData.showForm == FormType.history">
										<bx-livechat-form-history/>	
									</template>
									<template v-else-if="widgetData.showForm == FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>	
									</template>
								</keep-alive>
							</div>
						</template>	
						<div class="bx-livechat-textarea" :style="textareaHeightStyle" ref="textarea">
							<div class="bx-livechat-textarea-resize-handle" @mousedown="onTextareaStartDrag" @touchstart="onTextareaStartDrag"></div>
							<bx-messenger-textarea
								:siteId="widgetData.siteId"
								:userId="userData.id"
								:dialogId="dialogData.dialogId"
								:writesEventLetter="3"
								:enableEdit="true"
								:enableCommand="false"
								:enableMention="false"
								:enableFile="widgetData.disk.enabled"
								:autoFocus="widgetData.deviceType !== DeviceType.mobile"
								:isMobile="widgetData.deviceType === DeviceType.mobile"
								:styles="{button: {backgroundColor: widgetData.styles.backgroundColor, iconColor: widgetData.styles.iconColor}}"
								listenEventInsertText="onMessengerTextareaInsertText"
								listenEventFocus="onMessengerTextareaFocus"
								listenEventBlur="onMessengerTextareaBlur"
								@writes="onTextareaWrites" 
								@send="onTextareaSend" 
								@focus="onTextareaFocus" 
								@blur="onTextareaBlur" 
								@edit="logEvent('edit message', $event)"
								@fileSelected="onTextareaFileSelected"
								@appButtonClick="onTextareaAppButtonClick"
							/>
						</div>
						<bx-livechat-form-consent @agree="agreeConsentWidow" @disagree="disagreeConsentWidow"/>
						<template v-if="widgetData.copyright">
							<bx-livechat-footer/>
						</template>
					</template>
				</div>
			</div>
		</transition>
	`
});
/* endregion 06-01. bx-livechat component */

/* region 06-02. bx-livechat-head component */
BX.Vue.component('bx-livechat-head',
{
	/**
	 * @emits 'close'
	 * @emits 'like'
	 * @emits 'history'
	 */
	props:
	{
		isWidgetDisabled: { default: false },
	},
	methods:
	{
		close(event)
		{
			this.$emit('close');
		},
		like(event)
		{
			this.$emit('like');
		},
		history(event)
		{
			this.$emit('history');
		},
	},
	computed:
	{
		VoteType: () => VoteType,

		customBackgroundStyle(state)
		{
			return state.widgetData.styles.backgroundColor? 'background-color: '+state.widgetData.styles.backgroundColor+'!important;': '';
		},
		customBackgroundOnlineStyle(state)
		{
			return state.widgetData.styles.backgroundColor? 'border-color: '+state.widgetData.styles.backgroundColor+'!important;': '';
		},
		showName()
		{
			return this.dialogData.operator.firstName || this.dialogData.operator.lastName;
		},
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData,
			dialogData: state => state.widget.dialogData
		})
	},
	watch:
	{
		showName(value)
		{
			if (value)
			{
				setTimeout(() => {
					this.$root.$emit('onMessengerDialogScrollToBottom');
				}, 300);
			}
		},
	},
	template: `
		<div class="bx-livechat-head-wrap">
			<template v-if="isWidgetDisabled">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widgetData.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>	
			</template>
			<template v-else-if="widgetData.error.active">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widgetData.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
			<template v-else-if="!widgetData.configId">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widgetData.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>			
			<template v-else>
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<template v-if="!showName">
						<div class="bx-livechat-title">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>
					</template>
					<template v-else>
						<div class="bx-livechat-user bx-livechat-status-online">
							<template v-if="dialogData.operator.avatar">
								<div class="bx-livechat-user-icon" :style="'background-image: url('+encodeURI(dialogData.operator.avatar)+')'">
									<div v-if="dialogData.operator.online" class="bx-livechat-user-status" :style="customBackgroundOnlineStyle"></div>
								</div>
							</template>
							<template v-else>
								<div class="bx-livechat-user-icon">
									<div v-if="dialogData.operator.online" class="bx-livechat-user-status" :style="customBackgroundOnlineStyle"></div>
								</div>
							</template>
						</div>
						<div class="bx-livechat-user-info">
							<div class="bx-livechat-user-name">{{dialogData.operator.firstName? dialogData.operator.firstName: dialogData.operator.name}}</div>
							<div class="bx-livechat-user-position">{{dialogData.operator.workPosition? dialogData.operator.workPosition: localize.BX_LIVECHAT_USER}}</div>
						</div>
					</template>
					<div class="bx-livechat-control-box">
						<span class="bx-livechat-control-box-active" v-if="widgetData.dialogStart && dialogData.sessionId">
							<button v-if="widgetData.vote.enable && (!dialogData.sessionClose || dialogData.sessionClose && dialogData.userVote == VoteType.none)" :class="'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(dialogData.userVote)" :title="localize.BX_LIVECHAT_VOTE_BUTTON" @click="like"></button>
							<button v-if="widgetData.vote.enable && dialogData.sessionClose && dialogData.userVote != VoteType.none" :class="'bx-livechat-control-btn bx-livechat-control-btn-disabled bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(dialogData.userVote)"></button>
							<button class="bx-livechat-control-btn bx-livechat-control-btn-mail" :title="localize.BX_LIVECHAT_MAIL_BUTTON_NEW" @click="history"></button>
						</span>	
						<button v-if="!widgetData.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
		</div>
	`
});
/* endregion 06-02. bx-livechat-head component */

/* region 06-03. bx-livechat-body-orientation-disabled component */
BX.Vue.component('bx-livechat-body-orientation-disabled',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				BX_LIVECHAT_MOBILE_ROTATE: this.$root.$bitrixMessages.BX_LIVECHAT_MOBILE_ROTATE
			});
		}
	},
	template: `
		<div class="bx-livechat-body" key="orientation-head">
			<div class="bx-livechat-mobile-orientation-box">
				<div class="bx-livechat-mobile-orientation-icon"></div>
				<div class="bx-livechat-mobile-orientation-text">{{localize.BX_LIVECHAT_MOBILE_ROTATE}}</div>
			</div>
		</div>
	`
});
/* endregion 06-03. bx-livechat-body-orientation-disabled component */

/* region 06-04. bx-livechat-body-loading component */
BX.Vue.component('bx-livechat-body-loading',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				BX_LIVECHAT_LOADING: this.$root.$bitrixMessages.BX_LIVECHAT_LOADING
			});
		}
	},
	template: `
		<div class="bx-livechat-loading-window">
			<svg class="bx-livechat-loading-circular" viewBox="25 25 50 50">
				<circle class="bx-livechat-loading-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				<circle class="bx-livechat-loading-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
			<h3 class="bx-livechat-help-title bx-livechat-help-title-md bx-livechat-loading-msg">{{localize.BX_LIVECHAT_LOADING}}</h3>
		</div>
	`
});
/* endregion 06-04. bx-livechat-body-loading component */

/* region 06-05. bx-livechat-body-operators component */
BX.Vue.component('bx-livechat-body-operators',
{
	computed:
	{
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData
		})
	},
	template: `
		<div class="bx-livechat-help-container">
			<transition name="bx-livechat-animation-fade">
				<h2 v-if="widgetData.online" key="online" class="bx-livechat-help-title bx-livechat-help-title-lg">{{localize.BX_LIVECHAT_ONLINE_LINE_1}}<div class="bx-livechat-help-subtitle">{{localize.BX_LIVECHAT_ONLINE_LINE_2}}</div></h2>
				<h2 v-else key="offline" class="bx-livechat-help-title bx-livechat-help-title-sm">{{localize.BX_LIVECHAT_OFFLINE}}</h2>
			</transition>	
			<div class="bx-livechat-help-user">
				<template v-for="operator in widgetData.operators">
					<div class="bx-livechat-user" :key="operator.id">
						<template v-if="operator.avatar">
							<div class="bx-livechat-user-icon" :style="'background-image: url('+encodeURI(operator.avatar)+')'"></div>
						</template>
						<template v-else>
							<div class="bx-livechat-user-icon"></div>
						</template>	
						<div class="bx-livechat-user-info">
							<div class="bx-livechat-user-name">{{operator.firstName? operator.firstName: operator.name}}</div>
						</div>
					</div>
				</template>	
			</div>
		</div>
	`
});
/* endregion 06-05. bx-livechat-body-operators component */

/* region 06-06. bx-livechat-body-error component */
BX.Vue.component('bx-livechat-body-error',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				BX_LIVECHAT_ERROR_TITLE: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_TITLE,
				BX_LIVECHAT_ERROR_DESC: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_DESC
			});
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData
		}),
	},
	template: `
		<div class="bx-livechat-body" key="error-body">
			<div class="bx-livechat-warning-window">
				<div class="bx-livechat-warning-icon"></div>
				<template v-if="widgetData.error.description"> 
					<div class="bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg" v-html="widgetData.error.description"></div>
				</template> 
				<template v-else>
					<div class="bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg">{{localize.BX_LIVECHAT_ERROR_TITLE}}</div>
					<div class="bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg">{{localize.BX_LIVECHAT_ERROR_DESC}}</div>
				</template> 
			</div>
		</div>
	`
});
/* endregion 06-06. bx-livechat-body-error component */

/* region 06-07. bx-livechat-smiles component */
BX.Vue.cloneComponent('bx-livechat-smiles', 'bx-smiles',
{
	methods:
	{
		hideForm(event)
		{
			this.$parent.hideForm();
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-livechat-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show" key="vote">
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-smiles-box">
					#PARENT_TEMPLATE#
				</div>
			</div>
		</transition>
	`
});
/* endregion 06-07. bx-livechat-smiles component */

/* region 06-08. bx-livechat-form-welcome component */
BX.Vue.component('bx-livechat-form-welcome',
{
	data()
	{
		return {
			fieldName: '',
			fieldEmail: '',
			fieldPhone: '',
			isFullForm: BX.Messenger.Utils.platform.isMobile()
		}
	},
	watch:
	{
		fieldName()
		{
			clearTimeout(this.showFormTimeout);
			this.showFormTimeout = setTimeout(this.showFullForm, 1000);

			clearTimeout(this.fieldNameTimeout);
			this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
		},
		fieldEmail(value)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
		fieldPhone(value)
		{
			clearTimeout(this.fieldPhoneTimeout);
			this.fieldPhoneTimeout = setTimeout(this.checkPhoneField, 300);
		}
	},
	computed:
	{
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			userData: state => state.widget.userData,
		})
	},
	created()
	{
		this.fieldName = '' + this.userData.name;
		this.fieldEmail = '' + this.userData.email;
		this.fieldPhone = '' + this.userData.phone;
	},
	methods:
	{
		formShowed()
		{
			if (!BX.Messenger.Utils.platform.isMobile())
			{
				this.$refs.nameInput.focus();
			}
		},
		showFullForm()
		{
			clearTimeout(this.showFormTimeout);
			this.isFullForm = true;
		},
		sendForm()
		{
			let name = this.fieldName;
			let email = this.checkEmailField()? this.fieldEmail: '';
			let phone = this.checkPhoneField()? this.fieldPhone: '';

			if (name || email || phone)
			{
				this.$root.$bitrixController.sendForm(FormType.welcome, {name, email, phone});
			}

			this.hideForm();
		},
		hideForm(event)
		{
			clearTimeout(this.showFormTimeout);
			clearTimeout(this.fieldNameTimeout);
			clearTimeout(this.fieldEmailTimeout);
			clearTimeout(this.fieldPhoneTimeout);

			this.$parent.hideForm();
		},
		onFieldEnterPress(event)
		{
			if (event.target === this.$refs.nameInput)
			{
				this.showFullForm();
				this.$refs.emailInput.focus();
			}
			else if (event.target === this.$refs.emailInput)
			{
				this.$refs.phoneInput.focus();
			}
			else
			{
				this.sendForm();
			}

			event.preventDefault();
		},
		checkNameField()
		{
			if (this.fieldName.length > 0)
			{
				if (this.$refs.name)
				{
					this.$refs.name.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.nameInput)
				{
					if (this.$refs.name)
					{
						this.$refs.name.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		},
		checkEmailField()
		{
			if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/))
			{
				if (this.$refs.email)
				{
					this.$refs.email.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.emailInput)
				{
					if (this.$refs.email)
					{
						this.$refs.email.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		},
		checkPhoneField()
		{
			if (this.fieldPhone.match(/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/))
			{
				if (this.$refs.phone)
				{
					this.$refs.phone.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.phoneInput)
				{
					if (this.$refs.phone)
					{
						this.$refs.phone.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		}
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_ABOUT_TITLE}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg" ref="name">
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_NAME" v-model="fieldName" ref="nameInput" @blur="checkNameField" @keydown.enter="onFieldEnterPress"  @keydown.tab="onFieldEnterPress">
					</div>
					<div :class="['bx-livechat-form-short', {
						'bx-livechat-form-full': isFullForm,
					}]">
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
						   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
						</div>
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="phone">
						   <div class="ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_PHONE" v-model="fieldPhone" ref="phoneInput" @blur="checkPhoneField" @keydown.enter="onFieldEnterPress">
						</div>
						<div class="bx-livechat-btn-box">
							<button class="bx-livechat-btn bx-livechat-btn-success" @click="sendForm">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>
						</div>
					</div>
				</div>
			</div>	
		</transition>	
	`
});
/* endregion 06-08. bx-livechat-form-welcome component */

BX.Vue.cloneComponent('bx-livechat-form-offline', 'bx-livechat-form-welcome',
{
	methods:
	{
		formShowed()
		{
			if (!BX.Messenger.Utils.platform.isMobile())
			{
				this.$refs.emailInput.focus();
			}
		},
		sendForm()
		{
			let name = this.fieldName;
			let email = this.checkEmailField()? this.fieldEmail: '';
			let phone = this.checkPhoneField()? this.fieldPhone: '';

			if (name || email || phone)
			{
				this.$root.$bitrixController.sendForm(FormType.offline, {name, email, phone});
			}

			this.hideForm();
		},
		onFieldEnterPress(event)
		{
			if (event.target === this.$refs.emailInput)
			{
				this.showFullForm();
				this.$refs.phoneInput.focus();
			}
			else if (event.target === this.$refs.phoneInput)
			{
				this.$refs.nameInput.focus();
			}
			else
			{
				this.sendForm();
			}

			event.preventDefault();
		},
	},
	watch:
	{
		fieldName()
		{
			clearTimeout(this.fieldNameTimeout);
			this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
		},
		fieldEmail()
		{
			clearTimeout(this.showFormTimeout);
			this.showFormTimeout = setTimeout(this.showFullForm, 1000);

			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_OFFLINE_TITLE}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
					   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
					</div>
					<div :class="['bx-livechat-form-short', {
						'bx-livechat-form-full': isFullForm,
					}]">
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="phone">
						   <div class="ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_PHONE" v-model="fieldPhone" ref="phoneInput" @blur="checkPhoneField" @keydown.enter="onFieldEnterPress">
						</div>
						<div class="bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg" ref="name">
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_NAME" v-model="fieldName" ref="nameInput" @blur="checkNameField" @keydown.enter="onFieldEnterPress"  @keydown.tab="onFieldEnterPress">
						</div>
						<div class="bx-livechat-btn-box">
							<button class="bx-livechat-btn bx-livechat-btn-success" @click="sendForm">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>
						</div>
					</div>
				</div>
			</div>	
		</transition>	
	`
});

/* region 06-09. bx-livechat-form-history component */
BX.Vue.component('bx-livechat-form-history',
{
	data()
	{
		return {
			fieldEmail: '',
		}
	},
	watch:
	{
		fieldEmail(value)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
	},
	computed:
	{
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			userData: state => state.widget.userData,
		})
	},
	created()
	{
		this.fieldEmail = '' + this.userData.email;
	},
	methods:
	{
		formShowed()
		{
			if (!BX.Messenger.Utils.platform.isMobile())
			{
				this.$refs.emailInput.focus();
			}
		},
		sendForm()
		{
			let email = this.checkEmailField()? this.fieldEmail: '';
			if (email)
			{
				this.$root.$bitrixController.sendForm(FormType.history, {email});
			}

			this.hideForm();
		},
		hideForm(event)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.$parent.hideForm();
		},
		onFieldEnterPress(event)
		{
			this.sendForm();
			event.preventDefault();
		},
		checkEmailField()
		{
			if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/))
			{
				if (this.$refs.email)
				{
					this.$refs.email.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.emailInput)
				{
					if (this.$refs.email)
					{
						this.$refs.email.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_MAIL_TITLE_NEW}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
					   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
					</div>
					<div class="bx-livechat-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-success" @click="sendForm">{{localize.BX_LIVECHAT_MAIL_BUTTON_NEW}}</button>
					</div>
				</div>
			</div>	
		</transition>	
	`
});
/* endregion 06-09. bx-livechat-form component */

/* region 06-10. bx-livechat-form-vote component */
BX.Vue.component('bx-livechat-form-vote',
{
	computed:
	{
		VoteType: () => VoteType,

		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData,
		})
	},
	methods: {
		userVote(vote)
		{
			this.$store.commit(WidgetStore.widgetData, {showForm: FormType.none});
			this.$store.commit(WidgetStore.dialogData, {userVote: vote});

			this.$root.$bitrixController.sendDialogVote(vote);
		},
		hideForm(event)
		{
			this.$parent.hideForm();
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-livechat-alert-box bx-livechat-form-rate-show" key="vote">
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-rate-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-mdl">{{widgetData.vote.messageText}}</h4>
					<div class="bx-livechat-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-like" @click="userVote(VoteType.like)" :title="widgetData.vote.messageLike"></button>
						<button class="bx-livechat-btn bx-livechat-btn-dislike" @click="userVote(VoteType.dislike)" :title="widgetData.vote.messageDislike"></button>
					</div>
				</div>
			</div>
		</transition>	
	`
});
/* endregion 06-10. bx-livechat-form-vote component */

/* region 06-11. bx-livechat-form-consent component */
BX.Vue.component('bx-livechat-form-consent',
{
	/**
	 * @emits 'agree' {event: object} -- 'event' - click event
	 * @emits 'disagree' {event: object} -- 'event' - click event
	 */
	computed:
	{
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData,
		})
	},
	methods:
	{
		agree(event)
		{
			this.$emit('agree', {event});
		},
		disagree(event)
		{
			this.$emit('disagree', {event});
		},
		onShow(element, done)
		{
			element.classList.add('bx-livechat-consent-window-show');
			done();
		},
		onHide(element, done)
		{
			element.classList.remove('bx-livechat-consent-window-show');
			element.classList.add('bx-livechat-consent-window-close');
			setTimeout(function() {
				done();
			}, 400);
		},
		onKeyDown(event)
		{
			if (event.keyCode == 9)
			{
				if (event.target === this.$refs.iframe)
				{
					if (event.shiftKey)
					{
						this.$refs.cancel.focus();
					}
					else
					{
						this.$refs.success.focus();
					}
				}
				else if (event.target === this.$refs.success)
				{
					if (event.shiftKey)
					{
						this.$refs.iframe.focus();
					}
					else
					{
						this.$refs.cancel.focus();
					}
				}
				else if (event.target === this.$refs.cancel)
				{
					if (event.shiftKey)
					{
						this.$refs.success.focus();
					}
					else
					{
						this.$refs.iframe.focus();
					}
				}
				event.preventDefault();
			}
			else if (event.keyCode == 39 || event.keyCode == 37)
			{
				if (event.target.nextElementSibling)
				{
					event.target.nextElementSibling.focus();
				}
				else if (event.target.previousElementSibling)
				{
					event.target.previousElementSibling.focus();
				}
				event.preventDefault();
			}
		},
	},
	directives:
	{
		focus:
		{
			inserted(element, params)
			{
				element.focus();
			}
		}
	},
	template: `
		<transition @enter="onShow" @leave="onHide">
			<template v-if="widgetData.showConsent && widgetData.consentUrl">
				<div class="bx-livechat-consent-window">
					<div class="bx-livechat-consent-window-title">{{localize.BX_LIVECHAT_CONSENT_TITLE}}</div>
					<div class="bx-livechat-consent-window-content">
						<iframe class="bx-livechat-consent-window-content-iframe" ref="iframe" frameborder="0" marginheight="0"  marginwidth="0" allowtransparency="allow-same-origin" seamless="true" :src="widgetData.consentUrl" @keydown="onKeyDown"></iframe>
					</div>								
					<div class="bx-livechat-consent-window-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-success" ref="success" @click="agree" @keydown="onKeyDown" v-focus>{{localize.BX_LIVECHAT_CONSENT_AGREE}}</button>
						<button class="bx-livechat-btn bx-livechat-btn-cancel" ref="cancel" @click="disagree" @keydown="onKeyDown">{{localize.BX_LIVECHAT_CONSENT_DISAGREE}}</button>
					</div>
				</div>
			</template>
		</transition>
	`
});
/* endregion 06-11. bx-livechat-form-consent component */

/* region 06-12. bx-livechat-footer component */
BX.Vue.component('bx-livechat-footer',
{
	computed:
	{
		localize()
		{
			return BX.Vue.getFilteredPhrases('BX_LIVECHAT_COPYRIGHT_', this.$root.$bitrixMessages);
		},
		...BX.Vuex.mapState({
			widgetData: state => state.widget.widgetData
		})
	},
	template: `
		<div class="bx-livechat-copyright">	
			<template v-if="widgetData.copyrightUrl">
				<a :href="widgetData.copyrightUrl" target="_blank">
					<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
					<span class="bx-livechat-logo-icon"></span>
				</a>
			</template>
			<template v-else>
				<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
				<span class="bx-livechat-logo-icon"></span>
			</template>
		</div>
	`
});
/* endregion 06-12. bx-livechat-footer component */

/* endregion 06. Vue Components */

/* region 07. Initialize */
if (!window.BX)
{
	window.BX = {};
}
if (!window.BX.LiveChatWidget)
{
	BX.LiveChatWidget = LiveChatWidget;
	BX.LiveChatWidget.VoteType = VoteType;
	BX.LiveChatWidget.SubscriptionType = SubscriptionType;
	BX.LiveChatWidget.LocationStyle = LocationStyle;
	BX.LiveChatWidget.WidgetStore = WidgetStore;
}

window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {detail: {}}));
/* endregion 07. Initialize */