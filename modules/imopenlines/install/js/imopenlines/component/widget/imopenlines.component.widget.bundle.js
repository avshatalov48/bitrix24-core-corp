(function (exports) {
	'use strict';

	/* region 00. Startup operations */

	var GetObjectValues = function GetObjectValues(source) {
	  var destination = [];

	  for (var value in source) {
	    if (source.hasOwnProperty(value)) {
	      destination.push(source[value]);
	    }
	  }

	  return destination;
	};
	/* endregion 00. Startup operations */

	/* region 01. Constants */


	var _VoteType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  dislike: 'dislike'
	});

	var DeviceOrientation = Object.freeze({
	  horizontal: 'horizontal',
	  portrait: 'portrait'
	});

	var _DeviceType = Object.freeze({
	  mobile: 'mobile',
	  desktop: 'desktop'
	});

	var LanguageType = Object.freeze({
	  russian: 'ru',
	  ukraine: 'ua',
	  world: 'en'
	});

	var _FormType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  smile: 'smile',
	  consent: 'consent',
	  welcome: 'welcome',
	  offline: 'offline',
	  history: 'history'
	});

	var LocationType = Object.freeze({
	  topLeft: 1,
	  topMiddle: 2,
	  topBottom: 3,
	  bottomLeft: 6,
	  bottomMiddle: 5,
	  bottomRight: 4
	});
	var LocationStyle = Object.freeze({
	  1: 'top-left',
	  2: 'top-center',
	  3: 'top-right',
	  6: 'bottom-left',
	  5: 'bottom-center',
	  4: 'bottom-right'
	});
	var SubscriptionType = Object.freeze({
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
	  every: 'every'
	});
	var SubscriptionTypeCheck = GetObjectValues(SubscriptionType);
	var WidgetStore = Object.freeze({
	  dialogData: 'widget/dialogData',
	  widgetData: 'widget/widgetData',
	  userData: 'widget/userData'
	});
	var MessengerStore = Object.freeze({
	  messages: BX.Messenger.Model.Messages.getName(),
	  dialogues: BX.Messenger.Model.Dialogues.getName(),
	  users: BX.Messenger.Model.Users.getName(),
	  files: BX.Messenger.Model.Files.getName()
	});
	var MessengerMessageStore = Object.freeze({
	  initCollection: MessengerStore.messages + '/initCollection',
	  add: MessengerStore.messages + '/add',
	  set: MessengerStore.messages + '/set',
	  setBefore: MessengerStore.messages + '/setBefore',
	  update: MessengerStore.messages + '/update',
	  delete: MessengerStore.messages + '/delete',
	  actionStart: MessengerStore.messages + '/actionStart',
	  actionFinish: MessengerStore.messages + '/actionFinish',
	  actionError: MessengerStore.messages + '/actionError',
	  readMessages: MessengerStore.messages + '/readMessages'
	});
	var MessengerMessageGetters = Object.freeze({
	  getLastId: MessengerStore.messages + '/getLastId'
	});
	var MessengerUserStore = Object.freeze({
	  set: MessengerStore.users + '/set',
	  update: MessengerStore.users + '/update',
	  delete: MessengerStore.users + '/delete'
	});
	var MessengerFileStore = Object.freeze({
	  initCollection: MessengerStore.files + '/initCollection',
	  set: MessengerStore.files + '/set',
	  setBefore: MessengerStore.files + '/setBefore',
	  update: MessengerStore.files + '/update',
	  delete: MessengerStore.dialogues + '/delete'
	});
	var MessengerDialogStore = Object.freeze({
	  initCollection: MessengerStore.dialogues + '/initCollection',
	  set: MessengerStore.dialogues + '/set',
	  update: MessengerStore.dialogues + '/update',
	  updateWriting: MessengerStore.dialogues + '/updateWriting',
	  decreaseCounter: MessengerStore.dialogues + '/decreaseCounter',
	  increaseCounter: MessengerStore.dialogues + '/increaseCounter',
	  delete: MessengerStore.dialogues + '/delete'
	});
	var RestMethod = Object.freeze({
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
	  imMessageUpdate: 'im.message.update',
	  // TODO: method is not implemented
	  imMessageDelete: 'im.message.delete',
	  // TODO: method is  not implemented
	  imMessageLike: 'im.message.like',
	  // TODO: method is  not implemented
	  imChatGet: 'im.chat.get',
	  imChatSendTyping: 'im.chat.sendTyping',
	  imDialogMessagesGet: 'im.dialog.messages.get',
	  imDialogMessagesUnread: 'im.dialog.messages.unread',
	  imDialogRead: 'im.dialog.read',
	  diskFolderGet: 'im.disk.folder.get',
	  diskFileUpload: 'disk.folder.uploadfile',
	  diskFileCommit: 'im.disk.file.commit'
	});
	var RestMethodCheck = GetObjectValues(RestMethod);
	var RestAuth = Object.freeze({
	  guest: 'guest'
	});
	/* endregion 01. Constants */

	/* region 02. Widget public interface */

	var LiveChatWidget =
	/*#__PURE__*/
	function () {
	  function LiveChatWidget(config) {
	    babelHelpers.classCallCheck(this, LiveChatWidget);
	    this.developerInfo = 'Do not use private methods.';
	    this.__privateMethods__ = new LiveChatWidgetPrivate(config);
	    this.createLegacyMethods();
	  }

	  babelHelpers.createClass(LiveChatWidget, [{
	    key: "open",
	    value: function open(params) {
	      return this.__privateMethods__.open(params);
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      return this.__privateMethods__.close();
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(params) {
	      return this.__privateMethods__.showNotification(params);
	    }
	  }, {
	    key: "getUserData",
	    value: function getUserData() {
	      return this.__privateMethods__.getUserData();
	    }
	  }, {
	    key: "setUserRegisterData",
	    value: function setUserRegisterData(params) {
	      return this.__privateMethods__.setUserRegisterData(params);
	    }
	  }, {
	    key: "setCustomData",
	    value: function setCustomData(params) {
	      return this.__privateMethods__.setCustomData(params);
	    }
	  }, {
	    key: "mutateTemplateComponent",
	    value: function mutateTemplateComponent(id, params) {
	      return this.__privateMethods__.mutateTemplateComponent(id, params);
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      return this.__privateMethods__.addLocalize(phrases);
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {Function|Boolean} - Unsubscribe callback function or False
	     */

	  }, {
	    key: "subscribe",
	    value: function subscribe(params) {
	      return this.__privateMethods__.subscribe(params);
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      return this.__privateMethods__.start();
	    }
	  }, {
	    key: "createLegacyMethods",
	    value: function createLegacyMethods() {
	      var _this = this;

	      if (typeof window.BX.LiveChat === 'undefined') {
	        var sourceHref = document.createElement('a');
	        sourceHref.href = this.__privateMethods__.host;
	        var sourceDomain = sourceHref.protocol + '//' + sourceHref.hostname + (sourceHref.port && sourceHref.port != '80' && sourceHref.port != '443' ? ":" + sourceHref.port : "");
	        window.BX.LiveChat = {
	          openLiveChat: function openLiveChat() {
	            _this.open({
	              openFromButton: true
	            });
	          },
	          closeLiveChat: function closeLiveChat() {
	            _this.close();
	          },
	          addEventListener: function addEventListener(el, eventName, handler) {
	            if (eventName === 'message') {
	              _this.subscribe({
	                type: SubscriptionType.userMessage,
	                callback: function callback(event) {
	                  handler({
	                    origin: sourceDomain,
	                    data: JSON.stringify({
	                      action: 'sendMessage'
	                    }),
	                    event: event
	                  });
	                }
	              });
	            } else {
	              console.warn('Method BX.LiveChat.addEventListener is not supported, user new format for subscribe.');
	            }
	          },
	          setCookie: function setCookie() {},
	          getCookie: function getCookie() {},
	          sourceDomain: sourceDomain
	        };
	      }

	      if (typeof window.BxLiveChatInit === 'function') {
	        var config = window.BxLiveChatInit();

	        if (config.user) {
	          this.__privateMethods__.setUserRegisterData(config.user);
	        }

	        if (config.firstMessage) {
	          this.__privateMethods__.setCustomData(config.firstMessage);
	        }
	      }

	      if (window.BxLiveChatLoader instanceof Array) {
	        window.BxLiveChatLoader.forEach(function (callback) {
	          return callback();
	        });
	      }

	      return true;
	    }
	  }]);
	  return LiveChatWidget;
	}();
	/* endregion 02. Widget public interface */

	/* region 03. Widget private interface */


	var LiveChatWidgetPrivate =
	/*#__PURE__*/
	function () {
	  /* region 03-01. Initialize and store data */
	  function LiveChatWidgetPrivate() {
	    var _modules,
	        _this2 = this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, LiveChatWidgetPrivate);
	    this.ready = true;
	    this.widgetDataRequested = false;
	    this.offline = false;
	    this.code = params.code || '';
	    this.host = params.host || '';
	    this.language = params.language || 'en';
	    this.copyright = params.copyright !== false;
	    this.copyrightUrl = this.copyright && params.copyrightUrl ? params.copyrightUrl : '';
	    this.buttonInstance = babelHelpers.typeof(params.buttonInstance) === 'object' && params.buttonInstance !== null ? params.buttonInstance : null;
	    this.pageMode = babelHelpers.typeof(params.pageMode) === 'object' && params.pageMode;

	    if (this.pageMode) {
	      this.pageMode.useBitrixLocalize = params.pageMode.useBitrixLocalize === true;
	      this.pageMode.placeholder = document.getElementById(params.pageMode.placeholder);
	    }

	    if (typeof this.code === 'string') {
	      if (this.code.length <= 0) {
	        console.warn("%cLiveChatWidget.constructor: code is not correct (%c".concat(this.code, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        this.ready = false;
	      }
	    }

	    if (typeof this.host === 'string') {
	      if (this.host.length <= 0 || !this.host.startsWith('http')) {
	        console.warn("%cLiveChatWidget.constructor: host is not correct (%c".concat(this.host, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        this.ready = false;
	      }
	    }

	    this.inited = false;
	    this.initEventFired = false;
	    this.restClient = null;
	    this.pullClient = null;
	    this.userRegisterData = {};
	    this.customData = [];
	    this.localize = this.pageMode && this.pageMode.useBitrixLocalize ? BX.message : {};
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
	    var widgetData = {};

	    if (params.location && typeof LocationStyle[params.location] !== 'undefined') {
	      widgetData.location = params.location;
	    }

	    if (BX.Messenger.Utils.types.isPlainObject(params.styles) && (params.styles.backgroundColor || params.styles.iconColor)) {
	      if (typeof widgetData.styles === 'undefined') {
	        widgetData.styles = {};
	      }

	      if (params.styles.backgroundColor) {
	        widgetData.styles.backgroundColor = params.styles.backgroundColor;
	      }

	      if (params.styles.iconColor) {
	        widgetData.styles.iconColor = params.styles.iconColor;
	      }
	    }
	    /* store data */


	    this.store = new BX.Vuex.store({
	      name: 'Bitrix LiveChat Widget (' + this.code + ' / ' + this.host + ')',
	      modules: (_modules = {
	        widget: this.getWidgetStore({
	          widgetData: widgetData
	        })
	      }, babelHelpers.defineProperty(_modules, MessengerStore.messages, BX.Messenger.Model.Messages.getInstance().getStore()), babelHelpers.defineProperty(_modules, MessengerStore.dialogues, BX.Messenger.Model.Dialogues.getInstance().getStore({
	        host: this.host
	      })), babelHelpers.defineProperty(_modules, MessengerStore.users, BX.Messenger.Model.Users.getInstance().getStore({
	        host: this.host
	      })), babelHelpers.defineProperty(_modules, MessengerStore.files, BX.Messenger.Model.Files.getInstance().getStore({
	        host: this.host
	      })), _modules)
	    });

	    if (this.pageMode && this.pageMode.placeholder) {
	      this.rootNode = this.pageMode.placeholder;
	    } else {
	      this.rootNode = document.createElement('div');

	      if (document.body.firstChild) {
	        document.body.insertBefore(this.rootNode, document.body.firstChild);
	      } else {
	        document.body.appendChild(this.rootNode);
	      }
	    }

	    this.template = null;
	    window.addEventListener('orientationchange', function () {
	      _this2.store.commit(WidgetStore.widgetData, {
	        deviceOrientation: BX.Messenger.Utils.device.getOrientation()
	      });

	      if (_this2.store.state.widget.widgetData.showed && _this2.store.state.widget.widgetData.deviceType == _DeviceType.mobile && _this2.store.state.widget.widgetData.deviceOrientation == DeviceOrientation.horizontal) {
	        document.activeElement.blur();
	      }
	    });
	    var serverVariables = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);

	    if (serverVariables) {
	      this.addLocalize(serverVariables);
	    }

	    this.initRestClient();
	    window.dispatchEvent(new CustomEvent('onBitrixLiveChat', {
	      detail: {
	        widget: this,
	        widgetCode: this.code,
	        widgetHost: this.host
	      }
	    }));

	    if (this.pageMode) {
	      this.open();
	    }
	  }

	  babelHelpers.createClass(LiveChatWidgetPrivate, [{
	    key: "getWidgetStore",
	    value: function getWidgetStore(params) {
	      /* WIDGET DATA */
	      var widgetDataDefault = {
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
	          maxFileSize: 5242880
	        },
	        styles: {
	          backgroundColor: '#17a3ea',
	          iconColor: '#ffffff'
	        },
	        showForm: _FormType.none,
	        uploadFile: false,
	        deviceType: _DeviceType.desktop,
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
	      var widgetData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.widgetData, widgetDataDefault);
	      widgetData.deviceType = BX.Messenger.Utils.device.isMobile() ? _DeviceType.mobile : _DeviceType.desktop;
	      widgetData.deviceOrientation = BX.Messenger.Utils.device.getOrientation();
	      widgetData.language = this.language;
	      widgetData.copyright = this.copyright;
	      widgetData.copyrightUrl = this.copyrightUrl;
	      widgetData.pageMode = this.pageMode !== false;
	      widgetData.dragged = false;
	      widgetData.showConsent = false;
	      widgetData.showForm = _FormType.none;
	      widgetData.uploadFile = false;
	      widgetData.error = {
	        active: false,
	        code: '',
	        description: ''
	      };
	      widgetData.showed = false;

	      if (BX.Messenger.Utils.types.isPlainObject(params.widgetData)) {
	        if (params.widgetData.location && typeof LocationStyle[params.widgetData.location] !== 'undefined') {
	          widgetData.location = params.widgetData.location;
	        }

	        if (BX.Messenger.Utils.types.isPlainObject(params.widgetData.styles) && (params.widgetData.styles.backgroundColor || params.widgetData.styles.iconColor)) {
	          if (params.widgetData.styles.backgroundColor) {
	            widgetData.styles.backgroundColor = params.widgetData.styles.backgroundColor;
	          }

	          if (params.widgetData.styles.iconColor) {
	            widgetData.styles.iconColor = params.widgetData.styles.iconColor;
	          }
	        }
	      }

	      for (var param in widgetDataDefault) {
	        if (!widgetDataDefault.hasOwnProperty(param)) continue;

	        if (typeof widgetData[param] == 'undefined') {
	          widgetData[param] = widgetDataDefault[param];
	        }
	      }
	      /* DIALOG DATA */


	      var dialogDataDefault = {
	        dialogId: 'chat0',
	        chatId: 0,
	        diskFolderId: 0,
	        sessionId: 0,
	        sessionClose: true,
	        userVote: _VoteType.none,
	        userConsent: false,
	        messageLimit: 0,
	        operator: {
	          name: '',
	          firstName: '',
	          lastName: '',
	          workPosition: '',
	          avatar: '',
	          online: false
	        }
	      };
	      var dialogData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.dialogData, dialogDataDefault);

	      for (var _param in dialogDataDefault) {
	        if (!dialogDataDefault.hasOwnProperty(_param)) continue;

	        if (typeof dialogData[_param] == 'undefined') {
	          dialogData[_param] = dialogDataDefault[_param];
	        }
	      }

	      dialogData.messageLimit = this.defaultMessageLimit;
	      /* USER DATA */

	      var userDataDefault = {
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
	        position: ''
	      };
	      var userData = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, WidgetStore.userData, userDataDefault);

	      if (!userData.hash) {
	        var userHash = this.getUserHashCookie();

	        if (userHash) {
	          userData.hash = userHash;
	        }
	      }

	      for (var _param2 in userDataDefault) {
	        if (!userDataDefault.hasOwnProperty(_param2)) continue;

	        if (typeof userData[_param2] == 'undefined') {
	          userData[_param2] = userDataDefault[_param2];
	        }
	      }
	      /* VUEX INIT DATA */


	      return {
	        namespaced: true,
	        state: {
	          widgetData: widgetData,
	          dialogData: dialogData,
	          userData: userData
	        },
	        mutations: {
	          widgetData: function widgetData(state, params) {
	            if (typeof params.configId === 'number') {
	              state.widgetData.configId = params.configId;
	            }

	            if (typeof params.configName === 'string') {
	              state.widgetData.configName = params.configName;
	            }

	            if (typeof params.language === 'string') {
	              state.widgetData.language = params.language;
	            }

	            if (typeof params.online === 'boolean') {
	              state.widgetData.online = params.online;
	            }

	            if (BX.Messenger.Utils.types.isPlainObject(params.vote)) {
	              if (typeof params.vote.enable === 'boolean') {
	                state.widgetData.vote.enable = params.vote.enable;
	              }

	              if (typeof params.vote.messageText === 'string') {
	                state.widgetData.vote.messageText = params.vote.messageText;
	              }

	              if (typeof params.vote.messageLike === 'string') {
	                state.widgetData.vote.messageLike = params.vote.messageLike;
	              }

	              if (typeof params.vote.messageDislike === 'string') {
	                state.widgetData.vote.messageDislike = params.vote.messageDislike;
	              }
	            }

	            if (typeof params.dragged === 'boolean') {
	              state.widgetData.dragged = params.dragged;
	            }

	            if (typeof params.textareaHeight === 'number') {
	              state.widgetData.textareaHeight = params.textareaHeight;
	            }

	            if (typeof params.showConsent === 'boolean') {
	              state.widgetData.showConsent = params.showConsent;
	            }

	            if (typeof params.consentUrl === 'string') {
	              state.widgetData.consentUrl = params.consentUrl;
	            }

	            if (typeof params.showed === 'boolean') {
	              state.widgetData.showed = params.showed;
	              state.widgetData.reopen = params.showed;
	            }

	            if (typeof params.copyright === 'boolean') {
	              state.widgetData.copyright = params.copyright;
	            }

	            if (typeof params.dialogStart === 'boolean') {
	              state.widgetData.dialogStart = params.dialogStart;
	            }

	            if (BX.Messenger.Utils.types.isPlainObject(params.disk)) {
	              if (typeof params.disk.enabled === 'boolean') {
	                state.widgetData.disk.enabled = params.disk.enabled;
	              }

	              if (typeof params.disk.maxFileSize === 'number') {
	                state.widgetData.disk.maxFileSize = params.disk.maxFileSize;
	              }
	            }

	            if (BX.Messenger.Utils.types.isPlainObject(params.error) && typeof params.error.active === 'boolean') {
	              state.widgetData.error = {
	                active: params.error.active,
	                code: params.error.code || '',
	                description: params.error.description || ''
	              };
	            }

	            if (params.operators instanceof Array) {
	              state.widgetData.operators = params.operators;
	            }

	            if (params.connectors instanceof Array) {
	              state.widgetData.connectors = params.connectors;
	            }

	            if (typeof params.uploadFilePlus !== 'undefined') {
	              state.widgetData.uploadFile = state.widgetData.uploadFile + 1;
	            }

	            if (typeof params.uploadFileMinus !== 'undefined') {
	              state.widgetData.uploadFile = state.widgetData.uploadFile - 1;
	            }

	            if (typeof params.showForm === 'string' && typeof _FormType[params.showForm] !== 'undefined') {
	              state.widgetData.showForm = params.showForm;
	            }

	            if (typeof params.deviceType === 'string' && typeof _DeviceType[params.deviceType] !== 'undefined') {
	              state.widgetData.deviceType = params.deviceType;
	            }

	            if (typeof params.deviceOrientation === 'string' && typeof DeviceOrientation[params.deviceOrientation] !== 'undefined') {
	              state.widgetData.deviceOrientation = params.deviceOrientation;
	            }

	            if (typeof params.location === 'number' && typeof LocationStyle[params.location] !== 'undefined') {
	              state.widgetData.location = params.location;
	            }

	            BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.widgetData, state.widgetData);
	          },
	          dialogData: function dialogData(state, params) {
	            if (typeof params.chatId === 'number') {
	              state.dialogData.chatId = params.chatId;
	              state.dialogData.dialogId = params.chatId ? 'chat' + params.chatId : 0;
	            }

	            if (typeof params.diskFolderId === 'number') {
	              state.dialogData.diskFolderId = params.diskFolderId;
	            }

	            if (typeof params.sessionId === 'number') {
	              state.dialogData.sessionId = params.sessionId;
	            }

	            if (typeof params.messageLimit === 'number') {
	              state.dialogData.messageLimit = params.messageLimit;
	            }

	            if (typeof params.sessionClose === 'boolean') {
	              state.dialogData.sessionClose = params.sessionClose;
	            }

	            if (typeof params.userConsent === 'boolean') {
	              state.dialogData.userConsent = params.userConsent;
	            }

	            if (typeof params.userVote === 'string' && typeof params.userVote !== 'undefined') {
	              state.dialogData.userVote = params.userVote;
	            }

	            if (BX.Messenger.Utils.types.isPlainObject(params.operator)) {
	              if (typeof params.operator.name === 'string' || typeof params.operator.name === 'number') {
	                state.dialogData.operator.name = params.operator.name.toString();
	              }

	              if (typeof params.operator.lastName === 'string' || typeof params.operator.lastName === 'number') {
	                state.dialogData.operator.lastName = params.operator.lastName.toString();
	              }

	              if (typeof params.operator.firstName === 'string' || typeof params.operator.firstName === 'number') {
	                state.dialogData.operator.firstName = params.operator.firstName.toString();
	              }

	              if (typeof params.operator.workPosition === 'string' || typeof params.operator.workPosition === 'number') {
	                state.dialogData.operator.workPosition = params.operator.workPosition.toString();
	              }

	              if (typeof params.operator.avatar === 'string') {
	                if (!params.operator.avatar || params.operator.avatar.startsWith('http')) {
	                  state.dialogData.operator.avatar = params.operator.avatar;
	                } else {
	                  state.dialogData.operator.avatar = state.widgetData.host + params.operator.avatar;
	                }
	              }

	              if (typeof params.operator.online === 'boolean') {
	                state.dialogData.operator.online = params.operator.online;
	              }
	            }

	            BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.dialogData, state.dialogData);
	          },
	          userData: function userData(state, params) {
	            if (typeof params.id === 'number') {
	              state.userData.id = params.id;
	            }

	            if (typeof params.hash === 'string' && params.hash !== state.userData.hash) {
	              state.userData.hash = params.hash;
	              Cookie.set(null, 'LIVECHAT_HASH', params.hash, {
	                expires: 365 * 86400,
	                path: '/'
	              });
	            }

	            if (typeof params.name === 'string' || typeof params.name === 'number') {
	              state.userData.name = params.name.toString();
	            }

	            if (typeof params.firstName === 'string' || typeof params.firstName === 'number') {
	              state.userData.firstName = params.firstName.toString();
	            }

	            if (typeof params.lastName === 'string' || typeof params.lastName === 'number') {
	              state.userData.lastName = params.lastName.toString();
	            }

	            if (typeof params.avatar === 'string') {
	              state.userData.avatar = params.avatar;
	            }

	            if (typeof params.email === 'string') {
	              state.userData.email = params.email;
	            }

	            if (typeof params.phone === 'string' || typeof params.phone === 'number') {
	              state.userData.phone = params.phone.toString();
	            }

	            if (typeof params.www === 'string') {
	              state.userData.www = params.www;
	            }

	            if (typeof params.gender === 'string') {
	              state.userData.gender = params.gender;
	            }

	            if (typeof params.position === 'string') {
	              state.userData.position = params.position;
	            }

	            BX.Messenger.LocalStorage.set(state.widgetData.siteId, 0, WidgetStore.userData, state.userData);
	          }
	        }
	      };
	    }
	  }, {
	    key: "initRestClient",
	    value: function initRestClient() {
	      this.restClient = new LiveChatRestClient({
	        endpoint: this.host + '/rest'
	      });

	      if (this.isUserRegistered()) {
	        this.restClient.setAuthId(this.getUserHash());
	      } else {
	        this.restClient.setAuthId(RestAuth.guest);
	      }
	    }
	  }, {
	    key: "requestWidgetData",
	    value: function requestWidgetData() {
	      var _this3 = this;

	      if (!this.isReady()) {
	        console.error('LiveChatWidget.start: widget code or host is not specified');
	        return false;
	      }

	      this.widgetDataRequested = true;

	      if (!this.isUserRegistered() && this.userRegisterData.hash) {
	        this.requestData();
	        this.inited = true;
	        this.fireInitEvent();
	      } else if (this.isConfigDataLoaded() && this.isUserRegistered()) {
	        this.requestData();
	        this.inited = true;
	        this.fireInitEvent();
	      } else {
	        this.restClient.callMethod(RestMethod.widgetConfigGet, {
	          code: this.code
	        }, function (xhr) {
	          _this3.configRequestXhr = xhr;
	        }).then(function (result) {
	          _this3.configRequestXhr = null;

	          _this3.clearError();

	          _this3.storeDataFromRest(RestMethod.widgetConfigGet, result.data());

	          if (!_this3.inited) {
	            _this3.inited = true;

	            _this3.fireInitEvent();
	          }
	        }).catch(function (result) {
	          _this3.configRequestXhr = null;

	          _this3.setError(result.error().ex.error, result.error().ex.error_description);
	        });

	        if (this.isConfigDataLoaded()) {
	          this.inited = true;
	          this.fireInitEvent();
	        }
	      }

	      this.timer = new BX.Messenger.Timer();
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      var _this4 = this;

	      if (this.requestDataSend) {
	        return true;
	      }

	      this.requestDataSend = true;

	      if (this.configRequestXhr) {
	        this.configRequestXhr.abort();
	      }

	      var query = babelHelpers.defineProperty({}, RestMethod.widgetConfigGet, [RestMethod.widgetConfigGet, {
	        code: this.code
	      }]);

	      if (this.isUserRegistered()) {
	        query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	          config_id: this.getConfigId(),
	          trace_data: this.getCrmTraceData(),
	          custom_data: this.getCustomData()
	        }];
	        query[RestMethod.imChatGet] = [RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetDialogGet + '][dialogId]'
	        }];
	        query[RestMethod.imDialogMessagesGet] = [RestMethod.imDialogMessagesGet, {
	          chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	          limit: this.requestMessageLimit,
	          convert_text: 'Y'
	        }];
	      } else {
	        query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, babelHelpers.objectSpread({
	          config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]'
	        }, this.getUserRegisterFields())];
	        query[RestMethod.imChatGet] = [RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetUserRegister + '][dialogId]'
	        }];

	        if (this.userRegisterData.hash) {
	          query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	            config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]',
	            trace_data: this.getCrmTraceData(),
	            custom_data: this.getCustomData()
	          }];
	          query[RestMethod.imDialogMessagesGet] = [RestMethod.imDialogMessagesGet, {
	            chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	            limit: this.requestMessageLimit,
	            convert_text: 'Y'
	          }];
	        }
	      }

	      query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
	      query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {
	        'CACHE': 'N'
	      }];
	      query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this4.requestDataSend = false;

	          _this4.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var configGet = response[RestMethod.widgetConfigGet];

	        if (configGet && configGet.error()) {
	          _this4.requestDataSend = false;

	          _this4.setError(configGet.error().ex.error, configGet.error().ex.error_description);

	          return false;
	        }

	        _this4.storeDataFromRest(RestMethod.widgetConfigGet, configGet.data());

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this4.requestDataSend = false;

	          _this4.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this4.storeDataFromRest(RestMethod.widgetUserGet, userGetResult.data());

	        var chatGetResult = response[RestMethod.imChatGet];

	        if (chatGetResult.error()) {
	          _this4.requestDataSend = false;

	          _this4.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);

	          return false;
	        }

	        _this4.storeDataFromRest(RestMethod.imChatGet, chatGetResult.data());

	        var dialogGetResult = response[RestMethod.widgetDialogGet];

	        if (dialogGetResult) {
	          if (dialogGetResult.error()) {
	            _this4.requestDataSend = false;

	            _this4.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);

	            return false;
	          }

	          _this4.storeDataFromRest(RestMethod.widgetDialogGet, dialogGetResult.data());
	        }

	        var dialogMessagesGetResult = response[RestMethod.imDialogMessagesGet];

	        if (dialogMessagesGetResult) {
	          if (dialogMessagesGetResult.error()) {
	            _this4.requestDataSend = false;

	            _this4.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);

	            return false;
	          }

	          _this4.storeDataFromRest(RestMethod.imDialogMessagesGet, dialogMessagesGetResult.data());
	        }

	        var userRegisterResult = response[RestMethod.widgetUserRegister];

	        if (userRegisterResult) {
	          if (userRegisterResult.error()) {
	            _this4.requestDataSend = false;

	            _this4.setError(userRegisterResult.error().ex.error, userRegisterResult.error().ex.error_description);

	            return false;
	          }

	          _this4.storeDataFromRest(RestMethod.widgetUserRegister, userRegisterResult.data());
	        }

	        var timeShift = 0;
	        var serverTimeResult = response[RestMethod.pullServerTime];

	        if (serverTimeResult && !serverTimeResult.error()) {
	          timeShift = Math.floor((new Date().getTime() - new Date(serverTimeResult.data()).getTime()) / 1000);
	        }

	        var config = null;
	        var pullConfigResult = response[RestMethod.pullConfigGet];

	        if (pullConfigResult && !pullConfigResult.error()) {
	          config = pullConfigResult.data();
	          config.server.timeShift = timeShift;
	        }

	        _this4.startPullClient(config).then(function () {
	          _this4.processSendMessages();

	          _this4.processSendFiles();
	        }).catch(function (error) {
	          _this4.setError(error.ex.error, error.ex.error_description);
	        });

	        _this4.requestDataSend = false;
	      }, false, false, BX.Messenger.Utils.getLogTrackingParams({
	        name: 'widget.init.config',
	        dialog: this.getDialogData()
	      }));
	    }
	  }, {
	    key: "storeDataFromRest",
	    value: function storeDataFromRest(type, result) {
	      if (!RestMethodCheck.includes(type)) {
	        console.warn("%cLiveChatWidget.storeDataFromRest: config is not set, because you are trying to set as unknown type (%c".concat(type, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (type == RestMethod.widgetConfigGet) {
	        this.store.commit(WidgetStore.widgetData, {
	          configId: result.configId,
	          configName: result.configName,
	          vote: result.vote,
	          operators: result.operators || [],
	          online: result.online,
	          consentUrl: result.consentUrl,
	          connectors: result.connectors || [],
	          disk: result.disk
	        });
	        this.addLocalize(result.serverVariables);
	        BX.Messenger.LocalStorage.set(this.getSiteId(), 0, 'serverVariables', result.serverVariables || {});
	      } else if (type == RestMethod.widgetUserRegister) {
	        this.restClient.setAuthId(result.hash);
	        var previousData = [];

	        if (typeof this.store.state[MessengerStore.messages].collection[this.getChatId()] !== 'undefined') {
	          previousData = this.store.state[MessengerStore.messages].collection[this.getChatId()];
	        }

	        this.store.commit(MessengerMessageStore.initCollection, {
	          chatId: result.chatId,
	          messages: previousData
	        });
	        this.store.commit(MessengerDialogStore.initCollection, {
	          dialogId: result.dialogId,
	          fields: {
	            entityType: 'LIVECHAT',
	            type: 'livechat'
	          }
	        });
	        this.store.commit(WidgetStore.dialogData, {
	          chatId: result.chatId
	        });
	      } else if (type == RestMethod.imChatGet) {
	        this.store.dispatch(MessengerDialogStore.set, result);
	      } else if (type == RestMethod.widgetUserGet) {
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
	          position: result.position
	        });
	      } else if (type == RestMethod.widgetDialogGet) {
	        this.store.commit(MessengerMessageStore.initCollection, {
	          chatId: result.chatId
	        });
	        this.store.commit(WidgetStore.dialogData, {
	          chatId: result.chatId,
	          diskFolderId: result.diskFolderId,
	          sessionId: result.sessionId,
	          sessionClose: result.sessionClose,
	          userVote: result.userVote,
	          userConsent: result.userConsent,
	          operator: result.operator
	        });
	      } else if (type == RestMethod.diskFolderGet) {
	        this.store.commit(WidgetStore.dialogData, {
	          diskFolderGet: result.ID
	        });
	      } else if (type == RestMethod.imDialogMessagesGet) {
	        this.store.dispatch(MessengerMessageStore.setBefore, result.messages);
	        this.store.dispatch(MessengerUserStore.set, result.users);
	        this.store.dispatch(MessengerFileStore.setBefore, this.prepareFileData(result.files));

	        if (result.messages && result.messages.length > 0 && !this.isDialogStart()) {
	          this.store.commit(WidgetStore.widgetData, {
	            dialogStart: true
	          });
	        }
	      } else if (type == RestMethod.imDialogMessagesUnread) {
	        this.store.dispatch(MessengerMessageStore.set, result.messages);
	        this.store.dispatch(MessengerUserStore.set, result.users);
	        this.store.dispatch(MessengerFileStore.set, this.prepareFileData(result.files));
	      }

	      return true;
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var _this5 = this;

	      if (Cookie.get(null, 'BITRIX_LIVECHAT_AUTH')) {
	        return files;
	      }

	      return files.map(function (file) {
	        var hash = md5(_this5.getUserId() + '|' + file.id + '|' + _this5.getUserHash());

	        var urlParam = 'livechat_auth_id=' + hash + '&livechat_user_id=' + _this5.getUserId();

	        if (file.urlPreview) {
	          file.urlPreview = file.urlPreview + '&' + urlParam;
	        }

	        if (file.urlShow) {
	          file.urlShow = file.urlShow + '&' + urlParam;
	        }

	        if (file.urlDownload) {
	          file.urlDownload = file.urlDownload + '&' + urlParam;
	        }

	        return file;
	      });
	    }
	  }, {
	    key: "checkBrowserVersion",
	    value: function checkBrowserVersion() {
	      if (BX.Messenger.Utils.platform.isIos()) {
	        var version = BX.Messenger.Utils.platform.getIosVersion();

	        if (version && version <= 10) {
	          return false;
	        }
	      }

	      return true;
	    }
	    /* endregion 03-01. Initialize and store data */

	    /* region 03-02. Push & Pull */

	  }, {
	    key: "startPullClient",
	    value: function startPullClient(config) {
	      var _this6 = this;

	      var promise = new BX.Promise();

	      if (this.pullClient) {
	        if (!this.pullClient.isConnected()) {
	          this.pullClient.scheduleReconnect();
	        }

	        promise.resolve(true);
	        return promise;
	      }

	      if (!this.getUserId() || !this.getSiteId() || !this.restClient) {
	        promise.reject({
	          ex: {
	            error: 'WIDGET_NOT_LOADED',
	            error_description: 'Widget is not loaded.'
	          }
	        });
	        return promise;
	      }

	      this.pullClient = new BX.PullClient({
	        serverEnabled: true,
	        userId: this.getUserId(),
	        siteId: this.getSiteId(),
	        restClient: this.restClient,
	        configTimestamp: config ? config.server.config_timestamp : 0,
	        skipCheckRevision: true
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
	        callback: function callback(result) {
	          if (result.status == BX.PullClient.PullStatus.Online) {
	            promise.resolve(true);

	            _this6.pullConnectedFirstTime();
	          }
	        }
	      });

	      if (this.template) {
	        this.template.$bitrixPullClient = this.pullClient;
	        this.template.$root.$emit('onBitrixPullClientInited');
	      }

	      this.pullClient.start(config).catch(function () {
	        promise.reject({
	          ex: {
	            error: 'PULL_CONNECTION_ERROR',
	            error_description: 'Pull is not connected.'
	          }
	        });
	      });
	      return promise;
	    }
	  }, {
	    key: "recoverPullConnection",
	    value: function recoverPullConnection() {
	      // this.pullClient.session.mid = 0; // TODO specially for disable pull history, remove after recode im
	      this.pullClient.restart(BX.PullClient.CloseReasons.MANUAL, 'Restart after click by connection status button.');
	    }
	  }, {
	    key: "stopPullClient",
	    value: function stopPullClient() {
	      if (this.pullClient) {
	        this.pullClient.stop(BX.PullClient.CloseReasons.MANUAL, 'Closed manually');
	      }
	    }
	  }, {
	    key: "eventMessengerInteraction",
	    value: function eventMessengerInteraction(data) {
	      var _this7 = this;

	      BX.Messenger.Logger.info('eventMessengerInteraction', data);

	      if (data.command == "messageChat") {
	        if (data.params.chat && data.params.chat[data.params.chatId]) {
	          this.store.dispatch(MessengerDialogStore.update, {
	            dialogId: 'chat' + data.params.chatId,
	            fields: data.params.chat[data.params.chatId]
	          });
	        }

	        if (data.params.users) {
	          this.store.dispatch(MessengerUserStore.set, BX.Messenger.Model.Users.convertToArray(data.params.users));
	        }

	        if (data.params.files) {
	          var dataParamsFiles = BX.Messenger.Model.Files.convertToArray(data.params.files);
	          this.store.dispatch(MessengerFileStore.set, this.prepareFileData(dataParamsFiles));
	        }

	        var collection = this.store.state[MessengerStore.messages].collection[this.getChatId()];

	        if (!collection) {
	          collection = [];
	        }

	        var update = false;

	        if (data.params.message.tempId && collection.length > 0) {
	          for (var index = collection.length - 1; index >= 0; index--) {
	            if (collection[index].id == data.params.message.tempId) {
	              update = true;
	              break;
	            }
	          }
	        }

	        if (update) {
	          this.store.dispatch(MessengerMessageStore.update, {
	            id: data.params.message.tempId,
	            chatId: data.params.message.chatId,
	            fields: data.params.message
	          });
	        } else if (this.isUnreadMessagesLoaded()) {
	          var unreadCountInCollection = 0;

	          if (collection.length > 0) {
	            collection.forEach(function (element) {
	              return element.unread ? unreadCountInCollection++ : 0;
	            });
	          }

	          if (unreadCountInCollection > 0) {
	            this.store.commit(WidgetStore.dialogData, {
	              messageLimit: this.requestMessageLimit + unreadCountInCollection
	            });
	          } else if (this.getMessageLimit() != this.requestMessageLimit) {
	            this.store.commit(WidgetStore.dialogData, {
	              messageLimit: this.requestMessageLimit
	            });
	          }

	          this.store.dispatch(MessengerMessageStore.set, babelHelpers.objectSpread({}, data.params.message, {
	            unread: true
	          }));
	        }

	        this.startWriting({
	          dialogId: 'chat' + data.params.message.chatId,
	          userId: data.params.message.senderId,
	          action: false
	        });

	        if (data.params.message.senderId == this.getUserId()) {
	          this.store.dispatch(MessengerMessageStore.readMessages, {
	            chatId: data.params.message.chatId
	          }).then(function (result) {
	            _this7.store.dispatch(MessengerDialogStore.update, {
	              dialogId: 'chat' + data.params.message.chatId,
	              fields: {
	                counter: 0
	              }
	            });
	          });
	        } else {
	          this.store.dispatch(MessengerDialogStore.increaseCounter, {
	            dialogId: 'chat' + data.params.message.chatId,
	            count: 1
	          });
	          this.sendEvent({
	            type: SubscriptionType.operatorMessage,
	            data: data.params
	          });

	          if (!this.store.state.widget.widgetData.showed && !this.onceShowed) {
	            this.onceShowed = true;
	            this.open();
	          }
	        }
	      } else if (data.command == "messageUpdate" || data.command == "messageDelete") {
	        this.store.dispatch(MessengerMessageStore.update, {
	          id: data.params.id,
	          chatId: data.params.chatId,
	          fields: {
	            text: data.command == "messageUpdate" ? data.params.text : '',
	            textOriginal: data.command == "messageUpdate" ? data.params.textOriginal : '',
	            params: data.params.params,
	            blink: true
	          }
	        });
	        this.startWriting({
	          dialogId: data.params.dialogId,
	          userId: data.params.senderId,
	          action: false
	        });
	      } else if (data.command == "messageDeleteComplete") {
	        this.store.dispatch(MessengerMessageStore.delete, {
	          id: data.params.id,
	          chatId: data.params.chatId
	        });
	        this.startWriting({
	          dialogId: data.params.dialogId,
	          userId: data.params.senderId,
	          action: false
	        });
	      } else if (data.command == "messageParamsUpdate") {
	        this.store.dispatch(MessengerMessageStore.update, {
	          id: data.params.id,
	          chatId: data.params.chatId,
	          fields: {
	            params: data.params.params
	          }
	        });
	      } else if (data.command == "startWriting") {
	        this.startWriting(data.params);
	      } else if (data.command == "readMessageChat") {
	        this.store.dispatch(MessengerMessageStore.readMessages, {
	          chatId: data.params.chatId,
	          readId: data.params.lastId
	        }).then(function (result) {
	          _this7.store.dispatch(MessengerDialogStore.update, {
	            dialogId: 'chat' + data.params.chatId,
	            fields: {
	              counter: data.params.counter
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "eventLinesInteraction",
	    value: function eventLinesInteraction(data) {
	      BX.Messenger.Logger.info('eventLinesInteraction', data);

	      if (data.command == "sessionStart") {
	        this.store.commit(WidgetStore.dialogData, {
	          sessionId: data.params.sessionId,
	          sessionClose: false,
	          userVote: _VoteType.none
	        });
	        this.sendEvent({
	          type: SubscriptionType.sessionStart,
	          data: {
	            sessionId: data.params.sessionId
	          }
	        });
	      } else if (data.command == "sessionOperatorChange") {
	        this.store.commit(WidgetStore.dialogData, {
	          operator: data.params.operator
	        });
	        this.sendEvent({
	          type: SubscriptionType.sessionOperatorChange,
	          data: {
	            operator: data.params.operator
	          }
	        });
	      } else if (data.command == "sessionFinish") {
	        this.store.commit(WidgetStore.dialogData, {
	          sessionId: data.params.sessionId,
	          sessionClose: true
	        });
	        this.sendEvent({
	          type: SubscriptionType.sessionFinish,
	          data: {
	            sessionId: data.params.sessionId
	          }
	        });

	        if (!data.params.spam) {
	          this.store.commit(WidgetStore.dialogData, {
	            operator: {
	              name: '',
	              firstName: '',
	              lastName: '',
	              workPosition: '',
	              avatar: '',
	              online: false
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "eventStatusInteraction",
	    value: function eventStatusInteraction(data) {
	      var _this8 = this;

	      if (data.status === BX.PullClient.PullStatus.Online) {
	        this.offline = false;

	        if (this.pullRequestMessage) {
	          this.getDialogUnread().then(function () {
	            _this8.readMessage();

	            _this8.processSendMessages();

	            _this8.processSendFiles();
	          });
	          this.pullRequestMessage = false;
	        } else {
	          this.readMessage();
	          this.processSendMessages();
	          this.processSendFiles();
	        }
	      } else if (data.status === BX.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	        this.offline = true;
	      }
	    }
	    /* endregion 03-02. Push & Pull */

	    /* region 03-03. Template engine */

	  }, {
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      if (this.template) {
	        this.store.commit(WidgetStore.widgetData, {
	          showed: true
	        });
	        return true;
	      }

	      this.rootNode.innerHTML = '';
	      this.rootNode.appendChild(document.createElement('div'));
	      var widgetContext = this;
	      var restClient = this.restClient;
	      var pullClient = this.pullClient;
	      this.template = BX.Vue.create({
	        el: this.rootNode.firstChild,
	        store: this.store,
	        template: '<bx-livechat/>',
	        beforeCreate: function beforeCreate() {
	          this.$bitrixController = widgetContext;
	          this.$bitrixRestClient = restClient;
	          this.$bitrixPullClient = pullClient;
	          this.$bitrixMessages = widgetContext.localize;
	          widgetContext.sendEvent({
	            type: SubscriptionType.widgetOpen,
	            data: {}
	          });
	        },
	        destroyed: function destroyed() {
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
	  }, {
	    key: "detachTemplate",
	    value: function detachTemplate() {
	      if (!this.template) {
	        return true;
	      }

	      this.template.$destroy();
	      return true;
	    }
	  }, {
	    key: "mutateTemplateComponent",
	    value: function mutateTemplateComponent(id, params) {
	      return BX.Vue.mutateComponent(id, params);
	    }
	    /* endregion 03-03. Template engine */

	    /* region 03-04. Rest methods */

	  }, {
	    key: "addMessage",
	    value: function addMessage() {
	      var _this9 = this;

	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';

	      if (!text) {
	        return false;
	      }

	      BX.Messenger.Logger.warn('addMessage', text);

	      if (!this.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: 0,
	          text: text
	        });
	        this.processSendMessages();
	        return true;
	      }

	      this.store.dispatch(MessengerMessageStore.add, {
	        chatId: this.getChatId(),
	        authorId: this.getUserId(),
	        text: text
	      }).then(function (messageId) {
	        if (!_this9.isDialogStart()) {
	          _this9.store.commit(WidgetStore.widgetData, {
	            dialogStart: true
	          });
	        }

	        _this9.messagesQueue.push({
	          id: messageId,
	          text: text,
	          sending: false
	        });

	        if (_this9.getChatId()) {
	          _this9.processSendMessages();
	        } else {
	          _this9.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "addFile",
	    value: function addFile(fileInput) {
	      if (!fileInput) {
	        return false;
	      }

	      BX.Messenger.Logger.warn('addFile', fileInput.files[0].name, fileInput.files[0].size);

	      if (!this.isDialogStart()) {
	        this.store.commit(WidgetStore.widgetData, {
	          dialogStart: true
	        });
	      }

	      this.filesQueue.push({
	        id: this.filesQueueIndex,
	        fileInput: fileInput
	      });
	      this.filesQueueIndex++;

	      if (this.getChatId()) {
	        this.processSendFiles();
	      } else {
	        this.requestData();
	      }

	      return true;
	    }
	  }, {
	    key: "writesMessage",
	    value: function writesMessage() {
	      var _this10 = this;

	      if (!this.getChatId() || this.timer.has('writes')) {
	        return;
	      }

	      this.timer.start('writes', null, 28);
	      this.timer.start('writesSend', null, 5, function (id) {
	        _this10.restClient.callMethod(RestMethod.imChatSendTyping, {
	          'CHAT_ID': _this10.getChatId()
	        }).catch(function () {
	          _this10.timer.stop('writes', _this10.getChatId());
	        });
	      });
	    }
	  }, {
	    key: "stopWritesMessage",
	    value: function stopWritesMessage() {
	      this.timer.stop('writes');
	      this.timer.stop('writesSend');
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this11 = this;

	      if (this.offline) {
	        return false;
	      }

	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        _this11.sendMessage(element);
	      });
	      return true;
	    }
	  }, {
	    key: "processSendFiles",
	    value: function processSendFiles() {
	      var _this12 = this;

	      if (this.offline) {
	        return false;
	      }

	      this.filesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        _this12.sendFile(element);
	      });
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      var _this13 = this;

	      this.stopWritesMessage();
	      this.restClient.callMethod(RestMethod.imMessageAdd, {
	        'TEMP_ID': message.id,
	        'CHAT_ID': this.getChatId(),
	        'MESSAGE': message.text
	      }, null, null, BX.Messenger.Utils.getLogTrackingParams({
	        name: RestMethod.imMessageAdd,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.getDialogData()
	      })).then(function (response) {
	        var messageId = response.data();

	        if (typeof messageId === "number") {
	          _this13.store.dispatch(MessengerMessageStore.update, {
	            id: message.id,
	            chatId: _this13.getChatId(),
	            fields: {
	              id: messageId,
	              sending: false,
	              error: false
	            }
	          });

	          _this13.store.dispatch(MessengerMessageStore.actionFinish, {
	            id: messageId,
	            chatId: _this13.getChatId()
	          });
	        } else {
	          _this13.store.dispatch(MessengerMessageStore.actionError, {
	            id: message.id,
	            chatId: _this13.getChatId()
	          });
	        }

	        _this13.messagesQueue = _this13.messagesQueue.filter(function (el) {
	          return el.id != message.id;
	        });

	        _this13.sendEvent({
	          type: SubscriptionType.userMessage,
	          data: {
	            id: messageId,
	            text: message.text
	          }
	        });
	      }).catch(function (error) {
	        _this13.store.dispatch(MessengerMessageStore.actionError, {
	          id: message.id,
	          chatId: _this13.getChatId()
	        });

	        _this13.messagesQueue = _this13.messagesQueue.filter(function (el) {
	          return el.id != message.id;
	        });
	      });
	    }
	  }, {
	    key: "sendFile",
	    value: function sendFile(file) {
	      var _this14 = this;

	      var fileName = file.fileInput.files[0].name;
	      var fileType = 'file'; // TODO set type by fileInput type

	      var diskFolderId = this.getDiskFolderId();
	      var query = {};

	      if (diskFolderId) {
	        query[RestMethod.diskFileUpload] = [RestMethod.diskFileUpload, {
	          id: diskFolderId,
	          data: {
	            NAME: fileName
	          },
	          fileContent: file.fileInput,
	          generateUniqueName: true
	        }];
	      } else {
	        query[RestMethod.diskFolderGet] = [RestMethod.diskFolderGet, {
	          chat_id: this.getChatId()
	        }];
	        query[RestMethod.diskFileUpload] = [RestMethod.diskFileUpload, {
	          id: '$result[' + RestMethod.diskFolderGet + '][ID]',
	          data: {
	            NAME: fileName
	          },
	          fileContent: file.fileInput,
	          generateUniqueName: true
	        }];
	      }

	      query[RestMethod.diskFileCommit] = [RestMethod.diskFileCommit, {
	        chat_id: this.getChatId(),
	        upload_id: '$result[' + RestMethod.diskFileUpload + '][ID]'
	      }];
	      this.store.commit(WidgetStore.widgetData, {
	        uploadFilePlus: true
	      }); // TODO remove this after create new load workflow

	      this.restClient.callBatch(query, function (response) {
	        _this14.store.commit(WidgetStore.widgetData, {
	          uploadFileMinus: true
	        }); // TODO  remove this after create new load workflow


	        if (!response) {
	          _this14.requestDataSend = false;

	          _this14.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        if (!diskFolderId) {
	          var diskFolderGet = response[RestMethod.diskFolderGet];

	          if (diskFolderGet && diskFolderGet.error()) {
	            console.warn(diskFolderGet.error().ex.error, diskFolderGet.error().ex.error_description);
	            return false;
	          }

	          _this14.storeDataFromRest(RestMethod.diskFolderGet, diskFolderGet.data());
	        }

	        var diskFileUpload = response[RestMethod.diskFileUpload];

	        if (diskFileUpload && diskFileUpload.error()) {
	          console.warn(diskFileUpload.error().ex.error, diskFileUpload.error().ex.error_description);
	          return false;
	        } else {
	          BX.Messenger.Logger.log('upload success', diskFileUpload.data());
	        }

	        var diskFileCommit = response[RestMethod.diskFileCommit];

	        if (diskFileCommit && diskFileCommit.error()) {
	          console.warn(diskFileCommit.error().ex.error, diskFileCommit.error().ex.error_description);
	          return false;
	        } else {
	          BX.Messenger.Logger.log('commit success', diskFileCommit.data());
	        }
	      }, false, false, BX.Messenger.Utils.getLogTrackingParams({
	        name: RestMethod.diskFileCommit,
	        data: {
	          timMessageType: fileType
	        },
	        dialog: this.getDialogData()
	      }));
	      this.filesQueue = this.filesQueue.filter(function (el) {
	        return el.id != file.id;
	      });
	    }
	  }, {
	    key: "sendDialogVote",
	    value: function sendDialogVote(result) {
	      var _this15 = this;

	      if (!this.getSessionId()) {
	        return false;
	      }

	      this.restClient.callMethod(RestMethod.widgetVoteSend, {
	        'SESSION_ID': this.getSessionId(),
	        'ACTION': result
	      }).catch(function (result) {
	        _this15.store.commit(WidgetStore.dialogData, {
	          userVote: _VoteType.none
	        });
	      });
	      this.sendEvent({
	        type: SubscriptionType.userVote,
	        data: {
	          vote: result
	        }
	      });
	    }
	  }, {
	    key: "sendForm",
	    value: function sendForm(type, fields) {
	      var _query2,
	          _this16 = this;

	      BX.Messenger.Logger.info('LiveChatWidgetPrivate.sendForm:', type, fields);
	      var query = (_query2 = {}, babelHelpers.defineProperty(_query2, RestMethod.widgetFormSend, [RestMethod.widgetFormSend, {
	        'CHAT_ID': this.getChatId(),
	        'FORM': type.toUpperCase(),
	        'FIELDS': fields
	      }]), babelHelpers.defineProperty(_query2, RestMethod.widgetUserGet, [RestMethod.widgetUserGet, {}]), _query2);
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this16.requestDataSend = false;

	          _this16.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this16.requestDataSend = false;

	          _this16.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this16.storeDataFromRest(RestMethod.widgetUserGet, userGetResult.data());

	        _this16.sendEvent({
	          type: SubscriptionType.userForm,
	          data: {
	            form: type,
	            fields: fields
	          }
	        });
	      }, false, false, BX.Messenger.Utils.getLogTrackingParams({
	        name: RestMethod.widgetUserGet,
	        dialog: this.getDialogData()
	      }));
	    }
	  }, {
	    key: "sendConsentDecision",
	    value: function sendConsentDecision(result) {
	      result = result === true;
	      this.store.commit(WidgetStore.dialogData, {
	        userConsent: result
	      });

	      if (result && this.isUserRegistered()) {
	        this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
	          config_id: this.getConfigId(),
	          consent_url: location.href
	        });
	      }
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this17 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.requestMessageLimit;
	      this.restClient.callMethod(RestMethod.imDialogMessagesGet, {
	        'CHAT_ID': this.getChatId(),
	        'LAST_ID': lastId,
	        'LIMIT': limit,
	        'CONVERT_TEXT': 'Y'
	      }).then(function (result) {
	        var requestResult = result.data();

	        _this17.storeDataFromRest(RestMethod.imDialogMessagesGet, requestResult);

	        _this17.template.$emit('onDialogRequestHistoryResult', {
	          count: requestResult.messages.length
	        });
	      }).catch(function (result) {
	        _this17.template.$emit('onDialogRequestHistoryResult', {
	          error: result.error().ex
	        });
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _query3,
	          _this18 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.requestMessageLimit;
	      var promise = new BX.Promise();

	      if (!lastId) {
	        lastId = this.store.getters[MessengerMessageGetters.getLastId](this.getChatId());
	      }

	      if (!lastId) {
	        this.template.$emit('onDialogRequestUnreadResult', {
	          error: {
	            error: 'LAST_ID_EMPTY',
	            error_description: 'LastId is empty.'
	          }
	        });
	        promise.reject();
	        return promise;
	      }

	      var query = (_query3 = {}, babelHelpers.defineProperty(_query3, RestMethod.imChatGet, [RestMethod.imChatGet, {
	        dialog_id: this.getDialogId()
	      }]), babelHelpers.defineProperty(_query3, RestMethod.imDialogMessagesUnread, [RestMethod.imDialogMessagesGet, {
	        chat_id: this.getChatId(),
	        first_id: lastId,
	        limit: limit,
	        convert_text: 'Y'
	      }]), _query3);
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this18.template.$emit('onDialogRequestUnreadResult', {
	            error: {
	              error: 'EMPTY_RESPONSE',
	              error_description: 'Server returned an empty response.'
	            }
	          });

	          promise.reject();
	          return false;
	        }

	        var chatGetResult = response[RestMethod.imChatGet];

	        if (!chatGetResult.error()) {
	          _this18.storeDataFromRest(RestMethod.imChatGet, chatGetResult.data());
	        }

	        var dialogMessageUnread = response[RestMethod.imDialogMessagesUnread];

	        if (dialogMessageUnread.error()) {
	          _this18.template.$emit('onDialogRequestUnreadResult', {
	            error: dialogMessageUnread.error().ex
	          });
	        } else {
	          var dialogMessageUnreadResult = dialogMessageUnread.data();

	          _this18.storeDataFromRest(RestMethod.imDialogMessagesUnread, dialogMessageUnreadResult);

	          _this18.template.$emit('onDialogRequestUnreadResult', {
	            count: dialogMessageUnreadResult.messages.length
	          });
	        }

	        promise.fulfill(response);
	      }, false, false, BX.Messenger.Utils.getLogTrackingParams({
	        name: RestMethod.imDialogMessagesUnread,
	        dialog: this.getDialogData()
	      }));
	      return promise;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      if (this.messagesQueue.find(function (el) {
	        return el.id == message.id;
	      })) {
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
	  }, {
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      var _this19 = this;

	      if (messageId) {
	        this.messageReadQueue.push(parseInt(messageId));
	      }

	      if (this.offline) {
	        return false;
	      }

	      this.timer.start('readMessage', null, .1, function (id, params) {
	        _this19.messageReadQueue = _this19.messageReadQueue.filter(function (elementId) {
	          if (!_this19.messageLastReadId) {
	            _this19.messageLastReadId = elementId;
	          } else if (_this19.messageLastReadId < elementId) {
	            _this19.messageLastReadId = elementId;
	          }

	          return false;
	        });

	        if (_this19.messageLastReadId <= 0) {
	          return false;
	        }

	        _this19.store.dispatch(MessengerMessageStore.readMessages, {
	          chatId: _this19.getChatId(),
	          readId: _this19.messageLastReadId
	        }).then(function (result) {
	          _this19.store.dispatch(MessengerDialogStore.decreaseCounter, {
	            dialogId: _this19.getDialogId(),
	            count: result.count
	          });
	        });

	        _this19.timer.start('readMessageServer', null, .5, function (id, params) {
	          _this19.restClient.callMethod(RestMethod.imDialogRead, {
	            'DIALOG_ID': _this19.getDialogId(),
	            'MESSAGE_ID': _this19.messageLastReadId
	          }); // TODO catch set message to unread status

	        });
	      });
	    }
	    /* endregion 05. Templates and template interaction */

	    /* region 03-05. Messenger interaction and utils */

	  }, {
	    key: "startWriting",
	    value: function startWriting(params) {
	      var _this20 = this;

	      var dialogId = params.dialogId,
	          userId = params.userId,
	          userName = params.userName,
	          _params$action = params.action,
	          action = _params$action === void 0 ? true : _params$action;

	      if (action) {
	        this.store.dispatch(MessengerDialogStore.updateWriting, {
	          dialogId: dialogId,
	          userId: userId,
	          userName: userName,
	          action: true
	        });
	        this.timer.start('writingEnd', dialogId + '|' + userId, 35, function (id, params) {
	          var dialogId = params.dialogId,
	              userId = params.userId;

	          _this20.store.dispatch(MessengerDialogStore.updateWriting, {
	            dialogId: dialogId,
	            userId: userId,
	            action: false
	          });
	        }, {
	          dialogId: dialogId,
	          userId: userId
	        });
	      } else {
	        this.timer.stop('writingStart', dialogId + '|' + userId, true);
	        this.timer.stop('writingEnd', dialogId + '|' + userId);
	      }
	    }
	  }, {
	    key: "start",

	    /* endregion */

	    /* region 03-06. Widget interaction and utils */
	    value: function start() {
	      if (this.isSessionActive()) {
	        this.requestWidgetData();
	      }
	    }
	  }, {
	    key: "open",
	    value: function open() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (!params.openFromButton && this.buttonInstance) {
	        this.buttonInstance.wm.showById('openline_livechat');
	      }

	      if (!this.checkBrowserVersion()) {
	        this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
	      } else if (BX.Messenger.Utils.versionCompare(Vue.version, '2.1') < 0) {
	        alert(this.localize.BX_LIVECHAT_OLD_VUE);
	        console.error("LiveChatWidget.error: OLD_VUE_VERSION (".concat(this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', Vue.version), ")"));
	        return false;
	      } else if (!this.isWidgetDataRequested()) {
	        this.requestWidgetData();
	      }

	      this.attachTemplate();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (this.pageMode) {
	        return false;
	      }

	      if (this.buttonInstance) {
	        this.buttonInstance.onWidgetClose();
	      }

	      this.detachTemplate();
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(params) {// TODO show popup notification and set badge on button
	      // operatorName
	      // notificationText
	      // counter
	    }
	  }, {
	    key: "fireInitEvent",
	    value: function fireInitEvent() {
	      if (this.initEventFired) {
	        return true;
	      }

	      this.sendEvent({
	        type: SubscriptionType.configLoaded,
	        data: {}
	      });

	      if (this.store.state.widget.widgetData.reopen) {
	        this.open();
	      }

	      this.initEventFired = true;
	      return true;
	    }
	  }, {
	    key: "isReady",
	    value: function isReady() {
	      return this.ready;
	    }
	  }, {
	    key: "isInited",
	    value: function isInited() {
	      return this.inited;
	    }
	  }, {
	    key: "isUserRegistered",
	    value: function isUserRegistered() {
	      return !!this.getUserHash();
	    }
	  }, {
	    key: "isConfigDataLoaded",
	    value: function isConfigDataLoaded() {
	      return this.store.state.widget.widgetData.configId;
	    }
	  }, {
	    key: "isWidgetDataRequested",
	    value: function isWidgetDataRequested() {
	      return this.widgetDataRequested;
	    }
	  }, {
	    key: "isChatLoaded",
	    value: function isChatLoaded() {
	      return this.store.state.widget.dialogData.chatId > 0;
	    }
	  }, {
	    key: "isSessionActive",
	    value: function isSessionActive() {
	      return !this.store.state.widget.dialogData.sessionClose;
	    }
	  }, {
	    key: "getCrmTraceData",
	    value: function getCrmTraceData() {
	      var traceData = '';

	      if (!this.buttonInstance) {
	        return traceData;
	      }

	      if (typeof this.buttonInstance.getTrace !== 'function') {
	        traceData = this.buttonInstance.getTrace();
	      } else if (typeof this.buttonInstance.b24Tracker !== 'undefined' && typeof this.buttonInstance.b24Tracker.guest !== 'undefined') {
	        traceData = this.buttonInstance.b24Tracker.guest.getTrace();
	      }

	      return traceData;
	    }
	  }, {
	    key: "getCustomData",
	    value: function getCustomData() {
	      var customData = [];

	      if (this.customData.length > 0) {
	        customData = this.customData;
	      } else {
	        customData = [{
	          MESSAGE: this.localize.BX_LIVECHAT_EXTRA_SITE + ': ' + location.href
	        }];
	      }

	      return JSON.stringify(customData);
	    }
	  }, {
	    key: "isUserLoaded",
	    value: function isUserLoaded() {
	      return this.store.state.widget.userData.id > 0;
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return this.host.replace(/(http.?:\/\/)|([:.\\\/])/mg, "") + this.code;
	    }
	  }, {
	    key: "getMessageLimit",
	    value: function getMessageLimit() {
	      return this.store.state.widget.dialogData.messageLimit;
	    }
	  }, {
	    key: "isUnreadMessagesLoaded",
	    value: function isUnreadMessagesLoaded() {
	      var dialog = this.store.state[MessengerStore.dialogues].collection[this.getDialogId()];

	      if (!dialog) {
	        return true;
	      }

	      if (dialog.unreadLastId <= 0) {
	        return true;
	      }

	      var collection = this.store.state[MessengerStore.messages].collection[this.getChatId()];

	      if (!collection || collection.length <= 0) {
	        return true;
	      }

	      var lastElementId = 0;

	      for (var index = collection.length - 1; index >= 0; index--) {
	        var lastElement = collection[index];

	        if (typeof lastElement.id === "number") {
	          lastElementId = lastElement.id;
	          break;
	        }
	      }

	      return lastElementId >= dialog.unreadLastId;
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return this.host;
	    }
	  }, {
	    key: "getConfigId",
	    value: function getConfigId() {
	      return this.store.state.widget.widgetData.configId;
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.store.state.widget.dialogData.chatId;
	    }
	  }, {
	    key: "isDialogStart",
	    value: function isDialogStart() {
	      return this.store.state.widget.widgetData.dialogStart;
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return 'chat' + this.getChatId();
	    }
	  }, {
	    key: "getDialogData",
	    value: function getDialogData() {
	      var dialogId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.getDialogId();
	      return this.store.state[MessengerStore.dialogues].collection[dialogId];
	    }
	  }, {
	    key: "getDiskFolderId",
	    value: function getDiskFolderId() {
	      return this.store.state.widget.dialogData.diskFolderId;
	    }
	  }, {
	    key: "getSessionId",
	    value: function getSessionId() {
	      return this.store.state.widget.dialogData.sessionId;
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.store.state.widget.userData.hash;
	    }
	  }, {
	    key: "getUserHashCookie",
	    value: function getUserHashCookie() {
	      var userHash = '';
	      var cookie = Cookie.get(null, 'LIVECHAT_HASH');

	      if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/)) {
	        userHash = cookie;
	      } else {
	        var _cookie = Cookie.get(this.getSiteId(), 'LIVECHAT_HASH');

	        if (typeof _cookie === 'string' && _cookie.match(/^[a-f0-9]{32}$/)) {
	          userHash = _cookie;
	        }
	      }

	      return userHash;
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this.store.state.widget.userData.id;
	    }
	  }, {
	    key: "getUserData",
	    value: function getUserData() {
	      return this.store.state.widget.userData;
	    }
	  }, {
	    key: "getUserRegisterFields",
	    value: function getUserRegisterFields() {
	      return {
	        'name': this.userRegisterData.name || '',
	        'last_name': this.userRegisterData.lastName || '',
	        'avatar': this.userRegisterData.avatar || '',
	        'email': this.userRegisterData.email || '',
	        'www': this.userRegisterData.www || '',
	        'gender': this.userRegisterData.gender || '',
	        'position': this.userRegisterData.position || '',
	        'user_hash': this.userRegisterData.hash || '',
	        'consent_url': this.store.state.widget.widgetData.consentUrl ? location.href : '',
	        'trace_data': this.getCrmTraceData(),
	        'custom_data': this.getCustomData()
	      };
	    }
	  }, {
	    key: "getWidgetLocationCode",
	    value: function getWidgetLocationCode() {
	      return LocationStyle[this.store.state.widget.widgetData.location];
	    }
	  }, {
	    key: "setUserRegisterData",
	    value: function setUserRegisterData(params) {
	      var validUserFields = ['hash', 'name', 'lastName', 'avatar', 'email', 'www', 'gender', 'position'];

	      if (!BX.Messenger.Utils.types.isPlainObject(params)) {
	        console.error("%cLiveChatWidget.setUserData: params is not a object", "color: black;");
	        return false;
	      }

	      for (var field in this.userRegisterData) {
	        if (!this.userRegisterData.hasOwnProperty(field)) {
	          continue;
	        }

	        if (!params[field]) {
	          delete this.userRegisterData[field];
	        }
	      }

	      for (var _field in params) {
	        if (!params.hasOwnProperty(_field)) {
	          continue;
	        }

	        if (validUserFields.indexOf(_field) === -1) {
	          console.warn("%cLiveChatWidget.setUserData: user field is not set, because you are trying to set an unknown field (%c".concat(_field, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	          continue;
	        }

	        this.userRegisterData[_field] = params[_field];
	      }

	      if (this.userRegisterData.hash && this.getUserHash() && this.userRegisterData.hash != this.getUserHash()) {
	        this.setNewAuthToken(this.userRegisterData.hash);
	      }
	    }
	  }, {
	    key: "setNewAuthToken",
	    value: function setNewAuthToken() {
	      var authToken = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var siteId = this.getSiteId();
	      this.store.commit(WidgetStore.userData, {
	        id: 0,
	        hash: ''
	      });
	      this.store.commit(WidgetStore.widgetData, {
	        configId: 0,
	        dialogStart: false
	      });
	      this.store.commit(WidgetStore.dialogData, {
	        chatId: 0,
	        diskFolderId: 0,
	        sessionId: 0,
	        sessionClose: false,
	        userVote: false,
	        userConsent: false
	      });
	      BX.LiveChatCookie = {};
	      BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.widgetData);
	      BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.dialogData);
	      BX.Messenger.LocalStorage.remove(siteId, 0, WidgetStore.userData);
	      Cookie.set(null, 'LIVECHAT_HASH', '', {
	        expires: 365 * 86400,
	        path: '/'
	      });
	      this.restClient.setAuthId(RestAuth.guest, authToken);
	    }
	  }, {
	    key: "setCustomData",
	    value: function setCustomData(params) {
	      var result = [];

	      if (params instanceof Array) {
	        params.forEach(function (element) {
	          if (element && babelHelpers.typeof(element) === 'object') {
	            result.push(element);
	          }
	        });

	        if (result.length <= 0) {
	          console.error('LiveChatWidget.setCustomData: params is empty');
	          return false;
	        }
	      } else {
	        if (!params) {
	          return false;
	        }

	        result = [{
	          'MESSAGE': params
	        }];
	      }

	      this.customData = this.customData.concat(result);
	      return true;
	    }
	  }, {
	    key: "setError",
	    value: function setError() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var description = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      console.error("LiveChatWidget.error: ".concat(code, " (").concat(description, ")"));
	      var localizeDescription = '';

	      if (code == 'LIVECHAT_AUTH_FAILED') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="#reload" onclick="location.reload()">').replace('#LINK_END#', '</a>');
	        this.setNewAuthToken();
	      } else if (code == 'LIVECHAT_AUTH_PORTAL_USER') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="' + this.host + '">').replace('#LINK_END#', '</a>');
	      } else if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }

	      this.store.commit(WidgetStore.widgetData, {
	        error: {
	          active: true,
	          code: code,
	          description: localizeDescription
	        }
	      });
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      this.store.commit(WidgetStore.widgetData, {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {Function|Boolean} - Unsubscribe callback function or False
	     */

	  }, {
	    key: "subscribe",
	    value: function subscribe(params) {
	      if (!BX.Messenger.Utils.types.isPlainObject(params)) {
	        console.error("%cLiveChatWidget.subscribe: params is not a object", "color: black;");
	        return false;
	      }

	      if (!SubscriptionTypeCheck.includes(params.type)) {
	        console.error("%cLiveChatWidget.subscribe: subscription type is not correct (%c".concat(params.type, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (typeof params.callback !== 'function') {
	        console.error("%cLiveChatWidget.subscribe: callback is not a function (%c".concat(babelHelpers.typeof(params.callback), "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (typeof this.subscribers[params.type] === 'undefined') {
	        this.subscribers[params.type] = [];
	      }

	      this.subscribers[params.type].push(params.callback);
	      return function () {
	        this.subscribers[params.type] = this.subscribers[params.type].filter(function (element) {
	          return element !== params.callback;
	        });
	      }.bind(this);
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {boolean}
	     */

	  }, {
	    key: "sendEvent",
	    value: function sendEvent(params) {
	      params = params || {};

	      if (!params.type) {
	        return false;
	      }

	      if (babelHelpers.typeof(params.data) !== 'object' || !params.data) {
	        params.data = {};
	      }

	      if (this.subscribers[params.type] instanceof Array && this.subscribers[params.type].length > 0) {
	        this.subscribers[params.type].forEach(function (callback) {
	          return callback(params.data);
	        });
	      }

	      if (this.subscribers[SubscriptionType.every] instanceof Array && this.subscribers[SubscriptionType.every].length > 0) {
	        this.subscribers[SubscriptionType.every].forEach(function (callback) {
	          return callback({
	            type: params.type,
	            data: params.data
	          });
	        });
	      }

	      return true;
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      if (babelHelpers.typeof(phrases) !== "object") {
	        return false;
	      }

	      for (var name in phrases) {
	        if (phrases.hasOwnProperty(name)) {
	          this.localize[name] = phrases[name];
	        }
	      }

	      return true;
	    }
	  }, {
	    key: "getLocalize",
	    value: function getLocalize(name) {
	      var phrase = '';

	      if (typeof name === 'undefined') {
	        return this.localize;
	      } else if (typeof this.localize[name.toString()] === 'undefined') {
	        console.warn("LiveChatWidget.getLocalize: message with code '".concat(name.toString(), "' is undefined."));
	      } else {
	        phrase = this.localize[name];
	      }

	      return phrase;
	    }
	  }, {
	    key: "getDateFormat",
	    value: function getDateFormat() {
	      var _this21 = this;

	      if (this.dateFormat) {
	        return this.dateFormat;
	      }

	      this.dateFormat = Object.create(BX.Main.Date);

	      this.dateFormat._getMessage = function (phrase) {
	        return _this21.getLocalize(phrase);
	      };

	      return this.dateFormat;
	    }
	    /* endregion */

	  }]);
	  return LiveChatWidgetPrivate;
	}();
	/* endregion 03. Widget private interface */

	/* region 04. Cookie function */


	var Cookie = {
	  get: function get(siteId, name) {
	    var cookieName = siteId ? siteId + '_' + name : name;

	    if (navigator.cookieEnabled) {
	      var result = document.cookie.match(new RegExp("(?:^|; )" + cookieName.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + "=([^;]*)"));

	      if (result) {
	        return decodeURIComponent(result[1]);
	      }
	    }

	    if (BX.Messenger.LocalStorage.isEnabled()) {
	      var _result = BX.Messenger.LocalStorage.get(siteId, 0, name, undefined);

	      if (typeof _result !== 'undefined') {
	        return _result;
	      }
	    }

	    if (typeof BX.LiveChatCookie === 'undefined') {
	      BX.LiveChatCookie = {};
	    }

	    return BX.LiveChatCookie[cookieName];
	  },
	  set: function set(siteId, name, value, options) {
	    options = options || {};
	    var expires = options.expires;

	    if (typeof expires == "number" && expires) {
	      var currentDate = new Date();
	      currentDate.setTime(currentDate.getTime() + expires * 1000);
	      expires = options.expires = currentDate;
	    }

	    if (expires && expires.toUTCString) {
	      options.expires = expires.toUTCString();
	    }

	    value = encodeURIComponent(value);
	    var cookieName = siteId ? siteId + '_' + name : name;
	    var updatedCookie = cookieName + "=" + value;

	    for (var propertyName in options) {
	      if (!options.hasOwnProperty(propertyName)) {
	        continue;
	      }

	      updatedCookie += "; " + propertyName;
	      var propertyValue = options[propertyName];

	      if (propertyValue !== true) {
	        updatedCookie += "=" + propertyValue;
	      }
	    }

	    document.cookie = updatedCookie;

	    if (typeof BX.LiveChatCookie === 'undefined') {
	      BX.LiveChatCookie = {};
	    }

	    BX.LiveChatCookie[cookieName] = value;
	    BX.Messenger.LocalStorage.set(siteId, 0, name, value);
	    return true;
	  }
	};
	/* endregion 04. Cookie function */

	/* region 05. Rest client */

	var LiveChatRestClient =
	/*#__PURE__*/
	function () {
	  function LiveChatRestClient(params) {
	    babelHelpers.classCallCheck(this, LiveChatRestClient);
	    this.queryAuthRestore = false;
	    this.setAuthId(RestAuth.guest);
	    this.restClient = new BX.RestClient({
	      endpoint: params.endpoint,
	      queryParams: this.queryParams,
	      cors: true
	    });
	  }

	  babelHelpers.createClass(LiveChatRestClient, [{
	    key: "setAuthId",
	    value: function setAuthId(authId) {
	      var customAuthId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

	      if (babelHelpers.typeof(this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }

	      if (authId == RestAuth.guest || typeof authId === 'string' && authId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.livechat_auth_id = authId;
	      } else {
	        console.error("%LiveChatRestClient.setAuthId: auth is not correct (%c".concat(authId, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (authId == RestAuth.guest && typeof customAuthId === 'string' && customAuthId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.livechat_custom_auth_id = customAuthId;
	      }

	      return true;
	    }
	  }, {
	    key: "getAuthId",
	    value: function getAuthId() {
	      if (babelHelpers.typeof(this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }

	      return this.queryParams.livechat_auth_id || null;
	    }
	  }, {
	    key: "callMethod",
	    value: function callMethod(method, params, callback, sendCallback) {
	      var _this22 = this;

	      var logTag = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;

	      if (!logTag) {
	        logTag = BX.Messenger.Utils.getLogTrackingParams({
	          name: method
	        });
	      }

	      var promise = new BX.Promise();
	      this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	        _this22.queryAuthRestore = false;
	        promise.fulfill(result);
	      }).catch(function (result) {
	        var error = result.error();

	        if (error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	          _this22.setAuthId(error.ex.hash);

	          if (method === RestMethod.widgetUserRegister) {
	            console.warn("BX.LiveChatRestClient: ".concat(error.ex.error_description, " (").concat(error.ex.error, ")"));
	            _this22.queryAuthRestore = false;
	            promise.reject(result);
	            return false;
	          }

	          if (!_this22.queryAuthRestore) {
	            console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	            _this22.queryAuthRestore = true;

	            _this22.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	              _this22.queryAuthRestore = false;
	              promise.fulfill(result);
	            }).catch(function (result) {
	              _this22.queryAuthRestore = false;
	              promise.reject(result);
	            });

	            return false;
	          }
	        }

	        _this22.queryAuthRestore = false;
	        promise.reject(result);
	      });
	      return promise;
	    }
	  }, {
	    key: "callBatch",
	    value: function callBatch(calls, callback, bHaltOnError, sendCallback, logTag) {
	      var _this23 = this;

	      var resultCallback = function resultCallback(result) {

	        for (var method in calls) {
	          if (!calls.hasOwnProperty(method)) {
	            continue;
	          }

	          var _error = result[method].error();

	          if (_error && _error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	            _this23.setAuthId(_error.ex.hash);

	            if (method === RestMethod.widgetUserRegister) {
	              console.warn("BX.LiveChatRestClient: ".concat(_error.ex.error_description, " (").concat(_error.ex.error, ")"));
	              _this23.queryAuthRestore = false;
	              callback(result);
	              return false;
	            }

	            if (!_this23.queryAuthRestore) {
	              console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	              _this23.queryAuthRestore = true;

	              _this23.restClient.callBatch(calls, callback, bHaltOnError, sendCallback, logTag);

	              return false;
	            }
	          }
	        }

	        _this23.queryAuthRestore = false;
	        callback(result);
	        return true;
	      };

	      return this.restClient.callBatch(calls, resultCallback, bHaltOnError, sendCallback, logTag);
	    }
	  }]);
	  return LiveChatRestClient;
	}();
	/* endregion 05. Rest client */

	/* region 06. Vue Components */

	/* region 06-01. bx-livechat component */

	/**
	 * @notice Do not mutate or clone this component! It is under development.
	 */


	BX.Vue.component('bx-livechat', {
	  data: function data() {
	    return {
	      viewPortMetaSiteNode: null,
	      viewPortMetaWidgetNode: null,
	      storedMessage: '',
	      storedFile: null,
	      textareaFocused: false,
	      textareaDrag: false,
	      textareaHeight: 100,
	      textareaMinimumHeight: 100,
	      textareaMaximumHeight: BX.Messenger.Utils.device.isMobile() ? 200 : 300,
	      chat: {
	        id: 0
	      }
	    };
	  },
	  created: function created() {
	    this.onCreated();
	    document.addEventListener('keydown', this.onWindowKeyDown);
	    this.$root.$on('requestShowForm', this.onRequestShowForm);
	  },
	  beforeDestroy: function beforeDestroy() {
	    document.removeEventListener('keydown', this.onWindowKeyDown);
	    this.$root.$off('requestShowForm', this.onRequestShowForm);
	    this.onTextareaDragEventRemove();
	  },
	  computed: babelHelpers.objectSpread({
	    FormType: function FormType() {
	      return _FormType;
	    },
	    VoteType: function VoteType() {
	      return _VoteType;
	    },
	    DeviceType: function DeviceType() {
	      return _DeviceType;
	    },
	    textareaHeightStyle: function textareaHeightStyle(state) {
	      return 'flex: 0 0 ' + this.textareaHeight + 'px;';
	    },
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    },
	    widgetMobileDisabled: function widgetMobileDisabled(state) {
	      if (state.widgetData.deviceType == _DeviceType.mobile) {
	        if (navigator.userAgent.toString().includes('iPad')) ; else if (state.widgetData.deviceOrientation == DeviceOrientation.horizontal) {
	          if (navigator.userAgent.toString().includes('iPhone')) {
	            return true;
	          } else {
	            return !(babelHelpers.typeof(window.screen) === 'object' && window.screen.availHeight >= 800);
	          }
	        }
	      }

	      return false;
	    },
	    widgetClassName: function widgetClassName(state) {
	      var className = ['bx-livechat-wrapper'];
	      className.push('bx-livechat-show');

	      if (state.widgetData.pageMode) {
	        className.push('bx-livechat-page-mode');
	      } else {
	        className.push('bx-livechat-position-' + LocationStyle[state.widgetData.location]);
	      }

	      if (state.widgetData.language == LanguageType.russian) {
	        className.push('bx-livechat-logo-ru');
	      } else if (state.widgetData.language == LanguageType.ukraine) {
	        className.push('bx-livechat-logo-ua');
	      } else {
	        className.push('bx-livechat-logo-en');
	      }

	      if (!state.widgetData.online) {
	        className.push('bx-livechat-offline-state');
	      }

	      if (state.widgetData.dragged) {
	        className.push('bx-livechat-drag-n-drop');
	      }

	      if (state.widgetData.dialogStart) {
	        className.push('bx-livechat-chat-start');
	      }

	      if (state.dialogData.operator.name && !(state.widgetData.deviceType == _DeviceType.mobile && state.widgetData.deviceOrientation == DeviceOrientation.horizontal)) {
	        className.push('bx-livechat-has-operator');
	      }

	      if (BX.Messenger.Utils.device.isMobile()) {
	        className.push('bx-livechat-mobile');
	      } else if (BX.Messenger.Utils.browser.isSafari()) {
	        className.push('bx-livechat-browser-safari');
	      } else if (BX.Messenger.Utils.browser.isIe()) {
	        className.push('bx-livechat-browser-ie');
	      }

	      if (BX.Messenger.Utils.platform.isMac()) {
	        className.push('bx-livechat-mac');
	      } else {
	        className.push('bx-livechat-custom-scroll');
	      }

	      if (state.widgetData.styles.backgroundColor && BX.Messenger.Utils.isDarkColor(state.widgetData.styles.iconColor)) {
	        className.push('bx-livechat-bright-header');
	      }

	      return className.join(' ');
	    },
	    showMessageDialog: function showMessageDialog() {
	      return this.messageCollection.length > 0;
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    },
	    userData: function userData(state) {
	      return state.widget.userData;
	    },
	    dialogData: function dialogData(state) {
	      return state.widget.dialogData;
	    },
	    messageCollection: function messageCollection(state) {
	      return state[MessengerStore.messages].collection[state.widget.dialogData.chatId];
	    }
	  })),
	  watch: {
	    sessionClose: function sessionClose(value) {
	      BX.Messenger.Logger.log('sessionClose change', value);
	    }
	  },
	  methods: {
	    close: function close(event) {
	      if (this.$store.state.widget.widgetData.pageMode) {
	        return false;
	      }

	      this.onBeforeClose();
	      this.$store.commit(WidgetStore.widgetData, {
	        showed: false
	      });
	    },
	    showLikeForm: function showLikeForm() {
	      if (this.offline) {
	        return false;
	      }

	      clearTimeout(this.showFormTimeout);

	      if (!this.$store.state.widget.widgetData.vote.enable) {
	        return false;
	      }

	      if (this.$store.state.widget.dialogData.sessionClose && this.$store.state.widget.dialogData.userVote != _VoteType.none) {
	        return false;
	      }

	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.like
	      });
	    },
	    showWelcomeForm: function showWelcomeForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.welcome
	      });
	    },
	    showOfflineForm: function showOfflineForm() {
	      clearTimeout(this.showFormTimeout);

	      if (this.$store.state.widget.dialogData.showForm !== _FormType.welcome) {
	        this.$store.commit(WidgetStore.widgetData, {
	          showForm: _FormType.offline
	        });
	      }
	    },
	    showHistoryForm: function showHistoryForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.history
	      });
	    },
	    hideForm: function hideForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.none
	      });
	    },
	    showConsentWidow: function showConsentWidow() {
	      this.$store.commit(WidgetStore.widgetData, {
	        showConsent: true
	      });
	    },
	    agreeConsentWidow: function agreeConsentWidow() {
	      this.$store.commit(WidgetStore.widgetData, {
	        showConsent: false
	      });
	      this.$root.$bitrixController.sendConsentDecision(true);

	      if (this.storedMessage || this.storedFile) {
	        if (this.storedMessage) {
	          this.onTextareaSend({
	            focus: this.widgetData.deviceType != _DeviceType.mobile
	          });
	          this.storedMessage = '';
	        }

	        if (this.storedFile) {
	          this.onTextareaFileSelected();
	          this.storedFile = '';
	        }
	      } else if (this.widgetData.showForm == _FormType.none) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    disagreeConsentWidow: function disagreeConsentWidow() {
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.none
	      });
	      this.$store.commit(WidgetStore.widgetData, {
	        showConsent: false
	      });
	      this.$root.$bitrixController.sendConsentDecision(false);

	      if (this.storedMessage) {
	        this.$root.$emit('onMessengerTextareaInsertText', {
	          text: this.storedMessage,
	          focus: this.widgetData.deviceType != _DeviceType.mobile
	        });
	        this.storedMessage = '';
	      }

	      if (this.storedFile) {
	        this.storedFile = '';
	      }

	      if (this.widgetData.deviceType != _DeviceType.mobile) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    logEvent: function logEvent(name) {
	      var _BX$Messenger$Logger;

	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }

	      (_BX$Messenger$Logger = BX.Messenger.Logger).info.apply(_BX$Messenger$Logger, [name].concat(params));
	    },
	    onCreated: function onCreated() {
	      var _this24 = this;

	      if (BX.Messenger.Utils.device.isMobile()) {
	        var viewPortMetaSiteNode = Array.from(document.head.getElementsByTagName('meta')).filter(function (element) {
	          return element.name == 'viewport';
	        })[0];

	        if (viewPortMetaSiteNode) {
	          this.viewPortMetaSiteNode = viewPortMetaSiteNode;
	          document.head.removeChild(this.viewPortMetaSiteNode);
	        } else {
	          var contentWidth = document.body.offsetWidth;

	          if (contentWidth < window.innerWidth) {
	            contentWidth = window.innerWidth;
	          }

	          if (contentWidth < 1024) {
	            contentWidth = 1024;
	          }

	          this.viewPortMetaSiteNode = document.createElement('meta');
	          this.viewPortMetaSiteNode.setAttribute('name', 'viewport');
	          this.viewPortMetaSiteNode.setAttribute('content', "width=".concat(contentWidth, ", initial-scale=1.0, user-scalable=1"));
	        }

	        if (!this.viewPortMetaWidgetNode) {
	          this.viewPortMetaWidgetNode = document.createElement('meta');
	          this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
	          this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
	          document.head.appendChild(this.viewPortMetaWidgetNode);
	        }

	        document.body.classList.add('bx-livechat-mobile-state');

	        if (BX.Messenger.Utils.browser.isSafariBased()) {
	          document.body.classList.add('bx-livechat-mobile-safari-based');
	        }

	        setTimeout(function () {
	          _this24.$store.commit(WidgetStore.widgetData, {
	            showed: true
	          });
	        }, 50);
	      } else {
	        this.$store.commit(WidgetStore.widgetData, {
	          showed: true
	        });
	      }

	      this.textareaHeight = this.$store.state.widget.widgetData.textareaHeight || this.textareaHeight;
	      this.$store.commit(MessengerFileStore.initCollection, {
	        chatId: this.$root.$bitrixController.getChatId()
	      });
	      this.$store.commit(MessengerMessageStore.initCollection, {
	        chatId: this.$root.$bitrixController.getChatId()
	      });
	      this.$store.commit(MessengerDialogStore.initCollection, {
	        dialogId: this.$root.$bitrixController.getDialogId(),
	        fields: {
	          entityType: 'LIVECHAT',
	          type: 'livechat'
	        }
	      });
	    },
	    onBeforeClose: function onBeforeClose() {
	      if (BX.Messenger.Utils.device.isMobile()) {
	        document.body.classList.remove('bx-livechat-mobile-state');

	        if (BX.Messenger.Utils.browser.isSafariBased()) {
	          document.body.classList.remove('bx-livechat-mobile-safari-based');
	        }

	        if (this.viewPortMetaWidgetNode) {
	          document.head.removeChild(this.viewPortMetaWidgetNode);
	          this.viewPortMetaWidgetNode = null;
	        }

	        if (this.viewPortMetaSiteNode) {
	          document.head.appendChild(this.viewPortMetaSiteNode);
	          this.viewPortMetaSiteNode = null;
	        }
	      }
	    },
	    onAfterClose: function onAfterClose() {
	      this.$root.$bitrixController.close();
	    },
	    onRequestShowForm: function onRequestShowForm(event) {
	      var _this25 = this;

	      clearTimeout(this.showFormTimeout);

	      if (event.type == _FormType.welcome) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this25.showWelcomeForm();
	          }, 5000);
	        } else {
	          this.showWelcomeForm();
	        }
	      } else if (event.type == _FormType.offline) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this25.showOfflineForm();
	          }, 3000);
	        } else {
	          this.showOfflineForm();
	        }
	      } else if (event.type == _FormType.like) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this25.showLikeForm();
	          }, 5000);
	        } else {
	          this.showLikeForm();
	        }
	      }
	    },
	    onDialogRequestHistory: function onDialogRequestHistory(event) {
	      this.$root.$bitrixController.getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.$root.$bitrixController.getDialogUnread(event.lastId);
	    },
	    onDialogMessageClickByUserName: function onDialogMessageClickByUserName(event) {
	      // TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
	      this.$root.$emit('onMessengerTextareaInsertText', {
	        text: event.user.name + ' '
	      });
	    },
	    onDialogMessageClickByCommand: function onDialogMessageClickByCommand(event) {
	      if (event.type === 'put') {
	        this.$root.$emit('onMessengerTextareaInsertText', {
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.$root.$bitrixController.addMessage(event.value);
	      } else {
	        BX.Messenger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onDialogMessageMenuClick: function onDialogMessageMenuClick(event) {
	      BX.Messenger.Logger.warn('Message menu:', event);
	    },
	    onDialogMessageRetryClick: function onDialogMessageRetryClick(event) {
	      BX.Messenger.Logger.warn('Message retry:', event);
	      this.$root.$bitrixController.retrySendMessage(event.message);
	    },
	    onDialogReadMessage: function onDialogReadMessage(event) {
	      this.$root.$bitrixController.readMessage(event.id);
	    },
	    onDialogClick: function onDialogClick(event) {
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.none
	      });
	    },
	    onTextareaSend: function onTextareaSend(event) {
	      event.focus = event.focus !== false;

	      if (this.widgetData.showForm == _FormType.smile) {
	        this.$store.commit(WidgetStore.widgetData, {
	          showForm: _FormType.none
	        });
	      }

	      if (!this.dialogData.userConsent && this.widgetData.consentUrl) {
	        if (event.text) {
	          this.storedMessage = event.text;
	        }

	        this.showConsentWidow();
	        return false;
	      }

	      event.text = event.text ? event.text : this.storedMessage;

	      if (!event.text) {
	        return false;
	      }

	      this.hideForm();
	      this.$root.$bitrixController.addMessage(event.text);

	      if (event.focus) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }

	      return true;
	    },
	    onTextareaWrites: function onTextareaWrites(event) {
	      this.$root.$bitrixController.writesMessage();
	    },
	    onTextareaFocus: function onTextareaFocus(event) {
	      var _this26 = this;

	      if (this.widgetData.copyright && this.widgetData.deviceType == _DeviceType.mobile) {
	        this.widgetData.copyright = false;
	      }

	      if (BX.Messenger.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        this.onTextareaFocusScrollTimeout = setTimeout(function () {
	          document.addEventListener('scroll', _this26.onWindowScroll);
	        }, 1000);
	      }

	      this.textareaFocused = true;
	    },
	    onTextareaBlur: function onTextareaBlur(event) {
	      var _this27 = this;

	      if (!this.widgetData.copyright && this.widgetData.copyright !== this.$root.$bitrixController.copyright) {
	        this.widgetData.copyright = this.$root.$bitrixController.copyright;
	        this.$nextTick(function () {
	          _this27.$root.$emit('onMessengerDialogScrollToBottom', {
	            force: true
	          });
	        });
	      }

	      if (BX.Messenger.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        document.removeEventListener('scroll', this.onWindowScroll);
	      }

	      this.textareaFocused = false;
	    },
	    onTextareaStartDrag: function onTextareaStartDrag(event) {
	      if (this.textareaDrag) {
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
	    onTextareaContinueDrag: function onTextareaContinueDrag(event) {
	      if (!this.textareaDrag) {
	        return;
	      }

	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.textareaDragCursorControlPoint = event.clientY;
	      var textareaHeight = Math.max(Math.min(this.textareaDragHeightStartPoint + this.textareaDragCursorStartPoint - this.textareaDragCursorControlPoint, this.textareaMaximumHeight), this.textareaMinimumHeight);
	      BX.Messenger.Logger.log('Livechat: textarea drag', 'new: ' + textareaHeight, 'curr: ' + this.textareaHeight);

	      if (this.textareaHeight != textareaHeight) {
	        this.textareaHeight = textareaHeight;
	      }
	    },
	    onTextareaStopDrag: function onTextareaStopDrag() {
	      if (!this.textareaDrag) {
	        return;
	      }

	      BX.Messenger.Logger.log('Livechat: textarea drag ended');
	      this.textareaDrag = false;
	      this.onTextareaDragEventRemove();
	      this.$store.commit(WidgetStore.widgetData, {
	        textareaHeight: this.textareaHeight
	      });
	      this.$root.$emit('onMessengerDialogScrollToBottom', {
	        force: true
	      });
	    },
	    onTextareaDragEventAdd: function onTextareaDragEventAdd() {
	      document.addEventListener('mousemove', this.onTextareaContinueDrag);
	      document.addEventListener('touchmove', this.onTextareaContinueDrag);
	      document.addEventListener('touchend', this.onTextareaStopDrag);
	      document.addEventListener('mouseup', this.onTextareaStopDrag);
	      document.addEventListener('mouseleave', this.onTextareaStopDrag);
	    },
	    onTextareaDragEventRemove: function onTextareaDragEventRemove() {
	      document.removeEventListener('mousemove', this.onTextareaContinueDrag);
	      document.removeEventListener('touchmove', this.onTextareaContinueDrag);
	      document.removeEventListener('touchend', this.onTextareaStopDrag);
	      document.removeEventListener('mouseup', this.onTextareaStopDrag);
	      document.removeEventListener('mouseleave', this.onTextareaStopDrag);
	    },
	    onTextareaFileSelected: function onTextareaFileSelected(event) {
	      var fileInput = event && event.fileInput ? event.fileInput : this.storedFile;

	      if (!fileInput) {
	        return false;
	      }

	      if (fileInput.files[0].size > this.widgetData.disk.maxFileSize) {
	        // TODO change alert to correct overlay window
	        alert(this.localize.BX_LIVECHAT_FILE_SIZE_EXCEEDED.replace('#LIMIT#', Math.round(this.widgetData.disk.maxFileSize / 1024 / 1024)));
	        return false;
	      }

	      if (!this.dialogData.userConsent && this.widgetData.consentUrl) {
	        this.storedFile = event.fileInput;
	        this.showConsentWidow();
	        return false;
	      }

	      this.$root.$bitrixController.addFile(fileInput);
	    },
	    onTextareaAppButtonClick: function onTextareaAppButtonClick(event) {
	      if (event.appId == _FormType.smile) {
	        if (this.widgetData.showForm == _FormType.smile) {
	          this.$store.commit(WidgetStore.widgetData, {
	            showForm: _FormType.none
	          });
	        } else {
	          this.$store.commit(WidgetStore.widgetData, {
	            showForm: _FormType.smile
	          });
	        }
	      } else {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    onPullRequestConfig: function onPullRequestConfig(event) {
	      this.$root.$bitrixController.recoverPullConnection();
	    },
	    onSmilesSelectSmile: function onSmilesSelectSmile(event) {
	      this.$root.$emit('onMessengerTextareaInsertText', {
	        text: event.text
	      });
	    },
	    onSmilesSelectSet: function onSmilesSelectSet() {
	      this.$root.$emit('onMessengerTextareaFocus');
	    },
	    onWindowKeyDown: function onWindowKeyDown(event) {
	      if (event.keyCode == 27) {
	        if (this.widgetData.showForm != _FormType.none) {
	          this.$store.commit(WidgetStore.widgetData, {
	            showForm: _FormType.none
	          });
	        } else if (this.widgetData.showConsent) {
	          this.disagreeConsentWidow();
	        } else {
	          this.close();
	        }

	        event.preventDefault();
	        event.stopPropagation();
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    onWindowScroll: function onWindowScroll(event) {
	      var _this28 = this;

	      clearTimeout(this.onWindowScrollTimeout);
	      this.onWindowScrollTimeout = setTimeout(function () {
	        _this28.$root.$emit('onMessengerTextareaBlur', true);
	      }, 50);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-show\" leave-active-class=\"bx-livechat-close\" @after-leave=\"onAfterClose\">\n\t\t\t<div :class=\"widgetClassName\" v-if=\"widgetData.showed\">\n\t\t\t\t<div class=\"bx-livechat-box\">\n\t\t\t\t\t<bx-livechat-head :isWidgetDisabled=\"widgetMobileDisabled\" @like=\"showLikeForm\" @history=\"showHistoryForm\" @close=\"close\"/>\n\t\t\t\t\t<template v-if=\"widgetMobileDisabled\">\n\t\t\t\t\t\t<bx-livechat-body-orientation-disabled/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"widgetData.error.active\">\n\t\t\t\t\t\t<bx-livechat-body-error/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"!widgetData.configId\">\n\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"loading-body\">\n\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\t\t\t\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<template v-if=\"!widgetData.dialogStart\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"welcome-body\">\n\t\t\t\t\t\t\t\t<bx-livechat-body-operators/>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widgetData.showForm == FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"widgetData.dialogStart\">\n\t\t\t\t\t\t\t<bx-pull-status :canReconnect=\"true\" @reconnect=\"onPullRequestConfig\"/>\n\t\t\t\t\t\t\t<div :class=\"['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]\" key=\"with-message\">\n\t\t\t\t\t\t\t\t<transition name=\"bx-livechat-animation-upload-file\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widgetData.uploadFile\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload\">\t\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload-sending\"></div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload-text\">{{localize.BX_LIVECHAT_FILE_UPLOAD}}</div>\n\t\t\t\t\t\t\t\t\t\t</div>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</transition>\t\n\t\t\t\t\t\t\t\t<template v-if=\"showMessageDialog\">\n\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-dialog\">\n\t\t\t\t\t\t\t\t\t\t<bx-messenger-dialog\n\t\t\t\t\t\t\t\t\t\t\t:userId=\"userData.id\" \n\t\t\t\t\t\t\t\t\t\t\t:dialogId=\"dialogData.dialogId\"\n\t\t\t\t\t\t\t\t\t\t\t:chatId=\"dialogData.chatId\"\n\t\t\t\t\t\t\t\t\t\t\t:messageLimit=\"dialogData.messageLimit\"\n\t\t\t\t\t\t\t\t\t\t\t:enableEmotions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageAvatar=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventScrollToBottom=\"onMessengerDialogScrollToBottom\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventRequestHistory=\"onDialogRequestHistoryResult\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventRequestUnread=\"onDialogRequestUnreadResult\"\n\t\t\t\t\t\t\t\t\t\t\t@readMessage=\"onDialogReadMessage\"\n\t\t\t\t\t\t\t\t\t\t\t@requestHistory=\"onDialogRequestHistory\"\n\t\t\t\t\t\t\t\t\t\t\t@requestUnread=\"onDialogRequestUnread\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByCommand=\"onDialogMessageClickByCommand\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByUserName=\"onDialogMessageClickByUserName\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByMessageMenu=\"onDialogMessageMenuClick\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByMessageRetry=\"onDialogMessageRetryClick\"\n\t\t\t\t\t\t\t\t\t\t\t@click=\"onDialogClick\"\n\t\t\t\t\t\t\t\t\t\t />\n\t\t\t\t\t\t\t\t\t</div>\t \n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widgetData.showForm == FormType.like && widgetData.vote.enable\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-vote/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widgetData.showForm == FormType.welcome\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-welcome/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widgetData.showForm == FormType.offline\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-offline/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widgetData.showForm == FormType.history\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-history/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widgetData.showForm == FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-textarea\" :style=\"textareaHeightStyle\" ref=\"textarea\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-textarea-resize-handle\" @mousedown=\"onTextareaStartDrag\" @touchstart=\"onTextareaStartDrag\"></div>\n\t\t\t\t\t\t\t<bx-messenger-textarea\n\t\t\t\t\t\t\t\t:siteId=\"widgetData.siteId\"\n\t\t\t\t\t\t\t\t:userId=\"userData.id\"\n\t\t\t\t\t\t\t\t:dialogId=\"dialogData.dialogId\"\n\t\t\t\t\t\t\t\t:writesEventLetter=\"3\"\n\t\t\t\t\t\t\t\t:enableEdit=\"true\"\n\t\t\t\t\t\t\t\t:enableCommand=\"false\"\n\t\t\t\t\t\t\t\t:enableMention=\"false\"\n\t\t\t\t\t\t\t\t:enableFile=\"widgetData.disk.enabled\"\n\t\t\t\t\t\t\t\t:autoFocus=\"widgetData.deviceType !== DeviceType.mobile\"\n\t\t\t\t\t\t\t\t:isMobile=\"widgetData.deviceType === DeviceType.mobile\"\n\t\t\t\t\t\t\t\t:styles=\"{button: {backgroundColor: widgetData.styles.backgroundColor, iconColor: widgetData.styles.iconColor}}\"\n\t\t\t\t\t\t\t\tlistenEventInsertText=\"onMessengerTextareaInsertText\"\n\t\t\t\t\t\t\t\tlistenEventFocus=\"onMessengerTextareaFocus\"\n\t\t\t\t\t\t\t\tlistenEventBlur=\"onMessengerTextareaBlur\"\n\t\t\t\t\t\t\t\t@writes=\"onTextareaWrites\" \n\t\t\t\t\t\t\t\t@send=\"onTextareaSend\" \n\t\t\t\t\t\t\t\t@focus=\"onTextareaFocus\" \n\t\t\t\t\t\t\t\t@blur=\"onTextareaBlur\" \n\t\t\t\t\t\t\t\t@edit=\"logEvent('edit message', $event)\"\n\t\t\t\t\t\t\t\t@fileSelected=\"onTextareaFileSelected\"\n\t\t\t\t\t\t\t\t@appButtonClick=\"onTextareaAppButtonClick\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<bx-livechat-form-consent @agree=\"agreeConsentWidow\" @disagree=\"disagreeConsentWidow\"/>\n\t\t\t\t\t\t<template v-if=\"widgetData.copyright\">\n\t\t\t\t\t\t\t<bx-livechat-footer/>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});
	/* endregion 06-01. bx-livechat component */

	/* region 06-02. bx-livechat-head component */

	BX.Vue.component('bx-livechat-head', {
	  /**
	   * @emits 'close'
	   * @emits 'like'
	   * @emits 'history'
	   */
	  props: {
	    isWidgetDisabled: {
	      default: false
	    }
	  },
	  methods: {
	    close: function close(event) {
	      this.$emit('close');
	    },
	    like: function like(event) {
	      this.$emit('like');
	    },
	    history: function history(event) {
	      this.$emit('history');
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    VoteType: function VoteType() {
	      return _VoteType;
	    },
	    customBackgroundStyle: function customBackgroundStyle(state) {
	      return state.widgetData.styles.backgroundColor ? 'background-color: ' + state.widgetData.styles.backgroundColor + '!important;' : '';
	    },
	    customBackgroundOnlineStyle: function customBackgroundOnlineStyle(state) {
	      return state.widgetData.styles.backgroundColor ? 'border-color: ' + state.widgetData.styles.backgroundColor + '!important;' : '';
	    },
	    showName: function showName() {
	      return this.dialogData.operator.firstName || this.dialogData.operator.lastName;
	    },
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    },
	    dialogData: function dialogData(state) {
	      return state.widget.dialogData;
	    }
	  })),
	  watch: {
	    showName: function showName(value) {
	      var _this29 = this;

	      if (value) {
	        setTimeout(function () {
	          _this29.$root.$emit('onMessengerDialogScrollToBottom');
	        }, 300);
	      }
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-head-wrap\">\n\t\t\t<template v-if=\"isWidgetDisabled\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widgetData.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\t\n\t\t\t</template>\n\t\t\t<template v-else-if=\"widgetData.error.active\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widgetData.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"!widgetData.configId\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widgetData.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<template v-if=\"!showName\">\n\t\t\t\t\t\t<div class=\"bx-livechat-title\">{{widgetData.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"bx-livechat-user bx-livechat-status-online\">\n\t\t\t\t\t\t\t<template v-if=\"dialogData.operator.avatar\">\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(dialogData.operator.avatar)+')'\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"dialogData.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"dialogData.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{dialogData.operator.firstName? dialogData.operator.firstName: dialogData.operator.name}}</div>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-position\">{{dialogData.operator.workPosition? dialogData.operator.workPosition: localize.BX_LIVECHAT_USER}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<span class=\"bx-livechat-control-box-active\" v-if=\"widgetData.dialogStart && dialogData.sessionId\">\n\t\t\t\t\t\t\t<button v-if=\"widgetData.vote.enable && (!dialogData.sessionClose || dialogData.sessionClose && dialogData.userVote == VoteType.none)\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(dialogData.userVote)\" :title=\"localize.BX_LIVECHAT_VOTE_BUTTON\" @click=\"like\"></button>\n\t\t\t\t\t\t\t<button v-if=\"widgetData.vote.enable && dialogData.sessionClose && dialogData.userVote != VoteType.none\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-disabled bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(dialogData.userVote)\"></button>\n\t\t\t\t\t\t\t<button class=\"bx-livechat-control-btn bx-livechat-control-btn-mail\" :title=\"localize.BX_LIVECHAT_MAIL_BUTTON_NEW\" @click=\"history\"></button>\n\t\t\t\t\t\t</span>\t\n\t\t\t\t\t\t<button v-if=\"!widgetData.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	});
	/* endregion 06-02. bx-livechat-head component */

	/* region 06-03. bx-livechat-body-orientation-disabled component */

	BX.Vue.component('bx-livechat-body-orientation-disabled', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_MOBILE_ROTATE: this.$root.$bitrixMessages.BX_LIVECHAT_MOBILE_ROTATE
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"orientation-head\">\n\t\t\t<div class=\"bx-livechat-mobile-orientation-box\">\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-icon\"></div>\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-text\">{{localize.BX_LIVECHAT_MOBILE_ROTATE}}</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});
	/* endregion 06-03. bx-livechat-body-orientation-disabled component */

	/* region 06-04. bx-livechat-body-loading component */

	BX.Vue.component('bx-livechat-body-loading', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_LOADING: this.$root.$bitrixMessages.BX_LIVECHAT_LOADING
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-loading-window\">\n\t\t\t<svg class=\"bx-livechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-livechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-livechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-loading-msg\">{{localize.BX_LIVECHAT_LOADING}}</h3>\n\t\t</div>\n\t"
	});
	/* endregion 06-04. bx-livechat-body-loading component */

	/* region 06-05. bx-livechat-body-operators component */

	BX.Vue.component('bx-livechat-body-operators', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-help-container\">\n\t\t\t<transition name=\"bx-livechat-animation-fade\">\n\t\t\t\t<h2 v-if=\"widgetData.online\" key=\"online\" class=\"bx-livechat-help-title bx-livechat-help-title-lg\">{{localize.BX_LIVECHAT_ONLINE_LINE_1}}<div class=\"bx-livechat-help-subtitle\">{{localize.BX_LIVECHAT_ONLINE_LINE_2}}</div></h2>\n\t\t\t\t<h2 v-else key=\"offline\" class=\"bx-livechat-help-title bx-livechat-help-title-sm\">{{localize.BX_LIVECHAT_OFFLINE}}</h2>\n\t\t\t</transition>\t\n\t\t\t<div class=\"bx-livechat-help-user\">\n\t\t\t\t<template v-for=\"operator in widgetData.operators\">\n\t\t\t\t\t<div class=\"bx-livechat-user\" :key=\"operator.id\">\n\t\t\t\t\t\t<template v-if=\"operator.avatar\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(operator.avatar)+')'\"></div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\"></div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{operator.firstName? operator.firstName: operator.name}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\t\n\t\t\t</div>\n\t\t</div>\n\t"
	});
	/* endregion 06-05. bx-livechat-body-operators component */

	/* region 06-06. bx-livechat-body-error component */

	BX.Vue.component('bx-livechat-body-error', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_ERROR_TITLE: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_TITLE,
	        BX_LIVECHAT_ERROR_DESC: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_DESC
	      });
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-livechat-warning-window\">\n\t\t\t\t<div class=\"bx-livechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"widgetData.error.description\"> \n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\" v-html=\"widgetData.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg\">{{localize.BX_LIVECHAT_ERROR_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\">{{localize.BX_LIVECHAT_ERROR_DESC}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	});
	/* endregion 06-06. bx-livechat-body-error component */

	/* region 06-07. bx-livechat-smiles component */

	BX.Vue.cloneComponent('bx-livechat-smiles', 'bx-smiles', {
	  methods: {
	    hideForm: function hideForm(event) {
	      this.$parent.hideForm();
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-smiles-box\">\n\t\t\t\t\t#PARENT_TEMPLATE#\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});
	/* endregion 06-07. bx-livechat-smiles component */

	/* region 06-08. bx-livechat-form-welcome component */

	BX.Vue.component('bx-livechat-form-welcome', {
	  data: function data() {
	    return {
	      fieldName: '',
	      fieldEmail: '',
	      fieldPhone: '',
	      isFullForm: BX.Messenger.Utils.platform.isMobile()
	    };
	  },
	  watch: {
	    fieldName: function fieldName() {
	      clearTimeout(this.showFormTimeout);
	      this.showFormTimeout = setTimeout(this.showFullForm, 1000);
	      clearTimeout(this.fieldNameTimeout);
	      this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
	    },
	    fieldEmail: function fieldEmail(value) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    },
	    fieldPhone: function fieldPhone(value) {
	      clearTimeout(this.fieldPhoneTimeout);
	      this.fieldPhoneTimeout = setTimeout(this.checkPhoneField, 300);
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    userData: function userData(state) {
	      return state.widget.userData;
	    }
	  })),
	  created: function created() {
	    this.fieldName = '' + this.userData.name;
	    this.fieldEmail = '' + this.userData.email;
	    this.fieldPhone = '' + this.userData.phone;
	  },
	  methods: {
	    formShowed: function formShowed() {
	      if (!BX.Messenger.Utils.platform.isMobile()) {
	        this.$refs.nameInput.focus();
	      }
	    },
	    showFullForm: function showFullForm() {
	      clearTimeout(this.showFormTimeout);
	      this.isFullForm = true;
	    },
	    sendForm: function sendForm() {
	      var name = this.fieldName;
	      var email = this.checkEmailField() ? this.fieldEmail : '';
	      var phone = this.checkPhoneField() ? this.fieldPhone : '';

	      if (name || email || phone) {
	        this.$root.$bitrixController.sendForm(_FormType.welcome, {
	          name: name,
	          email: email,
	          phone: phone
	        });
	      }

	      this.hideForm();
	    },
	    hideForm: function hideForm(event) {
	      clearTimeout(this.showFormTimeout);
	      clearTimeout(this.fieldNameTimeout);
	      clearTimeout(this.fieldEmailTimeout);
	      clearTimeout(this.fieldPhoneTimeout);
	      this.$parent.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      if (event.target === this.$refs.nameInput) {
	        this.showFullForm();
	        this.$refs.emailInput.focus();
	      } else if (event.target === this.$refs.emailInput) {
	        this.$refs.phoneInput.focus();
	      } else {
	        this.sendForm();
	      }

	      event.preventDefault();
	    },
	    checkNameField: function checkNameField() {
	      if (this.fieldName.length > 0) {
	        if (this.$refs.name) {
	          this.$refs.name.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.nameInput) {
	          if (this.$refs.name) {
	            this.$refs.name.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    },
	    checkEmailField: function checkEmailField() {
	      if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/)) {
	        if (this.$refs.email) {
	          this.$refs.email.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.emailInput) {
	          if (this.$refs.email) {
	            this.$refs.email.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    },
	    checkPhoneField: function checkPhoneField() {
	      if (this.fieldPhone.match(/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/)) {
	        if (this.$refs.phone) {
	          this.$refs.phone.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.phoneInput) {
	          if (this.$refs.phone) {
	            this.$refs.phone.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_ABOUT_TITLE}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg\" ref=\"name\">\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_NAME\" v-model=\"fieldName\" ref=\"nameInput\" @blur=\"checkNameField\" @keydown.enter=\"onFieldEnterPress\"  @keydown.tab=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div :class=\"['bx-livechat-form-short', {\n\t\t\t\t\t\t'bx-livechat-form-full': isFullForm,\n\t\t\t\t\t}]\">\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"phone\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_PHONE\" v-model=\"fieldPhone\" ref=\"phoneInput\" @blur=\"checkPhoneField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});
	/* endregion 06-08. bx-livechat-form-welcome component */

	BX.Vue.cloneComponent('bx-livechat-form-offline', 'bx-livechat-form-welcome', {
	  methods: {
	    formShowed: function formShowed() {
	      if (!BX.Messenger.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var name = this.fieldName;
	      var email = this.checkEmailField() ? this.fieldEmail : '';
	      var phone = this.checkPhoneField() ? this.fieldPhone : '';

	      if (name || email || phone) {
	        this.$root.$bitrixController.sendForm(_FormType.offline, {
	          name: name,
	          email: email,
	          phone: phone
	        });
	      }

	      this.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      if (event.target === this.$refs.emailInput) {
	        this.showFullForm();
	        this.$refs.phoneInput.focus();
	      } else if (event.target === this.$refs.phoneInput) {
	        this.$refs.nameInput.focus();
	      } else {
	        this.sendForm();
	      }

	      event.preventDefault();
	    }
	  },
	  watch: {
	    fieldName: function fieldName() {
	      clearTimeout(this.fieldNameTimeout);
	      this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
	    },
	    fieldEmail: function fieldEmail() {
	      clearTimeout(this.showFormTimeout);
	      this.showFormTimeout = setTimeout(this.showFullForm, 1000);
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_OFFLINE_TITLE}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div :class=\"['bx-livechat-form-short', {\n\t\t\t\t\t\t'bx-livechat-form-full': isFullForm,\n\t\t\t\t\t}]\">\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"phone\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_PHONE\" v-model=\"fieldPhone\" ref=\"phoneInput\" @blur=\"checkPhoneField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg\" ref=\"name\">\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_NAME\" v-model=\"fieldName\" ref=\"nameInput\" @blur=\"checkNameField\" @keydown.enter=\"onFieldEnterPress\"  @keydown.tab=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});
	/* region 06-09. bx-livechat-form-history component */

	BX.Vue.component('bx-livechat-form-history', {
	  data: function data() {
	    return {
	      fieldEmail: ''
	    };
	  },
	  watch: {
	    fieldEmail: function fieldEmail(value) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    userData: function userData(state) {
	      return state.widget.userData;
	    }
	  })),
	  created: function created() {
	    this.fieldEmail = '' + this.userData.email;
	  },
	  methods: {
	    formShowed: function formShowed() {
	      if (!BX.Messenger.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var email = this.checkEmailField() ? this.fieldEmail : '';

	      if (email) {
	        this.$root.$bitrixController.sendForm(_FormType.history, {
	          email: email
	        });
	      }

	      this.hideForm();
	    },
	    hideForm: function hideForm(event) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.$parent.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      this.sendForm();
	      event.preventDefault();
	    },
	    checkEmailField: function checkEmailField() {
	      if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/)) {
	        if (this.$refs.email) {
	          this.$refs.email.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.emailInput) {
	          if (this.$refs.email) {
	            this.$refs.email.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_MAIL_TITLE_NEW}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_MAIL_BUTTON_NEW}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});
	/* endregion 06-09. bx-livechat-form component */

	/* region 06-10. bx-livechat-form-vote component */

	BX.Vue.component('bx-livechat-form-vote', {
	  computed: babelHelpers.objectSpread({
	    VoteType: function VoteType() {
	      return _VoteType;
	    },
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    }
	  })),
	  methods: {
	    userVote: function userVote(vote) {
	      this.$store.commit(WidgetStore.widgetData, {
	        showForm: _FormType.none
	      });
	      this.$store.commit(WidgetStore.dialogData, {
	        userVote: vote
	      });
	      this.$root.$bitrixController.sendDialogVote(vote);
	    },
	    hideForm: function hideForm(event) {
	      this.$parent.hideForm();
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-rate-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-rate-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-mdl\">{{widgetData.vote.messageText}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-like\" @click=\"userVote(VoteType.like)\" :title=\"widgetData.vote.messageLike\"></button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-dislike\" @click=\"userVote(VoteType.dislike)\" :title=\"widgetData.vote.messageDislike\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\t\n\t"
	});
	/* endregion 06-10. bx-livechat-form-vote component */

	/* region 06-11. bx-livechat-form-consent component */

	BX.Vue.component('bx-livechat-form-consent', {
	  /**
	   * @emits 'agree' {event: object} -- 'event' - click event
	   * @emits 'disagree' {event: object} -- 'event' - click event
	   */
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    }
	  })),
	  methods: {
	    agree: function agree(event) {
	      this.$emit('agree', {
	        event: event
	      });
	    },
	    disagree: function disagree(event) {
	      this.$emit('disagree', {
	        event: event
	      });
	    },
	    onShow: function onShow(element, done) {
	      element.classList.add('bx-livechat-consent-window-show');
	      done();
	    },
	    onHide: function onHide(element, done) {
	      element.classList.remove('bx-livechat-consent-window-show');
	      element.classList.add('bx-livechat-consent-window-close');
	      setTimeout(function () {
	        done();
	      }, 400);
	    },
	    onKeyDown: function onKeyDown(event) {
	      if (event.keyCode == 9) {
	        if (event.target === this.$refs.iframe) {
	          if (event.shiftKey) {
	            this.$refs.cancel.focus();
	          } else {
	            this.$refs.success.focus();
	          }
	        } else if (event.target === this.$refs.success) {
	          if (event.shiftKey) {
	            this.$refs.iframe.focus();
	          } else {
	            this.$refs.cancel.focus();
	          }
	        } else if (event.target === this.$refs.cancel) {
	          if (event.shiftKey) {
	            this.$refs.success.focus();
	          } else {
	            this.$refs.iframe.focus();
	          }
	        }

	        event.preventDefault();
	      } else if (event.keyCode == 39 || event.keyCode == 37) {
	        if (event.target.nextElementSibling) {
	          event.target.nextElementSibling.focus();
	        } else if (event.target.previousElementSibling) {
	          event.target.previousElementSibling.focus();
	        }

	        event.preventDefault();
	      }
	    }
	  },
	  directives: {
	    focus: {
	      inserted: function inserted(element, params) {
	        element.focus();
	      }
	    }
	  },
	  template: "\n\t\t<transition @enter=\"onShow\" @leave=\"onHide\">\n\t\t\t<template v-if=\"widgetData.showConsent && widgetData.consentUrl\">\n\t\t\t\t<div class=\"bx-livechat-consent-window\">\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-title\">{{localize.BX_LIVECHAT_CONSENT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-content\">\n\t\t\t\t\t\t<iframe class=\"bx-livechat-consent-window-content-iframe\" ref=\"iframe\" frameborder=\"0\" marginheight=\"0\"  marginwidth=\"0\" allowtransparency=\"allow-same-origin\" seamless=\"true\" :src=\"widgetData.consentUrl\" @keydown=\"onKeyDown\"></iframe>\n\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" ref=\"success\" @click=\"agree\" @keydown=\"onKeyDown\" v-focus>{{localize.BX_LIVECHAT_CONSENT_AGREE}}</button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-cancel\" ref=\"cancel\" @click=\"disagree\" @keydown=\"onKeyDown\">{{localize.BX_LIVECHAT_CONSENT_DISAGREE}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</transition>\n\t"
	});
	/* endregion 06-11. bx-livechat-form-consent component */

	/* region 06-12. bx-livechat-footer component */

	BX.Vue.component('bx-livechat-footer', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return BX.Vue.getFilteredPhrases('BX_LIVECHAT_COPYRIGHT_', this.$root.$bitrixMessages);
	    }
	  }, BX.Vuex.mapState({
	    widgetData: function widgetData(state) {
	      return state.widget.widgetData;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-copyright\">\t\n\t\t\t<template v-if=\"widgetData.copyrightUrl\">\n\t\t\t\t<a :href=\"widgetData.copyrightUrl\" target=\"_blank\">\n\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t</a>\n\t\t\t</template>\n\t\t\t<template v-else>\n\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t</template>\n\t\t</div>\n\t"
	});
	/* endregion 06-12. bx-livechat-footer component */

	/* endregion 06. Vue Components */

	/* region 07. Initialize */

	if (!window.BX) {
	  window.BX = {};
	}

	if (!window.BX.LiveChatWidget) {
	  BX.LiveChatWidget = LiveChatWidget;
	  BX.LiveChatWidget.VoteType = _VoteType;
	  BX.LiveChatWidget.SubscriptionType = SubscriptionType;
	  BX.LiveChatWidget.LocationStyle = LocationStyle;
	  BX.LiveChatWidget.WidgetStore = WidgetStore;
	}

	window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {
	  detail: {}
	}));
	/* endregion 07. Initialize */

}((this.window = this.window || {})));
//# sourceMappingURL=imopenlines.component.widget.bundle.js.map
